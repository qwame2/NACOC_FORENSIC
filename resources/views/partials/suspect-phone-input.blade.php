@php
    $unitSlug = $unit ?? 'general';
    $suspectPhone = old('phone', $suspect->phone ?? ($suspect['phone'] ?? ''));
    $suspectPhones = old('phones', $suspect->phones ?? ($suspect['phones'] ?? []));
    if (is_string($suspectPhones)) {
        $suspectPhones = json_decode($suspectPhones, true) ?: [];
    }
    $oldAdditional = old('additional_phones', []);
    $oldLabels = old('additional_phone_labels', []);

    $primaryLabel = old('phone_label', 'Primary');
    $existingRows = [];

    if (!empty($oldAdditional) && is_array($oldAdditional)) {
        foreach ($oldAdditional as $idx => $num) {
            $num = trim((string)$num);
            if ($num !== '') {
                $existingRows[] = [
                    'label' => $oldLabels[$idx] ?? 'Alternate',
                    'number' => $num
                ];
            }
        }
    } elseif (!empty($suspectPhones) && is_array($suspectPhones)) {
        foreach ($suspectPhones as $idx => $p) {
            if (is_array($p)) {
                $num = trim((string)($p['number'] ?? ($p['phone'] ?? '')));
                $lbl = trim((string)($p['label'] ?? 'Alternate'));
                if ($idx === 0 && empty($suspectPhone)) {
                    $suspectPhone = $num;
                    $primaryLabel = $lbl;
                } elseif ($num !== '' && ($num !== $suspectPhone || $idx > 0)) {
                    $existingRows[] = ['label' => $lbl, 'number' => $num];
                }
            } elseif (is_string($p) && trim($p) !== '') {
                $trimP = trim($p);
                if ($idx === 0 && empty($suspectPhone)) {
                    $suspectPhone = $trimP;
                } elseif ($trimP !== $suspectPhone || $idx > 0) {
                    $existingRows[] = ['label' => 'Alternate', 'number' => $trimP];
                }
            }
        }
    }
@endphp

