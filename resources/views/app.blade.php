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
            gap: 8px;
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
        .accordion .accordion-title {
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 999px;
            background: #dbeafe;
            color: #1e3a8a;
            font-size: 12px;
            font-weight: 700;
        }
        p { margin: 4px 0 12px; line-height: 1.6; }
        label { display: block; margin-bottom: 6px; font-weight: 600; }
        input[type="text"], input[type="password"], textarea { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; box-sizing: border-box; }
        textarea { min-height: 140px; font-family: ui-monospace, SFMono-Regular, SFMono, Consolas, "Liberation Mono", Menlo, monospace; }
        .row { display: flex; gap: 12px; }
        .row > div { flex: 1; }
        button { background: #2563eb; color: #fff; border: none; border-radius: 10px; padding: 10px 14px; cursor: pointer; font-weight: 700; }
        button:hover { background: #1d4ed8; }
        .section-header { display: flex; align-items: center; justify-content: flex-end; gap: 12px; }
        .button-danger { background: #b91c1c; }
        .button-danger:hover { background: #991b1b; }
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
        .drop-zone.disabled { opacity: 0.6; pointer-events: none; }
        #loading-overlay {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: rgba(15, 23, 42, 0.6);
            color: #fff;
            font-size: 24px;
            font-weight: 800;
            z-index: 1000;
        }
        #loading-overlay.hidden {
            display: none !important;
            opacity: 0;
            pointer-events: none;
        }
        #loading-overlay .loading-wave { display: inline-flex; gap: 4px; letter-spacing: 1px; }
        #loading-overlay .loading-wave span { display: inline-block; animation: loading-wave 1.2s ease-in-out infinite; }
        #loading-overlay .loading-wave span:nth-child(2) { animation-delay: 0.12s; }
        #loading-overlay .loading-wave span:nth-child(3) { animation-delay: 0.24s; }
        #loading-overlay .loading-wave span:nth-child(4) { animation-delay: 0.36s; }
        @keyframes loading-wave {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-8px); }
        }
    </style>
</head>
<body>
<div id="loading-overlay" class="hidden" aria-hidden="true">
    <span class="loading-wave" aria-label="解析中…">
        <span>解</span><span>析</span><span>中</span><span>…</span>
    </span>
</div>
<header>
    <h1>
        Business Card Scanner for Notion
    </h1>
</header>
<main>
    <section id="login-section">
        <h2>ログイン</h2>
        <div class="stack">
            <form id="login-form" method="post" action="/api/login">
                <label for="login-username">ユーザー名</label>
                <input id="login-username" type="text" name="username" placeholder="ユーザー名" required>
                <label for="login-password">パスワード</label>
                <input id="login-password" type="password" name="password" placeholder="パスワード" required>
                <button type="submit">ログイン</button>
            </form>
            <form id="passkey-login-form" class="row" method="post" action="/api/passkey/login">
                <div>
                    <label for="passkey-login">パスキーでログイン</label>
                    <input id="passkey-login" type="password" name="passkey" placeholder="登録済みパスキー" required>
                </div>
                <div style="align-self: end;">
                    <button id="passkey-login-button" type="button">パスキーでログイン</button>
                </div>
            </form>
            <p class="muted" id="passkey-login-message">アカウントに紐づくパスキーを登録済みの場合はこちらからログインできます。</p>
        </div>
    </section>

    <section id="post-login-section" class="hidden">
        <div class="section-header">
            <button id="logout-button" type="button" class="button-danger">ログアウト</button>
        </div>
        <div class="stack">
            <details class="accordion" id="passkey-accordion">
                <summary aria-controls="passkey-accordion-body" aria-expanded="false">
                    <span class="accordion-title">
                        <span id="passkey-accordion-title">パスキーの登録</span>
                        <span id="passkey-registered-badge" class="badge hidden">パスキー登録済</span>
                    </span>
                </summary>
                <div class="accordion-body" id="passkey-accordion-body">
                    <form id="passkey-register-form" class="row" method="post" action="/api/passkey/register">
                        <div>
                            <input id="passkey-register" type="password" name="passkey" placeholder="例: my-device-passkey" required>
                        </div>
                        <div style="align-self: end;">
                            <button type="submit">パスキー登録</button>
                        </div>
                    </form>
                    <p class="muted" id="passkey-register-note">パスキーを登録すると、以後はパスキーだけでログインできます。</p>
                </div>
            </details>

            <div>
                <h3>名刺画像から情報抽出</h3>
                <p id="extraction-status" class="muted">1〜2 枚の名刺画像をアップロードして解析を実行してください。</p>
                <form id="extract-form" method="post" action="/api/extract" enctype="multipart/form-data">
                    <input id="extract-images" type="file" name="images" accept="image/*" multiple required>
                    <div id="drop-zone" class="drop-zone">
                        ここに画像ファイルをドラッグ＆ドロップ
                        <small>画像のみ対応・最大 2 枚まで</small>
                    </div>
                    <button type="submit">名刺写真を解析</button>
                </form>
            </div>

            <section>
                <h3>Notion 連携</h3>
                <p id="notion-ready" class="muted">解析が成功すると Notion への登録ボタンが有効になります。</p>
                <form id="notion-create-form" method="post" action="/api/notion/create">
                    <div id="contact-section" class="hidden">
                        <textarea id="contact-json" aria-label="連携データ" required></textarea>
                        <label><input type="checkbox" id="notion-confirm"> 解析内容を確認しました</label>
                        <button id="notion-submit" type="submit">Notion ページ作成</button>
                    </div>
                </form>
            </section>
        </div>

    </section>
