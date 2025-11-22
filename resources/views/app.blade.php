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
        button { background: #2563eb; color: #fff; border: none; border-radius: 10px; padding: 10px 14px; cursor: pointer; font-weight: 700; transition: background 0.2s, color 0.2s, box-shadow 0.2s; }
        button:hover { background: #1d4ed8; }
        #login-form button { margin-top: 8px; }
        #extract-form button { margin-top: 10px; }
        #notion-submit:disabled { background: #cbd5e1; color: #0f172a; cursor: not-allowed; }
        .form-error {
            color: #b91c1c;
            background: #fef2f2;
            border: 1px solid #fecdd3;
            border-radius: 10px;
            padding: 10px 12px;
            font-weight: 700;
        }
        .section-header { display: flex; align-items: flex-start; justify-content: flex-end; gap: 12px; margin-bottom: 8px; }
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
        .contact-table-container { margin: 10px 0 14px; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #fff; overflow-x: auto; }
        .contact-table { width: 100%; border-collapse: collapse; min-width: 480px; }
        .contact-table th, .contact-table td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        .contact-table th { width: 35%; background: #f8fafc; color: #1e293b; font-weight: 700; }
        .contact-table tr:last-child th, .contact-table tr:last-child td { border-bottom: none; }
        .toast {
            position: fixed;
            right: 24px;
            top: 20px;
            background: #0ea5e9;
            color: #fff;
            padding: 12px 16px;
            border-radius: 10px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.2);
            font-weight: 700;
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.2s ease, transform 0.2s ease;
            z-index: 1200;
        }
        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }
        .contact-empty { margin: 8px 0 12px; }
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

        @media (max-width: 768px) {
            header { padding: 16px; }
            main { padding: 16px; }
            section { padding: 16px; }
            h1 { font-size: 20px; }
            h2 { font-size: 16px; }
            .row { flex-direction: column; }
            .row > div { width: 100%; }
            .section-header { flex-direction: column; align-items: stretch; }
            .section-header button { width: 100%; }
            #extract-form button,
            #login-form button,
            #passkey-register-form button,
            #passkey-login-button,
            #notion-submit { width: 100%; }
            input[type="text"], input[type="password"], textarea { font-size: 16px; }
            .drop-zone { padding: 12px; }
        }
    </style>
</head>
@php
    $apiBase = rtrim(url('/api'), '/');
@endphp

<body>
<div id="loading-overlay" class="hidden" aria-hidden="true">
    <span id="loading-message" class="loading-wave" aria-label="解析中…"></span>
</div>
<div id="toast" class="toast" role="status" aria-live="polite"></div>
<header>
    <h1>
        Business Card Scanner for Notion
    </h1>
</header>
<main>
    <section id="login-section">
        <h2>ログイン</h2>
        <div class="stack">
            <p id="login-error" class="form-error hidden" role="alert"></p>
            <form id="login-form" method="post" action="{{ $apiBase }}/login">
                <label for="login-username">ユーザー名</label>
                <input id="login-username" type="text" name="username" placeholder="ユーザー名" required>
                <label for="login-password">パスワード</label>
                <input id="login-password" type="password" name="password" placeholder="パスワード" required>
                <button type="submit">ログイン</button>
            </form>
            <form id="passkey-login-form" class="row" method="post" action="{{ $apiBase }}/passkey/login">
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
            <button id="clear-button" type="button" aria-label="入力内容をクリア">クリア</button>
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
                    <form id="passkey-register-form" class="row" method="post" action="{{ $apiBase }}/passkey/register">
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
                <p id="extraction-status" class="muted">名刺画像をアップロードして解析を実行してください。（最大2枚 表と裏）</p>
                <form id="extract-form" method="post" action="{{ $apiBase }}/extract" enctype="multipart/form-data">
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
                <form id="notion-create-form" method="post" action="{{ $apiBase }}/notion/create">
                    <div id="contact-section" class="hidden">
                        <div id="contact-table-wrapper" class="contact-table-container hidden">
                            <table class="contact-table">
                                <tbody id="contact-table-body"></tbody>
                            </table>
                        </div>
                        <p id="contact-empty" class="muted contact-empty">解析結果がここに表示されます。</p>
                        <label><input type="checkbox" id="notion-confirm"> 内容を確認しました</label>
                        <button id="notion-submit" type="submit">Notionへ登録</button>
                    </div>
                </form>
            </section>
        </div>

    </section>
</main>
<script>
    const propertyConfig = @json($propertyConfig ?? []);
    const API_BASE = @json($apiBase);
    const api = (path = '') => {
        if (!path) return API_BASE;
        return path.startsWith('/') ? `${API_BASE}${path}` : `${API_BASE}/${path}`;
    };
    const loginSection = document.getElementById('login-section');
    const postLoginSection = document.getElementById('post-login-section');
    const extractionStatus = document.getElementById('extraction-status');
    const notionReady = document.getElementById('notion-ready');
    const notionSubmit = document.getElementById('notion-submit');
    const notionConfirm = document.getElementById('notion-confirm');
    const contactSection = document.getElementById('contact-section');
    const contactTableBody = document.getElementById('contact-table-body');
    const contactTableWrapper = document.getElementById('contact-table-wrapper');
    const contactEmpty = document.getElementById('contact-empty');
    const logoutButton = document.getElementById('logout-button');
    const clearButton = document.getElementById('clear-button');
    const passkeyAccordion = document.getElementById('passkey-accordion');
    const passkeyAccordionSummary = passkeyAccordion?.querySelector('summary');
    const passkeyAccordionTitle = document.getElementById('passkey-accordion-title');
    const passkeyRegisteredBadge = document.getElementById('passkey-registered-badge');
    const passkeyRegisterNote = document.getElementById('passkey-register-note');
    const passkeyLoginMessage = document.getElementById('passkey-login-message');
    const loginErrorMessage = document.getElementById('login-error');
    const loadingOverlay = document.getElementById('loading-overlay');
    const toast = document.getElementById('toast');
    const loadingMessage = document.getElementById('loading-message');
    const extractionDefault = extractionStatus?.textContent || '';
    const notionReadyDefault = notionReady?.textContent || '';
    const notionSubmitDefault = notionSubmit?.textContent || '';
    const appState = {
        authenticated: false,
        contact: null,
        hasPasskey: false,
    };
    let contactSectionVisible = false;

    function setLoadingMessage(message = '解析中…') {
        if (!loadingMessage) return;
        loadingMessage.setAttribute('aria-label', message);
        loadingMessage.innerHTML = '';
        message.split('').forEach((char, index) => {
            const span = document.createElement('span');
            span.textContent = char;
            span.style.animationDelay = `${0.12 * index}s`;
            loadingMessage.appendChild(span);
        });
    }

    function setUiDisabled(disabled, message = '解析中…') {
        const isDisabled = Boolean(disabled);
        if (loadingOverlay) {
            loadingOverlay.classList.toggle('hidden', !isDisabled);
            loadingOverlay.setAttribute('aria-hidden', (!isDisabled).toString());
            if (isDisabled) {
                setLoadingMessage(message);
            } else {
                setLoadingMessage('解析中…');
            }
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

    function showToast(message) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }

    function revealContactSection() {
        if (!contactSection) return;
        contactSectionVisible = true;
        contactSection.classList.remove('hidden');
    }

    function showResponse(data) {
        console.log('Response:', data);
    }

    function setLoginError(message) {
        if (!loginErrorMessage) return;
        if (message) {
            loginErrorMessage.textContent = message;
            loginErrorMessage.classList.remove('hidden');
        } else {
            loginErrorMessage.textContent = '';
            loginErrorMessage.classList.add('hidden');
        }
    }

    function getErrorMessage(error, fallback = 'ログインに失敗しました。もう一度お試しください。') {
        if (!error) return fallback;
        if (typeof error === 'string') return error;
        if (typeof error === 'object') {
            if (error.error) return error.error;
            if (error.message) return error.message;
            if (error.statusText) return error.statusText;
        }
        return fallback;
    }

    function renderContactTable(contact) {
        if (!contactTableBody) return;

        const entries = [];
        const propertyKeys = Object.keys(propertyConfig || {});

        propertyKeys.forEach((key) => {
            const label = propertyConfig?.[key]?.name || key;
            entries.push({ key, label, value: contact?.[key] ?? '' });
        });

        if (contact) {
            Object.keys(contact)
                .filter((key) => !propertyKeys.includes(key))
                .forEach((key) => entries.push({ key, label: key, value: contact[key] ?? '' }));
        }

        contactTableBody.innerHTML = '';

        entries.forEach(({ label, value }) => {
            const row = document.createElement('tr');
            const th = document.createElement('th');
            const td = document.createElement('td');
            th.textContent = label;
            td.textContent = value || '－';
            row.appendChild(th);
            row.appendChild(td);
            contactTableBody.appendChild(row);
        });
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
            extractionStatus.textContent = extractionDefault;
        }

        if (notionReady) {
            if (appState.contact) {
                notionReady.textContent = '解析済みデータを Notion に登録できます。内容を確認してください。';
            } else {
                notionReady.textContent = notionReadyDefault;
            }
        }

        if (contactTableWrapper && contactEmpty) {
            const hasContact = Boolean(appState.contact);
            contactTableWrapper.classList.toggle('hidden', !hasContact);
            contactEmpty.classList.toggle('hidden', hasContact);

            if (hasContact) {
                renderContactTable(appState.contact);
            } else if (contactTableBody) {
                contactTableBody.innerHTML = '';
            }
        }

        if (notionSubmit) {
            const hasContact = Boolean(appState.contact);
            const hasConfirmation = Boolean(notionConfirm?.checked);
            notionSubmit.disabled = !hasContact || !hasConfirmation;
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

        if (notionConfirm) {
            notionConfirm.checked = false;
        }

        if (extractionStatus) {
            extractionStatus.textContent = extractionDefault;
        }
        if (notionReady) {
            notionReady.textContent = notionReadyDefault;
        }

        setLoginError('');

        updateUi();
    }

    passkeyAccordion?.addEventListener('toggle', () => {
        if (!passkeyAccordionSummary) return;
        passkeyAccordionSummary.setAttribute('aria-expanded', passkeyAccordion.open ? 'true' : 'false');
    });

    logoutButton?.addEventListener('click', async () => {
        try {
            const data = await postJson(api('/logout'), {});
            resetUi();
            showResponse(data);
        } catch (err) {
            resetUi();
            showResponse(err);
        }
    });

    clearButton?.addEventListener('click', () => {
        resetUi({ preserveAuth: true });
    });

    async function postJson(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify(body)
        });

        const contentType = res.headers.get('content-type') || '';
        let payload = null;

        if (contentType.includes('application/json')) {
            try {
                payload = await res.json();
            } catch (err) {
                console.warn('Failed to parse JSON response', err);
            }
        }

        if (payload === null) {
            const text = await res.text().catch(() => '');
            payload = text ? { message: text } : {};
        }

        if (!res.ok) {
            const errorData = typeof payload === 'object' && payload !== null ? payload : {};
            errorData.status = res.status;
            errorData.statusText = res.statusText;
            throw errorData;
        }

        return payload ?? {};
    }

    async function refreshAuthState() {
        try {
            const res = await fetch(api('/auth/status'), { credentials: 'include' });
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
            const data = await postJson(api('/login'), { username, password });
            appState.authenticated = true;
            appState.contact = null;
            showResponse(data);
            setLoginError('');
            await refreshAuthState();
        } catch (err) {
            showResponse(err);
            setLoginError(getErrorMessage(err, 'ユーザー名またはパスワードが正しくありません。'));
        }
    });

    document.getElementById('passkey-register-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const passkey = document.getElementById('passkey-register').value;
        try {
            const data = await postJson(api('/passkey/register'), { passkey });
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
            setLoginError('パスキーを入力してください。');
            return false;
        }

        try {
            const data = await postJson(api('/passkey/login'), { passkey });
            appState.authenticated = true;
            appState.contact = null;
            showResponse(data);
            setLoginError('');
            await refreshAuthState();
        } catch (err) {
            showResponse(err);
            setLoginError(getErrorMessage(err, 'パスキーでのログインに失敗しました。'));
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
            setUiDisabled(true, '解析中…');
            const res = await fetch(api('/extract'), {
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
        if (!hasContact) {
            showResponse({ error: '解析済みデータと確認チェックが必要です。' });
            return;
        }
        if (!notionConfirm.checked) {
            alert('チェックを入れてください');
            return;
        }
        if (!notionSubmit) return;
        notionSubmit.textContent = '登録中…';
        notionSubmit.disabled = true;
        setUiDisabled(true, '処理中…');
        try {
            if (!appState.contact) {
                showResponse({ error: '解析結果がありません。名刺画像をアップロードしてください。' });
                return;
            }
            const data = await postJson(api('/notion/create'), { contact: appState.contact, attachments: [] });
            showResponse(data);
            showToast('登録が完了しました');
        } catch (err) {
            showResponse(err);
        } finally {
            notionSubmit.textContent = notionSubmitDefault || 'Notionへ登録';
            notionSubmit.disabled = false;
            setUiDisabled(false);
        }
    });

    refreshAuthState();
</script>
</body>
</html>
