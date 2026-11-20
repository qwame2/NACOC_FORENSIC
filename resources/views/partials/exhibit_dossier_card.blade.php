@php
    $exList = $dossierExhibits ?? ($primaryExhibits ?? []);
    $badgeText = $dossierBadgeText ?? 'PRIMARY EXHIBIT DOSSIER';
    $headingName = $dossierHeadingName ?? ($sName ?? 'Primary Suspect');
    $headingText = $dossierHeadingText ?? "Primary Suspect Seizures & Exhibits — {$headingName}";

    if (!isset($extractAllPhotosHelper) || !is_callable($extractAllPhotosHelper)) {
        $extractAllPhotosHelper = function ($item) {
            $found = [];
            if (empty($item)) return $found;
            if (is_object($item)) $item = (array) $item;
            if (!is_array($item)) return $found;
            $keys = [
                'photos', 'exhibit_photos', 'evidence_photos', 'photo', 'exhibit_photo',
                'photo_url', 'image', 'image_path', 'image_url', 'file_path', 'attachment', 'attachments',
            ];
            foreach ($keys as $k) {
                if (!empty($item[$k])) {
                    $val = $item[$k];
                    if (is_string($val)) {
                        $val = trim($val);
                        if (str_starts_with($val, '[') || str_starts_with($val, '{')) {
                            $dec = json_decode($val, true);
                            if (is_array($dec)) {
                                foreach ($dec as $p) {
                                    if (!empty($p) && is_string($p)) $found[] = $p;
                                }
                            } else {
                                $found[] = $val;
                            }
                        } else {
                            $found[] = $val;
                        }
                    } elseif (is_array($val)) {
                        foreach ($val as $p) {
                            if (!empty($p) && is_string($p)) $found[] = $p;
                        }
                    }
                }
            }
            return array_values(array_unique($found));
        };
    }

    if (!isset($formatPhotoUrlHelper) || !is_callable($formatPhotoUrlHelper)) {
        $formatPhotoUrlHelper = function ($p) {
            if (empty($p)) return '';
            $p = trim((string) $p);
            if (str_starts_with($p, 'http://') || str_starts_with($p, 'https://') || str_starts_with($p, 'data:')) {
                return $p;
            }
            $clean = ltrim($p, '/');
            if (str_starts_with($clean, 'storage/')) {
                return asset($clean);
            }
            if (file_exists(public_path($clean))) {
                return asset($clean);
            }
            return asset('storage/' . $clean);
        };
    }

    $galleryPhotos = $dossierPhotos ?? [];
    if (empty($galleryPhotos) && !empty($exList) && is_array($exList)) {
        foreach ($exList as $pExItem) {
            $extractedP = $extractAllPhotosHelper($pExItem);
            foreach ($extractedP as $p) {
                if ($p && !in_array($p, $galleryPhotos)) {
                    $galleryPhotos[] = $p;
                }
            }
        }
    }
@endphp

