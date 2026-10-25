@php
    $hasFlash = session('status') || session('success') || session('error') || session('warning') || session('info') || (isset($errors) && $errors->any());
@endphp

@if ($hasFlash)
    @php
        $statusMsg = session('status') ?? session('success') ?? session('info');
        $errorMsg = session('error');
        $warningMsg = session('warning');
        $validationErrors = (isset($errors) && $errors->any()) ? $errors->all() : [];

        $isError = !empty($errorMsg) || count($validationErrors) > 0;
        $isWarning = !empty($warningMsg);

        // Derive title
        if ($isError) {
            $checkText = strtolower($errorMsg ?? ($validationErrors[0] ?? ''));
            if (str_contains($checkText, 'password') || str_contains($checkText, 'credential') || str_contains($checkText, 'login') || str_contains($checkText, 'approval')) {
                $title = 'Security & Authentication Alert';
            } else {
                $title = 'Action Required';
            }
        } elseif ($isWarning) {
            $title = 'System Warning';
        } else {
            $checkText = strtolower((string)$statusMsg);
            if (str_contains($checkText, 'profile') || str_contains($checkText, 'updated') || str_contains($checkText, 'saved') || str_contains($checkText, 'changed')) {
                $title = 'Changes Saved Successfully';
            } elseif (str_contains($checkText, 'approved')) {
                $title = 'Docket Approved';
            } elseif (str_contains($checkText, 'login') || str_contains($checkText, 'logged in')) {
                $title = 'Login Successful';
            } elseif (str_contains($checkText, 'logout') || str_contains($checkText, 'logged out')) {
                $title = 'Officer Logged Out';
            } else {
                $title = 'System Notification';
            }
        }
    @endphp

    <div id="topRightFlashContainer" 
         class="fixed top-5 right-5 z-[999999] pointer-events-none flex flex-col items-end gap-3 max-w-sm w-[calc(100vw-2.5rem)] font-sans"
         style="position: fixed; top: 1.25rem; right: 1.25rem; z-index: 999999; max-width: 26rem; width: calc(100vw - 2.5rem); pointer-events: none;">
        
        <div id="topRightFlashToast" 
             role="alert" 
             aria-live="assertive" 
             aria-atomic="true"
             class="pointer-events-auto w-full rounded-2xl shadow-2xl border backdrop-blur-md overflow-hidden transition-all duration-300 transform translate-x-0 opacity-100 {{ $isError ? 'bg-slate-900/95 text-white border-rose-500/50 shadow-rose-950/30' : ($isWarning ? 'bg-slate-900/95 text-white border-amber-500/50 shadow-amber-950/30' : 'bg-slate-900/95 text-white border-emerald-500/40 shadow-emerald-950/30') }}"
             style="pointer-events: auto; animation: flashSlideInRight 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;">
            
            <div class="p-4 flex items-start gap-3.5">
                {{-- ICON --}}
                <div class="shrink-0 mt-0.5">
                    @if ($isError)
                        <div class="w-8 h-8 rounded-xl bg-rose-600/20 border border-rose-500/40 text-rose-400 flex items-center justify-center shadow-xs">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                    @elseif ($isWarning)
                        <div class="w-8 h-8 rounded-xl bg-amber-500/20 border border-amber-500/40 text-amber-300 flex items-center justify-center shadow-xs">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    @else
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 flex items-center justify-center shadow-xs">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </div>
                    @endif
                </div>

                {{-- CONTENT --}}
                <div class="flex-1 min-w-0 pr-1">
                    <div class="flex items-center gap-1.5 font-mono-code font-bold text-[10.5px] uppercase tracking-wider {{ $isError ? 'text-rose-400' : ($isWarning ? 'text-amber-300' : 'text-emerald-400') }}">
                        <span>{{ $title }}</span>
                    </div>

                    @if ($statusMsg && !$isError)
                        <div class="text-xs text-slate-200 font-sans mt-1 leading-snug font-medium">
                            {{ $statusMsg }}
                        </div>
                    @endif

                    @if (!empty($errorMsg))
                        <div class="text-xs text-rose-100 font-sans mt-1 leading-snug font-medium">
                            {{ $errorMsg }}
                        </div>
                    @endif

                    @if (count($validationErrors) > 0)
                        <ul class="text-xs text-rose-200/90 font-sans mt-1.5 space-y-1 font-medium list-disc list-inside">
                            @foreach ($validationErrors as $vErr)
                                <li>{{ $vErr }}</li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($warningMsg)
                        <div class="text-xs text-amber-200 font-sans mt-1 leading-snug font-medium">
                            {{ $warningMsg }}
                        </div>
                    @endif
                </div>

                {{-- DISMISS BUTTON --}}
                <button type="button" 
                        onclick="dismissTopRightFlashToast()" 
                        class="text-slate-400 hover:text-white transition-colors p-1 rounded-lg hover:bg-slate-800 shrink-0 cursor-pointer"
                        title="Dismiss notification">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- PROGRESS TIMEOUT BAR --}}
            <div class="w-full bg-slate-800/80 h-1 overflow-hidden">
                <div id="flashProgressBar" class="h-full {{ $isError ? 'bg-rose-500' : ($isWarning ? 'bg-amber-400' : 'bg-emerald-500') }}" style="width: 100%; transition: width 6s linear;"></div>
            </div>
        </div>
    </div>

    <style>
        @keyframes flashSlideInRight {
            0% {
                transform: translateX(110%);
                opacity: 0;
            }
            100% {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes flashSlideOutRight {
            0% {
                transform: translateX(0);
                opacity: 1;
            }
            100% {
                transform: translateX(110%);
                opacity: 0;
            }
        }
    </style>

    <script>
        function dismissTopRightFlashToast() {
            const toast = document.getElementById('topRightFlashToast');
            if (toast) {
                toast.style.animation = 'flashSlideOutRight 0.3s cubic-bezier(0.4, 0, 1, 1) forwards';
                setTimeout(() => {
                    const container = document.getElementById('topRightFlashContainer');
                    if (container) container.remove();
                }, 310);
            }
        }

        (function() {
            const bar = document.getElementById('flashProgressBar');
            if (bar) {
                setTimeout(() => {
                    bar.style.width = '0%';
                }, 40);
            }
            setTimeout(dismissTopRightFlashToast, 6000);
        })();
    </script>
@endif

<script>
    // Global client-side trigger for top-right flash notifications
    if (typeof window.showFlashToast !== 'function') {
        window.showFlashToast = function(message, type = 'success', title = null) {
            let container = document.getElementById('topRightFlashContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'topRightFlashContainer';
                container.className = 'fixed top-5 right-5 z-[999999] pointer-events-none flex flex-col items-end gap-3 max-w-sm w-[calc(100vw-2.5rem)] font-sans';
                container.style.cssText = 'position: fixed; top: 1.25rem; right: 1.25rem; z-index: 999999; max-width: 26rem; width: calc(100vw - 2.5rem); pointer-events: none;';
                document.body.appendChild(container);
            }

            const toastId = 'clientToast_' + Date.now();
            const isError = type === 'error' || type === 'danger';
            const isWarning = type === 'warning';
            const finalTitle = title || (isError ? 'Action Required' : (isWarning ? 'System Warning' : 'Changes Saved Successfully'));

            const toast = document.createElement('div');
            toast.id = toastId;
            toast.className = `pointer-events-auto w-full rounded-2xl shadow-2xl border backdrop-blur-md overflow-hidden transition-all duration-300 transform translate-x-0 opacity-100 ${
                isError ? 'bg-slate-900/95 text-white border-rose-500/50 shadow-rose-950/30' :
                (isWarning ? 'bg-slate-900/95 text-white border-amber-500/50 shadow-amber-950/30' :
                'bg-slate-900/95 text-white border-emerald-500/40 shadow-emerald-950/30')
            }`;
            toast.style.cssText = 'pointer-events: auto; animation: flashSlideInRight 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;';

            toast.innerHTML = `
                <div class="p-4 flex items-start gap-3.5">
                    <div class="shrink-0 mt-0.5">
                        <div class="w-8 h-8 rounded-xl ${isError ? 'bg-rose-600/20 border border-rose-500/40 text-rose-400' : (isWarning ? 'bg-amber-500/20 border border-amber-500/40 text-amber-300' : 'bg-emerald-500/20 border border-emerald-500/40 text-emerald-400')} flex items-center justify-center shadow-xs">
                            ${isError 
                                ? '<svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>'
                                : (isWarning 
                                    ? '<svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                                    : '<svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>')}
                        </div>
                    </div>
                    <div class="flex-1 min-w-0 pr-1">
                        <div class="flex items-center gap-1.5 font-mono-code font-bold text-[10.5px] uppercase tracking-wider ${isError ? 'text-rose-400' : (isWarning ? 'text-amber-300' : 'text-emerald-400')}">
                            <span>${finalTitle}</span>
                        </div>
                        <div class="text-xs ${isError ? 'text-rose-100' : (isWarning ? 'text-amber-100' : 'text-slate-200')} font-sans mt-1 leading-snug font-medium">
                            ${message}
                        </div>
                    </div>
                    <button type="button" onclick="dismissCustomFlashToast('${toastId}')" class="text-slate-400 hover:text-white transition-colors p-1 rounded-lg hover:bg-slate-800 shrink-0 cursor-pointer" title="Dismiss">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="w-full bg-slate-800/80 h-1 overflow-hidden">
                    <div id="bar_${toastId}" class="h-full ${isError ? 'bg-rose-500' : (isWarning ? 'bg-amber-400' : 'bg-emerald-500')}" style="width: 100%; transition: width 6s linear;"></div>
                </div>
            `;

            container.appendChild(toast);

            setTimeout(() => {
                const b = document.getElementById('bar_' + toastId);
                if (b) b.style.width = '0%';
            }, 40);

            setTimeout(() => {
                dismissCustomFlashToast(toastId);
            }, 6000);
        };

        window.dismissCustomFlashToast = function(id) {
            const toast = document.getElementById(id);
            if (toast) {
                toast.style.animation = 'flashSlideOutRight 0.3s cubic-bezier(0.4, 0, 1, 1) forwards';
                setTimeout(() => {
                    toast.remove();
                }, 310);
            }
        };
    }
</script>
