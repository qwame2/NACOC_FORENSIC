@php
    $normalizedAlertDomain = \App\Models\Exhibit::normalizeCreateUnitKey($alertDomain ?? '') ?: strtolower(trim((string) ($alertDomain ?? '')));
    $isSupportedAlertDomain = in_array($normalizedAlertDomain, ['lab', 'digital'], true);
@endphp

@if($isSupportedAlertDomain)
<div id="unitExhibitAlertPopover"
     data-domain="{{ $normalizedAlertDomain }}"
     data-endpoint="{{ route('api.unit.exhibit_alerts', ['domain' => $normalizedAlertDomain]) }}"
     role="dialog"
     aria-live="polite"
     aria-label="{{ $normalizedAlertDomain === 'lab' ? 'Drug Exhibit and Suspect Alerts' : 'Electronic Device Exhibit and Suspect Alerts' }}"
     style="z-index: 2147483647;"
     class="hidden fixed bottom-5 right-5 z-[2147483647] w-[calc(100vw-2.5rem)] max-w-md bg-white border {{ $normalizedAlertDomain === 'lab' ? 'border-emerald-300 shadow-emerald-950/15' : 'border-cyan-300 shadow-cyan-950/15' }} rounded-2xl shadow-2xl overflow-hidden transition-all duration-200">
    <div class="px-4 py-3 {{ $normalizedAlertDomain === 'lab' ? 'bg-emerald-700' : 'bg-cyan-700' }} text-white flex items-center justify-between gap-3">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-8 h-8 rounded-xl bg-white/15 flex items-center justify-center shrink-0">
                <svg class="w-4.5 h-4.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
            </span>
            <div class="min-w-0">
                <p class="text-xs font-extrabold tracking-tight truncate">
                    {{ $normalizedAlertDomain === 'lab' ? 'Cross-Unit Drug Intake Notice' : 'Cross-Unit Electronic Device Notice' }}
                </p>
                <p class="text-[11px] text-white/85 font-medium truncate">
                    <span id="unitExhibitAlertCount">0</span>
                    <span>active notifications</span>
                </p>
            </div>
        </div>
        <button type="button"
                id="unitExhibitAlertDismissAllBtn"
                onclick="dismissAllUnitExhibitAlerts()"
                class="p-1.5 rounded-lg text-white/80 hover:text-white hover:bg-white/15 transition-colors shrink-0"
                title="Dismiss popover">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <div id="unitExhibitAlertList" class="max-h-80 overflow-y-auto divide-y divide-slate-100 p-2 space-y-1.5"></div>
</div>

