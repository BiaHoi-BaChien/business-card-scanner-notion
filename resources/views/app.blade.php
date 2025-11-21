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
        .hidden { display: none; }
        .status-box { background: #eef2ff; color: #1e293b; border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px; margin: 12px 0; }
        .pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; background: #e2e8f0; border-radius: 9999px; font-size: 13px; }
        .pill small { color: #475569; font-weight: 600; }
        .stack { display: grid; gap: 12px; }
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
            <li>ログイン画面では「ユーザー名」「パスワード」入力または「パスキーでログイン」を実行します。</li>
            <li>ログイン後に「パスキー登録 / 更新」→「名刺画像のアップロード & API 解析」を順に進めます。</li>
            <li>解析が成功すると Notion への登録ボタンが有効化されます。内容を確認して送信してください。</li>
            <li>Notion の接続確認だけをしたい場合は、ログイン後に「Notion 接続確認」を利用できます。</li>
        </ul>
        <p class="muted">※ 全てのリクエストは同一オリジンで送信され、セッション Cookie を介して認証されます。</p>
    </section>

    <section id="login-section">
        <h2>ログイン</h2>
        <div class="status-box" id="auth-notice">セッションを開始するためにログインしてください。</div>
        <div class="stack">
            <form id="login-form">
                <label for="login-username">ユーザー名</label>
                <input id="login-username" type="text" name="username" placeholder="sample_user" required>
                <label for="login-password">パスワード</label>
                <input id="login-password" type="password" name="password" placeholder="R7k!pA32#vQm" required>
                <button type="submit">ログイン</button>
            </form>
            <form id="passkey-login-form" class="row">
                <div>
                    <label for="passkey-login">パスキーでログイン</label>
                    <input id="passkey-login" type="text" name="passkey" placeholder="登録済みパスキー" required>
                </div>
                <div style="align-self: end;">
                    <button type="submit">パスキーでログイン</button>
                </div>
            </form>
            <p class="muted">アカウントに紐づくパスキーを登録済みの場合はこちらからログインできます。</p>
        </div>
    </section>

    <section id="post-login-section" class="hidden">
        <h2>ログイン後の操作</h2>
        <div class="pill" id="passkey-state"><small>Passkey</small><span>未登録</span></div>
        <div class="stack">
            <div>
                <h3>パスキーの登録 / 更新</h3>
                <form id="passkey-register-form" class="row">
                    <div>
                        <label for="passkey-register">登録するパスキー</label>
                        <input id="passkey-register" type="text" name="passkey" placeholder="例: my-device-passkey" required>
                    </div>
                    <div style="align-self: end;">
                        <button type="submit">パスキー登録</button>
                    </div>
                </form>
                <p class="muted">ユーザー名/パスワードでログインした後にパスキーを登録すると、以後はパスキーだけでログインできます。</p>
            </div>

            <div>
                <h3>名刺画像から抽出</h3>
                <p id="extraction-status" class="muted">1〜2 枚の名刺画像をアップロードして解析を実行してください。</p>
                <form id="extract-form">
                    <label for="extract-images">1〜2 枚の画像ファイルを選択</label>
                    <input id="extract-images" type="file" name="images" accept="image/*" multiple required>
                    <button type="submit">API による解析を実行</button>
                </form>
            </div>

            <div id="notion-section">
                <h3>Notion 連携</h3>
                <div class="row">
                    <button id="notion-verify" type="button">Notion 接続確認</button>
                    <div style="flex: 1"></div>
                </div>
                <div class="status-box" id="notion-ready">解析が成功すると Notion への登録ボタンが有効になります。</div>
                <form id="notion-create-form">
                    <label for="contact-json">contact JSON</label>
                    <textarea id="contact-json" required placeholder="解析結果がここに表示されます"></textarea>
                    <label for="attachments">添付ファイル (data URL) を 1 行ずつ</label>
                    <textarea id="attachments" placeholder="data:image/png;base64,..."></textarea>
                    <button id="notion-submit" type="submit" disabled>Notion ページ作成</button>
                </form>
            </div>
        </div>
    </section>

    <section id="response-section" class="hidden">
        <h2>レスポンス</h2>
        <p class="muted">各操作のレスポンスやエラーをここに表示します。</p>
        <pre id="response-view">まだレスポンスはありません。</pre>
    </section>
</main>
<script>
    const responseView = document.getElementById('response-view');
    const authNotice = document.getElementById('auth-notice');
    const loginSection = document.getElementById('login-section');
    const postLoginSection = document.getElementById('post-login-section');
    const extractionStatus = document.getElementById('extraction-status');
    const notionReady = document.getElementById('notion-ready');
    const notionSubmit = document.getElementById('notion-submit');
    const contactJsonInput = document.getElementById('contact-json');
    const passkeyState = document.getElementById('passkey-state');
    const appState = {
        authenticated: false,
        contact: null,
        hasPasskey: false,
    };
    const responseSection = document.getElementById('response-section');

    function showResponse(data) {
        responseView.textContent = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
    }

    function updateUi() {
        loginSection.classList.toggle('hidden', appState.authenticated);
        postLoginSection.classList.toggle('hidden', !appState.authenticated);
        responseSection.classList.toggle('hidden', !appState.authenticated);
        authNotice.textContent = appState.authenticated
            ? 'ログイン済みです。パスキー登録や名刺解析を続行できます。'
            : 'セッションを開始するためにログインしてください。';

        extractionStatus.textContent = appState.contact
            ? '解析結果を確認し、Notion 登録に進めます。'
            : '1〜2 枚の名刺画像をアップロードして解析を実行してください。';

        if (appState.contact) {
            contactJsonInput.value = JSON.stringify(appState.contact, null, 2);
            notionReady.textContent = '解析済みデータを Notion に登録できます。内容を確認してください。';
        } else {
            contactJsonInput.value = '';
            notionReady.textContent = '解析が成功すると Notion への登録ボタンが有効になります。';
        }

        notionSubmit.disabled = !appState.contact;
        passkeyState.querySelector('span').textContent = appState.hasPasskey ? '登録済み' : '未登録';
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

    async function refreshAuthState() {
        try {
            const res = await fetch('/api/auth/status', { credentials: 'include' });
            const json = await res.json();
            appState.authenticated = Boolean(json.authenticated);
            appState.hasPasskey = Boolean(json.has_registered_passkey);
            updateUi();
        } catch (err) {
            console.error('Failed to fetch auth status', err);
        }
    }

    document.getElementById('login-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const username = document.getElementById('login-username').value;
        const password = document.getElementById('login-password').value;
        try {
            const data = await postJson('/api/login', { username, password });
            appState.authenticated = true;
            appState.contact = null;
            showResponse(data);
            await refreshAuthState();
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
            appState.hasPasskey = true;
            updateUi();
        } catch (err) {
            showResponse(err);
        }
    });

    document.getElementById('passkey-login-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const passkey = document.getElementById('passkey-login').value;
        try {
            const data = await postJson('/api/passkey/login', { passkey });
            appState.authenticated = true;
            appState.contact = null;
            showResponse(data);
            await refreshAuthState();
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
            appState.contact = json.contact || null;
            showResponse(json);
            updateUi();
        } catch (err) {
            showResponse(err);
        }
    });

    document.getElementById('notion-verify').addEventListener('click', async () => {
        try {
            const data = await postJson('/api/notion/verify', {});
            showResponse(data);
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

    refreshAuthState();
</script>
</body>
</html>
