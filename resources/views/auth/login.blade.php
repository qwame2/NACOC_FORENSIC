<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In | NACOC Forensic Directorate</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif

    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #F8FAFC;
            color: #0F172A;
        }
        .font-mono-code {
            font-family: 'JetBrains Mono', monospace;
        }
        .white-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between bg-slate-50 text-slate-900 antialiased selection:bg-emerald-600 selection:text-white transition-opacity duration-300 opacity-100">

    <!-- TOP SYSTEM STATUS BAR (WHITE THEME) -->
    <header class="w-full px-6 py-4 flex items-center justify-between border-b border-slate-200 bg-white shadow-2xs">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-white p-0.5 border border-slate-200 flex items-center justify-center shadow-xs overflow-hidden">
                <img src="{{ asset('NACOC LOGO.jfif') }}" alt="NACOC Logo" class="w-full h-full object-contain">
            </div>
            <div>
                <div class="font-extrabold text-slate-900 tracking-wider text-xs font-mono-code leading-none mb-0.5">NACOC FORENSIC DIRECTORATE</div>
                <div class="text-[10px] text-blue-600 font-semibold uppercase tracking-wider">NATIONAL NARCOTICS CONTROL COMMISSION</div>
            </div>
        </div>

        <div class="flex items-center space-x-4">
            <div class="text-right hidden sm:block">
                <div class="text-xs font-semibold text-slate-800" id="liveClock">2026-09-05 00:00:00 UTC</div>
                <div class="text-[10px] font-mono-code text-blue-600 font-bold">SECURE PORTAL v4.2</div>
            </div>
        </div>
    </header>

    <!-- MAIN LOGIN CONTAINER (WHITE BG) -->
    <main class="flex-1 flex items-center justify-center p-4 my-8">
        <div class="w-full max-w-md space-y-6">
            
            <!-- BRAND HEADER INSIGNIA -->
            <div class="text-center space-y-2">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-white p-1.5 shadow-md border border-slate-200 mb-1 overflow-hidden">
                    <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Official Logo" class="w-full h-full object-contain">
                </div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Sign In to Forensic Portal</h1>
                <p class="text-xs text-slate-500 max-w-xs mx-auto">
                    Enter your official service credentials to access restricted case files and exhibit dockets
                </p>
            </div>



            <!-- MAIN LOGIN CARD (WHITE CARD) -->
            <div class="white-card p-6 sm:p-8 rounded-2xl space-y-5">

                @include('partials.flash')

                @if ($errors->any())
                    <div class="p-3.5 bg-red-50 border border-red-200 rounded-xl text-xs font-semibold text-red-800 flex items-center space-x-2">
                        <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif
                
                <form id="loginForm" method="POST" action="{{ url('/login') }}">
                    @csrf
                    <div class="space-y-4">
                        
                        <!-- SERVICE ID / EMAIL INPUT -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 font-mono-code uppercase mb-1.5">Username</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </div>
                                <input 
                                    type="text" 
                                    id="serviceId" 
                                    name="login_id"
                                    required 
                                    value="{{ old('login_id') }}" 
                                    placeholder="e.g. NFC-89201 or smensah" 
                                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 font-mono-code focus:outline-none focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/15 transition-all"
                                >
                            </div>
                        </div>

                        <!-- PASSWORD / PIN INPUT -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold text-slate-700 font-mono-code uppercase">Password</label>
                                <a href="#" onclick="showResetToast()" class="text-[10px] font-mono-code text-blue-600 hover:underline">Forgot Passcode?</a>
                            </div>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </div>
                                <input 
                                    type="password" 
                                    id="passwordInput" 
                                    name="password"
                                    required 
                                    placeholder="Enter Security Passcode" 
                                    class="w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 font-mono-code focus:outline-none focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/15 transition-all"
                                >
                                <button 
                                    type="button" 
                                    onclick="togglePasswordVisibility()" 
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-700"
                                >
                                    <svg id="eyeIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            </div>
                        </div>



                        <!-- REMEMBER ME -->
                        <div class="flex items-center justify-between pt-1">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" checked class="w-4 h-4 rounded bg-slate-100 border-slate-300 text-blue-600 focus:ring-blue-600">
                                <span class="text-xs text-slate-600">Remember credentials</span>
                            </label>

                            <span class="text-[10px] text-emerald-600 font-mono-code font-semibold flex items-center gap-1">
                                <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                2FA Verified
                            </span>
                        </div>

                        <!-- SUBMIT BUTTON -->
                        <button 
                            type="submit" 
                            id="submitBtn" 
                            class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold rounded-xl shadow-md shadow-emerald-600/25 border border-emerald-500 transition-all hover:scale-[1.01] flex items-center justify-center space-x-2 cursor-pointer mt-2"
                        >
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            <span id="btnText">Sign In to Portal</span>
                        </button>

                        <!-- REGISTER LINK -->
                        <div class="text-center pt-3 border-t border-slate-100">
                            <p class="text-xs text-slate-600">
                                Don't have an account? 
                                <a href="{{ url('/register') }}" onclick="navigateWithFade(event, '{{ url('/register') }}')" class="font-bold text-blue-600 hover:text-blue-700 hover:underline transition-colors">Register</a>
                            </p>
                        </div>

                    </div>
                </form>

            </div>

            <!-- SECURITY BADGE FOOTER -->
            <div class="text-center text-[11px] text-slate-500 font-mono-code">
                NACOC FORENSIC DIRECTORATE &bull; HQ ACCRA, GHANA<br>
                <span>ISO/IEC 17025 ACCREDITED LAW ENFORCEMENT LAB</span>
            </div>

        </div>
    </main>

    <!-- FOOTER BAR (WHITE THEME) -->
    <footer class="w-full px-6 py-3 border-t border-slate-200 bg-white flex flex-col sm:flex-row items-center justify-between text-[11px] text-slate-500 font-mono-code gap-2">
        <div>
            Official Property of the Narcotics Control Commission, Ghana
        </div>
        <div class="flex items-center space-x-4">
            <a href="#" class="hover:text-slate-800">Security Policy</a>
            <span>&bull;</span>
            <a href="#" class="hover:text-slate-800">Chain of Custody Spec</a>
            <span>&bull;</span>
            <a href="#" class="hover:text-slate-800">IT Support Desk</a>
        </div>
    </footer>

    <!-- ACCOUNT APPROVED BY HEAD OF FORENSIC DIRECTORATE POPOVER MODAL CONTAINER -->
    <div id="loginAccountApprovedPopoverModal" class="fixed inset-0 z-50 bg-slate-900/70 backdrop-blur-md flex items-center justify-center p-4 transition-all duration-300">
        <div class="bg-white rounded-3xl border border-emerald-500/30 shadow-2xl max-w-lg w-full p-6 sm:p-8 space-y-6 relative transform transition-all scale-100">
            
            <!-- HEADER -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 p-1 border border-emerald-200 flex items-center justify-center shadow-xs overflow-hidden">
                        <img src="{{ asset('nacoc.jfif') }}" alt="NACOC Logo" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-mono-code font-bold text-[10px] uppercase tracking-wider">
                            DIRECTORATE COMMAND NOTIFICATION
                        </span>
                        <div class="text-xs font-black text-slate-900 mt-0.5">HEAD OF FORENSIC DIRECTORATE</div>
                    </div>
                </div>
                <button onclick="closeLoginApprovedPopover()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center text-lg font-bold cursor-pointer transition-colors">&times;</button>
            </div>

            <!-- CONTENT -->
            <div class="text-center space-y-3 py-2">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 shadow-inner mb-1">
                    <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                
                <h3 class="text-xl font-black text-slate-900 tracking-tight leading-snug">
                    Account Approved by Head of Forensic Directorate!
                </h3>
                
                <p class="text-xs sm:text-sm text-slate-600 max-w-md mx-auto leading-relaxed">
                    Your user account has been officially reviewed, verified, and approved by the Head of Forensic Directorate. You are now authorized to sign in to the NACOC Forensic Portal.
                </p>
            </div>

            <!-- STATUS BOX -->
            <div class="bg-emerald-50/70 border border-emerald-200 rounded-2xl p-4 font-mono-code text-xs space-y-2">
                <div class="flex items-center justify-between text-emerald-900 font-bold">
                    <span>AUTHORIZATION STATUS:</span>
                    <span class="px-2 py-0.5 bg-emerald-600 text-white rounded text-[10px] uppercase">APPROVED &amp; ACTIVE</span>
                </div>
                <p class="text-[11px] text-emerald-800 font-sans">
                    Please enter your Service ID / Username and Password below to access your unit workspace.
                </p>
            </div>

            <!-- ACTION BUTTON -->
            <div class="pt-2">
                <button onclick="closeLoginApprovedPopover()" class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-extrabold rounded-xl shadow-lg shadow-emerald-600/30 transition-all cursor-pointer flex items-center justify-center space-x-2">
                    <span>Proceed to Login</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </div>

        </div>
    </div>

    <!-- TOAST NOTIFICATION -->
    <div id="toastNotification" class="fixed bottom-5 right-5 z-50 hidden transform transition-all duration-300 translate-y-2">
        <div class="flex items-center space-x-3 px-4 py-3 bg-slate-900 text-white rounded-xl shadow-xl">
            <div class="w-7 h-7 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <div class="text-xs font-bold text-white" id="toastTitle">Notice</div>
                <div class="text-[11px] text-slate-300" id="toastMessage">Information updated.</div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        function showLoginApprovedPopover() {
            const modal = document.getElementById('loginAccountApprovedPopoverModal');
            if (modal) modal.classList.remove('hidden');
        }

        function closeLoginApprovedPopover() {
            const modal = document.getElementById('loginAccountApprovedPopoverModal');
            if (modal) modal.classList.add('hidden');
        }

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
        function handleLoginSubmit(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const targetPortal = '/cases';

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

            setTimeout(() => {
                // Determine base URL path
                const currentPath = window.location.pathname;
                let prefix = '';
                if (currentPath.includes('/public')) {
                    prefix = currentPath.substring(0, currentPath.indexOf('/public') + 7);
                }
                
                window.location.href = prefix + targetPortal;
            }, 1000);
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
