@if(empty($seizuresOnlyMode))
@php
    $selectorUnit = $selectorTargetUnit ?? ($userUnit ?? (auth()->user()?->unit ?? ''));
    $unassignedExhibitsList = \App\Models\Exhibit::with(['singleSuspectCase.user', 'multipleSuspectCase.user', 'labResult'])
        ->unassignedOrAwaitingSuspect()
        ->forCreateUnit($selectorUnit)
        ->orderByDesc('id')
        ->get();
    $unassignedManifestPayloads = $unassignedExhibitsList
        ->map(fn ($ue) => $ue->toManifestPayloadArray())
        ->values();
@endphp

<!-- LINK EXISTING SEIZED EXHIBIT (SEIZED WITHOUT SUSPECT) CARD -->
<div id="unassignedExhibitsSelectorCard"
    data-current-user-id="{{ auth()->id() ?? '' }}"
    data-viewer-unit="{{ $selectorUnit }}"
    class="bg-gradient-to-br from-emerald-50/60 via-white to-slate-50 p-5 rounded-2xl border border-emerald-200/90 shadow-2xs space-y-4 transition-all">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-3 border-b border-emerald-100">
        <div class="flex items-center space-x-3">
            <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-xs shrink-0">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
            </div>
            <div>
                <h4 class="font-extrabold text-slate-900 text-xs uppercase tracking-wider flex flex-wrap items-center gap-2">
                    <span>Link Existing Seized Exhibit(s)</span>
                    <span class="text-[10px] font-mono-code font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                        Seized Without Suspect
                    </span>
                    <span id="unassignedSelectorActiveBadge" class="hidden text-[10px] font-mono-code font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-900 border border-amber-300 uppercase">
                        Activated &bull; Auto-Fill Active
                    </span>
                </h4>
                <p id="unassignedSelectorHelpText" class="text-[11px] text-slate-500 font-medium">
                    Select exhibit(s) previously logged without a suspect to auto-fill Section 4 Seized Commodities &amp; Contraband Manifest or link to a suspect.
                </p>
            </div>
        </div>
        <div class="flex items-center space-x-2 shrink-0">
            <span id="unassignedCountBadge" class="px-2.5 py-1 bg-white text-emerald-700 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider shadow-2xs">
                {{ $unassignedExhibitsList->count() }} Available in Vault
            </span>
        </div>
    </div>

    <div class="space-y-2">
        <div class="flex items-center justify-between gap-2">
            <label for="linkedUnassignedExhibitsSelect" class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase">
                Select Exhibit(s) from Vault
            </label>
            <span id="unassignedAutoFillStatus" class="hidden text-[10px] font-bold text-amber-800 bg-amber-50 border border-amber-200 px-2.5 py-0.5 rounded-full"></span>
        </div>
        <div class="w-full">
            <select id="linkedUnassignedExhibitsSelect" name="linked_exhibit_ids[]" multiple="multiple"
                class="select2-unassigned-exhibits w-full"
                data-placeholder="Search by Property ID, Commodity Name, Location, or Date...">
                @foreach($unassignedExhibitsList as $ue)
                    @php
                        $payload = $ue->toManifestPayloadArray();
                        $isLocked = !empty($payload['has_preliminary_report']) || !empty($payload['has_dpf_status_report']) || ($payload['is_selectable'] ?? true) === false;
                        $attachedMeta = is_array($payload['preliminary_report'] ?? null) ? $payload['preliminary_report'] : null;
                        $lockSuffix = (($attachedMeta['report_type'] ?? '') === 'dpf_status_log')
                            ? ' [Extraction & Investigation Status Log Report PDF Attached — Locked]'
                            : ' [Preliminary Report PDF Attached — Locked]';
                    @endphp
                    <option value="{{ $ue->id }}"
                        @if($isLocked) disabled @endif
                        data-case-number="{{ $payload['case_number'] ?? '' }}"
                        data-property-id="{{ $ue->property_id }}"
                        data-exhibit-number="{{ $ue->exhibit_number }}"
                        data-category="{{ $ue->exhibit_category }}"
                        data-commodity-name="{{ $ue->commodity_name }}"
                        data-quantity="{{ $ue->seizure_quantity }}"
                        data-amount="{{ $ue->amount }}"
                        data-weight="{{ $ue->seizure_weight }}"
                        data-unit="{{ $ue->seizure_unit }}"
                        data-packaging="{{ $ue->seizure_packaging }}"
                        data-value="{{ $ue->seizure_value }}"
                        data-location="{{ $ue->seizure_location }}"
                        data-date="{{ $ue->seizure_date }}"
                        data-officer="{{ $ue->seizure_officer }}"
                        data-investigating-officer="{{ $ue->investigating_officer }}"
                        data-source-unit="{{ $ue->source_requesting_unit }}"
                        data-source-of-exhibits="{{ $ue->source_of_exhibits }}"
                        data-reason="{{ $ue->seizure_reason }}"
                        data-remark="{{ $ue->exhibit_remark }}"
                        data-time="{{ $ue->seizure_time }}"
                        data-concealment="{{ $ue->seizure_concealment }}"
                        data-description="{{ $payload['description_of_exhibits'] ?? '' }}"
                        data-other-description="{{ $ue->other_exhibit_description }}"
                        data-contents="{{ $ue->contents }}"
                        data-exhibit-type="{{ $ue->exhibit_type }}"
                        data-drug-classification="{{ $ue->drug_classification }}"
                        data-dev-model="{{ $ue->dev_model }}"
                        data-dev-serial-no="{{ $ue->dev_serial_no }}"
                        data-dev-imei1="{{ $ue->dev_imei1 }}"
                        data-dev-imei2="{{ $ue->dev_imei2 }}"
                        data-dev-mac-address="{{ $ue->dev_mac_address }}"
                        data-dev-storage="{{ $ue->dev_storage }}"
                        data-dev-colour="{{ $ue->dev_colour }}"
                        data-dev-marks="{{ $ue->dev_marks }}"
                        data-evidence-photos='@json($payload['evidence_photos'] ?? [])'
                        data-preliminary-report='@json($payload['preliminary_report'] ?? null)'
                        data-has-preliminary-report="{{ $isLocked ? '1' : '0' }}"
                        data-is-selectable="{{ $isLocked ? '0' : '1' }}">
                        {{ $payload['display_text'] }}{{ $isLocked ? $lockSuffix : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <p class="text-[11px] text-slate-500">
            Selecting saved exhibit(s) automatically populates the Seized Commodities &amp; Contraband Manifest below with the saved details.
        </p>

        <label for="linkSuspectToLockedExhibitCheckbox" id="linkSuspectToLockedExhibitLabel"
            class="flex items-start sm:items-center justify-between gap-3 p-3 rounded-xl border border-indigo-200 bg-indigo-50/70 hover:bg-indigo-50 cursor-pointer transition-colors">
            <div class="flex items-start gap-2.5">
                <input type="checkbox" id="linkSuspectToLockedExhibitCheckbox" name="link_suspect_to_locked_exhibit" value="1"
                    onchange="window.toggleLinkSuspectToLockedExhibitMode && window.toggleLinkSuspectToLockedExhibitMode()"
                    class="mt-0.5 sm:mt-0 w-4 h-4 text-indigo-600 rounded border-indigo-400 focus:ring-indigo-500 cursor-pointer">
                <div>
                    <span class="block text-xs font-extrabold text-indigo-950 uppercase tracking-wide">
                        Link Suspect Biodata to Exhibit Without Suspect
                    </span>
                    <span class="block text-[11px] font-medium text-indigo-800/80">
                        Check this box to select an exhibit without a suspect (including locked exhibits with an attached Report PDF), enter the suspect's biodata above, and update that existing case using its same Case Reference.
                    </span>
                </div>
            </div>
            <span id="linkSuspectToLockedExhibitBadge"
                class="hidden shrink-0 px-2 py-0.5 rounded-lg bg-indigo-600 text-white text-[10px] font-extrabold uppercase tracking-wider">
                Suspect Linking Active
            </span>
        </label>
    </div>

    <!-- ALL AVAILABLE EXHIBITS WITHOUT SUSPECT(S) DIRECTORY -->
    <div id="availableUnassignedExhibitsContainer" class="space-y-2 pt-2 border-t border-emerald-100/80">
        <div class="text-[11px] font-extrabold uppercase tracking-wider text-slate-700 flex items-center justify-between gap-2">
            <span>All Exhibits Without Suspect(s):</span>
            <span id="availableUnassignedSubCount" class="text-[10px] font-mono-code font-bold text-slate-500">
                {{ $unassignedExhibitsList->count() }} exhibit(s)
            </span>
        </div>
        <div id="availableUnassignedExhibitsList" class="grid grid-cols-1 md:grid-cols-2 gap-2.5 max-h-64 overflow-y-auto pr-1">
            @forelse($unassignedExhibitsList as $ue)
                @php
                    $payload = $ue->toManifestPayloadArray();
                    $enteredBy = trim(implode(' • ', array_filter([$payload['entered_by_unit'] ?? null, $payload['entered_by_name'] ?? null])));
                    $uePhotos = is_array($payload['evidence_photos'] ?? null) ? array_values(array_filter($payload['evidence_photos'])) : [];
                    $prelimReport = is_array($payload['preliminary_report'] ?? null) ? $payload['preliminary_report'] : null;
                    $isLocked = !empty($payload['has_preliminary_report']) || !empty($payload['has_dpf_status_report']) || ($payload['is_selectable'] ?? true) === false;
                    $reportLabel = $prelimReport['report_label'] ?? 'Preliminary Exhibit Analysis Report (PDF)';
                    $lockTitle = ($prelimReport['report_type'] ?? '') === 'dpf_status_log'
                        ? 'Extraction & Investigation Status Log Report PDF attached — cannot be selected again'
                        : 'Preliminary Exhibit Analysis Report PDF attached — cannot be selected again';
                @endphp
                <div data-unassigned-card-id="{{ $ue->id }}"
                    data-locked="{{ $isLocked ? '1' : '0' }}"
                    @if($isLocked)
                        aria-disabled="true"
                        title="{{ $lockTitle }}"
                        class="text-left p-3 bg-slate-50/90 border border-emerald-300 rounded-xl shadow-2xs transition-all flex items-start justify-between gap-2.5 opacity-90 cursor-not-allowed"
                    @else
                        role="button"
                        tabindex="0"
                        onclick="toggleUnassignedExhibitSelection('{{ $ue->id }}')"
                        onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();toggleUnassignedExhibitSelection('{{ $ue->id }}');}"
                        class="text-left p-3 bg-white border border-slate-200 hover:border-emerald-400 rounded-xl shadow-2xs transition-all flex items-start justify-between gap-2.5 group cursor-pointer"
                    @endif>
                    @if(!empty($uePhotos))
                        <div class="relative shrink-0 w-11 h-11 rounded-lg overflow-hidden border border-slate-200 bg-slate-100">
                            <img src="{{ $uePhotos[0] }}" alt="{{ $ue->commodity_name ?: 'Exhibit' }}" class="w-full h-full object-cover">
                            @if(count($uePhotos) > 1)
                                <span class="absolute bottom-0.5 right-0.5 px-1 bg-slate-900/80 text-white text-[9px] font-bold rounded">+{{ count($uePhotos) - 1 }}</span>
                            @endif
                        </div>
                    @endif
                    <div class="space-y-1 min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="px-2 py-0.5 bg-emerald-50 text-emerald-800 border border-emerald-200 font-mono-code font-bold text-[10px] rounded">
                                {{ $ue->property_id ?: ($ue->exhibit_number ?: 'Exhibit #' . $ue->id) }}
                            </span>
                            @if($ue->exhibit_category)
                                <span class="px-2 py-0.5 bg-slate-100 text-slate-700 font-bold text-[10px] rounded uppercase">
                                    {{ $ue->exhibit_category }}
                                </span>
                            @endif
                            @if($enteredBy !== '')
                                <span class="px-2 py-0.5 bg-amber-50 text-amber-800 border border-amber-200 font-bold text-[10px] rounded">
                                    {{ $enteredBy }}
                                </span>
                            @endif
                        </div>
                        <div class="font-extrabold text-slate-900 text-xs truncate">
                            {{ $ue->commodity_name ?: ($ue->exhibit_category ?: 'Unnamed Exhibit') }}
                        </div>
                        <div class="text-[10px] text-slate-500 font-medium truncate">
                            {{ collect([
                                $ue->seizure_quantity ? 'Qty: ' . $ue->seizure_quantity : null,
                                $ue->seizure_weight ? 'Wt: ' . $ue->seizure_weight . ' ' . ($ue->seizure_unit ?: '') : null,
                                $ue->seizure_location ? 'Loc: ' . $ue->seizure_location : null,
                                $ue->seizure_date ? 'Date: ' . $ue->seizure_date : null,
                            ])->filter()->implode(' • ') ?: 'Awaiting suspect assignment' }}
                        </div>
                        @if($prelimReport)
                            <div class="pt-1 flex flex-wrap items-center gap-1.5 text-[10px]" data-attached-prelim-report="{{ $prelimReport['id'] ?? '' }}">
                                <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-900 border border-emerald-300 font-bold rounded">
                                    {{ $reportLabel }}
                                </span>
                                <a href="{{ $prelimReport['url'] ?? '#' }}" target="_blank" rel="noopener" onclick="event.stopPropagation()"
                                    class="px-2 py-0.5 bg-white hover:bg-emerald-50 text-emerald-800 border border-emerald-300 font-bold rounded underline">
                                    Open PDF
                                </a>
                                <a href="{{ $prelimReport['download_url'] ?? ($prelimReport['url'] ?? '#') }}" target="_blank" rel="noopener" download onclick="event.stopPropagation()"
                                    class="px-2 py-0.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded">
                                    Download PDF
                                </a>
                            </div>
                        @endif
                    </div>
                    @if($isLocked)
                        <button type="button" disabled aria-disabled="true" data-unassigned-pill-id="{{ $ue->id }}"
                            class="shrink-0 px-2 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wider bg-slate-200 text-slate-600 cursor-not-allowed">
                            Locked • PDF Attached
                        </button>
                    @else
                        <span data-unassigned-pill-id="{{ $ue->id }}"
                            class="shrink-0 px-2 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wider bg-slate-100 text-slate-600 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                            Select
                        </span>
                    @endif
                </div>
            @empty
                <div class="col-span-full p-3 rounded-xl bg-white/80 border border-dashed border-slate-300 text-[11px] text-slate-500 font-medium text-center">
                    No exhibits without suspect(s) are currently in the Vault.
                </div>
            @endforelse
        </div>
    </div>

    <!-- SELECTED EXHIBITS CARDS PREVIEW -->
    <div id="linkedExhibitsPreviewContainer" class="hidden space-y-2 pt-2">
        <div class="text-[11px] font-extrabold uppercase tracking-wider text-slate-700 flex items-center justify-between">
            <span>Linked Exhibits Preview:</span>
            <span id="linkedCountBadge" class="text-emerald-700 font-bold"></span>
        </div>
        <div id="linkedExhibitsCardsList" class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
            <!-- Dynamically populated via JavaScript -->
        </div>
    </div>
</div>

<script>
    (function() {
        const initialCatalogList = @json($unassignedManifestPayloads);
        window.unassignedExhibitsListData = Array.isArray(initialCatalogList) ? initialCatalogList : [];
        window.unassignedExhibitsCatalog = {};
        window._manifestAutoFilledFromLinked = false;
        window._unassignedSelectionManuallyChanged = false;

        function indexCatalog(list) {
            const map = {};
            (list || []).forEach(item => {
                if (item && item.id !== undefined && item.id !== null) {
                    map[String(item.id)] = item;
                }
            });
            window.unassignedExhibitsListData = Array.isArray(list) ? list : [];
            window.unassignedExhibitsCatalog = map;
        }

        indexCatalog(initialCatalogList);

        function parseOptionPhotos(raw) {
            if (!raw) return [];
            try {
                const parsed = JSON.parse(raw);
                return Array.isArray(parsed) ? parsed.filter(Boolean) : [];
            } catch (e) {
                return [];
            }
        }

        function hasAttachedPreliminaryReport(item) {
            return Boolean(item && (item.has_preliminary_report === true || item.has_preliminary_report === '1' || item.has_dpf_status_report === true || item.has_dpf_status_report === '1' || item.is_selectable === false || item.is_selectable === '0'));
        }

        function resolveReportLabel(reportMeta) {
            if (reportMeta && reportMeta.report_label) return reportMeta.report_label;
            if (reportMeta && reportMeta.report_type === 'dpf_status_log') return 'Extraction & Investigation Status Log Report (PDF)';
            return 'Preliminary Exhibit Analysis Report (PDF)';
        }

        function resolveLockedOptionSuffix(reportMeta) {
            if (reportMeta && reportMeta.report_type === 'dpf_status_log') {
                return 'Extraction & Investigation Status Log Report PDF Attached — Locked';
            }
            return 'Preliminary Report PDF Attached — Locked';
        }

        window.isLinkingSuspectToLockedExhibitMode = function() {
            return Boolean(document.getElementById('linkSuspectToLockedExhibitCheckbox')?.checked);
        };

        function isUnassignedExhibitLocked(item) {
            return hasAttachedPreliminaryReport(item) && !window.isLinkingSuspectToLockedExhibitMode();
        }

        function syncReusedCaseReferenceFromLinkedExhibits() {
            const caseRefInput = document.getElementById('labCaseReferenceInput');
            if (!caseRefInput) return;

            if (window._originalGeneratedCaseRef === undefined) {
                window._originalGeneratedCaseRef = caseRefInput.value || '';
            }

            if (!window.isLinkingSuspectToLockedExhibitMode()) {
                if (caseRefInput.dataset.reusedExistingCaseRef) {
                    delete caseRefInput.dataset.reusedExistingCaseRef;
                    if (window._originalGeneratedCaseRef) {
                        caseRefInput.value = window._originalGeneratedCaseRef;
                    }
                }
                return;
            }

            const select = document.getElementById('linkedUnassignedExhibitsSelect');
            const selectedOptions = select ? Array.from(select.selectedOptions || []) : [];
            let existingCaseRef = '';
            for (const opt of selectedOptions) {
                const item = (window.unassignedExhibitsCatalog || {})[String(opt.value)] || readFallbackItemFromOption(opt);
                const refCandidate = (item && item.case_number) || opt.getAttribute('data-case-number') || '';
                if (refCandidate && String(refCandidate).trim() !== '') {
                    existingCaseRef = String(refCandidate).trim();
                    break;
                }
            }

            if (existingCaseRef) {
                caseRefInput.value = existingCaseRef;
                caseRefInput.dataset.reusedExistingCaseRef = existingCaseRef;
            } else if (caseRefInput.dataset.reusedExistingCaseRef) {
                delete caseRefInput.dataset.reusedExistingCaseRef;
                if (window._originalGeneratedCaseRef) {
                    caseRefInput.value = window._originalGeneratedCaseRef;
                }
            }
        }

        window.toggleLinkSuspectToLockedExhibitMode = function(skipNoSuspectSync) {
            const chk = document.getElementById('linkSuspectToLockedExhibitCheckbox');
            const badge = document.getElementById('linkSuspectToLockedExhibitBadge');
            const isActive = Boolean(chk && chk.checked);

            if (badge) {
                badge.classList.toggle('hidden', !isActive);
            }

            if (isActive && !skipNoSuspectSync) {
                const noSuspectChk = document.getElementById('noSuspectToggleCheckbox');
                if (noSuspectChk && noSuspectChk.checked && typeof window.toggleSections1To3Optional === 'function') {
                    window.toggleSections1To3Optional(false);
                }
                const hasSeizuresChk = document.getElementById('hasSeizuresCheckbox');
                if (hasSeizuresChk && !hasSeizuresChk.checked) {
                    hasSeizuresChk.checked = true;
                    hasSeizuresChk.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }

            const select = document.getElementById('linkedUnassignedExhibitsSelect');
            let preserveIds = select ? Array.from(select.selectedOptions || []).map(o => String(o.value)) : [];
            if (!isActive) {
                preserveIds = preserveIds.filter(id => {
                    const item = (window.unassignedExhibitsCatalog || {})[id];
                    return !hasAttachedPreliminaryReport(item);
                });
            }

            rebuildSelectOptions(window.unassignedExhibitsListData, preserveIds);
            renderLinkedExhibitsPreview();
            if (preserveIds.length > 0 || !isActive) {
                window.autoFillManifestFromLinkedExhibits();
            }
        };

        function parseOptionPrelimReport(raw) {
            if (!raw || raw === 'null') return null;
            try {
                const parsed = JSON.parse(raw);
                return (parsed && typeof parsed === 'object') ? parsed : null;
            } catch (e) {
                return null;
            }
        }

        function readFallbackItemFromOption(opt) {
            if (!opt) return null;
            const hasPrelim = opt.getAttribute('data-has-preliminary-report') === '1' || (!window.isLinkingSuspectToLockedExhibitMode() && opt.disabled);
            return {
                id: Number(opt.value) || opt.value,
                linked_exhibit_id: Number(opt.value) || opt.value,
                case_number: opt.getAttribute('data-case-number') || '',
                property_id: opt.getAttribute('data-property-id') || '',
                exhibit_number: opt.getAttribute('data-exhibit-number') || '',
                exhibit_category: opt.getAttribute('data-category') || '',
                category: opt.getAttribute('data-category') || '',
                commodity_name: opt.getAttribute('data-commodity-name') || '',
                seizure_name: opt.getAttribute('data-commodity-name') || '',
                name: opt.getAttribute('data-commodity-name') || '',
                seizure_quantity: opt.getAttribute('data-quantity') || '',
                quantity: opt.getAttribute('data-quantity') || '',
                amount: opt.getAttribute('data-amount') || '',
                seizure_weight: opt.getAttribute('data-weight') || '',
                weight: opt.getAttribute('data-weight') || '',
                seizure_unit: opt.getAttribute('data-unit') || '',
                unit: opt.getAttribute('data-unit') || '',
                seizure_packaging: opt.getAttribute('data-packaging') || '',
                packaging: opt.getAttribute('data-packaging') || '',
                seizure_value: opt.getAttribute('data-value') || '',
                value: opt.getAttribute('data-value') || '',
                seizure_location: opt.getAttribute('data-location') || '',
                location: opt.getAttribute('data-location') || '',
                seizure_date: opt.getAttribute('data-date') || '',
                date: opt.getAttribute('data-date') || '',
                seizure_officer: opt.getAttribute('data-officer') || '',
                seizing_officer: opt.getAttribute('data-officer') || '',
                investigating_officer: opt.getAttribute('data-investigating-officer') || '',
                source_requesting_unit: opt.getAttribute('data-source-unit') || '',
                source_of_exhibits: opt.getAttribute('data-source-of-exhibits') || '',
                source: opt.getAttribute('data-source-of-exhibits') || '',
                seizure_reason: opt.getAttribute('data-reason') || '',
                reason: opt.getAttribute('data-reason') || '',
                exhibit_remark: opt.getAttribute('data-remark') || '',
                remark: opt.getAttribute('data-remark') || '',
                seizure_time: opt.getAttribute('data-time') || '',
                time: opt.getAttribute('data-time') || '',
                seizure_concealment: opt.getAttribute('data-concealment') || '',
                concealment: opt.getAttribute('data-concealment') || '',
                description_of_exhibits: opt.getAttribute('data-description') || '',
                other_exhibit_description: opt.getAttribute('data-other-description') || '',
                contents: opt.getAttribute('data-contents') || '',
                exhibit_type: opt.getAttribute('data-exhibit-type') || '',
                drug_classification: opt.getAttribute('data-drug-classification') || '',
                dev_model: opt.getAttribute('data-dev-model') || '',
                dev_serial_no: opt.getAttribute('data-dev-serial-no') || '',
                dev_imei1: opt.getAttribute('data-dev-imei1') || '',
                dev_imei2: opt.getAttribute('data-dev-imei2') || '',
                dev_mac_address: opt.getAttribute('data-dev-mac-address') || '',
                dev_storage: opt.getAttribute('data-dev-storage') || '',
                dev_colour: opt.getAttribute('data-dev-colour') || '',
                dev_marks: opt.getAttribute('data-dev-marks') || '',
                evidence_photos: parseOptionPhotos(opt.getAttribute('data-evidence-photos')),
                preliminary_report: parseOptionPrelimReport(opt.getAttribute('data-preliminary-report')),
                has_preliminary_report: hasPrelim,
                is_selectable: !hasPrelim
            };
        }

        function rebuildSelectOptions(list, preserveIds) {
            const select = document.getElementById('linkedUnassignedExhibitsSelect');
            if (!select) return;

            const selectedSet = new Set((preserveIds || Array.from(select.selectedOptions).map(o => String(o.value))).map(String));
            select.innerHTML = '';

            (list || []).forEach(item => {
                const hasPrelim = hasAttachedPreliminaryReport(item);
                const isLocked = isUnassignedExhibitLocked(item);
                const linkMode = window.isLinkingSuspectToLockedExhibitMode();
                const opt = document.createElement('option');
                opt.value = String(item.id);
                const baseText = item.display_text || `${item.property_id || item.exhibit_number || ('Item #' + item.id)} — ${item.commodity_name || item.exhibit_category || 'Exhibit'}`;
                if (hasPrelim && isLocked) {
                    opt.textContent = `${baseText} [${resolveLockedOptionSuffix(item.preliminary_report)}]`;
                } else if (hasPrelim && linkMode) {
                    opt.textContent = `${baseText}${item.case_number ? ' [Case: ' + item.case_number + ' — Link Suspect]' : ' [Link Suspect]'}`;
                } else {
                    opt.textContent = baseText;
                }
                opt.disabled = isLocked;
                opt.setAttribute('data-case-number', item.case_number || '');
                opt.setAttribute('data-property-id', item.property_id || '');
                opt.setAttribute('data-exhibit-number', item.exhibit_number || '');
                opt.setAttribute('data-category', item.exhibit_category || '');
                opt.setAttribute('data-commodity-name', item.commodity_name || '');
                opt.setAttribute('data-quantity', item.seizure_quantity || '');
                opt.setAttribute('data-amount', item.amount || '');
                opt.setAttribute('data-weight', item.seizure_weight || '');
                opt.setAttribute('data-unit', item.seizure_unit || '');
                opt.setAttribute('data-packaging', item.seizure_packaging || '');
                opt.setAttribute('data-value', item.seizure_value || '');
                opt.setAttribute('data-location', item.seizure_location || '');
                opt.setAttribute('data-date', item.seizure_date || '');
                opt.setAttribute('data-officer', item.seizure_officer || '');
                opt.setAttribute('data-investigating-officer', item.investigating_officer || '');
                opt.setAttribute('data-source-unit', item.source_requesting_unit || '');
                opt.setAttribute('data-source-of-exhibits', item.source_of_exhibits || '');
                opt.setAttribute('data-reason', item.seizure_reason || '');
                opt.setAttribute('data-remark', item.exhibit_remark || '');
                opt.setAttribute('data-time', item.seizure_time || '');
                opt.setAttribute('data-concealment', item.seizure_concealment || '');
                opt.setAttribute('data-description', item.description_of_exhibits || '');
                opt.setAttribute('data-other-description', item.other_exhibit_description || '');
                opt.setAttribute('data-contents', item.contents || '');
                opt.setAttribute('data-exhibit-type', item.exhibit_type || '');
                opt.setAttribute('data-drug-classification', item.drug_classification || '');
                opt.setAttribute('data-dev-model', item.dev_model || '');
                opt.setAttribute('data-dev-serial-no', item.dev_serial_no || '');
                opt.setAttribute('data-dev-imei1', item.dev_imei1 || '');
                opt.setAttribute('data-dev-imei2', item.dev_imei2 || '');
                opt.setAttribute('data-dev-mac-address', item.dev_mac_address || '');
                opt.setAttribute('data-dev-storage', item.dev_storage || '');
                opt.setAttribute('data-dev-colour', item.dev_colour || '');
                opt.setAttribute('data-dev-marks', item.dev_marks || '');
                opt.setAttribute('data-evidence-photos', JSON.stringify(Array.isArray(item.evidence_photos) ? item.evidence_photos.filter(Boolean) : []));
                opt.setAttribute('data-preliminary-report', JSON.stringify(item.preliminary_report || null));
                opt.setAttribute('data-has-preliminary-report', hasPrelim ? '1' : '0');
                opt.setAttribute('data-is-selectable', isLocked ? '0' : '1');
                if (!isLocked && selectedSet.has(String(item.id))) {
                    opt.selected = true;
                }
                select.appendChild(opt);
            });

            const countBadge = document.getElementById('unassignedCountBadge');
            if (countBadge) {
                countBadge.textContent = `${(list || []).length} Available in Vault`;
            }

            if (typeof window.renderAvailableUnassignedExhibits === 'function') {
                window.renderAvailableUnassignedExhibits(list || []);
            }

            if (typeof jQuery !== 'undefined' && jQuery(select).hasClass('select2-hidden-accessible')) {
                jQuery(select).trigger('change.select2');
            }
        }

        function setBlockVal(block, selector, val) {
            if (val === undefined || val === null || val === '') return;
            block.querySelectorAll(selector).forEach(el => {
                el.value = val;
            });
        }

        function hasElectronicDeviceSpecs(item) {
            if (!item || typeof item !== 'object') return false;
            return Boolean(
                item.dev_model || item.model ||
                item.dev_serial_no || item.serial_number ||
                item.dev_imei1 || item.imei1 ||
                item.dev_imei2 || item.imei2 ||
                item.dev_mac_address || item.mac_address ||
                item.dev_storage || item.storage_capacity ||
                item.dev_colour || item.colour ||
                item.dev_marks || item.identifying_marks
            );
        }

        function normalizeManifestCategory(cat, item) {
            const raw = String(cat || '').trim();
            const low = raw.toLowerCase();
            if (low === 'drug' || low === 'drugs' || low === 'narcotics' || low === 'narcotic') return 'Drugs';
            if (low === 'weapon' || low === 'weapons' || low === 'firearm' || low === 'firearms') return 'Weapons';
            if (low === 'money' || low === 'currency' || low === 'cash') return 'Money';
            if (low === 'ammo' || low === 'ammunition') return 'Ammunition';
            if (low === 'device' || low === 'devices' || low === 'electronic device' || low === 'electronic devices' || low === 'electronics') return 'Electronic Devices';
            if (low === 'vehicle' || low === 'vehicles') return 'Vehicles';
            if (low === 'other' || low === 'other exhibits' || low === 'other contraband') {
                if (hasElectronicDeviceSpecs(item)) return 'Electronic Devices';
                return 'Other Exhibits';
            }

            if (!low.includes('weapon') && !low.includes('firearm') && !low.includes('ammo') && !low.includes('drug') && !low.includes('narcotic') && !low.includes('vehicle')) {
                if (hasElectronicDeviceSpecs(item)) {
                    return 'Electronic Devices';
                }
                if (low && ['electronic', 'device', 'digital', 'storage media', 'communication hardware', 'digital documents', 'phone', 'mobile', 'laptop', 'computer', 'hardware'].some(kw => low.includes(kw))) {
                    return 'Electronic Devices';
                }
                if (!raw) {
                    const nameLow = String(item?.commodity_name || item?.seizure_name || item?.name || '').toLowerCase();
                    if (['phone', 'mobile', 'iphone', 'samsung', 'laptop', 'macbook', 'ipad', 'hard drive', 'hdd', 'ssd', 'usb', 'flash drive', 'memory card', 'sim card', 'router', 'drone', 'camera', 'desktop', 'smartwatch', 'computer'].some(kw => nameLow.includes(kw))) {
                        return 'Electronic Devices';
                    }
                }
            }

            return raw;
        }

        function setCommodityInputs(block, cat, name) {
            if (!name) return;
            const catLower = String(cat || '').toLowerCase();
            if (catLower.includes('drug') || catLower === 'narcotic' || catLower === 'narcotics') {
                setBlockVal(block, '#newSeizureNameDrug', name);
                setBlockVal(block, '#drugSearchInput', name);
                setBlockVal(block, '#drugSelect', name);
            } else if (catLower.includes('weapon') || catLower.includes('firearm')) {
                setBlockVal(block, '#newSeizureNameWeapon', name);
                setBlockVal(block, '#weaponSearchInput', name);
                setBlockVal(block, '#weaponSelect', name);
            } else if (catLower.includes('money') || catLower.includes('currency') || catLower.includes('cash')) {
                setBlockVal(block, '#newSeizureNameCurrency', name);
                setBlockVal(block, '#currencySearchInput', name);
            } else if (catLower.includes('ammo')) {
                setBlockVal(block, '#newSeizureNameAmmo', name);
                setBlockVal(block, '#ammoSearchInput', name);
            } else if (catLower.includes('device') || catLower.includes('electronic')) {
                setBlockVal(block, '#newSeizureNameDevice', name);
                setBlockVal(block, '#deviceSearchInput', name);
            } else {
                setBlockVal(block, '#newSeizureName', name);
            }

            // Ensure visible fallback #newSeizureName also receives value if textWrapper is shown
            const textWrap = block.querySelector('#commodityTextWrapper');
            if (textWrap && !textWrap.classList.contains('hidden')) {
                setBlockVal(block, '#newSeizureName', name);
            }
        }

        function fillSeizureOfficersRows(block, rawOfficerStr) {
            const container = block.querySelector('#seizureOfficersContainer');
            const raw = String(rawOfficerStr || '').trim();

            // Always also fill legacy officer inputs if present
            if (raw) {
                setBlockVal(block, '#newSeizureOfficer', raw);
                setBlockVal(block, '.officerSearchInput', raw);
            }

            if (!container) return;
            const rows = Array.from(container.querySelectorAll('.seizure-officer-row'));
            if (rows.length === 0) return;

            // Remove extra rows beyond row 0 before repopulating
            for (let i = rows.length - 1; i > 0; i--) {
                rows[i].remove();
            }
            const baseRow = container.querySelector('.seizure-officer-row');
            if (!baseRow) return;
            const baseNameInp = baseRow.querySelector('.seizure-officer-name');
            const baseIdInp = baseRow.querySelector('.seizure-officer-service-id');
            if (baseNameInp) baseNameInp.value = '';
            if (baseIdInp) baseIdInp.value = '';

            if (!raw) return;

            const parts = raw.split(',').map(s => s.trim()).filter(Boolean);
            if (parts.length === 0) return;

            parts.forEach((entry, idx) => {
                let targetRow = idx === 0 ? baseRow : null;
                if (!targetRow) {
                    const clone = baseRow.cloneNode(true);
                    const rmBtn = clone.querySelector('.remove-officer-btn');
                    if (rmBtn) rmBtn.classList.remove('hidden');
                    container.appendChild(clone);
                    targetRow = clone;
                }
                const match = entry.match(/^(.+?)\s*\(([^)]+)\)\s*$/);
                const officerName = match ? match[1].trim() : entry;
                const serviceId = match ? match[2].trim() : '';
                const nInp = targetRow.querySelector('.seizure-officer-name');
                const sInp = targetRow.querySelector('.seizure-officer-service-id');
                if (nInp) nInp.value = officerName;
                if (sInp) sInp.value = serviceId;
            });
        }

        function resetSingleBlockInputs(block) {
            if (!block) return;
            delete block.dataset.linkedExhibitId;
            delete block.dataset.propertyId;
            block.querySelectorAll('input[type="text"], input[type="number"], input[type="date"], input[type="time"], textarea').forEach(inp => {
                inp.value = '';
            });
            block.querySelectorAll('input[type="hidden"]').forEach(inp => {
                inp.value = '';
            });
            block.querySelectorAll('select').forEach(sel => {
                sel.selectedIndex = 0;
                if (sel.classList.contains('seizure-associated-person-select')) {
                    sel.removeAttribute('data-user-selected');
                    sel.disabled = false;
                    sel.removeAttribute('readonly');
                }
            });
            [
                '#drugSelectWrapper', '#weaponSelectWrapper', '#currencySelectWrapper',
                '#ammoSelectWrapper', '#deviceSelectWrapper', '#deviceFieldsWrapper',
                '#vehicleFieldsWrapper', '#weaponFieldsWrapper', '#ammoFieldsWrapper',
                '#otherExhibitDescriptionWrapper', '.other-exhibit-description-wrapper'
            ].forEach(sel => {
                const wrap = block.querySelector(sel);
                if (wrap) wrap.classList.add('hidden');
            });
            const commText = block.querySelector('#commodityTextWrapper');
            if (commText) commText.classList.remove('hidden');
        }

        function applyEditableOwnershipOnBlock(block, data) {
            if (!block) return;
            block.querySelectorAll('.seizure-ownership-default').forEach(el => el.classList.remove('hidden'));
            block.querySelectorAll('.seizure-ownership-unassigned').forEach(el => el.classList.add('hidden'));

            const selectEl = block.querySelector('.seizure-associated-person-select');
            if (!selectEl) return;

            // Never lock or disable Suspect / Ownership
            selectEl.disabled = false;
            selectEl.removeAttribute('readonly');

            let unknownOpt = Array.from(selectEl.options).find(o => o.value.toLowerCase() === 'unknown');
            if (!unknownOpt) {
                unknownOpt = document.createElement('option');
                unknownOpt.value = 'Unknown';
                unknownOpt.textContent = 'Unknown';
                selectEl.insertBefore(unknownOpt, selectEl.firstChild);
            }

            if (selectEl.getAttribute('data-user-selected') === 'true') {
                return;
            }

            const rawOwner = String(data?.associated_person ?? data?.resolved_owner ?? data?.suspect_name ?? '').trim();
            const rawLower = rawOwner.toLowerCase();
            const isUnknownOrPlaceholder = !rawOwner
                || rawLower === 'unknown'
                || rawLower === 'all suspects'
                || rawLower === 'primary suspect'
                || rawLower === 'none'
                || rawLower === 'n/a'
                || rawLower.includes('unassigned')
                || rawLower.includes('awaiting')
                || rawLower.includes('no suspect');

            if (isUnknownOrPlaceholder) {
                selectEl.value = 'Unknown';
            } else {
                const matchOpt = Array.from(selectEl.options).find(o => o.value.toLowerCase().trim() === rawLower);
                if (matchOpt) {
                    selectEl.value = matchOpt.value;
                } else {
                    const newOpt = document.createElement('option');
                    newOpt.value = rawOwner;
                    newOpt.textContent = rawOwner;
                    selectEl.appendChild(newOpt);
                    selectEl.value = rawOwner;
                }
            }
        }

        function closeAllSeizurePanels(root) {
            const scope = root || document;
            [
                '#exhibitListPanel', '#drugListPanel', '#weaponListPanel',
                '#currencyListPanel', '#ammoListPanel', '#deviceListPanel',
                '#pkgListPanel', '#unitListPanel', '#vehTypeListPanel'
            ].forEach(sel => {
                scope.querySelectorAll(sel).forEach(panel => panel.classList.add('hidden'));
            });
        }

        function installOpenSeizurePanelGuard() {
            if (typeof window.openSeizurePanel === 'function' && !window.openSeizurePanel._unassignedGuarded) {
                const origOpenSeizurePanel = window.openSeizurePanel;
                window.openSeizurePanel = function(el, panelSelector) {
                    if (window._suppressAutoOpenPanels) return;
                    return origOpenSeizurePanel.apply(this, arguments);
                };
                window.openSeizurePanel._unassignedGuarded = true;
            }
        }
        installOpenSeizurePanelGuard();
        document.addEventListener('DOMContentLoaded', installOpenSeizurePanelGuard);

        function autoFillSingleManifestBlock(block, data) {
            if (!block || !data) return;

            installOpenSeizurePanelGuard();
            window._suppressAutoOpenPanels = true;
            if (window._suppressAutoOpenPanelsTimer) {
                clearTimeout(window._suppressAutoOpenPanelsTimer);
            }

            resetSingleBlockInputs(block);

            if (data.id || data.linked_exhibit_id) {
                block.dataset.linkedExhibitId = String(data.linked_exhibit_id || data.id);
            }
            if (data.property_id) {
                block.dataset.propertyId = String(data.property_id);
            }

            const rawCat = data.exhibit_category || data.category || '';
            const normalizedCat = normalizeManifestCategory(rawCat, data);
            if (normalizedCat) {
                setBlockVal(block, '#newSeizureCategory', normalizedCat);
                setBlockVal(block, '#exhibitSearchInput', normalizedCat);
                if (typeof window.updateCommodityField === 'function') {
                    try {
                        window.updateCommodityField(normalizedCat, block);
                    } catch (e) {}
                } else if (typeof window.selectExhibitCategory === 'function') {
                    try {
                        window.selectExhibitCategory(normalizedCat, { target: block });
                    } catch (e) {}
                }
            }

            if (normalizedCat === 'Electronic Devices' || hasElectronicDeviceSpecs(data)) {
                const devWrap = block.querySelector('#deviceSelectWrapper');
                const devFieldsWrap = block.querySelector('#deviceFieldsWrapper');
                const commTextWrap = block.querySelector('#commodityTextWrapper');
                if (devWrap) devWrap.classList.remove('hidden');
                if (devFieldsWrap) devFieldsWrap.classList.remove('hidden');
                if (normalizedCat === 'Electronic Devices' && commTextWrap) {
                    commTextWrap.classList.add('hidden');
                }
            }

            if (data.exhibit_type) {
                setBlockVal(block, '.exhibit-type-select', data.exhibit_type);
            } else if (normalizedCat) {
                setBlockVal(block, '.exhibit-type-select', normalizedCat === 'Drugs' ? 'Drug Exhibit' : 'Non-Drug Exhibit');
            }
            if (data.drug_classification) {
                setBlockVal(block, '.drug-classification-select', data.drug_classification);
            } else if (normalizedCat && normalizedCat !== 'Drugs') {
                setBlockVal(block, '.drug-classification-select', 'N/A');
            }

            const name = data.commodity_name || data.seizure_name || data.name || data.seizure_name_drug || '';
            setCommodityInputs(block, normalizedCat || rawCat, name);
            closeAllSeizurePanels(block);
            window._suppressAutoOpenPanelsTimer = setTimeout(() => {
                closeAllSeizurePanels(block);
                window._suppressAutoOpenPanels = false;
            }, 150);

            setBlockVal(block, '#newSeizureQuantity', data.seizure_quantity ?? data.quantity ?? data.amount);
            setBlockVal(block, '#newSeizureAmount', data.amount ?? data.seizure_quantity);
            setBlockVal(block, '#newSeizureWeight', data.seizure_weight ?? data.weight);
            setBlockVal(block, '#newSeizureUnit', data.seizure_unit ?? data.unit);
            setBlockVal(block, '#unitSearchInput', data.seizure_unit ?? data.unit);
            setBlockVal(block, '#newSeizurePackaging', data.seizure_packaging ?? data.packaging);
            setBlockVal(block, '#pkgSearchInput', data.seizure_packaging ?? data.packaging);
            setBlockVal(block, '#newSeizureValue', data.seizure_value ?? data.value ?? data.estimated_street_value);
            setBlockVal(block, '#newSeizureConcealment', data.seizure_concealment ?? data.concealment);
            setBlockVal(block, '#newSeizureDate', data.seizure_date ?? data.date);
            setBlockVal(block, '#newSeizureTime', data.seizure_time ?? data.time);
            setBlockVal(block, '#newSeizureLocation', data.seizure_location ?? data.location);
            setBlockVal(block, '#newSeizureReason', data.seizure_reason ?? data.reason);
            setBlockVal(block, '#newSeizureSourceOfExhibits', data.source_of_exhibits ?? data.source);
            setBlockVal(block, '[name="source_of_exhibits"]', data.source_of_exhibits ?? data.source);
            if (data.source_of_exhibits ?? data.source) {
                block.dataset.preservedSourceOfExhibits = data.source_of_exhibits ?? data.source;
            }
            setBlockVal(block, '#newSeizureInvestigatingOfficer', data.investigating_officer ?? data.investigation_officer);
            setBlockVal(block, '[name="investigating_officer"]', data.investigating_officer ?? data.investigation_officer);
            setBlockVal(block, '#newSeizureSourceRequestingUnit', data.source_requesting_unit ?? data.requesting_unit);
            setBlockVal(block, '[name="source_requesting_unit"]', data.source_requesting_unit ?? data.requesting_unit);
            setBlockVal(block, '#newSeizureRemark', data.exhibit_remark ?? data.remark ?? data.remarks);
            setBlockVal(block, '#newSeizureExhibitRemark', data.exhibit_remark ?? data.remark ?? data.remarks);
            setBlockVal(block, '.seizure-remark-input', data.exhibit_remark ?? data.remark ?? data.remarks);
            setBlockVal(block, '[name="exhibit_remark"]', data.exhibit_remark ?? data.remark ?? data.remarks);

            fillSeizureOfficersRows(block, data.seizure_officer || data.seizing_officer || data.officer_name || '');

            const contents = data.contents || data.seizure_contents || '';
            if (contents) {
                setBlockVal(block, '#newSeizureContents', contents);
                setBlockVal(block, '.seizure-contents-input', contents);
            }

            const otherDesc = data.other_exhibit_description || data.other_description || '';
            if (otherDesc) {
                setBlockVal(block, '#newOtherExhibitDescription', otherDesc);
                setBlockVal(block, '.other-exhibit-description-input', otherDesc);
                const otherWrap = block.querySelector('#otherExhibitDescriptionWrapper, .other-exhibit-description-wrapper');
                if (otherWrap) otherWrap.classList.remove('hidden');
            }

            // Device, Weapon, Vehicle, and Ammunition specifications
            setBlockVal(block, '#newDevModel', data.dev_model ?? data.model);
            setBlockVal(block, '#newDevSerialNo', data.dev_serial_no ?? data.serial_number);
            setBlockVal(block, '#newDevImei1', data.dev_imei1 ?? data.imei1);
            setBlockVal(block, '#newDevImei2', data.dev_imei2 ?? data.imei2);
            setBlockVal(block, '#newDevMacAddress', data.dev_mac_address ?? data.mac_address);
            setBlockVal(block, '#newDevStorage', data.dev_storage ?? data.storage_capacity);
            setBlockVal(block, '#newDevColour', data.dev_colour ?? data.colour);
            setBlockVal(block, '#newDevMarks', data.dev_marks ?? data.identifying_marks);

            setBlockVal(block, '#wpnModel', data.wpn_model ?? data.weapon_model ?? data.model);
            setBlockVal(block, '#wpnSerialNo', data.wpn_serial_no ?? data.weapon_serial_no ?? data.serial_number);
            setBlockVal(block, '#wpnCalibre', data.wpn_calibre ?? data.caliber);
            setBlockVal(block, '#wpnColour', data.wpn_colour ?? data.colour);
            setBlockVal(block, '#wpnSize', data.wpn_size ?? data.size);
            setBlockVal(block, '#wpnMarks', data.wpn_marks ?? data.identifying_marks);
            setBlockVal(block, '#wpnLocationFound', data.wpn_location_found ?? data.location_found);
            setBlockVal(block, '#wpnLoadedStatus', data.wpn_loaded_status ?? data.loaded_status);
            setBlockVal(block, '#wpnCondition', data.wpn_condition ?? data.condition);

            setBlockVal(block, '#vehMake', data.veh_make ?? data.make);
            setBlockVal(block, '#vehModel', data.veh_model ?? data.model);
            setBlockVal(block, '#vehType', data.veh_type ?? data.type);
            setBlockVal(block, '#vehTypeSearchInput', data.veh_type ?? data.type);
            setBlockVal(block, '#vehYear', data.veh_year ?? data.year);
            setBlockVal(block, '#vehColour', data.veh_colour ?? data.colour);
            setBlockVal(block, '#vehRegNo', data.veh_reg_no ?? data.registration_number);
            setBlockVal(block, '#vehVIN', data.veh_vin ?? data.vin);
            setBlockVal(block, '#vehEngineNo', data.veh_engine_no ?? data.engine_number);
            setBlockVal(block, '#vehCountryReg', data.veh_country_reg ?? data.country_registration);
            setBlockVal(block, '#vehOwner', data.veh_owner ?? data.owner);
            setBlockVal(block, '#vehDriver', data.veh_driver ?? data.driver);
            setBlockVal(block, '#vehOwnerContact', data.veh_owner_contact ?? data.owner_contact);
            setBlockVal(block, '#vehDocsAvailable', data.veh_docs_available ?? data.documents_available);
            setBlockVal(block, '#vehDocRef', data.veh_doc_ref ?? data.document_reference);
            setBlockVal(block, '#vehCondition', data.veh_condition ?? data.condition);
            setBlockVal(block, '#vehFuel', data.veh_fuel ?? data.fuel_level);
            setBlockVal(block, '#vehKeysRecv', data.veh_keys_recv ?? data.keys_received);
            setBlockVal(block, '#vehKeyCount', data.veh_key_count ?? data.key_count);
            setBlockVal(block, '#vehDamage', data.veh_damage ?? data.damages_visible);
            setBlockVal(block, '#vehMods', data.veh_mods ?? data.modifications);
            setBlockVal(block, '#vehFeatures', data.veh_features ?? data.features);

            setBlockVal(block, '#ammoCartridgeAppearance', data.ammo_cartridge_appearance ?? data.cartridge_appearance);
            setBlockVal(block, '#ammoProjectileType', data.ammo_projectile_type ?? data.projectile_type);
            setBlockVal(block, '#ammoCaseMaterial', data.ammo_case_material ?? data.case_material);
            setBlockVal(block, '#ammoColourMarkings', data.ammo_colour_markings ?? data.colour_markings);
            setBlockVal(block, '#ammoCondition', data.ammo_condition ?? data.condition);
            setBlockVal(block, '#ammoOtherCondition', data.ammo_other_condition ?? data.other_condition);
            setBlockVal(block, '#ammoPackagingType', data.ammo_packaging_type ?? data.packaging_type);
            setBlockVal(block, '#ammoSealCondition', data.ammo_seal_condition ?? data.seal_condition);
            setBlockVal(block, '#ammoPackageReferenceNumber', data.ammo_package_reference_number ?? data.package_reference_number);
            setBlockVal(block, '#ammoConditionWhenReceived', data.ammo_condition_when_received ?? data.condition_when_received);

            applyEditableOwnershipOnBlock(block, data);
        }

        function resetFirstManifestBlockClean() {
            const container = document.getElementById('seizuresListContainer');
            if (!container) return;

            let blocks = container.querySelectorAll('.seizure-block');
            for (let i = blocks.length - 1; i > 0; i--) {
                blocks[i].remove();
            }
            const first = container.querySelector('.seizure-block');
            if (first) {
                resetSingleBlockInputs(first);
                const exhibitOptions = Array.from(document.querySelectorAll('#exhibitList .exhibit-item'));
                if (exhibitOptions.length === 1 && exhibitOptions[0].textContent.includes('Electronic Devices')) {
                    const catIn = first.querySelector('#newSeizureCategory');
                    const catDis = first.querySelector('#exhibitSearchInput');
                    if (catIn) catIn.value = 'Electronic Devices';
                    if (catDis) catDis.value = 'Electronic Devices';
                    if (typeof window.updateCommodityField === 'function') {
                        window.updateCommodityField('Electronic Devices', first);
                    }
                }
            }

            const countInput = document.getElementById('seizureCountInput');
            if (countInput) countInput.value = 1;
            if (typeof window.updateSeizureBlockNumbers === 'function') window.updateSeizureBlockNumbers();
            if (typeof window.syncSeizureOwnershipBoxes === 'function') window.syncSeizureOwnershipBoxes();
        }

        function currentManifestHasManualData() {
            if (window._manifestAutoFilledFromLinked) return false;
            const container = document.getElementById('seizuresListContainer');
            if (!container) return false;
            const blocks = container.querySelectorAll('.seizure-block');
            return Array.from(blocks).some(b => {
                if (b.dataset && b.dataset.linkedExhibitId) {
                    return true;
                }
                const name = b.querySelector('#newSeizureName')?.value?.trim()
                    || b.querySelector('#drugSearchInput')?.value?.trim()
                    || b.querySelector('#newSeizureNameDrug')?.value?.trim()
                    || b.querySelector('#weaponSearchInput')?.value?.trim()
                    || b.querySelector('#newSeizureNameWeapon')?.value?.trim()
                    || b.querySelector('#currencySearchInput')?.value?.trim()
                    || b.querySelector('#newSeizureNameCurrency')?.value?.trim()
                    || b.querySelector('#ammoSearchInput')?.value?.trim()
                    || b.querySelector('#newSeizureNameAmmo')?.value?.trim()
                    || b.querySelector('#deviceSearchInput')?.value?.trim()
                    || b.querySelector('#newSeizureNameDevice')?.value?.trim();
                const details = b.querySelector('#newDevModel')?.value?.trim()
                    || b.querySelector('#newDevSerialNo')?.value?.trim()
                    || b.querySelector('#newDevImei1')?.value?.trim()
                    || b.querySelector('#newSeizureLocation')?.value?.trim()
                    || b.querySelector('#newSeizureWeight')?.value?.trim()
                    || b.querySelector('#newSeizureValue')?.value?.trim()
                    || b.querySelector('#newSeizureReason')?.value?.trim()
                    || b.querySelector('#newSeizureSourceOfExhibits')?.value?.trim()
                    || b.querySelector('#newSeizureInvestigatingOfficer')?.value?.trim()
                    || b.querySelector('#newSeizureSourceRequestingUnit')?.value?.trim()
                    || b.querySelector('.seizure-officer-name')?.value?.trim();
                const suspectSelect = b.querySelector('.seizure-associated-person-select');
                const customOwner = suspectSelect && suspectSelect.getAttribute('data-user-selected') === 'true';
                return Boolean(name || details || customOwner);
            });
        }

        function resolveTargetPhotosArray() {
            try {
                if (typeof seizedExhibitDataUrls !== 'undefined' && Array.isArray(seizedExhibitDataUrls)) {
                    return seizedExhibitDataUrls;
                }
            } catch (e) {
                // Ignore TDZ ReferenceError when parent script declares `let seizedExhibitDataUrls` later in the document
            }
            return Array.isArray(window.seizedExhibitDataUrls) ? window.seizedExhibitDataUrls : null;
        }

        function syncLinkedExhibitPhotos(collectedPhotos) {
            const nextList = Array.isArray(collectedPhotos) ? collectedPhotos.filter(Boolean) : [];
            const targetPhotosArr = resolveTargetPhotosArray();
            const renderFn = (typeof renderSeizurePhotosGrid === 'function')
                ? renderSeizurePhotosGrid
                : (typeof window.renderSeizurePhotosGrid === 'function' ? window.renderSeizurePhotosGrid : null);

            if (!targetPhotosArr || !renderFn) {
                window._pendingLinkedExhibitPhotos = nextList;
                return;
            }

            try {
                const prevAuto = new Set(Array.isArray(window._autoFilledPhotoUrls) ? window._autoFilledPhotoUrls : []);
                for (let i = targetPhotosArr.length - 1; i >= 0; i--) {
                    if (prevAuto.has(targetPhotosArr[i])) {
                        targetPhotosArr.splice(i, 1);
                    }
                }
                nextList.forEach(p => {
                    if (p && !targetPhotosArr.includes(p)) targetPhotosArr.push(p);
                });
                window._autoFilledPhotoUrls = nextList;
                window._pendingLinkedExhibitPhotos = null;
                renderFn();
            } catch (e) {
                window._pendingLinkedExhibitPhotos = nextList;
            }
        }

        function flushPendingLinkedExhibitPhotos() {
            if (Array.isArray(window._pendingLinkedExhibitPhotos)) {
                syncLinkedExhibitPhotos(window._pendingLinkedExhibitPhotos);
            }
        }

        window.autoFillManifestFromLinkedExhibits = function() {
            const select = document.getElementById('linkedUnassignedExhibitsSelect');
            const statusPill = document.getElementById('unassignedAutoFillStatus');
            const container = document.getElementById('seizuresListContainer');
            if (!select || !container) return;

            const selectedOptions = Array.from(select.selectedOptions || []);
            if (selectedOptions.length === 0) {
                container.querySelectorAll('.seizure-block').forEach(b => {
                    delete b.dataset.linkedExhibitId;
                    delete b.dataset.propertyId;
                    delete b.dataset.autoFilledFromLinked;
                });
                if (statusPill) {
                    statusPill.classList.add('hidden');
                    statusPill.textContent = '';
                }
                if (window._manifestAutoFilledFromLinked) {
                    syncLinkedExhibitPhotos([]);
                    resetFirstManifestBlockClean();
                    window._manifestAutoFilledFromLinked = false;
                }
                if (typeof window.toggleSeizuresContainer === 'function') {
                    window.toggleSeizuresContainer();
                }
                return;
            }

            const selectedItems = selectedOptions.map(opt => {
                return window.unassignedExhibitsCatalog[String(opt.value)] || readFallbackItemFromOption(opt);
            }).filter(Boolean);

            if (selectedItems.length === 0) return;

            const hasSeizuresChk = document.getElementById('hasSeizuresCheckbox');
            if (hasSeizuresChk && !hasSeizuresChk.checked) {
                hasSeizuresChk.checked = true;
                if (typeof window.toggleSeizuresContainer === 'function') {
                    window.toggleSeizuresContainer();
                }
            }

            if (typeof window.getPristineSeizureBlockTemplate === 'function') {
                window.getPristineSeizureBlockTemplate();
            }

            const manifestDescEl = document.getElementById('manifestDescriptionOfExhibits');
            const savedManifestDesc = selectedItems.map(i => i.description_of_exhibits).find(Boolean);
            if (manifestDescEl && savedManifestDesc && (!manifestDescEl.value || manifestDescEl.value.trim() === '' || window._manifestAutoFilledFromLinked)) {
                manifestDescEl.value = savedManifestDesc;
            }

            const countInput = document.getElementById('seizureCountInput');
            if (countInput) {
                countInput.value = selectedItems.length;
            }

            let currentBlocks = container.querySelectorAll('.seizure-block');
            while (currentBlocks.length < selectedItems.length) {
                if (typeof window.addSeizureBlock === 'function') {
                    window.addSeizureBlock(false);
                } else {
                    break;
                }
                currentBlocks = container.querySelectorAll('.seizure-block');
            }
            while (currentBlocks.length > selectedItems.length) {
                currentBlocks[currentBlocks.length - 1].remove();
                currentBlocks = container.querySelectorAll('.seizure-block');
            }

            currentBlocks.forEach((block, idx) => {
                if (selectedItems[idx]) {
                    autoFillSingleManifestBlock(block, selectedItems[idx]);
                }
            });

            const collectedPhotos = [];
            selectedItems.forEach(item => {
                if (Array.isArray(item.evidence_photos)) {
                    item.evidence_photos.forEach(p => {
                        if (p && !collectedPhotos.includes(p)) collectedPhotos.push(p);
                    });
                }
            });
            syncLinkedExhibitPhotos(collectedPhotos);

            if (typeof window.updateSeizureBlockNumbers === 'function') {
                window.updateSeizureBlockNumbers();
            }
            if (typeof window.syncSuspectDropdownsForExhibits === 'function') {
                window.syncSuspectDropdownsForExhibits();
            }
            if (typeof window.syncSection4ExhibitsToLabSection === 'function') {
                window.syncSection4ExhibitsToLabSection();
            }
            if (typeof window.evaluateLabExaminationVisibility === 'function') {
                window.evaluateLabExaminationVisibility();
            }
            if (typeof window.syncSeizureOwnershipBoxes === 'function') {
                window.syncSeizureOwnershipBoxes();
            }

            // Re-apply editable 'Unknown' ownership AFTER syncSuspectDropdownsForExhibits / syncSeizureOwnershipBoxes
            container.querySelectorAll('.seizure-block').forEach((block, idx) => {
                if (selectedItems[idx]) {
                    applyEditableOwnershipOnBlock(block, selectedItems[idx]);
                }
            });

            if (typeof window.saveDocketFormDraft === 'function') {
                window.saveDocketFormDraft();
            }

            window._manifestAutoFilledFromLinked = true;

            if (statusPill) {
                const sourceMeta = selectedItems[0]?.entered_by_unit || selectedItems[0]?.entered_by_name || 'Vault';
                statusPill.textContent = `Auto-filled ${selectedItems.length} item(s) from ${sourceMeta}`;
                statusPill.classList.remove('hidden');
            }
        };

        function pickLatestExhibitBatchToAutoSelect(list) {
            if (!Array.isArray(list) || list.length === 0) return [];
            const selectableList = list.filter(i => !isUnassignedExhibitLocked(i));
            if (selectableList.length === 0) return [];

            const card = document.getElementById('unassignedExhibitsSelectorCard');
            const currentUserId = card?.dataset.currentUserId ? String(card.dataset.currentUserId) : '';

            const otherUserItems = currentUserId
                ? selectableList.filter(i => i.entered_by_user_id && String(i.entered_by_user_id) !== currentUserId)
                : [];
            const pool = otherUserItems.length > 0 ? otherUserItems : selectableList;
            const leadItem = pool[0];
            if (!leadItem) return [];

            if (leadItem.single_suspect_case_id) {
                return selectableList
                    .filter(i => String(i.single_suspect_case_id) === String(leadItem.single_suspect_case_id))
                    .slice()
                    .reverse()
                    .map(i => String(i.id));
            }
            return [String(leadItem.id)];
        }

        function applyAutoSelectionIfEligible() {
            const select = document.getElementById('linkedUnassignedExhibitsSelect');
            if (!select) return;
            if (typeof window.isSections1To3OptionalMode === 'function' && !window.isSections1To3OptionalMode()) {
                return;
            }

            const alreadySelected = Array.from(select.selectedOptions || []).map(o => String(o.value));
            if (alreadySelected.length > 0) {
                renderLinkedExhibitsPreview();
                window.autoFillManifestFromLinkedExhibits();
                return;
            }

            if (window._unassignedSelectionManuallyChanged || currentManifestHasManualData()) {
                return;
            }

            const idsToSelect = pickLatestExhibitBatchToAutoSelect(window.unassignedExhibitsListData);
            if (idsToSelect.length === 0) return;

            Array.from(select.options).forEach(opt => {
                opt.selected = !opt.disabled && idsToSelect.includes(String(opt.value));
            });

            window._unassignedSelectionAutoAssigned = true;
            if (typeof jQuery !== 'undefined' && jQuery(select).hasClass('select2-hidden-accessible')) {
                jQuery(select).trigger('change.select2');
            }
            renderLinkedExhibitsPreview();
            window.autoFillManifestFromLinkedExhibits();
        }

        window.refreshUnassignedExhibitsCatalog = async function() {
            try {
                const card = document.getElementById('unassignedExhibitsSelectorCard');
                const viewerUnit = card ? String(card.getAttribute('data-viewer-unit') || '').trim() : '';
                const url = viewerUnit
                    ? '/api/exhibits/unassigned?unit=' + encodeURIComponent(viewerUnit)
                    : '/api/exhibits/unassigned';
                const resp = await fetch(url, {
                    headers: { 'Accept': 'application/json' }
                });
                if (!resp.ok) return;
                const data = await resp.json();
                if (!data || !data.success || !Array.isArray(data.exhibits)) return;

                const select = document.getElementById('linkedUnassignedExhibitsSelect');
                const preserveIds = select ? Array.from(select.selectedOptions || []).map(o => String(o.value)) : [];
                indexCatalog(data.exhibits);
                rebuildSelectOptions(data.exhibits, preserveIds);
                applyAutoSelectionIfEligible();
            } catch (e) {}
        };
        window.refreshUnassignedExhibitsSelector = window.refreshUnassignedExhibitsCatalog;
        window.afterCaseSavedRefreshUnassignedSelector = function() {
            const chk = document.getElementById('linkSuspectToLockedExhibitCheckbox');
            if (chk && chk.checked) {
                chk.checked = false;
            }
            const badge = document.getElementById('linkSuspectToLockedExhibitBadge');
            if (badge) {
                badge.classList.add('hidden');
            }
            const select = document.getElementById('linkedUnassignedExhibitsSelect');
            if (select) {
                Array.from(select.options || []).forEach(opt => {
                    opt.selected = false;
                });
                if (typeof jQuery !== 'undefined' && jQuery(select).hasClass('select2-hidden-accessible')) {
                    jQuery(select).val(null).trigger('change');
                }
            }
            const caseRefInput = document.getElementById('labCaseReferenceInput');
            if (caseRefInput && caseRefInput.dataset.reusedExistingCaseRef) {
                delete caseRefInput.dataset.reusedExistingCaseRef;
                if (window._originalGeneratedCaseRef) {
                    caseRefInput.value = window._originalGeneratedCaseRef;
                }
            }
            window._unassignedSelectionManuallyChanged = false;
            renderLinkedExhibitsPreview();
            if (typeof window.refreshUnassignedExhibitsCatalog === 'function') {
                return window.refreshUnassignedExhibitsCatalog();
            }
        };

        window.linkUnassignedExhibitById = function(exhibitId) {
            const targetId = String(exhibitId || '').trim();
            const select = document.getElementById('linkedUnassignedExhibitsSelect');
            if (!targetId || !select) return;

            const catalogItem = (window.unassignedExhibitsCatalog || {})[targetId];
            const opt = Array.from(select.options || []).find(o => String(o.value) === targetId);
            if (isUnassignedExhibitLocked(catalogItem) || (!window.isLinkingSuspectToLockedExhibitMode() && opt?.disabled)) {
                alert('This exhibit already has an attached Preliminary Exhibit Analysis Report PDF and cannot be selected again.');
                return;
            }

            if (!window.isLinkingSuspectToLockedExhibitMode()) {
                const noSuspectToggle = document.getElementById('noSuspectToggleCheckbox');
                if (noSuspectToggle && !noSuspectToggle.checked) {
                    noSuspectToggle.checked = true;
                    if (typeof window.toggleSections1To3Optional === 'function') {
                        window.toggleSections1To3Optional(true);
                    } else {
                        noSuspectToggle.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
                const noSuspectChk = document.getElementById('seizureWithoutSuspectCheckbox');
                if (noSuspectChk && !noSuspectChk.checked) {
                    noSuspectChk.checked = true;
                    if (typeof window.toggleSeizureWithoutSuspectMode === 'function') {
                        window.toggleSeizureWithoutSuspectMode();
                    } else {
                        noSuspectChk.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            }

            if (opt) {
                opt.selected = true;
                window._unassignedSelectionManuallyChanged = true;
                window._unassignedSelectionAutoAssigned = false;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                if (typeof jQuery !== 'undefined' && jQuery(select).hasClass('select2-hidden-accessible')) {
                    jQuery(select).trigger('change.select2');
                }
                const card = document.getElementById('unassignedExhibitsSelectorCard');
                if (card && typeof card.scrollIntoView === 'function') {
                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        };

        window.activateLinkExistingExhibitsForNoSuspect = function(isActive) {
            const card = document.getElementById('unassignedExhibitsSelectorCard');
            const badge = document.getElementById('unassignedSelectorActiveBadge');
            const helpText = document.getElementById('unassignedSelectorHelpText');
            const select = document.getElementById('linkedUnassignedExhibitsSelect');

            if (card) {
                card.classList.toggle('ring-2', Boolean(isActive));
                card.classList.toggle('ring-amber-400', Boolean(isActive));
                card.classList.toggle('border-amber-400', Boolean(isActive));
            }
            if (badge) {
                badge.classList.toggle('hidden', !isActive);
            }
            if (helpText) {
                helpText.textContent = isActive
                    ? 'Link Existing Seized Exhibit(s) is active. Select or review exhibit(s) saved without a suspect to auto-fill Section 4 Seized Commodities & Contraband Manifest.'
                    : 'Select exhibit(s) previously logged without a suspect to auto-fill Section 4 Seized Commodities & Contraband Manifest or link to a suspect.';
            }

            if (isActive) {
                window._unassignedSelectionManuallyChanged = false;
                applyAutoSelectionIfEligible();
                if (typeof window.refreshUnassignedExhibitsCatalog === 'function') {
                    window.refreshUnassignedExhibitsCatalog();
                }
            }
        };

        function initUnassignedExhibitsSelect2() {
            const select = document.getElementById('linkedUnassignedExhibitsSelect');
            if (!select) return;

            if (!select.dataset.nativeListenerBound) {
                select.dataset.nativeListenerBound = '1';
                select.addEventListener('change', function(evt) {
                    if (evt && evt.isTrusted) {
                        window._unassignedSelectionManuallyChanged = true;
                    }
                    renderLinkedExhibitsPreview();
                    window.autoFillManifestFromLinkedExhibits();
                });
            }

            if (typeof jQuery === 'undefined' || !jQuery.fn.select2) {
                renderLinkedExhibitsPreview();
                return;
            }

            const $select = jQuery('#linkedUnassignedExhibitsSelect');
            if (!$select.length) return;

            if (!$select.hasClass('select2-hidden-accessible')) {
                $select.select2({
                    placeholder: $select.attr('data-placeholder') || 'Search unassigned exhibits...',
                    allowClear: true,
                    width: '100%',
                    dropdownAutoWidth: true
                });

                $select.on('select2:select select2:unselect select2:clear', function() {
                    window._unassignedSelectionManuallyChanged = true;
                    window._unassignedSelectionAutoAssigned = false;
                });

                $select.on('change', function() {
                    renderLinkedExhibitsPreview();
                    window.autoFillManifestFromLinkedExhibits();
                });
            }

            renderLinkedExhibitsPreview();
        }

        function escHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function syncAvailableCardsSelectionState() {
            const select = document.getElementById('linkedUnassignedExhibitsSelect');
            const selectedIds = new Set(select ? Array.from(select.selectedOptions || []).map(o => String(o.value)) : []);
            const linkMode = window.isLinkingSuspectToLockedExhibitMode();
            document.querySelectorAll('[data-unassigned-card-id]').forEach(btn => {
                const id = String(btn.getAttribute('data-unassigned-card-id') || '');
                const catalogItem = (window.unassignedExhibitsCatalog || {})[id];
                const hasPrelim = btn.getAttribute('data-locked') === '1' || hasAttachedPreliminaryReport(catalogItem);
                const isLocked = hasPrelim && !linkMode;
                const pill = btn.querySelector(`[data-unassigned-pill-id="${CSS.escape ? CSS.escape(id) : id}"]`);
                if (isLocked) {
                    btn.classList.remove('border-emerald-500', 'bg-emerald-50/50', 'border-slate-200', 'bg-white');
                    btn.classList.add('border-emerald-300', 'bg-slate-50/90', 'opacity-90', 'cursor-not-allowed');
                    btn.setAttribute('aria-disabled', 'true');
                    if (pill) {
                        pill.textContent = 'Locked • PDF Attached';
                        pill.classList.remove('bg-emerald-600', 'text-white', 'bg-slate-100');
                        pill.classList.add('bg-slate-200', 'text-slate-600', 'cursor-not-allowed');
                    }
                    return;
                }
                const isSelected = selectedIds.has(id);
                btn.classList.toggle('border-emerald-500', isSelected);
                btn.classList.toggle('bg-emerald-50/50', isSelected);
                btn.classList.toggle('border-slate-200', !isSelected);
                btn.classList.toggle('bg-white', !isSelected);
                if (pill) {
                    pill.textContent = isSelected ? 'Selected' : (hasPrelim && linkMode ? 'Select to Link Suspect' : 'Select');
                    pill.classList.toggle('bg-emerald-600', isSelected);
                    pill.classList.toggle('text-white', isSelected);
                    pill.classList.toggle('bg-slate-100', !isSelected);
                    pill.classList.toggle('text-slate-600', !isSelected);
                }
            });
        }

        function renderExhibitThumbBadge(photos, altText) {
            const list = Array.isArray(photos) ? photos.filter(Boolean) : [];
            if (list.length === 0) return '';
            const extra = list.length > 1
                ? `<span class="absolute bottom-0.5 right-0.5 px-1 bg-slate-900/80 text-white text-[9px] font-bold rounded">+${list.length - 1}</span>`
                : '';
            return `<div class="relative shrink-0 w-11 h-11 rounded-lg overflow-hidden border border-slate-200 bg-slate-100"><img src="${escHtml(list[0])}" alt="${escHtml(altText || 'Exhibit')}" class="w-full h-full object-cover">${extra}</div>`;
        }

        function renderExhibitPhotoStrip(photos, altText) {
            const list = Array.isArray(photos) ? photos.filter(Boolean) : [];
            if (list.length === 0) return '';
            const imgs = list.map((src, idx) =>
                `<img src="${escHtml(src)}" alt="${escHtml(altText || 'Exhibit')} Photo ${idx + 1}" class="w-14 h-14 object-cover rounded-lg border border-slate-200 bg-slate-50 shadow-2xs">`
            ).join('');
            return `<div class="flex flex-wrap items-center gap-2 pt-1.5">${imgs}</div>`;
        }

        window.renderAvailableUnassignedExhibits = function(list) {
            const listEl = document.getElementById('availableUnassignedExhibitsList');
            const subCountEl = document.getElementById('availableUnassignedSubCount');
            if (!listEl) return;

            const items = Array.isArray(list) ? list : [];
            if (subCountEl) {
                subCountEl.textContent = `${items.length} exhibit(s)`;
            }

            if (items.length === 0) {
                listEl.innerHTML = `<div class="col-span-full p-3 rounded-xl bg-white/80 border border-dashed border-slate-300 text-[11px] text-slate-500 font-medium text-center">No exhibits without suspect(s) are currently in the Vault.</div>`;
                return;
            }

            listEl.innerHTML = items.map(payload => {
                const idStr = escHtml(payload.id);
                const propId = escHtml(payload.property_id || payload.exhibit_number || ('Exhibit #' + payload.id));
                const cat = escHtml(payload.exhibit_category || '');
                const name = escHtml(payload.commodity_name || payload.exhibit_category || 'Unnamed Exhibit');
                const enteredBy = escHtml([payload.entered_by_unit, payload.entered_by_name].filter(Boolean).join(' • '));
                const thumbHtml = renderExhibitThumbBadge(payload.evidence_photos, payload.commodity_name || payload.exhibit_category);
                const detailParts = [];
                if (payload.seizure_quantity) detailParts.push(`Qty: ${payload.seizure_quantity}`);
                if (payload.seizure_weight) detailParts.push(`Wt: ${payload.seizure_weight} ${payload.seizure_unit || ''}`.trim());
                if (payload.seizure_location) detailParts.push(`Loc: ${payload.seizure_location}`);
                if (payload.seizure_date) detailParts.push(`Date: ${payload.seizure_date}`);
                const detailStr = escHtml(detailParts.join(' • ') || 'Awaiting suspect assignment');

                const prelim = payload.preliminary_report && typeof payload.preliminary_report === 'object' ? payload.preliminary_report : null;
                const hasPrelim = hasAttachedPreliminaryReport(payload);
                const isLocked = isUnassignedExhibitLocked(payload);
                const linkMode = window.isLinkingSuspectToLockedExhibitMode();
                const caseRefBadge = payload.case_number
                    ? `<span class="px-2 py-0.5 bg-indigo-50 text-indigo-800 border border-indigo-200 font-mono-code font-bold text-[10px] rounded">${escHtml(payload.case_number)}</span>`
                    : '';
                const reportLabel = escHtml(resolveReportLabel(prelim));
                const lockTitle = escHtml(`${resolveLockedOptionSuffix(prelim)} — check Link Suspect Biodata to unlock and assign a suspect`);
                const prelimHtml = prelim
                    ? `<div class="pt-1 flex flex-wrap items-center gap-1.5 text-[10px]" data-attached-prelim-report="${escHtml(prelim.id || '')}">
                            <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-900 border border-emerald-300 font-bold rounded">${reportLabel}</span>
                            <a href="${escHtml(prelim.url || '#')}" target="_blank" rel="noopener" onclick="event.stopPropagation()" class="px-2 py-0.5 bg-white hover:bg-emerald-50 text-emerald-800 border border-emerald-300 font-bold rounded underline">Open PDF</a>
                            <a href="${escHtml(prelim.download_url || prelim.url || '#')}" target="_blank" rel="noopener" download onclick="event.stopPropagation()" class="px-2 py-0.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded">Download PDF</a>
                       </div>`
                    : '';

                const cardAttr = isLocked
                    ? `data-unassigned-card-id="${idStr}" data-locked="1" aria-disabled="true" title="${lockTitle}" class="text-left p-3 bg-slate-50/90 border border-emerald-300 rounded-xl shadow-2xs transition-all flex items-start justify-between gap-2.5 opacity-90 cursor-not-allowed"`
                    : `data-unassigned-card-id="${idStr}" data-locked="${hasPrelim ? '1' : '0'}" role="button" tabindex="0" onclick="toggleUnassignedExhibitSelection('${idStr}')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();toggleUnassignedExhibitSelection('${idStr}');}" class="text-left p-3 bg-white border border-slate-200 hover:border-emerald-400 rounded-xl shadow-2xs transition-all flex items-start justify-between gap-2.5 group cursor-pointer"`;

                const pillHtml = isLocked
                    ? `<button type="button" disabled aria-disabled="true" data-unassigned-pill-id="${idStr}" class="shrink-0 px-2 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wider bg-slate-200 text-slate-600 cursor-not-allowed">Locked • PDF Attached</button>`
                    : `<span data-unassigned-pill-id="${idStr}" class="shrink-0 px-2 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wider bg-slate-100 text-slate-600 group-hover:bg-emerald-600 group-hover:text-white transition-colors">${hasPrelim && linkMode ? 'Select to Link Suspect' : 'Select'}</span>`;

                return `
                    <div ${cardAttr}>
                        ${thumbHtml}
                        <div class="space-y-1 min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="px-2 py-0.5 bg-emerald-50 text-emerald-800 border border-emerald-200 font-mono-code font-bold text-[10px] rounded">${propId}</span>
                                ${caseRefBadge}
                                ${cat ? `<span class="px-2 py-0.5 bg-slate-100 text-slate-700 font-bold text-[10px] rounded uppercase">${cat}</span>` : ''}
                                ${enteredBy ? `<span class="px-2 py-0.5 bg-amber-50 text-amber-800 border border-amber-200 font-bold text-[10px] rounded">${enteredBy}</span>` : ''}
                            </div>
                            <div class="font-extrabold text-slate-900 text-xs truncate">${name}</div>
                            <div class="text-[10px] text-slate-500 font-medium truncate">${detailStr}</div>
                            ${prelimHtml}
                        </div>
                        ${pillHtml}
                    </div>
                `;
            }).join('');

            syncAvailableCardsSelectionState();
        };

        window.toggleUnassignedExhibitSelection = function(id) {
            const select = document.getElementById('linkedUnassignedExhibitsSelect');
            if (!select) return;
            const item = (window.unassignedExhibitsCatalog || {})[String(id)];
            const target = Array.from(select.options).find(o => String(o.value) === String(id));
            if (isUnassignedExhibitLocked(item) || target?.disabled) {
                return;
            }
            window._unassignedSelectionManuallyChanged = true;
            window._unassignedSelectionAutoAssigned = false;
            if (target) {
                target.selected = !target.selected;
                if (typeof jQuery !== 'undefined' && jQuery(select).hasClass('select2-hidden-accessible')) {
                    jQuery(select).trigger('change');
                } else {
                    renderLinkedExhibitsPreview();
                    window.autoFillManifestFromLinkedExhibits();
                }
            }
        };

        function renderLinkedExhibitsPreview() {
            syncAvailableCardsSelectionState();
            syncReusedCaseReferenceFromLinkedExhibits();
            const select = document.getElementById('linkedUnassignedExhibitsSelect');
            const container = document.getElementById('linkedExhibitsPreviewContainer');
            const list = document.getElementById('linkedExhibitsCardsList');
            const badge = document.getElementById('linkedCountBadge');
            if (!select || !container || !list) return;

            const selectedOptions = Array.from(select.selectedOptions || []);
            if (selectedOptions.length === 0) {
                container.classList.add('hidden');
                list.innerHTML = '';
                return;
            }

            container.classList.remove('hidden');
            if (badge) badge.textContent = `${selectedOptions.length} Exhibit(s) Linked`;

            const isNoSuspect = typeof window.isSections1To3OptionalMode === 'function' && window.isSections1To3OptionalMode();

            list.innerHTML = selectedOptions.map(opt => {
                const payload = window.unassignedExhibitsCatalog[String(opt.value)] || readFallbackItemFromOption(opt) || {};
                const propId = escHtml(payload.property_id || opt.getAttribute('data-property-id') || 'N/A');
                const name = escHtml(payload.commodity_name || opt.getAttribute('data-commodity-name') || 'Unnamed Commodity');
                const cat = escHtml(payload.exhibit_category || opt.getAttribute('data-category') || 'General');
                const qty = payload.seizure_quantity || opt.getAttribute('data-quantity') || '';
                const wt = payload.seizure_weight || opt.getAttribute('data-weight') || '';
                const unit = payload.seizure_unit || opt.getAttribute('data-unit') || '';
                const loc = payload.seizure_location || opt.getAttribute('data-location') || '';
                const date = payload.seizure_date || opt.getAttribute('data-date') || '';
                const enteredBy = escHtml([payload.entered_by_unit, payload.entered_by_name].filter(Boolean).join(' • '));

                let detailParts = [];
                if (qty) detailParts.push(`Qty: ${qty}`);
                if (wt) detailParts.push(`Wt: ${wt} ${unit}`.trim());
                if (loc) detailParts.push(`Loc: ${loc}`);
                if (date) detailParts.push(`Date: ${date}`);
                const detailStr = escHtml(detailParts.join(' • '));
                const photosStripHtml = renderExhibitPhotoStrip(payload.evidence_photos, payload.commodity_name || payload.exhibit_category);

                const caseRefBadge = payload.case_number
                    ? `<span class="px-2 py-0.5 bg-indigo-50 text-indigo-800 border border-indigo-200 font-mono-code font-bold text-[10px] rounded">${escHtml(payload.case_number)}</span>`
                    : '';
                const existingRefSuffix = (window.isLinkingSuspectToLockedExhibitMode() && payload.case_number)
                    ? ` (reusing Case Ref ${escHtml(payload.case_number)})`
                    : '';

                return `
                    <div class="p-3 bg-white border border-emerald-300 rounded-xl shadow-2xs flex items-start justify-between gap-3 relative">
                        <div class="space-y-1 min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 font-mono-code font-bold text-[10px] rounded">
                                    ${propId}
                                </span>
                                ${caseRefBadge}
                                <span class="px-2 py-0.5 bg-slate-100 text-slate-700 font-bold text-[10px] rounded uppercase">
                                    ${cat}
                                </span>
                                ${enteredBy ? `<span class="px-2 py-0.5 bg-amber-50 text-amber-800 border border-amber-200 font-bold text-[10px] rounded">Saved by: ${enteredBy}</span>` : ''}
                            </div>
                            <h5 class="font-extrabold text-slate-900 text-xs truncate">${name}</h5>
                            ${detailStr ? `<p class="text-[10px] text-slate-500 font-medium">${detailStr}</p>` : ''}
                            ${photosStripHtml}
                            <p class="text-[10px] text-emerald-700 font-bold flex items-center gap-1 pt-0.5">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                ${isNoSuspect ? 'Auto-filled into Seized Commodities &amp; Contraband Manifest' : `Will be linked to this suspect${existingRefSuffix}`}
                            </p>
                        </div>
                        <button type="button" onclick="unlinkUnassignedExhibit('${escHtml(opt.value)}')"
                            class="text-slate-400 hover:text-red-600 transition-colors p-1" title="Unlink this exhibit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                `;
            }).join('');
        }

        window.unlinkUnassignedExhibit = function(id) {
            const select = document.getElementById('linkedUnassignedExhibitsSelect');
            if (!select) return;
            window._unassignedSelectionManuallyChanged = true;
            window._unassignedSelectionAutoAssigned = false;
            const options = Array.from(select.options);
            const target = options.find(o => String(o.value) === String(id));
            if (target) {
                target.selected = false;
                if (typeof jQuery !== 'undefined' && jQuery(select).hasClass('select2-hidden-accessible')) {
                    jQuery(select).trigger('change');
                } else {
                    renderLinkedExhibitsPreview();
                    window.autoFillManifestFromLinkedExhibits();
                }
            }
        };

        function bindAutoDetachOnManifestIdentityEdit() {
            const container = document.getElementById('seizuresListContainer');
            if (!container || container.dataset.autoDetachBound === '1') return;
            container.dataset.autoDetachBound = '1';

            const identitySelectors = '#deviceSearchInput, #newSeizureNameDevice, #newDevModel, #newDevSerialNo, #newDevImei1, #newSeizureName, #drugSearchInput, #weaponSearchInput, #currencySearchInput, #ammoSearchInput';
            container.addEventListener('input', function(evt) {
                if (!evt || !evt.isTrusted || !window._unassignedSelectionAutoAssigned || window._unassignedSelectionManuallyChanged) {
                    return;
                }
                const target = evt.target;
                if (!target || !target.matches || !target.matches(identitySelectors)) {
                    return;
                }
                const block = target.closest('.seizure-block');
                if (!block || !block.dataset.linkedExhibitId) {
                    return;
                }

                // Detach only the auto-selected link when officer edits exhibit identity to enter a new exhibit
                window._unassignedSelectionManuallyChanged = true;
                window._unassignedSelectionAutoAssigned = false;
                window._manifestAutoFilledFromLinked = false;
                container.querySelectorAll('.seizure-block').forEach(b => {
                    delete b.dataset.linkedExhibitId;
                    delete b.dataset.propertyId;
                    delete b.dataset.autoFilledFromLinked;
                });
                const select = document.getElementById('linkedUnassignedExhibitsSelect');
                if (select) {
                    Array.from(select.options || []).forEach(o => { o.selected = false; });
                    if (typeof jQuery !== 'undefined' && jQuery(select).hasClass('select2-hidden-accessible')) {
                        jQuery(select).trigger('change.select2');
                    }
                }
                const statusPill = document.getElementById('unassignedAutoFillStatus');
                if (statusPill) {
                    statusPill.classList.add('hidden');
                    statusPill.textContent = '';
                }
                renderLinkedExhibitsPreview();
            }, true);
        }

        function bootUnassignedExhibitsSelector() {
            bindAutoDetachOnManifestIdentityEdit();
            const caseRefInput = document.getElementById('labCaseReferenceInput');
            if (caseRefInput && window._originalGeneratedCaseRef === undefined) {
                window._originalGeneratedCaseRef = caseRefInput.value || '';
            }
            if (window.isLinkingSuspectToLockedExhibitMode()) {
                window.toggleLinkSuspectToLockedExhibitMode(true);
            }
            initUnassignedExhibitsSelect2();
            flushPendingLinkedExhibitPhotos();
            setTimeout(flushPendingLinkedExhibitPhotos, 60);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bootUnassignedExhibitsSelector);
        } else {
            bootUnassignedExhibitsSelector();
        }
        window.addEventListener('load', flushPendingLinkedExhibitPhotos);
    })();
</script>
@endif