</main>
<script>
    const loginSection = document.getElementById('login-section');
    const postLoginSection = document.getElementById('post-login-section');
    const extractionStatus = document.getElementById('extraction-status');
    const notionReady = document.getElementById('notion-ready');
    const notionSubmit = document.getElementById('notion-submit');
    const notionConfirm = document.getElementById('notion-confirm');
    const contactJsonInput = document.getElementById('contact-json');
    const contactSection = document.getElementById('contact-section');
    const logoutButton = document.getElementById('logout-button');
    const passkeyAccordion = document.getElementById('passkey-accordion');
    const passkeyAccordionSummary = passkeyAccordion?.querySelector('summary');
    const passkeyAccordionTitle = document.getElementById('passkey-accordion-title');
    const passkeyRegisteredBadge = document.getElementById('passkey-registered-badge');
    const passkeyRegisterNote = document.getElementById('passkey-register-note');
    const passkeyLoginMessage = document.getElementById('passkey-login-message');
    const loadingOverlay = document.getElementById('loading-overlay');
    const sampleContactJson = `{
  "name": "山田 太郎",
  "company": "Example 株式会社",
  "website": "https://example.com",
  "email": "taro@example.com",
  "phone_number_1": "+81-3-1234-5678",
  "phone_number_2": "",
  "industry": "IT"
}`;
    const contactJsonDefault = sampleContactJson;
    const extractionDefault = extractionStatus?.textContent || '';
    const notionReadyDefault = notionReady?.textContent || '';
    const appState = {
        authenticated: false,
        contact: null,
        hasPasskey: false,
    };
    let contactSectionVisible = false;

    function setUiDisabled(disabled) {
        const isDisabled = Boolean(disabled);
        if (loadingOverlay) {
            loadingOverlay.classList.toggle('hidden', !isDisabled);
            loadingOverlay.setAttribute('aria-hidden', (!isDisabled).toString());
        }

        document.querySelectorAll('button, input, textarea, select').forEach((el) => {
            if (isDisabled) {
                el.dataset.disabledBefore = el.disabled ? 'true' : 'false';
                el.disabled = true;
            } else if (el.dataset.disabledBefore !== undefined) {
                const wasDisabled = el.dataset.disabledBefore === 'true';
                delete el.dataset.disabledBefore;
                el.disabled = wasDisabled;
            }
        });

        document.getElementById('drop-zone')?.classList.toggle('disabled', disabled);

        if (!isDisabled) {
            updateUi();
        }
    }

    function revealContactSection() {
        if (!contactSection) return;
        contactSectionVisible = true;
        contactSection.classList.remove('hidden');
        if (contactJsonInput && !contactJsonInput.value) {
            contactJsonInput.value = contactJsonDefault;
        }
    }

    function showResponse(data) {
        console.log('Response:', data);
    }

    function updateUi() {
        loginSection.classList.toggle('hidden', appState.authenticated);
        postLoginSection.classList.toggle('hidden', !appState.authenticated);
        if (appState.contact) {
            revealContactSection();
        }
        if (contactSection) {
            contactSection.classList.toggle('hidden', !contactSectionVisible);
        }

        if (extractionStatus) {
            extractionStatus.textContent = appState.contact
                ? '解析結果を確認し、Notion 登録に進めます。'
                : extractionDefault;
        }

        if (contactJsonInput && notionReady) {
            if (appState.contact) {
                contactJsonInput.value = JSON.stringify(appState.contact, null, 2);
                notionReady.textContent = '解析済みデータを Notion に登録できます。内容を確認してください。';
            } else if (contactSectionVisible) {
                contactJsonInput.value = contactJsonDefault;
                notionReady.textContent = notionReadyDefault;
            } else {
                contactJsonInput.value = '';
                notionReady.textContent = notionReadyDefault;
            }
        }

        if (notionSubmit) {
            notionSubmit.disabled = !appState.contact || !notionConfirm?.checked;
        }

        if (passkeyAccordionTitle) {
            passkeyAccordionTitle.textContent = appState.hasPasskey ? 'パスキーの更新' : 'パスキーの登録';
        }

        if (passkeyRegisteredBadge) {
            passkeyRegisteredBadge.classList.toggle('hidden', !appState.hasPasskey);
        }

        passkeyRegisterNote?.classList.toggle('hidden', appState.hasPasskey);

        passkeyLoginMessage?.classList.toggle('hidden', appState.hasPasskey);
    }

    function resetUi(options = {}) {
        const { preserveAuth = false } = options;

        if (!preserveAuth) {
            appState.authenticated = false;
            appState.hasPasskey = false;
        }
        appState.contact = null;
        contactSectionVisible = false;

        document.querySelectorAll('form#extract-form').forEach((form) => form.reset());
        document.getElementById('login-form')?.reset();
        document.getElementById('passkey-login-form')?.reset();
        document.getElementById('passkey-register-form')?.reset();
        document.getElementById('notion-create-form')?.reset();

        contactSection?.classList.add('hidden');

        document.querySelectorAll('input[type="file"]').forEach((input) => {
            input.value = '';
        });

        if (contactJsonInput) {
            contactJsonInput.value = contactJsonDefault;
        }

        if (notionConfirm) {
            notionConfirm.checked = false;
        }

        if (extractionStatus) {
            extractionStatus.textContent = extractionDefault;
        }
        if (notionReady) {
            notionReady.textContent = notionReadyDefault;
        }

        updateUi();
    }

    passkeyAccordion?.addEventListener('toggle', () => {
        if (!passkeyAccordionSummary) return;
        passkeyAccordionSummary.setAttribute('aria-expanded', passkeyAccordion.open ? 'true' : 'false');
    });

    logoutButton?.addEventListener('click', async () => {
        try {
            const data = await postJson('/api/logout', {});
            resetUi();
            showResponse(data);
        } catch (err) {
            resetUi();
            showResponse(err);
        }
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

    async function handlePasskeyLogin(event) {
        event?.preventDefault();
        event?.stopPropagation();

        const passkey = document.getElementById('passkey-login')?.value;
        if (!passkey) {
            showResponse({ error: 'パスキーを入力してください。' });
            return false;
        }

        try {
            const data = await postJson('/api/passkey/login', { passkey });
            appState.authenticated = true;
            appState.contact = null;
            showResponse(data);
            await refreshAuthState();
        } catch (err) {
            showResponse(err);
        }

        return false;
    }

    document.getElementById('passkey-login-form')?.addEventListener('submit', handlePasskeyLogin);
    document.getElementById('passkey-login-button')?.addEventListener('click', handlePasskeyLogin);

    const extractForm = document.getElementById('extract-form');
    const extractImagesInput = document.getElementById('extract-images');
    const dropZone = document.getElementById('drop-zone');

    function setFileInput(files) {
        if (!extractImagesInput) return;

        const dataTransfer = new DataTransfer();
        files.forEach((file) => dataTransfer.items.add(file));
        extractImagesInput.files = dataTransfer.files;
    }

    function buildExtractionFormData(files) {
        const formData = new FormData();
        Array.from(files).slice(0, 2).forEach((file, idx) => {
            formData.append('images[]', file, file.name || `image-${idx + 1}`);
        });
        return formData;
    }

    async function submitExtraction(files) {
        const selectedFiles = files && files.length ? files : extractImagesInput.files;
        revealContactSection();
        if (!selectedFiles.length) {
            showResponse({ error: '画像ファイルを選択してください' });
            return;
        }

        const abortController = new AbortController();
        const timeoutId = setTimeout(() => abortController.abort(), 45000);

        try {
            setUiDisabled(true);
            const res = await fetch('/api/extract', {
                method: 'POST',
                body: buildExtractionFormData(selectedFiles),
                credentials: 'include',
                signal: abortController.signal,
            });
            const json = await res.json();
            if (!res.ok) throw json;
            appState.contact = json.contact || null;
            showResponse(json);
            updateUi();
        } catch (err) {
            if (err?.name === 'AbortError') {
                showResponse({ error: '解析がタイムアウトしました。もう一度お試しください。' });
            } else {
                showResponse(err);
            }
        } finally {
            clearTimeout(timeoutId);
            setUiDisabled(false);
        }
    }

    extractForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        await submitExtraction(extractImagesInput.files);
    });

    dropZone?.addEventListener('click', () => extractImagesInput?.click());

    dropZone?.addEventListener('dragover', (event) => {
        event.preventDefault();
        dropZone.classList.add('dragover');
    });

    dropZone?.addEventListener('dragleave', () => {
        dropZone.classList.remove('dragover');
    });

    dropZone?.addEventListener('drop', async (event) => {
        event.preventDefault();
        dropZone.classList.remove('dragover');

        const droppedFiles = Array.from(event.dataTransfer?.files || []);
        const imageFiles = droppedFiles.filter((file) => file.type?.startsWith('image/')).slice(0, 2);

        if (!imageFiles.length) {
            showResponse({ error: '画像ファイルをドロップしてください。' });
            return;
        }

        setFileInput(imageFiles);
    });

    notionConfirm?.addEventListener('change', updateUi);

    document.getElementById('notion-create-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const hasContact = Boolean(appState.contact);
        if (!hasContact || !notionConfirm.checked) {
            showResponse({ error: '解析済みデータと確認チェックが必要です。' });
            return;
        }
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
