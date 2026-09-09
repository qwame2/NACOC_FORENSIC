@if (session('status') || session('success') || session('error') || session('warning') || session('info'))
    @php
        $statusMsg = session('status') ?? session('success') ?? session('info');
        $errorMsg = session('error');
        $warningMsg = session('warning');
        $msg = $statusMsg ?? $errorMsg ?? $warningMsg;
        $isErrorOrWarning = !empty($errorMsg) || !empty($warningMsg);
        
        $title = 'System Notification';
        if (!empty($errorMsg)) {
            $title = 'Action Failed';
        } elseif (!empty($warningMsg)) {
            $title = 'System Warning';
        } elseif (stripos($msg, 'Registration') !== false) {
            $title = 'Registration Successful';
        } elseif (stripos($msg, 'logged out') !== false || stripos($msg, 'logout') !== false) {
            $title = 'Officer Logged Out Successfully';
        } elseif (stripos($msg, 'Login') !== false) {
            $title = 'Login Successful';
        }
    @endphp

    <div id="topRightFlashToast" class="fixed top-5 right-5 z-50 max-w-md w-11/12 sm:w-auto transition-all duration-500 ease-out transform translate-y-0 opacity-100 pointer-events-auto">
        <div class="flex items-center space-x-3 p-4 bg-white text-slate-900 rounded-2xl shadow-2xl border-2 {{ $isErrorOrWarning ? 'border-red-500' : 'border-emerald-500' }}">
            <div class="w-10 h-10 rounded-xl {{ $isErrorOrWarning ? 'bg-red-50 text-red-600 border border-red-200' : 'bg-emerald-50 text-emerald-600 border border-emerald-200' }} flex items-center justify-center shrink-0 font-bold shadow-xs">
                @if ($isErrorOrWarning)
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                @else
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                @endif
            </div>
            <div class="flex-1 min-w-[220px] pr-2">
                <div class="text-xs font-black font-mono-code uppercase tracking-wider {{ $isErrorOrWarning ? 'text-red-700' : 'text-[#0B132B]' }} flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full {{ $isErrorOrWarning ? 'bg-red-500' : 'bg-emerald-500' }}"></span>
                    {{ $title }}
                </div>
                <div class="text-xs font-semibold text-slate-800 mt-1 leading-relaxed">
                    {{ $msg }}
                </div>
            </div>
            <button type="button" onclick="dismissTopRightFlashToast()" class="text-slate-400 hover:text-slate-800 p-1.5 rounded-lg transition-colors cursor-pointer hover:bg-slate-100">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    <script>
        function dismissTopRightFlashToast() {
            const toast = document.getElementById('topRightFlashToast');
            if (toast) {
                toast.classList.add('-translate-y-4', 'opacity-0');
                setTimeout(() => {
                    if (toast && toast.parentElement) {
                        toast.parentElement.removeChild(toast);
                    }
                }, 450);
            }
        }
        setTimeout(dismissTopRightFlashToast, 6000);
    </script>
@endif
