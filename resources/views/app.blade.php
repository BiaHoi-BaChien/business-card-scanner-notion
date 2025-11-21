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
        .drop-zone { margin-top: 12px; padding: 14px; border: 2px dashed #94a3b8; border-radius: 12px; background: #f8fafc; color: #475569; text-align: center; transition: background 0.2s, border-color 0.2s, color 0.2s; }
        .drop-zone.dragover { background: #e0f2fe; border-color: #0ea5e9; color: #0f172a; }
        .drop-zone small { display: block; margin-top: 6px; color: #64748b; }
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
        <h2>ログイン後の操作</h2>
        <div class="pill" id="passkey-state"><small>Passkey</small><span>未登録</span></div>
        <div class="stack">
            <div>
                <h3>パスキーの登録 / 更新</h3>
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

            <div>
                <h3>名刺画像から抽出</h3>
                <p id="extraction-status" class="muted">1〜2 枚の名刺画像をアップロードして解析を実行してください。</p>
                <form id="extract-form">
                    <label for="extract-images">1〜2 枚の画像ファイルを選択</label>
                    <input id="extract-images" type="file" name="images" accept="image/*" multiple required>
                    <button type="submit">API による解析を実行</button>
                </form>
                <div id="drop-zone" class="drop-zone">
                    ここに画像ファイルをドラッグ＆ドロップ
                    <small>画像のみ対応・最大 2 枚まで</small>
                </div>
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

    <section id="response-section" class="hidden">
        <h2>レスポンス</h2>
        <p class="muted">各操作のレスポンスやエラーをここに表示します。</p>
        <pre id="response-view">まだレスポンスはありません。</pre>
    </section>
</main>
<script>
    const responseView = document.getElementById('response-view');
    const loginSection = document.getElementById('login-section');
    const postLoginSection = document.getElementById('post-login-section');
    const extractionStatus = document.getElementById('extraction-status');
    const notionReady = document.getElementById('notion-ready');
    const notionSubmit = document.getElementById('notion-submit');
    const contactJsonInput = document.getElementById('contact-json');
    const passkeyState = document.getElementById('passkey-state');
    const buildVersionEl = document.getElementById('build-version');
    const appState = {
        authenticated: false,
        contact: null,
        hasPasskey: false,
    };
    const responseSection = document.getElementById('response-section');

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
        responseView.textContent = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
        responseSection.classList.remove('hidden');
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

    const extractForm = document.getElementById('extract-form');
    const extractImagesInput = document.getElementById('extract-images');
    const dropZone = document.getElementById('drop-zone');

    function buildExtractionFormData(files) {
        const formData = new FormData();
        Array.from(files).slice(0, 2).forEach((file, idx) => {
            formData.append('images[]', file, file.name || `image-${idx + 1}`);
        });
        return formData;
    }

    async function submitExtraction(files) {
        const selectedFiles = files && files.length ? files : extractImagesInput.files;
        if (!selectedFiles.length) {
            showResponse({ error: '画像ファイルを選択してください' });
            return;
        }
        try {
            const res = await fetch('/api/extract', { method: 'POST', body: buildExtractionFormData(selectedFiles), credentials: 'include' });
            const json = await res.json();
            if (!res.ok) throw json;
            appState.contact = json.contact || null;
            showResponse(json);
            updateUi();
        } catch (err) {
            showResponse(err);
        }
    }

    extractForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        await submitExtraction(extractImagesInput.files);
    });

    function handleDragOver(e) {
        e.preventDefault();
        if (dropZone) {
            dropZone.classList.add('dragover');
        }
        if (e.dataTransfer) {
            e.dataTransfer.dropEffect = 'copy';
        }
    }

    function handleDragLeave() {
        dropZone?.classList.remove('dragover');
    }

    function handleDrop(e) {
        e.preventDefault();
        dropZone?.classList.remove('dragover');
        const droppedFiles = Array.from(e.dataTransfer?.files || []).filter((file) => file.type?.startsWith('image/'));
        if (!droppedFiles.length) {
            showResponse({ error: '画像ファイルをドロップしてください（画像のみ対応）' });
            return;
        }
        const dataTransfer = new DataTransfer();
        droppedFiles.slice(0, 2).forEach((file) => dataTransfer.items.add(file));
        extractImagesInput.files = dataTransfer.files;
        submitExtraction(extractImagesInput.files);
    }

    document.addEventListener('dragover', handleDragOver);
    document.addEventListener('drop', handleDrop);
    document.addEventListener('dragleave', handleDragLeave);
    dropZone?.addEventListener('dragover', handleDragOver);
    dropZone?.addEventListener('drop', handleDrop);
    dropZone?.addEventListener('dragleave', handleDragLeave);

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
