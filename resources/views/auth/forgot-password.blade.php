<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Forgot Password | Forensic Information Management System (FIMS)</title>

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
    $dispatchedMaskedEmail = session('reset_dispatched_email');
    $pendingResetRequestId = session('pending_reset_request_id');
    $pendingMaskedEmail = session('pending_masked_email');
    $pendingUnitHead = session('pending_unit_head');
    $otpAcctInit = session('otp_acct', '');
    $needsRecoveryEmailInit = (bool) session('needs_recovery_email', false);
    $maskedPhoneInit = session('masked_phone', '••• ••• ••••');
    $initialLoginValue = old('login_id', $prefillLoginId ?? '');
    $isAwaitingApprovalInit = !empty($pendingResetRequestId) && empty($dispatchedMaskedEmail);
@endphp

<body class="antialiased selection:bg-teal-700 selection:text-white transition-opacity duration-300 opacity-100">

    <div class="auth-shell">
        @include('auth.partials.auth_brand', ['mode' => 'forgot'])

        <main class="auth-panel auth-panel--login">
            <div class="flex items-center justify-between gap-3">
                <a href="{{ url('/login') }}"
                    onclick="navigateWithFade(event, '{{ url('/login') }}')"
                    class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-teal-800 transition-colors py-1.5 px-2.5 rounded-lg hover:bg-white/80">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Back to sign in</span>
                </a>

                <span class="auth-switch">Remembered it?
                    <a href="{{ url('/login') }}"
                        onclick="navigateWithFade(event, '{{ url('/login') }}')">Sign in</a>
                </span>
            </div>

            <div class="auth-panel__inner auth-panel__inner--recovery">
                <div class="auth-header mb-6">
                    <img src="{{ asset('NACOC_LOGO.PNG') }}" alt="NACOC crest" width="152" height="152"
                        class="auth-logo auth-logo--compact">
                    <h1 class="auth-title" id="forgotPageHeading">
                        @if ($dispatchedMaskedEmail)
                            Enter OTP code
                        @elseif ($isAwaitingApprovalInit)
                            Awaiting Head approval
                        @else
                            Forgot password?
                        @endif
                    </h1>
                    <p class="auth-sub max-w-md" id="forgotPageSub">
                        @if ($dispatchedMaskedEmail)
                            Enter the 6-digit verification code sent to your registered Gmail account.
                        @elseif ($isAwaitingApprovalInit)
                            Your password reset request has been sent to your Head of Unit. Once approved, your 6-digit OTP code will be sent to your Gmail.
                        @else
                            Enter your registered Gmail address, username, or Service ID to send a password reset request to your Head of Unit.
                        @endif
                    </p>
                </div>

                <div class="auth-card auth-card--recovery">
                    @include('partials.flash')

                    <!-- 3-STAGE OTP RECOVERY PROGRESS RAIL -->
                    <div class="recovery-rail" aria-label="Password recovery progress">
                        <div id="railStep1" class="recovery-step {{ $dispatchedMaskedEmail ? 'is-done' : 'is-active' }}">
                            <span class="recovery-step__num font-mono-code" id="railStep1Num">
                                @if ($dispatchedMaskedEmail)
                                    &#10003;
                                @else
                                    01
                                @endif
                            </span>
                            <span class="truncate">Request</span>
                        </div>
                        <div id="railStep2" class="recovery-step {{ $dispatchedMaskedEmail ? 'is-active' : '' }}">
                            <span class="recovery-step__num font-mono-code" id="railStep2Num">02</span>
                            <span class="truncate">Verify OTP</span>
                        </div>
                        <div id="railStep3" class="recovery-step">
                            <span class="recovery-step__num font-mono-code" id="railStep3Num">03</span>
                            <span class="truncate">New Password</span>
                        </div>
                    </div>

                    <!-- INLINE ERROR ALERT -->
                    <div id="forgotInlineError"
                        class="hidden mb-5 p-3.5 bg-red-50/95 border border-red-200 text-red-700 text-xs font-semibold rounded-xl flex items-start gap-2.5 shadow-xs"
                        role="alert">
                        <svg class="w-4 h-4 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span id="forgotInlineErrorText"></span>
                    </div>

                    <!-- ========================================================= -->
                    <!-- STEP 1: IDENTIFY OFFICER & SEND PASSWORD RESET REQUEST    -->
                    <!-- ========================================================= -->
                    <div id="step1IdentifyWrapper" class="{{ ($dispatchedMaskedEmail || $isAwaitingApprovalInit) ? 'hidden' : '' }}">
                        <form id="forgotPasswordForm" method="POST" action="{{ route('password.email') }}"
                            onsubmit="handleSendOtpSubmit(event)" class="space-y-5">
                            @csrf

                            <div class="ref-input-line !py-3.5 !px-4">
                                <div class="flex items-center justify-between gap-2 mb-0.5">
                                    <label for="loginIdInput" class="!mb-0">
                                        <svg class="w-3.5 h-3.5 text-teal-700 inline shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        <span>Registered Gmail, username or service ID</span>
                                    </label>
                                    <span id="detectedTypeBadge"
                                        class="hidden px-2 py-0.5 rounded-md bg-teal-50 border border-teal-200 text-[10px] font-extrabold font-mono-code text-teal-800 uppercase tracking-wider">
                                        GMAIL
                                    </span>
                                </div>
                                <div class="relative flex items-center">
                                    <input type="text" id="loginIdInput" name="login_id" required
                                        autocomplete="username"
                                        value="{{ $initialLoginValue }}"
                                        oninput="updateDetectedIdentifierBadge(this.value)"
                                        placeholder="officer@gmail.com, smensah or SD-345"
                                        class="w-full py-1 bg-transparent font-semibold text-slate-900 placeholder-slate-400 focus:outline-none">
                                </div>
                                @error('login_id')
                                    <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- STEP-UP SECTION FOR ACCOUNTS REGISTERED WITHOUT A GMAIL ADDRESS -->
                            <div id="noEmailVerifySection"
                                class="{{ $needsRecoveryEmailInit ? '' : 'hidden' }} space-y-4 pt-1">
                                <div class="p-3.5 rounded-2xl bg-amber-50/90 border border-amber-200/90 text-xs text-amber-950 space-y-1.5">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-extrabold uppercase tracking-wider font-mono-code text-[10.5px] text-amber-900 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-amber-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                            </svg>
                                            <span>Verify Officer Contact</span>
                                        </span>
                                        <span id="verifyOfficerBadge" class="px-2 py-0.5 rounded bg-white border border-amber-300 font-mono-code font-bold text-[10px] text-slate-800">
                                            FIMS RECORD
                                        </span>
                                    </div>
                                    <p class="text-[11.5px] text-amber-900/90 leading-relaxed" id="noEmailVerifyPrompt">
                                        No email address is linked to this officer account yet. Confirm your registered phone number (<span id="maskedPhoneHint" class="font-mono-code font-bold">{{ $maskedPhoneInit }}</span>) and provide the Gmail address where your OTP code should be sent.
                                    </p>
                                </div>

                                <div class="ref-input-line !py-3 !px-4">
                                    <label for="verifyPhoneInput" class="block">
                                        <svg class="w-3.5 h-3.5 text-teal-700 inline shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                        <span>Registered 10-digit phone number</span>
                                    </label>
                                    <input type="tel" id="verifyPhoneInput" name="phone_number"
                                        maxlength="10" inputmode="numeric"
                                        value="{{ old('phone_number') }}"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)"
                                        placeholder="0200000000"
                                        class="w-full py-1 bg-transparent font-semibold font-mono-code text-slate-900 placeholder-slate-400 focus:outline-none">
                                    @error('phone_number')
                                        <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="ref-input-line !py-3 !px-4">
                                    <label for="recoveryEmailInput" class="block">
                                        <svg class="w-3.5 h-3.5 text-teal-700 inline shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        <span>Gmail address for OTP code</span>
                                    </label>
                                    <input type="email" id="recoveryEmailInput" name="recovery_email"
                                        autocomplete="email"
                                        value="{{ old('recovery_email') }}"
                                        placeholder="officer@gmail.com"
                                        class="w-full py-1 bg-transparent font-semibold text-slate-900 placeholder-slate-400 focus:outline-none">
                                    @error('recovery_email')
                                        <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <button type="submit" id="sendOtpSubmitBtn" class="auth-cta mt-2">
                                <svg class="w-4 h-4 text-white/90 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                <span id="sendOtpBtnText">
                                    {{ $needsRecoveryEmailInit ? 'Verify & send reset request' : 'Send password reset request' }}
                                </span>
                            </button>
                        </form>

                        <div class="mt-5 pt-4 border-t border-slate-100 flex items-start gap-2.5 text-[11.5px] text-slate-500 leading-relaxed">
                            <svg class="w-4 h-4 text-teal-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <span>
                                Submitting sends a <strong class="text-slate-700">password reset request</strong> to your Head of Unit. Once approved, a single-use <strong class="text-slate-700">6-digit OTP code</strong> (valid for <strong class="text-slate-700">5 minutes</strong>) will be sent to your registered Gmail.
                            </span>
                        </div>
                    </div>

                    <!-- ========================================================= -->
                    <!-- STEP 1B: AWAITING HEAD OF UNIT APPROVAL                   -->
                    <!-- ========================================================= -->
                    <div id="step1bPendingApprovalWrapper" class="{{ $isAwaitingApprovalInit ? '' : 'hidden' }} space-y-5">
                        <input type="hidden" id="pendingResetRequestIdInput" value="{{ $pendingResetRequestId ?? '' }}">

                        <div class="p-4 rounded-2xl bg-amber-50/85 border border-amber-200 flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-amber-600 text-white flex items-center justify-center shrink-0 shadow-sm shadow-amber-600/20 mt-0.5">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="min-w-0 space-y-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-[10.5px] font-mono-code font-extrabold uppercase tracking-wider text-amber-900">
                                            Request Sent to Head of Unit
                                        </span>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-white border border-amber-300 font-mono-code text-[10px] font-bold text-amber-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                            <span>Live Sync</span>
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-700 leading-relaxed">
                                        Your Head of Unit must approve this password reset request on their <strong class="text-slate-900">Password</strong> console before your 6-digit OTP is dispatched.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/90 space-y-2.5 text-xs">
                            <div class="flex items-center justify-between gap-2 pb-2 border-b border-slate-200/70">
                                <span class="text-slate-500 font-medium">Approving Authority</span>
                                <strong id="pendingUnitHeadLabel" class="text-slate-900 font-bold text-right">{{ $pendingUnitHead ?? 'Head of Unit' }}</strong>
                            </div>
                            <div class="flex items-center justify-between gap-2 pb-2 border-b border-slate-200/70">
                                <span class="text-slate-500 font-medium">Officer Account</span>
                                <span id="pendingOfficerBadge" class="font-mono-code font-bold text-teal-800 bg-white px-2 py-0.5 rounded border border-slate-200">FIMS Staff</span>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-slate-500 font-medium">Gmail for OTP Delivery</span>
                                <strong id="pendingEmailBadge" class="font-mono-code text-slate-900 bg-white px-2 py-0.5 rounded border border-teal-200">{{ $pendingMaskedEmail ?? '' }}</strong>
                            </div>
                        </div>

                        <div class="p-3.5 rounded-xl bg-teal-50/60 border border-teal-200/70 flex items-center gap-2.5 text-[11.5px] text-teal-900">
                            <svg class="animate-spin h-4 w-4 text-teal-700 shrink-0" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span id="pendingPollStatusText">
                                Waiting for Head of Unit approval — this page will open the OTP keypad automatically as soon as your request is approved.
                            </span>
                        </div>

                        <div class="space-y-2.5">
                            <button type="button" id="manualCheckStatusBtn" onclick="pollResetRequestStatus(true)" class="auth-secondary-btn">
                                <svg class="w-4 h-4 text-teal-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <span id="manualCheckStatusBtnText">Check approval status now</span>
                            </button>

                            <div class="text-center pt-1">
                                <button type="button" onclick="switchToStep1()"
                                    class="text-xs font-bold text-slate-500 hover:text-teal-800 cursor-pointer bg-transparent border-0 underline-offset-2 hover:underline">
                                    Entered the wrong Gmail or Service ID? Change account
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================================= -->
                    <!-- STEP 2: ENTER 6-DIGIT GMAIL OTP CODE                      -->
                    <!-- ========================================================= -->
                    <div id="step2VerifyOtpWrapper" class="{{ $dispatchedMaskedEmail ? '' : 'hidden' }} space-y-5">
                        <div class="p-4 rounded-2xl bg-teal-50/75 border border-teal-200/90 flex items-center justify-between gap-3 flex-wrap">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-teal-700 text-white flex items-center justify-center shrink-0 shadow-sm shadow-teal-700/25">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <div id="otpSentBannerTitle" class="text-[10.5px] font-mono-code font-extrabold uppercase tracking-wider text-teal-800">
                                        OTP Sent to Registered Gmail
                                    </div>
                                    <p class="text-xs text-slate-700 mt-0.5 truncate">
                                        Code dispatched to <strong id="dispatchedEmailBadge" class="font-mono-code text-slate-900 bg-white px-1.5 py-0.5 rounded border border-teal-200">{{ $dispatchedMaskedEmail ?? '' }}</strong>
                                    </p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-lg bg-white border border-teal-200 font-mono-code text-[11px] font-bold text-teal-800 shrink-0">
                                Valid 5 min
                            </span>
                        </div>

                        <form id="verifyOtpForm" onsubmit="event.preventDefault(); maybeAutoVerifyOtp();" class="space-y-5">
                            <input type="hidden" id="otpAcctInput" value="{{ $otpAcctInit }}">

                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label for="otpDigit0" class="text-xs font-extrabold uppercase tracking-wider text-slate-600 font-mono-code">
                                        6-Digit Verification Code
                                    </label>
                                    <button type="button" id="clearOtpDigitsBtn" onclick="clearOtpDigits()"
                                        class="text-[11px] font-bold text-slate-400 hover:text-teal-700 bg-transparent border-0 p-0 cursor-pointer">
                                        Clear digits
                                    </button>
                                </div>

                                <div id="otpGridContainer" class="otp-grid" role="group" aria-label="6-digit OTP verification code">
                                    @for ($i = 0; $i < 6; $i++)
                                        <input type="text"
                                            id="otpDigit{{ $i }}"
                                            data-otp-index="{{ $i }}"
                                            inputmode="numeric"
                                            pattern="[0-9]*"
                                            maxlength="1"
                                            autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}"
                                            aria-label="Digit {{ $i + 1 }}"
                                            class="otp-digit-input">
                                    @endfor
                                </div>

                                <!-- LIVE AUTO-VERIFICATION STATUS BAR -->
                                <div id="otpAutoStatusRow" class="mt-3 min-h-[22px] flex items-center justify-between gap-2 text-xs">
                                    <span id="otpHelperHint" class="text-[11.5px] text-slate-500">
                                        Tip: Paste or enter all 6 digits from your Gmail message.
                                    </span>
                                    <span id="otpVerifyingIndicator" style="display: none;" class="hidden items-center gap-2 font-semibold text-teal-800">
                                        <svg class="animate-spin h-3.5 w-3.5 text-teal-700 shrink-0" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span id="otpVerifyingText">Verifying code...</span>
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-2.5 pt-1">
                                <button type="button" id="resendOtpBtn" onclick="resendOtpCode()" class="auth-secondary-btn">
                                    <svg class="w-4 h-4 text-teal-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span id="resendOtpBtnText">Resend OTP code</span>
                                </button>
                            </div>

                            <div class="text-center pt-1">
                                <button type="button" onclick="switchToStep1()"
                                    class="text-xs font-bold text-slate-500 hover:text-teal-800 cursor-pointer bg-transparent border-0 underline-offset-2 hover:underline">
                                    Entered the wrong Gmail or Service ID? Change account
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- ========================================================= -->
                    <!-- STEP 3: SET NEW PASSWORD AFTER OTP VERIFICATION           -->
                    <!-- ========================================================= -->
                    <div id="step3NewPasswordWrapper" class="hidden space-y-5">
                        <!-- VERIFIED OFFICER DOSSIER PILL -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/90 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-[10px] font-mono-code font-bold uppercase tracking-wider text-teal-800">
                                    OTP Verified &bull; Officer Account
                                </div>
                                <div id="verifiedOfficerName" class="text-xs font-extrabold text-slate-900 truncate mt-0.5">
                                    Officer
                                </div>
                                <div id="verifiedOfficerUnit" class="text-[11px] text-slate-500 truncate">
                                    Forensic Division
                                </div>
                            </div>
                            <span id="verifiedOfficerBadge" class="px-2.5 py-1 rounded-lg bg-teal-700 text-white font-mono-code text-xs font-extrabold shrink-0 shadow-xs">
                                FIMS
                            </span>
                        </div>

                        <form id="setNewPasswordForm" method="POST" action="{{ route('password.update') }}"
                            onsubmit="handleSetNewPasswordSubmit(event)" class="space-y-4">
                            @csrf
                            <input type="hidden" id="verifiedResetTokenInput" name="token" value="">
                            <input type="hidden" id="verifiedAcctInput" name="acct" value="">

                            <div class="ref-input-line !py-3 !px-4">
                                <label for="newPasswordInput" class="block">
                                    <svg class="w-3.5 h-3.5 text-teal-700 inline shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    <span>New password</span>
                                </label>
                                <div class="relative flex items-center">
                                    <input type="password" id="newPasswordInput" name="password" required
                                        minlength="8" autocomplete="new-password"
                                        oninput="evaluateNewPasswordStrength(this.value); evaluateNewPasswordMatch();"
                                        placeholder="Minimum 8 characters"
                                        class="w-full py-1 bg-transparent font-semibold text-slate-900 placeholder-slate-400 focus:outline-none pr-9">
                                    <button type="button" onclick="togglePasswordField('newPasswordInput')"
                                        class="absolute right-0 p-1 text-slate-400 hover:text-teal-700 transition-colors cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 rounded-lg"
                                        aria-label="Toggle new password visibility">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="ref-input-line !py-3 !px-4">
                                <label for="confirmPasswordInput" class="block">
                                    <svg class="w-3.5 h-3.5 text-teal-700 inline shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <span>Confirm new password</span>
                                </label>
                                <div class="relative flex items-center">
                                    <input type="password" id="confirmPasswordInput" name="password_confirmation" required
                                        minlength="8" autocomplete="new-password"
                                        oninput="evaluateNewPasswordMatch()"
                                        placeholder="Re-enter your new password"
                                        class="w-full py-1 bg-transparent font-semibold text-slate-900 placeholder-slate-400 focus:outline-none pr-9">
                                    <button type="button" onclick="togglePasswordField('confirmPasswordInput')"
                                        class="absolute right-0 p-1 text-slate-400 hover:text-teal-700 transition-colors cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-700 rounded-lg"
                                        aria-label="Toggle confirm password visibility">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                </div>
                                <div id="newPasswordMatchNotice" class="hidden text-[11px] font-mono-code mt-1 font-bold"></div>
                            </div>

                            <!-- FIMS PASSWORD STRENGTH & SECURITY POLICY METER -->
                            <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                                <div class="flex items-center justify-between text-[11px] font-mono-code">
                                    <span class="text-slate-600 font-semibold">Password Strength:</span>
                                    <span id="newStrengthLabel" class="font-bold text-slate-400">Enter password</span>
                                </div>

                                <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                    <div id="newStrengthBar"
                                        class="h-1.5 w-0 bg-slate-400 transition-all duration-300 rounded-full"></div>
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-5 gap-1 pt-0.5 text-[10px] font-mono-code">
                                    <div id="polLen" class="px-1.5 py-0.5 rounded bg-slate-200 text-slate-500 font-semibold text-center transition-colors">8+ Chars</div>
                                    <div id="polCaps" class="px-1.5 py-0.5 rounded bg-slate-200 text-slate-500 font-semibold text-center transition-colors">Caps (A-Z)</div>
                                    <div id="polNum" class="px-1.5 py-0.5 rounded bg-slate-200 text-slate-500 font-semibold text-center transition-colors">Num (0-9)</div>
                                    <div id="polSym" class="px-1.5 py-0.5 rounded bg-slate-200 text-slate-500 font-semibold text-center transition-colors">Symbol</div>
                                    <div id="polNoId" class="col-span-2 sm:col-span-1 px-1.5 py-0.5 rounded bg-slate-200 text-slate-500 font-semibold text-center transition-colors">No Name/User</div>
                                </div>
                            </div>

                            <button type="submit" id="savePasswordSubmitBtn" class="auth-cta mt-2">
                                <svg class="w-4 h-4 text-white/90 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                <span id="savePasswordBtnText">Save new password &amp; sign in</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500">
                <span>Official system of the Narcotics Control Commission, Ghana</span>
                <button type="button" onclick="openHelpdeskModal()"
                    class="auth-link bg-transparent border-0 p-0 cursor-pointer text-[11px]">
                    Need help?
                </button>
            </div>
        </main>
    </div>

    <!-- IT COMMAND HELPDESK MODAL -->
    <div id="helpdeskModal" role="dialog" aria-modal="true" aria-labelledby="helpdeskModalTitle"
        class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-md w-full p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3.5">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-teal-50 border border-teal-200 text-teal-800 flex items-center justify-center font-mono-code font-extrabold text-xs">
                        IT
                    </div>
                    <div>
                        <h2 id="helpdeskModalTitle" class="text-sm font-extrabold text-slate-900">NACOC IT Command Desk</h2>
                        <p class="text-[11px] text-slate-500">OTP &amp; Credential Assistance</p>
                    </div>
                </div>
                <button type="button" onclick="closeHelpdeskModal()" aria-label="Close help dialog"
                    class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center font-bold cursor-pointer border-0">
                    &times;
                </button>
            </div>

            <div class="space-y-2.5 text-xs text-slate-600 leading-relaxed">
                <p>Staff password resets require approval from your <strong class="text-slate-800">Head of Unit</strong> before your 6-digit OTP is emailed. If you cannot access your registered Gmail account, contact the NACOC IT Command Helpdesk:</p>
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 font-mono-code text-xs space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Internal Extension:</span>
                        <strong class="text-teal-800">Ext. 4022</strong>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Desk Hours:</span>
                        <strong class="text-slate-800">24/7 Duty Operations</strong>
                    </div>
                </div>
            </div>

            <button type="button" onclick="closeHelpdeskModal()" class="auth-secondary-btn">
                Close
            </button>
        </div>
    </div>

    <script>
        const VERIFY_OTP_ENDPOINT = '{{ route('password.otp.verify') }}';
        const CHECK_REQUEST_STATUS_ENDPOINT = '{{ url('/forgot-password/request-status') }}';
        let resendCooldownTimer = null;
        let approvalPollTimer = null;
        let isPollingApproval = false;
        let isVerifyingOtp = false;
        let verifiedOfficerUsername = '';
        let verifiedOfficerFullname = '';

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

        function navigateWithFade(e, url) {
            e.preventDefault();
            document.body.classList.remove('opacity-100');
            document.body.classList.add('opacity-0');
            setTimeout(() => {
                window.location.href = url;
            }, 220);
        }

        function broadcastPasswordResetSync() {
            const ts = String(Date.now());
            try {
                localStorage.setItem('nacoc_password_reset_sync', ts);
                localStorage.setItem('nacoc_badge_sync', ts);
            } catch (e) {}
            if (typeof BroadcastChannel !== 'undefined') {
                try {
                    const ch = new BroadcastChannel('nacoc_password_reset');
                    ch.postMessage({ type: 'password_reset_updated', ts });
                    ch.close();
                } catch (e) {}
            }
        }

        function stopApprovalPolling() {
            if (approvalPollTimer) {
                clearInterval(approvalPollTimer);
                approvalPollTimer = null;
            }
        }

        function startApprovalPolling() {
            stopApprovalPolling();
            approvalPollTimer = setInterval(() => {
                pollResetRequestStatus(false);
            }, 2500);
        }

        function updateDetectedIdentifierBadge(raw) {
            const badge = document.getElementById('detectedTypeBadge');
            if (!badge) return;
            const val = String(raw || '').trim();
            if (val.length < 2) {
                badge.classList.add('hidden');
                return;
            }

            if (/^(SD|JD|SVC)-[0-9]+$/i.test(val) || /^(SD|JD|SVC)-/i.test(val)) {
                badge.textContent = 'SERVICE ID';
                badge.classList.remove('hidden');
            } else if (val.toLowerCase().includes('@gmail.com')) {
                badge.textContent = 'GMAIL';
                badge.classList.remove('hidden');
            } else if (val.includes('@')) {
                badge.textContent = 'EMAIL';
                badge.classList.remove('hidden');
            } else if (val.length >= 3) {
                badge.textContent = 'USERNAME';
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }

        function showInlineError(msg) {
            const box = document.getElementById('forgotInlineError');
            const text = document.getElementById('forgotInlineErrorText');
            if (!box || !text) return;
            text.textContent = msg;
            box.classList.remove('hidden');
        }

        function hideInlineError() {
            const box = document.getElementById('forgotInlineError');
            if (box) box.classList.add('hidden');
        }

        function getOtpInputs() {
            return Array.from(document.querySelectorAll('.otp-digit-input'));
        }

        function getAssembledOtp() {
            return getOtpInputs().map(input => input.value.trim()).join('');
        }

        function showOtpVerifyingStatus(text) {
            const hint = document.getElementById('otpHelperHint');
            const ind = document.getElementById('otpVerifyingIndicator');
            const indText = document.getElementById('otpVerifyingText');
            if (hint) hint.classList.add('hidden');
            if (indText && text) indText.textContent = text;
            if (ind) {
                ind.classList.remove('hidden');
                ind.style.display = 'inline-flex';
            }
        }

        function hideOtpVerifyingStatus() {
            const hint = document.getElementById('otpHelperHint');
            const ind = document.getElementById('otpVerifyingIndicator');
            if (ind) {
                ind.style.display = 'none';
                ind.classList.add('hidden');
            }
            if (hint) hint.classList.remove('hidden');
        }

        function resetOtpVisualState() {
            const grid = document.getElementById('otpGridContainer');
            if (grid) grid.classList.remove('is-shaking');
            getOtpInputs().forEach(inp => {
                inp.classList.remove('is-verifying', 'is-valid', 'is-invalid');
            });
            hideOtpVerifyingStatus();
        }

        function clearOtpDigits() {
            if (isVerifyingOtp) return;
            resetOtpVisualState();
            const inputs = getOtpInputs();
            inputs.forEach(inp => {
                inp.disabled = false;
                inp.value = '';
                inp.classList.remove('is-filled');
            });
            if (inputs[0]) inputs[0].focus();
        }

        function maybeAutoVerifyOtp() {
            if (isVerifyingOtp) return;
            const otpCode = getAssembledOtp();
            if (/^[0-9]{6}$/.test(otpCode)) {
                verifyOtpAutomatically(otpCode);
            }
        }

        function setupOtpGridInteractions() {
            const inputs = getOtpInputs();
            inputs.forEach((input, idx) => {
                input.addEventListener('input', function() {
                    if (isVerifyingOtp) return;
                    hideInlineError();
                    resetOtpVisualState();

                    const rawDigits = this.value.replace(/[^0-9]/g, '');
                    if (rawDigits.length > 1) {
                        const chars = rawDigits.slice(0, 6).split('');
                        inputs.forEach((box, boxIdx) => {
                            const d = chars[boxIdx] || '';
                            box.value = d;
                            box.classList.toggle('is-filled', d !== '');
                        });
                        const lastIdx = Math.min(chars.length - 1, inputs.length - 1);
                        inputs[lastIdx].focus();
                        maybeAutoVerifyOtp();
                        return;
                    }

                    const digit = rawDigits.slice(-1);
                    this.value = digit;
                    this.classList.toggle('is-filled', digit !== '');

                    if (digit !== '' && idx < inputs.length - 1) {
                        inputs[idx + 1].focus();
                        inputs[idx + 1].select();
                    }

                    maybeAutoVerifyOtp();
                });

                input.addEventListener('keydown', function(e) {
                    if (isVerifyingOtp) {
                        e.preventDefault();
                        return;
                    }
                    if (e.key === 'Backspace') {
                        resetOtpVisualState();
                        hideInlineError();
                        if (!this.value && idx > 0) {
                            inputs[idx - 1].value = '';
                            inputs[idx - 1].classList.remove('is-filled');
                            inputs[idx - 1].focus();
                        }
                    } else if (e.key === 'ArrowLeft' && idx > 0) {
                        e.preventDefault();
                        inputs[idx - 1].focus();
                    } else if (e.key === 'ArrowRight' && idx < inputs.length - 1) {
                        e.preventDefault();
                        inputs[idx + 1].focus();
                    }
                });

                input.addEventListener('paste', function(e) {
                    if (isVerifyingOtp) {
                        e.preventDefault();
                        return;
                    }
                    e.preventDefault();
                    hideInlineError();
                    resetOtpVisualState();

                    const pasted = (e.clipboardData || window.clipboardData).getData('text') || '';
                    const digits = pasted.replace(/[^0-9]/g, '').slice(0, 6).split('');
                    if (!digits.length) return;
                    inputs.forEach((box, boxIdx) => {
                        const d = digits[boxIdx] || '';
                        box.value = d;
                        box.classList.toggle('is-filled', d !== '');
                    });
                    const nextIdx = Math.min(digits.length, inputs.length - 1);
                    inputs[nextIdx].focus();
                    maybeAutoVerifyOtp();
                });
            });
        }

        function startResendCooldown(seconds) {
            const btn = document.getElementById('resendOtpBtn');
            const btnText = document.getElementById('resendOtpBtnText');
            if (!btn || !btnText) return;

            if (resendCooldownTimer) {
                clearInterval(resendCooldownTimer);
            }

            let remaining = Number(seconds);
            if (!Number.isFinite(remaining) || remaining <= 0) {
                btn.disabled = false;
                btnText.textContent = 'Resend OTP code';
                return;
            }

            btn.disabled = true;
            btnText.textContent = `Resend OTP in ${remaining}s`;

            resendCooldownTimer = setInterval(() => {
                remaining -= 1;
                if (remaining <= 0) {
                    clearInterval(resendCooldownTimer);
                    resendCooldownTimer = null;
                    btn.disabled = false;
                    btnText.textContent = 'Resend OTP code';
                } else {
                    btnText.textContent = `Resend OTP in ${remaining}s`;
                }
            }, 1000);
        }

        function transitionToPendingApproval(data) {
            hideInlineError();

            const step1Wrap = document.getElementById('step1IdentifyWrapper');
            const pendingWrap = document.getElementById('step1bPendingApprovalWrapper');
            const step2Wrap = document.getElementById('step2VerifyOtpWrapper');
            const step3Wrap = document.getElementById('step3NewPasswordWrapper');
            const heading = document.getElementById('forgotPageHeading');
            const sub = document.getElementById('forgotPageSub');

            const reqIdInput = document.getElementById('pendingResetRequestIdInput');
            const otpAcctInput = document.getElementById('otpAcctInput');
            const headLabel = document.getElementById('pendingUnitHeadLabel');
            const officerBadge = document.getElementById('pendingOfficerBadge');
            const emailBadge = document.getElementById('pendingEmailBadge');

            if (reqIdInput && data.request_id) reqIdInput.value = data.request_id;
            if (otpAcctInput && data.acct) otpAcctInput.value = data.acct;
            if (headLabel && data.officer && data.officer.unit_head) {
                headLabel.textContent = data.officer.unit_head;
            }
            if (officerBadge && data.officer) {
                const sid = data.officer.service_id || '';
                const fname = data.officer.fullname || 'Staff Officer';
                officerBadge.textContent = sid ? `${fname} (${sid})` : fname;
            }
            if (emailBadge && data.masked_email) {
                emailBadge.textContent = data.masked_email;
            }

            if (heading) heading.textContent = 'Awaiting Head approval';
            if (sub) {
                sub.textContent = 'Your password reset request has been sent to your Head of Unit. Once approved, your 6-digit OTP code will be sent to your Gmail.';
            }

            const rail1 = document.getElementById('railStep1');
            const rail1Num = document.getElementById('railStep1Num');
            const rail2 = document.getElementById('railStep2');
            const rail3 = document.getElementById('railStep3');
            if (rail1) {
                rail1.classList.remove('is-done');
                rail1.classList.add('is-active');
            }
            if (rail1Num) rail1Num.textContent = '01';
            if (rail2) rail2.classList.remove('is-active', 'is-done');
            if (rail3) rail3.classList.remove('is-active', 'is-done');

            if (step1Wrap) step1Wrap.classList.add('hidden');
            if (step2Wrap) step2Wrap.classList.add('hidden');
            if (step3Wrap) step3Wrap.classList.add('hidden');
            if (pendingWrap) pendingWrap.classList.remove('hidden');

            startApprovalPolling();
        }

        async function pollResetRequestStatus(isManual = false) {
            if (isPollingApproval) return;
            const reqId = document.getElementById('pendingResetRequestIdInput')?.value || '';
            const acctVal = document.getElementById('otpAcctInput')?.value || '';
            if (!reqId || !acctVal) return;

            isPollingApproval = true;
            const manualBtn = document.getElementById('manualCheckStatusBtn');
            const manualTxt = document.getElementById('manualCheckStatusBtnText');
            if (isManual && manualBtn && manualTxt) {
                manualBtn.disabled = true;
                manualTxt.textContent = 'Checking approval status...';
            }

            try {
                const url = `${CHECK_REQUEST_STATUS_ENDPOINT}?request_id=${encodeURIComponent(reqId)}&acct=${encodeURIComponent(acctVal)}`;
                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await res.json();

                if (res.ok && data && data.success) {
                    if (data.status === 'approved') {
                        stopApprovalPolling();
                        transitionToStep2Otp(data);
                        return;
                    }
                    if (data.status === 'declined') {
                        stopApprovalPolling();
                        switchToStep1();
                        showInlineError(data.message || 'Your password reset request was declined by your Head of Unit.');
                        return;
                    }
                    if (isManual) {
                        const statusTxt = document.getElementById('pendingPollStatusText');
                        if (statusTxt) {
                            statusTxt.textContent = 'Still awaiting Head of Unit approval — we will transition automatically as soon as your Head approves.';
                        }
                    }
                }
            } catch (e) {
                // Ignore transient network hiccups during background polling
            } finally {
                isPollingApproval = false;
                if (isManual && manualBtn && manualTxt) {
                    manualBtn.disabled = false;
                    manualTxt.textContent = 'Check approval status now';
                }
            }
        }

        function transitionToStep2Otp(data) {
            stopApprovalPolling();
            hideInlineError();

            const step1Wrap = document.getElementById('step1IdentifyWrapper');
            const pendingWrap = document.getElementById('step1bPendingApprovalWrapper');
            const step2Wrap = document.getElementById('step2VerifyOtpWrapper');
            const step3Wrap = document.getElementById('step3NewPasswordWrapper');
            const heading = document.getElementById('forgotPageHeading');
            const sub = document.getElementById('forgotPageSub');
            const emailBadge = document.getElementById('dispatchedEmailBadge');
            const otpAcctInput = document.getElementById('otpAcctInput');
            const bannerTitle = document.getElementById('otpSentBannerTitle');

            const rail1 = document.getElementById('railStep1');
            const rail1Num = document.getElementById('railStep1Num');
            const rail2 = document.getElementById('railStep2');
            const rail2Num = document.getElementById('railStep2Num');
            const rail3 = document.getElementById('railStep3');

            if (emailBadge && data.masked_email) {
                emailBadge.textContent = data.masked_email;
            }
            if (otpAcctInput && data.acct) {
                otpAcctInput.value = data.acct;
            }
            if (bannerTitle) {
                bannerTitle.textContent = data.approved_by
                    ? `Approved by ${data.approved_by} • OTP Sent`
                    : 'OTP Sent to Registered Gmail';
            }

            if (heading) heading.textContent = 'Enter OTP code';
            if (sub) sub.textContent = 'Enter the 6-digit verification code sent to your registered Gmail account.';

            if (rail1) {
                rail1.classList.remove('is-active');
                rail1.classList.add('is-done');
            }
            if (rail1Num) rail1Num.innerHTML = '&#10003;';
            if (rail2) {
                rail2.classList.remove('is-done');
                rail2.classList.add('is-active');
            }
            if (rail2Num) rail2Num.textContent = '02';
            if (rail3) rail3.classList.remove('is-active', 'is-done');

            if (step1Wrap) step1Wrap.classList.add('hidden');
            if (pendingWrap) pendingWrap.classList.add('hidden');
            if (step3Wrap) step3Wrap.classList.add('hidden');
            if (step2Wrap) step2Wrap.classList.remove('hidden');

            clearOtpDigits();
            startResendCooldown(data.cooldown_seconds !== undefined ? data.cooldown_seconds : 60);
        }

        function switchToStep1() {
            stopApprovalPolling();
            hideInlineError();

            const step1Wrap = document.getElementById('step1IdentifyWrapper');
            const pendingWrap = document.getElementById('step1bPendingApprovalWrapper');
            const step2Wrap = document.getElementById('step2VerifyOtpWrapper');
            const step3Wrap = document.getElementById('step3NewPasswordWrapper');
            const verifySec = document.getElementById('noEmailVerifySection');
            const heading = document.getElementById('forgotPageHeading');
            const sub = document.getElementById('forgotPageSub');
            const btnText = document.getElementById('sendOtpBtnText');
            const loginInput = document.getElementById('loginIdInput');

            const rail1 = document.getElementById('railStep1');
            const rail1Num = document.getElementById('railStep1Num');
            const rail2 = document.getElementById('railStep2');
            const rail2Num = document.getElementById('railStep2Num');
            const rail3 = document.getElementById('railStep3');

            if (verifySec) verifySec.classList.add('hidden');
            const phoneInput = document.getElementById('verifyPhoneInput');
            const recEmailInput = document.getElementById('recoveryEmailInput');
            if (phoneInput) {
                phoneInput.required = false;
                phoneInput.value = '';
            }
            if (recEmailInput) {
                recEmailInput.required = false;
                recEmailInput.value = '';
            }
            if (btnText) btnText.textContent = 'Send password reset request';

            if (heading) heading.textContent = 'Forgot password?';
            if (sub) sub.textContent = 'Enter your registered Gmail address, username, or Service ID to send a password reset request to your Head of Unit.';

            if (rail1) {
                rail1.classList.remove('is-done');
                rail1.classList.add('is-active');
            }
            if (rail1Num) rail1Num.textContent = '01';
            if (rail2) rail2.classList.remove('is-active', 'is-done');
            if (rail2Num) rail2Num.textContent = '02';
            if (rail3) rail3.classList.remove('is-active', 'is-done');

            if (pendingWrap) pendingWrap.classList.add('hidden');
            if (step2Wrap) step2Wrap.classList.add('hidden');
            if (step3Wrap) step3Wrap.classList.add('hidden');
            if (step1Wrap) step1Wrap.classList.remove('hidden');

            if (loginInput) {
                loginInput.focus();
                loginInput.select();
            }
        }

        function transitionToStep3NewPassword(data) {
            stopApprovalPolling();
            hideInlineError();

            const step1Wrap = document.getElementById('step1IdentifyWrapper');
            const pendingWrap = document.getElementById('step1bPendingApprovalWrapper');
            const step2Wrap = document.getElementById('step2VerifyOtpWrapper');
            const step3Wrap = document.getElementById('step3NewPasswordWrapper');
            const heading = document.getElementById('forgotPageHeading');
            const sub = document.getElementById('forgotPageSub');

            const rail2 = document.getElementById('railStep2');
            const rail2Num = document.getElementById('railStep2Num');
            const rail3 = document.getElementById('railStep3');

            if (rail2) {
                rail2.classList.remove('is-active');
                rail2.classList.add('is-done');
            }
            if (rail2Num) rail2Num.innerHTML = '&#10003;';
            if (rail3) rail3.classList.add('is-active');

            if (heading) heading.textContent = 'Set new password';
            if (sub) sub.textContent = 'Your OTP has been verified. Create a strong new password for your FIMS account.';

            const tokenField = document.getElementById('verifiedResetTokenInput');
            const acctField = document.getElementById('verifiedAcctInput');
            if (tokenField) tokenField.value = data.reset_token || '';
            if (acctField) acctField.value = data.acct || '';

            if (data.officer) {
                verifiedOfficerUsername = String(data.officer.username || '').toLowerCase();
                verifiedOfficerFullname = String(data.officer.fullname || '').toLowerCase();
                const nameEl = document.getElementById('verifiedOfficerName');
                const unitEl = document.getElementById('verifiedOfficerUnit');
                const badgeEl = document.getElementById('verifiedOfficerBadge');
                if (nameEl) nameEl.textContent = data.officer.fullname || 'Officer';
                if (unitEl) unitEl.textContent = `${data.officer.unit || 'Forensic Division'} • ${data.officer.role_label || 'Officer'}`;
                if (badgeEl) badgeEl.textContent = data.officer.service_id || 'FIMS';
            }

            if (step1Wrap) step1Wrap.classList.add('hidden');
            if (pendingWrap) pendingWrap.classList.add('hidden');
            if (step2Wrap) step2Wrap.classList.add('hidden');
            if (step3Wrap) step3Wrap.classList.remove('hidden');

            const pwdInput = document.getElementById('newPasswordInput');
            if (pwdInput) pwdInput.focus();
        }

        async function handleSendOtpSubmit(e) {
            e.preventDefault();
            hideInlineError();

            const form = document.getElementById('forgotPasswordForm');
            const submitBtn = document.getElementById('sendOtpSubmitBtn');
            const btnText = document.getElementById('sendOtpBtnText');
            const loginInput = document.getElementById('loginIdInput');
            const verifySection = document.getElementById('noEmailVerifySection');
            const isStepUpVisible = verifySection && !verifySection.classList.contains('hidden');

            const loginVal = loginInput ? loginInput.value.trim() : '';
            if (!loginVal) {
                showInlineError('Please enter your registered Gmail, Username, or Service ID.');
                return;
            }

            if (isStepUpVisible) {
                const phoneVal = (document.getElementById('verifyPhoneInput')?.value || '').trim();
                const emailVal = (document.getElementById('recoveryEmailInput')?.value || '').trim();
                if (phoneVal.length !== 10) {
                    showInlineError('Please enter the 10-digit phone number registered on your officer account.');
                    return;
                }
                if (!emailVal || !emailVal.includes('@')) {
                    showInlineError('Please enter a valid Gmail address to receive your OTP code.');
                    return;
                }
            }

            const originalLabel = isStepUpVisible ? 'Verify & send reset request' : 'Send password reset request';
            submitBtn.disabled = true;
            btnText.innerHTML = `
                <span class="inline-flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Sending reset request...</span>
                </span>
            `;

            try {
                const formData = new FormData(form);
                const csrfToken = document.querySelector('input[name="_token"]')?.value || '';

                const res = await fetch(form.action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    submitBtn.disabled = false;
                    btnText.textContent = originalLabel;
                    broadcastPasswordResetSync();
                    if (data.awaiting_head_approval) {
                        transitionToPendingApproval(data);
                    } else {
                        transitionToStep2Otp(data);
                    }
                    return;
                }

                submitBtn.disabled = false;

                if (data && data.needs_recovery_email) {
                    if (verifySection) verifySection.classList.remove('hidden');
                    const hintEl = document.getElementById('maskedPhoneHint');
                    if (hintEl && data.masked_phone) hintEl.textContent = data.masked_phone;
                    const badgeEl = document.getElementById('verifyOfficerBadge');
                    if (badgeEl && data.service_id) {
                        badgeEl.textContent = data.unit ? `${data.service_id} • ${data.unit}` : data.service_id;
                    }
                    const phoneField = document.getElementById('verifyPhoneInput');
                    const emailField = document.getElementById('recoveryEmailInput');
                    if (phoneField) phoneField.required = true;
                    if (emailField) emailField.required = true;
                    btnText.textContent = 'Verify & send reset request';

                    if (!res.ok && data.message) {
                        showInlineError(data.message);
                    } else if (phoneField) {
                        phoneField.focus();
                    }
                    return;
                }

                btnText.textContent = originalLabel;
                const errMsg = data.message || (data.errors ? Object.values(data.errors).flat()[0] : 'Unable to submit password reset request.');
                showInlineError(errMsg);
                if (data && data.account_revoked && data.redirect_url) {
                    setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 1200);
                }
            } catch (err) {
                form.submit();
            }
        }

        async function verifyOtpAutomatically(otpCode) {
            if (isVerifyingOtp || !/^[0-9]{6}$/.test(otpCode)) return;
            isVerifyingOtp = true;
            hideInlineError();

            const inputs = getOtpInputs();
            const grid = document.getElementById('otpGridContainer');
            const clearBtn = document.getElementById('clearOtpDigitsBtn');

            inputs.forEach(inp => {
                inp.disabled = true;
                inp.classList.remove('is-valid', 'is-invalid');
                inp.classList.add('is-verifying');
            });
            if (clearBtn) clearBtn.disabled = true;
            showOtpVerifyingStatus('Verifying code...');

            const acctVal = document.getElementById('otpAcctInput')?.value || '';
            const loginVal = document.getElementById('loginIdInput')?.value || '';
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            try {
                const res = await fetch(VERIFY_OTP_ENDPOINT, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        acct: acctVal,
                        login_id: loginVal,
                        otp: otpCode,
                    }),
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    inputs.forEach(inp => {
                        inp.classList.remove('is-verifying');
                        inp.classList.add('is-valid');
                    });
                    showOtpVerifyingStatus('OTP verified! Proceeding...');
                    setTimeout(() => {
                        isVerifyingOtp = false;
                        if (clearBtn) clearBtn.disabled = false;
                        inputs.forEach(inp => inp.disabled = false);
                        resetOtpVisualState();
                        transitionToStep3NewPassword(data);
                    }, 280);
                    return;
                }

                isVerifyingOtp = false;
                if (clearBtn) clearBtn.disabled = false;
                hideOtpVerifyingStatus();

                inputs.forEach(inp => {
                    inp.disabled = false;
                    inp.classList.remove('is-verifying');
                    inp.classList.add('is-invalid');
                });
                if (grid) {
                    grid.classList.remove('is-shaking');
                    void grid.offsetWidth;
                    grid.classList.add('is-shaking');
                }

                if (data && (data.locked || data.expired)) {
                    const resendBtn = document.getElementById('resendOtpBtn');
                    const resendTxt = document.getElementById('resendOtpBtnText');
                    if (resendCooldownTimer) {
                        clearInterval(resendCooldownTimer);
                        resendCooldownTimer = null;
                    }
                    if (resendBtn) resendBtn.disabled = false;
                    if (resendTxt) resendTxt.textContent = 'Resend OTP code';
                }

                const errMsg = data.message || (data.errors ? Object.values(data.errors).flat()[0] : 'Invalid OTP verification code.');
                showInlineError(errMsg);

                setTimeout(() => {
                    if (isVerifyingOtp) return;
                    inputs.forEach(inp => {
                        inp.value = '';
                        inp.classList.remove('is-filled', 'is-invalid');
                    });
                    if (inputs[0]) inputs[0].focus();
                }, 420);
            } catch (err) {
                isVerifyingOtp = false;
                if (clearBtn) clearBtn.disabled = false;
                hideOtpVerifyingStatus();
                inputs.forEach(inp => {
                    inp.disabled = false;
                    inp.classList.remove('is-verifying');
                });
                if (inputs[5]) inputs[5].focus();
                showInlineError('Network error while verifying OTP. Please check your connection and re-enter the last digit.');
            }
        }

        async function resendOtpCode() {
            const btn = document.getElementById('resendOtpBtn');
            const btnText = document.getElementById('resendOtpBtnText');
            const form = document.getElementById('forgotPasswordForm');
            if (!btn || !btnText || !form || btn.disabled) return;

            hideInlineError();
            btn.disabled = true;
            btnText.textContent = 'Sending new OTP...';

            try {
                const formData = new FormData(form);
                formData.append('resend_otp', '1');
                const csrfToken = document.querySelector('input[name="_token"]')?.value || '';

                const res = await fetch(form.action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    broadcastPasswordResetSync();
                    if (data.awaiting_head_approval) {
                        transitionToPendingApproval(data);
                    } else {
                        transitionToStep2Otp(data);
                    }
                } else {
                    if (data && data.retry_after) {
                        startResendCooldown(data.retry_after);
                    } else {
                        btn.disabled = false;
                        btnText.textContent = 'Resend OTP code';
                    }
                    if (data && data.message) {
                        showInlineError(data.message);
                    }
                }
            } catch (e) {
                btn.disabled = false;
                btnText.textContent = 'Resend OTP code';
                showInlineError('Network error while resending OTP. Please try again.');
            }
        }

        function togglePasswordField(inputId) {
            const el = document.getElementById(inputId);
            if (!el) return;
            el.type = el.type === 'password' ? 'text' : 'password';
        }

        function setPolicyBadge(id, passed) {
            const el = document.getElementById(id);
            if (!el) return;
            if (passed) {
                el.className = el.className
                    .replace(/bg-slate-200|text-slate-500|bg-rose-100|text-rose-700/g, '')
                    .trim() + ' bg-teal-100 text-teal-800';
            } else {
                el.className = el.className
                    .replace(/bg-teal-100|text-teal-800|bg-rose-100|text-rose-700/g, '')
                    .trim() + ' bg-slate-200 text-slate-500';
            }
        }

        function containsPersonalIdentifier(pwd) {
            const lower = String(pwd || '').toLowerCase();
            if (!lower) return false;
            if (verifiedOfficerUsername.length >= 3 && lower.includes(verifiedOfficerUsername)) {
                return true;
            }
            const parts = verifiedOfficerFullname.split(/\s+/).filter(p => p.length >= 3);
            return parts.some(part => lower.includes(part));
        }

        function evaluateNewPasswordStrength(val) {
            const bar = document.getElementById('newStrengthBar');
            const label = document.getElementById('newStrengthLabel');
            if (!bar || !label) return;

            if (!val) {
                bar.style.width = '0%';
                bar.className = 'h-1.5 w-0 bg-slate-400 transition-all duration-300 rounded-full';
                label.textContent = 'Enter password';
                label.className = 'font-bold text-slate-400';
                ['polLen', 'polCaps', 'polNum', 'polSym', 'polNoId'].forEach(id => setPolicyBadge(id, false));
                return;
            }

            const hasLen = val.length >= 8;
            const hasCaps = /[A-Z]/.test(val);
            const hasNum = /[0-9]/.test(val);
            const hasSym = /[^A-Za-z0-9]/.test(val);
            const hasNoId = !containsPersonalIdentifier(val);

            setPolicyBadge('polLen', hasLen);
            setPolicyBadge('polCaps', hasCaps);
            setPolicyBadge('polNum', hasNum);
            setPolicyBadge('polSym', hasSym);
            const polNoIdEl = document.getElementById('polNoId');
            if (polNoIdEl) {
                if (hasNoId) {
                    setPolicyBadge('polNoId', true);
                } else {
                    polNoIdEl.className = 'col-span-2 sm:col-span-1 px-1.5 py-0.5 rounded bg-rose-100 text-rose-700 font-semibold text-center transition-colors';
                }
            }

            if (!hasNoId) {
                bar.style.width = '25%';
                bar.className = 'h-1.5 bg-rose-600 transition-all duration-300 rounded-full';
                label.textContent = 'Contains name/username';
                label.className = 'font-bold text-rose-600';
                return;
            }

            const score = [hasLen, hasCaps, hasNum, hasSym].filter(Boolean).length;
            if (!hasLen || score <= 1) {
                bar.style.width = '30%';
                bar.className = 'h-1.5 bg-rose-500 transition-all duration-300 rounded-full';
                label.textContent = 'Weak';
                label.className = 'font-bold text-rose-600';
            } else if (score === 2) {
                bar.style.width = '55%';
                bar.className = 'h-1.5 bg-amber-500 transition-all duration-300 rounded-full';
                label.textContent = 'Moderate';
                label.className = 'font-bold text-amber-600';
            } else if (score === 3) {
                bar.style.width = '80%';
                bar.className = 'h-1.5 bg-teal-600 transition-all duration-300 rounded-full';
                label.textContent = 'Strong';
                label.className = 'font-bold text-teal-700';
            } else {
                bar.style.width = '100%';
                bar.className = 'h-1.5 bg-emerald-600 transition-all duration-300 rounded-full';
                label.textContent = 'High Security';
                label.className = 'font-bold text-emerald-700';
            }
        }

        function evaluateNewPasswordMatch() {
            const p1 = document.getElementById('newPasswordInput')?.value || '';
            const p2 = document.getElementById('confirmPasswordInput')?.value || '';
            const notice = document.getElementById('newPasswordMatchNotice');
            if (!notice) return;

            if (!p2) {
                notice.classList.add('hidden');
                return;
            }

            notice.classList.remove('hidden');
            if (p1 === p2) {
                notice.textContent = 'Passwords match';
                notice.className = 'text-[11px] font-mono-code mt-1 font-bold text-teal-700';
            } else {
                notice.textContent = 'Passwords do not match';
                notice.className = 'text-[11px] font-mono-code mt-1 font-bold text-red-600';
            }
        }

        async function handleSetNewPasswordSubmit(e) {
            e.preventDefault();
            hideInlineError();

            const p1 = document.getElementById('newPasswordInput')?.value || '';
            const p2 = document.getElementById('confirmPasswordInput')?.value || '';

            if (p1.length < 8) {
                showInlineError('Your new password must be at least 8 characters long.');
                return;
            }
            if (p1 !== p2) {
                showInlineError('Password confirmation does not match.');
                return;
            }
            if (containsPersonalIdentifier(p1)) {
                showInlineError('Security Policy: Your new password must not contain your name or username.');
                return;
            }

            const form = document.getElementById('setNewPasswordForm');
            const submitBtn = document.getElementById('savePasswordSubmitBtn');
            const btnText = document.getElementById('savePasswordBtnText');

            submitBtn.disabled = true;
            btnText.innerHTML = `
                <span class="inline-flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Updating password...</span>
                </span>
            `;

            try {
                const formData = new FormData(form);
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

                const res = await fetch(form.action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    broadcastPasswordResetSync();
                    btnText.innerHTML = `
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span>Password Updated! Redirecting...</span>
                        </span>
                    `;
                    setTimeout(() => {
                        window.location.href = data.redirect_url || '{{ url('/login') }}';
                    }, 350);
                    return;
                }

                submitBtn.disabled = false;
                btnText.textContent = 'Save new password & sign in';

                if (data && data.expired) {
                    switchToStep1();
                }

                const errMsg = data.message || (data.errors ? Object.values(data.errors).flat()[0] : 'Unable to update password.');
                showInlineError(errMsg);
            } catch (err) {
                form.submit();
            }
        }

        function openHelpdeskModal() {
            const modal = document.getElementById('helpdeskModal');
            if (modal) modal.classList.remove('hidden');
        }

        function closeHelpdeskModal() {
            const modal = document.getElementById('helpdeskModal');
            if (modal) modal.classList.add('hidden');
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeHelpdeskModal();
            }
        });

        document.getElementById('helpdeskModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeHelpdeskModal();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            setupOtpGridInteractions();
            const loginInput = document.getElementById('loginIdInput');
            if (loginInput && loginInput.value) {
                updateDetectedIdentifierBadge(loginInput.value);
            }
            @if ($dispatchedMaskedEmail)
                startResendCooldown(60);
                getOtpInputs()[0]?.focus();
            @elseif ($isAwaitingApprovalInit)
                startApprovalPolling();
            @endif
        });
    </script>
</body>

</html>
