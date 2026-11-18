                @if($exhibits->isEmpty())
                    <!-- EMPTY STATE -->
                    <div class="bg-white rounded-2xl p-12 text-center border border-slate-200 shadow-2xs space-y-4">
                        <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400">
                            <i data-lucide="package-x" class="w-8 h-8"></i>
                        </div>
                        <div class="max-w-md mx-auto">
                            <h3 class="text-base font-bold text-slate-800">No Exhibits Found</h3>
                            <p class="text-xs text-slate-500 mt-1">
                                @if($search || $category !== 'all' || $actionStatus !== 'all')
                                    No exhibits matched your search query "<span class="font-bold text-slate-700">{{ $search }}</span>" or selected filters.
                                @else
                                    No exhibits have been logged into the Evidence Vault yet.
                                @endif
                            </p>
                        </div>
                        <div>
                            <a href="{{ url('/Evidence_Management/vault') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-colors inline-flex items-center space-x-2">
                                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                                <span>View All Vault Exhibits</span>
                            </a>
                        </div>
                    </div>
                @else

                    @if($viewMode === 'grid')
                        <!-- ============================================== -->
                        <!-- A. TACTICAL GRID / CARDS VIEW                  -->
                        <!-- ============================================== -->
                        <style>
                            #exhibitsGridContainer {
                                display: grid !important;
                                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
                                gap: 1.25rem !important;
                                width: 100% !important;
                            }
                            @media (max-width: 900px) {
                                #exhibitsGridContainer {
                                    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                                }
                            }
                            @media (max-width: 600px) {
                                #exhibitsGridContainer {
                                    grid-template-columns: 1fr !important;
                                }
                            }
                        </style>
                        <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-3 gap-5 exhibits-grid-3cols" id="exhibitsGridContainer" style="display: grid !important; grid-template-columns: repeat(3, minmax(0, 1fr)) !important; gap: 1.25rem !important; width: 100% !important;">
                            @foreach($exhibits as $exhibit)
                                @php
                                    $suspects = $exhibit->associated_suspects;
                                    $caseNo = $exhibit->case_number;
                                    $caseUrl = $exhibit->case_review_url;
                                    $cat = strtolower($exhibit->exhibit_category ?? '');
                                    
                                    // Determine category styling
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
                                @endphp

                                <div data-exhibit-id="{{ $exhibit->id }}" class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs hover:shadow-md hover:border-emerald-300 transition-all flex flex-col justify-between overflow-hidden group">
                                    
                                    <div>
                                        <!-- CARD HEADER: Category, Exhibit No. & QR Tag Status -->
                                        <div class="p-3.5 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between gap-2">
                                            <div class="flex items-center space-x-1.5 flex-wrap gap-y-1">
                                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase font-mono-code flex items-center space-x-1 {{ $badgeClass }}">
                                                    <i data-lucide="{{ $iconName }}" class="w-3 h-3"></i>
                                                    <span>{{ $exhibit->exhibit_category ?: 'Unspecified' }}</span>
                                                </span>
                                                @if($exhibit->exhibit_type)
                                                    <span class="text-[10px] text-slate-600 font-semibold truncate max-w-[110px]" title="{{ $exhibit->exhibit_type }}">
                                                        • {{ $exhibit->exhibit_type }}
                                                    </span>
                                                @endif
                                                <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[9px] font-mono-code font-bold uppercase bg-blue-50 text-blue-800 border border-blue-200 shadow-2xs" data-custodial-badge="{{ $exhibit->id }}" title="Live Dynamic Custodial Status">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                                                    <span class="custodial-status-text">{{ $exhibit->custodial_status ?: 'Seized' }}</span>
                                                </span>
                                            </div>

                                            <div class="flex items-center space-x-1.5 shrink-0">
                                                @if($exhibit->property_id || $exhibit->tag_url)
                                                    <button type="button" 
                                                            data-vault-prop-badge
                                                            onclick='openVaultQrModal(@json($exhibit))'
                                                            class="px-2 py-0.5 rounded-md bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] font-mono-code font-bold transition-colors flex items-center space-x-1 shadow-2xs" 
                                                            title="Seized Property Tag Assigned - Click to view/reprint">
                                                        <i data-lucide="qr-code" class="w-3 h-3 text-emerald-600"></i>
                                                        <span>{{ $exhibit->property_id ?: 'TAGGED' }}</span>
                                                    </button>
                                                @else
                                                    <button type="button" 
                                                            data-vault-prop-badge
                                                            onclick='openVaultQrModal(@json($exhibit))'
                                                            class="px-2 py-0.5 rounded-md bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 text-[10px] font-bold transition-colors flex items-center space-x-1 shadow-2xs" 
                                                            title="Assign QR Code Tag">
                                                        <i data-lucide="qr-code" class="w-3 h-3 text-amber-600"></i>
                                                        <span>⚠️ Assign QR</span>
                                                    </button>
                                                @endif

                                                <span class="text-[11px] font-extrabold font-mono-code text-slate-900 bg-white px-2 py-0.5 rounded-md border border-slate-200 shadow-2xs">
                                                    {{ $exhibit->exhibit_number ?: 'EXH-#' . str_pad($exhibit->id, 4, '0', STR_PAD_LEFT) }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- CARD BODY: Commodity & Specs -->
                                        <div class="p-4 space-y-3">
                                            <div>
                                                <h3 class="text-sm font-extrabold text-slate-900 tracking-tight leading-snug group-hover:text-emerald-700 transition-colors">
                                                    {{ $exhibit->commodity_name ?: ($exhibit->veh_model ?? $exhibit->wpn_model ?? $exhibit->dev_model ?? 'N/A') }}
                                                </h3>

                                                <!-- Custodial Classification & Type Control Strip -->
                                                <div class="flex items-center gap-1.5 flex-wrap pt-1.5">
                                                    @if(!empty($exhibit->drug_classification) && $exhibit->drug_classification !== 'N/A')
                                                        <button type="button" 
                                                                onclick='openVaultClassifyModal(@json($exhibit), true)'
                                                                class="vault-drug-badge inline-flex items-center space-x-1 px-2 py-0.5 rounded-md bg-emerald-50 hover:bg-emerald-100 text-emerald-900 border border-emerald-200 text-[10px] font-bold transition-colors cursor-pointer"
                                                                title="Drug Classification: {{ $exhibit->drug_classification }} - Click to reclassify">
                                                            <i data-lucide="pill" class="w-3 h-3 text-emerald-600"></i>
                                                            <span class="truncate max-w-[160px]">{{ $exhibit->drug_classification }}</span>
                                                        </button>
                                                    @else
                                                        <button type="button" 
                                                                onclick='openVaultClassifyModal(@json($exhibit), true)'
                                                                class="vault-classify-btn inline-flex items-center space-x-1 px-2 py-0.5 rounded-md bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] font-bold transition-colors shadow-3xs cursor-pointer"
                                                                title="Assign Drug Classification to this Exhibit">
                                                            <i data-lucide="pill" class="w-3 h-3 text-emerald-600"></i>
                                                            <span>+ Assign Drug Classification</span>
                                                        </button>
                                                    @endif

                                                    @if($exhibit->exhibit_type)
                                                        <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 border border-slate-200 text-[10px] font-semibold"
                                                              title="Exhibit Type">
                                                            <i data-lucide="layers" class="w-2.5 h-2.5 text-slate-500"></i>
                                                            <span class="truncate max-w-[140px]">{{ $exhibit->exhibit_type }}</span>
                                                        </span>
                                                    @else
                                                        <button type="button" 
                                                                onclick='openVaultClassifyModal(@json($exhibit))'
                                                                class="vault-classify-btn inline-flex items-center space-x-1 px-2 py-0.5 rounded-md bg-slate-50 hover:bg-emerald-50 text-slate-500 hover:text-emerald-800 border border-dashed border-slate-300 hover:border-emerald-300 text-[10px] font-medium transition-colors"
                                                                title="Assign Exhibit Type">
                                                            <i data-lucide="plus" class="w-2.5 h-2.5"></i>
                                                            <span>Set Type</span>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Physical Quantities / Value Grid -->
                                            <div class="grid grid-cols-2 gap-2 bg-slate-50 p-2.5 rounded-xl border border-slate-100 text-xs">
                                                <div>
                                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Quantity &amp; Weight</span>
                                                    <span class="font-bold text-slate-800 font-mono-code">
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
                                                        <span class="text-[10px] text-slate-500 block truncate" title="{{ $exhibit->seizure_packaging }}">
                                                            {{ $exhibit->seizure_packaging }}
                                                        </span>
                                                    @endif
                                                </div>

                                                <div>
                                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Estimated Value</span>
                                                    <span class="font-extrabold text-emerald-700 font-mono-code">
                                                        @if($exhibit->seizure_value && $exhibit->seizure_value !== 'N/A')
                                                            GH₵ {{ is_numeric($exhibit->seizure_value) ? number_format((float)$exhibit->seizure_value, 2) : $exhibit->seizure_value }}
                                                        @elseif($exhibit->amount)
                                                            GH₵ {{ is_numeric(str_replace(',', '', $exhibit->amount)) ? number_format((float)str_replace(',', '', $exhibit->amount), 2) : $exhibit->amount }}
                                                        @else
                                                            <span class="text-slate-400 font-normal">Pending Valuation</span>
                                                        @endif
                                                    </span>
                                                    @php
                                                        $sourceLocation = $exhibit->source_of_exhibit ?: ($exhibit->source_of_exhibits ?: ($exhibit->seizure_location ?: null));
                                                    @endphp
                                                    @if($sourceLocation)
                                                        <span class="text-[10px] text-slate-500 block truncate" title="Source: {{ $sourceLocation }}">
                                                            Src: {{ $sourceLocation }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Category-Specific Quick Details -->
                                            @if($exhibit->veh_make || $exhibit->veh_reg_no || $exhibit->veh_vin)
                                                <div class="text-[11px] bg-blue-50/70 p-2 rounded-lg border border-blue-100 text-blue-900 font-mono-code flex items-center justify-between">
                                                    <span>🚗 {{ $exhibit->veh_make }} {{ $exhibit->veh_model }}</span>
                                                    <span class="font-bold font-mono-code text-blue-700">{{ $exhibit->veh_reg_no ?: ($exhibit->veh_vin ? 'VIN:' . substr($exhibit->veh_vin, -6) : '') }}</span>
                                                </div>
                                            @elseif($exhibit->wpn_model || $exhibit->wpn_serial_no || $exhibit->wpn_calibre)
                                                <div class="text-[11px] bg-amber-50/70 p-2 rounded-lg border border-amber-100 text-amber-900 font-mono-code flex items-center justify-between">
                                                    <span>🎯 {{ $exhibit->wpn_model ?: 'Weapon' }} ({{ $exhibit->wpn_calibre ?: 'N/A' }})</span>
                                                    <span class="font-bold text-amber-700">S/N: {{ $exhibit->wpn_serial_no ?: 'N/A' }}</span>
                                                </div>
                                            @elseif($exhibit->dev_model || $exhibit->dev_imei1 || $exhibit->dev_serial_no)
                                                <div class="text-[11px] bg-purple-50/70 p-2 rounded-lg border border-purple-100 text-purple-900 font-mono-code flex items-center justify-between">
                                                    <span>📱 {{ $exhibit->dev_model ?: 'Electronic Device' }}</span>
                                                    <span class="font-bold text-purple-700">{{ $exhibit->dev_imei1 ? 'IMEI:' . substr($exhibit->dev_imei1, 0, 8) . '...' : ($exhibit->dev_serial_no ? 'SN:' . $exhibit->dev_serial_no : '') }}</span>
                                                </div>
                                            @endif

                                            <!-- EXHIBIT REMARK (from Seized Item Section) -->
                                            @if($exhibit->exhibit_remark)
                                                <div class="bg-amber-50/50 border-l-2 border-amber-500 px-2.5 py-1.5 rounded-r-md">
                                                    <span class="text-[9px] font-bold uppercase tracking-wider text-amber-800 block">Exhibit Remark:</span>
                                                    <p class="text-[11px] text-slate-700 italic line-clamp-2">"{{ $exhibit->exhibit_remark }}"</p>
                                                </div>
                                            @endif

                                            <!-- ============================================== -->
                                            <!-- ASSOCIATED SUSPECT(S) SECTION                  -->
                                            <!-- ============================================== -->
                                            <div class="pt-2 border-t border-slate-100">
                                                <div class="flex items-center justify-between mb-1.5">
                                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 flex items-center space-x-1">
                                                        <i data-lucide="user-check" class="w-3 h-3 text-emerald-600"></i>
                                                        <span>Associated Suspect(s)</span>
                                                    </span>
                                                    <span class="text-[10px] font-mono-code text-slate-400">
                                                        Case: <a href="{{ $caseUrl }}" class="font-bold text-indigo-600 hover:underline">{{ $caseNo }}</a>
                                                    </span>
                                                </div>

                                                @if($suspects->isNotEmpty())
                                                    <div class="space-y-1.5">
                                                        @foreach($suspects->take(2) as $s)
                                                            <div class="flex items-center justify-between bg-slate-50 hover:bg-emerald-50/60 p-2 rounded-xl border border-slate-200/80 transition-colors">
                                                                <div class="flex items-center space-x-2.5 min-w-0">
                                                                    <!-- Suspect Avatar / Mugshot -->
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
                                                                    <div class="w-8 h-8 rounded-lg bg-emerald-100 border border-emerald-200 flex items-center justify-center text-emerald-800 font-extrabold text-[11px] shrink-0 overflow-hidden shadow-2xs">
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
                                                                        <div class="text-[10px] text-slate-500 flex items-center space-x-1.5 font-mono-code">
                                                                            @if($s->ghana_card)
                                                                                <span class="text-slate-600">{{ $s->ghana_card }}</span>
                                                                                <span>•</span>
                                                                            @endif
                                                                            <span class="text-emerald-700 font-semibold">{{ $s->role }}</span>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <a href="{{ $caseUrl }}" class="px-2 py-1 rounded-md bg-white hover:bg-emerald-600 hover:text-white border border-slate-200 text-slate-600 text-[10px] font-bold transition-colors shrink-0 shadow-2xs" title="View in Docket">
                                                                    Docket
                                                                </a>
                                                            </div>
                                                        @endforeach

                                                        @if($suspects->count() > 2)
                                                            <div class="text-center pt-0.5">
                                                                <span class="text-[10px] font-mono-code text-slate-500 font-semibold">
                                                                    + {{ $suspects->count() - 2 }} additional suspect(s) on case
                                                                </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @else
                                                    <!-- UNASSIGNED SUSPECT ALERT -->
                                                    <div class="bg-amber-50/80 border border-amber-200/80 rounded-xl p-2.5 flex items-center justify-between">
                                                        <div class="flex items-center space-x-2">
                                                            <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600 shrink-0"></i>
                                                            <span class="text-[11px] font-semibold text-amber-800">
                                                                No Suspect Associated Yet
                                                            </span>
                                                        </div>
                                                        @if($caseNo !== 'N/A')
                                                            <a href="{{ $caseUrl }}" class="text-[10px] font-bold text-amber-700 underline hover:text-amber-900">
                                                                Link in Case
                                                            </a>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>

                                        </div>
                                    </div>

                                    <!-- CARD FOOTER: Actions & Inspector -->
                                    <div class="p-3 bg-slate-50/90 border-t border-slate-100 flex items-center justify-between text-xs">
                                        <div class="text-[10px] text-slate-400 font-mono-code">
                                            Lodged: {{ $exhibit->created_at ? $exhibit->created_at->format('d M Y') : 'N/A' }}
                                        </div>

                                        <div class="flex items-center space-x-1.5 flex-wrap gap-y-1">
                                            @if($caseNo !== 'N/A')
                                                <a href="{{ $caseUrl }}" class="px-2 py-1 bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 font-bold text-[10px] rounded-lg transition-colors flex items-center space-x-1" title="Open Case File">
                                                    <i data-lucide="external-link" class="w-3 h-3 text-slate-400"></i>
                                                    <span class="hidden sm:inline">Case</span>
                                                </a>
                                            @endif

                                            <button type="button" 
                                                    data-vault-footer-qr-btn
                                                    onclick='openVaultQrModal(@json($exhibit))' 
                                                    class="px-2.5 py-1 bg-white hover:bg-emerald-50 border border-emerald-300 text-emerald-800 font-bold text-[11px] rounded-lg shadow-2xs transition-colors flex items-center space-x-1"
                                                    title="Assign or Preview Official Seized Property QR Code Tag">
                                                <i data-lucide="qr-code" class="w-3 h-3 text-emerald-600"></i>
                                                <span>QR Tag</span>
                                            </button>

                                            @php
                                                $hasBothClassificationAndType = !empty($exhibit->drug_classification) && $exhibit->drug_classification !== 'N/A' && !empty($exhibit->exhibit_type);
                                            @endphp
                                            @if(!$hasBothClassificationAndType)
                                            <button type="button" 
                                                    onclick='openVaultClassifyModal(@json($exhibit), true)' 
                                                    class="vault-classify-btn px-2.5 py-1 bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 font-bold text-[11px] rounded-lg transition-colors flex items-center space-x-1"
                                                    title="Classify Drug and Assign Exhibit Type">
                                                <i data-lucide="pill" class="w-3 h-3 text-emerald-600"></i>
                                                <span>Classify</span>
                                            </button>
                                            @endif
                                            
                                            <button type="button" 
                                                    onclick='openExhibitModal(@json($exhibit), @json($suspects))' 
                                                    class="px-2.5 py-1 bg-slate-900 hover:bg-slate-800 text-white font-bold text-[11px] rounded-lg shadow-2xs transition-colors flex items-center space-x-1">
                                                <i data-lucide="eye" class="w-3 h-3 text-white"></i>
                                                <span>Inspect</span>
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            @endforeach
                        </div>

                    @else
                        <!-- ============================================== -->
                        <!-- B. INTERACTIVE DATA TABLE VIEW                 -->
                        <!-- ============================================== -->
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                            <div class="overflow-x-auto custom-scrollbar">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead>
                                        <tr class="bg-slate-50/80 border-b border-slate-200 text-[10px] font-mono-code uppercase text-slate-500 tracking-wider">
                                            <th class="py-3 px-3.5 font-bold">Exhibit #</th>
                                            <th class="py-3 px-3.5 font-bold">QR Tag &amp; Property ID</th>
                                            <th class="py-3 px-3.5 font-bold">Category &amp; Type</th>
                                            <th class="py-3 px-3.5 font-bold">Commodity &amp; Classification</th>
                                            <th class="py-3 px-3.5 font-bold">Weight / Qty</th>
                                            <th class="py-3 px-3.5 font-bold">Est. Value</th>
                                            <th class="py-3 px-3.5 font-bold">Associated Suspect(s)</th>
                                            <th class="py-3 px-3.5 font-bold">Case #</th>
                                            <th class="py-3 px-3.5 font-bold text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
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
                                                elseif (str_contains($cat, 'devic')) $badgeClass = 'badge-category-device';
                                                elseif (str_contains($cat, 'money')) $badgeClass = 'badge-category-money';
                                            @endphp
                                            <tr data-exhibit-id="{{ $exhibit->id }}" class="hover:bg-slate-50/80 transition-colors">
                                                
                                                <!-- Exhibit Number -->
                                                <td class="py-3 px-3.5 font-mono-code font-bold text-slate-900 whitespace-nowrap">
                                                    {{ $exhibit->exhibit_number ?: 'EXH-#' . str_pad($exhibit->id, 4, '0', STR_PAD_LEFT) }}
                                                </td>

                                                <!-- QR Tag & Property ID -->
                                                <td class="py-3 px-3.5 whitespace-nowrap">
                                                    @if($exhibit->property_id || $exhibit->tag_url)
                                                        <button type="button" 
                                                                data-vault-prop-badge
                                                                onclick='openVaultQrModal(@json($exhibit))'
                                                                class="px-2 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] font-mono-code font-bold transition-colors inline-flex items-center space-x-1 shadow-2xs" 
                                                                title="View or reprint official Seized Property QR tag">
                                                            <i data-lucide="qr-code" class="w-3.5 h-3.5 text-emerald-600"></i>
                                                            <span>{{ $exhibit->property_id ?: 'TAGGED' }}</span>
                                                        </button>
                                                    @else
                                                        <button type="button" 
                                                                data-vault-prop-badge
                                                                onclick='openVaultQrModal(@json($exhibit))'
                                                                class="px-2 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 text-[10px] font-bold transition-colors inline-flex items-center space-x-1 shadow-2xs" 
                                                                title="Assign QR Code Tag">
                                                            <i data-lucide="qr-code" class="w-3.5 h-3.5 text-amber-600"></i>
                                                            <span>⚠️ Assign Tag</span>
                                                        </button>
                                                    @endif
                                                </td>

                                                <!-- Category & Type -->
                                                <td class="py-3 px-3.5 whitespace-nowrap">
                                                    <div class="space-y-1">
                                                        <div class="flex items-center space-x-1.5 flex-wrap gap-1">
                                                            <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase font-mono-code inline-block {{ $badgeClass }}">
                                                                {{ $exhibit->exhibit_category ?: 'General' }}
                                                            </span>
                                                            <span class="inline-flex items-center space-x-1 px-1.5 py-0.5 rounded-full text-[9px] font-mono-code font-bold uppercase bg-blue-50 text-blue-800 border border-blue-200" data-custodial-badge="{{ $exhibit->id }}" title="Live Custodial Status">
                                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                                                                <span class="custodial-status-text">{{ $exhibit->custodial_status ?: 'Seized' }}</span>
                                                            </span>
                                                        </div>
                                                        <div>
                                                            @if($exhibit->exhibit_type)
                                                                <span class="text-[9.5px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200 font-semibold truncate max-w-[130px] block"
                                                                      title="Exhibit Type">
                                                                    {{ $exhibit->exhibit_type }}
                                                                </span>
                                                            @else
                                                                <button type="button" 
                                                                        onclick='openVaultClassifyModal(@json($exhibit))'
                                                                        class="vault-classify-btn text-[9.5px] px-1.5 py-0.5 rounded bg-slate-50 hover:bg-emerald-50 text-slate-500 hover:text-emerald-800 border border-dashed border-slate-300 text-center block transition-colors"
                                                                        title="Click to set exhibit type">
                                                                    + Set Type
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>

                                                <!-- Commodity & Classification -->
                                                <td class="py-3 px-3.5 max-w-xs">
                                                    <div class="font-extrabold text-slate-900 truncate">
                                                        {{ $exhibit->commodity_name ?: ($exhibit->veh_model ?? $exhibit->wpn_model ?? $exhibit->dev_model ?? 'N/A') }}
                                                    </div>
                                                    <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                                        @if(!empty($exhibit->drug_classification) && $exhibit->drug_classification !== 'N/A')
                                                            <button type="button" 
                                                                    onclick='openVaultClassifyModal(@json($exhibit), true)'
                                                                    class="vault-drug-badge px-1.5 py-0.5 rounded bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-[9.5px] font-bold font-mono-code inline-flex items-center space-x-1 transition-colors cursor-pointer"
                                                                    title="Drug Classification: {{ $exhibit->drug_classification }} - Click to reclassify">
                                                                <i data-lucide="pill" class="w-2.5 h-2.5 text-emerald-600"></i>
                                                                <span class="truncate max-w-[140px]">{{ $exhibit->drug_classification }}</span>
                                                            </button>
                                                        @else
                                                            <button type="button" 
                                                                    onclick='openVaultClassifyModal(@json($exhibit), true)'
                                                                    class="vault-classify-btn px-1.5 py-0.5 rounded bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 text-[9.5px] font-bold inline-flex items-center space-x-1 transition-colors cursor-pointer"
                                                                    title="Assign Drug Classification to this Exhibit">
                                                                <i data-lucide="pill" class="w-2.5 h-2.5 text-emerald-600"></i>
                                                                <span>+ Assign Drug Class</span>
                                                            </button>
                                                        @endif
                                                    </div>
                                                    @if($exhibit->exhibit_remark)
                                                        <div class="text-[10px] text-amber-700 italic truncate mt-0.5" title="{{ $exhibit->exhibit_remark }}">
                                                            "{{ $exhibit->exhibit_remark }}"
                                                        </div>
                                                    @endif
                                                </td>

                                                <!-- Weight / Quantity -->
                                                <td class="py-3 px-3.5 font-mono-code font-semibold text-slate-800 whitespace-nowrap">
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

                                                <!-- Value -->
                                                <td class="py-3 px-3.5 font-mono-code font-bold text-emerald-700 whitespace-nowrap">
                                                    @if($exhibit->seizure_value && $exhibit->seizure_value !== 'N/A')
                                                        GH₵ {{ is_numeric($exhibit->seizure_value) ? number_format((float)$exhibit->seizure_value, 2) : $exhibit->seizure_value }}
                                                    @elseif($exhibit->amount)
                                                        GH₵ {{ is_numeric(str_replace(',', '', $exhibit->amount)) ? number_format((float)str_replace(',', '', $exhibit->amount), 2) : $exhibit->amount }}
                                                    @else
                                                        <span class="text-slate-400 font-normal">--</span>
                                                    @endif
                                                </td>

                                                <!-- Associated Suspects with Photo -->
                                                <td class="py-3 px-3.5">
                                                    @if($suspects->isNotEmpty())
                                                        <div class="space-y-2">
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
                                                                <div class="flex items-center space-x-2.5">
                                                                    <!-- Suspect Mugshot / Photo -->
                                                                    <div class="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden shrink-0 shadow-2xs relative group/mug">
                                                                        @if($mugUrl)
                                                                            <img src="{{ $mugUrl }}" 
                                                                                 alt="{{ $s->name }} Photo" 
                                                                                 class="w-full h-full object-cover transition-transform duration-200 group-hover/mug:scale-125 cursor-zoom-in"
                                                                                 title="{{ $s->name }} - Click to view dossier"
                                                                                 onclick='openExhibitModal(@json($exhibit), @json($suspects))'>
                                                                        @else
                                                                            <div class="w-full h-full bg-emerald-100 text-emerald-800 font-extrabold text-xs flex items-center justify-center">
                                                                                {{ strtoupper(substr($s->name, 0, 2)) }}
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                    <!-- Suspect Details -->
                                                                    <div class="min-w-0">
                                                                        <div class="font-bold text-slate-900 truncate max-w-[150px] leading-tight" title="{{ $s->name }}">
                                                                            {{ $s->name }}
                                                                            @if($s->alias)
                                                                                <span class="text-[10px] text-slate-500 font-normal">("{{ $s->alias }}")</span>
                                                                            @endif
                                                                        </div>
                                                                        <div class="text-[10px] text-slate-500 flex items-center space-x-1 font-mono-code mt-0.5">
                                                                            <span class="text-emerald-700 font-semibold truncate">{{ $s->role }}</span>
                                                                            @if($s->ghana_card)
                                                                                <span>•</span>
                                                                                <span class="truncate text-slate-400">{{ $s->ghana_card }}</span>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                            @if($suspects->count() > 2)
                                                                <div class="text-[9px] text-slate-400 font-mono-code pl-1">+ {{ $suspects->count() - 2 }} more suspect(s)</div>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-700 text-[10px] font-semibold border border-amber-200/60 inline-flex items-center space-x-1">
                                                            <span>⚠️ Unassigned</span>
                                                        </span>
                                                    @endif
                                                </td>

                                                <!-- Case Number -->
                                                <td class="py-3 px-3.5 font-mono-code whitespace-nowrap">
                                                    @if($caseNo !== 'N/A')
                                                        <a href="{{ $caseUrl }}" class="font-bold text-indigo-600 hover:underline">
                                                            {{ $caseNo }}
                                                        </a>
                                                    @else
                                                        <span class="text-slate-400">N/A</span>
                                                    @endif
                                                </td>

                                                <!-- Actions -->
                                                <td class="py-3 px-3.5 text-right whitespace-nowrap">
                                                    <div class="flex items-center justify-end space-x-1">
                                                        <button type="button" 
                                                                onclick='openVaultQrModal(@json($exhibit))' 
                                                                class="p-1.5 bg-white hover:bg-emerald-50 text-emerald-700 border border-emerald-300 rounded-lg shadow-3xs transition-colors"
                                                                title="Assign / View QR Tag">
                                                            <i data-lucide="qr-code" class="w-3.5 h-3.5"></i>
                                                        </button>
                                                        @php
                                                            $hasBothClassificationAndType = !empty($exhibit->drug_classification) && $exhibit->drug_classification !== 'N/A' && !empty($exhibit->exhibit_type);
                                                        @endphp
                                                        @if(!$hasBothClassificationAndType)
                                                        <button type="button" 
                                                                onclick='openVaultClassifyModal(@json($exhibit), true)' 
                                                                class="vault-classify-btn p-1.5 bg-white hover:bg-emerald-50 text-emerald-700 border border-emerald-300 rounded-lg shadow-3xs transition-colors"
                                                                title="Assign Drug Classification & Exhibit Type">
                                                            <i data-lucide="pill" class="w-3.5 h-3.5 text-emerald-600"></i>
                                                        </button>
                                                        @endif
                                                        <button type="button" 
                                                                onclick='openExhibitModal(@json($exhibit), @json($suspects))' 
                                                                class="px-2 py-1 bg-slate-900 hover:bg-slate-800 text-white font-bold text-[10px] rounded-lg shadow-2xs transition-colors inline-flex items-center space-x-1">
                                                            <i data-lucide="eye" class="w-3 h-3 text-white"></i>
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
                    <!-- 6. PAGINATION CONTROLS                         -->
                    <!-- ============================================== -->
                    <div class="mt-6 sm:mt-8 bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="text-xs text-slate-500 font-medium">
                            Showing <span class="font-bold text-slate-900">{{ $exhibits->firstItem() ?? 0 }}</span> to 
                            <span class="font-bold text-slate-900">{{ $exhibits->lastItem() ?? 0 }}</span> of 
                            <span class="font-bold text-slate-900">{{ $exhibits->total() }}</span> exhibits in Vault
                        </div>
                        <div>
                            {{ $exhibits->links() }}
                        </div>
                    </div>

                @endif
