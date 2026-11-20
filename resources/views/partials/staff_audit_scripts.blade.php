<script>
    let auditSearchTimer = null;
    let auditPollingInterval = 10000; // 10 seconds
    let auditPollingTimer = null;
    let auditPollingActive = true;
    let isAuditFetching = false;
    let currentAuditPage = 1;
    let activeModalPayload = null;

    /**
     * Skeleton Loading Method
     * Generates a realistic 7-column pulsing shimmer placeholder matching the exact table layout.
     */
    function getAuditSkeletonHtml(rows = 8) {
        let html = '';
        for (let i = 0; i < rows; i++) {
            const descW = (i % 3 === 0) ? 'w-5/6' : ((i % 2 === 0) ? 'w-11/12' : 'w-4/5');
            const subW = (i % 2 === 0) ? 'w-1/2' : 'w-3/5';
            html += `
            <tr class="animate-pulse border-b border-slate-100 bg-white">
                <!-- TIMESTAMP -->
                <td class="py-3 px-4 whitespace-nowrap">
                    <div class="h-3.5 bg-slate-200 rounded w-28 mb-1.5"></div>
                    <div class="h-2.5 bg-slate-100 rounded w-16"></div>
                </td>
                <!-- STAFF OFFICER -->
                <td class="py-3 px-4 whitespace-nowrap">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-lg bg-slate-200 shrink-0"></div>
                        <div class="space-y-1.5 flex-1 min-w-[120px]">
                            <div class="h-3.5 bg-slate-200 rounded w-28"></div>
                            <div class="h-2.5 bg-slate-100 rounded w-20"></div>
                        </div>
                    </div>
                </td>
                <!-- ACTIVITY DESCRIPTION -->
                <td class="py-3 px-4 min-w-[280px]">
                    <div class="space-y-1.5 max-w-lg">
                        <div class="h-3.5 bg-slate-200 rounded ${descW}"></div>
                        <div class="h-2.5 bg-slate-100 rounded ${subW}"></div>
                    </div>
                </td>
                <!-- DETAILS -->
                <td class="py-3 px-4 text-right whitespace-nowrap">
                    <div class="inline-block h-6 bg-slate-200 rounded-lg w-16"></div>
                </td>
            </tr>
            `;
        }
        return html;
    }

    function showAuditSkeleton(count = 8) {
        const tbody = document.getElementById('staffAuditTableBody');
        if (tbody) {
            tbody.innerHTML = getAuditSkeletonHtml(count);
        }
    }

    /**
     * Polling Lifecycle Management
     */
    function startAuditPolling() {
        stopAuditPolling();
        if (!auditPollingActive) return;

        auditPollingTimer = setInterval(() => {
            const modal = document.getElementById('auditInspectModal');
            const modalOpen = modal && !modal.classList.contains('hidden');
            const searchInput = document.getElementById('auditSearchInput');
            const searchFocused = searchInput && document.activeElement === searchInput;

            // Only background poll on page 1, when modal is closed, and tab is visible
            if (currentAuditPage === 1 && !modalOpen && !searchFocused && !document.hidden && !isAuditFetching) {
                refreshAuditTable({ isPolling: true });
            }
        }, auditPollingInterval);
    }

    function stopAuditPolling() {
        if (auditPollingTimer) {
            clearInterval(auditPollingTimer);
            auditPollingTimer = null;
        }
    }

    function toggleAuditPolling() {
        auditPollingActive = !auditPollingActive;
        updatePollingUiState();
        if (auditPollingActive) {
            startAuditPolling();
            refreshAuditTable({ showSkeleton: false });
        } else {
            stopAuditPolling();
        }
    }

    function updatePollingUiState() {
        const pill = document.getElementById('auditPollingPill');
        const dot = document.getElementById('auditPollingDot');
        const label = document.getElementById('auditPollingLabel');
        const toggleBtn = document.getElementById('auditPollingToggleBtn');

        if (auditPollingActive) {
            if (dot) dot.className = 'w-2 h-2 rounded-full bg-emerald-500 animate-pulse';
            if (label) label.textContent = 'Live (10s)';
            if (pill) pill.className = 'hidden md:flex items-center space-x-1.5 px-2.5 py-1 bg-emerald-50 text-emerald-800 rounded-lg border border-emerald-200 text-[11px] font-bold';
            if (toggleBtn) {
                toggleBtn.title = 'Pause Live Polling';
                toggleBtn.innerHTML = `
                    <svg class="w-3.5 h-3.5 text-slate-500 hover:text-slate-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                `;
            }
        } else {
            if (dot) dot.className = 'w-2 h-2 rounded-full bg-amber-500';
            if (label) label.textContent = 'Paused';
            if (pill) pill.className = 'hidden md:flex items-center space-x-1.5 px-2.5 py-1 bg-amber-50 text-amber-800 rounded-lg border border-amber-200 text-[11px] font-bold';
            if (toggleBtn) {
                toggleBtn.title = 'Resume Live Polling';
                toggleBtn.innerHTML = `
                    <svg class="w-3.5 h-3.5 text-emerald-600 hover:text-emerald-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                `;
            }
        }
    }

    function updateStatCard(elementId, value) {
        const el = document.getElementById(elementId);
        if (el && el.textContent !== value) {
            el.textContent = value;
        }
    }

    function updateLastSyncTime() {
        const el = document.getElementById('auditSyncStatus');
        if (el) {
            const now = new Date();
            const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            el.textContent = `Synced: ${timeStr}`;
        }
    }

    /**
     * Table Refresh & AJAX Request
     */
    function refreshAuditTable(options = {}) {
        const {
            page = currentAuditPage,
            showSkeleton = true,
            isPolling = false
        } = options;

        const container = document.getElementById('staffAuditTableContainer');
        if (!container) return;

        if (isAuditFetching) return;
        isAuditFetching = true;

        const icon = document.getElementById('refreshIcon');
        if (icon) icon.classList.add('animate-spin');

        if (showSkeleton && !isPolling) {
            showAuditSkeleton(8);
        }

        const search = document.getElementById('auditSearchInput')?.value || '';
        const action = document.getElementById('auditActionFilter')?.value || 'ALL';
        const range = document.getElementById('auditRangeFilter')?.value || 'all';
        const unit = document.getElementById('auditUnitFilter')?.value || '';
        const perPage = document.getElementById('auditPerPage')?.value || '20';

        currentAuditPage = page;

        const params = new URLSearchParams({
            search: search,
            action: action,
            range: range,
            unit: unit,
            per_page: perPage,
            page: page
        });

        const targetUrl = `${window.location.pathname}?${params.toString()}`;

        if (!isPolling && window.history && window.history.pushState) {
            window.history.pushState({ path: targetUrl, page: page }, '', targetUrl);
        }

        fetch(targetUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(res => {
            // Update metric summary numbers from response headers
            const hTotal = res.headers.get('X-Audit-Total');
            const hStaff = res.headers.get('X-Audit-Staff');
            const hMutations = res.headers.get('X-Audit-Mutations');
            const hToday = res.headers.get('X-Audit-Today');

            if (hTotal !== null) updateStatCard('statTotalLogs', Number(hTotal).toLocaleString());
            if (hStaff !== null) updateStatCard('statUniqueStaff', Number(hStaff).toLocaleString());
            if (hMutations !== null) updateStatCard('statMutations', Number(hMutations).toLocaleString());
            if (hToday !== null) updateStatCard('statToday', Number(hToday).toLocaleString());

            return res.text();
        })
        .then(html => {
            container.innerHTML = html;
            updateLastSyncTime();
        })
        .catch(err => {
            console.error('Failed to reload audit logs:', err);
        })
        .finally(() => {
            isAuditFetching = false;
            if (icon) icon.classList.remove('animate-spin');
        });
    }

    /**
     * Pagination Handler
     */
    function goToAuditPage(page, event) {
        if (event) {
            event.preventDefault();
        }
        const p = parseInt(page, 10) || 1;
        refreshAuditTable({ page: p, showSkeleton: true });
        const container = document.getElementById('staffAuditTableContainer');
        if (container) {
            container.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    // Event delegation on table container for pagination clicks
    document.addEventListener('click', function(e) {
        const pageLink = e.target.closest('.audit-pagination a');
        if (pageLink && pageLink.href) {
            e.preventDefault();
            const url = new URL(pageLink.href);
            const p = url.searchParams.get('page') || 1;
            goToAuditPage(p, e);
        }
    });

    /**
     * Filter inputs listeners
     */
    document.getElementById('auditSearchInput')?.addEventListener('input', function() {
        clearTimeout(auditSearchTimer);
        auditSearchTimer = setTimeout(() => {
            refreshAuditTable({ page: 1, showSkeleton: true });
        }, 350);
    });

    document.getElementById('auditActionFilter')?.addEventListener('change', () => refreshAuditTable({ page: 1, showSkeleton: true }));
    document.getElementById('auditRangeFilter')?.addEventListener('change', () => refreshAuditTable({ page: 1, showSkeleton: true }));
    document.getElementById('auditUnitFilter')?.addEventListener('change', () => refreshAuditTable({ page: 1, showSkeleton: true }));
    document.getElementById('auditPerPage')?.addEventListener('change', () => refreshAuditTable({ page: 1, showSkeleton: true }));

    function resetAuditFilters() {
        const s = document.getElementById('auditSearchInput');
        const a = document.getElementById('auditActionFilter');
        const r = document.getElementById('auditRangeFilter');
        const u = document.getElementById('auditUnitFilter');
        const p = document.getElementById('auditPerPage');

        if (s) s.value = '';
        if (a) a.value = 'ALL';
        if (r) r.value = 'all';
        if (u) u.value = 'ALL';
        if (p) p.value = '20';

        refreshAuditTable({ page: 1, showSkeleton: true });
    }

    /**
     * Audit Inspect Modal
     */
    function openAuditModal(data) {
        const m = document.getElementById('auditInspectModal');
        if (!m) return;

        document.getElementById('modalTimestampHeader').innerText = `${data.timestamp} (${data.relative})`;
        document.getElementById('modalStaffName').innerText = data.staff_name;
        document.getElementById('modalServiceId').innerText = data.service_id;
        document.getElementById('modalUnitRole').innerText = `${data.unit} / ${data.role}`;
        document.getElementById('modalActionMethod').innerText = `${data.method} ${data.action} (${data.status_code})`;
        document.getElementById('modalDescription').innerText = data.description;
        document.getElementById('modalRoute').innerText = data.route;
        document.getElementById('modalIpAddress').innerText = data.ip_address;
        document.getElementById('modalUrl').innerText = data.url;
        document.getElementById('modalUserAgent').innerText = data.user_agent;

        const pContainer = document.getElementById('modalPayloadContainer');
        const pPre = document.getElementById('modalPayloadJson');

        activeModalPayload = data.payload;

        if (data.payload && data.payload.trim() !== '') {
            pPre.innerText = data.payload;
            pContainer.classList.remove('hidden');
        } else {
            pContainer.classList.add('hidden');
        }

        m.classList.remove('hidden');
    }

    function closeAuditModal() {
        document.getElementById('auditInspectModal')?.classList.add('hidden');
    }

    document.getElementById('auditInspectModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeAuditModal();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeAuditModal();
    });

    function copyPayloadJson() {
        if (!activeModalPayload) return;
        navigator.clipboard.writeText(activeModalPayload).then(() => {
            alert('Payload JSON copied to clipboard.');
        });
    }

    /**
     * CSV Export
     */
    function exportAuditCsv() {
        const table = document.querySelector('#staffAuditTableContainer table');
        if (!table) return;

        let csv = [];
        const rows = table.querySelectorAll('tr');

        rows.forEach(row => {
            const cols = row.querySelectorAll('th, td');
            let rowData = [];
            cols.forEach((col, idx) => {
                if (idx < cols.length - 1) { // Skip inspect column
                    let text = col.innerText.replace(/(\r\n|\n|\r)/gm, ' ').replace(/\s+/g, ' ').trim();
                    text = text.replace(/"/g, '""');
                    rowData.push(`"${text}"`);
                }
            });
            if (rowData.length > 0) csv.push(rowData.join(','));
        });

        const blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `Staff_Audit_Logs_${new Date().toISOString().slice(0, 10)}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    /**
     * Print Log Method (Safely pauses background polling during print dialog)
     */
    function printAuditLog() {
        stopAuditPolling();
        window.print();
    }

    window.addEventListener('beforeprint', function() {
        stopAuditPolling();
    });

    window.addEventListener('afterprint', function() {
        if (auditPollingActive) startAuditPolling();
    });

    /**
     * Browser Popstate & Visibility Handling
     */
    window.addEventListener('popstate', function(e) {
        const params = new URLSearchParams(window.location.search);
        const p = parseInt(params.get('page') || '1', 10);
        const s = params.get('search') || '';
        const a = params.get('action') || 'ALL';
        const r = params.get('range') || 'all';
        const u = params.get('unit') || '';
        const pp = params.get('per_page') || '20';

        const sEl = document.getElementById('auditSearchInput');
        const aEl = document.getElementById('auditActionFilter');
        const rEl = document.getElementById('auditRangeFilter');
        const uEl = document.getElementById('auditUnitFilter');
        const ppEl = document.getElementById('auditPerPage');

        if (sEl) sEl.value = s;
        if (aEl) aEl.value = a;
        if (rEl) rEl.value = r;
        if (uEl) uEl.value = u;
        if (ppEl) ppEl.value = pp;

        refreshAuditTable({ page: p, showSkeleton: true, isPolling: false });
    });

    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopAuditPolling();
        } else if (auditPollingActive) {
            startAuditPolling();
        }
    });

    // Start polling and sync indicator when document is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            startAuditPolling();
            updateLastSyncTime();
        });
    } else {
        startAuditPolling();
        updateLastSyncTime();
    }
</script>

