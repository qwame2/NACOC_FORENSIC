@php
    $exList = $dossierExhibits ?? ($primaryExhibits ?? []);
    $badgeText = $dossierBadgeText ?? 'PRIMARY EXHIBIT DOSSIER';
    $headingName = $dossierHeadingName ?? ($sName ?? 'Primary Suspect');
    $headingText = $dossierHeadingText ?? "Primary Exhibits — {$headingName}";

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
                    <div class="font-extrabold text-slate-900 text-xs mt-0.5">
                        {{ $pEx['seizure_name'] ?? ($pEx['commodity_name'] ?? ($pEx['name'] ?? 'N/A')) }}
                    </div>
                </div>
                <div class="p-3 rounded-xl bg-white border border-slate-200 shadow-2xs">
                    <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">
                        Quantity / Weight</div>
                    <div class="font-extrabold text-slate-900 font-mono-code text-xs mt-0.5">
                        @if (!empty($pEx['seizure_quantity']) || !empty($pEx['seizure_weight']))
                            {{ implode(' • ', array_filter([!empty($pEx['seizure_quantity']) ? "Qty: {$pEx['seizure_quantity']}" : null, !empty($pEx['seizure_weight']) ? "Wt: {$pEx['seizure_weight']} " . ($pEx['seizure_unit'] ?? '') : null])) }}
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
                        !empty($pEx['dev_serial_no']) ||
                        !empty($pEx['dev_imei1']) ||
                        !empty($pEx['dev_imei_1']) ||
                        !empty($pEx['dev_imei2']) ||
                        !empty($pEx['dev_imei_2']) ||
                        !empty($pEx['dev_mac_address']) ||
                        !empty($pEx['dev_storage']) ||
                        !empty($pEx['storage_capacity']) ||
                        !empty($pEx['dev_storage_capacity']) ||
                        !empty($pEx['storage']) ||
                        !empty($pEx['dev_colour']) ||
                        !empty($pEx['dev_color']) ||
                        !empty($pEx['colour']) ||
                        !empty($pEx['color']) ||
                        !empty($pEx['device_color']) ||
                        !empty($pEx['dev_marks']) ||
                        !empty($pEx['dev_other_marks']) ||
                        !empty($pEx['marks']) ||
                        !empty($pEx['other_marks']) ||
                        !empty($pEx['distinguishing_marks']) ||
                        !empty($pEx['device_model']) ||
                        !empty($pEx['device_serial']) ||
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
                        $vMake = !empty($pEx['veh_make']) ? $pEx['veh_make'] : null;
                        $vModel = !empty($pEx['veh_model']) ? $pEx['veh_model'] : null;
                        $vMakeModelFull = implode(' ', array_filter([$vMake, $vModel]));
                        if (empty($vMakeModelFull)) {
                            $vMakeModelFull =
                                $pEx['vehicle_make_model'] ??
                                ($pEx['seizure_name'] ??
                                    ($pEx['commodity_name'] ?? ($pEx['name'] ?? 'N/A')));
                        }

                        $vRegNo =
                            $pEx['veh_reg_no'] ??
                            ($pEx['vehicle_registration'] ??
                                ($pEx['registration_number'] ?? 'N/A'));
                        $vVin =
                            $pEx['veh_vin'] ??
                            ($pEx['vehicle_chassis_vin'] ??
                                ($pEx['chassis_number'] ?? 'N/A'));
                        $vType =
                            $pEx['veh_type'] ?? ($pEx['vehicle_type'] ?? 'Vehicle Exhibit');
                        $vColor =
                            $pEx['veh_colour'] ?? ($pEx['vehicle_color_type'] ?? 'N/A');
                        $vYear = $pEx['veh_year'] ?? ($pEx['vehicle_year'] ?? 'N/A');
                        $vEngine =
                            $pEx['veh_engine_no'] ?? ($pEx['engine_number'] ?? 'N/A');
                        $vCountry =
                            $pEx['veh_country_reg'] ??
                            ($pEx['country_of_registration'] ?? 'N/A');
                        $vOwner =
                            $pEx['veh_owner'] ??
                            ($pEx['vehicle_owner'] ??
                                ($headingName ?? ($case->suspect_name ?? 'Primary Suspect')));
                        $vDriver = $pEx['veh_driver'] ?? ($pEx['vehicle_driver'] ?? 'N/A');
                        $vLoc = $pEx['seizure_location'] ?? ($pEx['location'] ?? 'N/A');
                        $vOfficer = $pEx['seizure_officer'] ?? ($pEx['officer'] ?? 'N/A');
                        $vDate = !empty($pEx['seizure_date']) ? $pEx['seizure_date'] : null;
                        $vTime = !empty($pEx['seizure_time']) ? "@ {$pEx['seizure_time']}" : '';
                        $vDateTime = !empty($vDate) ? "{$vDate} {$vTime}" : 'N/A';
                        $vReason = $pEx['seizure_reason'] ?? 'N/A';
                    @endphp
                    <div
                        class="col-span-full bg-slate-900 text-white p-4 rounded-xl border border-blue-500/40 space-y-3 mt-2 shadow-md">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                            <div class="flex items-center space-x-2">
                                <span
                                    class="px-2 py-0.5 bg-blue-600 text-white font-mono-code font-extrabold text-[10px] rounded uppercase tracking-wider">VEHICLE
                                    / VESSEL SPECIFIC DETAILS</span>
                                <span
                                    class="text-xs font-black text-slate-100 font-mono-code">{{ strtoupper($vMakeModelFull) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                @if (!empty($vRegNo) && $vRegNo !== 'N/A')
                                    <span
                                        class="text-[10px] font-mono-code bg-slate-800 px-2.5 py-1 rounded text-amber-400 font-extrabold border border-amber-500/30">REG
                                        #: {{ strtoupper($vRegNo) }}</span>
                                @endif
                                @if (!empty($vVin) && $vVin !== 'N/A')
                                    <span
                                        class="text-[10px] font-mono-code bg-slate-800 px-2.5 py-1 rounded text-blue-300 font-extrabold border border-blue-500/30">VIN:
                                        {{ strtoupper($vVin) }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                    MAKE / MODEL / TRIM</div>
                                <div class="font-black text-white text-xs mt-0.5">
                                    {{ $vMakeModelFull }}</div>
                            </div>
                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                    REGISTRATION NUMBER</div>
                                <div class="font-extrabold text-amber-400 font-mono-code text-xs mt-0.5">
                                    {{ $vRegNo }}</div>
                            </div>
                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                    VIN / CHASSIS NUMBER</div>
                                <div class="font-extrabold text-blue-300 font-mono-code text-xs mt-0.5">
                                    {{ $vVin }}</div>
                            </div>
                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                    COLOUR / FINISH</div>
                                <div class="font-bold text-slate-200 text-xs mt-0.5">
                                    {{ $vColor }}</div>
                            </div>
                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                    VEHICLE TYPE / BODY</div>
                                <div class="font-bold text-slate-200 text-xs mt-0.5">
                                    {{ $vType }}</div>
                            </div>
                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                    MANUFACTURE YEAR</div>
                                <div class="font-bold text-slate-200 text-xs mt-0.5">
                                    {{ $vYear }}</div>
                            </div>
                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                    ENGINE NUMBER</div>
                                <div class="font-bold text-slate-200 text-xs mt-0.5">
                                    {{ $vEngine }}</div>
                            </div>
                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                    REGISTERED COUNTRY</div>
                                <div class="font-bold text-slate-200 text-xs mt-0.5">
                                    {{ $vCountry }}</div>
                            </div>
                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                    DOCUMENTED OWNER</div>
                                <div class="font-extrabold text-amber-300 text-xs mt-0.5">
                                    {{ $vOwner }}</div>
                            </div>
                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                    DRIVER AT ARREST</div>
                                <div class="font-bold text-slate-200 text-xs mt-0.5">
                                    {{ $vDriver }}</div>
                            </div>
                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                    CONFISCATION TIMESTAMP</div>
                                <div class="font-bold text-slate-200 text-xs mt-0.5">
                                    {{ $vDateTime }}</div>
                            </div>
                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                    SEIZING OFFICER</div>
                                <div class="font-bold text-slate-200 text-xs mt-0.5">
                                    {{ $vOfficer }}</div>
                            </div>
                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700 col-span-2">
                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                    SEIZURE LOCATION</div>
                                <div class="font-bold text-slate-200 text-xs mt-0.5">
                                    {{ $vLoc }}</div>
                            </div>
                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700 col-span-2">
                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                    CONFISCATION BASIS / REASON</div>
                                <div class="font-bold text-slate-200 text-xs mt-0.5">
                                    {{ $vReason }}</div>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($isDevEx)
                    @php
                        $dModel = $pEx['dev_model'] ?? ($pEx['device_model'] ?? ($pEx['seizure_name'] ?? ($pEx['commodity_name'] ?? ($pEx['name'] ?? 'N/A'))));
                        $dSerial = $pEx['dev_serial_no'] ?? ($pEx['device_serial'] ?? 'N/A');
                        $dImei1 = $pEx['dev_imei1'] ?? ($pEx['dev_imei_1'] ?? ($pEx['imei1'] ?? 'N/A'));
                        $dImei2 = $pEx['dev_imei2'] ?? ($pEx['dev_imei_2'] ?? ($pEx['imei2'] ?? 'N/A'));
                        $dMac = $pEx['dev_mac_address'] ?? ($pEx['mac_address'] ?? 'N/A');
                        $dStorage = $pEx['dev_storage'] ?? ($pEx['storage_capacity'] ?? ($pEx['dev_storage_capacity'] ?? ($pEx['storage'] ?? ($case->dev_storage ?? 'N/A'))));
                        $dColor = $pEx['dev_colour'] ?? ($pEx['dev_color'] ?? ($pEx['colour'] ?? ($pEx['color'] ?? ($pEx['device_color'] ?? ($case->dev_colour ?? 'N/A')))));
                        $dMarks = $pEx['dev_marks'] ?? ($pEx['dev_other_marks'] ?? ($pEx['marks'] ?? ($pEx['other_marks'] ?? ($pEx['distinguishing_marks'] ?? ($case->dev_marks ?? 'N/A')))));
                    @endphp
                    <div
                        class="col-span-full bg-slate-900 text-white p-4 rounded-xl border border-emerald-500/40 space-y-3 mt-2 shadow-md">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                            <div class="flex items-center space-x-2">
                                <span
                                    class="px-2 py-0.5 bg-emerald-600 text-white font-mono-code font-extrabold text-[10px] rounded uppercase tracking-wider">ELECTRONIC
                                    / DIGITAL DEVICE DETAILS</span>
                                <span
                                    class="text-xs font-black text-slate-100 font-mono-code">{{ strtoupper($dModel) }}</span>
                            </div>
                            @if (!empty($dSerial) && $dSerial !== 'N/A')
                                <span
                                    class="text-[10px] font-mono-code bg-slate-800 px-2.5 py-1 rounded text-emerald-400 font-extrabold border border-emerald-500/30">SERIAL:
                                    {{ strtoupper($dSerial) }}</span>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                    DEVICE MODEL</div>
                                <div class="font-black text-white text-xs mt-0.5">
                                    {{ $dModel }}</div>
                            </div>
                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                    SERIAL NUMBER</div>
                                <div class="font-extrabold text-amber-400 font-mono-code text-xs mt-0.5">
                                    {{ $dSerial }}</div>
                            </div>
                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                    IMEI 1</div>
                                <div class="font-extrabold text-blue-300 font-mono-code text-xs mt-0.5">
                                    {{ $dImei1 }}</div>
                            </div>
                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                    IMEI 2</div>
                                <div class="font-extrabold text-blue-300 font-mono-code text-xs mt-0.5">
                                    {{ $dImei2 }}</div>
                            </div>
                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                    MAC ADDRESS</div>
                                <div class="font-mono-code font-bold text-slate-200 text-xs mt-0.5">
                                    {{ $dMac }}</div>
                            </div>
                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                    STORAGE CAPACITY</div>
                                <div class="font-bold text-emerald-400 text-xs mt-0.5">
                                    {{ $dStorage }}</div>
                            </div>
                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                    COLOUR / FINISH</div>
                                <div class="font-bold text-slate-200 text-xs mt-0.5">
                                    {{ $dColor }}</div>
                            </div>
                            <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                    OTHER MARKS / CONDITION</div>
                                <div class="font-bold text-slate-200 text-xs mt-0.5">
                                    {{ $dMarks }}</div>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($isWeaponEx)
                    <div
                        class="col-span-full bg-slate-900 text-white p-4 rounded-xl border border-red-500/40 space-y-3 mt-2 shadow-md">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                            <div class="flex items-center space-x-2">
                                <span
                                    class="px-2 py-0.5 bg-red-600 text-white font-mono-code font-extrabold text-[10px] rounded uppercase tracking-wider">FIREARM
                                    / WEAPON SPECIFIC DETAILS</span>
                                <span
                                    class="text-xs font-black text-slate-200">{{ $pEx['weapon_model'] ?? ($pEx['seizure_name'] ?? 'Firearm / Weapon Exhibit') }}</span>
                            </div>
                            @if (!empty($pEx['weapon_serial_number']))
                                <span
                                    class="text-[10px] font-mono-code bg-slate-800 px-2.5 py-1 rounded text-amber-400 font-extrabold border border-amber-500/30">SERIAL
                                    #: {{ $pEx['weapon_serial_number'] }}</span>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                            @if (!empty($pEx['weapon_model']))
                                <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                    <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                        MODEL / TYPE</div>
                                    <div class="font-bold text-white text-xs mt-0.5">
                                        {{ $pEx['weapon_model'] }}</div>
                                </div>
                            @endif
                            @if (!empty($pEx['weapon_calibre']))
                                <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                    <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                        CALIBRE / GAUGE</div>
                                    <div class="font-extrabold text-amber-400 font-mono-code text-xs mt-0.5">
                                        {{ $pEx['weapon_calibre'] }}</div>
                                </div>
                            @endif
                            @if (!empty($pEx['weapon_color']))
                                <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                    <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                        COLOUR / FINISH</div>
                                    <div class="font-bold text-slate-200 text-xs mt-0.5">
                                        {{ $pEx['weapon_color'] }}</div>
                                </div>
                            @endif
                            @if (!empty($pEx['weapon_size_length']))
                                <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                    <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                        SIZE / LENGTH</div>
                                    <div class="font-bold text-slate-200 text-xs mt-0.5">
                                        {{ $pEx['weapon_size_length'] }}</div>
                                </div>
                            @endif
                            @if (!empty($pEx['weapon_loaded_status']))
                                <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                    <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                        LOADED STATUS</div>
                                    <div class="font-extrabold text-red-400 text-xs mt-0.5">
                                        {{ $pEx['weapon_loaded_status'] }}</div>
                                </div>
                            @endif
                            @if (!empty($pEx['weapon_condition_at_recovery']))
                                <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                    <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                        CONDITION AT RECOVERY</div>
                                    <div class="font-extrabold text-emerald-400 text-xs mt-0.5">
                                        {{ $pEx['weapon_condition_at_recovery'] }}</div>
                                </div>
                            @endif
                            @if (!empty($pEx['weapon_location_found']))
                                <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700 col-span-2">
                                    <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                        EXACT LOCATION FOUND</div>
                                    <div class="font-bold text-slate-100 text-xs mt-0.5">
                                        {{ $pEx['weapon_location_found'] }}</div>
                                </div>
                            @endif
                            @if (!empty($pEx['weapon_distinguishing_marks']))
                                <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700 col-span-full">
                                    <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">
                                        DISTINGUISHING MARKS / ENGRAVINGS</div>
                                    <div class="font-bold text-slate-100 text-xs mt-0.5">
                                        {{ $pEx['weapon_distinguishing_marks'] }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        @if (!empty($galleryPhotos) && count($galleryPhotos) > 0)
            <div class="pt-3 border-t border-slate-200 space-y-2 mt-3">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-black text-slate-900 uppercase font-mono-code">SEIZURE &amp; EXHIBIT EVIDENCE GALLERY ({{ strtoupper($headingName) }})</span>
                    <span class="text-[10px] font-mono-code font-bold text-emerald-900 bg-emerald-100 px-2 py-0.5 rounded border border-emerald-300">
                        {{ count($galleryPhotos) }} Attached Photo(s)
                    </span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3">
                    @foreach ($galleryPhotos as $pIdx => $pUrl)
                        @php
                            $pUrlFormatted = $formatPhotoUrlHelper($pUrl);
                        @endphp
                        <div class="p-1 bg-white border border-slate-200 rounded-xl space-y-1 text-center shadow-2xs group hover:border-emerald-500 transition-colors">
                            <div class="w-full h-24 bg-slate-100 rounded-lg overflow-hidden border border-slate-200 cursor-pointer relative"
                                data-img-src="{{ $pUrlFormatted }}"
                                data-img-title="Exhibit Photo #{{ $pIdx + 1 }} — {{ $headingName }}"
                                onclick="openMugshotModal(this.getAttribute('data-img-src'), this.getAttribute('data-img-title'))">
                                <img src="{{ $pUrlFormatted }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                                    alt="Exhibit Photo"
                                    onerror="if(!this.dataset.triedStorage &amp;&amp; !this.src.startsWith('data:')){this.dataset.triedStorage=1; this.src='{{ asset('storage/') }}/' + this.src.split('/').pop();}">
                            </div>
                            <div class="text-[9px] font-mono-code font-bold text-slate-600 uppercase">
                                Exhibit Photo #{{ $pIdx + 1 }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endif