<div id="suspectPhonesContainer" data-unit="{{ $unitSlug }}" class="bg-slate-50/80 p-3.5 rounded-2xl border border-slate-200/90 shadow-2xs space-y-3 transition-all hover:border-slate-300">
    <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
        <div class="flex items-center space-x-2">
            <div class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs shadow-2xs">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
            </div>
            <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-0">
                Contact Phone Number(s) <span class="text-red-600 font-bold">*</span>
            </label>
        </div>
        <span class="text-[9px] font-mono-code bg-white text-slate-500 font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-md border border-slate-200/80 shadow-2xs">Multi-Phone</span>
    </div>

    <div id="suspectPhonesList" class="space-y-2.5">
        {{-- PRIMARY PHONE (ROW #1 - Unremovable) --}}
        <div class="suspect-phone-row flex items-center gap-2">
            <div class="relative shrink-0">
                <select name="phone_labels[]" class="w-28 sm:w-32 px-2.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-800 outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer shadow-2xs">
                    @foreach(['Primary', 'Alternate', 'WhatsApp', 'Emergency', 'Other'] as $opt)
                        <option value="{{ $opt }}" {{ $primaryLabel === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="relative flex-1 flex items-center group">
                <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                </span>
                <input type="text"
                       name="phone"
                       id="newArrestedPhone"
                       value="{{ $suspectPhone }}"
                       placeholder="e.g. +233 24 555 0192"
                       required
                       class="w-full pl-10 pr-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400 text-xs">
            </div>
            <div class="w-9 h-9 shrink-0 flex items-center justify-center"></div>
        </div>

        {{-- EXISTING ADDITIONAL PHONE ROWS (Server-rendered if present) --}}
        @foreach($existingRows as $exRow)
            <div class="suspect-phone-row flex items-center gap-2 mt-2 transition-all">
                <div class="relative shrink-0">
                    <select name="additional_phone_labels[]" class="w-28 sm:w-32 px-2.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-800 outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer shadow-2xs">
                        @foreach(['Primary', 'Alternate', 'WhatsApp', 'Emergency', 'Other'] as $opt)
                            <option value="{{ $opt }}" {{ ($exRow['label'] === $opt) ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="relative flex-1 flex items-center group">
                    <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </span>
                    <input type="text"
                           name="additional_phones[]"
                           value="{{ $exRow['number'] }}"
                           placeholder="e.g. 0201 987 654"
                           class="w-full pl-10 pr-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400 text-xs">
                </div>
                <button type="button"
                        onclick="removeSuspectPhoneRow(this)"
                        title="Remove phone number"
                        class="w-9 h-9 p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-xl border border-slate-200 hover:border-red-200 transition-colors cursor-pointer shrink-0 flex items-center justify-center bg-white shadow-2xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        @endforeach
    </div>

    <div class="pt-0.5">
        <button type="button"
                onclick="addSuspectPhoneRow()"
                class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-extrabold rounded-xl transition-all cursor-pointer shadow-2xs hover:shadow-xs active:scale-[0.98]">
            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Add Phone Number</span>
        </button>
    </div>

    {{-- DYNAMIC ROW TEMPLATE --}}
    <template id="suspectPhoneRowTemplate">
        <div class="suspect-phone-row flex items-center gap-2 mt-2 transition-all">
            <div class="relative shrink-0">
                <select name="additional_phone_labels[]" class="w-28 sm:w-32 px-2.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-800 outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all cursor-pointer shadow-2xs">
                    <option value="Primary">Primary</option>
                    <option value="Alternate" selected>Alternate</option>
                    <option value="WhatsApp">WhatsApp</option>
                    <option value="Emergency">Emergency</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="relative flex-1 flex items-center group">
                <span class="absolute left-3.5 text-slate-400 group-focus-within:text-black transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                </span>
                <input type="text"
                       name="additional_phones[]"
                       placeholder="e.g. 0201 987 654"
                       class="w-full pl-10 pr-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs placeholder:text-slate-400 text-xs">
            </div>
            <button type="button"
                    onclick="removeSuspectPhoneRow(this)"
                    title="Remove phone number"
                    class="w-9 h-9 p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-xl border border-slate-200 hover:border-red-200 transition-colors cursor-pointer shrink-0 flex items-center justify-center bg-white shadow-2xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </div>
    </template>
</div>

<script>
    if (!window._suspectPhoneScriptInitialized) {
        window._suspectPhoneScriptInitialized = true;

        window.addSuspectPhoneRow = function(label = 'Alternate', number = '') {
            const list = document.getElementById('suspectPhonesList');
            const template = document.getElementById('suspectPhoneRowTemplate');
            if (!list || !template) return null;

            const clone = template.content.cloneNode(true);
            const row = clone.querySelector('.suspect-phone-row');
            const select = clone.querySelector('select[name="additional_phone_labels[]"]');
            const input = clone.querySelector('input[name="additional_phones[]"]');

            if (select && label) select.value = label;
            if (input && number) input.value = number;

            list.appendChild(clone);
            if (!number && input) input.focus();
            if (typeof saveDocketFormDraft === 'function') saveDocketFormDraft();
            return row;
        };

        window.removeSuspectPhoneRow = function(btn) {
            if (!btn) return;
            const row = btn.closest('.suspect-phone-row');
            const list = document.getElementById('suspectPhonesList');
            if (!row || !list) return;

            const allRows = list.querySelectorAll('.suspect-phone-row');
            if (allRows.length > 0 && allRows[0] === row) return; // Guard: row #1 is primary

            row.remove();
            if (typeof saveDocketFormDraft === 'function') saveDocketFormDraft();
        };

        window.getAdditionalPhonesPayload = function() {
            const list = document.getElementById('suspectPhonesList');
            if (!list) return [];
            const rows = list.querySelectorAll('.suspect-phone-row');
            const phones = [];
            for (let i = 1; i < rows.length; i++) {
                const labelEl = rows[i].querySelector('select[name="additional_phone_labels[]"]');
                const numEl = rows[i].querySelector('input[name="additional_phones[]"]');
                const num = numEl?.value?.trim() || '';
                if (num) {
                    phones.push({ label: labelEl?.value || 'Alternate', number: num });
                }
            }
            return phones;
        };

        window.getAllSuspectPhonesPayload = function() {
            const list = document.getElementById('suspectPhonesList');
            if (!list) return [];
            const rows = list.querySelectorAll('.suspect-phone-row');
            const phones = [];
            rows.forEach((row, idx) => {
                const isPrimary = (idx === 0);
                const labelEl = isPrimary 
                    ? (row.querySelector('select[name="phone_label"]') || row.querySelector('select[name="phone_labels[]"]'))
                    : row.querySelector('select[name="additional_phone_labels[]"]');
                const numEl = isPrimary
                    ? (row.querySelector('input[name="phone"]') || document.getElementById('newArrestedPhone'))
                    : row.querySelector('input[name="additional_phones[]"]');
                const num = numEl?.value?.trim() || '';
                if (num) {
                    phones.push({
                        label: labelEl?.value || (isPrimary ? 'Primary' : 'Alternate'),
                        number: num,
                        is_primary: isPrimary
                    });
                }
            });
            return phones;
        };

        window.validateSuspectPhones = function() {
            const list = document.getElementById('suspectPhonesList');
            if (!list) return true;
            const inputs = list.querySelectorAll('input[type="text"]');
            const seen = new Set();
            for (const input of inputs) {
                const clean = input.value.replace(/[\s\-\(\)]/g, '').trim();
                if (clean) {
                    if (seen.has(clean)) {
                        if (typeof showToast === 'function') {
                            showToast('⚠ Duplicate suspect phone number detected: ' + input.value);
                        } else {
                            alert('Duplicate suspect phone number detected: ' + input.value);
                        }
                        input.focus();
                        return false;
                    }
                    seen.add(clean);
                }
            }
            return true;
        };
    }
</script>
