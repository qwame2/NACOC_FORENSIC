<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Staff Password Reset Approvals | NACOC Forensic</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    @endif

    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #F6F7F9;
            color: #0F172A;
            -webkit-font-smoothing: antialiased;
        }
        .font-mono-code {
            font-family: 'JetBrains Mono', ui-monospace, monospace;
        }
        .tnum {
            font-variant-numeric: tabular-nums;
        }
        a[href], button:not(:disabled), select:not(:disabled) {
            cursor: pointer;
        }
        button:disabled, select:disabled {
            cursor: not-allowed;
            opacity: 0.65;
        }
        a:focus-visible, button:not(:disabled):focus-visible, input:focus-visible, select:focus-visible {
            outline: 2px solid #059669;
            outline-offset: 2px;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-[#F6F7F9] text-slate-900 antialiased selection:bg-emerald-700 selection:text-white">

    <div class="flex min-h-screen lg:h-screen overflow-hidden">

        @if(request()->is('Forensic_Head*'))
            @include('Forensic_Head.partials.sidebar')
        @else
            @include('Crime_Scene_Support.partials.sidebar')
        @endif

        <div class="flex-1 flex flex-col min-w-0 bg-[#F6F7F9] overflow-hidden">

            <!-- TOP COMMAND HEADER -->
            <header class="h-16 bg-white shadow-2xs px-4 sm:px-6 flex items-center justify-between gap-4 shrink-0 z-20 border-b border-slate-200/70">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200/70 flex items-center justify-center shrink-0" aria-hidden="true">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-sm font-extrabold text-slate-900 tracking-tight truncate">
                            Staff Password Reset Approvals
                        </h1>
                        <p class="text-[11px] text-slate-500 font-medium truncate">
                            Approve staff password reset requests in your unit to dispatch their 6-digit Gmail OTP verification code
                        </p>
                    </div>
                </div>

                @php
                    $headFullname = auth()->user()->fullname ?? 'Unit Head';
                    $headParts = preg_split('/\s+/', trim((string) $headFullname)) ?: [];
                    $headInitials = count($headParts) >= 2
                        ? strtoupper(substr($headParts[0], 0, 1) . substr(end($headParts), 0, 1))
                        : strtoupper(substr((string) $headFullname, 0, 2));
                @endphp
                <div class="flex items-center gap-3 shrink-0 pl-2">
                    <div class="hidden sm:block text-right">
                        <div class="text-xs font-extrabold text-slate-900 leading-tight">{{ $headFullname }}</div>
                        <div class="text-[11px] text-emerald-700 font-mono-code font-semibold mt-0.5">
                            {{ (auth()->user()->unit ?? 'Operational Unit') . ' Head' }}
                        </div>
                    </div>
                    <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-extrabold text-xs uppercase shrink-0 shadow-xs">
                        {{ $headInitials }}
                    </div>
                </div>
            </header>

            <!-- SCROLLABLE MAIN WORKSPACE -->
            <main class="flex-1 overflow-y-auto px-4 sm:px-6 py-6 space-y-6">

                @include('partials.flash')

                <!-- LIVE FEEDBACK TOAST BANNER -->
                <div id="passwordFeedbackBanner" style="display: none;" class="hidden rounded-2xl bg-white border border-slate-200 shadow-sm px-5 py-3.5 text-xs font-semibold items-center justify-between gap-3 text-slate-800" role="status" aria-live="polite">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span id="passwordFeedbackIcon" class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0" aria-hidden="true">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                        <span id="passwordFeedbackMessage" class="truncate"></span>
                    </div>
                    <button type="button" onclick="hidePasswordFeedback()" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-bold transition-colors shrink-0">
                        Dismiss
                    </button>
                </div>

                <!-- COMMAND OVERVIEW + 4-COLUMN KPI STRIP -->
                <section class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="px-6 py-5 sm:px-7 sm:py-6 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <div class="max-w-2xl space-y-1">
                            <div class="text-[11px] font-mono-code font-bold uppercase tracking-widest text-emerald-700">
                                Unit Credential Governance &bull; {{ $viewerUnit?->value ?? (auth()->user()->unit ?? 'Forensic Division') }}
                            </div>
                            <h2 class="text-lg sm:text-xl font-extrabold tracking-tight text-slate-900">
                                Password Reset &amp; OTP Dispatch Authorization
                            </h2>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Staff officers cannot receive a password reset OTP until approved here. Clicking <strong class="text-slate-800">Approve &amp; Send OTP</strong> generates a single-use 6-digit code (valid for 5 minutes) and delivers it straight to the officer's registered Gmail.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-slate-100 bg-slate-50/40">
                        <div class="p-4 sm:px-6">
                            <div class="text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-amber-700">Pending Approval</div>
                            <div id="statPendingCount" class="text-2xl font-extrabold text-slate-900 tnum mt-1">{{ $stats['pending'] ?? 0 }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Awaiting your OTP release</div>
                        </div>
                        <div class="p-4 sm:px-6">
                            <div class="text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-teal-700">Approved (OTP Sent)</div>
                            <div id="statApprovedCount" class="text-2xl font-extrabold text-slate-900 tnum mt-1">{{ $stats['approved'] ?? 0 }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">6-digit code sent to Gmail</div>
                        </div>
                        <div class="p-4 sm:px-6">
                            <div class="text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-emerald-700">Completed Resets</div>
                            <div id="statCompletedCount" class="text-2xl font-extrabold text-slate-900 tnum mt-1">{{ $stats['completed'] ?? 0 }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">New password saved</div>
                        </div>
                        <div class="p-4 sm:px-6">
                            <div class="text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-slate-500">Declined / Revoked</div>
                            <div id="statDeclinedCount" class="text-2xl font-extrabold text-slate-900 tnum mt-1">{{ $stats['declined'] ?? 0 }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Requests rejected</div>
                        </div>
                    </div>
                </section>

                <!-- FILTER TABS, SEARCH & REQUESTS TABLE -->
                <section class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="p-4 sm:px-6 border-b border-slate-200/80 flex flex-col lg:flex-row lg:items-center justify-between gap-3.5">
                        <!-- STATUS FILTER PILLS -->
                        <div class="flex items-center gap-1.5 flex-wrap" id="statusFilterTabs">
                            @php
                                $tabs = [
                                    'all' => 'All Requests',
                                    'pending' => 'Pending Approval',
                                    'approved' => 'OTP Sent',
                                    'completed' => 'Completed',
                                    'declined' => 'Declined',
                                ];
                            @endphp
                            @foreach ($tabs as $tabKey => $tabLabel)
                                <button type="button"
                                    data-status-tab="{{ $tabKey }}"
                                    onclick="setPasswordStatusFilter('{{ $tabKey }}')"
                                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ $statusFilter === $tabKey ? 'bg-emerald-600 text-white shadow-xs shadow-emerald-600/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200/80 hover:text-slate-900' }}">
                                    <span>{{ $tabLabel }}</span>
                                    @if ($tabKey === 'pending')
                                        @php $pendingInit = (int) ($stats['pending'] ?? 0); @endphp
                                        <span id="tabPendingBadge" class="{{ $pendingInit > 0 ? 'inline-flex' : 'hidden' }} ml-1 items-center justify-center px-1.5 py-0.2 rounded-full text-[10px] font-mono-code font-extrabold {{ $statusFilter === 'pending' ? 'bg-white text-emerald-700' : 'bg-red-600 text-white' }}">
                                            {{ $pendingInit }}
                                        </span>
                                    @endif
                                </button>
                            @endforeach
                        </div>

                        <!-- UNIT FILTER (FORENSIC DIRECTORATE ONLY) + LIVE SEARCH -->
                        <div class="flex items-center gap-2.5 flex-wrap sm:flex-nowrap">
                            @if (empty($isUnitScoped))
                                <select id="unitFilterSelect" onchange="onPasswordUnitFilterChange()"
                                    class="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-700 focus:outline-none">
                                    <option value="all" {{ $unitFilter === 'all' ? 'selected' : '' }}>All Operational Units</option>
                                    <option value="css" {{ $unitFilter === 'css' ? 'selected' : '' }}>Crime Scene Support</option>
                                    <option value="lab" {{ $unitFilter === 'lab' ? 'selected' : '' }}>Lab Analysis</option>
                                    <option value="em" {{ $unitFilter === 'em' ? 'selected' : '' }}>Evidence Management</option>
                                    <option value="dpf" {{ $unitFilter === 'dpf' ? 'selected' : '' }}>Digital &amp; Physical Forensics</option>
                                </select>
                            @endif

                            <div class="relative flex-1 sm:w-64">
                                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <input type="search" id="passwordSearchInput"
                                    value="{{ $search }}"
                                    oninput="debouncedPasswordSearch()"
                                    placeholder="Search name, Service ID, Gmail..."
                                    class="w-full pl-9 pr-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-medium text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none">
                            </div>

                            <select id="passwordPerPageSelect"
                                onchange="onPasswordPerPageChange()"
                                aria-label="Requests per page"
                                class="px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-mono-code font-bold text-slate-700 focus:bg-white focus:outline-none shrink-0">
                                @foreach ([5, 10, 15, 25, 50] as $ppOpt)
                                    <option value="{{ $ppOpt }}" {{ (int) ($perPage ?? $requests->perPage() ?? 10) === $ppOpt ? 'selected' : '' }}>{{ $ppOpt }} / page</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div id="passwordRequestsTableContainer">
                        @include('head.partials.password_requests_table')
                    </div>
                </section>

            </main>
        </div>
    </div>

    <script>
        const CURRENT_PASSWORD_ENDPOINT = @json($currentEndpoint);
        let activePasswordStatus = @json($statusFilter);
        let activePasswordPage = @json((int) $requests->currentPage());
        let activePasswordPerPage = @json((int) ($perPage ?? $requests->perPage() ?? 10));
        let lastPasswordSignature = @json($initialSignature ?? null);
        let passwordSearchTimer = null;
        let isPollingPasswordTable = false;
        let isActionInFlight = false;

        function showPasswordFeedback(message, isError = false) {
            const banner = document.getElementById('passwordFeedbackBanner');
            const msgEl = document.getElementById('passwordFeedbackMessage');
            const iconEl = document.getElementById('passwordFeedbackIcon');
            if (!banner || !msgEl) return;

            msgEl.textContent = message;
            if (iconEl) {
                iconEl.className = isError
                    ? 'w-6 h-6 rounded-lg bg-rose-50 text-rose-700 flex items-center justify-center shrink-0'
                    : 'w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0';
            }
            banner.classList.remove('hidden');
            banner.style.display = 'flex';
        }

        function hidePasswordFeedback() {
            const banner = document.getElementById('passwordFeedbackBanner');
            if (banner) {
                banner.style.display = 'none';
                banner.classList.add('hidden');
            }
        }

        function syncTabPendingBadge(pendingCount) {
            const badge = document.getElementById('tabPendingBadge');
            if (!badge) return;
            const num = parseInt(pendingCount !== undefined ? pendingCount : badge.textContent, 10) || 0;
            badge.textContent = String(num);
            const colorCls = activePasswordStatus === 'pending'
                ? 'bg-white text-emerald-700'
                : 'bg-red-600 text-white';
            badge.className = `${num > 0 ? 'inline-flex' : 'hidden'} ml-1 items-center justify-center px-1.5 py-0.2 rounded-full text-[10px] font-mono-code font-extrabold ${colorCls}`;
        }

        function applyStatsToDom(stats) {
            if (!stats) return;
            const map = {
                statPendingCount: stats.pending,
                statApprovedCount: stats.approved,
                statCompletedCount: stats.completed,
                statDeclinedCount: stats.declined,
            };
            Object.entries(map).forEach(([id, val]) => {
                const el = document.getElementById(id);
                if (el && val !== undefined) el.textContent = String(val);
            });

            if (stats.pending !== undefined) {
                syncTabPendingBadge(stats.pending);
            }

            if (typeof window.nacocRefreshSidebarBadges === 'function') {
                window.nacocRefreshSidebarBadges();
            }
        }

        function setPasswordStatusFilter(status) {
            activePasswordStatus = status;
            activePasswordPage = 1;
            document.querySelectorAll('[data-status-tab]').forEach(btn => {
                const isSelected = btn.getAttribute('data-status-tab') === status;
                btn.className = 'px-3 py-1.5 rounded-xl text-xs font-bold transition-all ' + (
                    isSelected
                        ? 'bg-emerald-600 text-white shadow-xs shadow-emerald-600/20'
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200/80 hover:text-slate-900'
                );
            });
            syncTabPendingBadge();
            pollPasswordRequestsTable(true);
        }

        function onPasswordUnitFilterChange() {
            activePasswordPage = 1;
            pollPasswordRequestsTable(true);
        }

        function onPasswordPerPageChange() {
            const sel = document.getElementById('passwordPerPageSelect');
            activePasswordPerPage = Math.max(5, Math.min(50, parseInt(sel?.value || '10', 10) || 10));
            activePasswordPage = 1;
            pollPasswordRequestsTable(true);
        }

        function goToPasswordRequestsPage(page, event) {
            if (event) event.preventDefault();
            activePasswordPage = Math.max(1, parseInt(page, 10) || 1);
            pollPasswordRequestsTable(true);
            const container = document.getElementById('passwordRequestsTableContainer');
            if (container) {
                container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        function debouncedPasswordSearch() {
            if (passwordSearchTimer) clearTimeout(passwordSearchTimer);
            passwordSearchTimer = setTimeout(() => {
                passwordSearchTimer = null;
                activePasswordPage = 1;
                pollPasswordRequestsTable(true);
            }, 260);
        }

        /* ── Real-Time Silent Polling Engine for Password Reset & OTP Dispatch Authorization ── */
        async function pollPasswordRequestsTable(force = false) {
            if (isPollingPasswordTable || isActionInFlight) return;
            if (!force && (document.hidden || passwordSearchTimer !== null)) return;

            isPollingPasswordTable = true;
            const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
            const timeoutId = controller ? setTimeout(() => controller.abort(), 6000) : null;

            try {
                const params = new URLSearchParams();
                params.set('poll', '1');
                if (activePasswordStatus && activePasswordStatus !== 'all') {
                    params.set('status', activePasswordStatus);
                }
                const searchVal = (document.getElementById('passwordSearchInput')?.value || '').trim();
                if (searchVal) {
                    params.set('search', searchVal);
                }
                const unitSel = document.getElementById('unitFilterSelect');
                if (unitSel && unitSel.value) {
                    params.set('unit', unitSel.value);
                }
                if (activePasswordPerPage) {
                    params.set('per_page', String(activePasswordPerPage));
                }
                if (activePasswordPage > 1) {
                    params.set('page', String(activePasswordPage));
                }

                const res = await fetch(`${CURRENT_PASSWORD_ENDPOINT}?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    signal: controller ? controller.signal : undefined,
                });
                if (timeoutId) clearTimeout(timeoutId);
                if (!res.ok || isActionInFlight) return;

                const data = await res.json();
                if (!data || !data.success) return;

                if (data.stats) {
                    applyStatsToDom(data.stats);
                }

                // Skip DOM swap when signature is unchanged so hover/click/scroll states remain untouched
                if (!force && data.signature && data.signature === lastPasswordSignature) {
                    return;
                }
                if (data.signature) {
                    lastPasswordSignature = data.signature;
                }

                const container = document.getElementById('passwordRequestsTableContainer');
                if (container && typeof data.html === 'string') {
                    const prevScrollLeft = container.querySelector('.overflow-x-auto')?.scrollLeft || 0;
                    container.innerHTML = data.html;
                    if (prevScrollLeft > 0) {
                        const nextScrollWrap = container.querySelector('.overflow-x-auto');
                        if (nextScrollWrap) nextScrollWrap.scrollLeft = prevScrollLeft;
                    }
                }
            } catch (e) {
                // Silently ignore transient network failures during background polling
            } finally {
                if (timeoutId) clearTimeout(timeoutId);
                isPollingPasswordTable = false;
            }
        }
        window.refreshPasswordRequestsTable = () => pollPasswordRequestsTable(true);
        window.pollPasswordRequestsTable = pollPasswordRequestsTable;

        function broadcastPasswordResetEvent() {
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

        async function handlePasswordRequestAction(requestId, action, btnEl) {
            if (isActionInFlight) return;
            isActionInFlight = true;
            hidePasswordFeedback();

            const originalHtml = btnEl ? btnEl.innerHTML : '';
            if (btnEl) {
                btnEl.disabled = true;
                btnEl.innerHTML = action === 'approve'
                    ? '<span>Sending OTP...</span>'
                    : '<span>Updating...</span>';
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const endpoint = `/password-requests/${encodeURIComponent(requestId)}/${action === 'approve' ? 'approve' : 'decline'}`;

            try {
                const res = await fetch(endpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({}),
                });
                const data = await res.json();

                if (res.ok && data && data.success) {
                    showPasswordFeedback(data.message || 'Password reset request updated.', false);
                    if (data.stats) {
                        applyStatsToDom(data.stats);
                    }
                    broadcastPasswordResetEvent();
                    isActionInFlight = false;
                    await pollPasswordRequestsTable(true);
                    return;
                }

                if (btnEl) {
                    btnEl.disabled = false;
                    btnEl.innerHTML = originalHtml;
                }
                showPasswordFeedback((data && data.message) ? data.message : 'Unable to process request.', true);
            } catch (err) {
                if (btnEl) {
                    btnEl.disabled = false;
                    btnEl.innerHTML = originalHtml;
                }
                showPasswordFeedback('Network error while contacting FIMS server. Please try again.', true);
            } finally {
                isActionInFlight = false;
            }
        }

        // Seamless AJAX pagination inside the table container
        document.getElementById('passwordRequestsTableContainer')?.addEventListener('click', function(e) {
            const link = e.target.closest('a[href]');
            if (!link) return;
            if (link.closest('nav') || link.href.includes('page=')) {
                e.preventDefault();
                try {
                    const urlObj = new URL(link.href, window.location.origin);
                    activePasswordPage = Math.max(1, parseInt(urlObj.searchParams.get('page') || '1', 10) || 1);
                } catch (err) {
                    activePasswordPage = 1;
                }
                pollPasswordRequestsTable(true);
            }
        });

        // 1. Fast 3-second silent background polling interval
        setInterval(() => pollPasswordRequestsTable(false), 3000);

        // 2. Immediate silent check when window regains focus or tab visibility
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) pollPasswordRequestsTable(false);
        });
        window.addEventListener('focus', () => pollPasswordRequestsTable(false));

        // 3. Cross-tab & broadcast sync for instant updates when staff submit or complete a reset
        window.addEventListener('storage', (e) => {
            if (e.key === 'nacoc_badge_sync' || e.key === 'nacoc_password_reset_sync') {
                pollPasswordRequestsTable(true);
            }
        });
        window.addEventListener('nacoc:badge-update', () => pollPasswordRequestsTable(true));

        if (typeof BroadcastChannel !== 'undefined') {
            try {
                const resetChannel = new BroadcastChannel('nacoc_password_reset');
                resetChannel.addEventListener('message', () => pollPasswordRequestsTable(true));
            } catch (e) {}
        }
    </script>
</body>
</html>

