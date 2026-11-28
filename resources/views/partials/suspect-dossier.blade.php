{{--
    Partial: partials/suspect-dossier.blade.php
    Description: Renders the suspect booking & personal details dossier, associate exhibits, per-suspect lab result card, GSA/NLR reports, and drug screening card.
    Required Inputs (@props):
      - $suspect (array): Suspect array containing pre-resolved dossier presentation data under resolved_view.
      - $case (object): The ForensicCase model/adapter instance.
--}}@props([
    'suspect' => [],
    'case' => null,
])
                    <!-- SUSPECT BOOKING & PERSONAL DETAILS FOR SUSPECT #{{ $suspect['resolved_view']['sNum'] }} -->
                                        <div class="dossier-card p-6 border border-slate-200 space-y-6">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                            <div class="flex items-center gap-3">
                                <span
                                    class="text-xs font-mono-code font-extrabold text-emerald-700 uppercase tracking-wider">
                                    SUSPECT #{{ $suspect['resolved_view']['sNum'] }} DOSSIER
                                </span>
                                <span
                                    class="text-[11px] font-mono-code font-extrabold uppercase tracking-wider {{ str_contains($suspect['resolved_view']['sDisp'], 'Active') ? 'text-amber-700' : (str_contains($suspect['resolved_view']['sDisp'], 'Cleared') ? 'text-emerald-700' : 'text-slate-700') }}">
                                    {{ strtoupper($suspect['resolved_view']['sDisp']) }}
                                </span>
                                                                @if ($suspect['resolved_view']['sHeaderDrugScreening'])
                                                                        <span class="text-xs font-mono-code font-black uppercase tracking-wider {{ $suspect['resolved_view']['sScreeningTextCls'] }}" title="Substances: {{ $suspect['resolved_view']['sHeaderDrugScreening']->substances_found ?: 'None' }}">
                                        DRUG SCREENING: {{ strtoupper($suspect['resolved_view']['sHeaderDrugScreening']->result) }}
                                    </span>
                                @endif
                                <div>
                                    <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                                        {{ $suspect['resolved_view']['sName'] }} — Personal Booking Details
                                        @if ($suspect['resolved_view']['isAssociate'])
                                            <span
                                                class="px-2 py-0.5 rounded text-[10px] font-mono-code font-extrabold bg-amber-500 text-white shadow-2xs">{{ strtoupper($suspect['resolved_view']['sRoleLabel']) }}</span>
                                        @endif
                                    </h2>
                                    <p class="text-xs text-slate-500">Legal identity, charges, family records, and
                                        custody allocation</p>
                                </div>
                            </div>
                            @if ((!empty($suspect['resolved_view']['isCaseController']) || !empty($suspect['resolved_view']['authUserIsHead'])) && !empty($suspect['resolved_view']['isMultipleCase']))
                                <button type="button"
                                    onclick="openSuspectDispositionModal('{{ addslashes($suspect['resolved_view']['sName']) }}', '{{ addslashes($suspect['resolved_view']['sDisp']) }}')"
                                    class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-amber-300 text-xs font-bold rounded-xl shadow-2xs transition-all flex items-center gap-1.5 cursor-pointer border-none shrink-0">
                                    <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Update Disposition
                                </button>
                            @endif
                        </div>

                        <!-- CASE CREATION & STATUS AUDIT TRACKING -->
                        <div data-no-rollback="true" class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs font-sans">
                            <div class="flex items-center gap-2" data-no-rollback="true">
                                <span class="text-[10px] font-mono-code font-extrabold text-slate-500 uppercase tracking-wider">Case Created By:</span>
                                <span class="font-bold text-slate-900 font-mono-code">{{ $suspect['resolved_view']['caseCreatorName'] }} <span class="text-emerald-700 font-bold">({{ $suspect['resolved_view']['caseCreatorUnit'] }})</span></span>
                                @if ($suspect['resolved_view']['caseCreatedAt'])
                                    <span class="text-slate-400 text-[10px] font-mono-code">{{ (request()->is('*/staff*') || request()->is('staff*') || request()->is('*field-officers*')) ? '|' : '•' }} {{ $suspect['resolved_view']['caseCreatedAt'] }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 p-1.5 rounded-lg border border-transparent transition-all rollback-allowed-item" data-rollback-allowed="true" data-rollback-target="Arresting Officer">
                                <span class="text-[10px] font-mono-code font-extrabold text-slate-500 uppercase tracking-wider">Arresting Officer:</span>
                                <span class="font-bold text-slate-900 font-mono-code bg-white px-2 py-0.5 rounded border border-slate-200 shadow-2xs">{{ $suspect['resolved_view']['arrestingOfficerName'] }}</span>
                            </div>
                            <div class="flex items-center gap-2" data-no-rollback="true">
                                <span class="text-[10px] font-mono-code font-extrabold text-slate-500 uppercase tracking-wider">Status Updated By:</span>
                                @if (!empty($suspect['resolved_view']['statusUpdatedBy']))
                                    <span class="font-bold text-slate-900 font-mono-code bg-white px-2 py-0.5 rounded border border-slate-200 shadow-2xs">
                                        {{ $suspect['resolved_view']['statusUpdatedBy'] }} <span class="text-amber-700 font-bold">({{ $suspect['resolved_view']['statusUpdatedByUnit'] ?: 'Unit Head' }})</span>
                                    </span>
                                    @if ($suspect['resolved_view']['statusUpdatedAt'])
                                        <span class="text-slate-400 text-[10px] font-mono-code">{{ (request()->is('*/staff*') || request()->is('staff*') || request()->is('*field-officers*')) ? '|' : '•' }} {{ $suspect['resolved_view']['statusUpdatedAt'] }}</span>
                                    @endif
                                @else
                                    <span class="italic text-slate-400 font-mono-code">Initial ({{ $suspect['resolved_view']['caseCreatorName'] }})</span>
                                @endif
                            </div>
                        </div>

                        <!-- Legal Identity & Origin -->
                        <div class="space-y-3">
                            <div
                                class="text-xs font-black text-slate-900 uppercase tracking-wider font-mono-code border-b border-slate-100 pb-1">
                                Legal Identity Details (Suspect #{{ $suspect['resolved_view']['sNum'] }})
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                                <div
                                    class="p-3 rounded-xl {{ $suspect['resolved_view']['isAssociate'] ? 'bg-amber-50/90 border-amber-200' : 'bg-slate-50 border-slate-200' }} border">
                                    <div
                                        class="text-[10px] font-mono-code font-bold {{ $suspect['resolved_view']['isAssociate'] ? 'text-amber-800' : 'text-slate-500' }} uppercase">
                                        Suspect Role / Classification</div>
                                    <div
                                        class="font-extrabold text-xs sm:text-sm mt-0.5 {{ $suspect['resolved_view']['isAssociate'] ? 'text-amber-900' : 'text-slate-900' }}">
                                        {{ strtoupper($suspect['resolved_view']['sRoleLabel']) }}
                                    </div>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200" data-field-group="suspect_name" data-rollback-target="Suspect Name">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Suspect
                                        Full Name</div>
                                    <div class="font-extrabold text-slate-900 text-sm mt-0.5">{{ $suspect['resolved_view']['sName'] }}
                                    </div>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200" data-field-group="suspect_alias" data-rollback-target="Suspect Alias">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Alias /
                                        Street Name</div>
                                    <div class="font-bold text-slate-800 text-sm mt-0.5">{{ $suspect['resolved_view']['sAlias'] }}</div>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200" data-field-group="ghana_card" data-rollback-target="Ghana Card">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Ghana
                                        Card #</div>
                                    <div class="font-bold font-mono-code text-sm mt-0.5">{{ $suspect['resolved_view']['sCard'] }}</div>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200" data-field-group="nationality" data-rollback-target="Nationality">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">
                                        Nationality</div>
                                    <div class="font-bold text-slate-800 text-sm mt-0.5">{{ $suspect['resolved_view']['sNat'] }}</div>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200" data-field-group="pob" data-rollback-target="Place of Birth">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Hometown
                                        / POB</div>
                                    <div class="font-bold text-slate-800 text-sm mt-0.5">{{ $suspect['resolved_view']['sPOB'] }}</div>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200" data-field-group="phone" data-rollback-target="Contact Phone">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase flex items-center justify-between">
                                        <span>Contact Phone(s)</span>
                                        @if(count($suspect['resolved_view']['sPhones']) > 1)
                                            <span class="text-[9px] bg-blue-100 text-blue-700 font-extrabold px-1.5 py-0.5 rounded-full border border-blue-200">{{ count($suspect['resolved_view']['sPhones']) }} numbers</span>
                                        @endif
                                    </div>
                                    @if(!empty($suspect['resolved_view']['sPhones']) && count($suspect['resolved_view']['sPhones']) > 0)
                                        <div class="space-y-1.5 mt-1">
                                            @foreach($suspect['resolved_view']['sPhones'] as $pIdx => $pItem)
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span class="text-[9px] uppercase px-1.5 py-0.5 rounded font-black tracking-wider {{ strtolower($pItem['label']) === 'primary' ? 'bg-blue-600 text-white shadow-2xs' : (strtolower($pItem['label']) === 'whatsapp' ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-slate-200 text-slate-800 border border-slate-300') }}">
                                                        {{ $pItem['label'] }}
                                                    </span>
                                                    <span class="font-bold text-slate-900 text-xs font-mono-code">{{ $pItem['number'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="font-bold text-slate-800 text-sm mt-0.5">{{ $suspect['resolved_view']['sPhone'] }}</div>
                                    @endif
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200" data-field-group="address" data-rollback-target="Residential Address">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">
                                        Residential Address / GPS</div>
                                    <div class="font-bold text-slate-800 text-sm mt-0.5">{{ $suspect['resolved_view']['sAddr'] }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Physical Descriptors & Arrest Details -->
                        <div class="space-y-3 pt-2">
                            <div
                                class="text-xs font-black text-slate-900 uppercase tracking-wider font-mono-code border-b border-slate-100 pb-1">
                                Physical Descriptors &amp; Arrest Details (Suspect #{{ $suspect['resolved_view']['sNum'] }})
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5 text-xs">
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200" data-field-group="gender" data-rollback-target="Gender">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Gender</div>
                                    <div class="font-bold text-slate-800 text-sm mt-0.5">
                                        {{ $suspect['resolved_view']['sGender'] ?: 'N/A' }}
                                    </div>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200" data-field-group="age" data-rollback-target="Age">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Age</div>
                                    <div class="font-bold text-slate-800 text-sm mt-0.5">
                                        {{ !empty($suspect['resolved_view']['sAge']) ? "{$suspect['resolved_view']['sAge']} yrs" : 'N/A' }}
                                    </div>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200" data-field-group="dob" data-rollback-target="Date of Birth">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Date of Birth (DOB)</div>
                                    <div class="font-bold text-slate-800 text-sm mt-0.5">
                                        {{ !empty($suspect['resolved_view']['sDOB']) ? (strtotime($suspect['resolved_view']['sDOB']) !== false ? date('M d, Y', strtotime($suspect['resolved_view']['sDOB'])) : $suspect['resolved_view']['sDOB']) : 'N/A' }}
                                    </div>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200" data-field-group="height" data-rollback-target="Height">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Height</div>
                                    <div class="font-bold text-slate-800 text-sm mt-0.5">
                                        {{ !empty($suspect['resolved_view']['sHeight']) ? $suspect['resolved_view']['sHeight'] : 'N/A' }}
                                    </div>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200" data-field-group="eye_color" data-rollback-target="Eye Color">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Eye Color</div>
                                    <div class="font-bold text-slate-800 text-sm mt-0.5">
                                        {{ !empty($suspect['resolved_view']['sEye']) ? $suspect['resolved_view']['sEye'] : 'N/A' }}
                                    </div>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200" data-field-group="arrest_date" data-rollback-target="Arrest Date & Time">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Arrest Date &amp; Time</div>
                                    @php
                                        $rawArrestDate = trim((string) ($suspect['resolved_view']['sArrestDate'] ?? ''));
                                        $rawArrestTime = trim((string) ($suspect['resolved_view']['sArrestTime'] ?? ''));
                                        $formattedArrestDate = null;
                                        if ($rawArrestDate !== '' && !in_array(strtoupper($rawArrestDate), ['N/A', 'NA', '-', 'NULL', 'UNSPECIFIED'], true)) {
                                            try {
                                                $formattedArrestDate = \Carbon\Carbon::parse($rawArrestDate)->format('M d, Y');
                                            } catch (\Throwable $e) {
                                                $formattedArrestDate = $rawArrestDate;
                                            }
                                        }
                                        $hasValidTime = $rawArrestTime !== '' && !in_array(strtoupper($rawArrestTime), ['N/A', 'NA', '-', 'NULL', 'UNSPECIFIED'], true);
                                        $formattedArrestTime = $rawArrestTime;
                                        if ($hasValidTime) {
                                            try {
                                                $formattedArrestTime = \Carbon\Carbon::parse($rawArrestTime)->format('g:i A');
                                            } catch (\Throwable $e) {
                                                $formattedArrestTime = $rawArrestTime;
                                            }
                                        }
                                    @endphp
                                    <div class="font-bold text-slate-800 text-sm mt-0.5">
                                        {{ $formattedArrestDate ?: 'N/A' }}
                                        {{ $hasValidTime ? "@ {$formattedArrestTime}" : '' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Family & Marital Records -->
                        <div class="space-y-3 pt-2">
                            <div
                                class="text-xs font-black text-slate-900 uppercase tracking-wider font-mono-code border-b border-slate-100 pb-1">
                                Family &amp; Relationship Records (Suspect #{{ $suspect['resolved_view']['sNum'] }})
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200" data-field-group="marital_status" data-rollback-target="Marital Status">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Marital
                                        / Relationship Status</div>
                                    <div class="font-bold text-slate-900 mt-0.5">{{ $suspect['resolved_view']['sMarital'] ?: 'N/A' }}</div>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200" data-field-group="spouse_name" data-rollback-target="Spouse / Partner Details">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Spouse /
                                        Partner Details</div>
                                    <div class="font-bold text-slate-900 mt-0.5">
                                        @if (!empty($suspect['resolved_view']['sSpouseN']))
                                            <span class="font-black text-slate-950">Spouse:
                                                {{ $suspect['resolved_view']['sSpouseN'] }}</span>
                                            @if (!empty($suspect['resolved_view']['sSpouseP']))
                                                <span
                                                    class="font-mono-code text-slate-700 font-semibold">({{ $suspect['resolved_view']['sSpouseP'] }})</span>
                                            @endif
                                            @if (!empty($suspect['resolved_view']['sSpouseI']))
                                                <span
                                                    class="text-slate-600 font-normal italic text-[11px] block mt-0.5">—
                                                    {{ $suspect['resolved_view']['sSpouseI'] }}</span>
                                            @endif
                                        @elseif(!empty($suspect['resolved_view']['sPartnerN']))
                                            <span class="font-black text-slate-950">Partner:
                                                {{ $suspect['resolved_view']['sPartnerN'] }}</span>
                                            @if (!empty($suspect['resolved_view']['sPartnerP']))
                                                <span
                                                    class="font-mono-code text-slate-700 font-semibold">({{ $suspect['resolved_view']['sPartnerP'] }})</span>
                                            @endif
                                            @if (!empty($suspect['resolved_view']['sPartnerI']))
                                                <span
                                                    class="text-slate-600 font-normal italic text-[11px] block mt-0.5">—
                                                    {{ $suspect['resolved_view']['sPartnerI'] }}</span>
                                            @endif
                                        @else
                                            <span class="text-slate-400 font-normal">{{ $suspect['resolved_view']['sSpouseI'] ?: 'N/A' }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200" data-field-group="children" data-rollback-target="Children & Dependents">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Children
                                        &amp; Dependents</div>
                                    <div class="font-bold text-slate-900 mt-0.5">
                                        {{ $suspect['resolved_view']['sDependents'] }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Offence Charges & Custody Allocation -->
                        <div class="space-y-3 pt-2">
                            <div
                                class="text-xs font-black text-slate-900 uppercase tracking-wider font-mono-code border-b border-slate-100 pb-1">
                                Offence Charges &amp; Custody Details (Suspect #{{ $suspect['resolved_view']['sNum'] }})
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1" data-field-group="raid_location" data-rollback-target="Raid Scene Location">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Raid
                                        Scene / Offence Location</div>
                                    <div class="font-extrabold text-slate-900 text-sm">{{ $suspect['resolved_view']['sRaid'] }}</div>
                                </div>
                                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1" data-field-group="arrest_date" data-rollback-target="Arrest Date & Time">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Arrest
                                        Date &amp; Time</div>
                                    <div class="font-bold text-slate-900 text-sm">
                                        {{ $formattedArrestDate ?: 'N/A' }}
                                        {{ $hasValidTime ? "@ {$rawArrestTime}" : '' }}
                                    </div>
                                </div>
                                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1" data-field-group="holding_facility" data-rollback-target="Custody Holding Facility">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Assigned
                                        Custody Holding Facility</div>
                                    <div class="font-bold text-slate-900 text-sm">{{ $suspect['resolved_view']['sFacility'] }}</div>
                                </div>
                            </div>
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1 text-xs" data-field-group="offense_description" data-rollback-target="Charge Description">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Brief Facts/
                                    Charge Description</div>
                                <p class="text-slate-800 leading-relaxed font-semibold">
                                    {{ $suspect['resolved_view']['sOffense'] }}
                                </p>
                            </div>
                        </div>

                        @if ($suspect['resolved_view']['isAssociate'])
                            
                            @if (!empty($suspect['resolved_view']['assocExhibits']) && count($suspect['resolved_view']['assocExhibits']) > 0)
                                <div
                                    class="p-5 bg-amber-50/50 border-2 border-amber-300 rounded-2xl space-y-4 shadow-sm">
                                    <div
                                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-amber-200">
                                        <div class="flex items-center space-x-2">
                                            <span
                                                class="px-2.5 py-1 rounded-md text-[10px] font-mono-code font-extrabold bg-amber-600 text-white uppercase tracking-wider shadow-2xs">
                                                {{ $suspect['resolved_view']['isCoSuspect'] ? 'CO-SUSPECT EXHIBIT DOSSIER' : 'ASSOCIATE EXHIBIT DOSSIER' }}
                                            </span>
                                            <div>
                                                <h3
                                                    class="text-sm font-black text-slate-900 flex items-center gap-1.5">
                                                    {{ $suspect['resolved_view']['isCoSuspect'] ? 'Co-Suspect Seizures & Exhibits' : 'Associated Seizures & Exhibits' }} — {{ $suspect['resolved_view']['sName'] }}
                                                </h3>
                                                <p class="text-[11px] text-slate-600">Confiscated items and exhibit
                                                    inventory attached to {{ $suspect['resolved_view']['isCoSuspect'] ? 'Co-Suspect' : 'Associate' }} {{ $suspect['resolved_view']['sName'] }}</p>
                                            </div>
                                        </div>
                                        <span
                                            class="px-2.5 py-1 rounded-full text-[10px] font-mono-code font-extrabold bg-amber-100 text-amber-900 border border-amber-300 w-max">
                                            {{ count($suspect['resolved_view']['assocExhibits']) }} EXHIBIT ITEM(S) LOGGED
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                                        @foreach ($suspect['resolved_view']['assocExhibits'] as $aEx)
                                            <div class="p-3 rounded-xl bg-white border border-amber-200 shadow-2xs">
                                                <div
                                                    class="text-[10px] font-mono-code font-bold text-amber-800 uppercase">
                                                    Exhibit Category</div>
                                                <div
                                                    class="font-extrabold text-amber-900 text-xs mt-0.5 font-mono-code">
                                                    {{ $aEx['category'] ?? ($aEx['exhibit_category'] ?? 'Associate Exhibit') }}
                                                </div>
                                            </div>
                                            @php
                                                $aExCatVal = $aEx['category'] ?? ($aEx['exhibit_category'] ?? '');
                                                $isMoneyExA = in_array(strtolower(trim((string)$aExCatVal)), ['money', 'currency', 'cash']);
                                                $rawSeizureNameA = $aEx['seizure_name'] ?? null;
                                                $rawCommodityNameA = $aEx['commodity_name'] ?? ($aEx['seizure_name_currency'] ?? null);
                                                if (!empty($rawCommodityNameA) && (empty($rawSeizureNameA) || in_array(strtolower(trim((string)$rawSeizureNameA)), ['exhibit', 'exhibits', 'general exhibit', 'confiscated exhibit']))) {
                                                    $displayNameA = $rawCommodityNameA;
                                                } else {
                                                    $displayNameA = (!empty($rawCommodityNameA) && !in_array(strtolower(trim((string)$rawCommodityNameA)), ['exhibit', 'exhibits']))
                                                        ? $rawCommodityNameA
                                                        : ($rawSeizureNameA ?? ($aEx['name'] ?? 'N/A'));
                                                }
                                            @endphp
                                            <div class="p-3 rounded-xl bg-white border border-amber-200 shadow-2xs">
                                                <div
                                                    class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">
                                                    Exhibit / Commodity Name</div>
                                                <div class="font-extrabold text-slate-900 text-xs mt-0.5">
                                                    {{ $displayNameA }}</div>
                                            </div>
                                            <div class="p-3 rounded-xl bg-white border border-amber-200 shadow-2xs">
                                                <div
                                                    class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">
                                                    {{ $isMoneyExA || !empty($aEx['amount']) ? 'Amount / Quantity' : 'Quantity / Weight' }}</div>
                                                <div
                                                    class="font-extrabold text-slate-900 font-mono-code text-xs mt-0.5">
                                                    @if ($isMoneyExA || !empty($aEx['amount']))
                                                        {{ !empty($aEx['amount']) ? "Amount: {$aEx['amount']}" : (!empty($aEx['seizure_quantity']) ? "Amount: {$aEx['seizure_quantity']}" : 'N/A') }}
                                                    @elseif (!empty($aEx['seizure_quantity']) || !empty($aEx['seizure_weight']))
                                                        {{ implode((request()->is('*/staff*') || request()->is('staff*') || request()->is('*field-officers*')) ? ' | ' : ' • ', array_filter([!empty($aEx['seizure_quantity']) ? "Qty: {$aEx['seizure_quantity']}" : null, !empty($aEx['seizure_weight']) ? "Wt: {$aEx['seizure_weight']} " . ($aEx['seizure_unit'] ?? '') : null])) }}
                                                    @else
                                                        N/A
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="p-3 rounded-xl bg-white border border-amber-200 shadow-2xs">
                                                <div
                                                    class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">
                                                    Estimated Valuation</div>
                                                                                                <div
                                                    class="font-extrabold text-emerald-700 font-mono-code text-xs mt-0.5">
                                                    {{ $aEx['resolved_assoc_view']['cleanValA'] }}</div>
                                            </div>
                                            @if (!empty($aEx['seizure_packaging']))
                                                <div
                                                    class="p-3 rounded-xl bg-white border border-amber-200 shadow-2xs col-span-1 sm:col-span-2">
                                                    <div
                                                        class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">
                                                        Packaging &amp; Format</div>
                                                    <div class="font-bold text-slate-900 text-xs mt-0.5">
                                                        {{ $aEx['seizure_packaging'] }}</div>
                                                </div>
                                            @endif

                                            @php
                                                $isLabAnalysisCase = isset($case) && (
                                                    str_contains(strtolower((string) ($case->unit_name ?? '')), 'lab')
                                                    || (is_object($case) && method_exists($case, 'isDrugCase') && $case->isDrugCase())
                                                );
                                            @endphp

                                            @if ($isLabAnalysisCase)
                                            {{-- 0. Source of Exhibits --}}
                                            @php
                                                $aSourceOfExhibits = $aEx['source_of_exhibits'] ?? ($aEx['source'] ?? null);
                                            @endphp
                                            <div class="p-3 rounded-xl bg-white border border-amber-200 shadow-2xs">
                                                <div class="text-[10px] font-mono-code font-bold text-amber-800 uppercase">
                                                    Source of Exhibits</div>
                                                <div class="font-bold text-slate-900 text-xs mt-0.5">
                                                    {{ !empty($aSourceOfExhibits) ? $aSourceOfExhibits : 'N/A' }}
                                                </div>
                                            </div>

                                            {{-- 1. Source/Requesting Unit --}}
                                            @php
                                                $aSourceReqUnit = $aEx['source_requesting_unit'] ?? ($aEx['requesting_unit'] ?? ($aEx['source_unit'] ?? ($aEx['source_of_exhibits'] ?? ($aEx['source'] ?? ($case->source_requesting_unit ?? ($case->requesting_unit ?? ($case->unit_name ?? null)))))));
                                            @endphp
                                            <div class="p-3 rounded-xl bg-white border border-amber-200 shadow-2xs">
                                                <div class="text-[10px] font-mono-code font-bold text-amber-800 uppercase">
                                                    Source/Requesting Unit</div>
                                                <div class="font-bold text-slate-900 text-xs mt-0.5">
                                                    {{ !empty($aSourceReqUnit) ? $aSourceReqUnit : 'N/A' }}
                                                </div>
                                            </div>

                                            {{-- 2. Investigating Officer --}}
                                            @php
                                                $aInvOfficer = !empty($aEx['investigating_officer']) && $aEx['investigating_officer'] !== 'N/A'
                                                    ? $aEx['investigating_officer']
                                                    : (!empty($aEx['investigation_officer']) && $aEx['investigation_officer'] !== 'N/A'
                                                        ? $aEx['investigation_officer']
                                                        : null);

                                                if (empty($aInvOfficer) && !empty($aEx['id'])) {
                                                    try { $aInvOfficer = \App\Models\Exhibit::where('id', $aEx['id'])->value('investigating_officer'); } catch (\Throwable $e) {}
                                                }
                                                if (empty($aInvOfficer) && !empty($aEx['linked_exhibit_id'])) {
                                                    try { $aInvOfficer = \App\Models\Exhibit::where('id', $aEx['linked_exhibit_id'])->value('investigating_officer'); } catch (\Throwable $e) {}
                                                }
                                                if (empty($aInvOfficer) && !empty($aEx['exhibit_number'])) {
                                                    try { $aInvOfficer = \App\Models\Exhibit::where('exhibit_number', $aEx['exhibit_number'])->value('investigating_officer'); } catch (\Throwable $e) {}
                                                }
                                                if (empty($aInvOfficer) && isset($case->exhibits) && $case->exhibits instanceof \Illuminate\Support\Collection) {
                                                    $mEx = $case->exhibits->first(function ($e) use ($aEx) {
                                                        return (!empty($aEx['id']) && (int)$e->id === (int)$aEx['id'])
                                                            || (!empty($aEx['exhibit_number']) && (string)$e->exhibit_number === (string)$aEx['exhibit_number'])
                                                            || (!empty($aEx['commodity_name']) && strtolower(trim((string)$e->commodity_name)) === strtolower(trim((string)$aEx['commodity_name'])))
                                                            || (!empty($aEx['seizure_name']) && strtolower(trim((string)$e->commodity_name)) === strtolower(trim((string)$aEx['seizure_name'])));
                                                    });
                                                    if ($mEx && !empty($mEx->investigating_officer) && $mEx->investigating_officer !== 'N/A') {
                                                        $aInvOfficer = $mEx->investigating_officer;
                                                    }
                                                }
                                                if (empty($aInvOfficer) && !empty($case->id)) {
                                                    $uId = isset($case->underlyingRecord) ? $case->underlyingRecord->id : $case->id;
                                                    try {
                                                        $mDb = \App\Models\Exhibit::where(function ($q) use ($case, $uId) {
                                                            $q->where('single_suspect_case_id', $case->id)
                                                                ->orWhere('multiple_suspect_case_id', $case->id)
                                                                ->orWhere('single_suspect_case_id', $uId)
                                                                ->orWhere('multiple_suspect_case_id', $uId);
                                                        })->where(function ($q) use ($aEx) {
                                                            if (!empty($aEx['id'])) $q->orWhere('id', $aEx['id']);
                                                            if (!empty($aEx['exhibit_number'])) $q->orWhere('exhibit_number', $aEx['exhibit_number']);
                                                            if (!empty($aEx['commodity_name'])) $q->orWhere('commodity_name', $aEx['commodity_name']);
                                                            if (!empty($aEx['seizure_name'])) $q->orWhere('commodity_name', $aEx['seizure_name']);
                                                        })->whereNotNull('investigating_officer')->value('investigating_officer');
                                                        if (!empty($mDb) && $mDb !== 'N/A') {
                                                            $aInvOfficer = $mDb;
                                                        }
                                                    } catch (\Throwable $e) {}
                                                }
                                                if (empty($aInvOfficer) && !empty($case->id)) {
                                                    $uId = isset($case->underlyingRecord) ? $case->underlyingRecord->id : $case->id;
                                                    try {
                                                        $firstCaseInv = \App\Models\Exhibit::where(function ($q) use ($case, $uId) {
                                                            $q->where('single_suspect_case_id', $case->id)
                                                                ->orWhere('multiple_suspect_case_id', $case->id)
                                                                ->orWhere('single_suspect_case_id', $uId)
                                                                ->orWhere('multiple_suspect_case_id', $uId);
                                                        })->whereNotNull('investigating_officer')->value('investigating_officer');
                                                        if (!empty($firstCaseInv) && $firstCaseInv !== 'N/A') {
                                                            $aInvOfficer = $firstCaseInv;
                                                        }
                                                    } catch (\Throwable $e) {}
                                                }
                                                if (empty($aInvOfficer)) {
                                                    $aInvOfficer = $case->investigating_officer
                                                        ?? ($case->docket_data['investigating_officer']
                                                        ?? ($payload['investigating_officer']
                                                        ?? ($case->docket_data['seizures'][0]['investigating_officer'] ?? 'N/A')));
                                                }
                                                $aInvOfficer = !empty($aInvOfficer) ? $aInvOfficer : 'N/A';
                                            @endphp
                                            <div class="p-3 rounded-xl bg-white border border-amber-200 shadow-2xs">
                                                <div class="text-[10px] font-mono-code font-bold text-amber-800 uppercase">
                                                    Investigating Officer</div>
                                                <div class="font-bold text-slate-900 text-xs mt-0.5">
                                                    {{ !empty($aInvOfficer) ? $aInvOfficer : 'N/A' }}
                                                </div>
                                            </div>
                                            @else
                                            {{-- Location Seized (Digital Forensics, Crime Scene Support, Evidence Management) --}}
                                            @php
                                                $aLocSeized = $aEx['seizure_location'] ?? ($aEx['location_seized'] ?? ($aEx['location'] ?? ($case->raid_location ?? null)));
                                            @endphp
                                            <div class="p-3 rounded-xl bg-white border border-amber-200 shadow-2xs">
                                                <div class="text-[10px] font-mono-code font-bold text-amber-800 uppercase">
                                                    Location Seized</div>
                                                <div class="font-bold text-slate-900 text-xs mt-0.5">
                                                    {{ !empty($aLocSeized) ? $aLocSeized : 'N/A' }}
                                                </div>
                                            </div>
                                            @endif

                                            {{-- 3. Reason for Seizure --}}
                                            @php
                                                $aSeizureReason = $aEx['reason_for_seizure'] ?? ($aEx['seizure_reason'] ?? ($aEx['reason'] ?? ($case->reason_for_seizure ?? ($case->seizure_reason ?? null))));
                                            @endphp
                                            <div class="p-3 rounded-xl bg-white border border-amber-200 shadow-2xs col-span-1 sm:col-span-2">
                                                <div class="text-[10px] font-mono-code font-bold text-amber-800 uppercase">
                                                    Reason for Seizure</div>
                                                <div class="font-bold text-slate-900 text-xs mt-0.5 leading-relaxed">
                                                    {{ !empty($aSeizureReason) ? $aSeizureReason : 'N/A' }}
                                                </div>
                                            </div>

                                            {{-- 4. Concealment & Packaging Notes --}}
                                            @php
                                                $aPkgVal = $aEx['seizure_packaging'] ?? ($aEx['packaging'] ?? ($aEx['packaging_type'] ?? null));
                                                $aConcealVal = $aEx['seizure_concealment'] ?? ($aEx['concealment_method'] ?? ($aEx['concealment'] ?? ($aEx['packaging_notes'] ?? null)));
                                                $aConcealAndPkgNotes = implode(' — ', array_filter([$aPkgVal, $aConcealVal]));
                                                if (empty($aConcealAndPkgNotes)) {
                                                    $aConcealAndPkgNotes = $aConcealVal ?: ($aPkgVal ?: 'N/A');
                                                }
                                            @endphp
                                            <div class="p-3 rounded-xl bg-white border border-amber-200 shadow-2xs col-span-1 sm:col-span-2">
                                                <div class="text-[10px] font-mono-code font-bold text-amber-800 uppercase">
                                                    Concealment &amp; Packaging Notes</div>
                                                <div class="font-bold text-slate-900 text-xs mt-0.5 leading-relaxed">
                                                    {{ $aConcealAndPkgNotes }}
                                                </div>
                                            </div>

                                            {{-- 5. Remarks / Observations --}}
                                            @php
                                                $aRemarksObs = $aEx['exhibit_remark'] ?? ($aEx['remarks'] ?? ($aEx['observations'] ?? ($aEx['remarks_observations'] ?? ($aEx['seizure_remarks'] ?? ($aEx['notes'] ?? ($case->remarks ?? null))))));
                                            @endphp
                                            <div class="p-3 rounded-xl bg-white border border-amber-200 shadow-2xs col-span-1 sm:col-span-2">
                                                <div class="text-[10px] font-mono-code font-bold text-amber-800 uppercase">
                                                    Remarks / Observations</div>
                                                <div class="font-bold text-slate-900 text-xs mt-0.5 leading-relaxed whitespace-pre-line">
                                                    {{ !empty($aRemarksObs) ? $aRemarksObs : 'N/A' }}
                                                </div>
                                            </div>

                                            @if (!empty($aEx['other_exhibit_description']) || !empty($aEx['other_description']))
                                                <div
                                                    class="p-3 rounded-xl bg-white border border-amber-200 shadow-2xs col-span-full">
                                                    <div
                                                        class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">
                                                        Other Exhibit Description / Specifications</div>
                                                    <div
                                                        class="font-extrabold text-slate-900 text-xs mt-0.5 whitespace-pre-line">
                                                        {{ $aEx['other_exhibit_description'] ?? $aEx['other_description'] }}
                                                    </div>
                                                </div>
                                            @endif

                                                                                        @if (!empty($aEx['resolved_assoc_view']['exPhotosFormatted']) && count($aEx['resolved_assoc_view']['exPhotosFormatted']) > 0)
                                                <div
                                                    class="p-3 rounded-xl bg-white border border-amber-200 shadow-2xs col-span-full space-y-2">
                                                    <div class="flex items-center justify-between">
                                                        <span
                                                            class="text-[10px] font-mono-code font-bold text-amber-800 uppercase">Exhibit
                                                            Visual Evidence Photo(s)</span>
                                                        <span
                                                            class="text-[9px] font-mono-code bg-amber-100 text-amber-800 px-2 py-0.5 rounded font-extrabold border border-amber-300">{{ count($aEx['resolved_assoc_view']['exPhotosFormatted']) }}
                                                            Attached</span>
                                                    </div>
                                                    <div class="flex flex-wrap gap-2">
                                                        @foreach ($aEx['resolved_assoc_view']['exPhotosFormatted'] as $epIdx => $epFormatted)
                                                                                                                        <div class="w-16 h-16 rounded-lg overflow-hidden border border-slate-200 shadow-2xs cursor-pointer group relative bg-slate-100"
                                                                onclick="openMugshotModal('{{ $epFormatted }}', 'Exhibit Photo #{{ $epIdx + 1 }}')">
                                                                <img src="{{ $epFormatted }}"
                                                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform"
                                                                    alt="Exhibit Photo"
                                                                    onerror="if(!this.dataset.triedStorage &amp;&amp; !this.src.startsWith('data:')){this.dataset.triedStorage=1; this.src='{{ asset('storage/') }}/' + this.src.split('/').pop();}">
                                                                <div
                                                                    class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                                                    <svg class="w-4 h-4 text-white" fill="none"
                                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                                    </svg>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            
                                            @if (!empty($aEx['resolved_assoc_view']['isVehEx']))
                                                                                                <div
                                                    class="col-span-full bg-white text-slate-800 p-5 rounded-2xl border border-slate-200/90 space-y-5 mt-2 shadow-xs">
                                                    {{-- Header bar --}}
                                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                                                        <div class="flex items-center flex-wrap gap-2">
                                                            <span
                                                                class="px-2.5 py-1 bg-blue-50 text-blue-700 border border-blue-200 font-mono-code font-extrabold text-[10px] rounded-md uppercase tracking-wider">
                                                                VEHICLE / VESSEL SPECIFIC DETAILS
                                                            </span>
                                                            <span
                                                                class="text-sm font-black text-slate-900 font-mono-code tracking-tight">
                                                                {{ strtoupper($aEx['resolved_assoc_view']['vMakeModelFull']) }}
                                                            </span>
                                                        </div>
                                                        <div class="flex items-center flex-wrap gap-2">
                                                            @if (!empty($aEx['resolved_assoc_view']['vCondition']))
                                                                <span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 border border-emerald-200 px-2.5 py-1 rounded-md font-extrabold">
                                                                    {{ $aEx['resolved_assoc_view']['vCondition'] }}
                                                                </span>
                                                            @endif
                                                            @if (!empty($aEx['resolved_assoc_view']['vKeysRecv']))
                                                                <span class="text-[10px] font-mono-code bg-blue-50 text-blue-800 border border-blue-200 px-2.5 py-1 rounded-md font-extrabold">
                                                                    Keys: {{ $aEx['resolved_assoc_view']['vKeysRecv'] }} {{ !empty($aEx['resolved_assoc_view']['vKeyCount']) ? "({$aEx['resolved_assoc_view']['vKeyCount']})" : '' }}
                                                                </span>
                                                            @endif
                                                            @if (!empty($aEx['resolved_assoc_view']['vRegNo']) && $aEx['resolved_assoc_view']['vRegNo'] !== 'N/A')
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-amber-50 text-amber-900 border border-amber-200 px-2.5 py-1 rounded-md font-extrabold">
                                                                    REG #: {{ strtoupper($aEx['resolved_assoc_view']['vRegNo']) }}
                                                                </span>
                                                            @endif
                                                            @if (!empty($aEx['resolved_assoc_view']['vVin']) && $aEx['resolved_assoc_view']['vVin'] !== 'N/A')
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-slate-100 text-slate-800 border border-slate-300 px-2.5 py-1 rounded-md font-extrabold">
                                                                    VIN: {{ strtoupper($aEx['resolved_assoc_view']['vVin']) }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    {{-- Section 1: Vehicle Specs & Identification --}}
                                                    <div class="space-y-2.5">
                                                        <div class="flex items-center gap-2">
                                                            <span class="w-1.5 h-3.5 bg-blue-600 rounded-full"></span>
                                                            <span class="text-[11px] font-mono-code font-extrabold uppercase tracking-wider text-slate-800">
                                                                Vehicle Identification &amp; Technical Specs
                                                            </span>
                                                        </div>
                                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                                                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                                                <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                                                    MAKE / MODEL / TRIM
                                                                </div>
                                                                <div class="font-extrabold text-slate-900 text-xs mt-1">
                                                                    {{ $aEx['resolved_assoc_view']['vMakeModelFull'] }}
                                                                </div>
                                                            </div>
                                                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                                                <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                                                    REGISTRATION NUMBER
                                                                </div>
                                                                <div class="font-extrabold text-amber-700 font-mono-code text-xs mt-1">
                                                                    {{ $aEx['resolved_assoc_view']['vRegNo'] }}
                                                                </div>
                                                            </div>
                                                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                                                <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                                                    VIN / CHASSIS NUMBER
                                                                </div>
                                                                <div class="font-extrabold text-blue-700 font-mono-code text-xs mt-1">
                                                                    {{ $aEx['resolved_assoc_view']['vVin'] }}
                                                                </div>
                                                            </div>
                                                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                                                <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                                                    COLOUR / FINISH
                                                                </div>
                                                                <div class="font-bold text-slate-900 text-xs mt-1">
                                                                    {{ $aEx['resolved_assoc_view']['vColor'] }}
                                                                </div>
                                                            </div>
                                                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                                                <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                                                    VEHICLE TYPE / BODY
                                                                </div>
                                                                <div class="font-bold text-slate-900 text-xs mt-1">
                                                                    {{ $aEx['resolved_assoc_view']['vType'] }}
                                                                </div>
                                                            </div>
                                                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                                                <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                                                    MANUFACTURE YEAR
                                                                </div>
                                                                <div class="font-bold text-slate-900 text-xs mt-1">
                                                                    {{ $aEx['resolved_assoc_view']['vYear'] }}
                                                                </div>
                                                            </div>
                                                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                                                <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                                                    ENGINE NUMBER
                                                                </div>
                                                                <div class="font-bold text-slate-900 font-mono-code text-xs mt-1">
                                                                    {{ $aEx['resolved_assoc_view']['vEngine'] }}
                                                                </div>
                                                            </div>
                                                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                                                <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                                                    REGISTERED COUNTRY
                                                                </div>
                                                                <div class="font-bold text-slate-900 text-xs mt-1">
                                                                    {{ $aEx['resolved_assoc_view']['vCountry'] }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Section 2: Ownership & Custody --}}
                                                    <div class="space-y-2.5 pt-3 border-t border-slate-100">
                                                        <div class="flex items-center gap-2">
                                                            <span class="w-1.5 h-3.5 bg-indigo-600 rounded-full"></span>
                                                            <span class="text-[11px] font-mono-code font-extrabold uppercase tracking-wider text-slate-800">
                                                                Ownership &amp; Custody Details
                                                            </span>
                                                        </div>
                                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                                                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                                                <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                                                    DOCUMENTED OWNER
                                                                </div>
                                                                <div class="font-extrabold text-slate-900 text-xs mt-1">
                                                                    {{ $aEx['resolved_assoc_view']['vOwner'] }}
                                                                </div>
                                                            </div>
                                                            @if (!empty($aEx['resolved_assoc_view']['vOwnerContact']))
                                                                <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                                                    <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                                                        OWNER CONTACT PHONE
                                                                    </div>
                                                                    <div class="font-bold text-slate-900 text-xs mt-1 font-mono-code">
                                                                        {{ $aEx['resolved_assoc_view']['vOwnerContact'] }}
                                                                    </div>
                                                                </div>
                                                            @endif
                                                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                                                <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                                                    DRIVER AT ARREST
                                                                </div>
                                                                <div class="font-bold text-slate-900 text-xs mt-1">
                                                                    {{ $aEx['resolved_assoc_view']['vDriver'] }}
                                                                </div>
                                                            </div>
                                                            @if (!empty($aEx['resolved_assoc_view']['vDocsAvailable']) || !empty($aEx['resolved_assoc_view']['vDocRef']))
                                                                <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                                                    <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                                                        VEHICLE DOCS &amp; LOG BOOK
                                                                    </div>
                                                                    <div class="font-bold text-blue-800 text-xs mt-1 font-mono-code">
                                                                        {{ $aEx['resolved_assoc_view']['vDocsAvailable'] ?: 'Available' }} {{ !empty($aEx['resolved_assoc_view']['vDocRef']) ? "(Ref: {$aEx['resolved_assoc_view']['vDocRef']})" : '' }}
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    {{-- Section 3: Impound & Seizure Record --}}
                                                    <div class="space-y-2.5 pt-3 border-t border-slate-100">
                                                        <div class="flex items-center gap-2">
                                                            <span class="w-1.5 h-3.5 bg-emerald-600 rounded-full"></span>
                                                            <span class="text-[11px] font-mono-code font-extrabold uppercase tracking-wider text-slate-800">
                                                                Impound, Custody &amp; Physical Condition
                                                            </span>
                                                        </div>
                                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                                                            @if (!empty($aEx['resolved_assoc_view']['vCondition']))
                                                                <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                                                    <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                                                        OVERALL CONDITION
                                                                    </div>
                                                                    <div class="font-bold text-slate-900 text-xs mt-1">
                                                                        {{ $aEx['resolved_assoc_view']['vCondition'] }}
                                                                    </div>
                                                                </div>
                                                            @endif
                                                            @if (!empty($aEx['resolved_assoc_view']['vFuel']))
                                                                <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                                                    <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                                                        FUEL LEVEL AT IMPOUND
                                                                    </div>
                                                                    <div class="font-bold text-amber-800 text-xs mt-1">
                                                                        {{ $aEx['resolved_assoc_view']['vFuel'] }}
                                                                    </div>
                                                                </div>
                                                            @endif
                                                            @if (!empty($aEx['resolved_assoc_view']['vKeysRecv']))
                                                                <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                                                    <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                                                        KEYS RECEIVED
                                                                    </div>
                                                                    <div class="font-bold text-slate-900 text-xs mt-1">
                                                                        {{ $aEx['resolved_assoc_view']['vKeysRecv'] }} {{ !empty($aEx['resolved_assoc_view']['vKeyCount']) ? "— {$aEx['resolved_assoc_view']['vKeyCount']}" : '' }}
                                                                    </div>
                                                                </div>
                                                            @endif
                                                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                                                <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                                                    CONFISCATION TIMESTAMP
                                                                </div>
                                                                <div class="font-bold text-slate-900 text-xs mt-1 font-mono-code">
                                                                    {{ $aEx['resolved_assoc_view']['vDateTime'] }}
                                                                </div>
                                                            </div>
                                                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                                                <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                                                    SEIZING OFFICER
                                                                </div>
                                                                <div class="font-bold text-slate-900 text-xs mt-1">
                                                                    {{ $aEx['resolved_assoc_view']['vOfficer'] }}
                                                                </div>
                                                            </div>
                                                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors {{ empty($aEx['resolved_assoc_view']['vCondition']) || empty($aEx['resolved_assoc_view']['vFuel']) || empty($aEx['resolved_assoc_view']['vKeysRecv']) ? '' : 'col-span-2' }}">
                                                                <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                                                    SEIZURE LOCATION
                                                                </div>
                                                                <div class="font-bold text-slate-900 text-xs mt-1">
                                                                    {{ $aEx['resolved_assoc_view']['vLoc'] }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Section 4: Physical Inspection & Special Disclosures --}}
                                                    @if (!empty($aEx['resolved_assoc_view']['vDamage']) || !empty($aEx['resolved_assoc_view']['vMods']) || !empty($aEx['resolved_assoc_view']['vFeatures']) || (!empty($aEx['resolved_assoc_view']['vReason']) && $aEx['resolved_assoc_view']['vReason'] !== 'N/A'))
                                                        <div class="space-y-2.5 pt-3 border-t border-slate-100">
                                                            <div class="flex items-center gap-2">
                                                                <span class="w-1.5 h-3.5 bg-slate-600 rounded-full"></span>
                                                                <span class="text-[11px] font-mono-code font-extrabold uppercase tracking-wider text-slate-800">
                                                                    Forensic Inspection &amp; Special Disclosures
                                                                </span>
                                                            </div>
                                                            <div class="grid grid-cols-1 gap-2.5">
                                                                @if (!empty($aEx['resolved_assoc_view']['vDamage']))
                                                                    <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                                                        <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                                                            RECORDED VEHICLE DAMAGE / DEFECTS
                                                                        </div>
                                                                        <div class="font-bold text-slate-900 text-xs mt-1 leading-relaxed">
                                                                            {{ $aEx['resolved_assoc_view']['vDamage'] }}
                                                                        </div>
                                                                    </div>
                                                                @endif

                                                                @if (!empty($aEx['resolved_assoc_view']['vMods']))
                                                                    <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                                                        <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                                                            MODIFICATIONS / CUSTOM TRAP COMPARTMENTS
                                                                        </div>
                                                                        <div class="font-bold text-slate-900 text-xs mt-1 leading-relaxed">
                                                                            {{ $aEx['resolved_assoc_view']['vMods'] }}
                                                                        </div>
                                                                    </div>
                                                                @endif

                                                                @if (!empty($aEx['resolved_assoc_view']['vFeatures']))
                                                                    <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                                                        <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                                                            NOTABLE IDENTIFYING FEATURES
                                                                        </div>
                                                                        <div class="font-bold text-slate-900 text-xs mt-1 leading-relaxed">
                                                                            {{ $aEx['resolved_assoc_view']['vFeatures'] }}
                                                                        </div>
                                                                    </div>
                                                                @endif

                                                                @if (!empty($aEx['resolved_assoc_view']['vReason']) && $aEx['resolved_assoc_view']['vReason'] !== 'N/A')
                                                                    <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                                                        <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                                                            CONFISCATION BASIS / REASON
                                                                        </div>
                                                                        <div class="font-bold text-slate-900 text-xs mt-1">
                                                                            {{ $aEx['resolved_assoc_view']['vReason'] }}
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endif

                                                    {{-- Section 5: Evidence Photos --}}
                                                    @if (!empty($aEx['resolved_assoc_view']['vPhotosFormatted']) && count($aEx['resolved_assoc_view']['vPhotosFormatted']) > 0)
                                                        <div class="pt-3 border-t border-slate-200 space-y-2.5">
                                                            <div class="flex items-center justify-between">
                                                                <div class="flex items-center gap-2">
                                                                    <span class="w-1.5 h-3.5 bg-blue-500 rounded-full"></span>
                                                                    <span class="text-[11px] font-mono-code font-extrabold text-slate-800 uppercase tracking-wider">
                                                                        Vehicle Evidence Photos
                                                                    </span>
                                                                </div>
                                                                <span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 border border-blue-200 px-2.5 py-0.5 rounded font-extrabold">
                                                                    {{ count($aEx['resolved_assoc_view']['vPhotosFormatted']) }} Photo(s) Attached
                                                                </span>
                                                            </div>
                                                            <div class="flex flex-wrap gap-2.5">
                                                                @foreach ($aEx['resolved_assoc_view']['vPhotosFormatted'] as $vpIdx => $vpFormatted)
                                                                                                                                        <div class="w-20 h-20 rounded-xl overflow-hidden border border-slate-200 bg-slate-100 cursor-pointer group relative shadow-2xs hover:shadow-md hover:border-blue-400 transition-all"
                                                                        onclick="openMugshotModal('{{ $vpFormatted }}', 'Vehicle Photo #{{ $vpIdx + 1 }} — {{ $aEx['resolved_assoc_view']['vMakeModelFull'] }}')">
                                                                        <img src="{{ $vpFormatted }}"
                                                                            class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                                                                            alt="Vehicle Photo"
                                                                            onerror="if(!this.dataset.triedStorage &amp;&amp; !this.src.startsWith('data:')){this.dataset.triedStorage=1; this.src='{{ asset('storage/') }}/' + this.src.split('/').pop();}">
                                                                        <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                                            </svg>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif

                                            @if (!empty($aEx['resolved_assoc_view']['isWeaponEx']))
                                                                                                <div
                                                    class="col-span-full bg-slate-900 text-white p-4 rounded-xl border border-red-500/40 space-y-3 mt-2 shadow-md">
                                                    <div
                                                        class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-800 pb-2">
                                                        <div class="flex items-center space-x-2">
                                                            <span
                                                                class="px-2 py-0.5 bg-red-600 text-white font-mono-code font-extrabold text-[10px] rounded uppercase tracking-wider">FIREARM
                                                                / WEAPON SPECIFIC DETAILS</span>
                                                            <span
                                                                class="text-xs font-black text-slate-200">{{ $aEx['resolved_assoc_view']['wModel'] }}</span>
                                                        </div>
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            @if (!empty($aEx['resolved_assoc_view']['wSerial']))
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-slate-800 px-2.5 py-1 rounded text-amber-400 font-extrabold border border-amber-500/30">SERIAL
                                                                    #: {{ $aEx['resolved_assoc_view']['wSerial'] }}</span>
                                                            @endif
                                                            @if (!empty($aEx['resolved_assoc_view']['wLoaded']))
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-slate-800 px-2.5 py-1 rounded text-red-400 font-extrabold border border-red-500/30">STATUS:
                                                                    {{ strtoupper($aEx['resolved_assoc_view']['wLoaded']) }}</span>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                                                        @if (!empty($aEx['resolved_assoc_view']['wModel']))
                                                            <div
                                                                class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                                <div
                                                                    class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                                                    MODEL / TYPE</div>
                                                                <div class="font-bold text-white text-xs mt-0.5">
                                                                    {{ $aEx['resolved_assoc_view']['wModel'] }}</div>
                                                            </div>
                                                        @endif
                                                        @if (!empty($aEx['resolved_assoc_view']['wCalibre']))
                                                            <div
                                                                class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                                <div
                                                                    class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                                                    CALIBRE / GAUGE</div>
                                                                <div
                                                                    class="font-extrabold text-amber-400 font-mono-code text-xs mt-0.5">
                                                                    {{ $aEx['resolved_assoc_view']['wCalibre'] }}</div>
                                                            </div>
                                                        @endif
                                                        @if (!empty($aEx['resolved_assoc_view']['wColour']))
                                                            <div
                                                                class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                                <div
                                                                    class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                                                    COLOUR / FINISH</div>
                                                                <div class="font-bold text-slate-200 text-xs mt-0.5">
                                                                    {{ $aEx['resolved_assoc_view']['wColour'] }}</div>
                                                            </div>
                                                        @endif
                                                        @if (!empty($aEx['resolved_assoc_view']['wSize']))
                                                            <div
                                                                class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                                <div
                                                                    class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                                                    SIZE / LENGTH</div>
                                                                <div class="font-bold text-slate-200 text-xs mt-0.5">
                                                                    {{ $aEx['resolved_assoc_view']['wSize'] }}</div>
                                                            </div>
                                                        @endif
                                                        @if (!empty($aEx['resolved_assoc_view']['wLoaded']))
                                                            <div
                                                                class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                                <div
                                                                    class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                                                    LOADED STATUS</div>
                                                                <div
                                                                    class="font-extrabold text-red-400 text-xs mt-0.5">
                                                                    {{ $aEx['resolved_assoc_view']['wLoaded'] }}</div>
                                                            </div>
                                                        @endif
                                                        @if (!empty($aEx['resolved_assoc_view']['wCondition']))
                                                            <div
                                                                class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                                <div
                                                                    class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                                                    CONDITION AT RECOVERY</div>
                                                                <div
                                                                    class="font-extrabold text-emerald-400 text-xs mt-0.5">
                                                                    {{ $aEx['resolved_assoc_view']['wCondition'] }}</div>
                                                            </div>
                                                        @endif
                                                        @if (!empty($aEx['resolved_assoc_view']['wLocation']))
                                                            <div
                                                                class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700 col-span-2">
                                                                <div
                                                                    class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                                                    EXACT LOCATION FOUND</div>
                                                                <div class="font-bold text-slate-100 text-xs mt-0.5">
                                                                    {{ $aEx['resolved_assoc_view']['wLocation'] }}</div>
                                                            </div>
                                                        @endif
                                                        @if (!empty($aEx['resolved_assoc_view']['wMarks']))
                                                            <div
                                                                class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700 col-span-full">
                                                                <div
                                                                    class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                                                    DISTINGUISHING MARKS / ENGRAVINGS</div>
                                                                <div class="font-bold text-slate-100 text-xs mt-0.5">
                                                                    {{ $aEx['resolved_assoc_view']['wMarks'] }}</div>
                                                            </div>
                                                        @endif
                                                        @if (!empty($aEx['resolved_assoc_view']['wPhotosFormatted']) && count($aEx['resolved_assoc_view']['wPhotosFormatted']) > 0)
                                                            <div class="col-span-full pt-2 border-t border-slate-800 space-y-2">
                                                                <div class="flex items-center justify-between">
                                                                    <span class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">WEAPON EVIDENCE PHOTOS</span>
                                                                    <span class="text-[9px] font-mono-code bg-red-900/60 text-red-300 border border-red-500/30 px-2 py-0.5 rounded font-extrabold">{{ count($aEx['resolved_assoc_view']['wPhotosFormatted']) }} Photo(s) Attached</span>
                                                                </div>
                                                                <div class="flex flex-wrap gap-2">
                                                                    @foreach ($aEx['resolved_assoc_view']['wPhotosFormatted'] as $wpIdx => $wpFormatted)
                                                                                                                                                <div class="w-16 h-16 rounded-lg overflow-hidden border border-slate-700 shadow-2xs cursor-pointer group relative bg-slate-800"
                                                                            onclick="openMugshotModal('{{ $wpFormatted }}', 'Weapon Photo #{{ $wpIdx + 1 }} — {{ $aEx['resolved_assoc_view']['wModel'] }}')">
                                                                            <img src="{{ $wpFormatted }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform" alt="Weapon Photo">
                                                                            <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif

                                            @if (!empty($aEx['resolved_assoc_view']['isDevEx']))
                                                                                                <div
                                                    class="col-span-full bg-slate-900 text-white p-4 rounded-xl border border-blue-500/40 space-y-3 mt-2 shadow-md">
                                                    <div
                                                        class="flex items-center justify-between border-b border-slate-800 pb-2">
                                                        <div class="flex items-center space-x-2">
                                                            <span
                                                                class="px-2 py-0.5 bg-blue-600 text-white font-mono-code font-extrabold text-[10px] rounded uppercase tracking-wider">ELECTRONIC DEVICE FORENSIC SPECIFICATIONS</span>
                                                            <span
                                                                class="text-xs font-black text-slate-100 font-mono-code">{{ strtoupper($aEx['resolved_assoc_view']['dModel'] !== 'N/A' ? $aEx['resolved_assoc_view']['dModel'] : $aEx['seizure_name'] ?? ($aEx['commodity_name'] ?? 'Electronic Device Exhibit')) }}</span>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            @if (!empty($aEx['resolved_assoc_view']['dSerial']) && $aEx['resolved_assoc_view']['dSerial'] !== 'N/A')
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-slate-800 px-2.5 py-1 rounded text-amber-400 font-extrabold border border-amber-500/30">SERIAL
                                                                    #: {{ strtoupper($aEx['resolved_assoc_view']['dSerial']) }}</span>
                                                            @endif
                                                            @if (!empty($aEx['resolved_assoc_view']['dImei1']) && $aEx['resolved_assoc_view']['dImei1'] !== 'N/A')
                                                                <span
                                                                    class="text-[10px] font-mono-code bg-slate-800 px-2.5 py-1 rounded text-blue-300 font-extrabold border border-blue-500/30">IMEI
                                                                    1: {{ strtoupper($aEx['resolved_assoc_view']['dImei1']) }}</span>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                                                        <div
                                                            class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                            <div
                                                                class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                                                MODEL / MAKE</div>
                                                            <div class="font-black text-white text-xs mt-0.5">
                                                                {{ $aEx['resolved_assoc_view']['dModel'] }}</div>
                                                        </div>

                                                        <div
                                                            class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                            <div
                                                                class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                                                SERIAL NUMBER</div>
                                                            <div
                                                                class="font-extrabold text-amber-400 font-mono-code text-xs mt-0.5">
                                                                {{ $aEx['resolved_assoc_view']['dSerial'] }}</div>
                                                        </div>

                                                        <div
                                                            class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                            <div
                                                                class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                                                IMEI 1</div>
                                                            <div
                                                                class="font-extrabold text-blue-300 font-mono-code text-xs mt-0.5">
                                                                {{ $aEx['resolved_assoc_view']['dImei1'] }}</div>
                                                        </div>

                                                        <div
                                                            class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                            <div
                                                                class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                                                IMEI 2</div>
                                                            <div
                                                                class="font-extrabold text-blue-300 font-mono-code text-xs mt-0.5">
                                                                {{ $aEx['resolved_assoc_view']['dImei2'] }}</div>
                                                        </div>

                                                        <div
                                                            class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                            <div
                                                                class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                                                MAC ADDRESS</div>
                                                            <div
                                                                class="font-mono-code font-bold text-slate-200 text-xs mt-0.5">
                                                                {{ $aEx['resolved_assoc_view']['dMac'] }}</div>
                                                        </div>

                                                        <div
                                                            class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                            <div
                                                                class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                                                STORAGE CAPACITY</div>
                                                            <div class="font-bold text-emerald-400 text-xs mt-0.5">
                                                                {{ $aEx['resolved_assoc_view']['dStorage'] }}</div>
                                                        </div>

                                                        <div
                                                            class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                            <div
                                                                class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                                                COLOUR / FINISH</div>
                                                            <div class="font-bold text-slate-200 text-xs mt-0.5">
                                                                {{ $aEx['resolved_assoc_view']['dColor'] }}</div>
                                                        </div>

                                                        <div
                                                            class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                            <div
                                                                class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                                                OTHER MARKS / CONDITION</div>
                                                            <div class="font-bold text-slate-200 text-xs mt-0.5">
                                                                {{ $aEx['resolved_assoc_view']['dMarks'] }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                            
                                            @if (!empty($aEx['resolved_assoc_view']['isAmmoEx']) && !empty($aEx['resolved_assoc_view']['aAmmo']) && is_array($aEx['resolved_assoc_view']['aAmmo']))
                                                <div
                                                    class="col-span-full bg-slate-900 text-white p-4 rounded-xl border border-orange-500/40 space-y-3 mt-2 shadow-md">
                                                    <div
                                                        class="flex items-center justify-between border-b border-slate-800 pb-2">
                                                        <div class="flex items-center space-x-2">
                                                            <span
                                                                class="px-2 py-0.5 bg-orange-600 text-white font-mono-code font-extrabold text-[10px] rounded uppercase tracking-wider">AMMUNITION
                                                                SPECIFIC DETAILS</span>
                                                            <span
                                                                class="text-xs font-black text-slate-200">{{ $aEx['seizure_name'] ?? ($aEx['name'] ?? 'Ammunition Exhibit') }}</span>
                                                        </div>
                                                        @if (!empty($aEx['resolved_assoc_view']['aAmmo']['package_reference_number']))
                                                            <span
                                                                class="text-[10px] font-mono-code bg-slate-800 px-2.5 py-1 rounded text-amber-400 font-extrabold border border-amber-500/30">REF
                                                                #: {{ $aEx['resolved_assoc_view']['aAmmo']['package_reference_number'] }}</span>
                                                        @endif
                                                    </div>

                                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                                                        @if (!empty($aEx['resolved_assoc_view']['aAmmo']['cartridge_appearance']))
                                                            <div
                                                                class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                                <div
                                                                    class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                                                    CARTRIDGE APPEARANCE</div>
                                                                <div class="font-bold text-white text-xs mt-0.5">
                                                                    {{ $aEx['resolved_assoc_view']['aAmmo']['cartridge_appearance'] }}</div>
                                                            </div>
                                                        @endif
                                                        @if (!empty($aEx['resolved_assoc_view']['aAmmo']['projectile_bullet_type']))
                                                            <div
                                                                class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                                <div
                                                                    class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                                                    PROJECTILE TYPE</div>
                                                                <div
                                                                    class="font-extrabold text-amber-400 font-mono-code text-xs mt-0.5">
                                                                    {{ $aEx['resolved_assoc_view']['aAmmo']['projectile_bullet_type'] }}</div>
                                                            </div>
                                                        @endif
                                                        @if (!empty($aEx['resolved_assoc_view']['aAmmo']['cartridge_case_material']))
                                                            <div
                                                                class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                                <div
                                                                    class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                                                    CASE MATERIAL</div>
                                                                <div class="font-bold text-slate-200 text-xs mt-0.5">
                                                                    {{ $aEx['resolved_assoc_view']['aAmmo']['cartridge_case_material'] }}</div>
                                                            </div>
                                                        @endif
                                                        @if (!empty($aEx['resolved_assoc_view']['aAmmo']['colour_markings']))
                                                            <div
                                                                class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                                <div
                                                                    class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                                                    COLOUR / MARKINGS</div>
                                                                <div class="font-bold text-slate-200 text-xs mt-0.5">
                                                                    {{ $aEx['resolved_assoc_view']['aAmmo']['colour_markings'] }}</div>
                                                            </div>
                                                        @endif
                                                        @if (!empty($aEx['resolved_assoc_view']['aAmmo']['condition']))
                                                            <div
                                                                class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                                <div
                                                                    class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                                                    CONDITION</div>
                                                                <div
                                                                    class="font-extrabold text-emerald-400 text-xs mt-0.5">
                                                                    {{ $aEx['resolved_assoc_view']['aAmmo']['condition'] }}
                                                                    {{ !empty($aEx['resolved_assoc_view']['aAmmo']['other_condition']) ? "({$aEx['resolved_assoc_view']['aAmmo']['other_condition']})" : '' }}
                                                                </div>
                                                            </div>
                                                        @endif
                                                        @if (!empty($aEx['resolved_assoc_view']['aAmmo']['packaging_type']))
                                                            <div
                                                                class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                                <div
                                                                    class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                                                    PACKAGING TYPE</div>
                                                                <div class="font-bold text-slate-200 text-xs mt-0.5">
                                                                    {{ $aEx['resolved_assoc_view']['aAmmo']['packaging_type'] }}</div>
                                                            </div>
                                                        @endif
                                                        @if (!empty($aEx['resolved_assoc_view']['aAmmo']['seal_tamper_evident_condition']) || !empty($aEx['resolved_assoc_view']['aAmmo']['seal_condition']))
                                                            <div
                                                                class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                                <div
                                                                    class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                                                    SEAL / TAMPER CONDITION</div>
                                                                <div class="font-bold text-slate-100 text-xs mt-0.5">
                                                                    {{ $aEx['resolved_assoc_view']['aAmmo']['seal_tamper_evident_condition'] ?? $aEx['resolved_assoc_view']['aAmmo']['seal_condition'] }}
                                                                </div>
                                                            </div>
                                                        @endif
                                                        @if (!empty($aEx['resolved_assoc_view']['aAmmo']['condition_when_received']))
                                                            <div
                                                                class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700 col-span-2">
                                                                <div
                                                                    class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                                                    CONDITION WHEN RECEIVED</div>
                                                                <div class="font-bold text-slate-100 text-xs mt-0.5">
                                                                    {{ $aEx['resolved_assoc_view']['aAmmo']['condition_when_received'] }}</div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>

                                    <!-- ASSOCIATE EXHIBIT PHOTOS GALLERY -->
                                                                        @if (!empty($suspect['resolved_view']['assocPhotosFormatted']) && count($suspect['resolved_view']['assocPhotosFormatted']) > 0)
                                        <div class="pt-4 border-t border-slate-200 space-y-3 mt-4">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-1.5 h-3.5 bg-amber-600 rounded-full"></span>
                                                    <span class="text-[11px] font-black text-slate-900 uppercase font-mono-code">SEIZURE &amp; EXHIBIT EVIDENCE GALLERY ({{ strtoupper($suspect['resolved_view']['sName']) }})</span>
                                                </div>
                                                <span class="text-[10px] font-mono-code font-bold text-amber-900 bg-amber-100 px-2.5 py-0.5 rounded-md border border-amber-300">
                                                    {{ count($suspect['resolved_view']['assocPhotosFormatted']) }} Attached Photo(s)
                                                </span>
                                            </div>
                                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3">
                                                @foreach ($suspect['resolved_view']['assocPhotosFormatted'] as $apIdx => $apUrlFormatted)
                                                                                                        <div class="p-1.5 bg-white border border-slate-200/90 rounded-xl space-y-1.5 text-center shadow-xs group hover:border-amber-500 hover:shadow-md transition-all">
                                                        <div class="w-full h-44 sm:h-48 bg-slate-50/90 rounded-lg overflow-hidden border border-slate-200 cursor-pointer relative flex items-center justify-center p-1.5"
                                                            data-img-src="{{ $apUrlFormatted }}"
                                                            data-img-title="Exhibit Photo #{{ $apIdx + 1 }} — {{ $suspect['resolved_view']['sName'] }}"
                                                            onclick="openMugshotModal(this.getAttribute('data-img-src'), this.getAttribute('data-img-title'))">
                                                            <img src="{{ $apUrlFormatted }}"
                                                                class="max-w-full max-h-full object-contain rounded-md group-hover:scale-[1.02] transition-transform duration-200"
                                                                alt="Exhibit Photo"
                                                                onerror="if(!this.dataset.triedStorage &amp;&amp; !this.src.startsWith('data:')){this.dataset.triedStorage=1; this.src='{{ asset('storage/') }}/' + this.src.split('/').pop();}">
                                                            <div class="absolute inset-0 bg-slate-900/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none rounded-lg">
                                                                <span class="px-2.5 py-1 bg-slate-900/85 text-white text-[10px] font-mono-code font-bold rounded-md shadow-sm flex items-center gap-1">
                                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                                    Enlarge
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center justify-between px-1 text-[9.5px] font-mono-code">
                                                            <span class="font-extrabold text-slate-800 uppercase">Photo #{{ $apIdx + 1 }}</span>
                                                            <span class="text-amber-800 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded font-bold">Full View</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @else
                                                    @endif


                        @if (!empty($suspect['resolved_view']['sDossierHasSavedLab']))
                            @if (empty($suspect['resolved_view']['sDossierIsJointLab']))
                                @if (!empty($suspect['resolved_view']['sDossierLabExamCards']) && count($suspect['resolved_view']['sDossierLabExamCards']) > 0)
                                    @foreach ($suspect['resolved_view']['sDossierLabExamCards'] as $sCard)
                                        @include('partials.lab-examination-card', [
                                            'labExam' => $sCard,
                                            'labHeadingName' => $sCard['labHeadingName'] ?? $suspect['resolved_view']['labExamHeadingName'],
                                            'case' => $case,
                                        ])
                                    @endforeach
                                @else
                                    @include('partials.lab-examination-card', [
                                        'labExam' => $suspect['resolved_view']['sDossierLabExamCard'] ?? ($suspect['resolved_view']['sDossierLabExam'] ?? []),
                                        'labHeadingName' => $suspect['resolved_view']['labExamHeadingName'],
                                        'case' => $case,
                                    ])
                                @endif
                            @endif
                        @endif
                        {{-- ATTACHED REPORT FILES CONTAINER IS MOVED DIRECTLY ON TOP OF SEIZURES (COMMODITIES INVENTORY) --}}

                        {{-- ATTACHED DRUG SCREENING RESULT FOR THIS SPECIFIC SUSPECT --}}
                                                @include('partials.suspect_drug_screening_card', [
                            'screening' => $suspect['resolved_view']['sDrugScreening'],
                            'suspectName' => $suspect['resolved_view']['sName'],
                            'suspectId' => $suspect['id'] ?? ($suspect['resolved_view']['sId'] ?? null),
                            'caseNumber' => $case->case_number ?? ($case->id ?? ('NCC-' . date('Y'))),
                            'showEmpty' => (bool) $suspect['resolved_view']['isLabContext'],
                            'showActions' => (bool) $suspect['resolved_view']['isLabContext'],
                        ])
                    </div>
