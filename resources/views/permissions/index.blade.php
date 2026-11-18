<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Unit Staff Permissions &amp; Access Control | NACOC Forensic</title>

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
        a[href], button:not(:disabled), select:not(:disabled), [role="switch"]:not(:disabled) {
            cursor: pointer;
        }
        button:disabled, select:disabled, [role="switch"]:disabled {
            cursor: not-allowed;
        }
        .officer-card {
            background: #FFFFFF;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 6px 18px -6px rgba(15, 23, 42, 0.06);
            transition: box-shadow 0.18s ease, transform 0.18s ease;
        }
        .officer-card:hover {
            box-shadow: 0 14px 28px -10px rgba(15, 23, 42, 0.11);
        }
        .cap-tile {
            transition: background-color 0.16s ease, box-shadow 0.16s ease, transform 0.12s ease;
        }
        .cap-tile:not(:disabled):active {
            transform: scale(0.992);
        }
        .metric-action-btn {
            transition: background-color 0.16s ease;
        }
        .metric-action-btn:hover {
            background-color: #F1F5F9;
        }
        a:focus-visible, button:not(:disabled):focus-visible, input:focus-visible, select:focus-visible {
            outline: 2px solid #059669;
            outline-offset: 2px;
        }
        @media (prefers-reduced-motion: reduce) {
            *, ::before, ::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
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
            <header class="h-16 bg-white shadow-2xs px-4 sm:px-6 flex items-center justify-between gap-4 shrink-0 z-20">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50/80 text-emerald-700 flex items-center justify-center shrink-0" aria-hidden="true">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-sm font-extrabold text-slate-900 tracking-tight truncate">
                            Unit Staff Permissions &amp; Access Control
                        </h1>
                        <p class="text-[11px] text-slate-500 font-medium truncate">
                            Configure operational forensic module permissions for staff accounts in your unit
                        </p>
                    </div>
                </div>

                <!-- UNIT HEAD IDENTITY -->
                @php
                    $headFullname = auth()->user()->fullname ?? 'Unit Head';
                    $headParts = preg_split('/\s+/', trim((string) $headFullname));
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
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-800 flex items-center justify-center font-extrabold text-xs uppercase shrink-0" aria-hidden="true">
                        {{ $headInitials }}
                    </div>
                </div>
            </header>

            <!-- SCROLLABLE MAIN WORKSPACE -->
            <main class="flex-1 overflow-y-auto px-4 sm:px-6 py-6 space-y-6">

                @include('partials.flash')

                <!-- LIVE AJAX NOTIFICATION BANNER (FALLBACK IF FLASH TOAST NOT PRESENT) -->
                <div id="permissionsFeedbackBanner" class="hidden rounded-2xl bg-white border border-slate-200/90 shadow-sm px-5 py-3.5 text-xs font-semibold flex items-center justify-between gap-3 text-slate-800" role="status" aria-live="polite">
                    <div class="flex items-center gap-2.5">
                        <span id="permissionsFeedbackIcon" class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0" aria-hidden="true">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                        <span id="permissionsFeedbackMessage"></span>
                    </div>
                    <button type="button" onclick="document.getElementById('permissionsFeedbackBanner').classList.add('hidden')" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-bold transition-colors">
                        Dismiss
                    </button>
                </div>

                @php
                    $defaultUnitFilter = $defaultUnitFilter ?? ($scopedUnitSlug ?? 'all');
                    $isUnitScoped = !empty($isUnitScoped);
                @endphp

                <!-- COMMAND OVERVIEW BANNER + INTEGRATED 3-COLUMN KPI STRIP -->
                <section class="bg-white rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-6 py-6 sm:px-7 sm:py-6">
                        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                            <div class="max-w-2xl space-y-1.5">
                                <div class="text-[11px] font-mono-code font-bold uppercase tracking-widest text-emerald-700">
                                    Controlling staff permissions
                                </div>
                                <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900">
                                    Staff-level access control
                                </h2>
                                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                    Enable or restrict individual forensic workspace capabilities per officer. Updates take effect immediately across unit navigation and case submission endpoints.
                                </p>
                            </div>

                            <!-- CLEAN UNBOXED ACCESS LEGEND -->
                            <div class="flex items-center gap-4 text-xs shrink-0">
                                <span class="inline-flex items-center gap-1.5 font-semibold text-emerald-700">
                                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Granted</span>
                                </span>
                                <span class="text-slate-300" aria-hidden="true">|</span>
                                <span class="inline-flex items-center gap-1.5 font-semibold text-slate-500">
                                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/>
                                    </svg>
                                    <span>Restricted</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 3-COLUMN ARCHITECTURAL KPI SUMMARY BAR (UNBOXED TYPOGRAPHY) -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 bg-slate-50/50">
                        <button type="button" onclick="applyStatusSelectAndFilter('all')" class="metric-action-btn p-5 text-left flex items-start justify-between gap-3">
                            <div>
                                <div class="text-[11px] font-mono-code font-bold uppercase tracking-wider text-slate-500">Total Unit Staff</div>
                                <div id="statTotalStaff" class="text-2xl font-extrabold text-slate-900 tnum mt-1">{{ $totalStaffCount ?? $officers->total() }}</div>
                                <div class="text-[11px] font-medium text-emerald-700 mt-1">Registered unit accounts</div>
                            </div>
                            <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </button>

                        <button type="button" onclick="applyStatusSelectAndFilter('approved')" class="metric-action-btn p-5 text-left flex items-start justify-between gap-3">
                            <div>
                                <div class="text-[11px] font-mono-code font-bold uppercase tracking-wider text-slate-500">Approved Clearance</div>
                                <div id="statApprovedStaff" class="text-2xl font-extrabold text-emerald-700 tnum mt-1">{{ $approvedCount ?? 0 }}</div>
                                <div class="text-[11px] font-medium text-slate-500 mt-1">Active cleared personnel</div>
                            </div>
                            <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </button>

                        <div class="p-5 text-left flex items-start justify-between gap-3">
                            <div>
                                <div class="text-[11px] font-mono-code font-bold uppercase tracking-wider text-slate-500">Custom Policies</div>
                                <div id="statCustomizedStaff" class="text-2xl font-extrabold text-slate-900 tnum mt-1">{{ $customizedCount ?? 0 }}</div>
                                <div class="text-[11px] font-medium text-slate-500 mt-1">Tailored capability restrictions</div>
                            </div>
                            <svg class="w-5 h-5 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                            </svg>
                        </div>
                    </div>
                </section>

                <!-- COMMAND CONTROL BAR: STAFF SEARCH & CLEARANCE FILTER -->
                <form id="permissionsFilterForm" method="GET" action="{{ $currentEndpoint }}" class="bg-white rounded-2xl shadow-sm p-4 sm:px-5 sm:py-4 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <input type="hidden" name="unit" id="filterUnitInput" value="{{ $unitFilter }}">
                    <input type="hidden" name="status" id="filterStatusInput" value="{{ $statusFilter }}">

                    <!-- Left: Staff Member Search Input -->
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2.5 sm:gap-3 flex-1 max-w-xl">
                        <label for="permissionsSearchInput" class="text-[11px] font-bold font-mono-code text-slate-500 uppercase tracking-wider shrink-0">
                            Search Staff:
                        </label>
                        <div class="relative w-full">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400" aria-hidden="true">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </span>
                            <input
                                type="text"
                                name="search"
                                id="permissionsSearchInput"
                                value="{{ $search ?? '' }}"
                                placeholder="Filter by officer name, service ID, or @username..."
                                data-table-filter="#permissionsTableContainer"
                                class="w-full min-h-[42px] pl-10 pr-10 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-mono-code placeholder:text-slate-400 focus:outline-none focus:bg-white focus:border-emerald-600 transition-colors"
                            >
                            <button
                                type="button"
                                id="clearPermissionsSearchBtn"
                                onclick="clearPermissionsSearch()"
                                aria-label="Clear search filter"
                                class="{{ empty($search) ? 'hidden' : 'inline-flex' }} absolute inset-y-0 right-0 pr-3 items-center text-slate-400 hover:text-slate-700 transition-colors"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Right: Clearance Filter -->
                    <div class="flex items-center justify-between lg:justify-end gap-3 shrink-0">
                        <label class="text-[11px] font-bold font-mono-code text-slate-500 uppercase tracking-wider" for="permStatusSelect">Clearance:</label>
                        <select id="permStatusSelect" onchange="setPermStatusFilter(this.value)" class="min-h-[42px] px-3.5 py-2 rounded-xl border border-slate-200 bg-slate-50 hover:bg-white hover:border-slate-300 text-xs font-semibold text-slate-800 focus:bg-white focus:border-emerald-600 transition-colors">
                            <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Accounts ({{ $totalStaffCount ?? $officers->total() }})</option>
                            <option value="approved" {{ $statusFilter === 'approved' ? 'selected' : '' }}>Approved Only ({{ $approvedCount ?? 0 }})</option>
                            <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending Approval ({{ max(0, ($totalStaffCount ?? 0) - ($approvedCount ?? 0)) }})</option>
                        </select>
                    </div>
                </form>

                <!-- PERMISSIONS MATRIX CONTAINER -->
                <section aria-label="Staff Operational Permissions Matrix">
                    <div id="permissionsTableContainer" class="relative">
                        @include('partials.permissions_table')
                    </div>
                </section>

            </main>
        </div>
    </div>

    <script>
        (function () {
            const ENDPOINT = @json($currentEndpoint);
            const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            function showFeedback(message, isError = false) {
                const banner = document.getElementById('permissionsFeedbackBanner');
                if (typeof window.showFlashToast === 'function') {
                    if (banner) banner.classList.add('hidden');
                    window.showFlashToast(
                        message,
                        isError ? 'error' : 'success',
                        isError ? 'Action Required' : 'Permissions Updated'
                    );
                    return;
                }
                if (banner) {
                    const msgEl = document.getElementById('permissionsFeedbackMessage');
                    const iconEl = document.getElementById('permissionsFeedbackIcon');
                    if (msgEl) msgEl.textContent = message;
                    if (iconEl) {
                        iconEl.classList.toggle('bg-rose-50', isError);
                        iconEl.classList.toggle('text-rose-700', isError);
                        iconEl.classList.toggle('bg-emerald-50', !isError);
                        iconEl.classList.toggle('text-emerald-700', !isError);
                    }
                    banner.classList.toggle('text-rose-800', isError);
                    banner.classList.toggle('text-emerald-900', !isError);
                    banner.classList.remove('hidden');
                }
            }

            function syncToggleVisual(toggleBtn, isEnabled) {
                if (!toggleBtn) return;
                toggleBtn.setAttribute('aria-checked', isEnabled ? 'true' : 'false');
                toggleBtn.classList.toggle('bg-emerald-50/70', isEnabled);
                toggleBtn.classList.toggle('hover:bg-emerald-50', isEnabled);
                toggleBtn.classList.toggle('bg-slate-50/80', !isEnabled);
                toggleBtn.classList.toggle('hover:bg-slate-100/70', !isEnabled);

                const cb = toggleBtn.querySelector('input[type="checkbox"]');
                if (cb) cb.checked = isEnabled;

                const iconBox = toggleBtn.querySelector('[data-cap-icon]');
                if (iconBox) {
                    iconBox.classList.toggle('bg-emerald-600', isEnabled);
                    iconBox.classList.toggle('text-white', isEnabled);
                    iconBox.classList.toggle('bg-white', !isEnabled);
                    iconBox.classList.toggle('text-slate-500', !isEnabled);
                }

                const catBadge = toggleBtn.querySelector('[data-cap-category]');
                if (catBadge) {
                    catBadge.classList.toggle('text-emerald-800', isEnabled);
                    catBadge.classList.toggle('text-slate-500', !isEnabled);
                }

                const track = toggleBtn.querySelector('[data-switch-track]');
                if (track) {
                    track.classList.toggle('bg-emerald-600', isEnabled);
                    track.classList.toggle('bg-slate-300', !isEnabled);
                }

                const thumb = toggleBtn.querySelector('[data-switch-thumb]');
                if (thumb) {
                    thumb.classList.toggle('translate-x-5', isEnabled);
                    thumb.classList.toggle('translate-x-0', !isEnabled);
                }

                const onGlyph = toggleBtn.querySelector('[data-switch-glyph-on]');
                const offGlyph = toggleBtn.querySelector('[data-switch-glyph-off]');
                if (onGlyph) onGlyph.classList.toggle('hidden', !isEnabled);
                if (offGlyph) offGlyph.classList.toggle('hidden', isEnabled);
            }

            function syncOfficerSummaryBar(officerId, enabledCount, totalCount) {
                const summaryEl = document.querySelector('[data-officer-summary="' + officerId + '"]');
                if (summaryEl && totalCount > 0) {
                    summaryEl.textContent = enabledCount + '/' + totalCount + ' active';
                }
                const barEl = document.querySelector('[data-officer-bar="' + officerId + '"]');
                if (barEl && totalCount > 0) {
                    const pct = Math.round((enabledCount / totalCount) * 100);
                    barEl.style.width = pct + '%';
                }
            }

            window.toggleOfficerCapability = function (toggleBtn, officerId) {
                if (!toggleBtn || toggleBtn.disabled) return;
                const current = toggleBtn.getAttribute('aria-checked') === 'true';
                syncToggleVisual(toggleBtn, !current);

                const allToggles = document.querySelectorAll('[data-perm-toggle="' + officerId + '"]');
                const enabledCount = Array.from(allToggles).filter((el) => el.getAttribute('aria-checked') === 'true').length;
                syncOfficerSummaryBar(officerId, enabledCount, allToggles.length);

                window.submitOfficerPermissions(null, officerId);
            };

            window.submitOfficerPermissions = async function (event, officerId, preset = '') {
                if (event) event.preventDefault();
                const form = document.getElementById('permForm-' + officerId);
                if (!form) return false;

                const toggles = form.querySelectorAll('[data-perm-toggle="' + officerId + '"]');
                const payload = {};
                if (preset) {
                    payload.preset = preset;
                } else {
                    payload.permissions = {};
                    toggles.forEach((btnEl) => {
                        const key = btnEl.getAttribute('data-perm-key');
                        if (key) payload.permissions[key] = btnEl.getAttribute('aria-checked') === 'true';
                    });
                }

                try {
                    const resp = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(payload)
                    });
                    const data = await resp.json();
                    if (!resp.ok || !data.success) {
                        showFeedback(data.message || 'Unable to update permissions.', true);
                        return false;
                    }

                    if (data.officer && data.officer.permissions) {
                        toggles.forEach((btnEl) => {
                            const key = btnEl.getAttribute('data-perm-key');
                            if (key && Object.prototype.hasOwnProperty.call(data.officer.permissions, key)) {
                                syncToggleVisual(btnEl, Boolean(data.officer.permissions[key]));
                            }
                        });
                        syncOfficerSummaryBar(officerId, data.officer.enabled, data.officer.total);
                    }

                    showFeedback(data.message || 'Permissions saved.', false);
                    pollPermissions(true);
                } catch (err) {
                    showFeedback('Network error saving permissions. Please verify your connection and retry.', true);
                }
                return false;
            };

            window.renderPermissionsSkeletonRows = function (count = 3) {
                let html = '<div class="space-y-4" role="status" aria-live="polite"><div class="text-xs font-mono-code font-semibold text-slate-500 px-1">Loading unit staff permission profiles...</div>';
                for (let i = 0; i < count; i++) {
                    html += '<div class="h-32 bg-white rounded-2xl shadow-sm w-full"></div>';
                }
                html += '</div>';
                return html;
            };

            function syncMetaStats() {
                const meta = document.getElementById('permissionsTableMeta');
                if (!meta) return;
                const totalEl = document.getElementById('statTotalStaff');
                const appEl = document.getElementById('statApprovedStaff');
                const custEl = document.getElementById('statCustomizedStaff');
                if (totalEl) totalEl.textContent = meta.dataset.totalAll || '0';
                if (appEl) appEl.textContent = meta.dataset.approvedCount || '0';
                if (custEl) custEl.textContent = meta.dataset.customizedCount || '0';
            }

            async function pollPermissions(quiet = false, customUrl = null) {
                const form = document.getElementById('permissionsFilterForm');
                const container = document.getElementById('permissionsTableContainer');
                if (!form || !container) return;

                let requestUrl = customUrl;
                if (!requestUrl) {
                    const params = new URLSearchParams(new FormData(form));
                    const searchInput = document.getElementById('permissionsSearchInput');
                    if (searchInput) params.set('search', searchInput.value.trim());
                    requestUrl = ENDPOINT + '?' + params.toString();
                }

                if (!quiet) container.innerHTML = window.renderPermissionsSkeletonRows(3);

                try {
                    const resp = await fetch(requestUrl, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin'
                    });
                    if (!resp.ok) throw new Error('HTTP ' + resp.status);
                    container.innerHTML = await resp.text();
                    syncMetaStats();
                } catch (err) {
                    if (!quiet) {
                        container.innerHTML = '<div class="bg-white rounded-2xl shadow-sm p-10 text-center space-y-3"><div class="text-sm font-bold text-rose-900">Unable to load staff permissions</div><p class="text-xs text-slate-600">The server did not respond. Check your connection and reload the list.</p><button type="button" onclick="window.setPermStatusFilter(document.getElementById(\'filterStatusInput\')?.value || \'all\')" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold">Retry loading permissions</button></div>';
                    }
                }
            }

            window.clearPermissionsSearch = function () {
                const searchInput = document.getElementById('permissionsSearchInput');
                const clearBtn = document.getElementById('clearPermissionsSearchBtn');
                if (searchInput) searchInput.value = '';
                if (clearBtn) {
                    clearBtn.classList.add('hidden');
                    clearBtn.classList.remove('inline-flex');
                }
                pollPermissions(false);
            };

            window.setPermStatusFilter = function (status) {
                const input = document.getElementById('filterStatusInput');
                if (input) input.value = status;
                pollPermissions(false);
            };

            window.applyStatusSelectAndFilter = function (status) {
                const sel = document.getElementById('permStatusSelect');
                if (sel) sel.value = status;
                window.setPermStatusFilter(status);
                document.getElementById('permissionsTableContainer')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            };

            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('permissionsFilterForm');
                if (form) {
                    form.addEventListener('submit', function (e) {
                        e.preventDefault();
                        pollPermissions(false);
                    });
                }

                const searchInput = document.getElementById('permissionsSearchInput');
                const clearBtn = document.getElementById('clearPermissionsSearchBtn');
                if (searchInput) {
                    let timer = null;
                    searchInput.addEventListener('input', function () {
                        const val = searchInput.value.trim();
                        if (clearBtn) {
                            clearBtn.classList.toggle('hidden', val.length === 0);
                            clearBtn.classList.toggle('inline-flex', val.length > 0);
                        }
                        clearTimeout(timer);
                        timer = setTimeout(() => pollPermissions(false), 260);
                    });
                }

                const container = document.getElementById('permissionsTableContainer');
                if (container) {
                    container.addEventListener('click', function (e) {
                        const link = e.target.closest('a[href]');
                        if (!link) return;
                        if (link.closest('nav') || link.closest('.pagination') || link.href.includes('page=')) {
                            e.preventDefault();
                            pollPermissions(false, link.href);
                        }
                    });
                }
            });
        })();
    </script>
</body>
</html>
