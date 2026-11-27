@if($exhibits->isEmpty())
    <!-- EMPTY STATE -->
    <div class="rep-surface-card p-12 text-center space-y-4">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-100 border border-slate-200/90 flex items-center justify-center text-slate-400">
            <i data-lucide="package-x" class="w-7 h-7"></i>
        </div>
        <div class="max-w-md mx-auto">
            <h3 class="text-sm font-extrabold text-slate-900 font-mono-code uppercase tracking-wider">No Matching Vault Exhibits</h3>
            <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                @if($search || $category !== 'all' || $actionStatus !== 'all' || ($suspectFilter ?? 'all') !== 'all')
                    No exhibits matched your current search or filter criteria{{ $search ? ' ("' . e($search) . '")' : '' }}.
                @else
                    No exhibits have been logged into the Central Evidence Vault yet.
                @endif
            </p>
        </div>
        <div>
            <button type="button" onclick="resetVaultFilters()" class="rep-btn-green px-4 py-2 text-xs font-extrabold rounded-xl inline-flex items-center gap-2 cursor-pointer">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                <span>View All Vault Exhibits</span>
            </button>
        </div>
    </div>
@else

    @if($viewMode === 'grid')
        <!-- ============================================== -->
        <!-- A. CUSTODIAL DOSSIER CARDS VIEW                -->
        <!-- ============================================== -->
        <div class="exhibits-grid-3cols" id="exhibitsGridContainer">
            @foreach($exhibits as $exhibit)
                @php
                    $suspects = $exhibit->associated_suspects;
                    $caseNo = $exhibit->case_number;
                    $caseUrl = $exhibit->case_review_url;
                    $cat = strtolower($exhibit->exhibit_category ?? '');

                    $badgeClass = 'badge-category-other';
                    $iconName = 'box';
                    if (str_contains($cat, 'drug') || str_contains($cat, 'narcotic')) {
                        $badgeClass = 'badge-category-drug';
                        $iconName = 'pill';
                    } elseif (str_contains($cat, 'weapon') || str_contains($cat, 'firearm')) {
                        $badgeClass = 'badge-category-weapon';
                        $iconName = 'crosshair';
                    } elseif (str_contains($cat, 'vehic')) {
                        $badgeClass = 'badge-category-vehicle';
                        $iconName = 'truck';
                    } elseif (str_contains($cat, 'devic') || str_contains($cat, 'electron') || str_contains($cat, 'digital')) {
                        $badgeClass = 'badge-category-device';
                        $iconName = 'smartphone';
                    } elseif (str_contains($cat, 'money') || str_contains($cat, 'cash') || str_contains($cat, 'currency')) {
                        $badgeClass = 'badge-category-money';
                        $iconName = 'coins';
                    }

                    // Resolve optional first evidence photo for quick visual verification
                    $rawPhotos = $exhibit->evidence_photos;
                    $photoList = [];
                    if (is_array($rawPhotos)) {
                        $photoList = array_filter($rawPhotos);
                    } elseif (is_string($rawPhotos) && trim($rawPhotos) !== '') {
                        $decoded = json_decode($rawPhotos, true);
                        $photoList = is_array($decoded) ? array_filter($decoded) : [$rawPhotos];
                    }
                    $firstPhotoUrl = null;
                    if (!empty($photoList)) {
                        $fp = reset($photoList);
                        if (str_starts_with($fp, 'data:') || str_starts_with($fp, 'http://') || str_starts_with($fp, 'https://')) {
                            $firstPhotoUrl = $fp;
                        } elseif (str_starts_with($fp, 'storage/') || str_starts_with($fp, 'uploads/') || str_starts_with($fp, 'images/')) {
                            $firstPhotoUrl = asset($fp);
                        } else {
                            $firstPhotoUrl = asset('storage/' . ltrim($fp, '/'));
                        }
                    }

                    $exhibitDisplayNo = $exhibit->exhibit_number ?: 'EXH-#' . str_pad($exhibit->id, 4, '0', STR_PAD_LEFT);
                    $commodityDisplay = $exhibit->commodity_name ?: ($exhibit->veh_model ?? $exhibit->wpn_model ?? $exhibit->dev_model ?? 'Unspecified Exhibit');
                    $sourceLocation = $exhibit->source_of_exhibit ?: ($exhibit->source_of_exhibits ?: ($exhibit->seizure_location ?: null));
                    $hasBothClassificationAndType = !empty($exhibit->drug_classification) && $exhibit->drug_classification !== 'N/A' && !empty($exhibit->exhibit_type);
                @endphp

                <article data-exhibit-id="{{ $exhibit->id }}" class="vault-dossier-card flex flex-col justify-between overflow-hidden group">

                    <div>
                        <!-- CARD CUSTODIAL GREY TOP BAND -->
                        <div class="px-4 py-3 bg-slate-100/85 border-b border-slate-200/80 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase font-mono-code inline-flex items-center gap-1 shrink-0 {{ $badgeClass }}">
                                    <i data-lucide="{{ $iconName }}" class="w-3 h-3"></i>
                                    <span>{{ $exhibit->exhibit_category ?: 'General' }}</span>
                                </span>
                                <span class="text-[11px] font-extrabold font-mono-code text-slate-900 bg-white px-2 py-0.5 rounded-md border border-slate-200/90 shadow-2xs truncate">
                                    {{ $exhibitDisplayNo }}
                                </span>
                            </div>

                            <div class="shrink-0">
                                @if($exhibit->property_id || $exhibit->tag_url)
                                    <button type="button"
                                            data-vault-prop-badge
                                            onclick='openVaultQrModal(@json($exhibit))'
                                            class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] font-mono-code font-bold transition-colors inline-flex items-center gap-1 shadow-2xs cursor-pointer"
                                            title="Seized Property Tag Assigned - Click to view/reprint">
                                        <i data-lucide="qr-code" class="w-3 h-3 text-emerald-600"></i>
                                        <span>{{ $exhibit->property_id ?: 'TAGGED' }}</span>
                                    </button>
                                @else
                                    <button type="button"
                                            data-vault-prop-badge
                                            onclick='openVaultQrModal(@json($exhibit))'
                                            class="px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 text-[10px] font-mono-code font-bold transition-colors inline-flex items-center gap-1 shadow-2xs cursor-pointer"
                                            title="Assign Official Seized Property QR Tag">
                                        <i data-lucide="qr-code" class="w-3 h-3 text-amber-600"></i>
                                        <span>Assign QR</span>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- CARD BODY -->
                        <div class="p-4 space-y-3.5">

                            <!-- Commodity Identity + Optional Photo Thumbnail -->
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <h3 data-commodity-title class="text-sm font-extrabold text-slate-900 tracking-tight leading-snug group-hover:text-emerald-700 transition-colors">
                                        {{ $commodityDisplay }}
                                    </h3>

                                    <!-- Classification & Exhibit Type Strip -->
                                    <div class="flex items-center gap-1.5 flex-wrap pt-1.5">
                                        @if(!empty($exhibit->drug_classification) && $exhibit->drug_classification !== 'N/A')
                                            <button type="button"
                                                    onclick='openVaultClassifyModal(@json($exhibit), true)'
                                                    class="vault-drug-badge inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 hover:bg-emerald-100 text-emerald-900 border border-emerald-200 text-[10px] font-mono-code font-bold transition-colors cursor-pointer"
                                                    title="Drug Classification: {{ $exhibit->drug_classification }} - Click to reclassify">
                                                <i data-lucide="pill" class="w-3 h-3 text-emerald-600"></i>
                                                <span class="truncate max-w-[150px]">{{ $exhibit->drug_classification }}</span>
                                            </button>
                                        @else
                                            <button type="button"
                                                    onclick='openVaultClassifyModal(@json($exhibit), true)'
                                                    class="vault-classify-btn inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] font-mono-code font-bold transition-colors cursor-pointer"
                                                    title="Assign Drug Classification to this Exhibit">
                                                <i data-lucide="pill" class="w-3 h-3 text-emerald-600"></i>
                                                <span>+ Assign Drug Class</span>
                                            </button>
                                        @endif

                                        @if($exhibit->exhibit_type)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 border border-slate-200 text-[10px] font-semibold">
                                                <i data-lucide="layers" class="w-2.5 h-2.5 text-slate-500"></i>
                                                <span class="truncate max-w-[130px]">{{ $exhibit->exhibit_type }}</span>
                                            </span>
                                        @else
                                            <button type="button"
                                                    onclick='openVaultClassifyModal(@json($exhibit))'
                                                    class="vault-classify-btn inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-50 hover:bg-emerald-50 text-slate-600 hover:text-emerald-800 border border-dashed border-slate-300 hover:border-emerald-300 text-[10px] font-semibold transition-colors cursor-pointer"
                                                    title="Assign Exhibit Type">
                                                <i data-lucide="plus" class="w-2.5 h-2.5"></i>
                                                <span>Set Type</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                @if($firstPhotoUrl)
                                    <button type="button"
                                            onclick='openExhibitModal(@json($exhibit), @json($suspects))'
                                            class="relative w-12 h-12 rounded-xl border border-slate-200 bg-slate-100 overflow-hidden shrink-0 shadow-2xs cursor-pointer group/thumb"
                                            title="View {{ count($photoList) }} evidence photo(s)">
                                        <img src="{{ $firstPhotoUrl }}" alt="Exhibit Evidence" class="w-full h-full object-cover group-hover/thumb:scale-110 transition-transform">
                                        @if(count($photoList) > 1)
                                            <span class="absolute bottom-0.5 right-0.5 px-1 rounded bg-slate-900/75 text-white text-[8.5px] font-mono-code font-bold">
                                                +{{ count($photoList) - 1 }}
                                            </span>
                                        @endif
                                    </button>
                                @endif
                            </div>

                            <!-- Telemetry Grid: Weight/Qty & Valuation -->
                            <div class="grid grid-cols-2 gap-2.5 bg-slate-50/90 p-3 rounded-xl border border-slate-200/70 text-xs">
                                <div class="min-w-0">
                                    <span class="text-[9.5px] text-slate-500 font-extrabold font-mono-code uppercase tracking-wider block">Weight / Quantity</span>
                                    <span class="font-extrabold text-slate-900 font-mono-code tnum block truncate mt-0.5">
                                        @if($exhibit->seizure_weight)
                                            {{ $exhibit->seizure_weight }} {{ $exhibit->seizure_unit ?: 'kg' }}
                                        @elseif($exhibit->amount)
                                            {{ $exhibit->amount }}
                                        @elseif($exhibit->seizure_quantity)
                                            {{ $exhibit->seizure_quantity }} {{ $exhibit->seizure_unit ?: 'pcs' }}
                                        @else
                                            <span class="text-slate-400 font-normal">N/A</span>
                                        @endif
                                    </span>
                                    @if($exhibit->seizure_packaging)
                                        <span class="text-[10px] text-slate-500 block truncate mt-0.5" title="{{ $exhibit->seizure_packaging }}">
                                            {{ $exhibit->seizure_packaging }}
                                        </span>
                                    @endif
                                </div>

                                <div class="min-w-0">
                                    <span class="text-[9.5px] text-slate-500 font-extrabold font-mono-code uppercase tracking-wider block">Est. Valuation</span>
                                    <span class="font-extrabold text-emerald-700 font-mono-code tnum block truncate mt-0.5">
                                        @if($exhibit->seizure_value && $exhibit->seizure_value !== 'N/A')
                                            GH₵ {{ is_numeric($exhibit->seizure_value) ? number_format((float)$exhibit->seizure_value, 2) : $exhibit->seizure_value }}
                                        @elseif($exhibit->amount)
                                            GH₵ {{ is_numeric(str_replace(',', '', $exhibit->amount)) ? number_format((float)str_replace(',', '', $exhibit->amount), 2) : $exhibit->amount }}
                                        @else
                                            <span class="text-slate-400 font-normal">Pending</span>
                                        @endif
                                    </span>
                                    @if($sourceLocation)
                                        <span class="text-[10px] text-slate-500 block truncate mt-0.5" title="Source: {{ $sourceLocation }}">
                                            Src: {{ $sourceLocation }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Category Hardware / Asset Quick Strip -->
                            @if($exhibit->veh_make || $exhibit->veh_reg_no || $exhibit->veh_vin)
                                <div class="text-[11px] bg-blue-50/70 px-2.5 py-2 rounded-lg border border-blue-200/70 text-blue-900 font-mono-code flex items-center justify-between gap-2">
                                    <span class="inline-flex items-center gap-1.5 truncate"><i data-lucide="truck" class="w-3.5 h-3.5 text-blue-600 shrink-0"></i><span class="truncate">{{ $exhibit->veh_make }} {{ $exhibit->veh_model }}</span></span>
                                    <span class="font-bold text-blue-800 shrink-0">{{ $exhibit->veh_reg_no ?: ($exhibit->veh_vin ? 'VIN:' . substr($exhibit->veh_vin, -6) : '') }}</span>
                                </div>
                            @elseif($exhibit->wpn_model || $exhibit->wpn_serial_no || $exhibit->wpn_calibre)
                                <div class="text-[11px] bg-amber-50/70 px-2.5 py-2 rounded-lg border border-amber-200/70 text-amber-900 font-mono-code flex items-center justify-between gap-2">
                                    <span class="inline-flex items-center gap-1.5 truncate"><i data-lucide="crosshair" class="w-3.5 h-3.5 text-amber-600 shrink-0"></i><span class="truncate">{{ $exhibit->wpn_model ?: 'Weapon' }} ({{ $exhibit->wpn_calibre ?: 'N/A' }})</span></span>
                                    <span class="font-bold text-amber-800 shrink-0">S/N: {{ $exhibit->wpn_serial_no ?: 'N/A' }}</span>
                                </div>
                            @elseif($exhibit->dev_model || $exhibit->dev_imei1 || $exhibit->dev_serial_no)
                                <div class="text-[11px] bg-purple-50/70 px-2.5 py-2 rounded-lg border border-purple-200/70 text-purple-900 font-mono-code flex items-center justify-between gap-2">
                                    <span class="inline-flex items-center gap-1.5 truncate"><i data-lucide="smartphone" class="w-3.5 h-3.5 text-purple-600 shrink-0"></i><span class="truncate">{{ $exhibit->dev_model ?: 'Electronic Device' }}</span></span>
                                    <span class="font-bold text-purple-800 shrink-0">{{ $exhibit->dev_imei1 ? 'IMEI:' . substr($exhibit->dev_imei1, 0, 8) . '...' : ($exhibit->dev_serial_no ? 'SN:' . $exhibit->dev_serial_no : '') }}</span>
                                </div>
                            @endif

                            @if($exhibit->exhibit_remark)
                                <div class="bg-slate-50 border-l-2 border-emerald-500 px-2.5 py-1.5 rounded-r-lg">
                                    <p class="text-[11px] text-slate-600 italic line-clamp-2">"{{ $exhibit->exhibit_remark }}"</p>
                                </div>
                            @endif

                            <!-- Associated Suspect(s) & Case Docket -->
                            <div class="pt-2.5 border-t border-slate-100">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-extrabold font-mono-code uppercase tracking-wider text-slate-500 inline-flex items-center gap-1">
                                        <i data-lucide="user-check" class="w-3 h-3 text-emerald-600"></i>
                                        <span>Associated Suspect(s)</span>
                                    </span>
                                    @if($caseNo !== 'N/A')
                                        <a href="{{ $caseUrl }}" class="text-[10.5px] font-mono-code font-extrabold text-emerald-700 hover:underline">
                                            {{ $caseNo }}
                                        </a>
                                    @else
                                        <span class="text-[10px] font-mono-code text-slate-400">No Docket</span>
                                    @endif
                                </div>

                                @if($suspects->isNotEmpty())
                                    <div class="space-y-1.5">
                                        @foreach($suspects->take(2) as $s)
                                            @php
                                                $cardMug = $s->mugshot ?? null;
                                                $cardMugUrl = null;
                                                if (!empty($cardMug)) {
                                                    if (str_starts_with($cardMug, 'data:') || str_starts_with($cardMug, 'http://') || str_starts_with($cardMug, 'https://')) {
                                                        $cardMugUrl = $cardMug;
                                                    } elseif (str_starts_with($cardMug, 'storage/') || str_starts_with($cardMug, 'uploads/') || str_starts_with($cardMug, 'images/')) {
                                                        $cardMugUrl = asset($cardMug);
                                                    } else {
                                                        $cardMugUrl = asset('storage/' . ltrim($cardMug, '/'));
                                                    }
                                                }
                                            @endphp
                                            <div class="flex items-center justify-between gap-2 bg-slate-50/90 hover:bg-emerald-50/50 p-2 rounded-xl border border-slate-200/75 transition-colors">
                                                <div class="flex items-center gap-2.5 min-w-0">
                                                    <div class="w-8 h-8 rounded-lg bg-emerald-100 border border-emerald-200 flex items-center justify-center text-emerald-800 font-mono-code font-extrabold text-[10.5px] shrink-0 overflow-hidden">
                                                        @if($cardMugUrl)
                                                            <img src="{{ $cardMugUrl }}" alt="{{ $s->name }}" class="w-full h-full object-cover">
                                                        @else
                                                            {{ strtoupper(substr($s->name, 0, 2)) }}
                                                        @endif
                                                    </div>
                                                    <div class="min-w-0">
                                                        <div class="text-xs font-bold text-slate-900 truncate" title="{{ $s->name }}">
                                                            {{ $s->name }}
                                                            @if($s->alias)
                                                                <span class="text-[10px] font-normal text-slate-500 font-mono-code">("{{ $s->alias }}")</span>
                                                            @endif
                                                        </div>
                                                        <div class="text-[10px] text-slate-500 flex items-center gap-1.5 font-mono-code truncate">
                                                            @if($s->ghana_card)
                                                                <span class="text-slate-600">{{ $s->ghana_card }}</span>
                                                                <span>&bull;</span>
                                                            @endif
                                                            <span class="text-emerald-700 font-semibold">{{ $s->role }}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                @if($caseNo !== 'N/A')
                                                    <a href="{{ $caseUrl }}" class="rep-btn-green-soft px-2 py-1 rounded-lg text-[10px] font-bold shrink-0" title="View Case Docket">
                                                        Docket
                                                    </a>
                                                @endif
                                            </div>
                                        @endforeach

                                        @if($suspects->count() > 2)
                                            <div class="text-center pt-0.5">
                                                <span class="text-[10px] font-mono-code text-slate-500 font-semibold">
                                                    + {{ $suspects->count() - 2 }} additional suspect(s)
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="bg-amber-50/70 border border-amber-200/80 rounded-xl p-2.5 flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-amber-600 shrink-0"></i>
                                            <span class="text-[11px] font-semibold text-amber-800 truncate">No Suspect Associated Yet</span>
                                        </div>
                                        @if($caseNo !== 'N/A')
                                            <a href="{{ $caseUrl }}" class="text-[10px] font-bold text-emerald-700 hover:underline shrink-0">
                                                Open Case
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            </div>

                        </div>
                    </div>

                    <!-- CARD FOOTER: ALL GREEN BUTTONS -->
                    <div class="px-4 py-3 bg-slate-50/90 border-t border-slate-200/80 flex items-center justify-between gap-2 text-xs">
                        <div class="text-[10px] text-slate-500 font-mono-code tnum">
                            {{ $exhibit->created_at ? $exhibit->created_at->format('d M Y') : 'N/A' }}
                        </div>

                        <div class="flex items-center gap-1.5 flex-wrap justify-end">
                            <button type="button"
                                    data-vault-footer-qr-btn
                                    onclick='openVaultQrModal(@json($exhibit))'
                                    class="rep-btn-green-soft px-2.5 py-1.5 font-bold text-[11px] rounded-lg flex items-center gap-1 cursor-pointer"
                                    title="Assign or Preview Official Seized Property QR Code Tag">
                                <i data-lucide="qr-code" class="w-3 h-3"></i>
                                <span>QR Tag</span>
                            </button>

                            @if(!$hasBothClassificationAndType)
                                <button type="button"
                                        onclick='openVaultClassifyModal(@json($exhibit), true)'
                                        class="vault-classify-btn rep-btn-green-soft px-2.5 py-1.5 font-bold text-[11px] rounded-lg flex items-center gap-1 cursor-pointer"
                                        title="Classify Drug and Assign Exhibit Type">
                                    <i data-lucide="sliders" class="w-3 h-3"></i>
                                    <span>Classify</span>
                                </button>
                            @endif

                            <button type="button"
                                    onclick='openExhibitModal(@json($exhibit), @json($suspects))'
                                    class="rep-btn-green px-3 py-1.5 font-extrabold text-[11px] rounded-lg flex items-center gap-1 cursor-pointer">
                                <i data-lucide="eye" class="w-3 h-3"></i>
                                <span>Inspect</span>
                            </button>
                        </div>
                    </div>

                </article>
            @endforeach
        </div>

    @else
        <!-- ============================================== -->
        <!-- B. CUSTODIAL LEDGER DATA TABLE VIEW            -->
        <!-- ============================================== -->
        <div class="rep-surface-card overflow-hidden">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="rep-table w-full min-w-[1060px] text-left text-xs border-collapse">
                    <thead>
                        <tr>
                            <th class="py-3.5 px-4">Exhibit #</th>
                            <th class="py-3.5 px-4">QR Property Seal</th>
                            <th class="py-3.5 px-4">Category &amp; Type</th>
                            <th class="py-3.5 px-4">Commodity &amp; Drug Class</th>
                            <th class="py-3.5 px-4">Weight / Qty</th>
                            <th class="py-3.5 px-4">Est. Valuation</th>
                            <th class="py-3.5 px-4">Associated Suspect(s)</th>
                            <th class="py-3.5 px-4">Case Docket</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($exhibits as $exhibit)
                            @php
                                $suspects = $exhibit->associated_suspects;
                                $caseNo = $exhibit->case_number;
                                $caseUrl = $exhibit->case_review_url;
                                $cat = strtolower($exhibit->exhibit_category ?? '');

                                $badgeClass = 'badge-category-other';
                                if (str_contains($cat, 'drug') || str_contains($cat, 'narcotic')) $badgeClass = 'badge-category-drug';
                                elseif (str_contains($cat, 'weapon')) $badgeClass = 'badge-category-weapon';
                                elseif (str_contains($cat, 'vehic')) $badgeClass = 'badge-category-vehicle';
                                elseif (str_contains($cat, 'devic') || str_contains($cat, 'electron')) $badgeClass = 'badge-category-device';
                                elseif (str_contains($cat, 'money') || str_contains($cat, 'cash') || str_contains($cat, 'currency')) $badgeClass = 'badge-category-money';

                                $hasBothClassificationAndType = !empty($exhibit->drug_classification) && $exhibit->drug_classification !== 'N/A' && !empty($exhibit->exhibit_type);
                            @endphp
                            <tr data-exhibit-id="{{ $exhibit->id }}">

                                <!-- Exhibit Number -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="font-mono-code font-extrabold text-slate-900 block">
                                        {{ $exhibit->exhibit_number ?: 'EXH-#' . str_pad($exhibit->id, 4, '0', STR_PAD_LEFT) }}
                                    </span>
                                    <span class="text-[10px] font-mono-code text-slate-400 tnum">
                                        {{ $exhibit->created_at ? $exhibit->created_at->format('d M Y') : '' }}
                                    </span>
                                </td>

                                <!-- QR Tag & Property ID -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($exhibit->property_id || $exhibit->tag_url)
                                        <button type="button"
                                                data-vault-prop-badge
                                                onclick='openVaultQrModal(@json($exhibit))'
                                                class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] font-mono-code font-bold transition-colors inline-flex items-center gap-1 shadow-2xs cursor-pointer"
                                                title="View or reprint official Seized Property QR tag">
                                            <i data-lucide="qr-code" class="w-3 h-3 text-emerald-600"></i>
                                            <span>{{ $exhibit->property_id ?: 'TAGGED' }}</span>
                                        </button>
                                    @else
                                        <button type="button"
                                                data-vault-prop-badge
                                                onclick='openVaultQrModal(@json($exhibit))'
                                                class="px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 text-[10px] font-mono-code font-bold transition-colors inline-flex items-center gap-1 shadow-2xs cursor-pointer"
                                                title="Assign QR Code Tag">
                                            <i data-lucide="qr-code" class="w-3 h-3 text-amber-600"></i>
                                            <span>Assign QR</span>
                                        </button>
                                    @endif
                                </td>

                                <!-- Category & Type -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="space-y-1">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase font-mono-code inline-block {{ $badgeClass }}">
                                            {{ $exhibit->exhibit_category ?: 'General' }}
                                        </span>
                                        <div>
                                            @if($exhibit->exhibit_type)
                                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200 font-semibold truncate max-w-[130px] inline-block">
                                                    {{ $exhibit->exhibit_type }}
                                                </span>
                                            @else
                                                <button type="button"
                                                        onclick='openVaultClassifyModal(@json($exhibit))'
                                                        class="vault-classify-btn text-[10px] px-1.5 py-0.5 rounded bg-slate-50 hover:bg-emerald-50 text-slate-500 hover:text-emerald-800 border border-dashed border-slate-300 inline-block transition-colors cursor-pointer">
                                                    + Set Type
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- Commodity & Classification -->
                                <td class="py-3.5 px-4 max-w-xs">
                                    <div data-commodity-title class="font-extrabold text-slate-900 truncate">
                                        {{ $exhibit->commodity_name ?: ($exhibit->veh_model ?? $exhibit->wpn_model ?? $exhibit->dev_model ?? 'N/A') }}
                                    </div>
                                    <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                        @if(!empty($exhibit->drug_classification) && $exhibit->drug_classification !== 'N/A')
                                            <button type="button"
                                                    onclick='openVaultClassifyModal(@json($exhibit), true)'
                                                    class="vault-drug-badge px-2 py-0.5 rounded bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-[9.5px] font-bold font-mono-code inline-flex items-center gap-1 transition-colors cursor-pointer"
                                                    title="Drug Classification: {{ $exhibit->drug_classification }} - Click to reclassify">
                                                <i data-lucide="pill" class="w-2.5 h-2.5 text-emerald-600"></i>
                                                <span class="truncate max-w-[140px]">{{ $exhibit->drug_classification }}</span>
                                            </button>
                                        @else
                                            <button type="button"
                                                    onclick='openVaultClassifyModal(@json($exhibit), true)'
                                                    class="vault-classify-btn px-2 py-0.5 rounded bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 text-[9.5px] font-mono-code font-bold inline-flex items-center gap-1 transition-colors cursor-pointer">
                                                <i data-lucide="pill" class="w-2.5 h-2.5 text-emerald-600"></i>
                                                <span>+ Assign Drug Class</span>
                                            </button>
                                        @endif
                                    </div>
                                </td>

                                <!-- Weight / Quantity -->
                                <td class="py-3.5 px-4 font-mono-code font-semibold text-slate-800 whitespace-nowrap tnum">
                                    @if($exhibit->seizure_weight)
                                        {{ $exhibit->seizure_weight }} {{ $exhibit->seizure_unit ?: 'kg' }}
                                    @elseif($exhibit->amount)
                                        {{ $exhibit->amount }}
                                    @elseif($exhibit->seizure_quantity)
                                        {{ $exhibit->seizure_quantity }} {{ $exhibit->seizure_unit ?: 'pcs' }}
                                    @else
                                        <span class="text-slate-400 font-normal">N/A</span>
                                    @endif
                                </td>

                                <!-- Valuation -->
                                <td class="py-3.5 px-4 font-mono-code font-extrabold text-emerald-700 whitespace-nowrap tnum">
                                    @if($exhibit->seizure_value && $exhibit->seizure_value !== 'N/A')
                                        GH₵ {{ is_numeric($exhibit->seizure_value) ? number_format((float)$exhibit->seizure_value, 2) : $exhibit->seizure_value }}
                                    @elseif($exhibit->amount)
                                        GH₵ {{ is_numeric(str_replace(',', '', $exhibit->amount)) ? number_format((float)str_replace(',', '', $exhibit->amount), 2) : $exhibit->amount }}
                                    @else
                                        <span class="text-slate-400 font-normal">--</span>
                                    @endif
                                </td>

                                <!-- Associated Suspects -->
                                <td class="py-3.5 px-4">
                                    @if($suspects->isNotEmpty())
                                        <div class="space-y-1.5">
                                            @foreach($suspects->take(2) as $s)
                                                @php
                                                    $mug = $s->mugshot ?? null;
                                                    $mugUrl = null;
                                                    if (!empty($mug)) {
                                                        if (str_starts_with($mug, 'data:') || str_starts_with($mug, 'http://') || str_starts_with($mug, 'https://')) {
                                                            $mugUrl = $mug;
                                                        } elseif (str_starts_with($mug, 'storage/') || str_starts_with($mug, 'uploads/') || str_starts_with($mug, 'images/')) {
                                                            $mugUrl = asset($mug);
                                                        } else {
                                                            $mugUrl = asset('storage/' . ltrim($mug, '/'));
                                                        }
                                                    }
                                                @endphp
                                                <div class="flex items-center gap-2.5">
                                                    <div class="w-8 h-8 rounded-lg bg-emerald-100 border border-emerald-200 overflow-hidden shrink-0 flex items-center justify-center text-emerald-800 font-mono-code font-extrabold text-[10px]">
                                                        @if($mugUrl)
                                                            <img src="{{ $mugUrl }}" alt="{{ $s->name }}" class="w-full h-full object-cover cursor-pointer" onclick='openExhibitModal(@json($exhibit), @json($suspects))'>
                                                        @else
                                                            {{ strtoupper(substr($s->name, 0, 2)) }}
                                                        @endif
                                                    </div>
                                                    <div class="min-w-0">
                                                        <div class="font-bold text-slate-900 truncate max-w-[150px] leading-tight" title="{{ $s->name }}">
                                                            {{ $s->name }}
                                                        </div>
                                                        <div class="text-[10px] text-emerald-700 font-mono-code font-semibold truncate">
                                                            {{ $s->role }}
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                            @if($suspects->count() > 2)
                                                <div class="text-[9.5px] text-slate-400 font-mono-code">+ {{ $suspects->count() - 2 }} more</div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-800 text-[10px] font-semibold border border-amber-200 inline-block">
                                            Unassigned
                                        </span>
                                    @endif
                                </td>

                                <!-- Case Docket -->
                                <td class="py-3.5 px-4 font-mono-code whitespace-nowrap">
                                    @if($caseNo !== 'N/A')
                                        <a href="{{ $caseUrl }}" class="font-extrabold text-emerald-700 hover:underline">
                                            {{ $caseNo }}
                                        </a>
                                    @else
                                        <span class="text-slate-400">N/A</span>
                                    @endif
                                </td>

                                <!-- Actions (All Green) -->
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button"
                                                onclick='openVaultQrModal(@json($exhibit))'
                                                class="rep-btn-green-soft p-1.5 rounded-lg cursor-pointer"
                                                title="Assign / View QR Tag">
                                            <i data-lucide="qr-code" class="w-3.5 h-3.5"></i>
                                        </button>
                                        @if(!$hasBothClassificationAndType)
                                            <button type="button"
                                                    onclick='openVaultClassifyModal(@json($exhibit), true)'
                                                    class="vault-classify-btn rep-btn-green-soft p-1.5 rounded-lg cursor-pointer"
                                                    title="Classify Drug &amp; Set Type">
                                                <i data-lucide="sliders" class="w-3.5 h-3.5"></i>
                                            </button>
                                        @endif
                                        <button type="button"
                                                onclick='openExhibitModal(@json($exhibit), @json($suspects))'
                                                class="rep-btn-green px-2.5 py-1.5 font-extrabold text-[10.5px] rounded-lg inline-flex items-center gap-1 cursor-pointer">
                                            <i data-lucide="eye" class="w-3 h-3"></i>
                                            <span>Inspect</span>
                                        </button>
                                    </div>
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- ============================================== -->
    <!-- PAGINATION BAR                                 -->
    <!-- ============================================== -->
    <div class="mt-5 rep-surface-card rep-pagination px-5 py-3.5 flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="text-xs text-slate-600 font-mono-code tnum">
            Showing <span class="font-extrabold text-slate-900">{{ $exhibits->firstItem() ?? 0 }}</span>&ndash;<span class="font-extrabold text-slate-900">{{ $exhibits->lastItem() ?? 0 }}</span> of <span class="font-extrabold text-slate-900">{{ $exhibits->total() }}</span> vault exhibits
        </div>
        <div>
            {{ $exhibits->links() }}
        </div>
    </div>

@endif
