<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In | Forensic Information Management System (FIMS)</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    @endif

@include('auth.partials.auth_styles')
</head>

<body class="antialiased selection:bg-teal-700 selection:text-white transition-opacity duration-300 opacity-100">

    <div class="auth-shell">
        @include('auth.partials.auth_brand', ['mode' => 'login'])

        <main class="auth-panel">
            <div class="flex items-center justify-end">
                <span class="auth-switch">No account yet?
                    <a href="{{ url('/register') }}"
                        onclick="navigateWithFade(event, '{{ url('/register') }}')">Register as officer</a></span>
            </div>

            <div class="auth-panel__inner">
                <div class="auth-header mb-6">
                    <img src="{{ asset('NACOC_LOGO.PNG') }}" alt="NACOC crest" width="175" height="175"
                        class="auth-logo">
                    <h1 class="auth-title">Sign in</h1>
                    <p class="auth-sub">Authorised personnel only. Your session and actions are logged.</p>
                </div>

                <div class="auth-card">
                    @include('partials.flash')

                    <div id="loginInlineError"
                        class="hidden mb-5 p-3.5 bg-red-50/90 border border-red-200 text-red-700 text-xs font-semibold rounded-xl flex items-center space-x-2.5 shadow-xs"
                        role="alert">
                        <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span id="loginInlineErrorText"></span>
                    </div>

                    <form id="loginForm" method="POST" action="{{ url('/login') }}" onsubmit="handleLoginSubmit(event)"
                        class="space-y-4">
                        @csrf

                        <div class="ref-input-line">
                            <label for="serviceId" class="block">
                                <svg class="w-3.5 h-3.5 text-teal-700 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span>Username, email or service ID</span>
                            </label>
                            <div class="relative flex items-center">
                                <input type="text" id="serviceId" name="login_id" required autocomplete="username"
                                    value="{{ old('login_id') }}" placeholder="SD-345, smensah or email@nacoc.gov.gh"
                                    class="w-full py-1 bg-transparent font-semibold text-slate-900 placeholder-slate-400 focus:outline-none">
                            </div>
                            @error('login_id')
                                <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="ref-input-line">
                            <label for="passwordInput" class="block">
                                <svg class="w-3.5 h-3.5 text-teal-700 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <span>Password</span>
                            </label>
                            <div class="relative flex items-center">
                                <input type="password" id="passwordInput" name="password" required
                                    autocomplete="current-password" placeholder="Enter your password"
                                    class="w-full py-1 bg-transparent font-semibold text-slate-900 placeholder-slate-400 focus:outline-none pr-9">
                                <button type="button" onclick="togglePasswordVisibility()"
                                    class="absolute right-0 p-1 text-slate-400 hover:text-teal-700 transition-colors cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 rounded-lg"
                                    aria-label="Toggle password visibility">
                                    <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" checked
                                    class="w-4 h-4 rounded text-teal-700 focus:ring-teal-700 border-slate-300">
                                <span class="text-xs text-slate-600 font-medium">Keep me signed in</span>
                            </label>
                            <a href="#" onclick="showResetToast(); return false;" class="auth-link text-xs">Forgot
                                password?</a>
                        </div>

                        <button type="submit" id="submitBtn" class="auth-cta">
                            <svg class="w-4 h-4 text-white/90 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            <span id="btnText">Sign in to FIMS</span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500">
                <span>Official system of the Narcotics Control Commission, Ghana</span>
                <a href="#"
                    onclick="alert('For system access assistance, contact NACOC IT Helpdesk: ext 4022'); return false;"
                    class="auth-link">Need help?</a>
            </div>
        </main>
    </div>

    <!-- ACCOUNT APPROVED POPOVER MODAL CONTAINER -->
    <div id="loginAccountApprovedPopoverModal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="popoverApprovalTitle"
        class="hidden fixed inset-0 z-50 bg-slate-900/65 backdrop-blur-md flex items-center justify-center p-4 transition-all duration-300"
        style="backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);">
        <div
            class="bg-white rounded-3xl border border-teal-500/30 shadow-2xl max-w-lg w-full p-6 sm:p-8 space-y-6 relative transform transition-all scale-100">

            <!-- HEADER -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center space-x-3">
                    <div
                        class="w-10 h-10 rounded-2xl bg-teal-50 p-1 border border-teal-200 flex items-center justify-center shadow-xs overflow-hidden">
                        <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Logo" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <span id="popoverApprovalEyebrow"
                            class="px-2.5 py-0.5 rounded-full bg-teal-100 text-teal-800 font-mono-code font-bold text-[10px] uppercase tracking-wider">
                            UNIT COMMAND NOTIFICATION
                        </span>
                        <div id="popoverApprovalAuthority" class="text-xs font-black text-slate-900 mt-0.5">HEAD OF UNIT</div>
                    </div>
                </div>
                <button onclick="closeLoginApprovedPopover()"
                    class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center text-lg font-bold cursor-pointer transition-colors">&times;</button>
            </div>

            <!-- CONTENT -->
            <div class="text-center space-y-3 py-2">
                <div
                    class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-teal-100 text-teal-700 shadow-inner mb-1">
                    <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <h3 id="popoverApprovalTitle" class="text-xl font-black text-slate-900 tracking-tight leading-snug">
                    Account Approved by Head of Unit!
                </h3>

                <p id="popoverApprovalMessage" class="text-xs sm:text-sm text-slate-600 max-w-md mx-auto leading-relaxed">
                    Your staff account <span id="popoverOfficerName"
                        class="font-extrabold text-slate-900 bg-teal-50 px-2 py-0.5 rounded border border-teal-200 hidden"></span>
                    has been approved by your Head of Unit. Please go ahead and sign in to the NACOC Forensic Information Management System (FIMS).
                </p>
            </div>

            <!-- STATUS BOX -->
            <div class="bg-teal-50/70 border border-teal-200 rounded-2xl p-4 font-mono-code text-xs space-y-2">
                <div class="flex items-center justify-between text-teal-900 font-bold">
                    <span>AUTHORIZATION STATUS:</span>
                    <span class="px-2 py-0.5 bg-teal-700 text-white rounded text-[10px] uppercase font-bold">APPROVED
                        &amp; ACTIVE</span>
                </div>
                <p class="text-[11px] text-teal-800 font-sans">
                    Please enter your Service ID / Username and Password below to access your unit workspace.
                </p>
            </div>

            <!-- ACTION BUTTON -->
            <div class="pt-2">
                <button onclick="closeLoginApprovedPopover()"
                    class="w-full py-3 bg-teal-700 hover:bg-teal-800 text-white text-xs font-extrabold rounded-xl shadow-lg shadow-teal-700/30 transition-all cursor-pointer flex items-center justify-center space-x-2">
                    <span>Proceed to Login</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>
            </div>

        </div>
    </div>

    <!-- TOAST NOTIFICATION -->
    <div id="toastNotification"
        class="fixed bottom-5 right-5 z-50 hidden transform transition-all duration-300 translate-y-2">
        <div
            class="flex items-center space-x-3 px-4 py-3 bg-slate-900 text-white rounded-xl shadow-xl border border-slate-700">
            <div class="w-7 h-7 rounded-full bg-teal-500/20 text-teal-400 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <div class="text-xs font-bold text-white" id="toastTitle">Notice</div>
                <div class="text-[11px] text-slate-300" id="toastMessage">Information updated.</div>
            </div>
        </div>
    </div>

    <!-- PRESERVED EXACT JAVASCRIPT LOGIC -->
    <script>
        const PENDING_LOGIN_STORAGE_KEY = 'nacoc_pending_login_id';
        const CHECK_APPROVAL_ENDPOINT = '{{ url('/api/check-user-approval') }}';
        const INITIAL_PENDING_LOGIN_ID = @json(session('pending_login_id'));
        const PAGE_LOAD_TS = Math.floor(Date.now() / 1000);
        let approvalPollTimer = null;
        let isPollingApproval = false;
        let shownApprovalEventId = null;

        function showLoginApprovedPopover(userPayload = null) {
            const modal = document.getElementById('loginAccountApprovedPopoverModal');
            if (!modal) return;

            const eyebrow = document.getElementById('popoverApprovalEyebrow');
            const authority = document.getElementById('popoverApprovalAuthority');
            const title = document.getElementById('popoverApprovalTitle');
            const msg = document.getElementById('popoverApprovalMessage');
            const officerNameEl = document.getElementById('popoverOfficerName');
            const serviceIdInput = document.getElementById('serviceId');

            const isHeadAccount = Boolean(userPayload && userPayload.is_head);
            const officerLabel = userPayload ? (userPayload.fullname || userPayload.service_id || userPayload.username || '') : '';
            const unitLabel = userPayload && userPayload.unit ? userPayload.unit : '';

            if (isHeadAccount) {
                if (eyebrow) eyebrow.textContent = 'DIRECTORATE COMMAND NOTIFICATION';
                if (authority) authority.textContent = 'HEAD OF FORENSIC DIRECTORATE';
                if (title) title.textContent = 'Account Approved by Head of Forensic Directorate!';
                if (msg) {
                    msg.textContent = '';
                    msg.append('Your user account ');
                    if (officerLabel) {
                        const nameBadge = document.createElement('span');
                        nameBadge.id = 'popoverOfficerName';
                        nameBadge.className = 'font-extrabold text-slate-900 bg-teal-50 px-2 py-0.5 rounded border border-teal-200';
                        nameBadge.textContent = officerLabel;
                        msg.append(nameBadge, ' ');
                    }
                    msg.append('has been officially reviewed, verified, and approved by the Head of Forensic Directorate. Please go ahead and sign in to the NACOC Forensic Information Management System (FIMS).');
                }
            } else {
                if (eyebrow) eyebrow.textContent = 'UNIT COMMAND NOTIFICATION';
                if (authority) authority.textContent = unitLabel ? `HEAD OF UNIT • ${unitLabel.toUpperCase()}` : 'HEAD OF UNIT';
                if (title) title.textContent = 'Account Approved by Head of Unit!';
                if (msg) {
                    msg.textContent = '';
                    msg.append('Your staff account ');
                    if (officerLabel) {
                        const nameBadge = document.createElement('span');
                        nameBadge.id = 'popoverOfficerName';
                        nameBadge.className = 'font-extrabold text-slate-900 bg-teal-50 px-2 py-0.5 rounded border border-teal-200';
                        nameBadge.textContent = officerLabel;
                        msg.append(nameBadge, ' ');
                    }
                    if (unitLabel) {
                        msg.append('for ');
                        const unitStrong = document.createElement('strong');
                        unitStrong.textContent = unitLabel;
                        msg.append(unitStrong, ' ');
                    }
                    msg.append('has been approved by your Head of Unit. Please go ahead and sign in to the NACOC Forensic Information Management System (FIMS).');
                }
            }

            if (officerNameEl && officerLabel && !msg) {
                officerNameEl.textContent = officerLabel;
                officerNameEl.classList.remove('hidden');
            }

            if (serviceIdInput && !serviceIdInput.value.trim() && userPayload) {
                serviceIdInput.value = userPayload.service_id || userPayload.username || '';
            }

            const inlineBox = document.getElementById('loginInlineError');
            if (inlineBox) inlineBox.classList.add('hidden');

            modal.classList.remove('hidden');
        }

        function closeLoginApprovedPopover() {
            const modal = document.getElementById('loginAccountApprovedPopoverModal');
            if (modal) modal.classList.add('hidden');
            localStorage.removeItem(PENDING_LOGIN_STORAGE_KEY);
            const passwordInput = document.getElementById('passwordInput');
            if (passwordInput) passwordInput.focus();
        }

        function stopApprovalPolling() {
            if (approvalPollTimer) {
                clearInterval(approvalPollTimer);
                approvalPollTimer = null;
            }
        }

        async function checkPendingApprovalOnce(explicitLoginId = '') {
            if (isPollingApproval) return;
            const storedId = (localStorage.getItem(PENDING_LOGIN_STORAGE_KEY) || '').trim();
            const loginId = String(explicitLoginId || storedId).trim();
            isPollingApproval = true;

            try {
                const params = new URLSearchParams();
                if (loginId) {
                    params.set('login_id', loginId);
                } else {
                    params.set('since', String(PAGE_LOAD_TS));
                }

                const url = `${CHECK_APPROVAL_ENDPOINT}?${params.toString()}`;
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                });
                if (!res.ok) return;

                const data = await res.json();
                if (data && data.exists && data.is_approved) {
                    if (data.event_id && data.event_id === shownApprovalEventId) {
                        return;
                    }
                    if (data.event_id) {
                        shownApprovalEventId = data.event_id;
                    }
                    stopApprovalPolling();
                    localStorage.removeItem(PENDING_LOGIN_STORAGE_KEY);
                    showLoginApprovedPopover(data);
                }
            } catch (e) {
                // Ignore transient network errors while polling for approval
            } finally {
                isPollingApproval = false;
            }
        }

        function startApprovalPolling(loginId = '') {
            const trimmed = String(loginId || '').trim();
            if (trimmed) {
                localStorage.setItem(PENDING_LOGIN_STORAGE_KEY, trimmed);
            }
            stopApprovalPolling();
            checkPendingApprovalOnce(trimmed);
            approvalPollTimer = setInterval(() => checkPendingApprovalOnce(trimmed), 1500);
        }

        function handleBroadcastApproval(payload) {
            if (!payload || payload.type !== 'staff_approved' || !payload.user) return;
            const approvedUser = payload.user;
            const pendingId = (localStorage.getItem(PENDING_LOGIN_STORAGE_KEY) || '').trim().toLowerCase();
            const typedId = (document.getElementById('serviceId')?.value || '').trim().toLowerCase();
            const candidateId = pendingId || typedId;

            if (candidateId) {
                const matches = [approvedUser.service_id, approvedUser.username, approvedUser.email]
                    .filter(Boolean)
                    .some(v => String(v).trim().toLowerCase() === candidateId);
                if (!matches) return;
            }

            stopApprovalPolling();
            localStorage.removeItem(PENDING_LOGIN_STORAGE_KEY);
            showLoginApprovedPopover(approvedUser);
        }

        async function watchTypedServiceIdForPendingApproval(rawValue) {
            const val = String(rawValue || '').trim();
            if (val.length < 3) return;
            try {
                const res = await fetch(`${CHECK_APPROVAL_ENDPOINT}?login_id=${encodeURIComponent(val)}`, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                });
                if (!res.ok) return;
                const data = await res.json();
                if (data && data.exists && !data.is_approved) {
                    startApprovalPolling(data.service_id || data.username || val);
                }
            } catch (e) {}
        }

        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('account_approved') || urlParams.has('approved')) {
                showLoginApprovedPopover();
                const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({
                    path: cleanUrl
                }, '', cleanUrl);
                return;
            }

            if (typeof BroadcastChannel !== 'undefined') {
                try {
                    const approvalChannel = new BroadcastChannel('nacoc_staff_approval');
                    approvalChannel.addEventListener('message', (event) => handleBroadcastApproval(event.data));
                } catch (e) {}
            }

            window.addEventListener('storage', function(event) {
                if (event.key === 'nacoc_last_staff_approved' && event.newValue) {
                    try {
                        handleBroadcastApproval(JSON.parse(event.newValue));
                    } catch (e) {}
                }
            });

            const initialId = INITIAL_PENDING_LOGIN_ID || localStorage.getItem(PENDING_LOGIN_STORAGE_KEY) || '';
            startApprovalPolling(initialId);

            const serviceIdInput = document.getElementById('serviceId');
            if (serviceIdInput) {
                let typedWatchTimer = null;
                const scheduleWatch = () => {
                    clearTimeout(typedWatchTimer);
                    typedWatchTimer = setTimeout(() => watchTypedServiceIdForPendingApproval(serviceIdInput.value), 400);
                };
                serviceIdInput.addEventListener('input', scheduleWatch);
                serviceIdInput.addEventListener('change', scheduleWatch);
                if (serviceIdInput.value && serviceIdInput.value.trim().length >= 3) {
                    scheduleWatch();
                }
            }
        });

        // Live UTC Clock
        function updateClock() {
            const now = new Date();
            const year = now.getUTCFullYear();
            const month = String(now.getUTCMonth() + 1).padStart(2, '0');
            const day = String(now.getUTCDate()).padStart(2, '0');
            const hours = String(now.getUTCHours()).padStart(2, '0');
            const minutes = String(now.getUTCMinutes()).padStart(2, '0');
            const seconds = String(now.getUTCSeconds()).padStart(2, '0');
            const clockElem = document.getElementById('liveClock');
            if (clockElem) {
                clockElem.innerText = `${year}-${month}-${day} ${hours}:${minutes}:${seconds} UTC`;
            }
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Password visibility toggle
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('passwordInput');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
            } else {
                passwordInput.type = 'password';
            }
        }

        // Page transition fade-out
        function navigateWithFade(e, url) {
            e.preventDefault();
            document.body.classList.remove('opacity-100');
            document.body.classList.add('opacity-0');
            setTimeout(() => {
                window.location.href = url;
            }, 250);
        }

        // Handle Login Submission & Redirection
        async function handleLoginSubmit(e) {
            e.preventDefault();

            const form = document.getElementById('loginForm');
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const serviceIdInput = document.getElementById('serviceId');
            const passwordInput = document.getElementById('passwordInput');
            const inlineBox = document.getElementById('loginInlineError');
            const inlineText = document.getElementById('loginInlineErrorText');

            if (inlineBox) inlineBox.classList.add('hidden');

            const loginId = serviceIdInput ? serviceIdInput.value.trim() : '';
            const password = passwordInput ? passwordInput.value : '';

            if (!loginId || !password) {
                if (inlineBox && inlineText) {
                    inlineText.innerText = 'Please enter both your Service ID / Username and Password.';
                    inlineBox.classList.remove('hidden');
                }
                return;
            }

            stopApprovalPolling();
            submitBtn.disabled = true;
            btnText.innerHTML = `
                <span class="inline-flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Signing in...
                </span>
            `;

            try {
                const formData = new FormData(form);
                const csrfToken = document.querySelector('input[name="_token"]')?.value || '';

                const response = await fetch(form.action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    btnText.innerHTML = `
                        <span class="inline-flex items-center">
                            <svg class="w-4 h-4 mr-1.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            Access Granted...
                        </span>
                    `;
                    setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 300);
                } else {
                    submitBtn.disabled = false;
                    btnText.innerHTML = `Sign In to FIMS`;
                    const errMsg = data.message || (data.errors ? Object.values(data.errors).flat()[0] : 'Wrong username or password.');

                    if (data.pending_approval) {
                        startApprovalPolling(data.login_id || loginId);
                    }

                    if (inlineBox && inlineText) {
                        inlineText.innerText = errMsg;
                        inlineBox.classList.remove('hidden');
                    }
                    showErrorToast(errMsg);
                }
            } catch (err) {
                // If fetch fails (e.g. network error), fallback to normal form submission
                form.submit();
            }
        }

        function showErrorToast(msg) {
            const toast = document.getElementById('toastNotification');
            if (!toast) return;
            document.getElementById('toastTitle').innerText = 'Authentication Failed';
            document.getElementById('toastMessage').innerText = msg;

            toast.classList.remove('hidden');
            setTimeout(() => {
                toast.classList.remove('translate-y-2');
            }, 10);

            setTimeout(() => {
                toast.classList.add('translate-y-2');
                setTimeout(() => {
                    toast.classList.add('hidden');
                }, 300);
            }, 5000);
        }

        function showResetToast() {
            const toast = document.getElementById('toastNotification');
            document.getElementById('toastTitle').innerText = 'Security Desk Contacted';
            document.getElementById('toastMessage').innerText = 'Passcode reset request sent to IT Command Desk.';

            toast.classList.remove('hidden');
            setTimeout(() => {
                toast.classList.remove('translate-y-2');
            }, 10);

            setTimeout(() => {
                toast.classList.add('translate-y-2');
                setTimeout(() => {
                    toast.classList.add('hidden');
                }, 300);
            }, 3500);
        }
    </script>
</body>

</html>
