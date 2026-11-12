<!-- OPTIONAL SECTIONS 1-3 TOGGLE (SEIZURE WITHOUT SUSPECT) -->
<div class="bg-amber-50/70 p-4 rounded-2xl border border-amber-200/90 shadow-xs transition-all">
    <label for="noSuspectToggleCheckbox"
        class="flex items-start sm:items-center justify-between gap-3 cursor-pointer select-none">
        <div class="flex items-start sm:items-center space-x-3.5 min-w-0">
            <input type="checkbox" id="noSuspectToggleCheckbox"
                onchange="toggleSections1To3Optional()"
                class="w-5 h-5 mt-0.5 sm:mt-0 text-amber-600 rounded-md border-amber-300 focus:ring-amber-600 focus:ring-2 cursor-pointer accent-amber-600 shrink-0">
            <div class="flex flex-col min-w-0">
                <span class="text-xs font-extrabold text-slate-900">
                    Seizure Without Suspect (Disable Sections 1 &ndash; 3)
                </span>
                <span class="text-[11px] text-slate-600 font-medium mt-0.5">
                    Check this box when exhibits or commodities were seized with no suspect present. Sections 1 to 3 (Case Title, Mugshot &amp; Suspect Biodata) become optional and disabled, allowing direct submission of Section 4 Seizures.
                </span>
            </div>
        </div>
        <span id="sections1To3OptionalBadge"
            class="hidden px-2.5 py-1 bg-amber-100 text-amber-900 text-[10px] font-mono-code font-bold rounded-lg border border-amber-300 uppercase tracking-wider shrink-0">
            Sections 1&ndash;3 Disabled
        </span>
    </label>
</div>

<script>
    window.isSections1To3OptionalMode = function() {
        return Boolean(document.getElementById('noSuspectToggleCheckbox')?.checked);
    };

    window.syncSeizureOwnershipBoxes = function(explicitState) {
        const isUnassigned = typeof explicitState === 'boolean'
            ? explicitState
            : window.isSections1To3OptionalMode();

        document.querySelectorAll('.seizure-ownership-default').forEach(el => {
            // Keep Suspect / Ownership dropdown visible and editable (never replace with a read-only box)
            el.classList.remove('hidden');
            const selectEl = el.querySelector('.seizure-associated-person-select');
            if (selectEl) {
                selectEl.disabled = false;
                selectEl.removeAttribute('readonly');
                let unknownOpt = Array.from(selectEl.options).find(o => o.value.toLowerCase() === 'unknown');
                if (!unknownOpt) {
                    unknownOpt = document.createElement('option');
                    unknownOpt.value = 'Unknown';
                    unknownOpt.textContent = 'Unknown';
                    selectEl.insertBefore(unknownOpt, selectEl.firstChild);
                }
                if (isUnassigned && !selectEl.getAttribute('data-user-selected')) {
                    selectEl.value = 'Unknown';
                }
            }
        });

        document.querySelectorAll('.seizure-ownership-unassigned').forEach(el => {
            el.classList.add('hidden');
        });
    };

    window.toggleSections1To3Optional = function(explicitState) {
        const chk = document.getElementById('noSuspectToggleCheckbox');
        const wrapper = document.getElementById('sections1To3Wrapper');
        const badge = document.getElementById('sections1To3OptionalBadge');
        if (!chk || !wrapper) return;

        if (typeof explicitState === 'boolean') {
            chk.checked = explicitState;
        }
        const isDisabled = Boolean(chk.checked);

        if (badge) {
            badge.classList.toggle('hidden', !isDisabled);
        }

        wrapper.setAttribute('aria-disabled', isDisabled ? 'true' : 'false');
        wrapper.querySelectorAll(':scope > .bg-white').forEach(card => {
            card.classList.toggle('opacity-50', isDisabled);
            card.classList.toggle('pointer-events-none', isDisabled);
            card.classList.toggle('select-none', isDisabled);
        });

        wrapper.querySelectorAll('input, select, textarea, button').forEach(el => {
            if (el.closest('#docketDraftNotice')) return;

            if (isDisabled) {
                if (el.disabled && el.dataset.optionalPrevDisabled !== '0') {
                    el.dataset.optionalPrevDisabled = '1';
                } else {
                    el.dataset.optionalPrevDisabled = '0';
                    el.disabled = true;
                }
                el.classList.remove('border-red-500', 'ring-2', 'ring-red-500/20');
            } else {
                const wasDisabled = el.dataset.optionalPrevDisabled === '1';
                delete el.dataset.optionalPrevDisabled;
                el.disabled = wasDisabled;
            }
        });

        window.syncSeizureOwnershipBoxes(isDisabled);

        if (isDisabled) {
            const linkLockedChk = document.getElementById('linkSuspectToLockedExhibitCheckbox');
            if (linkLockedChk && linkLockedChk.checked) {
                linkLockedChk.checked = false;
                if (typeof window.toggleLinkSuspectToLockedExhibitMode === 'function') {
                    window.toggleLinkSuspectToLockedExhibitMode();
                }
            }
            const hasSeizuresChk = document.getElementById('hasSeizuresCheckbox');
            if (hasSeizuresChk && !hasSeizuresChk.checked) {
                hasSeizuresChk.checked = true;
                if (typeof toggleSeizuresContainer === 'function') {
                    toggleSeizuresContainer();
                }
            }
            if (typeof window.activateLinkExistingExhibitsForNoSuspect === 'function') {
                window.activateLinkExistingExhibitsForNoSuspect(true);
            }
        } else if (typeof window.activateLinkExistingExhibitsForNoSuspect === 'function') {
            window.activateLinkExistingExhibitsForNoSuspect(false);
        }

        if (typeof saveDocketFormDraft === 'function') {
            saveDocketFormDraft();
        }
    };

    window.syncSections1To3OptionalState = function() {
        if (typeof window.toggleSections1To3Optional === 'function') {
            window.toggleSections1To3Optional();
        }
    };
</script>

