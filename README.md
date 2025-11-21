# business-card-scanner-notion (PHP API)

名刺画像から連絡先を抽出し、Notion に登録するための PHP API 実装です。Python 版は撤去し、PHP 版のみをルートに配置しています。Laravel への移行を前提とした構成にしているため、環境構築時は Composer を利用してください。

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
2. 依存関係をインストールします（ネットワークに接続できる環境で実行してください）。
   ```bash
   composer install
   ```
3. PHP のビルトインサーバーで起動します。
   ```bash
   php -S localhost:8000 -t public
   ```

## エンドポイント

- `POST /login` … JSON `{ "username": "...", "password": "..." }` でログイン。
- `POST /passkey/register` … JSON `{ "passkey": "..." }` をセッションに登録。
- `POST /passkey/login` … JSON `{ "passkey": "..." }` でパスキー認証。
- `POST /extract` … `images[]` (1〜2枚) の multipart 画像から連絡先を抽出。
- `POST /notion/verify` … Notion データソースへの疎通確認。
- `POST /notion/create` … JSON `{ "contact": { ... }, "attachments": ["data:<mime>;base64,..."] }` で Notion ページを作成。

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

- ユーザー名/パスワードでログイン後に、`/passkey/register` で任意の文字列を登録できます。
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

## 備考

- PHP コードはルート直下に配置しました。`php` ディレクトリは不要です。
- Laravel 環境が利用できるネットワークであれば、そのまま依存関係を導入してフレームワーク配下に組み込めます。
