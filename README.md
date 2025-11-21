# business-card-scanner-notion (Laravel Web アプリ)

名刺画像から連絡先を抽出し、Notion に登録する Laravel ベースの Web アプリです。ブラウザでアクセスすると、ログイン・パスキー認証・画像アップロード・Notion 登録までを行える UI が表示されます。内部的には Laravel のルーティング/ミドルウェア/サービスコンテナ/セッション管理に従った API を呼び出しています。

## セットアップ

1. `.env.example` をコピーして `.env` を作成し、必要なキーを設定します。
2. 依存関係をインストールします（ネットワークに接続できる環境で実行してください）。
   ```bash
   composer install
   ```
3. アプリケーションキーを生成します。
   ```bash
   php artisan key:generate
   ```
4. ローカルサーバーを起動します。
   ```bash
   php artisan serve
   ```

5. ブラウザで `http://localhost:8000/` にアクセスすると、ログインから Notion 登録までを操作できる Web 画面が表示されます。

## 使い方 (Web)

1. ブラウザでトップページを開き、ユーザー名/パスワードでログインするか、既に登録済みのパスキーでログインします。
2. ログイン後に「名刺画像から抽出」で 1〜2 枚の画像をアップロードし、抽出結果を確認します。
3. 抽出結果を修正したい場合は JSON を編集し、「Notion ページ作成」を実行します。
4. Notion の設定確認だけを行いたい場合は「Notion 接続確認」をクリックしてください。

同一ブラウザ内でセッション Cookie を共有しており、すべての操作を UI から完結できます。API を直接呼び出す場合は以下のエンドポイントを利用できます。

## エンドポイント (補足)

- `POST /api/login` … JSON `{ "username": "...", "password": "..." }` でログイン。
- `POST /api/passkey/register` … JSON `{ "passkey": "..." }` をセッションに登録。
- `POST /api/passkey/login` … JSON `{ "passkey": "..." }` でパスキー認証。
- `POST /api/extract` … `images[]` (1〜2枚) の multipart 画像から連絡先を抽出。
- `POST /api/notion/verify` … Notion データソースへの疎通確認。
- `POST /api/notion/create` … JSON `{ "contact": { ... }, "attachments": ["data:<mime>;base64,..."] }` で Notion ページを作成。

`/api/extract` 以降のエンドポイントはセッションベースの認証が必要です。`/api/login` または `/api/passkey/login` でセッションを確立してください。

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

- ユーザー名/パスワードでログイン後に、`/api/passkey/register` で任意の文字列を登録できます。
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

- `会社名` は **リッチテキスト** プロパティとして送信されます。
- `メールアドレス` は **メール** プロパティとして送信されます。

## 備考

- 旧来のプレーン PHP 実装を Laravel の Web アプリとして組み直しました。トップページの UI から全機能を操作できます。
- API を直接利用する場合は `http://localhost:8000/api/...` に対してリクエストを送信してください。
