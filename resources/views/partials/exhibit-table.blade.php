{{--
    Partial: partials/exhibit-table.blade.php
    Description: Renders the master Seizures (Commodities Inventory) table and exhibit photo gallery for all case exhibits.
    Required Inputs (@props):
      - $exhibits (array): List of deduplicated case exhibit arrays with pre-resolved owner and formatted photo URLs.
      - $defaultOwnerName (string): Fallback suspect owner name when resolved_owner is not set.
--}}@props([
    'exhibits' => [],
    'defaultOwnerName' => 'Primary Suspect',
    'case' => null,
    'suspects' => [],
])
                @if (!empty($exhibits) && count($exhibits) > 0)
                    @php
                        // Resolve mugshot mapping for suspect(s) by name
                        $suspectListForMugshot = !empty($suspects) ? $suspects : ($suspectsList ?? []);
                        if (empty($suspectListForMugshot) && !empty($case)) {
                            if (is_object($case) && method_exists($case, 'getSuspectsList')) {
                                $suspectListForMugshot = $case->getSuspectsList();
                            } elseif (is_object($case) && method_exists($case, 'suspects') && $case->relationLoaded('suspects')) {
                                $suspectListForMugshot = $case->suspects->all();
                            } elseif (!empty($case->docket_data['suspects']) && is_array($case->docket_data['suspects'])) {
                                $suspectListForMugshot = $case->docket_data['suspects'];
                            }
                        }
                        if (empty($suspectListForMugshot) && !empty($case->underlyingRecord) && method_exists($case->underlyingRecord, 'suspects')) {
                            $suspectListForMugshot = $case->underlyingRecord->suspects()->orderBy('suspect_number')->get()->all();
                        }

                        $isMultipleCase = !empty($isMultiple)
                            || !empty($isMultiCase)
                            || ($case instanceof \App\Models\MultipleSuspectCase)
                            || (($case->underlyingRecord ?? null) instanceof \App\Models\MultipleSuspectCase)
                            || (!empty($case->case_type) && strtoupper((string)$case->case_type) === 'MULTIPLE')
                            || count($suspectListForMugshot) > 1
                            || (int) ($case->total_suspects_count ?? 1) > 1;

                        $suspectMugshotMap = [];
                        $allMultiFrontMugshots = [];
                        $seenSuspectPhotos = [];

                        foreach ($suspectListForMugshot as $sIdx => $sItem) {
                            $sName = is_array($sItem)
                                ? ($sItem['resolved_view']['sName'] ?? ($sItem['suspect_name'] ?? ('Suspect #' . ($sIdx + 1))))
                                : ($sItem->suspect_name ?? ('Suspect #' . ($sIdx + 1)));
                            $sNameKey = strtolower(trim((string) $sName));

                            $sPhoto = is_array($sItem)
                                ? ($sItem['resolved_view']['sFront'] ?? ($sItem['mugshot_front'] ?? null))
                                : ($sItem->mugshot_front ?? null);

                            if (!empty($sNameKey) && !empty($sPhoto)) {
                                $suspectMugshotMap[$sNameKey] = $sPhoto;
                            }
                            if (!empty($sNameKey) && !isset($seenSuspectPhotos[$sNameKey])) {
                                $seenSuspectPhotos[$sNameKey] = true;
                                $allMultiFrontMugshots[] = [
                                    'name' => $sName,
                                    'photo' => $sPhoto,
                                ];
                            }
                        }

                        $caseTopFront = $case->mugshot_front ?? ($case->docket_data['mugshot_front'] ?? null);
                        $casePrimName = strtolower(trim((string) ($case->suspect_name ?? ($case->docket_data['suspect_name'] ?? ''))));
                        if (!empty($casePrimName) && !empty($caseTopFront) && empty($suspectMugshotMap[$casePrimName])) {
                            $suspectMugshotMap[$casePrimName] = $caseTopFront;
                        }
                        if (!empty($caseTopFront) && empty($suspectMugshotMap['*default*'])) {
                            $suspectMugshotMap['*default*'] = $caseTopFront;
                        }
                        if (!empty($caseTopFront) && !empty($casePrimName) && !isset($seenSuspectPhotos[$casePrimName])) {
                            $seenSuspectPhotos[$casePrimName] = true;
                            array_unshift($allMultiFrontMugshots, [
                                'name' => $case->suspect_name ?? ($case->docket_data['suspect_name'] ?? 'Primary Suspect'),
                                'photo' => $caseTopFront,
                            ]);
                        } elseif (empty($allMultiFrontMugshots) && !empty($caseTopFront)) {
                            $allMultiFrontMugshots[] = [
                                'name' => $case->suspect_name ?? ($case->docket_data['suspect_name'] ?? 'Primary Suspect'),
                                'photo' => $caseTopFront,
                            ];
                        }
                    @endphp
                    <!-- SEIZURES (COMMODITIES INVENTORY) FOR ALL SUSPECTS (SINGLE MASTER SECTION) -->
                    <div class="p-5 bg-white border border-slate-200 rounded-2xl space-y-4 shadow-sm mt-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                            <div>
                                <h3 class="text-sm font-black text-slate-900">Seizures (Commodities Inventory)</h3>
                                <p class="text-[11px] text-slate-500">Master record of confiscated substances,
                                    exhibits, and vault allocation for all suspects</p>
                            </div>
                            <span
                                class="px-2.5 py-0.5 rounded-full text-[10px] font-mono-code font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                {{ count($exhibits) }} ITEM(S) LOGGED
                            </span>
                        </div>

                        @php
                            $manifestDesc = $case->description_of_exhibits ?? null;
                            if (empty($manifestDesc)) {
                                foreach ($exhibits as $ex) {
                                    if (!empty($ex['description_of_exhibits'])) {
                                        $manifestDesc = $ex['description_of_exhibits'];
                                        break;
                                    }
                                }
                            }
                        @endphp
                        @if (!empty($manifestDesc))
                            <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                                <span class="font-mono-code font-bold text-slate-500 uppercase text-[10px]">Description of Exhibit(s):</span>
                                <div class="font-medium text-slate-800 mt-0.5 leading-relaxed">{{ $manifestDesc }}</div>
                            </div>
                        @endif

                        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-2xs">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead class="bg-slate-100 text-slate-600 font-mono-code uppercase text-[10px]">
                                    <tr>
                                        <th class="py-2.5 px-4 font-bold">Item #</th>
                                        <th class="py-2.5 px-4 font-bold">Suspect(s) Image</th>
                                        <th class="py-2.5 px-4 font-bold">Suspect / Owner</th>
                                        <th class="py-2.5 px-4 font-bold">Category</th>
                                        <th class="py-2.5 px-4 font-bold">Exhibit Description</th>
                                        <th class="py-2.5 px-4 font-bold">Source of Exhibits</th>
                                        <th class="py-2.5 px-4 font-bold">Quantity</th>
                                        <th class="py-2.5 px-4 font-bold">Weight / Unit</th>
                                        <th class="py-2.5 px-4 font-bold">Packaging</th>
                                        <th class="py-2.5 px-4 font-bold">Est. Value</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-slate-800">
                                    @foreach ($exhibits as $eIdx => $szItem)
                                        @php
                                            $ownerRaw = trim((string) ($szItem['resolved_owner'] ?? ($szItem['associated_person'] ?? ($szItem['owner_name'] ?? ($szItem['suspect_ownership'] ?? $defaultOwnerName)))));
                                            $ownerLower = strtolower($ownerRaw);

                                            $rowMugshots = [];
                                            if ($isMultipleCase && count($allMultiFrontMugshots) > 1) {
                                                $rowMugshots = $allMultiFrontMugshots;
                                            } else {
                                                $suspectImg = null;
                                                if (!empty($ownerLower) && isset($suspectMugshotMap[$ownerLower])) {
                                                    $suspectImg = $suspectMugshotMap[$ownerLower];
                                                } else {
                                                    // Try substring matching or fallback
                                                    foreach ($suspectMugshotMap as $mk => $mv) {
                                                        if ($mk !== '*default*' && !empty($mk) && (str_contains($ownerLower, $mk) || str_contains($mk, $ownerLower))) {
                                                            $suspectImg = $mv;
                                                            break;
                                                        }
                                                    }
                                                    if (empty($suspectImg)) {
                                                        $suspectImg = $suspectMugshotMap['*default*'] ?? (!empty($suspectMugshotMap) ? reset($suspectMugshotMap) : null);
                                                    }
                                                }
                                                if (!empty($suspectImg)) {
                                                    $rowMugshots[] = [
                                                        'name' => $ownerRaw,
                                                        'photo' => $suspectImg,
                                                    ];
                                                }
                                            }
                                        @endphp
                                        <tr>
                                            <td class="py-2.5 px-4 font-mono-code font-bold text-slate-500">
                                                #{{ $eIdx + 1 }}</td>
                                            <td class="py-2.5 px-4">
                                                @php
                                                    $hasAnyPhoto = false;
                                                    foreach ($rowMugshots as $mShot) {
                                                        if (!empty($mShot['photo'])) {
                                                            $hasAnyPhoto = true;
                                                            break;
                                                        }
                                                    }
                                                @endphp
                                                @if ($hasAnyPhoto)
                                                    <div class="flex items-center gap-1.5 flex-nowrap">
                                                        @foreach ($rowMugshots as $mShot)
                                                            @if (!empty($mShot['photo']))
                                                                <div class="relative group inline-block shrink-0">
                                                                    <img src="{{ $mShot['photo'] }}" alt="{{ $mShot['name'] }} — Front Mugshot"
                                                                        data-img-src="{{ $mShot['photo'] }}"
                                                                        data-img-title="Suspect Front Mugshot — {{ $mShot['name'] }}"
                                                                        class="{{ count($rowMugshots) > 1 ? 'h-11 w-11' : 'h-12 w-12' }} object-cover rounded-lg border border-slate-200 shadow-xs cursor-pointer hover:scale-105 transition-transform"
                                                                        title="{{ $mShot['name'] }} (Front Mugshot)"
                                                                        onclick="if (typeof openMugshotModal === 'function') { openMugshotModal(this.getAttribute('data-img-src'), this.getAttribute('data-img-title')); } else if (typeof openImageLightbox === 'function') { openImageLightbox(this.getAttribute('data-img-src'), this.getAttribute('data-img-title')); }"
                                                                        onerror="if(!this.dataset.triedStorage && !this.src.startsWith('data:')){this.dataset.triedStorage=1; this.src='{{ asset('storage/') }}/' + this.src.split('/').pop();}" />
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span
                                                        class="px-2 py-1 bg-slate-100 text-slate-400 text-[10px] font-mono-code rounded border border-slate-200">No
                                                        Photo</span>
                                                @endif
                                            </td>
                                            <td class="py-2.5 px-4 font-mono-code font-black text-slate-950">
                                                {{ $szItem['resolved_owner'] ?? $defaultOwnerName }}</td>
                                            <td class="py-2.5 px-4 font-bold font-mono-code text-amber-700">
                                                {{ $szItem['category'] ?? ($szItem['exhibit_category'] ?? 'General Exhibit') }}</td>
                                            <td class="py-2.5 px-4 font-extrabold text-slate-900">
                                                @php
                                                    $rawSzNameT = $szItem['seizure_name'] ?? null;
                                                    $rawCommT = $szItem['commodity_name'] ?? ($szItem['seizure_name_currency'] ?? null);
                                                    if (!empty($rawCommT) && (empty($rawSzNameT) || in_array(strtolower(trim((string)$rawSzNameT)), ['exhibit', 'exhibits', 'general exhibit', 'confiscated exhibit']))) {
                                                        $dispNameT = $rawCommT;
                                                    } else {
                                                        $dispNameT = (!empty($rawCommT) && !in_array(strtolower(trim((string)$rawCommT)), ['exhibit', 'exhibits']))
                                                            ? $rawCommT
                                                            : ($rawSzNameT ?? ($szItem['name'] ?? ($szItem['weapon_model'] ?? 'N/A')));
                                                    }
                                                @endphp
                                                <div>{{ $dispNameT }}</div>
                                                @if(!empty($szItem['lab_exhibit_no']))
                                                    <div class="mt-1">
                                                        <span class="bg-emerald-100 text-emerald-900 border border-emerald-300 px-1.5 py-0.5 rounded font-mono font-bold text-[10px]">Lab Exh: {{ $szItem['lab_exhibit_no'] }}</span>
                                                    </div>
                                                @endif
                                                @if(!empty($szItem['contents']))
                                                    <div class="mt-1 text-[11px] text-slate-700 font-bold flex items-center gap-1">
                                                        <span class="text-[9px] font-mono-code font-bold text-slate-500 bg-slate-100 px-1 py-0.2 rounded border border-slate-200 uppercase not-italic">Contents:</span>
                                                        <span>{{ $szItem['contents'] }}</span>
                                                    </div>
                                                @endif
                                                @php
                                                    $catLowerT = strtolower(trim((string)($szItem['category'] ?? ($szItem['exhibit_category'] ?? ''))));
                                                    $nameLowerT = strtolower(trim((string)($dispNameT ?? '')));
                                                    $hasDevT = (!empty($szItem['dev_serial_no']) && is_string($szItem['dev_serial_no'])) || (!empty($szItem['dev_imei1']) && is_string($szItem['dev_imei1'])) || (!empty($szItem['dev_storage']) && is_string($szItem['dev_storage']));
                                                    $hasVehT = (!empty($szItem['veh_reg_no']) && is_string($szItem['veh_reg_no'])) || (!empty($szItem['veh_vin']) && is_string($szItem['veh_vin'])) || (!empty($szItem['veh_condition']) && is_string($szItem['veh_condition']));
                                                    $hasWpnT = (!empty($szItem['wpn_serial_no']) && is_string($szItem['wpn_serial_no'])) || (!empty($szItem['wpn_calibre']) && is_string($szItem['wpn_calibre'])) || (!empty($szItem['wpn_condition']) && is_string($szItem['wpn_condition']));

                                                    $isDevT = str_contains($catLowerT, 'device') || str_contains($catLowerT, 'electronic') || str_contains($catLowerT, 'digital') || str_contains($nameLowerT, 'phone') || str_contains($nameLowerT, 'laptop') || str_contains($nameLowerT, 'iphone') || $hasDevT;
                                                    $isVehT = str_contains($catLowerT, 'veh') || str_contains($catLowerT, 'car') || str_contains($catLowerT, 'auto') || $hasVehT;
                                                    $isWpnT = str_contains($catLowerT, 'wpn') || str_contains($catLowerT, 'weapon') || str_contains($catLowerT, 'firearm') || $hasWpnT;
                                                @endphp
                                                @if($isVehT && ($hasVehT || !empty($szItem['veh_fuel']) || !empty($szItem['veh_damage'])))
                                                    <div class="mt-1 flex flex-wrap gap-1 text-[10px] font-mono-code text-slate-600 font-normal">
                                                        @if(!empty($szItem['veh_reg_no']) && is_string($szItem['veh_reg_no'])) <span class="bg-amber-100 text-amber-900 px-1.5 py-0.5 rounded font-bold">Reg: {{ $szItem['veh_reg_no'] }}</span> @endif
                                                        @if(!empty($szItem['veh_vin']) && is_string($szItem['veh_vin'])) <span class="bg-blue-100 text-blue-900 px-1.5 py-0.5 rounded font-bold">VIN: {{ $szItem['veh_vin'] }}</span> @endif
                                                        @if(!empty($szItem['veh_condition']) && is_string($szItem['veh_condition'])) <span class="bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-bold">{{ $szItem['veh_condition'] }}</span> @endif
                                                        @if(!empty($szItem['veh_doc_ref']) && is_string($szItem['veh_doc_ref'])) <span class="bg-purple-100 text-purple-900 px-1.5 py-0.5 rounded">Doc: {{ $szItem['veh_doc_ref'] }}</span> @endif
                                                        @if(!empty($szItem['veh_owner_contact']) && is_string($szItem['veh_owner_contact'])) <span class="bg-indigo-100 text-indigo-900 px-1.5 py-0.5 rounded">Contact: {{ $szItem['veh_owner_contact'] }}</span> @endif
                                                        @if(!empty($szItem['veh_fuel']) && is_string($szItem['veh_fuel'])) <span class="bg-emerald-100 text-emerald-900 px-1.5 py-0.5 rounded">Fuel: {{ $szItem['veh_fuel'] }}</span> @endif
                                                        @if(!empty($szItem['veh_damage']) && is_string($szItem['veh_damage'])) <span class="bg-rose-100 text-rose-900 px-1.5 py-0.5 rounded">Dmg: {{ \Illuminate\Support\Str::limit($szItem['veh_damage'], 35) }}</span> @endif
                                                        @if(!empty($szItem['veh_mods']) && is_string($szItem['veh_mods'])) <span class="bg-amber-50 text-amber-900 px-1.5 py-0.5 rounded">Mods: {{ $szItem['veh_mods'] }}</span> @endif
                                                        @if(!empty($szItem['veh_features']) && is_string($szItem['veh_features'])) <span class="bg-cyan-50 text-cyan-900 px-1.5 py-0.5 rounded">Features: {{ $szItem['veh_features'] }}</span> @endif
                                                    </div>
                                                @elseif($isWpnT && ($hasWpnT || !empty($szItem['wpn_loaded_status'])))
                                                    <div class="mt-1 flex flex-wrap gap-1 text-[10px] font-mono-code text-slate-600 font-normal">
                                                        @if(!empty($szItem['wpn_serial_no']) && is_string($szItem['wpn_serial_no'])) <span class="bg-amber-100 text-amber-900 px-1.5 py-0.5 rounded font-bold">SN: {{ $szItem['wpn_serial_no'] }}</span> @endif
                                                        @if(!empty($szItem['wpn_calibre']) && is_string($szItem['wpn_calibre'])) <span class="bg-blue-100 text-blue-900 px-1.5 py-0.5 rounded font-bold">Cal: {{ $szItem['wpn_calibre'] }}</span> @endif
                                                        @if(!empty($szItem['wpn_loaded_status']) && is_string($szItem['wpn_loaded_status'])) <span class="bg-red-100 text-red-900 px-1.5 py-0.5 rounded font-bold">{{ $szItem['wpn_loaded_status'] }}</span> @endif
                                                        @if(!empty($szItem['wpn_condition']) && is_string($szItem['wpn_condition'])) <span class="bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded">{{ $szItem['wpn_condition'] }}</span> @endif
                                                        @if(!empty($szItem['wpn_marks']) && is_string($szItem['wpn_marks'])) <span class="bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded">Marks: {{ $szItem['wpn_marks'] }}</span> @endif
                                                        @if(!empty($szItem['wpn_location_found']) && is_string($szItem['wpn_location_found'])) <span class="bg-amber-50 text-amber-800 px-1.5 py-0.5 rounded">Loc: {{ $szItem['wpn_location_found'] }}</span> @endif
                                                    </div>
                                                @elseif($isDevT && $hasDevT)
                                                    <div class="mt-1 flex flex-wrap gap-1 text-[10px] font-mono-code text-slate-600 font-normal">
                                                        @if(!empty($szItem['dev_serial_no']) && is_string($szItem['dev_serial_no'])) <span class="bg-amber-100 text-amber-900 px-1.5 py-0.5 rounded font-bold">SN: {{ $szItem['dev_serial_no'] }}</span> @endif
                                                        @if(!empty($szItem['dev_imei1']) && is_string($szItem['dev_imei1'])) <span class="bg-blue-100 text-blue-900 px-1.5 py-0.5 rounded font-bold">IMEI: {{ $szItem['dev_imei1'] }}</span> @endif
                                                        @if(!empty($szItem['dev_storage']) && is_string($szItem['dev_storage'])) <span class="bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-bold">{{ $szItem['dev_storage'] }}</span> @endif
                                                    </div>
                                                @elseif(!empty($szItem['other_exhibit_description']) && is_string($szItem['other_exhibit_description']))
                                                    <div class="mt-1 text-[11px] text-slate-600 font-normal italic">
                                                        {{ \Illuminate\Support\Str::limit($szItem['other_exhibit_description'], 60) }}
                                                    </div>
                                                @elseif(!empty($szItem['other_description']) && is_string($szItem['other_description']))
                                                    <div class="mt-1 text-[11px] text-slate-600 font-normal italic">
                                                        {{ \Illuminate\Support\Str::limit($szItem['other_description'], 60) }}
                                                    </div>
                                                @endif
                                                @php
                                                    $remT = $szItem['exhibit_remark'] ?? ($szItem['remark'] ?? null);
                                                @endphp
                                                @if(!empty($remT) && is_string($remT))
                                                    <div class="mt-1 text-[11px] text-slate-600 font-medium italic flex items-start gap-1">
                                                        <span class="text-[9px] font-mono-code font-bold text-slate-500 bg-slate-100 px-1 py-0.2 rounded border border-slate-200 uppercase not-italic">Remark:</span>
                                                        <span>{{ $remT }}</span>
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="py-2.5 px-4 font-mono-code text-slate-700">
                                                <div>{{ $szItem['source_of_exhibits'] ?? ($szItem['source'] ?? 'N/A') }}</div>
                                                @if (!empty($szItem['source_requesting_unit']))
                                                    <div class="text-[10px] text-slate-500 font-sans mt-0.5"><span class="font-bold">Unit:</span> {{ $szItem['source_requesting_unit'] }}</div>
                                                @endif
                                                @if (!empty($szItem['investigating_officer']))
                                                    <div class="text-[10px] text-slate-500 font-sans mt-0.5"><span class="font-bold">IO:</span> {{ $szItem['investigating_officer'] }}</div>
                                                @endif
                                            </td>
                                            <td class="py-2.5 px-4 font-mono-code">
                                                @if(in_array(strtolower(trim((string)($szItem['category'] ?? ($szItem['exhibit_category'] ?? '')))), ['money', 'currency', 'cash']) || !empty($szItem['amount']))
                                                    <span class="font-bold text-emerald-800">{{ $szItem['amount'] ?? ($szItem['seizure_quantity'] ?? ($szItem['quantity'] ?? 'N/A')) }}</span>
                                                @else
                                                    {{ $szItem['seizure_quantity'] ?? ($szItem['quantity'] ?? 'N/A') }}
                                                @endif
                                            </td>
                                            <td class="py-2.5 px-4 font-mono-code">{{ !empty($szItem['seizure_weight']) || !empty($szItem['weight']) ? ($szItem['seizure_weight'] ?? $szItem['weight']) . ' ' . ($szItem['seizure_unit'] ?? ($szItem['unit'] ?? '')) : 'N/A' }}</td>
                                            <td class="py-2.5 px-4 font-mono-code">{{ $szItem['seizure_packaging'] ?? ($szItem['packaging'] ?? 'N/A') }}</td>
                                            <td class="py-2.5 px-4 font-mono-code font-bold text-emerald-700">
                                                {{ !empty($szItem['seizure_value'] ?? ($szItem['value'] ?? null)) && !str_contains((string) ($szItem['seizure_value'] ?? ($szItem['value'] ?? '')), 'Item #') && !str_contains((string) ($szItem['seizure_value'] ?? ($szItem['value'] ?? '')), "\n") ? trim((string) ($szItem['seizure_value'] ?? $szItem['value'])) : 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if (!empty($exhibits[0]['formatted_case_gallery_photos']) && count($exhibits[0]['formatted_case_gallery_photos']) > 0)
                            <div class="pt-3 border-t border-slate-100">
                                <h4
                                    class="text-xs font-bold text-slate-700 uppercase font-mono-code mb-2 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Exhibit Photo Gallery ({{ count($exhibits[0]['formatted_case_gallery_photos']) }} images)
                                </h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($exhibits[0]['formatted_case_gallery_photos'] as $pIdx => $gSrc)
                                                                                <div class="relative group cursor-pointer"
                                            data-img-src="{{ $gSrc }}"
                                            data-img-title="Exhibit Image #{{ $pIdx + 1 }}"
                                            onclick="openMugshotModal(this.getAttribute('data-img-src'), this.getAttribute('data-img-title'))">
                                            <img src="{{ $gSrc }}"
                                                class="h-16 w-16 object-cover rounded-xl border border-slate-200 shadow-2xs group-hover:border-emerald-500 group-hover:scale-105 transition-all"
                                                onerror="if(!this.dataset.triedStorage &amp;&amp; !this.src.startsWith('data:')){this.dataset.triedStorage=1; this.src='{{ asset('storage/') }}/' + this.src.split('/').pop();}" />
                                            <div
                                                class="absolute inset-0 bg-slate-900/20 opacity-0 group-hover:opacity-100 rounded-xl transition-opacity flex items-center justify-center">
                                                <svg class="w-4 h-4 text-white" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                </svg>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
