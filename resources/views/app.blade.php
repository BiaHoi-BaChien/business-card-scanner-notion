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
        .accordion {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            overflow: hidden;
            background: #f8fafc;
        }
        .accordion summary {
            cursor: pointer;
            padding: 12px 14px;
            font-weight: 700;
            list-style: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            user-select: none;
        }
        .accordion summary::-webkit-details-marker {
            display: none;
        }
        .accordion summary::after {
            content: '＋';
            font-weight: 900;
            color: #475569;
        }
        .accordion[open] summary::after {
            content: '－';
        }
        .accordion .accordion-body {
            padding: 0 14px 14px;
            border-top: 1px solid #e2e8f0;
            background: #fff;
        }
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
    <h1>
        Business Card Scanner for Notion
        <small class="muted" id="build-version" data-build-version="{{ $buildVersion ?? 'dev' }}">
            v{{ $buildVersion ?? 'dev' }}
        </small>
    </h1>
</header>
<main>
    <section id="login-section">
        <h2>ログイン</h2>
        <p id="auth-notice" class="muted">セッションを開始するためにログインしてください。</p>
        <div class="stack">
            <form id="login-form">
                <label for="login-username">ユーザー名</label>
                <input id="login-username" type="text" name="username" placeholder="ユーザー名" required>
                <label for="login-password">パスワード</label>
                <input id="login-password" type="password" name="password" placeholder="パスワード" required>
                <button type="submit">ログイン</button>
            </form>
            <form id="passkey-login-form" class="row">
                <div>
                    <label for="passkey-login">パスキーでログイン</label>
                    <input id="passkey-login" type="password" name="passkey" placeholder="登録済みパスキー" required>
                </div>
                <div style="align-self: end;">
                    <button type="submit">パスキーでログイン</button>
                </div>
            </form>
            <p class="muted">アカウントに紐づくパスキーを登録済みの場合はこちらからログインできます。</p>
        </div>
    </section>

    <section id="post-login-section" class="hidden">
        <div class="pill" id="passkey-state"><small>Passkey</small><span>未登録</span></div>
        <div class="stack">
            <details class="accordion" id="passkey-accordion">
                <summary aria-controls="passkey-accordion-body" aria-expanded="false">パスキーの登録 / 更新</summary>
                <div class="accordion-body" id="passkey-accordion-body">
                    <form id="passkey-register-form" class="row">
                        <div>
                            <label for="passkey-register">登録するパスキー</label>
                            <input id="passkey-register" type="password" name="passkey" placeholder="例: my-device-passkey" required>
                        </div>
                        <div style="align-self: end;">
                            <button type="submit">パスキー登録</button>
                        </div>
                    </form>
                    <p class="muted">ユーザー名/パスワードでログインした後にパスキーを登録すると、以後はパスキーだけでログインできます。</p>
                </div>
            </details>

            <div>
                <h3>名刺画像から抽出</h3>
                <p id="extraction-status" class="muted">1〜2 枚の名刺画像をアップロードして解析を実行してください。</p>
                <form id="extract-form">
                    <label for="extract-images">1〜2 枚の画像ファイルを選択</label>
                    <input id="extract-images" type="file" name="images" accept="image/*" multiple required>
                    <button type="submit">API による解析を実行</button>
                </form>
            </div>

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
            <p id="notion-ready" class="muted">解析が成功すると Notion への登録ボタンが有効になります。</p>
            <button id="notion-submit" type="submit" disabled>Notion ページ作成</button>
        </form>
    </section>

</main>
<script>
    const loginSection = document.getElementById('login-section');
    const postLoginSection = document.getElementById('post-login-section');
    const authNotice = document.getElementById('auth-notice');
    const extractionStatus = document.getElementById('extraction-status');
    const notionReady = document.getElementById('notion-ready');
    const notionSubmit = document.getElementById('notion-submit');
    const passkeyState = document.getElementById('passkey-state');
    const passkeyAccordion = document.getElementById('passkey-accordion');
    const passkeyAccordionSummary = passkeyAccordion?.querySelector('summary');
    const buildVersionEl = document.getElementById('build-version');
    const appState = {
        authenticated: false,
        contact: null,
        hasPasskey: false,
    };

    function renderBuildVersion(version) {
        if (!buildVersionEl) return;
        buildVersionEl.textContent = `v${version}`;
    }

    async function fetchBuildVersion() {
        try {
            const res = await fetch('/api/version');
            const json = await res.json();
            const version = json.build_version || json.version;
            if (version) {
                renderBuildVersion(version);
            }
        } catch (err) {
            console.error('Failed to fetch build version', err);
        }
    }

    if (buildVersionEl?.dataset.buildVersion) {
        renderBuildVersion(buildVersionEl.dataset.buildVersion);
    }

    fetchBuildVersion();

    function showResponse(data) {
        console.log('Response:', data);
    }

    function updateUi() {
        loginSection.classList.toggle('hidden', appState.authenticated);
        postLoginSection.classList.toggle('hidden', !appState.authenticated);
        responseSection.classList.toggle('hidden', !appState.authenticated);
        if (authNotice) {
            authNotice.textContent = appState.authenticated
                ? 'ログイン済みです。パスキー登録や名刺解析を続行できます。'
                : 'セッションを開始するためにログインしてください。';
        }

        extractionStatus.textContent = appState.contact
            ? '解析結果を確認し、Notion 登録に進めます。'
            : '1〜2 枚の名刺画像をアップロードして解析を実行してください。';

        notionReady.textContent = appState.contact
            ? '解析済みデータを Notion に登録できます。内容を確認してください。'
            : '解析が成功すると Notion への登録ボタンが有効になります。';

        notionSubmit.disabled = !appState.contact;
        passkeyState.querySelector('span').textContent = appState.hasPasskey ? '登録済み' : '未登録';
    }

    passkeyAccordion?.addEventListener('toggle', () => {
        if (!passkeyAccordionSummary) return;
        passkeyAccordionSummary.setAttribute('aria-expanded', passkeyAccordion.open ? 'true' : 'false');
    });

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

    logoutButton.addEventListener('click', async () => {
        try {
            const data = await postJson('/api/logout', {});
            appState.authenticated = false;
            appState.contact = null;
            appState.hasPasskey = false;
            showResponse(data);
            updateUi();
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

    document.getElementById('notion-create-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            if (!appState.contact) {
                showResponse({ error: '解析結果がありません。名刺画像をアップロードしてください。' });
                return;
            }
            const data = await postJson('/api/notion/create', { contact: appState.contact, attachments: [] });
            showResponse(data);
        } catch (err) {
            showResponse(err);
        }
    });

    refreshAuthState();
</script>
</body>
</html>
