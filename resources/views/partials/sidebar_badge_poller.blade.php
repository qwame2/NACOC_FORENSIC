{{--
    Partial: partials/sidebar_badge_poller.blade.php
    Description: Real-time, instant background sync for aside bar badge counts (Notifications, Reviews & Approvals, Field Officers) without refreshing the page.
--}}
@php
    $badgeCountsEndpoint = parse_url(url('/api/sidebar/badge-counts'), PHP_URL_PATH) ?: '/api/sidebar/badge-counts';
@endphp
<script>
(function() {
    if (window.__nacocSidebarBadgePollerInitialized) return;
    window.__nacocSidebarBadgePollerInitialized = true;

    const currentUnit = @json($sidebarUnit ?? (auth()->user()?->unit ?? ''));
    const badgeEndpointBase = @json($badgeCountsEndpoint);

    function updateBadgeElement(elId, count) {
        const badges = document.querySelectorAll('#' + elId);
        if (!badges || badges.length === 0) return;
        const num = parseInt(count, 10) || 0;
        badges.forEach(function(badge) {
            badge.textContent = String(num);
            if (num > 0) {
                badge.classList.remove('hidden');
                badge.style.removeProperty('display');
                badge.style.display = 'inline-flex';
            } else {
                badge.classList.add('hidden');
                badge.style.display = 'none';
            }
        });
    }

    let isFetching = false;
    async function refreshSidebarBadges() {
        if (isFetching) return;
        isFetching = true;
        try {
            const url = badgeEndpointBase + (currentUnit ? ('?unit=' + encodeURIComponent(currentUnit)) : '');
            const resp = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (resp.ok) {
                const data = await resp.json();
                if (data && data.success) {
                    if (data.notifications !== undefined) {
                        updateBadgeElement('sidebarNotificationsBadge', data.notifications);
                    }
                    if (data.reviews_approvals !== undefined) {
                        updateBadgeElement('sidebarReviewsApprovalsBadge', data.reviews_approvals);
                    }
                    if (data.field_officers !== undefined) {
                        updateBadgeElement('sidebarFieldOfficersBadge', data.field_officers);
                    }
                    if (data.password_requests !== undefined) {
                        updateBadgeElement('sidebarPasswordRequestsBadge', data.password_requests);
                    }
                    if (data.case_rollback !== undefined || data.staff_rollback !== undefined) {
                        const rbCount = parseInt(data.case_rollback !== undefined ? data.case_rollback : data.staff_rollback, 10) || 0;
                        const rbBadges = document.querySelectorAll('#sidebarStaffRollbackBadge, [data-rollback-badge="sidebar"]');
                        rbBadges.forEach(function(badge) {
                            badge.textContent = String(rbCount);
                            if (rbCount > 0) {
                                badge.className = 'inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-[11px] font-black font-mono-code text-white ' + 'bg-red-600 ' + 'rounded-full ' + 'animate-pulse shadow-xs shrink-0 ml-2';
                                badge.title = rbCount + ' Case(s) awaiting corrections';
                                badge.classList.remove('hidden');
                                badge.style.removeProperty('display');
                                badge.style.display = 'inline-flex';
                            } else {
                                badge.className = 'hidden';
                                badge.title = '';
                                badge.classList.add('hidden');
                                badge.style.display = 'none';
                            }
                        });
                    }
                    if (data.vault_pending !== undefined) {
                        const vBadges = document.querySelectorAll('#sidebarExhibitLogBadge, #sidebarStaffExhibitLogBadge');
                        const vCount = parseInt(data.vault_pending, 10) || 0;
                        vBadges.forEach(function(badge) {
                            badge.textContent = String(vCount);
                            if (vCount > 0) {
                                badge.className = 'ml-auto bg-red-600 text-white border border-red-400 animate-pulse shadow-xs font-mono-code text-[10px] px-2 py-0.5 rounded-full font-bold';
                                badge.title = vCount + ' exhibit(s) pending QR code or classification';
                                badge.classList.remove('hidden');
                                badge.style.display = 'inline-flex';
                            } else {
                                badge.className = 'hidden ml-auto font-mono-code text-[10px] px-2 py-0.5 rounded-full font-bold';
                                badge.title = '';
                                badge.classList.add('hidden');
                                badge.style.display = 'none';
                            }
                        });
                    }
                }
            }
        } catch (e) {
            // Silently ignore network failures
        } finally {
            isFetching = false;
        }
    }

    // Expose globally for manual or trigger-based updates
    window.nacocRefreshSidebarBadges = refreshSidebarBadges;

    function broadcastBadgeSync() {
        try {
            localStorage.setItem('nacoc_badge_sync', Date.now().toString());
        } catch (e) {}
    }

    // 1. Regular 3-second live sync interval (matches asidebar "3s Sync" UI indicator)
    setInterval(refreshSidebarBadges, 3000);

    // 2. Immediate check when window regains focus or visibility
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            refreshSidebarBadges();
        }
    });
    window.addEventListener('focus', refreshSidebarBadges);

    // 3. Cross-tab sync: update the moment an action or request is submitted in any tab
    window.addEventListener('storage', function(e) {
        if (e.key === 'nacoc_badge_sync') {
            refreshSidebarBadges();
        }
    });

    // 4. Custom event for local in-page dispatches
    window.addEventListener('nacoc:badge-update', function() {
        broadcastBadgeSync();
        refreshSidebarBadges();
    });

    // 5. Intercept all mutating fetch requests (POST/PUT/DELETE) so badges refresh immediately when a request is sent
    if (typeof window.fetch === 'function') {
        const originalFetch = window.fetch.bind(window);
        window.fetch = async function(...args) {
            const response = await originalFetch(...args);
            try {
                const method = (args[1] && args[1].method) ? String(args[1].method).toUpperCase() : 'GET';
                const url = typeof args[0] === 'string' ? args[0] : (args[0] && args[0].url ? args[0].url : '');
                if (method !== 'GET' && !url.includes('/api/sidebar/badge-counts')) {
                    broadcastBadgeSync();
                    setTimeout(refreshSidebarBadges, 300);
                }
            } catch (e) {}
            return response;
        };
    }

    // 6. Intercept XMLHttpRequest for Axios / jQuery AJAX calls
    if (typeof window.XMLHttpRequest === 'function') {
        const originalOpen = XMLHttpRequest.prototype.open;
        const originalSend = XMLHttpRequest.prototype.send;
        XMLHttpRequest.prototype.open = function(method, url, ...rest) {
            this._nacocMethod = method ? String(method).toUpperCase() : 'GET';
            this._nacocUrl = String(url || '');
            return originalOpen.call(this, method, url, ...rest);
        };
        XMLHttpRequest.prototype.send = function(...args) {
            if (this._nacocMethod && this._nacocMethod !== 'GET' && !this._nacocUrl.includes('/api/sidebar/badge-counts')) {
                this.addEventListener('load', function() {
                    broadcastBadgeSync();
                    setTimeout(refreshSidebarBadges, 300);
                });
            }
            return originalSend.apply(this, args);
        };
    }

    // 7. Form submission listener: broadcast sync immediately when any form is submitted
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form && form.method && form.method.toUpperCase() === 'POST') {
            broadcastBadgeSync();
        }
    }, true);
})();
</script>
