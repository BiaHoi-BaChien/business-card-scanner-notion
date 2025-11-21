# business-card-scanner-notion (Laravel Web アプリ)

名刺画像から連絡先を抽出し、Notion に登録する Laravel ベースの Web アプリです。ブラウザ UI だけでログイン（パスキー対応）から画像アップロード、Notion 登録まで完結します。内部では Laravel のルーティング/ミドルウェア/サービスコンテナ/セッション管理に従った API を呼び出しています。

## セットアップ

1. `.env.example` をコピーして `.env` を作成し、必要なキーを設定します。
   - 即座に動作確認できるよう、`.env.example` には有効な `APP_KEY` をあらかじめ含めています。セキュリティ上は `php artisan key:generate` で鍵を再生成してください。
2. 依存関係をインストールします（ネットワークに接続できる環境で実行してください）。
   ```bash
   composer install
   ```
3. アプリケーションキーを生成します。
   ```bash
   php artisan key:generate
   ```
   - `There are no commands defined in the "key" namespace.` が表示される場合は、`vendor/` ディレクトリが存在せず Laravel コマンドが読み込まれていません。先に `composer install` を実行し、依存関係をダウンロードしてから再度お試しください。
4. ローカルサーバーを起動します（デフォルトは `http://127.0.0.1:8000/`。`--host` や `--port` で任意指定も可能）。
   ```bash
   php artisan serve
   # 例: php artisan serve --host=0.0.0.0 --port=8000
   ```

5. ブラウザで `http://localhost:8000/` にアクセスすると、ログインから Notion 登録までを操作できる Web 画面が表示されます。

## 使い方 (Web)

1. ブラウザでトップページを開き、ユーザー名/パスワードでログインするか、既に登録済みのパスキーでログインします。
2. ログイン後に「名刺画像から情報抽出」で 1〜2 枚の画像をアップロードし、抽出結果を確認します。
3. 抽出結果を修正したい場合は JSON を編集し、「Notionへ登録」を実行します。

セッション Cookie を共有するため、ログイン後の操作は UI だけで完結します。API を直接呼び出す場合は以下のエンドポイントを利用できます。

## エンドポイント (補足)

- `POST /api/login` … JSON `{ "username": "...", "password": "..." }` でログイン。
- `POST /api/passkey/register` … JSON `{ "passkey": "..." }` をセッションに登録。
- `POST /api/passkey/login` … JSON `{ "passkey": "..." }` でパスキー認証。
- `POST /api/extract` … `images[]` (1〜2枚) の multipart 画像から連絡先を抽出。
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

アプリは `property_config.json` に定義されたプロパティ名・型を使って Notion に登録します。ファイルが無い場合やキーが欠けている場合は以下のデフォルト値を使用します。

```json
{
  "name": {
    "name": "名前",
    "type": "title"
  },
  "company": {
    "name": "会社名",
    "type": "rich_text"
  },
  "website": {
    "name": "会社HP",
    "type": "url"
  },
  "email": {
    "name": "メールアドレス",
    "type": "email"
  },
  "phone_number_1": {
    "name": "電話番号1",
    "type": "phone_number"
  },
  "phone_number_2": {
    "name": "電話番号2",
    "type": "phone_number"
  },
  "industry": {
    "name": "業種",
    "type": "select"
  }
}
```

- `type` は `title`, `rich_text`, `url`, `email`, `phone_number`, `select` のいずれかを指定できます。
- 既存の設定ファイルが文字列のみを持つ場合は、名前だけ上書きして型は上記のデフォルトを使います。
- Notion 側で `会社名` がセレクト型、`会社HP` がリッチテキストなど、デフォルトと異なる型の場合は `type` を変更して合わせてください。

## 備考

- 旧来のプレーン PHP 実装を Laravel の Web アプリとして組み直しました。トップページの UI から全機能を操作できます。
- API を直接利用する場合は `http://localhost:8000/api/...` に対してリクエストを送信してください。
