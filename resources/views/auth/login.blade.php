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

@php
    $revokedPayloadInit = session('revoked_staff_payload') ?? ($initialRevokedStaff ?? null);
    $isForgotInitiallyDisabled = !empty($revokedPayloadInit);
    $initialLoginIdVal = old('login_id', request()->query('login_id', ''));
@endphp

<body class="antialiased selection:bg-teal-700 selection:text-white transition-opacity duration-300 opacity-100">
    @include('partials.flash')

    <div class="auth-shell">
        @include('auth.partials.auth_brand', ['mode' => 'login'])

        <main class="auth-panel auth-panel--login">
            <div class="flex items-center justify-end">
                <span class="auth-switch">No account yet?
                    <a href="{{ url('/register') }}"
                        onclick="navigateWithFade(event, '{{ url('/register') }}')">Register as officer</a></span>
            </div>

            <div class="auth-panel__inner auth-panel__inner--login">
                <div class="auth-header mb-4">
                    <img src="{{ asset('NACOC_LOGO.png') }}" alt="NACOC crest" width="350" height="350"
                        class="auth-logo auth-logo--login">
                    <h1 class="auth-title">Sign in</h1>
                    <p class="auth-sub">Authorised personnel only. Your session and actions are logged.</p>
                </div>

                <div class="auth-card">
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
                                    value="{{ $initialLoginIdVal }}" placeholder="SD-345, smensah or email@nacoc.gov.gh"
                                    class="w-full py-1 bg-transparent font-semibold text-slate-900 placeholder-slate-400 focus:outline-none">
                            </div>
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
                        </div>

                        <div class="flex items-center justify-end pt-1">
                            <a id="forgotPasswordLink"
                                @if (!$isForgotInitiallyDisabled)
                                    href="{{ route('password.request') }}"
                                @endif
                                aria-disabled="{{ $isForgotInitiallyDisabled ? 'true' : 'false' }}"
                                title="{{ $isForgotInitiallyDisabled ? 'Disabled: Your staff account has been revoked. Wait for your Head of Unit to activate it first.' : '' }}"
                                onclick="openForgotPasswordPage(event)"
                                class="{{ $isForgotInitiallyDisabled ? 'inline-flex items-center gap-1 text-xs font-semibold text-slate-400 cursor-not-allowed select-none opacity-65' : 'inline-flex items-center gap-1 auth-link text-xs' }}">
                                <svg id="forgotPasswordLockIcon"
                                    style="{{ $isForgotInitiallyDisabled ? 'display: inline-block;' : 'display: none;' }}"
                                    class="{{ $isForgotInitiallyDisabled ? '' : 'hidden' }} w-3.5 h-3.5 text-rose-500 shrink-0"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <span>Forgot password?</span>
                            </a>
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

    <!-- ACCOUNT REVOKED POPOVER MODAL (5 FAILED ATTEMPTS / UNIT HEAD LOCKOUT) -->
    <div id="loginAccountRevokedPopoverModal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="popoverRevokedTitle"
        class="hidden fixed inset-0 z-50 bg-slate-900/65 backdrop-blur-md flex items-center justify-center p-4 transition-all duration-300"
        style="backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);">
        <div class="bg-white rounded-3xl border border-rose-200 shadow-2xl max-w-lg w-full overflow-hidden">
            <!-- HEADER BANNER -->
            <div class="px-6 py-5 bg-gradient-to-r from-rose-700 via-rose-600 to-red-700 text-white flex items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-2xl bg-white/15 border border-white/25 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[10px] font-mono-code font-extrabold uppercase tracking-widest text-rose-100">
                            Security Lockout &bull; Account Revoked
                        </div>
                        <h3 id="popoverRevokedTitle" class="text-base font-extrabold tracking-tight mt-0.5">
                            Staff Account Automatically Revoked
                        </h3>
                    </div>
                </div>
                <button type="button" onclick="closeLoginRevokedPopover()" aria-label="Close dialog"
                    class="w-8 h-8 rounded-full bg-white/15 hover:bg-white/25 text-white flex items-center justify-center text-lg font-bold cursor-pointer border-0 transition-colors">
                    &times;
                </button>
            </div>

            <!-- BODY -->
            <div class="p-6 sm:p-7 space-y-4 text-slate-700">
                <p id="popoverRevokedDescription" class="text-xs sm:text-sm leading-relaxed text-slate-700">
                    Your staff account has been automatically revoked after <strong class="text-rose-700">5 failed sign-in attempts</strong>. Sign-in access is disabled until your Head of Unit activates your account.
                </p>

                <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 space-y-1.5">
                    <div class="text-[10px] font-mono-code font-extrabold uppercase tracking-wider text-amber-800">
                        Action Required to Activate Account
                    </div>
                    <p id="popoverRevokedHeadInstruction" class="text-xs text-amber-950 font-semibold leading-relaxed">
                        Please see your Head of Unit to activate your account first before attempting to sign in again.
                    </p>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-slate-100 gap-3">
                    <div class="inline-flex items-center gap-2 text-[11px] font-mono-code font-semibold text-slate-500">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                        <span>Waiting for Head of Unit activation...</span>
                    </div>
                    <button type="button" onclick="closeLoginRevokedPopover()"
                        class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold font-mono-code transition-colors cursor-pointer border-0">
                        Understood
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const PENDING_LOGIN_STORAGE_KEY = 'nacoc_pending_login_id';
        const REVOKED_LOGIN_STORAGE_KEY = 'nacoc_revoked_login_id';
        const FORGOT_BASE_URL = '{{ route('password.request') }}';
        const CHECK_APPROVAL_ENDPOINT = '{{ url('/api/check-user-approval') }}';
        const INITIAL_PENDING_LOGIN_ID = @json(session('pending_login_id'));
        const PAGE_LOAD_TS = Math.floor(Date.now() / 1000);
        let approvalPollTimer = null;
        let isPollingApproval = false;
        let shownApprovalEventId = null;
        let isForgotPasswordDisabled = Boolean(@json($isForgotInitiallyDisabled));
        let lastRevokedPayload = @json($revokedPayloadInit);

        function setForgotPasswordRevokedState(isRevoked, payload = null) {
            isForgotPasswordDisabled = Boolean(isRevoked);
            const link = document.getElementById('forgotPasswordLink');
            const lockIcon = document.getElementById('forgotPasswordLockIcon');

            if (isForgotPasswordDisabled) {
                if (payload) {
                    lastRevokedPayload = payload;
                }
                const storedId = (payload && (payload.service_id || payload.login_id || payload.username))
                    || (document.getElementById('serviceId')?.value || '').trim();
                if (storedId) {
                    try {
                        localStorage.setItem(REVOKED_LOGIN_STORAGE_KEY, storedId);
                    } catch (e) {}
                }
                if (link) {
                    link.removeAttribute('href');
                    link.setAttribute('aria-disabled', 'true');
                    link.setAttribute('title', 'Disabled: Your staff account has been revoked. Wait for your Head of Unit to activate it first.');
                    link.className = 'inline-flex items-center gap-1 text-xs font-semibold text-slate-400 cursor-not-allowed select-none opacity-65';
                }
                if (lockIcon) {
                    lockIcon.classList.remove('hidden');
                    lockIcon.style.display = 'inline-block';
                }
            } else {
                lastRevokedPayload = null;
                try {
                    localStorage.removeItem(REVOKED_LOGIN_STORAGE_KEY);
                } catch (e) {}
                if (link) {
                    link.setAttribute('href', FORGOT_BASE_URL);
                    link.setAttribute('aria-disabled', 'false');
                    link.removeAttribute('title');
                    link.className = 'inline-flex items-center gap-1 auth-link text-xs';
                }
                if (lockIcon) {
                    lockIcon.style.display = 'none';
                    lockIcon.classList.add('hidden');
                }
            }
        }

        function showLoginApprovedPopover(userPayload = null) {
            // Re-enable Forgot Password immediately once the Head activates the staff account
            setForgotPasswordRevokedState(false);

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

            if (officerNameEl && officerLabel) {
                officerNameEl.textContent = officerLabel;
                officerNameEl.classList.remove('hidden');
            }

            if (serviceIdInput && !serviceIdInput.value.trim() && userPayload) {
                serviceIdInput.value = userPayload.service_id || userPayload.username || '';
            }

            const flashContainer = document.getElementById('topRightFlashContainer');
            if (flashContainer) flashContainer.innerHTML = '';

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
            const storedId = (localStorage.getItem(PENDING_LOGIN_STORAGE_KEY) || localStorage.getItem(REVOKED_LOGIN_STORAGE_KEY) || '').trim();
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
                if (data && data.exists && data.is_revoked) {
                    setForgotPasswordRevokedState(true, data);
                    return;
                }

                if (data && data.exists && data.is_approved) {
                    setForgotPasswordRevokedState(false);
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
            const pendingId = (localStorage.getItem(PENDING_LOGIN_STORAGE_KEY) || localStorage.getItem(REVOKED_LOGIN_STORAGE_KEY) || '').trim().toLowerCase();
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
            if (val.length < 3) {
                const lockedRevokedId = (localStorage.getItem(REVOKED_LOGIN_STORAGE_KEY) || '').trim();
                if (!lockedRevokedId) {
                    setForgotPasswordRevokedState(false);
                }
                return;
            }
            try {
                const res = await fetch(`${CHECK_APPROVAL_ENDPOINT}?login_id=${encodeURIComponent(val)}`, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                });
                if (!res.ok) return;
                const data = await res.json();
                if (data && data.exists) {
                    if (data.is_revoked) {
                        setForgotPasswordRevokedState(true, data);
                        startApprovalPolling(data.service_id || data.username || val);
                    } else {
                        setForgotPasswordRevokedState(false);
                        if (!data.is_approved) {
                            startApprovalPolling(data.service_id || data.username || val);
                        }
                    }
                } else {
                    const lockedRevokedId = (localStorage.getItem(REVOKED_LOGIN_STORAGE_KEY) || '').trim();
                    if (!lockedRevokedId) {
                        setForgotPasswordRevokedState(false);
                    }
                }
            } catch (e) {}
        }

        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const prefilledLogin = urlParams.get('login_id');
            if (prefilledLogin) {
                const sidEl = document.getElementById('serviceId');
                if (sidEl && !sidEl.value.trim()) {
                    sidEl.value = prefilledLogin.trim();
                }
            }
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

            const storedRevokedId = (localStorage.getItem(REVOKED_LOGIN_STORAGE_KEY) || '').trim();
            const initialId = INITIAL_PENDING_LOGIN_ID || storedRevokedId || localStorage.getItem(PENDING_LOGIN_STORAGE_KEY) || '';
            if (isForgotPasswordDisabled && lastRevokedPayload) {
                setForgotPasswordRevokedState(true, lastRevokedPayload);
            }
            if (initialId) {
                startApprovalPolling(initialId);
            } else {
                startApprovalPolling('');
            }

            const serviceIdInput = document.getElementById('serviceId');
            if (serviceIdInput) {
                let typedWatchTimer = null;
                const scheduleWatch = () => {
                    clearTimeout(typedWatchTimer);
                    typedWatchTimer = setTimeout(() => watchTypedServiceIdForPendingApproval(serviceIdInput.value), 320);
                };
                serviceIdInput.addEventListener('input', scheduleWatch);
                serviceIdInput.addEventListener('change', scheduleWatch);
                if (serviceIdInput.value && serviceIdInput.value.trim().length >= 3) {
                    watchTypedServiceIdForPendingApproval(serviceIdInput.value);
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

            const loginId = serviceIdInput ? serviceIdInput.value.trim() : '';
            const password = passwordInput ? passwordInput.value : '';

            if (!loginId || !password) {
                showErrorToast('Please enter both your Service ID / Username and Password.');
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
                    setForgotPasswordRevokedState(false);
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
                    btnText.innerHTML = `Sign in to FIMS`;
                    const errMsg = data.message || (data.errors ? Object.values(data.errors).flat()[0] : 'Wrong username or password.');

                    if (data.pending_approval) {
                        startApprovalPolling(data.login_id || loginId);
                    }

                    if (data.account_revoked) {
                        setForgotPasswordRevokedState(true, data);
                        showLoginRevokedPopover(data);
                    }

                    showErrorToast(errMsg, Boolean(data.account_revoked));
                }
            } catch (err) {
                form.submit();
            }
        }

        function showLoginRevokedPopover(payload = {}) {
            setForgotPasswordRevokedState(true, payload);

            const modal = document.getElementById('loginAccountRevokedPopoverModal');
            if (!modal) return;

            const approvedModal = document.getElementById('loginAccountApprovedPopoverModal');
            if (approvedModal) approvedModal.classList.add('hidden');

            const titleEl = document.getElementById('popoverRevokedTitle');
            const descEl = document.getElementById('popoverRevokedDescription');
            const headInstEl = document.getElementById('popoverRevokedHeadInstruction');

            const officerId = payload.service_id || payload.login_id || 'Staff Account';
            const officerName = payload.fullname ? `${payload.fullname} (${officerId})` : officerId;
            const unitLabel = payload.unit || 'your assigned unit';
            const headLabel = payload.unit_head_name
                ? `${payload.unit_head_name} — Head of ${unitLabel}`
                : `the Head of ${unitLabel}`;

            if (titleEl) {
                titleEl.textContent = payload.revoked_reason === 'manual'
                    ? 'Staff Account Revoked'
                    : 'Staff Account Automatically Revoked';
            }

            if (descEl) {
                descEl.textContent = '';
                if (payload.revoked_reason === 'manual') {
                    descEl.append(`Your staff account (${officerName}) for ${unitLabel} has been revoked. Sign-in and password reset access are disabled until your Head of Unit activates it.`);
                } else {
                    descEl.append(`Your staff account (${officerName}) for ${unitLabel} has been automatically revoked after 5 failed sign-in attempts. Sign-in and password reset access are disabled until your Head of Unit activates your account.`);
                }
            }

            if (headInstEl) {
                headInstEl.textContent = `Please see ${headLabel} to activate your account first before signing in or resetting your password.`;
            }

            const pwdEl = document.getElementById('passwordInput');
            if (pwdEl) pwdEl.value = '';

            modal.classList.remove('hidden');
        }

        function closeLoginRevokedPopover() {
            const modal = document.getElementById('loginAccountRevokedPopoverModal');
            if (modal) modal.classList.add('hidden');
        }

        // Automatically dismiss the Revoked modal and unlock Forgot Password the instant Head of Unit approval arrives
        (function bindRevokedApprovalAutoClose() {
            const approvedModal = document.getElementById('loginAccountApprovedPopoverModal');
            if (!approvedModal || typeof MutationObserver === 'undefined') return;
            const obs = new MutationObserver(() => {
                if (!approvedModal.classList.contains('hidden')) {
                    setForgotPasswordRevokedState(false);
                    closeLoginRevokedPopover();
                }
            });
            obs.observe(approvedModal, { attributes: true, attributeFilter: ['class'] });
        })();

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLoginRevokedPopover();
            }
        });

        @if (session('revoked_staff_payload'))
        document.addEventListener('DOMContentLoaded', function() {
            showLoginRevokedPopover(@json(session('revoked_staff_payload')));
        });
        @endif

        function showErrorToast(msg, isRevoked = false) {
            const existingContainer = document.getElementById('topRightFlashContainer');
            if (existingContainer) {
                existingContainer.innerHTML = '';
            }
            const title = isRevoked ? 'Account Revoked' : 'Security & Authentication Alert';
            if (typeof window.showFlashToast === 'function') {
                window.showFlashToast(msg, 'error', title);
            }
        }

        async function openForgotPasswordPage(e) {
            e.preventDefault();

            if (isForgotPasswordDisabled) {
                const headText = (lastRevokedPayload && lastRevokedPayload.unit_head_name)
                    ? `${lastRevokedPayload.unit_head_name} (Head of ${lastRevokedPayload.unit || 'your unit'})`
                    : 'your Head of Unit';
                showErrorToast(`Forgot password is disabled while your staff account is revoked. Please see ${headText} to activate your account first.`, true);
                if (lastRevokedPayload) {
                    showLoginRevokedPopover(lastRevokedPayload);
                }
                return false;
            }

            const typedId = (document.getElementById('serviceId')?.value || '').trim();
            if (typedId.length >= 2) {
                try {
                    const res = await fetch(`${CHECK_APPROVAL_ENDPOINT}?login_id=${encodeURIComponent(typedId)}`, {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin'
                    });
                    if (res.ok) {
                        const data = await res.json();
                        if (data && data.exists && data.is_revoked) {
                            setForgotPasswordRevokedState(true, data);
                            startApprovalPolling(data.service_id || data.username || typedId);
                            const headLabel = data.unit_head_name
                                ? `${data.unit_head_name} (Head of ${data.unit || 'your unit'})`
                                : 'your Head of Unit';
                            showErrorToast(`Forgot password is disabled while your staff account is revoked. Please see ${headLabel} to activate your account first.`, true);
                            showLoginRevokedPopover(data);
                            return false;
                        }
                    }
                } catch (err) {}
            }

            const targetUrl = typedId
                ? `${FORGOT_BASE_URL}?login_id=${encodeURIComponent(typedId)}`
                : FORGOT_BASE_URL;
            navigateWithFade(e, targetUrl);
            return false;
        }
    </script>
</body>

</html>
