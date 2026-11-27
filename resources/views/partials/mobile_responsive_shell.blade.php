{{-- Universal Mobile & Tablet Responsive Sidebar Drawer & Viewport Safeguards --}}
<style id="nacocMobileResponsiveShellStyles">
    .nacoc-mobile-menu-btn {
        display: none;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 0.75rem;
        background: #f1f5f9;
        color: #1e293b;
        border: 1px solid #cbd5e1;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
        cursor: pointer;
        flex-shrink: 0;
        transition: background-color 0.15s ease, border-color 0.15s ease;
    }
    .nacoc-mobile-menu-btn:hover {
        background: #e2e8f0;
        border-color: #94a3b8;
    }
    .nacoc-mobile-menu-btn--floating {
        position: fixed;
        top: 12px;
        left: 12px;
        z-index: 75;
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.14);
    }
    #nacocMobileSidebarBackdrop {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 80;
        background: rgba(15, 23, 42, 0.45);
        backdrop-filter: blur(2px);
        -webkit-backdrop-filter: blur(2px);
    }
    #nacocMobileSidebarBackdrop.is-open {
        display: block;
    }

    @media (max-width: 1023px) {
        html, body {
            max-width: 100% !important;
            overflow-x: hidden !important;
        }
        body.nacoc-drawer-lock {
            overflow: hidden !important;
        }
        .nacoc-mobile-menu-btn {
            display: inline-flex;
        }
        aside.w-64 {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            bottom: 0 !important;
            height: 100vh !important;
            height: 100dvh !important;
            width: 16.5rem !important;
            min-width: 16.5rem !important;
            max-width: 82vw !important;
            z-index: 90 !important;
            transform: translateX(-108%) !important;
            transition: transform 0.22s cubic-bezier(0.22, 1, 0.36, 1) !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
        }
        aside.w-64.nacoc-mobile-sidebar-open {
            transform: translateX(0) !important;
        }
        header.h-16 {
            height: auto !important;
            min-height: 3.75rem;
            padding-top: 0.5rem !important;
            padding-bottom: 0.5rem !important;
            flex-wrap: wrap;
        }
        .overflow-x-auto {
            max-width: 100%;
            -webkit-overflow-scrolling: touch;
        }
    }

    @media (max-width: 767px) {
        table.nacoc-mobile-cards {
            display: block !important;
            width: 100% !important;
            border-collapse: separate !important;
        }
        table.nacoc-mobile-cards > thead {
            display: none !important;
        }
        table.nacoc-mobile-cards > tbody {
            display: flex !important;
            flex-direction: column !important;
            gap: 0.9rem !important;
            padding: 0.75rem !important;
            background: #f1f5f9 !important;
        }
        table.nacoc-mobile-cards > tbody > tr {
            display: block !important;
            width: 100% !important;
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 1rem !important;
            box-shadow: 0 2px 10px -2px rgba(15, 23, 42, 0.08), 0 1px 3px rgba(15, 23, 42, 0.05) !important;
            padding: 0 0.95rem 0.35rem 0.95rem !important;
            overflow: hidden !important;
        }
        table.nacoc-mobile-cards > tbody > tr > td {
            display: flex !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            justify-content: flex-start !important;
            gap: 0.3rem !important;
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100% !important;
            padding: 0.65rem 0 !important;
            border-bottom: 1px solid #f1f5f9 !important;
            text-align: left !important;
            white-space: normal !important;
            box-sizing: border-box !important;
        }
        table.nacoc-mobile-cards > tbody > tr > td:first-child {
            background: #f8fafc !important;
            margin: 0 -0.95rem 0.15rem -0.95rem !important;
            padding: 0.7rem 0.95rem !important;
            width: calc(100% + 1.9rem) !important;
            max-width: calc(100% + 1.9rem) !important;
            border-bottom: 1px solid #e2e8f0 !important;
        }
        table.nacoc-mobile-cards > tbody > tr > td:last-child {
            border-bottom: 0 !important;
            padding-top: 0.75rem !important;
            padding-bottom: 0.5rem !important;
        }
        table.nacoc-mobile-cards > tbody > tr > td::before {
            content: attr(data-label);
            display: block !important;
            font-family: 'JetBrains Mono', ui-monospace, monospace;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            text-align: left;
            line-height: 1.2;
        }
        table.nacoc-mobile-cards > tbody > tr > td[data-label=""]::before {
            display: none !important;
        }
        table.nacoc-mobile-cards > tbody > tr > td:empty {
            display: none !important;
        }
        table.nacoc-mobile-cards > tbody > tr > td > * {
            max-width: 100% !important;
            min-width: 0 !important;
            text-align: left !important;
        }
        table.nacoc-mobile-cards > tbody > tr > td > div {
            width: 100%;
        }
        table.nacoc-mobile-cards > tbody > tr > td .inline-flex,
        table.nacoc-mobile-cards > tbody > tr > td .flex {
            flex-wrap: wrap !important;
            justify-content: flex-start !important;
            align-items: center !important;
            gap: 0.45rem !important;
            max-width: 100% !important;
            min-width: 0 !important;
        }
        table.nacoc-mobile-cards > tbody > tr > td .truncate,
        table.nacoc-mobile-cards > tbody > tr > td .whitespace-nowrap {
            white-space: normal !important;
            overflow: visible !important;
            text-overflow: unset !important;
            max-width: 100% !important;
        }
        table.nacoc-mobile-cards > tbody > tr > td[data-label*="Action" i] a,
        table.nacoc-mobile-cards > tbody > tr > td[data-label*="Action" i] button,
        table.nacoc-mobile-cards > tbody > tr > td:last-child a,
        table.nacoc-mobile-cards > tbody > tr > td:last-child button {
            min-height: 34px;
        }
        table.nacoc-mobile-cards > tbody > tr > td .opacity-0 {
            opacity: 1 !important;
        }
        table.nacoc-mobile-cards > tbody > tr > td[colspan] {
            display: block !important;
            text-align: center !important;
            border-bottom: 0 !important;
            padding: 1.5rem 0.5rem !important;
            margin: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            background: #ffffff !important;
        }
        table.nacoc-mobile-cards > tbody > tr > td[colspan]::before {
            display: none !important;
            content: none !important;
        }
    }

    @media (max-width: 640px) {
        input:not([type="checkbox"]):not([type="radio"]),
        select,
        textarea {
            max-width: 100%;
        }
    }

    @media print {
        #nacocMobileSidebarBackdrop,
        .nacoc-mobile-menu-btn {
            display: none !important;
        }
    }
