# business-card-scanner-notion

スマホでの名刺撮影 → AI による情報抽出 → Notion データベースへの連絡先登録ワークフローのサンプル実装

## セットアップ

1. `.env.example` をコピーして `.env` を作成し、必要なキーを設定します。
   ```env
   OPENAI_API_KEY=sk-...
   NOTION_API_KEY=secret_...
   NOTION_DATA_SOURCE_ID=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
   NOTION_VERSION=2025-09-03
   AUTH_SECRET=demo-shared-secret
   AUTH_USERNAME_ENC=Nfk-0FSls44iEbI=
   AUTH_PASSWORD_ENC=FK84gUiB38lyApHY
   ```
2. 依存関係をインストールします。
   ```bash
   pip install -r requirements.txt
   ```
3. Streamlit アプリを起動します。
   ```bash
   streamlit run app.py
   ```

## 使い方

- アプリにログインします（ユーザー名/パスワード または 事前登録したパスキー）。
- アプリの UI から名刺画像（表・裏の最大 2 枚）をアップロードします。
- OpenAI API が連絡先を抽出し、Notion API を通じて指定のデータソースに登録します。
- 抽出結果と Notion への登録状況が画面に表示されます。

## 認証の設定

- `.env` に格納するユーザー名とパスワードは、`AUTH_SECRET` を使った XOR 方式で暗号化した値を `AUTH_USERNAME_ENC` と `AUTH_PASSWORD_ENC` に設定します。
- `.env.example` にはサンプルとして以下が入っています。
  - 平文ユーザー名: `sample_user`
  - 平文パスワード: `R7k!pA32#vQm`
  - 秘密鍵: `demo-shared-secret`
- 独自の値に置き換える場合は以下のスニペットで暗号化できます。

```python
import base64, hashlib

def derive_key(secret: str) -> bytes:
    return hashlib.sha256(secret.encode("utf-8")).digest()

def xor_bytes(data: bytes, key: bytes) -> bytes:
    return bytes(b ^ key[i % len(key)] for i, b in enumerate(data))

def encrypt_value(value: str, secret: str) -> str:
    key = derive_key(secret)
    cipher = xor_bytes(value.encode('utf-8'), key)
    return base64.urlsafe_b64encode(cipher).decode('utf-8')

secret = "your-secret-here"
print("AUTH_USERNAME_ENC=", encrypt_value("your-username", secret))
print("AUTH_PASSWORD_ENC=", encrypt_value("your-password", secret))
```

### パスキー運用

- ユーザー名/パスワードでログイン後に、画面内の「パスキーを登録/更新する」で任意の文字列を登録できます。
- 以後は登録済みのパスキーだけでログインすることも可能です。

## プロパティ名のカスタマイズ

アプリは `property_config.json` に定義されたプロパティ名を使って Notion に登録します。ファイルが無い場合やキーが欠けている場合は以下のデフォルト値を使用します。

```json
{
  "name": "名前",
  "company": "会社名",
  "website": "会社HP",
  "email": "メールアドレス",
  "phone_number_1": "電話番号1",
  "phone_number_2": "電話番号2",
  "industry": "業種"
}
```

- `会社名` は **セレクト** プロパティとして送信され、選択肢に存在しない値は API がデータベースに追加します。
- `メールアドレス` は **リッチテキスト** プロパティとして送信されます。
