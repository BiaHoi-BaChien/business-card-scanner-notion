import base64
import json
import os
from pathlib import Path
from typing import Dict, List, Optional

import requests
import httpx
import streamlit as st
from dotenv import load_dotenv
from openai import OpenAI
from streamlit.runtime.uploaded_file_manager import UploadedFile

load_dotenv()


def load_settings() -> Dict[str, Optional[str]]:
    """Load required API settings from environment variables."""
    return {
        "openai_api_key": os.getenv("OPENAI_API_KEY"),
        "notion_api_key": os.getenv("NOTION_API_KEY"),
        "notion_data_source_id": os.getenv("NOTION_DATA_SOURCE_ID"),
        "notion_version": os.getenv("NOTION_VERSION", "2025-09-03"),
    }


def load_property_config(config_path: str = "property_config.json") -> Dict[str, str]:
    """Load configurable Notion property names from a JSON file.

    The file is expected to contain string values for these keys:
    name, company, website, email, phone_number_1, phone_number_2, industry.
    Missing keys fall back to sensible defaults.
    """

    defaults = {
        "name": "名前",
        "company": "会社名",
        "website": "会社HP",
        "email": "メールアドレス",
        "phone_number_1": "電話番号1",
        "phone_number_2": "電話番号2",
        "industry": "業種",
    }

    path = Path(config_path)
    if not path.is_file():
        return defaults

    try:
        overrides = json.loads(path.read_text(encoding="utf-8"))
    except Exception:
        return defaults

    merged = {**defaults}
    merged.update({k: v for k, v in overrides.items() if isinstance(v, str) and v})
    return merged


def create_openai_client(api_key: str) -> OpenAI:
    """Create an OpenAI client while honoring proxy settings if provided.

    Some environments set HTTP(S) proxy variables. The official OpenAI
    constructor does not accept a ``proxies`` keyword argument, so we build a
    compatible httpx client when a proxy is configured.
    """

    proxy = (
        os.getenv("HTTP_PROXY")
        or os.getenv("HTTPS_PROXY")
        or os.getenv("ALL_PROXY")
        or os.getenv("http_proxy")
        or os.getenv("https_proxy")
    )

    http_client = httpx.Client(proxies=proxy) if proxy else None
    return OpenAI(api_key=api_key, http_client=http_client)


def encode_image(uploaded_file: UploadedFile) -> str:
    """Base64-encode an uploaded image for OpenAI image inputs."""
    return base64.b64encode(uploaded_file.getvalue()).decode("utf-8")


def build_image_parts(files: List[UploadedFile]) -> List[dict]:
    """Convert uploaded files into OpenAI message image parts."""
    image_parts: List[dict] = []
    for file in files:
        mime_type = file.type or "image/png"
        encoded = encode_image(file)
        image_parts.append(
            {
                "type": "image_url",
                "image_url": {
                    "url": f"data:{mime_type};base64,{encoded}",
                },
            }
        )
    return image_parts


def extract_contact_data(client: OpenAI, files: List[UploadedFile]) -> Dict[str, Optional[str]]:
    """Use OpenAI to extract contact data from one or two business card images."""
    system_prompt = (
        "You are an assistant that extracts structured contact details from business cards. "
        "Return a single JSON object with these keys: name, company, website, email, "
        "phone_number_1, phone_number_2, industry. If a value is missing, use an empty string. "
        "Infer the industry from the company name when not explicitly shown. "
        "Use Japanese for all returned values, including the industry. When the card shows a name in Japanese, keep it as-is; "
        "if both Japanese and English names appear, choose the Japanese name."
    )

    user_message = [
        {
            "type": "text",
            "text": "Extract the contact information from the provided business card images.",
        },
        *build_image_parts(files),
    ]

    response = client.chat.completions.create(
        model="gpt-4o-mini",
        temperature=0,
        messages=[
            {"role": "system", "content": system_prompt},
            {"role": "user", "content": user_message},
        ],
        response_format={"type": "json_object"},
    )

    content = response.choices[0].message.content or "{}"
    return json.loads(content)


def build_notion_properties(
    data: Dict[str, Optional[str]], property_names: Dict[str, str]
) -> Dict[str, dict]:
    """Build Notion property payload, skipping empty values."""
    properties: Dict[str, dict] = {}

    def add_property(name: str, value: Optional[str], builder):
        if value:
            properties[name] = builder(value)

    add_property(
        property_names["name"],
        data.get("name"),
        lambda v: {"title": [{"text": {"content": v}}]},
    )
    add_property(
        property_names["company"],
        data.get("company"),
        lambda v: {"select": {"name": v}},
    )
    add_property(
        property_names["website"], data.get("website"), lambda v: {"url": v}
    )
    add_property(
        property_names["email"],
        data.get("email"),
        lambda v: {"rich_text": [{"text": {"content": v}}]},
    )
    add_property(
        property_names["phone_number_1"],
        data.get("phone_number_1"),
        lambda v: {"phone_number": v},
    )
    add_property(
        property_names["phone_number_2"],
        data.get("phone_number_2"),
        lambda v: {"phone_number": v},
    )
    add_property(
        property_names["industry"],
        data.get("industry"),
        lambda v: {"rich_text": [{"text": {"content": v}}]},
    )

    return properties