</style>

<div id="nacocMobileSidebarBackdrop" aria-hidden="true"></div>

<script>
(function() {
    if (window.__nacocMobileShellInitialized) return;
    window.__nacocMobileShellInitialized = true;

    function getSidebar() {
        return document.querySelector('aside.w-64');
    }

    function setSidebarOpen(open) {
        const aside = getSidebar();
        const backdrop = document.getElementById('nacocMobileSidebarBackdrop');
        if (!aside) return;

        if (open) {
            aside.classList.add('nacoc-mobile-sidebar-open');
            if (backdrop) backdrop.classList.add('is-open');
            document.body.classList.add('nacoc-drawer-lock');
        } else {
            aside.classList.remove('nacoc-mobile-sidebar-open');
            if (backdrop) backdrop.classList.remove('is-open');
            document.body.classList.remove('nacoc-drawer-lock');
        }
    }

    window.nacocToggleMobileSidebar = function(forceState) {
        const aside = getSidebar();
        if (!aside) return;
        const isOpen = aside.classList.contains('nacoc-mobile-sidebar-open');
        setSidebarOpen(typeof forceState === 'boolean' ? forceState : !isOpen);
    };

    function injectMobileMenuButton() {
        const aside = getSidebar();
        if (!aside || document.querySelector('.nacoc-mobile-menu-btn')) return;

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'nacoc-mobile-menu-btn';
        btn.setAttribute('aria-label', 'Open navigation menu');
        btn.setAttribute('title', 'Open Menu');
        btn.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 6h16M4 12h16M4 18h16"/></svg>';
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            window.nacocToggleMobileSidebar();
        });

        const mainHeader = document.querySelector('header:not(.topbar)');
        if (mainHeader) {
            const firstGroup = mainHeader.querySelector(':scope > div');
            if (firstGroup) {
                firstGroup.insertBefore(btn, firstGroup.firstChild);
            } else {
                mainHeader.insertBefore(btn, mainHeader.firstChild);
            }
        } else {
            btn.classList.add('nacoc-mobile-menu-btn--floating');
            document.body.appendChild(btn);
        }

        const backdrop = document.getElementById('nacocMobileSidebarBackdrop');
        if (backdrop) {
            backdrop.addEventListener('click', function() {
                setSidebarOpen(false);
            });
        }

        aside.querySelectorAll('a[href]').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth < 1024) {
                    setSidebarOpen(false);
                }
            });
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                setSidebarOpen(false);
            }
        });

        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024) {
                setSidebarOpen(false);
            }
        });
    }

    function applyMobileTableCardLabels() {
        const tables = document.querySelectorAll('table');
        tables.forEach(function(table) {
            if (table.classList.contains('expelling-table') || table.classList.contains('no-mobile-card')) {
                return;
            }
            const thead = table.querySelector(':scope > thead');
            const tbody = table.querySelector(':scope > tbody');
            if (!thead || !tbody) return;

            const headerRow = thead.querySelector('tr:last-child');
            if (!headerRow) return;
            const headers = Array.from(headerRow.children).map(function(th) {
                return (th.innerText || th.textContent || '').replace(/\s+/g, ' ').trim();
            });
            if (headers.length < 2) return;

            table.classList.add('nacoc-mobile-cards');

            const rows = tbody.querySelectorAll(':scope > tr');
            rows.forEach(function(tr) {
                const cells = Array.from(tr.children).filter(function(el) {
                    return el.tagName === 'TD' || el.tagName === 'TH';
                });
                cells.forEach(function(cell, idx) {
                    if (cell.hasAttribute('colspan')) {
                        cell.removeAttribute('data-label');
                        return;
                    }
                    const label = headers[idx] || '';
                    if (cell.getAttribute('data-label') !== label) {
                        cell.setAttribute('data-label', label);
                    }
                });
            });
        });
    }

    let mobileCardRaf = null;
    function scheduleMobileTableCardLabels() {
        if (mobileCardRaf !== null) return;
        mobileCardRaf = window.requestAnimationFrame(function() {
            mobileCardRaf = null;
            applyMobileTableCardLabels();
        });
    }

    function initMobileResponsiveShell() {
        injectMobileMenuButton();
        applyMobileTableCardLabels();

        if (typeof MutationObserver === 'function' && document.body) {
            const observer = new MutationObserver(function() {
                scheduleMobileTableCardLabels();
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMobileResponsiveShell);
    } else {
        initMobileResponsiveShell();
    }
})();
</script>

