<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Card Scanner for Notion</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; margin: 0; background: #f7f7f7; color: #222; }
        header { background: #1e293b; color: #fff; padding: 24px; }
        main { max-width: 960px; margin: 0 auto; padding: 24px; }
        section { background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 16px; box-shadow: 0 2px 6px rgba(0,0,0,0.04); }
        h1 { margin: 0 0 8px; font-size: 24px; }
        h2 { margin-top: 0; font-size: 18px; }
        p { margin: 4px 0 12px; line-height: 1.6; }
        label { display: block; margin-bottom: 6px; font-weight: 600; }
        input[type="text"], input[type="password"], textarea { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; box-sizing: border-box; }
        textarea { min-height: 140px; font-family: ui-monospace, SFMono-Regular, SFMono, Consolas, "Liberation Mono", Menlo, monospace; }
        .row { display: flex; gap: 12px; }
        .row > div { flex: 1; }
        button { background: #2563eb; color: #fff; border: none; border-radius: 10px; padding: 10px 14px; cursor: pointer; font-weight: 700; }
        button:hover { background: #1d4ed8; }
        .muted { color: #475569; font-size: 14px; }
        pre { background: #0f172a; color: #e2e8f0; padding: 12px; border-radius: 8px; overflow-x: auto; font-size: 14px; }
        .danger { color: #b91c1c; }
        ul { padding-left: 20px; margin-top: 4px; }
    </style>
</head>
<body>
<header>
    <h1>Business Card Scanner for Notion</h1>
    <p class="muted">Laravel ベースの Web アプリ。ブラウザからログインし、名刺画像のアップロード・Notion 登録までを完結できます。</p>
</header>
<main>
    <section>
        <h2>使い方</h2>
        <ul>
            <li>まず「ログイン」または「パスキーでログイン」を実行し、セッションを確立します。</li>
            <li>ログイン後、「名刺画像から抽出」で 1〜2 枚の画像をアップロードして連絡先を抽出します。</li>
            <li>抽出結果を確認し、必要に応じて編集してから「Notion ページ作成」で保存します。</li>
        </ul>
        <p class="muted">※ 全てのリクエストは同一オリジンで送信され、セッション Cookie を介して認証されます。</p>
    </section>

    <section>
        <h2>ログイン</h2>
        <form id="login-form">
            <label for="login-username">ユーザー名</label>
            <input id="login-username" type="text" name="username" placeholder="sample_user" required>
            <label for="login-password">パスワード</label>
            <input id="login-password" type="password" name="password" placeholder="R7k!pA32#vQm" required>
            <button type="submit">ログイン</button>
        </form>
    </section>

    <section>
        <h2>パスキーの登録 / ログイン</h2>
        <form id="passkey-register-form" class="row">
            <div>
                <label for="passkey-register">登録するパスキー</label>
                <input id="passkey-register" type="text" name="passkey" placeholder="例: my-device-passkey" required>
            </div>
            <div style="align-self: end;">
                <button type="submit">パスキー登録</button>
            </div>
        </form>
        <form id="passkey-login-form" class="row">
            <div>
                <label for="passkey-login">ログイン用パスキー</label>
                <input id="passkey-login" type="text" name="passkey" placeholder="登録済みパスキー" required>
            </div>
            <div style="align-self: end;">
                <button type="submit">パスキーでログイン</button>
            </div>
        </form>
        <p class="muted">ユーザー名/パスワードでログイン後にパスキーを登録すると、以後はパスキーだけでログインできます。</p>
    </section>

    <section>
        <h2>名刺画像から抽出</h2>
        <form id="extract-form">
            <label for="extract-images">1〜2 枚の画像ファイルを選択</label>
            <input id="extract-images" type="file" name="images" accept="image/*" multiple required>
            <button type="submit">抽出を実行</button>
        </form>
    </section>

    <section>
        <h2>Notion 連携</h2>
        <form id="notion-create-form">
            <label for="contact-json">contact JSON</label>
            <textarea id="contact-json" required>{
  "name": "山田 太郎",
  "company": "Example 株式会社",
  "website": "https://example.com",
  "email": "taro@example.com",
  "phone_number_1": "+81-3-1234-5678",
  "phone_number_2": "",
  "industry": "IT"
}</textarea>
            <label for="attachments">添付ファイル (data URL) を 1 行ずつ</label>
            <textarea id="attachments" placeholder="data:image/png;base64,..."></textarea>
            <button type="submit">Notion ページ作成</button>
        </form>
    </section>

    <section>
        <h2>レスポンス</h2>
        <p class="muted">各操作のレスポンスやエラーをここに表示します。</p>
        <pre id="response-view">まだレスポンスはありません。</pre>
    </section>
</main>
<script>
    const responseView = document.getElementById('response-view');

    function showResponse(data) {
        responseView.textContent = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
    }

    async function postJson(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(body)
        });
        const json = await res.json().catch(() => ({ message: 'レスポンスの解析に失敗しました', status: res.status }));
        if (!res.ok) {
            throw json;
        }
        return json;
    }

    document.getElementById('login-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const username = document.getElementById('login-username').value;
        const password = document.getElementById('login-password').value;
        try {
            const data = await postJson('/api/login', { username, password });
            showResponse(data);
        } catch (err) {
            showResponse(err);
        }
    });

    document.getElementById('passkey-register-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const passkey = document.getElementById('passkey-register').value;
        try {
            const data = await postJson('/api/passkey/register', { passkey });
            showResponse(data);
        } catch (err) {
            showResponse(err);
        }
    });

    document.getElementById('passkey-login-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const passkey = document.getElementById('passkey-login').value;
        try {
            const data = await postJson('/api/passkey/login', { passkey });
            showResponse(data);
        } catch (err) {
            showResponse(err);
        }
    });

    document.getElementById('extract-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const files = document.getElementById('extract-images').files;
        if (!files.length) {
            showResponse({ error: '画像ファイルを選択してください' });
            return;
        }
        const formData = new FormData();
        Array.from(files).slice(0, 2).forEach((file, idx) => formData.append('images[]', file, file.name || `image-${idx + 1}`));
        try {
            const res = await fetch('/api/extract', { method: 'POST', body: formData, credentials: 'include' });
            const json = await res.json();
            if (!res.ok) throw json;
            showResponse(json);
        } catch (err) {
            showResponse(err);
        }
    });

    document.getElementById('notion-create-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const contact = JSON.parse(document.getElementById('contact-json').value || '{}');
            const attachmentsRaw = document.getElementById('attachments').value.trim();
            const attachments = attachmentsRaw ? attachmentsRaw.split(/\n+/).filter(Boolean) : [];
            const data = await postJson('/api/notion/create', { contact, attachments });
            showResponse(data);
        } catch (err) {
            showResponse(err);
        }
    });
</script>
</body>
</html>
