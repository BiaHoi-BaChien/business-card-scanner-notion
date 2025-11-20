import base64
import json
import os
import hashlib
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
        "auth_secret": os.getenv("AUTH_SECRET"),
        "auth_username_enc": os.getenv("AUTH_USERNAME_ENC"),
        "auth_password_enc": os.getenv("AUTH_PASSWORD_ENC"),
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


def derive_key(secret: str) -> bytes:
    return hashlib.sha256(secret.encode("utf-8")).digest()


def xor_bytes(data: bytes, key: bytes) -> bytes:
    return bytes(b ^ key[i % len(key)] for i, b in enumerate(data))


def decrypt_value(token: Optional[str], secret: Optional[str]) -> Optional[str]:
    """Decrypt a base64-encoded token using a SHA-256 derived XOR key."""

    if not token or not secret:
        return None

    try:
        cipher = base64.urlsafe_b64decode(token.encode("utf-8"))
        plain = xor_bytes(cipher, derive_key(secret))
        return plain.decode("utf-8")
    except Exception:
        return None


def hash_passkey(passkey: str, secret: str) -> str:
    return hashlib.sha256((passkey + secret).encode("utf-8")).hexdigest()


def verify_password_login(
    username: str, password: str, settings: Dict[str, Optional[str]]
) -> bool:
    expected_username = decrypt_value(
        settings.get("auth_username_enc"), settings.get("auth_secret")
    )
    expected_password = decrypt_value(
        settings.get("auth_password_enc"), settings.get("auth_secret")
    )

    return bool(
        expected_username
        and expected_password
        and username == expected_username
        and password == expected_password
    )


def verify_passkey(passkey: str, settings: Dict[str, Optional[str]]) -> bool:
    secret = settings.get("auth_secret")
    stored_hash = st.session_state.get("registered_passkey_hash")

    if not passkey or not secret or not stored_hash:
        return False

    return stored_hash == hash_passkey(passkey, secret)


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
    """Base64-encode an image for OpenAI image inputs without resizing."""
    return base64.b64encode(uploaded_file.getvalue()).decode("utf-8")


def uploaded_file_to_data_url(uploaded_file: UploadedFile) -> str:
    """Convert an uploaded file to a data URL that Notion can store as an external file."""

    mime_type = uploaded_file.type or "application/octet-stream"
    encoded = base64.b64encode(uploaded_file.getvalue()).decode("utf-8")
    return f"data:{mime_type};base64,{encoded}"


def build_image_parts(files: List[UploadedFile]) -> List[dict]:
    """Convert uploaded files into OpenAI message image parts."""
    image_parts: List[dict] = []
    for file in files:
        image_parts.append(
            {
                "type": "image_url",
                "image_url": {
                    "url": uploaded_file_to_data_url(file),
                },
            }
        )
    return image_parts


