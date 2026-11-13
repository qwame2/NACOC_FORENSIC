<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Unit Permissions &amp; Access Control | NACOC Forensic</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    @endif

    <script src="{{ asset('js/ajax-table.js') }}" defer></script>

    <style>
        /*
         * Design Read: Access Control & Capability Matrix Workspace for NACOC Forensic Unit Heads,
         * in a high-precision forensic command ledger style, dial ENERGY 2 / RHYTHM 2 / MOTION 1.
         * Palette (R-29): Core 1 Deep Slate (#0F172A/#1E293B), Core 2 Alabaster Paper (#FFFFFF/#F8FAFC/#F1F5F9),
         * Accent NACOC Forensic Emerald (#047857/#065F46/#ECFDF5) reserved for active/granted states.
         */
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #F1F5F9;
            color: #0F172A;
        }
        .font-mono-code {
            font-family: 'JetBrains Mono', monospace;
        }
        a[href], button:not(:disabled), select:not(:disabled), [role="switch"]:not(:disabled) {
            cursor: pointer;
        }
        button:disabled, select:disabled, [role="switch"]:disabled {
            cursor: not-allowed;
        }
        .filter-tab-btn {
            min-height: 44px;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            background: transparent;
            transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .filter-tab-btn:hover {
            background: #FFFFFF;
            color: #0F172A;
            border-color: #CBD5E1;
        }
        .filter-tab-btn:active {
            transform: translateY(1px);
        }
        .filter-tab-btn.active-tab {
            background: #047857;
            color: #FFFFFF;
            border-color: #047857;
        }
        .surface-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.04);
        }
        .cap-tile {
            transition: background-color 0.15s ease, border-color 0.15s ease;
        }
        .cap-tile:not(:disabled):active {
            transform: scale(0.995);
        }
        a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible {
            outline: 2px solid #047857;
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
<body class="min-h-screen flex flex-col bg-slate-100 text-slate-900 antialiased selection:bg-emerald-700 selection:text-white">

    <div class="flex min-h-screen lg:h-screen overflow-hidden">

        @if(request()->is('Forensic_Head*'))
            @include('Forensic_Head.partials.sidebar')
        @else
            @include('Crime_Scene_Support.partials.sidebar')
        @endif

        <div class="flex-1 flex flex-col min-w-0 bg-slate-100/80 overflow-hidden">

            <!-- TOP COMMAND HEADER -->
            <header class="min-h-18 bg-white border-b border-slate-200 px-4 sm:px-6 py-3 flex flex-wrap items-center justify-between shrink-0 z-10 gap-4">
                <div class="flex items-center gap-3.5 shrink-0">
                    <div class="w-11 h-11 rounded-xl bg-slate-900 text-emerald-400 flex items-center justify-center shrink-0 border border-slate-800" aria-hidden="true">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight">
                                Unit Staff Permissions &amp; Access Control
                            </h1>
                        </div>
                        <p class="text-xs text-slate-600 font-medium mt-0.5">
                            Configure operational forensic module permissions for staff accounts in your unit
                        </p>
                    </div>
                </div>

                <!-- UNIT HEAD IDENTITY -->
                <div class="flex items-center gap-3 shrink-0 pl-2 border-l border-slate-200">
                    <div class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center font-bold text-xs uppercase shrink-0" aria-hidden="true">
                        {{ substr(auth()->user()->fullname ?? 'H', 0, 1) }}
                    </div>
                    <div class="hidden sm:block text-left">
                        <div class="text-xs font-bold text-slate-900 leading-tight">{{ auth()->user()->fullname ?? 'Unit Head' }}</div>
                        <div class="text-[11px] text-emerald-800 font-mono-code font-semibold mt-0.5">
                            {{ (auth()->user()->unit ?? 'Operational Unit') . ' Head' }}
                        </div>
                    </div>
                </div>
            </header>

            <!-- SCROLLABLE MAIN WORKSPACE -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-5">

                @include('partials.flash')

                <!-- LIVE AJAX NOTIFICATION TOAST -->
                <div id="permissionsFeedbackBanner" class="hidden rounded-xl border px-4 py-3 text-xs font-semibold flex items-center justify-between gap-3" role="status" aria-live="polite">
                    <div class="flex items-center gap-2.5">
                        <span id="permissionsFeedbackIcon" class="w-5 h-5 rounded-md flex items-center justify-center shrink-0" aria-hidden="true">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                        <span id="permissionsFeedbackMessage"></span>
                    </div>
                    <button type="button" onclick="document.getElementById('permissionsFeedbackBanner').classList.add('hidden')" class="min-h-[36px] px-3 py-1 rounded-lg border border-current/20 hover:bg-black/5 text-[11px] font-bold transition-colors">
                        Dismiss
                    </button>
                </div>

                @php
                    $defaultUnitFilter = $defaultUnitFilter ?? ($scopedUnitSlug ?? 'all');
                    $isUnitScoped = !empty($isUnitScoped);
                @endphp

                <!-- COMMAND CONTROL BAR: STAFF SEARCH, ACCOUNT STATUS & CAPABILITY LEGEND -->
                <form id="permissionsFilterForm" method="GET" action="{{ $currentEndpoint }}" data-ajax-table="#permissionsTableContainer" class="surface-card rounded-2xl p-4 sm:p-5 flex flex-col xl:flex-row xl:items-center justify-between gap-4">
                    <input type="hidden" name="unit" id="filterUnitInput" value="{{ $unitFilter }}">
                    <input type="hidden" name="status" id="filterStatusInput" value="{{ $statusFilter }}">

                    <!-- Left: Staff Member Search Input -->
                    <div class="flex items-center gap-3 flex-1 max-w-xl">
                        <label for="permissionsSearchInput" class="text-[11px] font-bold font-mono-code text-slate-600 uppercase tracking-wider shrink-0">
                            Search Staff:
                        </label>
                        <div class="relative w-full">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-500" aria-hidden="true">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </span>
                            <input
                                type="text"
                                name="search"
                                id="permissionsSearchInput"
                                value="{{ $search ?? '' }}"
                                placeholder="Search staff members by name, service ID, or @username..."
                                data-table-filter="#permissionsTableContainer"
                                class="w-full min-h-[44px] pl-10 pr-20 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 font-mono-code placeholder:text-slate-500 focus:outline-none focus:bg-white focus:border-emerald-700 transition-colors"
                            >
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2.5 gap-1">
                                <button
                                    type="button"
                                    id="clearPermissionsSearchBtn"
                                    onclick="clearPermissionsSearch()"
                                    aria-label="Clear search filter"
                                    class="{{ empty($search) ? 'hidden' : 'inline-flex' }} items-center justify-center w-7 h-7 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-200/70 transition-colors"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                                <kbd class="px-1.5 py-0.5 text-[10px] font-mono-code font-bold text-emerald-800 bg-emerald-50 rounded border border-emerald-200 pointer-events-none">Live</kbd>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Legend + Status Filter -->
                    <div class="flex flex-wrap items-center justify-between xl:justify-end gap-4 pt-3 xl:pt-0 border-t xl:border-t-0 border-slate-200/80">
                        <!-- Visual Switch Legend -->
                        <div class="flex items-center gap-3 text-xs text-slate-700 font-medium bg-slate-50 px-3 py-2 rounded-xl border border-slate-200/90">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-4 h-4 rounded bg-emerald-700 text-white inline-flex items-center justify-center" aria-hidden="true">
                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </span>
                                <span class="font-semibold text-slate-800">Granted</span>
                            </span>
                            <span class="text-slate-300" aria-hidden="true">|</span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-4 h-4 rounded bg-slate-200 text-slate-700 inline-flex items-center justify-center" aria-hidden="true">
                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M20 12H4"/>
                                    </svg>
                                </span>
                                <span class="font-semibold text-slate-700">Restricted</span>
                            </span>
                        </div>

                        <div class="flex items-center gap-2.5">
                            <label class="text-[11px] font-bold font-mono-code text-slate-600 uppercase tracking-wider" for="permStatusSelect">Clearance:</label>
                            <select id="permStatusSelect" onchange="setPermStatusFilter(this.value)" class="min-h-[44px] px-3.5 py-2 rounded-xl border border-slate-300 bg-white hover:border-slate-400 text-xs font-semibold text-slate-900 transition-colors">
                                <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Accounts ({{ $totalStaffCount ?? $officers->total() }})</option>
                                <option value="approved" {{ $statusFilter === 'approved' ? 'selected' : '' }}>Approved Only ({{ $approvedCount ?? 0 }})</option>
                                <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending Approval ({{ max(0, ($totalStaffCount ?? 0) - ($approvedCount ?? 0)) }})</option>
                            </select>
                        </div>
                    </div>
                </form>

                <!-- PERMISSIONS MATRIX TABLE CONTAINER -->
                <section aria-label="Staff Operational Permissions Matrix" class="surface-card rounded-2xl overflow-hidden">
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
                if (banner) {
                    banner.classList.add('hidden');
                }
                if (typeof window.showFlashToast === 'function') {
                    window.showFlashToast(
                        message,
                        isError ? 'error' : 'success',
                        isError ? 'Action Required' : 'Permissions Updated'
                    );
                }
            }

            function syncToggleVisual(toggleBtn, isEnabled) {
                if (!toggleBtn) return;
                toggleBtn.setAttribute('aria-checked', isEnabled ? 'true' : 'false');
                toggleBtn.classList.toggle('border-emerald-300', isEnabled);
                toggleBtn.classList.toggle('bg-emerald-50/50', isEnabled);
                toggleBtn.classList.toggle('hover:bg-emerald-50/90', isEnabled);
                toggleBtn.classList.toggle('hover:border-emerald-400', isEnabled);
                toggleBtn.classList.toggle('border-slate-200', !isEnabled);
                toggleBtn.classList.toggle('bg-slate-50/40', !isEnabled);
                toggleBtn.classList.toggle('hover:bg-white', !isEnabled);
                toggleBtn.classList.toggle('hover:border-slate-300', !isEnabled);

                const cb = toggleBtn.querySelector('input[type="checkbox"]');
                if (cb) cb.checked = isEnabled;

                const iconBox = toggleBtn.querySelector('[data-cap-icon]');
                if (iconBox) {
                    iconBox.classList.toggle('bg-emerald-700', isEnabled);
                    iconBox.classList.toggle('text-white', isEnabled);
                    iconBox.classList.toggle('border-emerald-700', isEnabled);
                    iconBox.classList.toggle('bg-white', !isEnabled);
                    iconBox.classList.toggle('text-slate-600', !isEnabled);
                    iconBox.classList.toggle('border-slate-200', !isEnabled);
                }

                const catBadge = toggleBtn.querySelector('[data-cap-category]');
                if (catBadge) {
                    catBadge.classList.toggle('text-emerald-800', isEnabled);
                    catBadge.classList.toggle('text-slate-600', !isEnabled);
                }

                const track = toggleBtn.querySelector('[data-switch-track]');
                if (track) {
                    track.classList.toggle('bg-emerald-700', isEnabled);
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

                window.markOfficerPermissionsDirty(officerId);
                window.submitOfficerPermissions(null, officerId);
            };

            window.markOfficerPermissionsDirty = function (officerId) {
                const btn = document.getElementById('saveBtn-' + officerId);
                if (btn) {
                    btn.classList.remove('bg-slate-900');
                    btn.classList.add('bg-emerald-700');
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
                        syncOfficerSummaryBar(officerId, data.officer.enabled, data.officer.total);
                    }

                    if (btn) {
                        btn.classList.remove('bg-emerald-700');
                        btn.classList.add('bg-slate-900');
                    }
                    showFeedback(data.message || 'Permissions saved.', false);
                    pollPermissions(true);
                } catch (err) {
                    showFeedback('Network error saving permissions. Please verify your connection and retry.', true);
                } finally {
                    if (btn) btn.disabled = false;
                }
                return false;
            };

            window.applyPermissionPreset = function (officerId, preset) {
                return window.submitOfficerPermissions(null, officerId, preset);
            };

            window.renderPermissionsSkeletonRows = function (count = 4) {
                let html = '<div class="p-6 space-y-4" role="status" aria-live="polite"><div class="text-xs font-mono-code font-semibold text-slate-600">Loading unit staff permission profiles...</div>';
                for (let i = 0; i < count; i++) {
                    html += '<div class="h-24 bg-slate-100 rounded-xl w-full border border-slate-200/70"></div>';
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
                        container.innerHTML = '<div class="p-10 text-center space-y-3"><div class="text-sm font-bold text-rose-900">Unable to load staff permissions</div><p class="text-xs text-slate-600">The server did not respond. Check your connection and reload the table.</p><button type="button" onclick="window.setPermStatusFilter(document.getElementById(\'filterStatusInput\')?.value || \'all\')" class="min-h-[44px] px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold">Retry loading permissions</button></div>';
                    }
                }
            }

            window.clearPermissionsSearch = function () {
                const searchInput = document.getElementById('permissionsSearchInput');
                const hidden = document.getElementById('filterSearchHidden');
                const clearBtn = document.getElementById('clearPermissionsSearchBtn');
                if (searchInput) searchInput.value = '';
                if (hidden) hidden.value = '';
                if (clearBtn) {
                    clearBtn.classList.add('hidden');
                    clearBtn.classList.remove('inline-flex');
                }
                pollPermissions(false);
            };

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
                const clearBtn = document.getElementById('clearPermissionsSearchBtn');
                if (searchInput) {
                    let timer = null;
                    searchInput.addEventListener('input', function () {
                        const val = searchInput.value.trim();
                        const hidden = document.getElementById('filterSearchHidden');
                        if (hidden) hidden.value = val;
                        if (clearBtn) {
                            clearBtn.classList.toggle('hidden', val.length === 0);
                            clearBtn.classList.toggle('inline-flex', val.length > 0);
                        }
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
