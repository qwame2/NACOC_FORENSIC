/**
 * NACOC Forensic Suite - Reusable AJAX Table & Skeleton Loader Engine
 * 
 * Provides instantaneous filter interception, skeleton loading animations,
 * race-condition prevention (AbortController), debounced text search,
 * browser history sync (pushState / popstate), and pagination interception.
 *
 * Usage:
 *   initAjaxTable({
 *       container: '#myTableContainer',   // Element replaced with partial HTML
 *       table: '#myTable',                // Table element for column detection
 *       form: '#filterForm',              // Optional filter form
 *       endpoint: '/my-endpoint',         // Defaults to window.location.pathname
 *       debounce: 350,                    // Debounce ms for text inputs
 *       columns: 7,                       // Fallback skeleton columns
 *       rows: 6,                          // Number of skeleton rows
 *       onAfterUpdate: (container) => {}  // Optional callback after DOM update
 *   });
 */

(function (window, document) {
    'use strict';

    class AjaxTable {
        constructor(config = {}) {
            if (!config.container) {
                console.error('[AjaxTable] "container" selector is required.');
                return;
            }

            this.containerSelector = config.container;
            this.container = document.querySelector(this.containerSelector);
            if (!this.container) {
                console.warn(`[AjaxTable] Container element "${this.containerSelector}" not found in DOM yet.`);
            }

            this.tableSelector = config.table || (this.containerSelector + ' table');
            this.formSelector = config.form || null;
            this.endpoint = config.endpoint || window.location.pathname;
            this.debounceMs = typeof config.debounce === 'number' ? config.debounce : 350;
            this.defaultColumns = config.columns || 6;
            this.defaultRows = config.rows || 6;
            this.filterInputs = config.filterInputs || null;
            this.skeletonRenderer = typeof config.skeletonRenderer === 'function' ? config.skeletonRenderer : null;
            this.onAfterUpdate = typeof config.onAfterUpdate === 'function' ? config.onAfterUpdate : null;
            this.onBeforeFetch = typeof config.onBeforeFetch === 'function' ? config.onBeforeFetch : null;
            this.syncInputsOnPopstate = config.syncInputs !== false;

            this.activeController = null;
            this.debounceTimer = null;
            this.previousHtml = null;
            this.isLoading = false;

            this.init();
        }

        init() {
            // Re-acquire container if not found at construct time
            if (!this.container) {
                this.container = document.querySelector(this.containerSelector);
            }
            if (!this.container) return;

            this.bindFormAndInputs();
            this.bindPaginationAndSorting();
            this.bindPopstate();
        }

        /**
         * Binds filter forms, text search boxes, dropdowns, radios, and dates
         */
        bindFormAndInputs() {
            // 1. If form exists, prevent traditional submit
            if (this.formSelector) {
                const form = document.querySelector(this.formSelector);
                if (form) {
                    form.addEventListener('submit', (e) => {
                        e.preventDefault();
                        if (this.debounceTimer) clearTimeout(this.debounceTimer);
                        this.applyFilters();
                    });
                }
            }

            // 2. Discover all inputs: either within the form, within container, or by filterInputs selector
            let inputs = [];
            if (this.filterInputs) {
                if (typeof this.filterInputs === 'string') {
                    inputs = Array.from(document.querySelectorAll(this.filterInputs));
                } else if (Array.isArray(this.filterInputs)) {
                    inputs = this.filterInputs;
                }
            } else if (this.formSelector) {
                const form = document.querySelector(this.formSelector);
                if (form) {
                    inputs = Array.from(form.querySelectorAll('input, select, textarea'));
                }
            }

            // Also check for elements with data-table-filter matching this container
            const declaredFilters = Array.from(document.querySelectorAll(`[data-table-filter="${this.containerSelector}"], [data-table-filter="default"]`));
            inputs = Array.from(new Set([...inputs, ...declaredFilters]));

            inputs.forEach(input => {
                const tag = input.tagName.toLowerCase();
                const type = (input.type || '').toLowerCase();

                if (tag === 'input' && (type === 'text' || type === 'search' || type === 'tel' || type === 'email')) {
                    // Debounced input
                    input.addEventListener('input', () => {
                        if (this.debounceTimer) clearTimeout(this.debounceTimer);
                        this.debounceTimer = setTimeout(() => {
                            this.applyFilters();
                        }, this.debounceMs);
                    });

                    // Immediate on Enter
                    input.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            if (this.debounceTimer) clearTimeout(this.debounceTimer);
                            this.applyFilters();
                        }
                    });
                } else if (tag === 'select' || type === 'date' || type === 'month' || type === 'radio' || type === 'checkbox') {
                    // Immediate on change
                    input.addEventListener('change', () => {
                        if (this.debounceTimer) clearTimeout(this.debounceTimer);
                        this.applyFilters();
                    });
                }
            });
        }

        /**
         * Intercepts pagination links and sorting headers inside the table container,
         * as well as document-level filter tabs and buttons targeting this table.
         */
        bindPaginationAndSorting() {
            if (!this.container) return;

            // 1. Container-level event delegation (pagination & sort headers)
            this.container.addEventListener('click', (e) => {
                const link = e.target.closest('a[href]');
                if (!link) return;

                // Check if this is a pagination link, sorting link, or tab link
                const isPagination = link.closest('.pagination') ||
                                     link.classList.contains('page-link') ||
                                     link.getAttribute('rel') === 'prev' ||
                                     link.getAttribute('rel') === 'next' ||
                                     link.hasAttribute('data-pagination-link') ||
                                     (link.closest('nav[role="navigation"]') && link.href.includes('page='));

                const isSort = link.hasAttribute('data-sort-link') || link.classList.contains('sort-link');
                const isTab = link.hasAttribute('data-table-tab') || link.hasAttribute('data-ajax-tab');

                if (isPagination || isSort || isTab) {
                    e.preventDefault();
                    const targetUrl = link.href;
                    if (targetUrl && targetUrl !== '#' && !targetUrl.startsWith('javascript:')) {
                        this.fetchData(targetUrl, true);
                    }
                }
            });

            // 2. Document-level delegation for filter tab links / action buttons targeting this table
            document.addEventListener('click', (e) => {
                const link = e.target.closest('a[data-table-tab], a[data-ajax-filter], a[data-table-filter-link]');
                if (!link) return;

                const targetTarget = link.getAttribute('data-table-target');
                if (targetTarget && targetTarget !== this.containerSelector) {
                    return; // Link explicitly targets a different table container
                }

                e.preventDefault();
                const targetUrl = link.href;
                if (targetUrl && targetUrl !== '#' && !targetUrl.startsWith('javascript:')) {
                    this.fetchData(targetUrl, true);
                }
            });
        }

        /**
         * Handles browser Back / Forward buttons without full page reloads
         */
        bindPopstate() {
            window.addEventListener('popstate', (e) => {
                const currentUrl = window.location.href;
                // Fetch the new URL state without pushing another state
                this.fetchData(currentUrl, false);

                // Sync inputs to match search params
                if (this.syncInputsOnPopstate) {
                    this.syncInputValuesFromUrl(currentUrl);
                }
            });
        }

        /**
         * Collects filter parameters from form and declared filter controls
         */
        collectParams() {
            const params = new URLSearchParams(window.location.search);
            params.delete('page');

            // 1. From Form if defined
            if (this.formSelector) {
                const form = document.querySelector(this.formSelector);
                if (form) {
                    const fd = new FormData(form);
                    for (const [key, value] of fd.entries()) {
                        if (value !== '' && value !== null) {
                            params.set(key, value);
                        } else {
                            params.delete(key);
                        }
                    }
                }
            }

            // 2. From declared filter elements
            const inputs = document.querySelectorAll(`[data-table-filter="${this.containerSelector}"], [data-table-filter="default"]`);
            inputs.forEach(input => {
                const name = input.name || input.id;
                if (!name) return;

                let val = null;
                if (input.type === 'checkbox') {
                    if (input.checked) val = input.value || '1';
                } else if (input.type === 'radio') {
                    if (input.checked) val = input.value;
                } else {
                    val = input.value;
                }

                if (val !== null && val !== '') {
                    params.set(name, val);
                } else {
                    params.delete(name);
                }
            });

            return params;
        }

        /**
         * Applies current filters and initiates AJAX request
         */
        applyFilters(customParams = {}) {
            const params = this.collectParams();

            // Merge any custom params passed in
            for (const [k, v] of Object.entries(customParams)) {
                if (v === null || v === '') {
                    params.delete(k);
                } else {
                    params.set(k, v);
                }
            }

            // Construct target URL
            const url = new URL(this.endpoint, window.location.origin);
            url.search = params.toString();

            this.fetchData(url.toString(), true);
        }

        /**
         * Core AJAX Fetcher with AbortController and Skeleton Loader
         */
        async fetchData(urlStr, pushHistory = true) {
            // Re-acquire container in case DOM shifted
            if (!this.container) {
                this.container = document.querySelector(this.containerSelector);
            }
            if (!this.container) return;

            // 1. Abort previous in-flight request
            if (this.activeController) {
                this.activeController.abort();
            }
            this.activeController = new AbortController();

            // 2. Allow hook to alter or prepare
            if (this.onBeforeFetch) {
                this.onBeforeFetch(urlStr);
            }

            // 3. Show Skeleton Loader
            this.showSkeleton();
            this.isLoading = true;

            const targetUrl = new URL(urlStr, window.location.origin);

            try {
                const response = await fetch(targetUrl.toString(), {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html, application/json'
                    },
                    signal: this.activeController.signal
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const contentType = response.headers.get('content-type') || '';
                let newHtml = '';
                let responseData = null;

                if (contentType.includes('application/json')) {
                    responseData = await response.json();
                    newHtml = responseData.html || responseData.table || '';
                } else {
                    newHtml = await response.text();
                }

                // 4. Update Table Container DOM
                if (newHtml.trim().length > 0) {
                    this.container.innerHTML = newHtml;
                }

                // 5. Update browser history URL
                if (pushHistory) {
                    window.history.pushState(
                        { ajaxTable: true, url: targetUrl.toString() },
                        '',
                        targetUrl.pathname + targetUrl.search
                    );
                }

                // 6. Post-update hook (icons, modals, dropdowns)
                if (this.onAfterUpdate) {
                    this.onAfterUpdate(this.container, responseData);
                }

                // Re-initialize Lucide icons if available
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }

            } catch (err) {
                if (err.name === 'AbortError') {
                    // Normal request cancellation from new user input — ignore
                    return;
                }

                console.error('[AjaxTable] Request failed:', err);
                this.showError(err.message, () => this.fetchData(urlStr, pushHistory));
            } finally {
                this.isLoading = false;
            }
        }

        /**
         * Renders high-fidelity animated pulse skeleton loading state
         */
        showSkeleton() {
            if (!this.container) return;
            this.previousHtml = this.container.innerHTML;

            // Dim pagination during loading if present
            const pagNav = this.container.querySelector('nav[role="navigation"], .pagination');
            if (pagNav) {
                pagNav.classList.add('opacity-40', 'pointer-events-none');
            }

            const tableEl = this.container.querySelector('table');
            if (tableEl) {
                // If existing table has thead, preserve thead and replace tbody with skeleton
                const tbody = tableEl.querySelector('tbody');
                const thead = tableEl.querySelector('thead');
                const colCount = thead ? thead.querySelectorAll('th').length : this.defaultColumns;

                if (tbody) {
                    if (this.skeletonRenderer) {
                        tbody.innerHTML = this.skeletonRenderer(colCount, this.defaultRows);
                    } else {
                        tbody.innerHTML = this.buildSkeletonRowsHtml(colCount, this.defaultRows);
                    }
                    return;
                }
            }

            // If no table exists yet or entire card is being replaced, build full skeleton table
            this.container.innerHTML = this.buildFullSkeletonHtml(this.defaultColumns, this.defaultRows);
            if (this.skeletonRenderer) {
                const newTbody = this.container.querySelector('tbody');
                if (newTbody) {
                    newTbody.innerHTML = this.skeletonRenderer(this.defaultColumns, this.defaultRows);
                }
            }
        }

        /**
         * Generates animated skeleton rows mimicking real data
         */
        buildSkeletonRowsHtml(colCount = 6, rowCount = 6) {
            let rowsHtml = '';
            const widthPatterns = [
                ['w-20', 'w-44', 'w-24', 'w-28', 'w-16 rounded-full', 'w-14 rounded-lg'],
                ['w-24', 'w-36', 'w-28', 'w-32', 'w-20 rounded-full', 'w-14 rounded-lg'],
                ['w-16', 'w-48', 'w-20', 'w-24', 'w-16 rounded-full', 'w-14 rounded-lg'],
                ['w-28', 'w-40', 'w-32', 'w-36', 'w-24 rounded-full', 'w-14 rounded-lg'],
                ['w-20', 'w-32', 'w-24', 'w-28', 'w-16 rounded-full', 'w-14 rounded-lg'],
                ['w-24', 'w-44', 'w-28', 'w-20', 'w-20 rounded-full', 'w-14 rounded-lg'],
            ];

            for (let r = 0; r < rowCount; r++) {
                const widths = widthPatterns[r % widthPatterns.length];
                rowsHtml += `<tr class="animate-pulse border-b border-slate-100 hover:bg-transparent">`;

                for (let c = 0; c < colCount; c++) {
                    const w = widths[c % widths.length];
                    const isLast = (c === colCount - 1);
                    const isBadge = (c === 2) || (colCount >= 8 && c === 6) || (c === colCount - 2);

                    if (c === 0) {
                        // First column: reference ID / Code + subtext
                        rowsHtml += `
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="h-3.5 bg-slate-200 rounded ${w}"></div>
                                <div class="h-2.5 bg-slate-100 rounded w-14 mt-1.5"></div>
                            </td>`;
                    } else if (c === 1) {
                        // Second column: Subject name / Avatar / Title
                        rowsHtml += `
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-xl bg-slate-200 shrink-0"></div>
                                    <div class="space-y-1.5 flex-1">
                                        <div class="h-3.5 bg-slate-200 rounded ${w}"></div>
                                        <div class="h-2.5 bg-slate-100 rounded w-20"></div>
                                    </div>
                                </div>
                            </td>`;
                    } else if (isLast) {
                        // Action column
                        rowsHtml += `
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <div class="h-7 bg-slate-200 rounded-xl w-14 ml-auto"></div>
                            </td>`;
                    } else if (isBadge) {
                        // Status badge column / Type column
                        rowsHtml += `
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <div class="h-5 bg-slate-200 rounded-full w-20 mx-auto"></div>
                            </td>`;
                    } else {
                        // Standard details
                        rowsHtml += `
                            <td class="py-3.5 px-4">
                                <div class="h-3.5 bg-slate-200 rounded ${w}"></div>
                            </td>`;
                    }
                }

                rowsHtml += `</tr>`;
            }

            return rowsHtml;
        }

        /**
         * Builds a full card skeleton with simulated header and body
         */
        buildFullSkeletonHtml(colCount = 6, rowCount = 6) {
            return `
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-100/80 border-b border-slate-200">
                                ${Array(colCount).fill(0).map(() => `
                                    <th class="py-3.5 px-4">
                                        <div class="h-3 bg-slate-300 rounded w-16 animate-pulse"></div>
                                    </th>
                                `).join('')}
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            ${this.buildSkeletonRowsHtml(colCount, rowCount)}
                        </tbody>
                    </table>
                </div>
            `;
        }

        /**
         * Displays error banner inside table container with retry option
         */
        showError(message, retryCallback) {
            if (!this.container) return;

            const errorHtml = `
                <div class="p-8 text-center bg-rose-50/50 border border-rose-200 rounded-2xl m-4 space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center mx-auto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div class="font-extrabold text-sm text-slate-800">Failed to load table data</div>
                    <p class="text-xs text-slate-500 max-w-md mx-auto leading-relaxed">
                        ${message || 'An unexpected connection or server error occurred while retrieving records.'}
                    </p>
                    <div class="pt-2 flex items-center justify-center gap-3">
                        ${this.previousHtml ? `
                            <button type="button" class="btn-restore-prev px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl transition-all cursor-pointer">
                                Restore Previous
                            </button>
                        ` : ''}
                        <button type="button" class="btn-retry-fetch px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition-all cursor-pointer">
                            Try Again
                        </button>
                    </div>
                </div>
            `;

            this.container.innerHTML = errorHtml;

            const retryBtn = this.container.querySelector('.btn-retry-fetch');
            if (retryBtn && typeof retryCallback === 'function') {
                retryBtn.addEventListener('click', () => retryCallback());
            }

            const restoreBtn = this.container.querySelector('.btn-restore-prev');
            if (restoreBtn && this.previousHtml) {
                restoreBtn.addEventListener('click', () => {
                    this.container.innerHTML = this.previousHtml;
                });
            }
        }

        /**
         * Synchronizes input values from URL search parameters on popstate (Back/Forward)
         */
        syncInputValuesFromUrl(urlStr) {
            try {
                const url = new URL(urlStr, window.location.origin);
                const params = url.searchParams;

                // Sync form inputs
                if (this.formSelector) {
                    const form = document.querySelector(this.formSelector);
                    if (form) {
                        Array.from(form.elements).forEach(el => {
                            if (!el.name) return;
                            if (params.has(el.name)) {
                                if (el.type === 'checkbox') {
                                    el.checked = (params.get(el.name) === el.value || params.get(el.name) === '1');
                                } else if (el.type === 'radio') {
                                    el.checked = (params.get(el.name) === el.value);
                                } else {
                                    el.value = params.get(el.name);
                                }
                            } else if (el.type !== 'button' && el.type !== 'submit') {
                                if (el.type === 'checkbox' || el.type === 'radio') {
                                    el.checked = false;
                                } else {
                                    el.value = '';
                                }
                            }
                        });
                    }
                }
            } catch (e) {
                console.warn('[AjaxTable] Failed to sync inputs from URL:', e);
            }
        }
    }

    // Expose Global Class and Factory Function
    window.AjaxTable = AjaxTable;
    window.initAjaxTable = function (config) {
        return new AjaxTable(config);
    };

    // Auto-discover declared AJAX tables on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-ajax-table]').forEach(el => {
            const selector = el.id ? `#${el.id}` : null;
            if (selector) {
                const formSelector = el.getAttribute('data-ajax-form');
                const endpoint = el.getAttribute('data-ajax-endpoint');
                window.initAjaxTable({
                    container: selector,
                    form: formSelector,
                    endpoint: endpoint || window.location.pathname
                });
            }
        });
    });

})(window, document);
