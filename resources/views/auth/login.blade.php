<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In | Forensic Inventory Management System (FIMS)</title>

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

    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #BBE7E4;
            color: #0F172A;
            margin: 0;
            padding: 0;
        }

        .font-mono-code {
            font-family: 'JetBrains Mono', monospace;
        }

        /* Geometric angled background mimicking the reference image */
        .fims-bg-canvas {
            position: fixed;
            inset: 0;
            z-index: 0;
            background: linear-gradient(135deg, #BBE7E4 0%, #B2E2DF 50%, #C7ECE9 100%);
            overflow: hidden;
            pointer-events: none;
        }

        .fims-bg-facet {
            position: absolute;
            top: 0;
            right: 0;
            width: 75vw;
            height: 100vh;
            background: #CEF0ED;
            clip-path: polygon(45% 0, 100% 0, 100% 100%, 0% 100%);
            opacity: 0.85;
        }

        .fims-bg-facet-2 {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50vw;
            height: 60vh;
            background: #AEE0DC;
            clip-path: polygon(0 40%, 100% 100%, 0 100%);
            opacity: 0.6;
        }

        /* Custom Underline Input Style from Reference */
        .ref-input-line {
            border-bottom: 2px solid #CBD5E1;
            transition: all 0.2s ease;
        }

        .ref-input-line:focus-within {
            border-bottom-color: #0D9488;
        }
    </style>
</head>

<body
    class="min-h-screen flex flex-col justify-between antialiased selection:bg-teal-700 selection:text-white transition-opacity duration-300 opacity-100 relative">

    <!-- Geometric Pastel Background -->
    <div class="fims-bg-canvas">
        <div class="fims-bg-facet"></div>
        <div class="fims-bg-facet-2"></div>
    </div>

    <!-- TOP SYSTEM STATUS BAR -->
    <header
        class="w-full px-6 py-3.5 flex items-center justify-between bg-white/95 border-b border-teal-900/10 shadow-sm relative z-10 backdrop-blur-sm">
        <div class="flex items-center space-x-3.5">
            <div
                class="w-11 h-11 rounded-xl bg-white p-1 border border-teal-700/20 flex items-center justify-center shadow-xs overflow-hidden shrink-0">
                <img src="{{ asset('NACOC_LOGO.PNG') }}" alt="NACOC Logo" class="w-full h-full object-contain">
            </div>
            <div>
                <div class="font-extrabold text-slate-900 tracking-wider text-xs font-mono-code leading-none mb-0.5">
                    FORENSIC INVENTORY MANAGEMENT SYSTEM (FIMS)</div>
                <div class="text-[10px] text-teal-700 font-bold uppercase tracking-wider">NARCOTICS CONTROL
                    COMMISSION GHANA &bull; FORENSICS DEPARTMENT</div>
            </div>
        </div>

        <div class="flex items-center space-x-4">
            <div class="text-right hidden sm:block">
                <div class="text-xs font-bold text-slate-800 font-mono-code" id="liveClock">2026-09-05 00:00:00 UTC
                </div>
                <div class="text-[10px] font-mono-code text-teal-700 font-extrabold tracking-wider">SECURE PORTAL v4.2
                </div>
            </div>
        </div>
    </header>

    <!-- MAIN LOGIN CONTAINER -->
    <main class="flex-1 flex items-center justify-center p-4 sm:p-6 my-auto relative z-10">
        <div
            class="w-full max-w-4xl bg-white rounded-3xl shadow-[0_24px_60px_-12px_rgba(15,23,42,0.18)] border border-slate-200/80 overflow-hidden flex flex-col md:flex-row min-h-[540px]">

            <!-- LEFT PANEL: Mint-Teal Curved Wave & Vector Graphic (From Reference Image) -->
            <div
                class="md:w-5/12 bg-gradient-to-br from-teal-600 via-teal-700 to-teal-800 text-white relative p-8 flex flex-col justify-between overflow-hidden">

                <!-- Organic Curved Wave SVG Dividing Edge -->
                <svg class="absolute top-0 bottom-0 right-0 h-full w-24 text-white hidden md:block translate-x-1"
                    viewBox="0 0 100 100" preserveAspectRatio="none" fill="currentColor">
                    <path d="M0,0 C65,25 20,70 100,100 L100,0 Z"></path>
                </svg>

                <!-- Vertical Insignia Typography from Reference Image -->
                <div
                    class="absolute left-6 top-8 bottom-8 flex items-center pointer-events-none select-none opacity-20">
                    <span
                        class="text-5xl font-black tracking-widest uppercase transform -rotate-90 origin-center text-white font-mono-code">FIMS</span>
                </div>

                <!-- Top Left Identity Crest -->
                <div class="relative z-10 flex items-center space-x-3 pl-8">
                    <div class="w-12 h-12 rounded-2xl bg-white p-1.5 shadow-md border border-white/30 shrink-0">
                        <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Forensics Emblem"
                            class="w-full h-full object-contain">
                    </div>
                    <div>
                        <div class="text-[11px] font-black uppercase tracking-widest text-teal-200 font-mono-code">NACOC
                            GHANA</div>
                        <div class="text-xs font-bold text-white">Forensic Department</div>
                    </div>
                </div>

                <!-- Isometric Forensic Evidence Terminal Illustration (Faithful to Reference Image) -->
                <div class="relative z-10 my-auto py-6 flex flex-col items-center justify-center pl-6">
                    <svg viewBox="0 0 320 220" class="w-full max-w-[260px] drop-shadow-2xl" fill="none">
                        <!-- Isometric Tablet Base -->
                        <polygon points="160,35 295,95 160,170 25,110" fill="#E2F7F5" stroke="#FFFFFF"
                            stroke-width="3" />
                        <polygon points="25,110 160,170 160,185 25,125" fill="#99D5CF" />
                        <polygon points="160,170 295,95 295,110 160,185" fill="#75C2BC" />

                        <!-- Tablet Screen Surface -->
                        <polygon points="160,48 280,102 160,160 40,108" fill="#F4FCFB" />

                        <!-- Isometric Forensic Chromatography Bars -->
                        <path d="M75,98 L75,70 L82,67 L82,95 Z" fill="#0D9488" />
                        <path d="M82,67 L88,70 L88,97 L82,95 Z" fill="#042F2E" />

                        <path d="M92,94 L92,58 L99,55 L99,91 Z" fill="#14B8A6" />
                        <path d="M99,55 L105,58 L105,93 L99,91 Z" fill="#0F766E" />

                        <path d="M109,90 L109,48 L116,45 L116,86 Z" fill="#2DD4BF" />
                        <path d="M116,45 L122,48 L122,89 L116,86 Z" fill="#0D9488" />

                        <path d="M126,86 L126,65 L133,62 L133,83 Z" fill="#0D9488" />
                        <path d="M133,62 L139,65 L139,85 L133,83 Z" fill="#042F2E" />

                        <!-- Forensic Spectroscopy Polyline -->
                        <polyline points="75,90 95,65 110,75 125,50 145,72 165,58" stroke="#0D9488" stroke-width="2.5"
                            fill="none" stroke-linecap="round" />

                        <!-- Chain of Custody Biometric Nodes -->
                        <ellipse cx="205" cy="118" rx="20" ry="11" fill="none"
                            stroke="#0D9488" stroke-width="2" stroke-dasharray="3 2" />
                        <ellipse cx="205" cy="118" rx="12" ry="6.5" fill="none"
                            stroke="#14B8A6" stroke-width="1.8" />
                        <ellipse cx="205" cy="118" rx="4" ry="2" fill="#042F2E" />

                        <!-- Evidence Tag / Document Grid -->
                        <polygon points="195,68 250,92 225,104 170,80" fill="#E6FFFA" stroke="#14B8A6"
                            stroke-width="1.5" />
                        <line x1="185" y1="78" x2="225" y2="95" stroke="#99F6E4"
                            stroke-width="2" />
                        <line x1="190" y1="83" x2="235" y2="102" stroke="#99F6E4"
                            stroke-width="2" />
                    </svg>
                </div>

                <!-- Footer Tagline from Reference Image -->
                <div class="relative z-10 pl-8">
                    <p
                        class="text-[10px] font-extrabold tracking-wider uppercase text-teal-100 font-mono-code leading-relaxed">
                        INTRODUCING FORENSIC INVENTORY MANAGEMENT SYSTEM &bull; FIMS
                    </p>
                </div>
            </div>

            <!-- RIGHT PANEL: Clean Login Form with Original Code & IDs -->
            <div class="md:w-7/12 p-8 sm:p-12 flex flex-col justify-between bg-white">

                <!-- TOP HEADER -->
                <div class="flex items-center justify-between pb-6 border-b border-slate-100">
                    <div>
                        <h1 class="text-3xl font-black text-teal-800 tracking-tight">LOGIN</h1>
                        <p class="text-xs text-slate-500 mt-1">Authorized personnel only &bull; Access custody records
                        </p>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-slate-50 p-1 border border-slate-200 flex items-center justify-center shrink-0 shadow-2xs">
                        <img src="{{ asset('NACOC_LOGO.PNG') }}" alt="NACOC Crest"
                            class="w-full h-full object-contain">
                    </div>
                </div>

                <!-- ORIGINAL FORM CONTAINER -->
                <div class="py-6">
                    @include('partials.flash')

                    <div id="loginInlineError" class="hidden mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-xs font-semibold rounded-xl flex items-center space-x-2">
                        <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span id="loginInlineErrorText"></span>
                    </div>

                    <form id="loginForm" method="POST" action="{{ url('/login') }}"
                        onsubmit="handleLoginSubmit(event)">
                        @csrf
                        <div class="space-y-6">

                            <!-- USERNAME / EMAIL / SERVICE ID INPUT (Underline Ref-Style) -->
                            <div class="ref-input-line pb-1.5">
                                <label
                                    class="block text-[11px] font-bold text-slate-600 font-mono-code uppercase mb-1">
                                    Username / Email / Service ID
                                </label>
                                <div class="relative flex items-center">
                                    <input type="text" id="serviceId" name="login_id" required
                                        value="{{ old('login_id') }}"
                                        placeholder="e.g. SD-345, smensah or officer@nacoc.gov.gh"
                                        class="w-full py-2 bg-transparent text-sm text-slate-900 font-semibold placeholder-slate-400 focus:outline-none pr-8">
                                    <div class="absolute right-0 text-teal-700 pointer-events-none">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                </div>
                                @error('login_id')
                                    <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- PASSWORD INPUT (Underline Ref-Style) -->
                            <div class="ref-input-line pb-1.5">
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-[11px] font-bold text-slate-600 font-mono-code uppercase">
                                        Password
                                    </label>
                                </div>
                                <div class="relative flex items-center">
                                    <input type="password" id="passwordInput" name="password" required
                                        placeholder="Enter Security Passcode"
                                        class="w-full py-2 bg-transparent text-sm text-slate-900 font-semibold placeholder-slate-400 focus:outline-none pr-8">
                                    <button type="button" onclick="togglePasswordVisibility()"
                                        class="absolute right-0 text-teal-700 hover:text-teal-900 cursor-pointer focus:outline-none"
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

                            <!-- REMEMBER ME & 2FA BADGE -->
                            <div class="flex items-center justify-between pt-1">
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="checkbox" checked
                                        class="w-4 h-4 rounded text-teal-700 focus:ring-teal-700 border-slate-300">
                                    <span class="text-xs text-slate-600 font-medium">Remember credentials</span>
                                </label>

                                <span
                                    class="text-[10px] text-teal-800 bg-teal-50 border border-teal-200 px-2 py-0.5 rounded-md font-mono-code font-bold flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-teal-700" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    2FA Active
                                </span>
                            </div>

                            <!-- SUBMIT BUTTON (Teal Pill Button from Reference) -->
                            <div class="pt-3">
                                <button type="submit" id="submitBtn"
                                    class="w-full py-3.5 px-6 bg-teal-700 hover:bg-teal-800 active:bg-teal-900 text-white text-sm font-extrabold rounded-2xl shadow-md shadow-teal-700/30 transition-all hover:scale-[1.01] flex items-center justify-center space-x-2 cursor-pointer">
                                    <span id="btnText">Sign In to FIMS</span>
                                    <svg class="w-4 h-4 text-white ml-1" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </button>
                            </div>

                            <!-- REGISTER LINK -->
                            <div class="text-center pt-2">
                                <p class="text-xs text-slate-600">
                                    Don't have an account?
                                    <a href="{{ url('/register') }}"
                                        onclick="navigateWithFade(event, '{{ url('/register') }}')"
                                        class="font-bold text-teal-700 hover:text-teal-900 hover:underline transition-colors">
                                        Register as Officer
                                    </a>
                                </p>
                            </div>

                        </div>
                    </form>
                </div>

                <!-- BOTTOM LINKS (Forgot / Help from Reference Image) -->
                <div
                    class="pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-teal-700">
                    <span class="text-[11px] text-slate-400 font-mono-code">NACOC &bull; ISO/IEC 17025</span>
                    <div class="space-x-4">
                        <a href="#" onclick="showResetToast(); return false;"
                            class="hover:underline">Forgot?</a>
                        <a href="#"
                            onclick="alert('For system access assistance, contact NACOC IT Helpdesk: ext 4022'); return false;"
                            class="hover:underline">Help</a>
                    </div>
                </div>

            </div>

        </div>
    </main>

    <!-- FOOTER BAR -->
    <footer
        class="w-full px-6 py-3 bg-white/95 border-t border-teal-900/10 flex flex-col sm:flex-row items-center justify-between text-[11px] text-slate-600 font-mono-code gap-2 relative z-10 backdrop-blur-sm">
        <div>
            Official Property of the Narcotics Control Commission, Ghana &bull; Forensic Inventory Management System
        </div>
        <div class="flex items-center space-x-4">
            <a href="#" class="hover:text-teal-800">Security Policy</a>
            <span>&bull;</span>
            <a href="#" class="hover:text-teal-800">Chain of Custody Spec</a>
            <span>&bull;</span>
            <a href="#" class="hover:text-teal-800">IT Support Desk</a>
        </div>
    </footer>

    <!-- ACCOUNT APPROVED BY HEAD OF FORENSIC DIRECTORATE POPOVER MODAL CONTAINER -->
    <div id="loginAccountApprovedPopoverModal"
        class="hidden fixed inset-0 z-50 bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4 transition-all duration-300">
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
                        <span
                            class="px-2.5 py-0.5 rounded-full bg-teal-100 text-teal-800 font-mono-code font-bold text-[10px] uppercase tracking-wider">
                            DIRECTORATE COMMAND NOTIFICATION
                        </span>
                        <div class="text-xs font-black text-slate-900 mt-0.5">HEAD OF FORENSIC DIRECTORATE</div>
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

                <h3 class="text-xl font-black text-slate-900 tracking-tight leading-snug">
                    Account Approved by Head of Forensic Directorate!
                </h3>

                <p class="text-xs sm:text-sm text-slate-600 max-w-md mx-auto leading-relaxed">
                    Your user account <span id="popoverOfficerName"
                        class="font-extrabold text-slate-900 bg-teal-50 px-2 py-0.5 rounded border border-teal-200 hidden"></span>
                    has been officially reviewed, verified, and approved by the Head of Forensic Directorate. You are
                    now authorized to sign in to the NACOC Forensic Inventory Management System (FIMS).
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
        function closeLoginApprovedPopover() {
            const modal = document.getElementById('loginAccountApprovedPopoverModal');
            if (modal) modal.classList.add('hidden');
            localStorage.removeItem('nacoc_pending_login_id');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('loginAccountApprovedPopoverModal');
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('account_approved') || urlParams.has('approved')) {
                if (modal) modal.classList.remove('hidden');
                const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({
                    path: cleanUrl
                }, '', cleanUrl);
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

                    if (inlineBox && inlineText) {
                        inlineText.innerText = errMsg;
                        inlineBox.classList.remove('hidden');
                    }
                    showErrorToast(errMsg);
                }
            } catch (err) {
                // If fetch fails (e.g. network error), fallback to normal form submission
                console.warn('AJAX login error, submitting via standard POST:', err);
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
