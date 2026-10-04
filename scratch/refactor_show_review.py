with open('resources/views/cases/show_review.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Markers
marker_s5_if = '<!-- SECTION 5: EXAMINATION INFORMATION (LABORATORY ANALYSIS DIRECTORATE) -->'
marker_s4_start = '<!-- SECTION 4: SEIZURES (COMMODITIES & EXHIBITS) -->'
marker_s5_form_container = '<!-- EXAMINATION INFORMATION CONTAINER (ALWAYS VISIBLE AT LAB ANALYSIS STAGE WHEN UNSAVED) -->'

# 1. Extract Section 4 block
idx_s4 = content.find(marker_s4_start)
idx_s5_form = content.find(marker_s5_form_container)

s4_raw = content[idx_s4:idx_s5_form]

# Replace s4 header with beautiful dossier-card header
old_s4_head = '''<!-- SECTION 4: SEIZURES (COMMODITIES & EXHIBITS) -->
                        <div id="section4Seizures"
                            class="mt-8 bg-white/90 border border-slate-300/80 rounded-2xl p-6 shadow-sm">
                            <form id="section4SeizuresForm"
                                onsubmit="{{ $isLabAnalysis ? 'submitCombinedLabAndSeizureForm(event)' : 'submitSection4SeizureForm(event)' }}">
                                <div id="seizuresListContainer" class="space-y-6">'''

new_s4_head = '''{{-- SECTION 4: SEIZURES — VISIBLE TO ALL UNITS --}}
                <div id="section4Seizures"
                    class="dossier-card p-6 border border-slate-200 space-y-6 shadow-sm bg-white rounded-2xl mt-4">
                    <div class="pb-3 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-md shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-slate-900 text-base">Seized Items &amp; Exhibits</h3>
                                <p class="text-xs text-emerald-700 font-semibold">Register confiscated items, commodities &amp; evidence</p>
                            </div>
                        </div>
                    </div>

                    <form id="section4SeizuresForm"
                        onsubmit="{{ $isLabAnalysis ? 'submitCombinedLabAndSeizureForm(event)' : 'submitSection4SeizureForm(event)' }}">
                        <div id="seizuresListContainer" class="space-y-6">'''

assert old_s4_head in s4_raw, "old_s4_head not found in s4_raw"
s4_new = s4_raw.replace(old_s4_head, new_s4_head)

# Uncomment action buttons in Section 4
old_buttons = '''                                <!-- ACTION BUTTONS -->
                                <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-200">
                                    <!--<button type="button"
                                        onclick="document.getElementById('section4Seizures').classList.add('hidden')"
                                        class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold text-xs rounded-xl transition-all cursor-pointer border-none">
                                        Cancel
                                    </button>
                                    <button type="submit" id="saveSection4SeizureBtn"
                                        onclick="{{ $isLabAnalysis ? 'submitCombinedLabAndSeizureForm(event)' : 'submitSection4SeizureForm(event)' }}"
                                        class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-600/30 transition-all flex items-center justify-center cursor-pointer border-none">
                                        <span
                                            id="saveSection4SeizureBtnText">{{ $isLabAnalysis ? 'Save Seized Item & Examination Information' : 'Save Seizure / Exhibit' }}</span>
                                    </button>-->
                                </div>'''

new_buttons = '''                                <!-- ACTION BUTTONS -->
                                <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-200">
                                    <button type="button"
                                        onclick="document.getElementById('section4Seizures').classList.add('hidden')"
                                        class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold text-xs rounded-xl transition-all cursor-pointer border-none">
                                        Cancel
                                    </button>
                                    <button type="submit" id="saveSection4SeizureBtn"
                                        onclick="{{ $isLabAnalysis ? 'submitCombinedLabAndSeizureForm(event)' : 'submitSection4SeizureForm(event)' }}"
                                        class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-600/30 transition-all flex items-center justify-center cursor-pointer border-none">
                                        <span
                                            id="saveSection4SeizureBtnText">{{ $isLabAnalysis ? 'Save Seized Item & Examination Information' : 'Save Seizure / Exhibit' }}</span>
                                    </button>
                                </div>'''

assert old_buttons in s4_new, "old_buttons not found in s4_new"
s4_new = s4_new.replace(old_buttons, new_buttons)

# Canonical suspect resolution block
canonical_suspect_block = '''                @php
                    // CANONICAL SUSPECT RESOLUTION (shared by Section 4 & Section 5)
                    $allSuspectsInCase = [];
                    if (!empty($docketData['suspects']) && is_array($docketData['suspects'])) {
                        foreach ($docketData['suspects'] as $sIndex => $sp) {
                            if (!empty($sp['suspect_name'])) {
                                $spRole =
                                    $sp['association_role'] ??
                                    ($sp['role'] ?? ($sIndex === 0 ? 'Primary Suspect' : 'Associate'));
                                $isPrimary = $sIndex === 0 || strtolower($spRole) === 'primary suspect';
                                $allSuspectsInCase[] = [
                                    'id'           => $sp['id'] ?? null,
                                    'associate_id' => $sp['associate_id'] ?? ($sp['suspect_id'] ?? null),
                                    'name'         => trim($sp['suspect_name']),
                                    'alias'        => $sp['suspect_alias'] ?? 'N/A',
                                    'role'         => $spRole,
                                    'is_primary'   => $isPrimary,
                                    'is_associate' => !$isPrimary,
                                ];
                            }
                        }
                    }
                    if (empty($allSuspectsInCase)) {
                        $allSuspectsInCase[] = [
                            'name'         => trim($case->suspect_name ?? 'Primary Suspect'),
                            'alias'        => $case->suspect_alias ?? 'N/A',
                            'role'         => 'Primary Suspect',
                            'is_primary'   => true,
                            'is_associate' => false,
                        ];
                    }

                    $reqSuspectParam = request()->query('suspect');
                    $activeSuspectItem = $allSuspectsInCase[0];
                    if (!empty($reqSuspectParam)) {
                        foreach ($allSuspectsInCase as $sCandidate) {
                            if ($suspectNamesMatch($sCandidate['name'] ?? '', $reqSuspectParam)) {
                                $activeSuspectItem = $sCandidate;
                                break;
                            }
                        }
                    }
                    $activeSuspectName        = $activeSuspectItem['name'];
                    $activeSuspectNameLower   = strtolower(trim($activeSuspectName));
                    $isActiveSuspectPrimary   = !empty($activeSuspectItem['is_primary']);
                @endphp

'''

# Remove Section 4 from inside Section 5
content_without_s4 = content[:idx_s4] + content[idx_s5_form:]

# Update section5LabExamination container class to have mt-4
old_s5_div = '''<div class="dossier-card p-6 border border-slate-200 space-y-6 shadow-sm bg-white rounded-2xl"
                        id="section5LabExamination">'''
new_s5_div = '''<div class="dossier-card p-6 border border-slate-200 space-y-6 shadow-sm bg-white rounded-2xl mt-4"
                        id="section5LabExamination">'''
assert old_s5_div in content_without_s4, "old_s5_div not found"
content_without_s4 = content_without_s4.replace(old_s5_div, new_s5_div, 1)

# Remove duplicate suspect resolution inside Section 5 @php
old_dup_suspect = '''                        @php
                            // GATHER ALL SUSPECTS IN THIS CASE FOR SUSPECT WORKSPACE SELECTOR TABS
                            $allSuspectsInCase = [];
                            if (!empty($docketData['suspects']) && is_array($docketData['suspects'])) {
                                foreach ($docketData['suspects'] as $sIndex => $sp) {
                                    if (!empty($sp['suspect_name'])) {
                                        $spRole =
                                            $sp['association_role'] ??
                                            ($sp['role'] ?? ($sIndex === 0 ? 'Primary Suspect' : 'Associate'));
                                        $isPrimary = $sIndex === 0 || strtolower($spRole) === 'primary suspect';
                                        $allSuspectsInCase[] = [
                                            'id' => $sp['id'] ?? null,
                                            'associate_id' => $sp['associate_id'] ?? ($sp['suspect_id'] ?? null),
                                            'name' => trim($sp['suspect_name']),
                                            'alias' => $sp['suspect_alias'] ?? 'N/A',
                                            'role' => $spRole,
                                            'is_primary' => $isPrimary,
                                            'is_associate' => !$isPrimary,
                                        ];
                                    }
                                }
                            }
                            if (empty($allSuspectsInCase)) {
                                $allSuspectsInCase[] = [
                                    'name' => trim($case->suspect_name ?? 'Primary Suspect'),
                                    'alias' => $case->suspect_alias ?? 'N/A',
                                    'role' => 'Primary Suspect',
                                    'is_primary' => true,
                                    'is_associate' => false,
                                ];
                            }

                            // RESOLVE ACTIVE SUSPECT FROM QUERY PARAMETERS
                            $reqSuspectParam = request()->query('suspect');
                            $activeSuspectItem = $allSuspectsInCase[0];
                            if (!empty($reqSuspectParam)) {
                                foreach ($allSuspectsInCase as $sCandidate) {
                                    if ($suspectNamesMatch($sCandidate['name'] ?? '', $reqSuspectParam)) {
                                        $activeSuspectItem = $sCandidate;
                                        break;
                                    }
                                }
                            }
                            $activeSuspectName = $activeSuspectItem['name'];
                            $activeSuspectNameLower = strtolower(trim($activeSuspectName));

                            // STRICT CROSS-SUSPECT ISOLATION LOOKUP FOR ACTIVE SUSPECT'''

new_cleaned_s5_php = '''                        @php
                            // STRICT CROSS-SUSPECT ISOLATION LOOKUP FOR ACTIVE SUSPECT'''

assert old_dup_suspect in content_without_s4, "old_dup_suspect not found"
content_without_s4 = content_without_s4.replace(old_dup_suspect, new_cleaned_s5_php, 1)

# Now insert canonical suspect block + s4_new BEFORE marker_s5_if
idx_s5_if = content_without_s4.find(marker_s5_if)
assert idx_s5_if != -1, "marker_s5_if not found"

final_content = (
    content_without_s4[:idx_s5_if]
    + canonical_suspect_block
    + s4_new
    + '\n                '
    + content_without_s4[idx_s5_if:]
)

print("Final content prepared successfully! Length:", len(final_content))

# Write to resources/views/cases/show_review.blade.php
with open('resources/views/cases/show_review.blade.php', 'w', encoding='utf-8') as f:
    f.write(final_content)

print("Successfully written back to show_review.blade.php!")