def extract_contact_data(client: OpenAI, files: List[UploadedFile]) -> Dict[str, Optional[str]]:
    """Use OpenAI to extract contact data from one or two business card images."""
    system_prompt = (
        "You are an assistant that extracts structured contact details from business cards. "
        "Always return a single JSON object with these keys: name, company, website, email, "
        "phone_number_1, phone_number_2, industry. If a value is missing, use an empty string. "
        "Multiple images may contain different business cards—merge every clue across all images into one consolidated contact. "
        "If multiple phone numbers are found, keep at most two unique ones. Prefer the most complete/modern-looking email, URL, and company name when variations exist. "
        "Infer the industry from the company name when not explicitly shown. Summarize the industry in Japanese within roughly 100 characters, avoiding overly terse labels. "
        "Use Japanese for all returned values, including the industry. When the card shows a name in Japanese, keep it as-is; "
        "if both Japanese and English names appear, choose the Japanese name. "
        "Do not translate or rewrite names or company names—copy them exactly as printed on the card, including spacing and punctuation."
    )

    user_message = [
        {
            "type": "text",
            "text": (
                "Extract and merge the contact information from all provided business card "
                "images into one consolidated record. Do not create multiple records."
            ),
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
        "parent": {"database_id": data_source_id},
        "properties": build_notion_properties(data, property_names),
    }

    return requests.post(url, headers=headers, json=payload, timeout=30)


def show_settings_warning(settings: Dict[str, Optional[str]]):
    missing_keys = [k for k, v in settings.items() if not v]
    if missing_keys:
        st.warning(
            "環境変数に API 設定がありません: " + ", ".join(missing_keys)
        )


def ensure_session_defaults():
    st.session_state.setdefault("authenticated", False)
    st.session_state.setdefault("login_method", "")
    st.session_state.setdefault("registered_passkey_hash", None)
    st.session_state.setdefault("uploader_key", 0)
    st.session_state.setdefault("contact_data", None)
    st.session_state.setdefault("confirm_notion", False)


def render_authentication(settings: Dict[str, Optional[str]]) -> bool:
    ensure_session_defaults()

    st.subheader("ログイン")
    missing_auth = [
        key
        for key in ("auth_secret", "auth_username_enc", "auth_password_enc")
        if not settings.get(key)
    ]

    if st.session_state["authenticated"]:
        st.success(
            f"{st.session_state['login_method']} ログイン済みです。アプリを利用できます。"
        )
        if st.button("ログアウト", use_container_width=True):
            st.session_state["authenticated"] = False
            st.session_state["login_method"] = ""
        else:
            return True

    if missing_auth:
        st.error(
            "ログイン設定が不足しています。AUTH_SECRET, AUTH_USERNAME_ENC, "
            "AUTH_PASSWORD_ENC を環境変数に設定してください。"
        )

    cols = st.columns(2)
    with cols[0]:
        with st.form("password_login"):
            username_input = st.text_input("ユーザー名", value="")
            password_input = st.text_input("パスワード", type="password", value="")
            submitted = st.form_submit_button("ユーザー名とパスワードでログイン", use_container_width=True)

        if submitted:
            if verify_password_login(username_input, password_input, settings):
                st.session_state["authenticated"] = True
                st.session_state["login_method"] = "パスワード"
                st.rerun()
            else:
                st.error("ユーザー名またはパスワードが正しくありません。")

    with cols[1]:
        with st.form("passkey_login"):
            passkey_input = st.text_input("パスキー", type="password")
            submitted = st.form_submit_button("パスキーでログイン", use_container_width=True)

        if submitted:
            if verify_passkey(passkey_input, settings):
                st.session_state["authenticated"] = True
                st.session_state["login_method"] = "パスキー"
                st.rerun()
            else:
                st.error("パスキーが認証できませんでした。事前に登録してください。")

    return st.session_state["authenticated"]


def render_passkey_registration(settings: Dict[str, Optional[str]]):
    if not st.session_state.get("authenticated"):
        return

    if st.session_state.get("login_method") != "パスワード":
        st.info("パスキーの登録や更新はユーザー名/パスワードでログイン後に行えます。")
        return

    if not settings.get("auth_secret"):
        st.warning("パスキー登録には AUTH_SECRET の設定が必要です。")
        return

    with st.expander("パスキーを登録/更新する", expanded=False):
        with st.form("register_passkey"):
            new_passkey = st.text_input(
                "新しいパスキー", type="password", help="再ログイン時に入力する任意の文字列です。"
            )
            submitted = st.form_submit_button("パスキーを保存", use_container_width=True)

        if submitted:
            if not new_passkey:
                st.error("パスキーを入力してください。")
            else:
                st.session_state["registered_passkey_hash"] = hash_passkey(
                    new_passkey, settings["auth_secret"]
                )
                st.success("パスキーを登録しました。次回以降はパスキーでログインできます。")

        if st.session_state.get("registered_passkey_hash"):
            st.caption("現在パスキーが登録されています。")


def render_app_body(
    settings: Dict[str, Optional[str]], property_names: Dict[str, str]
):
    uploader_key = st.session_state.get("uploader_key", 0)

    uploaded_files = st.file_uploader(
        "名刺画像をアップロード (最大2枚)",
        type=["png", "jpg", "jpeg"],
        accept_multiple_files=True,
        key=f"uploader_{uploader_key}",
    )

    if uploaded_files and len(uploaded_files) > 2:
        st.error("アップロードできるのは最大2枚までです。")
        uploaded_files = uploaded_files[:2]

    action_cols = st.columns(2)
    with action_cols[0]:
        analyze_clicked = st.button("AIで解析", use_container_width=True)
    with action_cols[1]:
        if st.button("クリア", type="secondary", use_container_width=True):
            st.session_state["contact_data"] = None
            st.session_state["confirm_notion"] = False
            st.session_state["uploader_key"] = uploader_key + 1
            st.rerun()

    if analyze_clicked:
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

            st.info("写真は Notion には保存されず、抽出した文字情報のみ登録します。")

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


def main():
    st.set_page_config(page_title="Business Card Scanner", page_icon="🪪")

    st.title("名刺スキャナ (Notion 連携デモ)")
    st.caption("スマホで撮影した名刺を AI で解析し、Notion に登録します。")

    settings = load_settings()
    property_names = load_property_config()

    show_settings_warning(settings)

    if not render_authentication(settings):
        return

    render_passkey_registration(settings)
    render_app_body(settings, property_names)


if __name__ == "__main__":
    main()
