<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Unit Permissions &amp; Access Control | NACOC Forensic</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    @endif

    <script src="{{ asset('js/ajax-table.js') }}"></script>

    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #FFFFFF;
            color: #0F172A;
        }
        .font-mono-code {
            font-family: 'JetBrains Mono', monospace;
        }
        .filter-tab-btn {
            min-height: 40px;
            padding: 6px 14px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 700;
            color: #334155;
            background: transparent;
            transition: all 0.2s ease;
            cursor: pointer;
            border: 1px solid transparent;
        }
        .filter-tab-btn:hover {
            background: #F1F5F9;
            color: #0F172A;
        }
        .filter-tab-btn.active-tab {
            background: #059669;
            color: #FFFFFF;
            box-shadow: 0 4px 10px rgba(5, 150, 105, 0.22);
        }
        a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible {
            outline: 2px solid #059669;
            outline-offset: 2px;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-white text-slate-900 antialiased selection:bg-emerald-600 selection:text-white">

    <div class="flex min-h-screen lg:h-screen overflow-hidden">

        @if(request()->is('Forensic_Head*'))
            @include('Forensic_Head.partials.sidebar')
        @else
            @include('Crime_Scene_Support.partials.sidebar')
        @endif

        <div class="flex-1 flex flex-col min-w-0 bg-slate-50/40 overflow-hidden">

            <!-- TOP NAVBAR -->
            <header class="min-h-16 bg-white border-b border-slate-200 px-6 py-2 flex flex-wrap items-center justify-between shrink-0 z-10 shadow-xs gap-4">
                <div class="flex items-center space-x-3.5 shrink-0">
                    <div class="w-10 h-10 rounded-xl bg-emerald-600/10 border border-emerald-600/20 flex items-center justify-center text-emerald-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-sm font-extrabold text-slate-900 tracking-wide font-mono-code uppercase">
                            Unit Staff Permissions &amp; Access Control
                        </h1>
                        <p class="text-[11px] text-slate-600 font-medium">
                            Configure operational forensic module permissions for staff accounts in your unit
                        </p>
                    </div>
                </div>

                <!-- DEBOUNCED SEARCH INPUT -->
                <div class="flex items-center flex-1 max-w-md mx-2">
                    <div class="relative w-full">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input
                            type="text"
                            name="search"
                            id="permissionsSearchInput"
                            value="{{ $search ?? '' }}"
                            placeholder="Search staff by name, service ID, username, unit..."
                            aria-label="Search staff permissions"
                            data-table-filter="#permissionsTableContainer"
                            class="w-full min-h-[42px] pl-10 pr-16 py-2 bg-slate-100/80 border border-slate-200 rounded-xl text-xs text-slate-900 font-mono-code focus:outline-none focus:bg-white focus:border-emerald-600 transition-all"
                        >
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <kbd class="px-1.5 py-0.5 text-[10px] font-mono-code font-semibold text-emerald-800 bg-emerald-50 rounded border border-emerald-200">Live</kbd>
                        </span>
                    </div>
                </div>

                <!-- UNIT HEAD IDENTITY -->
                <div class="flex items-center gap-2.5 shrink-0">
                    <div class="w-9 h-9 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs uppercase">
                        {{ substr(auth()->user()->fullname ?? 'H', 0, 1) }}
                    </div>
                    <div class="hidden sm:block text-left">
                        <div class="text-xs font-bold text-slate-900 leading-tight">{{ auth()->user()->fullname ?? 'Unit Head' }}</div>
                        <div class="text-[10px] text-emerald-700 font-mono-code font-bold">
                            {{ (auth()->user()->unit ?? 'Operational Unit') . ' Head' }}
                        </div>
                    </div>
                </div>
            </header>

            <!-- SCROLLABLE MAIN WORKSPACE -->
            <main class="flex-1 overflow-y-auto p-6 space-y-6">

                @include('partials.flash')

                <!-- LIVE AJAX NOTIFICATION TOAST -->
                <div id="permissionsFeedbackBanner" class="hidden rounded-xl border px-4 py-3 text-xs font-semibold flex items-center justify-between gap-3" role="status">
                    <span id="permissionsFeedbackMessage"></span>
                    <button type="button" onclick="document.getElementById('permissionsFeedbackBanner').classList.add('hidden')" class="px-2 py-1 rounded text-[11px] font-bold">Dismiss</button>
                </div>

                <!-- 1. KPI SUMMARY CARDS -->
                @php
                    $defaultUnitFilter = $defaultUnitFilter ?? ($scopedUnitSlug ?? 'all');
                    $isUnitScoped = !empty($isUnitScoped);
                @endphp
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <button type="button" onclick="applyPermQuickFilter('{{ $defaultUnitFilter }}', 'all')" class="bg-white rounded-2xl border border-slate-200 border-l-4 border-l-slate-900 p-4 flex items-center justify-between text-left hover:shadow-md transition-shadow">
                        <div>
                            <div class="text-[10px] font-bold font-mono-code text-slate-600 uppercase tracking-wider">Operational Staff Accounts</div>
                            <div id="statTotalStaff" class="text-2xl font-extrabold text-slate-900 font-mono-code mt-1">{{ $totalStaffCount }}</div>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-800 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                    </button>

                    <button type="button" onclick="applyPermQuickFilter('{{ $defaultUnitFilter }}', 'approved')" class="bg-white rounded-2xl border border-slate-200 border-l-4 border-l-emerald-600 p-4 flex items-center justify-between text-left hover:shadow-md transition-shadow">
                        <div>
                            <div class="text-[10px] font-bold font-mono-code text-emerald-700 uppercase tracking-wider">Approved &amp; Active Staff</div>
                            <div id="statApprovedStaff" class="text-2xl font-extrabold text-emerald-700 font-mono-code mt-1">{{ $approvedCount }}</div>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                    </button>

                    <button type="button" onclick="applyPermQuickFilter('{{ $defaultUnitFilter }}', 'customized')" class="bg-white rounded-2xl border border-slate-200 border-l-4 border-l-amber-600 p-4 flex items-center justify-between text-left hover:shadow-md transition-shadow">
                        <div>
                            <div class="text-[10px] font-bold font-mono-code text-amber-800 uppercase tracking-wider">Customized Policies</div>
                            <div id="statCustomizedStaff" class="text-2xl font-extrabold text-amber-800 font-mono-code mt-1">{{ $customizedCount }}</div>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-800 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                        </div>
                    </button>
                </div>

                <!-- 2. UNIT & POLICY FILTER CONTROLS -->
                <form id="permissionsFilterForm" method="GET" action="{{ $currentEndpoint }}" data-ajax-table="#permissionsTableContainer" class="bg-white rounded-2xl border border-slate-200 p-4 flex flex-col lg:flex-row lg:items-center justify-between gap-4 shadow-2xs">
                    <input type="hidden" name="unit" id="filterUnitInput" value="{{ $unitFilter }}">
                    <input type="hidden" name="status" id="filterStatusInput" value="{{ $statusFilter }}">
                    <input type="hidden" name="search" id="filterSearchHidden" value="{{ $search }}">

                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-[10px] font-bold font-mono-code text-slate-500 uppercase tracking-wider mr-2">Unit Scope:</span>
                        @if($isUnitScoped)
                            <button type="button" data-unit-tab="{{ $defaultUnitFilter }}" onclick="setPermUnitFilter('{{ $defaultUnitFilter }}')" class="filter-tab-btn active-tab">{{ $viewerUnit?->code() ?? strtoupper($defaultUnitFilter) }}</button>
                        @else
                            <button type="button" data-unit-tab="all" onclick="setPermUnitFilter('all')" class="filter-tab-btn {{ $unitFilter === 'all' ? 'active-tab' : '' }}">All Units</button>
                            <button type="button" data-unit-tab="css" onclick="setPermUnitFilter('css')" class="filter-tab-btn {{ $unitFilter === 'css' ? 'active-tab' : '' }}">CSSU</button>
                            <button type="button" data-unit-tab="em" onclick="setPermUnitFilter('em')" class="filter-tab-btn {{ $unitFilter === 'em' ? 'active-tab' : '' }}">EMU</button>
                            <button type="button" data-unit-tab="lab" onclick="setPermUnitFilter('lab')" class="filter-tab-btn {{ $unitFilter === 'lab' ? 'active-tab' : '' }}">LAU</button>
                            <button type="button" data-unit-tab="dpf" onclick="setPermUnitFilter('dpf')" class="filter-tab-btn {{ $unitFilter === 'dpf' ? 'active-tab' : '' }}">DPFU</button>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <label class="text-[10px] font-bold font-mono-code text-slate-500 uppercase tracking-wider" for="permStatusSelect">Status:</label>
                        <select id="permStatusSelect" onchange="setPermStatusFilter(this.value)" class="min-h-[40px] px-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50 text-xs font-semibold text-slate-900">
                            <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Accounts</option>
                            <option value="approved" {{ $statusFilter === 'approved' ? 'selected' : '' }}>Approved Only</option>
                            <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending Approval</option>
                            <option value="customized" {{ $statusFilter === 'customized' ? 'selected' : '' }}>Customized Policies</option>
                        </select>
                    </div>
                </form>

                <!-- 3. PERMISSIONS MATRIX TABLE WITH SKELETON LOADER -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                    <div id="permissionsTableContainer" class="relative">
                        @include('partials.permissions_table')
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script>
        (function () {
            const ENDPOINT = @json($currentEndpoint);
            const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            function showFeedback(message, isError = false) {
                const banner = document.getElementById('permissionsFeedbackBanner');
                const msgEl = document.getElementById('permissionsFeedbackMessage');
                if (!banner || !msgEl) return;
                banner.classList.remove('hidden', 'bg-emerald-50', 'border-emerald-200', 'text-emerald-900', 'bg-rose-50', 'border-rose-200', 'text-rose-900');
                if (isError) {
                    banner.classList.add('bg-rose-50', 'border-rose-200', 'text-rose-900');
                } else {
                    banner.classList.add('bg-emerald-50', 'border-emerald-200', 'text-emerald-900');
                }
                msgEl.textContent = message;
            }

            function syncToggleVisual(toggleBtn, isEnabled) {
                if (!toggleBtn) return;
                toggleBtn.setAttribute('aria-checked', isEnabled ? 'true' : 'false');
                toggleBtn.classList.toggle('border-emerald-300', isEnabled);
                toggleBtn.classList.toggle('bg-emerald-50/60', isEnabled);
                toggleBtn.classList.toggle('border-slate-200', !isEnabled);
                toggleBtn.classList.toggle('bg-white', !isEnabled);

                const cb = toggleBtn.querySelector('input[type="checkbox"]');
                if (cb) cb.checked = isEnabled;

                const track = toggleBtn.querySelector('[data-switch-track]');
                if (track) {
                    track.classList.toggle('bg-emerald-600', isEnabled);
                    track.classList.toggle('bg-slate-300', !isEnabled);
                }

                const thumb = toggleBtn.querySelector('[data-switch-thumb]');
                if (thumb) {
                    thumb.classList.toggle('translate-x-4', isEnabled);
                    thumb.classList.toggle('translate-x-0', !isEnabled);
                }
            }

            window.toggleOfficerCapability = function (toggleBtn, officerId) {
                if (!toggleBtn || toggleBtn.disabled) return;
                const current = toggleBtn.getAttribute('aria-checked') === 'true';
                syncToggleVisual(toggleBtn, !current);

                const allToggles = document.querySelectorAll('[data-perm-toggle="' + officerId + '"]');
                const enabledCount = Array.from(allToggles).filter((el) => el.getAttribute('aria-checked') === 'true').length;
                const summaryEl = document.querySelector('[data-officer-summary="' + officerId + '"]');
                if (summaryEl && allToggles.length > 0) {
                    summaryEl.textContent = enabledCount + '/' + allToggles.length + ' active';
                }
                window.markOfficerPermissionsDirty(officerId);
            };

            window.markOfficerPermissionsDirty = function (officerId) {
                const btn = document.getElementById('saveBtn-' + officerId);
                if (btn) {
                    btn.classList.remove('bg-slate-900');
                    btn.classList.add('bg-emerald-600');
                }
            };

            window.submitOfficerPermissions = async function (event, officerId, preset = '') {
                if (event) event.preventDefault();
                const form = document.getElementById('permForm-' + officerId);
                const btn = document.getElementById('saveBtn-' + officerId);
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

                if (btn) btn.disabled = true;

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
                        const summaryEl = document.querySelector('[data-officer-summary="' + officerId + '"]');
                        if (summaryEl) {
                            summaryEl.textContent = data.officer.enabled + '/' + data.officer.total + ' active';
                        }
                    }

                    if (btn) {
                        btn.classList.remove('bg-emerald-600');
                        btn.classList.add('bg-slate-900');
                    }
                    showFeedback(data.message || 'Permissions saved.', false);
                    pollPermissions(true);
                } catch (err) {
                    showFeedback('Network error saving permissions.', true);
                } finally {
                    if (btn) btn.disabled = false;
                }
                return false;
            };

            window.applyPermissionPreset = function (officerId, preset) {
                return window.submitOfficerPermissions(null, officerId, preset);
            };

            window.renderPermissionsSkeletonRows = function (count = 5) {
                let html = '<div class="p-4 space-y-3 animate-pulse">';
                for (let i = 0; i < count; i++) {
                    html += '<div class="h-14 bg-slate-100 rounded-xl w-full"></div>';
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

            async function pollPermissions(quiet = false) {
                const form = document.getElementById('permissionsFilterForm');
                const container = document.getElementById('permissionsTableContainer');
                if (!form || !container) return;

                const params = new URLSearchParams(new FormData(form));
                const searchInput = document.getElementById('permissionsSearchInput');
                if (searchInput) params.set('search', searchInput.value.trim());

                if (!quiet) container.innerHTML = window.renderPermissionsSkeletonRows(4);

                try {
                    const resp = await fetch(ENDPOINT + '?' + params.toString(), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin'
                    });
                    if (!resp.ok) throw new Error('HTTP ' + resp.status);
                    container.innerHTML = await resp.text();
                    syncMetaStats();
                } catch (err) {
                    if (!quiet) {
                        container.innerHTML = '<div class="p-8 text-center text-xs text-rose-800 font-semibold">Unable to refresh permissions list. Please try again.</div>';
                    }
                }
            }

            window.setPermUnitFilter = function (slug) {
                const input = document.getElementById('filterUnitInput');
                if (input) input.value = slug;
                document.querySelectorAll('[data-unit-tab]').forEach((btn) => {
                    btn.classList.toggle('active-tab', btn.getAttribute('data-unit-tab') === slug);
                });
                pollPermissions(false);
            };

            window.setPermStatusFilter = function (status) {
                const input = document.getElementById('filterStatusInput');
                if (input) input.value = status;
                pollPermissions(false);
            };

            window.applyPermQuickFilter = function (unitSlug, status) {
                const sel = document.getElementById('permStatusSelect');
                if (sel) sel.value = status;
                const statusIn = document.getElementById('filterStatusInput');
                if (statusIn) statusIn.value = status;
                window.setPermUnitFilter(unitSlug);
            };

            document.addEventListener('DOMContentLoaded', function () {
                const searchInput = document.getElementById('permissionsSearchInput');
                if (searchInput) {
                    let timer = null;
                    searchInput.addEventListener('input', function () {
                        const hidden = document.getElementById('filterSearchHidden');
                        if (hidden) hidden.value = searchInput.value.trim();
                        clearTimeout(timer);
                        timer = setTimeout(() => pollPermissions(false), 260);
                    });
                }
                if (typeof initAjaxTable === 'function') {
                    initAjaxTable('#permissionsFilterForm', '#permissionsTableContainer', {
                        onAfterSwap: syncMetaStats
                    });
                }
            });
        })();
    </script>
</body>
</html>