@if (!empty($exList) && count($exList) > 0)
    <!-- LOGGED SEIZURES & EXHIBITS DOSSIER FOR {{ strtoupper($headingName) }} -->
    <div class="p-5 bg-slate-50 border-2 border-slate-300 rounded-2xl space-y-4 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-200">
            <div class="flex items-center space-x-2">
                <span
                    class="px-2.5 py-1 rounded-md text-[10px] font-mono-code font-extrabold bg-slate-900 text-white uppercase tracking-wider shadow-2xs">
                    {{ $badgeText }}
                </span>
                <div>
                    <h3 class="text-sm font-black text-slate-900 flex items-center gap-1.5">
                        {{ $headingText }}
                    </h3>
                    <p class="text-[11px] text-slate-600">Confiscated items and exhibit inventory attached to {{ $headingName }}</p>
                </div>
            </div>
            <span
                class="px-2.5 py-1 rounded-full text-[10px] font-mono-code font-extrabold bg-slate-200 text-slate-800 border border-slate-300 w-max">
                {{ count($exList) }} EXHIBIT ITEM(S) LOGGED
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs">
            @foreach ($exList as $pEx)
                <div class="p-3 rounded-xl bg-white border border-slate-200 shadow-2xs">
                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">
                        Exhibit Category</div>
                    <div class="font-extrabold text-amber-700 text-xs mt-0.5 font-mono-code">
                        {{ $pEx['category'] ?? ($pEx['exhibit_category'] ?? 'General Exhibit') }}
                    </div>
                </div>
                <div class="p-3 rounded-xl bg-white border border-slate-200 shadow-2xs">
                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">
                        Seizure / Commodity Name</div>
                    @php
                        $pExCatVal = $pEx['category'] ?? ($pEx['exhibit_category'] ?? '');
                        $isMoneyExP = in_array(strtolower(trim((string)$pExCatVal)), ['money', 'currency', 'cash']);
                        $rawSeizureNameP = $pEx['seizure_name'] ?? null;
                        $rawCommodityNameP = $pEx['commodity_name'] ?? ($pEx['seizure_name_currency'] ?? null);
                        if (!empty($rawCommodityNameP) && (empty($rawSeizureNameP) || in_array(strtolower(trim((string)$rawSeizureNameP)), ['exhibit', 'exhibits', 'general exhibit', 'confiscated exhibit']))) {
                            $displayNameP = $rawCommodityNameP;
                        } else {
                            $displayNameP = (!empty($rawCommodityNameP) && !in_array(strtolower(trim((string)$rawCommodityNameP)), ['exhibit', 'exhibits']))
                                ? $rawCommodityNameP
                                : ($rawSeizureNameP ?? ($pEx['name'] ?? 'N/A'));
                        }
                        if (in_array(strtolower(trim((string)$displayNameP)), ['electronic devices', 'electronic device', 'device', 'devices', 'general exhibit', 'confiscated exhibit', 'exhibit', 'exhibits', 'n/a']) && !empty($pEx['dev_model'] ?? ($pEx['dev']['model'] ?? ($pEx['device_model'] ?? null)))) {
                            $displayNameP = $pEx['dev_model'] ?? ($pEx['dev']['model'] ?? ($pEx['device_model'] ?? null));
                        }
                    @endphp
                    <div class="font-extrabold text-slate-900 text-xs mt-0.5">
                        {{ $displayNameP }}
                    </div>
                </div>
                <div class="p-3 rounded-xl bg-white border border-slate-200 shadow-2xs">
                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">
                        {{ $isMoneyExP || !empty($pEx['amount']) ? 'Amount / Quantity' : 'Quantity / Weight' }}</div>
                    <div class="font-extrabold text-slate-900 font-mono-code text-xs mt-0.5">
                        @if ($isMoneyExP || !empty($pEx['amount']))
                            {{ !empty($pEx['amount']) ? "Amount: {$pEx['amount']}" : (!empty($pEx['seizure_quantity']) ? "Amount: {$pEx['seizure_quantity']}" : 'N/A') }}
                        @elseif (!empty($pEx['seizure_quantity']) || !empty($pEx['seizure_weight']))
                            {{ implode((request()->is('*/staff*') || request()->is('staff*') || request()->is('*field-officers*')) ? ' | ' : ' • ', array_filter([!empty($pEx['seizure_quantity']) ? "Qty: {$pEx['seizure_quantity']}" : null, !empty($pEx['seizure_weight']) ? "Wt: {$pEx['seizure_weight']} " . ($pEx['seizure_unit'] ?? '') : null])) }}
                        @else
                            N/A
                        @endif
                    </div>
                </div>
                <div class="p-3 rounded-xl bg-white border border-slate-200 shadow-2xs">
                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">
                        Estimated Valuation</div>
                    @php
                        $rawValP = $pEx['seizure_value'] ?? ($pEx['value'] ?? null);
                        $cleanValP =
                            !empty($rawValP) &&
                            !str_contains($rawValP, 'Item #') &&
                            !str_contains($rawValP, "\n")
                                ? trim($rawValP)
                                : 'N/A';
                    @endphp
                    <div class="font-extrabold text-emerald-700 font-mono-code text-xs mt-0.5">
                        {{ $cleanValP }}</div>
                </div>

                @if (!empty($pEx['source_of_exhibits']) || !empty($pEx['source']))
                    <div class="p-3 rounded-xl bg-white border border-slate-200 shadow-2xs col-span-1 sm:col-span-2">
                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">
                            Source of Exhibits</div>
                        <div class="font-bold text-slate-900 text-xs mt-0.5">
                            {{ $pEx['source_of_exhibits'] ?? $pEx['source'] }}
                        </div>
                    </div>
                @endif

                @if (!empty($pEx['other_exhibit_description']) || !empty($pEx['other_description']))
                    <div class="p-3 rounded-xl bg-white border border-slate-200 shadow-2xs col-span-full">
                        <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">
                            Other Exhibit Description / Specifications</div>
                        <div class="font-extrabold text-slate-900 text-xs mt-0.5 whitespace-pre-line">
                            {{ $pEx['other_exhibit_description'] ?? $pEx['other_description'] }}
                        </div>
                    </div>
                @endif

                @php
                    $pExPhotos = $extractAllPhotosHelper($pEx);
                @endphp
                @if (!empty($pExPhotos) && count($pExPhotos) > 0)
                    <div class="p-3 rounded-xl bg-white border border-slate-200 shadow-2xs col-span-full space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-mono-code font-bold text-slate-700 uppercase">Exhibit Visual Evidence Photo(s)</span>
                            <span class="text-[9px] font-mono-code bg-slate-100 text-slate-800 px-2 py-0.5 rounded font-extrabold border border-slate-300">{{ count($pExPhotos) }} Attached</span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($pExPhotos as $peIdx => $peUrl)
                                @php $peFormatted = $formatPhotoUrlHelper($peUrl); @endphp
                                <div class="w-16 h-16 rounded-lg overflow-hidden border border-slate-200 shadow-2xs cursor-pointer group relative bg-slate-100"
                                    onclick="openMugshotModal('{{ $peFormatted }}', 'Exhibit Photo #{{ $peIdx + 1 }}')">
                                    <img src="{{ $peFormatted }}"
                                        class="w-full h-full object-cover group-hover:scale-110 transition-transform"
                                        alt="Exhibit Photo"
                                        onerror="if(!this.dataset.triedStorage &amp;&amp; !this.src.startsWith('data:')){this.dataset.triedStorage=1; this.src='{{ asset('storage/') }}/' + this.src.split('/').pop();}">
                                    <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
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

                @php
                    $catLower = strtolower($pEx['category'] ?? ($pEx['exhibit_category'] ?? ''));
                    $nameLower = strtolower($pEx['seizure_name'] ?? ($pEx['commodity_name'] ?? ($pEx['name'] ?? '')));
                    $hasWeaponData =
                        !empty($pEx['weapon_model']) ||
                        !empty($pEx['weapon_serial_number']) ||
                        !empty($pEx['weapon_calibre']) ||
                        !empty($pEx['weapon_loaded_status']) ||
                        !empty($pEx['weapon_condition_at_recovery']) ||
                        !empty($pEx['weapon_location_found']) ||
                        !empty($pEx['weapon_distinguishing_marks']);
                    $isWeaponEx =
                        str_contains($catLower, 'weapon') ||
                        str_contains($nameLower, 'ak-47') ||
                        str_contains($nameLower, 'pistol') ||
                        str_contains($nameLower, 'rifle') ||
                        str_contains($nameLower, 'shotgun') ||
                        str_contains($nameLower, 'firearm') ||
                        $hasWeaponData;

                    $hasVehicleData =
                        !empty($pEx['veh_make']) ||
                        !empty($pEx['veh_model']) ||
                        !empty($pEx['veh_reg_no']) ||
                        !empty($pEx['veh_vin']) ||
                        !empty($pEx['veh_colour']) ||
                        !empty($pEx['veh_type']) ||
                        !empty($pEx['vehicle_make_model']) ||
                        !empty($pEx['vehicle_registration']) ||
                        !empty($pEx['vehicle_chassis_vin']);
                    $isVehEx =
                        str_contains($catLower, 'vehicle') ||
                        str_contains($catLower, 'vessel') ||
                        str_contains($catLower, 'car') ||
                        str_contains($catLower, 'truck') ||
                        str_contains($catLower, 'automobile') ||
                        str_contains($nameLower, 'zonda') ||
                        str_contains($nameLower, 'toyota') ||
                        str_contains($nameLower, 'nissan') ||
                        str_contains($nameLower, 'honda') ||
                        str_contains($nameLower, 'benz') ||
                        str_contains($nameLower, 'bmw') ||
                        $hasVehicleData;

                    $hasDeviceData =
                        !empty($pEx['dev_model']) ||
                        !empty($pEx['dev']['model']) ||
                        !empty($pEx['dev_serial_no']) ||
                        !empty($pEx['dev']['serial_no']) ||
                        !empty($pEx['dev_imei1']) ||
                        !empty($pEx['dev_imei_1']) ||
                        !empty($pEx['dev']['imei1']) ||
                        !empty($pEx['dev_imei2']) ||
                        !empty($pEx['dev_imei_2']) ||
                        !empty($pEx['dev']['imei2']) ||
                        !empty($pEx['dev_mac_address']) ||
                        !empty($pEx['dev']['mac_address']) ||
                        !empty($pEx['dev_storage']) ||
                        !empty($pEx['dev']['storage']) ||
                        !empty($pEx['storage_capacity']) ||
                        !empty($pEx['dev_storage_capacity']) ||
                        !empty($pEx['storage']) ||
                        !empty($pEx['dev_colour']) ||
                        !empty($pEx['dev']['colour']) ||
                        !empty($pEx['dev_color']) ||
                        !empty($pEx['colour']) ||
                        !empty($pEx['color']) ||
                        !empty($pEx['device_color']) ||
                        !empty($pEx['dev_marks']) ||
                        !empty($pEx['dev']['marks']) ||
                        !empty($pEx['dev_other_marks']) ||
                        !empty($pEx['marks']) ||
                        !empty($pEx['other_marks']) ||
                        !empty($pEx['distinguishing_marks']) ||
                        !empty($pEx['device_model']) ||
                        !empty($pEx['device_serial']) ||
                        !empty($case->dev_model) ||
                        !empty($case->dev_serial_no) ||
                        !empty($case->dev_imei1) ||
                        !empty($case->dev_imei2) ||
                        !empty($case->dev_mac_address) ||
                        !empty($case->dev_storage) ||
                        !empty($case->dev_colour) ||
                        !empty($case->dev_marks);
                    $isDevEx =
                        str_contains($catLower, 'device') ||
                        str_contains($catLower, 'electronic') ||
                        str_contains($catLower, 'digital') ||
                        str_contains($nameLower, 'phone') ||
                        str_contains($nameLower, 'laptop') ||
                        str_contains($nameLower, 'iphone') ||
                        str_contains($nameLower, 'samsung') ||
                        str_contains($nameLower, 'macbook') ||
                        str_contains($nameLower, 'hard drive') ||
                        $hasDeviceData;
                @endphp

                @if ($isVehEx)
                    @php
                        $vMake = !empty($pEx['veh_make']) && is_string($pEx['veh_make']) ? $pEx['veh_make'] : null;
                        $vModel = !empty($pEx['veh_model']) && is_string($pEx['veh_model']) ? $pEx['veh_model'] : null;
                        $vMakeModelFull = implode(' ', array_filter([$vMake, $vModel]));
                        if (empty($vMakeModelFull)) {
                            $vMakeModelFull =
                                $pEx['vehicle_make_model'] ??
                                ($pEx['seizure_name'] ??
                                    ($pEx['commodity_name'] ?? ($pEx['name'] ?? 'N/A')));
                        }
                        $vMakeModelFull = is_string($vMakeModelFull) ? $vMakeModelFull : 'N/A';

                        $vRegNo =
                            $pEx['veh_reg_no'] ??
                            ($pEx['vehicle_registration'] ??
                                ($pEx['registration_number'] ?? 'N/A'));
                        $vRegNo = is_string($vRegNo) ? $vRegNo : 'N/A';
                        $vVin =
                            $pEx['veh_vin'] ??
                            ($pEx['vehicle_chassis_vin'] ??
                                ($pEx['chassis_number'] ?? 'N/A'));
                        $vVin = is_string($vVin) ? $vVin : 'N/A';
                        $vType =
                            $pEx['veh_type'] ?? ($pEx['vehicle_type'] ?? 'Vehicle Exhibit');
                        $vType = is_string($vType) ? $vType : 'Vehicle Exhibit';
                        $vColor =
                            $pEx['veh_colour'] ?? ($pEx['vehicle_color_type'] ?? 'N/A');
                        $vColor = is_string($vColor) ? $vColor : 'N/A';
                        $vYear = $pEx['veh_year'] ?? ($pEx['vehicle_year'] ?? 'N/A');
                        $vYear = is_string($vYear) ? $vYear : 'N/A';
                        $vEngine =
                            $pEx['veh_engine_no'] ?? ($pEx['engine_number'] ?? 'N/A');
                        $vEngine = is_string($vEngine) ? $vEngine : 'N/A';
                        $vCountry =
                            $pEx['veh_country_reg'] ??
                            ($pEx['country_of_registration'] ?? 'N/A');
                        $vCountry = is_string($vCountry) ? $vCountry : 'N/A';
                        $vOwner =
                            $pEx['veh_owner'] ??
                            ($pEx['vehicle_owner'] ??
                                ($headingName ?? ($case->suspect_name ?? 'Primary Suspect')));
                        $vOwner = is_string($vOwner) ? $vOwner : 'Primary Suspect';
                        $vOwnerContact = $pEx['veh_owner_contact'] ?? ($pEx['vehicle_owner_contact'] ?? null);
                        $vOwnerContact = is_string($vOwnerContact) ? $vOwnerContact : null;
                        $vDriver = $pEx['veh_driver'] ?? ($pEx['vehicle_driver'] ?? 'N/A');
                        $vDriver = is_string($vDriver) ? $vDriver : 'N/A';
                        $vDocsAvailable = $pEx['veh_docs_available'] ?? ($pEx['vehicle_docs_available'] ?? null);
                        $vDocsAvailable = is_string($vDocsAvailable) ? $vDocsAvailable : null;
                        $vDocRef = $pEx['veh_doc_ref'] ?? ($pEx['vehicle_doc_ref'] ?? null);
                        $vDocRef = is_string($vDocRef) ? $vDocRef : null;
                        $vCondition = $pEx['veh_condition'] ?? ($pEx['vehicle_condition'] ?? null);
                        $vCondition = is_string($vCondition) ? $vCondition : null;
                        $vFuel = $pEx['veh_fuel'] ?? ($pEx['vehicle_fuel'] ?? null);
                        $vFuel = is_string($vFuel) ? $vFuel : null;
                        $vKeysRecv = $pEx['veh_keys_recv'] ?? ($pEx['vehicle_keys_recv'] ?? null);
                        $vKeysRecv = is_string($vKeysRecv) ? $vKeysRecv : null;
                        $vKeyCount = $pEx['veh_key_count'] ?? ($pEx['vehicle_key_count'] ?? null);
                        $vKeyCount = is_string($vKeyCount) ? $vKeyCount : null;
                        $vDamage = $pEx['veh_damage'] ?? ($pEx['vehicle_damage'] ?? null);
                        $vDamage = is_string($vDamage) ? $vDamage : null;
                        $vMods = $pEx['veh_mods'] ?? ($pEx['vehicle_mods'] ?? null);
                        $vMods = is_string($vMods) ? $vMods : null;
                        $vFeatures = $pEx['veh_features'] ?? ($pEx['vehicle_features'] ?? null);
                        $vFeatures = is_string($vFeatures) ? $vFeatures : null;
                        $vLoc = $pEx['seizure_location'] ?? ($pEx['location'] ?? 'N/A');
                        $vLoc = is_string($vLoc) ? $vLoc : 'N/A';
                        $vOfficer = $pEx['seizure_officer'] ?? ($pEx['officer'] ?? 'N/A');
                        $vOfficer = is_string($vOfficer) ? $vOfficer : 'N/A';
                        $vDate = !empty($pEx['seizure_date']) && is_string($pEx['seizure_date']) ? $pEx['seizure_date'] : null;
                        $vTime = !empty($pEx['seizure_time']) && is_string($pEx['seizure_time']) ? "@ {$pEx['seizure_time']}" : '';
                        $vDateTime = !empty($vDate) ? "{$vDate} {$vTime}" : 'N/A';
                        $vReason = $pEx['seizure_reason'] ?? 'N/A';
                        $vReason = is_string($vReason) ? $vReason : 'N/A';
                        $vPhotos = $extractAllPhotosHelper($pEx);
                    @endphp
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
                                    {{ strtoupper($vMakeModelFull) }}
                                </span>
                            </div>
                            <div class="flex items-center flex-wrap gap-2">
                                @if (!empty($vCondition))
                                    <span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 border border-emerald-200 px-2.5 py-1 rounded-md font-extrabold">
                                        {{ $vCondition }}
                                    </span>
                                @endif
                                @if (!empty($vKeysRecv))
                                    <span class="text-[10px] font-mono-code bg-blue-50 text-blue-800 border border-blue-200 px-2.5 py-1 rounded-md font-extrabold">
                                        Keys: {{ $vKeysRecv }} {{ !empty($vKeyCount) ? "({$vKeyCount})" : '' }}
                                    </span>
                                @endif
                                @if (!empty($vRegNo) && $vRegNo !== 'N/A')
                                    <span
                                        class="text-[10px] font-mono-code bg-amber-50 text-amber-900 border border-amber-200 px-2.5 py-1 rounded-md font-extrabold">
                                        REG #: {{ strtoupper($vRegNo) }}
                                    </span>
                                @endif
                                @if (!empty($vVin) && $vVin !== 'N/A')
                                    <span
                                        class="text-[10px] font-mono-code bg-slate-100 text-slate-800 border border-slate-300 px-2.5 py-1 rounded-md font-extrabold">
                                        VIN: {{ strtoupper($vVin) }}
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
                                        {{ $vMakeModelFull }}
                                    </div>
                                </div>
                                <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                        REGISTRATION NUMBER
                                    </div>
                                    <div class="font-extrabold text-amber-700 font-mono-code text-xs mt-1">
                                        {{ $vRegNo }}
                                    </div>
                                </div>
                                <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                        VIN / CHASSIS NUMBER
                                    </div>
                                    <div class="font-extrabold text-blue-700 font-mono-code text-xs mt-1">
                                        {{ $vVin }}
                                    </div>
                                </div>
                                <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                        COLOUR / FINISH
                                    </div>
                                    <div class="font-bold text-slate-900 text-xs mt-1">
                                        {{ $vColor }}
                                    </div>
                                </div>
                                <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                        VEHICLE TYPE / BODY
                                    </div>
                                    <div class="font-bold text-slate-900 text-xs mt-1">
                                        {{ $vType }}
                                    </div>
                                </div>
                                <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                        MANUFACTURE YEAR
                                    </div>
                                    <div class="font-bold text-slate-900 text-xs mt-1">
                                        {{ $vYear }}
                                    </div>
                                </div>
                                <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                        ENGINE NUMBER
                                    </div>
                                    <div class="font-bold text-slate-900 font-mono-code text-xs mt-1">
                                        {{ $vEngine }}
                                    </div>
                                </div>
                                <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                        REGISTERED COUNTRY
                                    </div>
                                    <div class="font-bold text-slate-900 text-xs mt-1">
                                        {{ $vCountry }}
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
                                        {{ $vOwner }}
                                    </div>
                                </div>
                                @if (!empty($vOwnerContact))
                                    <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                        <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                            OWNER CONTACT PHONE
                                        </div>
                                        <div class="font-bold text-slate-900 text-xs mt-1 font-mono-code">
                                            {{ $vOwnerContact }}
                                        </div>
                                    </div>
                                @endif
                                <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                        DRIVER AT ARREST
                                    </div>
                                    <div class="font-bold text-slate-900 text-xs mt-1">
                                        {{ $vDriver }}
                                    </div>
                                </div>
                                @if (!empty($vDocsAvailable) || !empty($vDocRef))
                                    <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                        <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                            VEHICLE DOCS &amp; LOG BOOK
                                        </div>
                                        <div class="font-bold text-blue-800 text-xs mt-1 font-mono-code">
                                            {{ $vDocsAvailable ?: 'Available' }} {{ !empty($vDocRef) ? "(Ref: {$vDocRef})" : '' }}
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
                                @if (!empty($vCondition))
                                    <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                        <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                            OVERALL CONDITION
                                        </div>
                                        <div class="font-bold text-slate-900 text-xs mt-1">
                                            {{ $vCondition }}
                                        </div>
                                    </div>
                                @endif
                                @if (!empty($vFuel))
                                    <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                        <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                            FUEL LEVEL AT IMPOUND
                                        </div>
                                        <div class="font-bold text-amber-800 text-xs mt-1">
                                            {{ $vFuel }}
                                        </div>
                                    </div>
                                @endif
                                @if (!empty($vKeysRecv))
                                    <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                        <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                            KEYS RECEIVED
                                        </div>
                                        <div class="font-bold text-slate-900 text-xs mt-1">
                                            {{ $vKeysRecv }} {{ !empty($vKeyCount) ? "— {$vKeyCount}" : '' }}
                                        </div>
                                    </div>
                                @endif
                                <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                        CONFISCATION TIMESTAMP
                                    </div>
                                    <div class="font-bold text-slate-900 text-xs mt-1 font-mono-code">
                                        {{ $vDateTime }}
                                    </div>
                                </div>
                                <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                        SEIZING OFFICER
                                    </div>
                                    <div class="font-bold text-slate-900 text-xs mt-1">
                                        {{ $vOfficer }}
                                    </div>
                                </div>
                                <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors {{ empty($vCondition) || empty($vFuel) || empty($vKeysRecv) ? '' : 'col-span-2' }}">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                        SEIZURE LOCATION
                                    </div>
                                    <div class="font-bold text-slate-900 text-xs mt-1">
                                        {{ $vLoc }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Section 4: Physical Inspection & Special Disclosures --}}
                        @if (!empty($vDamage) || !empty($vMods) || !empty($vFeatures) || (!empty($vReason) && $vReason !== 'N/A'))
                            <div class="space-y-2.5 pt-3 border-t border-slate-100">
                                <div class="flex items-center gap-2">
                                    <span class="w-1.5 h-3.5 bg-slate-600 rounded-full"></span>
                                    <span class="text-[11px] font-mono-code font-extrabold uppercase tracking-wider text-slate-800">
                                        Forensic Inspection &amp; Special Disclosures
                                    </span>
                                </div>
                                <div class="grid grid-cols-1 gap-2.5">
                                    @if (!empty($vDamage))
                                        <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                            <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                                RECORDED VEHICLE DAMAGE / DEFECTS
                                            </div>
                                            <div class="font-bold text-slate-900 text-xs mt-1 leading-relaxed">
                                                {{ $vDamage }}
                                            </div>
                                        </div>
                                    @endif

                                    @if (!empty($vMods))
                                        <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                            <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                                MODIFICATIONS / CUSTOM TRAP COMPARTMENTS
                                            </div>
                                            <div class="font-bold text-slate-900 text-xs mt-1 leading-relaxed">
                                                {{ $vMods }}
                                            </div>
                                        </div>
                                    @endif

                                    @if (!empty($vFeatures))
                                        <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                            <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                                NOTABLE IDENTIFYING FEATURES
                                            </div>
                                            <div class="font-bold text-slate-900 text-xs mt-1 leading-relaxed">
                                                {{ $vFeatures }}
                                            </div>
                                        </div>
                                    @endif

                                    @if (!empty($vReason) && $vReason !== 'N/A')
                                        <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                            <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                                CONFISCATION BASIS / REASON
                                            </div>
                                            <div class="font-bold text-slate-900 text-xs mt-1">
                                                {{ $vReason }}
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Section 5: Evidence Photos --}}
                        @if (!empty($vPhotos) && count($vPhotos) > 0)
                            <div class="pt-3 border-t border-slate-200 space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="w-1.5 h-3.5 bg-blue-500 rounded-full"></span>
                                        <span class="text-[11px] font-mono-code font-extrabold text-slate-800 uppercase tracking-wider">
                                            Vehicle Evidence Photos
                                        </span>
                                    </div>
                                    <span class="text-[10px] font-mono-code bg-blue-50 text-blue-700 border border-blue-200 px-2.5 py-0.5 rounded font-extrabold">
                                        {{ count($vPhotos) }} Photo(s) Attached
                                    </span>
                                </div>
                                <div class="flex flex-wrap gap-2.5">
                                    @foreach ($vPhotos as $vpIdx => $vpUrl)
                                        @php $vpFormatted = $formatPhotoUrlHelper($vpUrl); @endphp
                                        <div class="w-20 h-20 rounded-xl overflow-hidden border border-slate-200 bg-slate-100 cursor-pointer group relative shadow-2xs hover:shadow-md hover:border-blue-400 transition-all"
                                            onclick="openMugshotModal('{{ $vpFormatted }}', 'Vehicle Photo #{{ $vpIdx + 1 }}')">
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

                @if ($isDevEx)
                    @php
                        if (!isset($resolveDevField) || !is_callable($resolveDevField)) {
                            $resolveDevField = function(...$vals) {
                                foreach ($vals as $v) {
                                    if ($v !== null && !is_array($v) && !is_object($v) && trim((string)$v) !== '') return trim((string)$v);
                                }
                                return 'N/A';
                            };
                        }
                        $dModel = $resolveDevField(
                            $pEx['dev_model'] ?? null,
                            $pEx['dev']['model'] ?? null,
                            $pEx['device_model'] ?? null,
                            $pEx['model'] ?? null,
                            $case->dev_model ?? null,
                            $pEx['seizure_name'] ?? null,
                            $pEx['commodity_name'] ?? null,
                            $pEx['name'] ?? null
                        );
                        $dSerial = $resolveDevField(
                            $pEx['dev_serial_no'] ?? null,
                            $pEx['dev']['serial_no'] ?? null,
                            $pEx['device_serial'] ?? null,
                            $pEx['serial_no'] ?? null,
                            $pEx['serial_number'] ?? null,
                            $case->dev_serial_no ?? null
                        );
                        $dImei1 = $resolveDevField(
                            $pEx['dev_imei1'] ?? null,
                            $pEx['dev']['imei1'] ?? null,
                            $pEx['dev_imei_1'] ?? null,
                            $pEx['imei1'] ?? null,
                            $pEx['imei_1'] ?? null,
                            $case->dev_imei1 ?? null
                        );
                        $dImei2 = $resolveDevField(
                            $pEx['dev_imei2'] ?? null,
                            $pEx['dev']['imei2'] ?? null,
                            $pEx['dev_imei_2'] ?? null,
                            $pEx['imei2'] ?? null,
                            $pEx['imei_2'] ?? null,
                            $case->dev_imei2 ?? null
                        );
                        $dMac = $resolveDevField(
                            $pEx['dev_mac_address'] ?? null,
                            $pEx['dev']['mac_address'] ?? null,
                            $pEx['mac_address'] ?? null,
                            $case->dev_mac_address ?? null
                        );
                        $dStorage = $resolveDevField(
                            $pEx['dev_storage'] ?? null,
                            $pEx['dev']['storage'] ?? null,
                            $pEx['storage_capacity'] ?? null,
                            $pEx['dev_storage_capacity'] ?? null,
                            $pEx['storage'] ?? null,
                            $case->dev_storage ?? null
                        );
                        $dColor = $resolveDevField(
                            $pEx['dev_colour'] ?? null,
                            $pEx['dev']['colour'] ?? null,
                            $pEx['dev_color'] ?? null,
                            $pEx['colour'] ?? null,
                            $pEx['color'] ?? null,
                            $pEx['device_color'] ?? null,
                            $case->dev_colour ?? null
                        );
                        $dMarks = $resolveDevField(
                            $pEx['dev_marks'] ?? null,
                            $pEx['dev']['marks'] ?? null,
                            $pEx['dev_other_marks'] ?? null,
                            $pEx['marks'] ?? null,
                            $pEx['other_marks'] ?? null,
                            $pEx['distinguishing_marks'] ?? null,
                            $case->dev_marks ?? null
                        );
                    @endphp
                    <div
                        class="col-span-full bg-white text-slate-800 p-5 rounded-2xl border border-slate-200/90 space-y-4 mt-2 shadow-xs">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                            <div class="flex items-center flex-wrap gap-2">
                                <span
                                    class="px-2.5 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200 font-mono-code font-extrabold text-[10px] rounded-md uppercase tracking-wider">ELECTRONIC DEVICE FORENSIC SPECIFICATIONS</span>
                                <span
                                    class="text-sm font-black text-slate-900 font-mono-code tracking-tight">{{ strtoupper($dModel) }}</span>
                            </div>
                            @if (!empty($dSerial) && $dSerial !== 'N/A')
                                <span
                                    class="text-[10px] font-mono-code bg-slate-100 text-slate-800 px-2.5 py-1 rounded-md font-extrabold border border-slate-300">SERIAL:
                                    {{ strtoupper($dSerial) }}</span>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                    DEVICE MODEL</div>
                                <div class="font-extrabold text-slate-900 text-xs mt-1">
                                    {{ $dModel }}</div>
                            </div>
                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                    SERIAL NUMBER</div>
                                <div class="font-extrabold text-amber-700 font-mono-code text-xs mt-1">
                                    {{ $dSerial }}</div>
                            </div>
                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                    IMEI 1</div>
                                <div class="font-extrabold text-blue-700 font-mono-code text-xs mt-1">
                                    {{ $dImei1 }}</div>
                            </div>
                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                    IMEI 2</div>
                                <div class="font-extrabold text-blue-700 font-mono-code text-xs mt-1">
                                    {{ $dImei2 }}</div>
                            </div>
                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                    MAC ADDRESS</div>
                                <div class="font-mono-code font-bold text-slate-900 text-xs mt-1">
                                    {{ $dMac }}</div>
                            </div>
                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                    STORAGE CAPACITY</div>
                                <div class="font-bold text-emerald-800 text-xs mt-1">
                                    {{ $dStorage }}</div>
                            </div>
                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                    COLOUR / FINISH</div>
                                <div class="font-bold text-slate-900 text-xs mt-1">
                                    {{ $dColor }}</div>
                            </div>
                            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                    OTHER MARKS / CONDITION</div>
                                <div class="font-bold text-slate-900 text-xs mt-1">
                                    {{ $dMarks }}</div>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($isWeaponEx)
                    @php
                        $wModel = $pEx['weapon_model'] ?? ($pEx['wpn_model'] ?? ($pEx['weapon_type'] ?? ($pEx['seizure_name_weapon'] ?? ($pEx['seizure_name'] ?? 'Firearm / Weapon Exhibit'))));
                        $wModel = is_string($wModel) ? $wModel : 'Firearm / Weapon Exhibit';
                        $wSerial = $pEx['weapon_serial_number'] ?? ($pEx['weapon_serial'] ?? ($pEx['wpn_serial_no'] ?? null));
                        $wSerial = is_string($wSerial) ? $wSerial : null;
                        $wCalibre = $pEx['weapon_calibre'] ?? ($pEx['wpn_calibre'] ?? null);
                        $wCalibre = is_string($wCalibre) ? $wCalibre : null;
                        $wColour = $pEx['weapon_color'] ?? ($pEx['weapon_colour'] ?? ($pEx['wpn_colour'] ?? null));
                        $wColour = is_string($wColour) ? $wColour : null;
                        $wSize = $pEx['weapon_size_length'] ?? ($pEx['weapon_size'] ?? ($pEx['wpn_size'] ?? null));
                        $wSize = is_string($wSize) ? $wSize : null;
                        $wLoaded = $pEx['weapon_loaded_status'] ?? ($pEx['wpn_loaded_status'] ?? null);
                        $wLoaded = is_string($wLoaded) ? $wLoaded : null;
                        $wCondition = $pEx['weapon_condition_at_recovery'] ?? ($pEx['weapon_condition'] ?? ($pEx['wpn_condition'] ?? null));
                        $wCondition = is_string($wCondition) ? $wCondition : null;
                        $wLoc = $pEx['weapon_location_found'] ?? ($pEx['wpn_location_found'] ?? null);
                        $wLoc = is_string($wLoc) ? $wLoc : null;
                        $wMarks = $pEx['weapon_distinguishing_marks'] ?? ($pEx['wpn_marks'] ?? null);
                        $wMarks = is_string($wMarks) ? $wMarks : null;
                        $wPhotos = $extractAllPhotosHelper($pEx);
                    @endphp
                    <div
                        class="col-span-full bg-white text-slate-800 p-5 rounded-2xl border border-slate-200/90 space-y-4 mt-2 shadow-xs">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                            <div class="flex items-center flex-wrap gap-2">
                                <span
                                    class="px-2.5 py-1 bg-rose-50 text-rose-700 border border-rose-200 font-mono-code font-extrabold text-[10px] rounded-md uppercase tracking-wider">FIREARM
                                    / WEAPON SPECIFIC DETAILS</span>
                                <span
                                    class="text-sm font-black text-slate-900 font-mono-code tracking-tight">{{ $wModel }}</span>
                            </div>
                            <div class="flex items-center flex-wrap gap-2">
                                @if (!empty($wCondition))
                                    <span class="text-[10px] font-mono-code bg-emerald-50 text-emerald-800 border border-emerald-200 px-2.5 py-1 rounded-md font-extrabold">{{ $wCondition }}</span>
                                @endif
                                @if (!empty($wLoaded))
                                    <span class="text-[10px] font-mono-code bg-rose-50 text-rose-800 border border-rose-200 px-2.5 py-1 rounded-md font-extrabold">{{ $wLoaded }}</span>
                                @endif
                                @if (!empty($wSerial))
                                    <span
                                        class="text-[10px] font-mono-code bg-amber-50 text-amber-900 border border-amber-200 px-2.5 py-1 rounded-md font-extrabold">SERIAL
                                        #: {{ $wSerial }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                            @if (!empty($wModel))
                                <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                        MODEL / TYPE</div>
                                    <div class="font-extrabold text-slate-900 text-xs mt-1">
                                        {{ $wModel }}</div>
                                </div>
                            @endif
                            @if (!empty($wCalibre))
                                <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                        CALIBRE / GAUGE</div>
                                    <div class="font-extrabold text-amber-700 font-mono-code text-xs mt-1">
                                        {{ $wCalibre }}</div>
                                </div>
                            @endif
                            @if (!empty($wColour))
                                <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                        COLOUR / FINISH</div>
                                    <div class="font-bold text-slate-900 text-xs mt-1">
                                        {{ $wColour }}</div>
                                </div>
                            @endif
                            @if (!empty($wSize))
                                <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                        SIZE / LENGTH</div>
                                    <div class="font-bold text-slate-900 text-xs mt-1">
                                        {{ $wSize }}</div>
                                </div>
                            @endif
                            @if (!empty($wLoaded))
                                <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                        LOADED STATUS</div>
                                    <div class="font-extrabold text-rose-700 text-xs mt-1">
                                        {{ $wLoaded }}</div>
                                </div>
                            @endif
                            @if (!empty($wCondition))
                                <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                        CONDITION AT RECOVERY</div>
                                    <div class="font-extrabold text-emerald-800 text-xs mt-1">
                                        {{ $wCondition }}</div>
                                </div>
                            @endif
                            @if (!empty($wLoc))
                                <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors col-span-2">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                        EXACT LOCATION FOUND</div>
                                    <div class="font-bold text-slate-900 text-xs mt-1">
                                        {{ $wLoc }}</div>
                                </div>
                            @endif
                            @if (!empty($wMarks))
                                <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/90 hover:bg-slate-50 transition-colors col-span-full">
                                    <div class="text-[10px] font-mono-code font-bold text-slate-600 uppercase tracking-wider">
                                        DISTINGUISHING MARKS / ENGRAVINGS</div>
                                    <div class="font-bold text-slate-900 text-xs mt-1">
                                        {{ $wMarks }}</div>
                                </div>
                            @endif
                            @if (!empty($wPhotos) && count($wPhotos) > 0)
                                <div class="col-span-full pt-3 border-t border-slate-200 space-y-2 mt-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-mono-code font-extrabold text-slate-700 uppercase tracking-wider">WEAPON EVIDENCE PHOTOS</span>
                                        <span class="text-[10px] font-mono-code bg-rose-50 text-rose-700 border border-rose-200 px-2 py-0.5 rounded font-extrabold">{{ count($wPhotos) }} Photo(s) Attached</span>
                                    </div>
                                    <div class="flex flex-wrap gap-2.5">
                                        @foreach ($wPhotos as $wpIdx => $wpUrl)
                                            @php $wpFormatted = $formatPhotoUrlHelper($wpUrl); @endphp
                                            <div class="w-20 h-20 rounded-xl border border-slate-200 bg-slate-100 cursor-pointer group relative overflow-hidden shadow-2xs hover:shadow-md hover:border-rose-400 transition-all"
                                                onclick="openMugshotModal('{{ $wpFormatted }}', 'Weapon Photo #{{ $wpIdx + 1 }}')">
                                                <img src="{{ $wpFormatted }}"
                                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                                                    alt="Weapon Photo"
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
                    </div>
                @endif
            @endforeach
        </div>

        @if (!empty($galleryPhotos) && count($galleryPhotos) > 0)
            <div class="pt-4 border-t border-slate-200 space-y-3 mt-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-3.5 bg-emerald-600 rounded-full"></span>
                        <span class="text-[11px] font-black text-slate-900 uppercase font-mono-code">SEIZURE &amp; EXHIBIT EVIDENCE GALLERY ({{ strtoupper($headingName) }})</span>
                    </div>
                    <span class="text-[10px] font-mono-code font-bold text-emerald-900 bg-emerald-100 px-2.5 py-0.5 rounded-md border border-emerald-300">
                        {{ count($galleryPhotos) }} Attached Photo(s)
                    </span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3">
                    @foreach ($galleryPhotos as $pIdx => $pUrl)
                        @php
                            $pUrlFormatted = $formatPhotoUrlHelper($pUrl);
                        @endphp
                        <div class="p-1.5 bg-white border border-slate-200/90 rounded-xl space-y-1.5 text-center shadow-xs group hover:border-emerald-500 hover:shadow-md transition-all">
                            <div class="w-full h-44 sm:h-48 bg-slate-50/90 rounded-lg overflow-hidden border border-slate-200 cursor-pointer relative flex items-center justify-center p-1.5"
                                data-img-src="{{ $pUrlFormatted }}"
                                data-img-title="Exhibit Photo #{{ $pIdx + 1 }} — {{ $headingName }}"
                                onclick="openMugshotModal(this.getAttribute('data-img-src'), this.getAttribute('data-img-title'))">
                                <img src="{{ $pUrlFormatted }}"
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
                                <span class="font-extrabold text-slate-800 uppercase">Photo #{{ $pIdx + 1 }}</span>
                                <span class="text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded font-bold">Full View</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endif