def ensure_select_option(
    notion_api_key: str,
    notion_version: str,
    data_source_id: str,
    property_name: str,
    option_name: str,
):
    """Add a select option to the database if it does not already exist."""

    url = f"https://api.notion.com/v1/databases/{data_source_id}"
    headers = {
        "Authorization": f"Bearer {notion_api_key}",
        "Notion-Version": notion_version,
        "Content-Type": "application/json",
    }

    response = requests.get(url, headers=headers, timeout=30)
    if response.status_code != 200:
        return

    properties = response.json().get("properties", {})
    select_property = properties.get(property_name)
    if not select_property or select_property.get("type") != "select":
        return

    options = select_property.get("select", {}).get("options", [])
    if any(option.get("name") == option_name for option in options):
        return

    updated_options = options + [{"name": option_name, "color": "default"}]
    requests.patch(
        url,
        headers=headers,
        json={
            "properties": {
                property_name: {"select": {"options": updated_options}},
            }
        },
        timeout=30,
    )


def save_to_notion(
    notion_api_key: str,
    data_source_id: str,
    notion_version: str,
    data: Dict[str, Optional[str]],
    property_names: Dict[str, str],
) -> requests.Response:
    """Create a new page in Notion with the extracted contact data."""
    url = "https://api.notion.com/v1/pages"
    headers = {
        "Authorization": f"Bearer {notion_api_key}",
        "Notion-Version": notion_version,
        "Content-Type": "application/json",
    }

    company_value = data.get("company")
    if company_value:
        ensure_select_option(
            notion_api_key,
            notion_version,
            data_source_id,
            property_names["company"],
            company_value,
        )

    payload = {
        "parent": {"data_source_id": data_source_id},
        "properties": build_notion_properties(data, property_names),
    }

    return requests.post(url, headers=headers, json=payload, timeout=30)


def show_settings_warning(settings: Dict[str, Optional[str]]):
    missing_keys = [k for k, v in settings.items() if not v]
    if missing_keys:
        st.warning(
            "環境変数に API 設定がありません: " + ", ".join(missing_keys)
        )


def main():
    st.set_page_config(page_title="名刺スキャナ (OpenAI → Notion)", page_icon="🪪")
    st.title("名刺スキャナ (OpenAI → Notion)")
    st.write(
        "名刺の表裏をアップロードすると、OpenAI が情報を抽出し、Notion のデータソースに登録します。"
    )

    settings = load_settings()
    property_names = load_property_config()
    show_settings_warning(settings)

    uploaded_files = st.file_uploader(
        "名刺画像をアップロード (最大2枚)",
        type=["png", "jpg", "jpeg"],
        accept_multiple_files=True,
    )

    if uploaded_files and len(uploaded_files) > 2:
        st.error("アップロードできるのは最大2枚までです。")
        uploaded_files = uploaded_files[:2]

    if st.button("AIで解析"):
        if not uploaded_files:
            st.error("少なくとも1枚の名刺画像をアップロードしてください。")
            return

        missing = [k for k, v in settings.items() if not v]
        if missing:
            st.error("必要な設定が不足しています。環境変数を確認してください。")
            return

        try:
            with st.spinner("OpenAI で解析中..."):
                client = create_openai_client(settings["openai_api_key"])
                contact_data = extract_contact_data(client, uploaded_files)

            st.session_state["contact_data"] = contact_data
            st.session_state["confirm_notion"] = False
            st.success("OpenAI での抽出が完了しました。内容を確認してください。")
        except Exception as exc:  # pragma: no cover - handled in UI
            st.error(f"エラーが発生しました: {exc}")

    contact_data = st.session_state.get("contact_data")
    if contact_data:
        st.subheader("抽出結果")
        st.json(contact_data)

        confirm = st.checkbox(
            "この内容でNotionに登録してもよい", key="confirm_notion"
        )
        if st.button("Notionに登録", disabled=not confirm):
            missing = [k for k, v in settings.items() if not v]
            if missing:
                st.error("必要な設定が不足しています。環境変数を確認してください。")
                return

            with st.spinner("Notion に送信中..."):
                response = save_to_notion(
                    settings["notion_api_key"],
                    settings["notion_data_source_id"],
                    settings["notion_version"],
                    contact_data,
                    property_names,
                )

            if response.status_code in {200, 201}:
                notion_url = response.json().get("url", "")
                st.success("Notion への登録が完了しました。")
                if notion_url:
                    st.markdown(f"[作成されたページを開く]({notion_url})")
            else:
                st.error(
                    "Notion API への送信に失敗しました。"
                    f" (status: {response.status_code})"
                )
                try:
                    st.code(response.json(), language="json")
                except Exception:
                    st.text(response.text)


if __name__ == "__main__":
    main()