<script>
    (function() {
        const popover = document.getElementById('unitExhibitAlertPopover');
        const listEl = document.getElementById('unitExhibitAlertList');
        const countEl = document.getElementById('unitExhibitAlertCount');
        if (!popover || !listEl) return;

        // Ensure popover sits at document.body root so ancestor stacking contexts/overflow cannot clip or layer over it
        if (document.body && popover.parentElement !== document.body) {
            document.body.appendChild(popover);
        }

        const domain = popover.getAttribute('data-domain') || '';
        const endpoint = popover.getAttribute('data-endpoint') || '';
        const storageKey = 'unitExhibitAlertsDismissed:' + domain;
        let currentAlerts = [];

        function escAlertHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str).replace(/[&<>"']/g, function(ch) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch] || ch;
            });
        }

        function getDismissedIds() {
            try {
                const raw = sessionStorage.getItem(storageKey);
                const parsed = raw ? JSON.parse(raw) : [];
                return Array.isArray(parsed) ? parsed.map(String) : [];
            } catch (e) {
                return [];
            }
        }

        function saveDismissedIds(ids) {
            try {
                sessionStorage.setItem(storageKey, JSON.stringify(ids));
            } catch (e) {}
        }

        function buildHeadline(alertItem) {
            const unitLabel = escAlertHtml(alertItem.source_unit || '');
            const isLab = domain === 'lab';
            if (alertItem.alert_type === 'unassigned_exhibit') {
                return isLab
                    ? (unitLabel ? `${unitLabel} entered a new drug-related exhibit (without a suspect)` : '')
                    : (unitLabel ? `${unitLabel} entered a new electronic-device-related exhibit (without a suspect)` : '');
            }
            if (alertItem.alert_type === 'suspect_associated') {
                return isLab
                    ? (unitLabel ? `${unitLabel} entered a suspect associated with drugs` : '')
                    : (unitLabel ? `${unitLabel} entered a suspect associated with electronic devices` : '');
            }
            return '';
        }

        function renderAlerts() {
            const dismissed = new Set(getDismissedIds());
            const visibleAlerts = (Array.isArray(currentAlerts) ? currentAlerts : []).filter(function(item) {
                return item && item.id && !dismissed.has(String(item.id));
            });

            if (countEl) {
                countEl.textContent = String(visibleAlerts.length);
            }

            if (visibleAlerts.length === 0) {
                popover.classList.add('hidden');
                listEl.innerHTML = '';
                return;
            }

            const countContainer = countEl ? countEl.nextElementSibling : null;
            if (countContainer) {
                countContainer.textContent = visibleAlerts.length === 1 ? 'active notification' : 'active notifications';
            }

            listEl.innerHTML = visibleAlerts.map(function(item) {
                const alertId = escAlertHtml(item.id);
                const headline = buildHeadline(item);
                const metaBadges = [];

                if (item.source_unit) {
                    metaBadges.push(`<span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-bold text-[10px]">${escAlertHtml(item.source_unit)}</span>`);
                }
                if (item.case_number) {
                    metaBadges.push(`<span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-mono-code font-bold text-[10px]">${escAlertHtml(item.case_number)}</span>`);
                }
                if (item.property_id) {
                    metaBadges.push(`<span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono-code font-bold text-[10px]">${escAlertHtml(item.property_id)}</span>`);
                }

                let bodyDetail = '';
                if (item.alert_type === 'unassigned_exhibit') {
                    const commodityLabel = [item.commodity_name, item.exhibit_category].filter(Boolean).map(escAlertHtml).join(' • ');
                    if (commodityLabel) {
                        bodyDetail += `<p class="text-xs font-bold text-slate-800">${commodityLabel}</p>`;
                    }
                } else if (item.alert_type === 'suspect_associated') {
                    if (item.suspect_name) {
                        bodyDetail += `<p class="text-xs font-bold text-slate-900">Suspect: ${escAlertHtml(item.suspect_name)}</p>`;
                    }
                    if (Array.isArray(item.exhibits_summary) && item.exhibits_summary.length > 0) {
                        bodyDetail += `<p class="text-[11px] font-semibold text-slate-600">${item.exhibits_summary.map(escAlertHtml).join(', ')}</p>`;
                    }
                }

                if (item.entered_by_name || item.created_at_human) {
                    const footerParts = [item.entered_by_name, item.created_at_human].filter(Boolean).map(escAlertHtml);
                    if (footerParts.length > 0) {
                        bodyDetail += `<p class="text-[10px] text-slate-400 font-medium">${footerParts.join(' • ')}</p>`;
                    }
                }

                let actionButton = '';
                if (item.alert_type === 'unassigned_exhibit' && item.exhibit_id) {
                    actionButton = `<button type="button" onclick="selectUnassignedExhibitFromPopover('${escAlertHtml(item.exhibit_id)}', '${alertId}')" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold transition-colors">Link Exhibit</button>`;
                } else if (item.alert_type === 'suspect_associated' && item.review_url) {
                    actionButton = `<a href="${escAlertHtml(item.review_url)}" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-[11px] font-bold transition-colors inline-block">Review Case</a>`;
                }

                return `
                    <div class="p-3 rounded-xl bg-slate-50/90 hover:bg-white border border-slate-200/80 space-y-1.5 transition-colors">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-xs font-extrabold text-slate-900 leading-snug">${headline}</p>
                            <button type="button" onclick="dismissUnitExhibitAlert('${alertId}')" class="text-slate-400 hover:text-slate-600 p-0.5 shrink-0" title="Dismiss">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        ${metaBadges.length > 0 ? `<div class="flex flex-wrap gap-1">${metaBadges.join('')}</div>` : ''}
                        ${bodyDetail}
                        ${actionButton ? `<div class="pt-1 flex justify-end">${actionButton}</div>` : ''}
                    </div>
                `;
            }).join('');

            popover.classList.remove('hidden');
        }

        window.dismissUnitExhibitAlert = function(alertId) {
            if (!alertId) return;
            const ids = new Set(getDismissedIds());
            ids.add(String(alertId));
            saveDismissedIds(Array.from(ids));
            renderAlerts();
        };

        window.dismissAllUnitExhibitAlerts = function() {
            const ids = new Set(getDismissedIds());
            (Array.isArray(currentAlerts) ? currentAlerts : []).forEach(function(item) {
                if (item && item.id) ids.add(String(item.id));
            });
            saveDismissedIds(Array.from(ids));
            renderAlerts();
        };

        window.selectUnassignedExhibitFromPopover = async function(exhibitId, alertId) {
            const noSuspectBox = document.getElementById('noSuspectToggleCheckbox');
            if (noSuspectBox && !noSuspectBox.checked) {
                noSuspectBox.checked = true;
                noSuspectBox.dispatchEvent(new Event('change', { bubbles: true }));
            }

            if (typeof window.refreshUnassignedExhibitsCatalog === 'function') {
                await window.refreshUnassignedExhibitsCatalog();
            }

            const catalogItem = (window.unassignedExhibitsCatalog || {})[String(exhibitId)];
            const select = document.getElementById('linkedUnassignedExhibitsSelect');
            if (select) {
                const targetOpt = Array.from(select.options || []).find(function(opt) {
                    return String(opt.value) === String(exhibitId);
                });
                const isLocked = Boolean(
                    (catalogItem && (catalogItem.has_preliminary_report || catalogItem.is_selectable === false))
                    || (targetOpt && targetOpt.disabled)
                );
                if (isLocked) {
                    alert('This exhibit already has an attached Preliminary Exhibit Analysis Report PDF and cannot be selected again.');
                    return;
                }
                if (targetOpt) {
                    targetOpt.selected = true;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }

            if (typeof window.autoFillManifestFromLinkedExhibits === 'function') {
                window.autoFillManifestFromLinkedExhibits();
            }

            const selectorCard = document.getElementById('unassignedExhibitsSelectorCard');
            if (selectorCard && typeof selectorCard.scrollIntoView === 'function') {
                selectorCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            if (alertId) {
                window.dismissUnitExhibitAlert(alertId);
            }
        };

        window.refreshUnitExhibitAlerts = async function() {
            if (!endpoint) return;
            try {
                const resp = await fetch(endpoint, { headers: { 'Accept': 'application/json' } });
                if (!resp.ok) return;
                const payload = await resp.json();
                if (!payload || !payload.success || !Array.isArray(payload.alerts)) return;
                currentAlerts = payload.alerts;
                renderAlerts();
            } catch (e) {}
        };

        renderAlerts();
        window.refreshUnitExhibitAlerts();
        setInterval(window.refreshUnitExhibitAlerts, 15000);
    })();
</script>
@endif

