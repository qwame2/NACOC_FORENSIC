<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $context['unit_label'] }} Notifications | NACOC Forensic</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    @endif

    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #FFFFFF;
            color: #0F172A;
        }
        .font-mono-code {
            font-family: 'JetBrains Mono', monospace;
        }
        .filter-pill-btn {
            padding: 6px 14px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            background: transparent;
            transition: all 0.2s ease;
            cursor: pointer;
            border: 1px solid transparent;
        }
        .filter-pill-btn:hover {
            background: #F1F5F9;
            color: #0F172A;
        }
        .filter-pill-btn.active-tab {
            background: #059669;
            color: #FFFFFF;
            box-shadow: 0 4px 10px rgba(5, 150, 105, 0.25);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-white text-slate-900 antialiased selection:bg-emerald-600 selection:text-white">

    <div class="flex h-screen overflow-hidden">

        @if(!empty($context['is_forensic_head']))
            @include('Forensic_Head.partials.sidebar')
        @else
            @include('Crime_Scene_Support.partials.sidebar')
        @endif

        <div class="flex-1 flex flex-col min-w-0 bg-white overflow-hidden">

            <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 z-10 shadow-xs gap-4">
                <div class="flex items-center space-x-3.5 shrink-0">
                    <div class="w-9 h-9 rounded-xl bg-emerald-600/10 border border-emerald-600/20 flex items-center justify-center text-emerald-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-sm font-extrabold text-slate-900 tracking-wide font-mono-code uppercase">
                            {{ $context['unit_label'] }} Notifications
                        </h1>
                        <p class="text-[11px] text-slate-500 font-medium">
                            Cross-unit exhibit alerts, case handovers, pending sign-offs, and staff activations
                        </p>
                    </div>
                </div>

                <div class="flex items-center flex-1 max-w-md mx-4">
                    <div class="relative w-full">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input type="text"
                               id="notificationsSearchInput"
                               value="{{ $search }}"
                               placeholder="Search case #, property ID, commodity, or officer..."
                               class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 focus:border-emerald-600 transition-all">
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <span class="hidden md:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-[10px] font-bold font-mono-code text-emerald-700">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>15s Live Sync</span>
                    </span>
                    <button type="button"
                            id="markAllNotificationsReadBtn"
                            onclick="markAllNotificationsRead()"
                            class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition-colors cursor-pointer">
                        Mark All Read
                    </button>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-6 space-y-6 bg-slate-50/50">

                <!-- KPI SUMMARY STRIP -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs">
                        <div class="text-[10px] font-bold font-mono-code text-slate-500 uppercase tracking-wider">Total Alerts</div>
                        <div id="statTotalCount" class="text-2xl font-extrabold text-slate-900 mt-1">{{ $stats['total'] }}</div>
                    </div>
                    <div class="bg-white rounded-2xl border border-emerald-200 p-4 shadow-2xs">
                        <div class="text-[10px] font-bold font-mono-code text-emerald-700 uppercase tracking-wider">Unread</div>
                        <div id="statUnreadCount" class="text-2xl font-extrabold text-emerald-700 mt-1">{{ $stats['unread'] }}</div>
                    </div>
                    <div class="bg-white rounded-2xl border border-amber-200 p-4 shadow-2xs">
                        <div class="text-[10px] font-bold font-mono-code text-amber-700 uppercase tracking-wider">Cross-Unit</div>
                        <div id="statCrossUnitCount" class="text-2xl font-extrabold text-amber-700 mt-1">{{ $stats['cross_unit'] }}</div>
                    </div>
                    <div class="bg-white rounded-2xl border border-blue-200 p-4 shadow-2xs">
                        <div class="text-[10px] font-bold font-mono-code text-blue-700 uppercase tracking-wider">Handovers</div>
                        <div id="statHandoverCount" class="text-2xl font-extrabold text-blue-700 mt-1">{{ $stats['handover'] }}</div>
                    </div>
                    <div class="bg-white rounded-2xl border border-purple-200 p-4 shadow-2xs">
                        <div class="text-[10px] font-bold font-mono-code text-purple-700 uppercase tracking-wider">Sign-Offs</div>
                        <div id="statCaseApprovalCount" class="text-2xl font-extrabold text-purple-700 mt-1">{{ $stats['case_approval'] }}</div>
                    </div>
                    <div class="bg-white rounded-2xl border border-rose-200 p-4 shadow-2xs">
                        <div class="text-[10px] font-bold font-mono-code text-rose-700 uppercase tracking-wider">Staff Activations</div>
                        <div id="statStaffApprovalCount" class="text-2xl font-extrabold text-rose-700 mt-1">{{ $stats['staff_approval'] }}</div>
                    </div>
                </div>

                <!-- FILTER BAR -->
                <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-1.5" id="categoryFilterTabs">
                        <button type="button" data-category="all" class="filter-pill-btn {{ $category === 'all' ? 'active-tab' : '' }}">All</button>
                        <button type="button" data-category="cross_unit" class="filter-pill-btn {{ $category === 'cross_unit' ? 'active-tab' : '' }}">Cross-Unit Intake</button>
                        <button type="button" data-category="handover" class="filter-pill-btn {{ $category === 'handover' ? 'active-tab' : '' }}">Case Handovers</button>
                        <button type="button" data-category="case_approval" class="filter-pill-btn {{ $category === 'case_approval' ? 'active-tab' : '' }}">Pending Sign-Offs</button>
                        <button type="button" data-category="staff_approval" class="filter-pill-btn {{ $category === 'staff_approval' ? 'active-tab' : '' }}">Staff Activations</button>
                    </div>

                    <div class="flex items-center gap-2">
                        <label for="statusFilterSelect" class="text-[11px] font-bold text-slate-500 uppercase font-mono-code">Status:</label>
                        <select id="statusFilterSelect" class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:outline-none focus:border-emerald-600 cursor-pointer">
                            <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All</option>
                            <option value="unread" {{ $statusFilter === 'unread' ? 'selected' : '' }}>Unread Only</option>
                            <option value="read" {{ $statusFilter === 'read' ? 'selected' : '' }}>Read Only</option>
                        </select>
                    </div>
                </div>

                <!-- SKELETON LOADER -->
                <div id="notificationsSkeleton" class="hidden space-y-3" aria-hidden="true">
                    @for($i = 0; $i < 4; $i++)
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 animate-pulse space-y-2.5">
                            <div class="flex items-center gap-2">
                                <div class="h-4 w-24 bg-slate-200 rounded"></div>
                                <div class="h-4 w-28 bg-slate-100 rounded"></div>
                            </div>
                            <div class="h-4 w-2/3 bg-slate-200 rounded"></div>
                            <div class="h-3 w-1/2 bg-slate-100 rounded"></div>
                        </div>
                    @endfor
                </div>

                <!-- FEED CONTAINER -->
                <div id="notificationsFeedContainer">
                    @include('partials.notifications_feed')
                </div>

            </main>
        </div>
    </div>

    <script>
        (function() {
            const ENDPOINT = '{{ url($currentEndpoint) }}';
            const MARK_READ_URL = '{{ route('notifications.mark_read') }}';
            const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const feedContainer = document.getElementById('notificationsFeedContainer');
            const skeletonEl = document.getElementById('notificationsSkeleton');
            const searchInput = document.getElementById('notificationsSearchInput');
            const statusSelect = document.getElementById('statusFilterSelect');
            const categoryTabs = document.getElementById('categoryFilterTabs');

            let activeCategory = @json($category);
            let activeStatus = @json($statusFilter);
            let activeSearch = @json($search);
            let debounceTimer = null;

            function syncStatsAndBadge(stats) {
                if (!stats) return;
                const map = {
                    statTotalCount: stats.total,
                    statUnreadCount: stats.unread,
                    statCrossUnitCount: stats.cross_unit,
                    statHandoverCount: stats.handover,
                    statCaseApprovalCount: stats.case_approval,
                    statStaffApprovalCount: stats.staff_approval,
                };
                Object.keys(map).forEach(function(id) {
                    const el = document.getElementById(id);
                    if (el && map[id] !== undefined) {
                        el.textContent = String(map[id]);
                    }
                });
                const sidebarBadge = document.getElementById('sidebarNotificationsBadge');
                if (sidebarBadge && stats.unread !== undefined) {
                    sidebarBadge.textContent = String(stats.unread);
                }
            }

            async function loadNotifications(showSkeleton) {
                if (showSkeleton && skeletonEl && feedContainer) {
                    skeletonEl.classList.remove('hidden');
                    feedContainer.classList.add('hidden');
                }

                const params = new URLSearchParams();
                if (activeCategory && activeCategory !== 'all') params.set('category', activeCategory);
                if (activeStatus && activeStatus !== 'all') params.set('status', activeStatus);
                if (activeSearch && activeSearch.trim() !== '') params.set('search', activeSearch.trim());
                params.set('poll', '1');

                try {
                    const resp = await fetch(ENDPOINT + '?' + params.toString(), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!resp.ok) return;
                    const data = await resp.json();
                    if (data && data.success) {
                        if (feedContainer && typeof data.html === 'string') {
                            feedContainer.innerHTML = data.html;
                        }
                        syncStatsAndBadge(data.stats);
                    }
                } catch (e) {
                } finally {
                    if (skeletonEl && feedContainer) {
                        skeletonEl.classList.add('hidden');
                        feedContainer.classList.remove('hidden');
                    }
                }
            }

            if (categoryTabs) {
                categoryTabs.querySelectorAll('button[data-category]').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        activeCategory = btn.getAttribute('data-category') || 'all';
                        categoryTabs.querySelectorAll('button[data-category]').forEach(function(other) {
                            other.classList.remove('active-tab');
                        });
                        btn.classList.add('active-tab');
                        loadNotifications(true);
                    });
                });
            }

            if (statusSelect) {
                statusSelect.addEventListener('change', function() {
                    activeStatus = statusSelect.value || 'all';
                    loadNotifications(true);
                });
            }

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    activeSearch = searchInput.value || '';
                    if (debounceTimer) clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function() {
                        loadNotifications(true);
                    }, 300);
                });
            }

            window.markSingleNotificationRead = async function(id, btn) {
                if (!id) return;
                if (btn) btn.disabled = true;
                try {
                    const resp = await fetch(MARK_READ_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN,
                        },
                        body: JSON.stringify({ id: id, from: @json($context['unit_label']) })
                    });
                    if (resp.ok) {
                        await loadNotifications(false);
                    }
                } catch (e) {}
            };

            window.markAllNotificationsRead = async function() {
                try {
                    const resp = await fetch(MARK_READ_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN,
                        },
                        body: JSON.stringify({ all: true, from: @json($context['unit_label']) })
                    });
                    if (resp.ok) {
                        await loadNotifications(false);
                    }
                } catch (e) {}
            };

            window.acknowledgeHandoverNotification = async function(handoverId, btn) {
                if (!handoverId) return;
                if (btn) btn.disabled = true;
                try {
                    const resp = await fetch('/notifications/handovers/' + encodeURIComponent(handoverId) + '/acknowledge', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN,
                        },
                        body: JSON.stringify({})
                    });
                    if (resp.ok) {
                        await loadNotifications(false);
                    }
                } catch (e) {}
            };

            setInterval(function() {
                loadNotifications(false);
            }, 15000);
        })();
    </script>
</body>
</html>

