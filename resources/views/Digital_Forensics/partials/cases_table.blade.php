@php
    $cases = $cases ?? collect();
@endphp
<div class="overflow-x-auto">
<div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-700" id="regMasterCaseTable">
                            <thead class="bg-slate-100/80 border-b border-slate-200 text-slate-500 font-mono-code text-[11px] uppercase">
                                <tr>
                                    <th class="py-3.5 px-5">Case #</th>
                                    <th class="py-3.5 px-5">Logged Date</th>
                                    <th class="py-3.5 px-5">Title</th>
                                    <th class="py-3.5 px-5">User Disposition</th>
                                    <th class="py-3.5 px-5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 font-mono-code">
                                @forelse($cases as $c)
                                @php
                                    $cDateRaw = !empty($c->incident_date) ? $c->incident_date : ($c->created_at ? $c->created_at->format('Y-m-d') : date('Y-m-d'));
                                    $cTime = strtotime((string)$cDateRaw);
                                    $cDateIso = ($cTime && $cTime > 0) ? date('Y-m-d', $cTime) : date('Y-m-d');
                                    $cDateDisplay = ($cTime && $cTime > 0) ? date('d M Y', $cTime) : date('d M Y');
                                    $sDispListDfu = $c->getElectronicDeviceSuspects();
                                    if (empty($sDispListDfu)) {
                                        $sDispListDfu = $c->getSuspectsList();
                                    }

                                    $sList = !empty($sDispListDfu) ? $sDispListDfu : [[
                                        'suspect_name'  => $c->suspect_name ?? 'Unknown User',
                                        'suspect_alias' => $c->suspect_alias ?? null,
                                        'mugshot_front' => $c->mugshot_front ?? null,
                                        'role'          => 'Primary User',
                                        'is_associate'  => false,
                                    ]];
                                    $primaryS = $sList[0] ?? [];
                                    $primaryName = $primaryS['suspect_name'] ?? ($c->suspect_name ?? 'Unknown User');
                                    $primaryAlias = $primaryS['suspect_alias'] ?? ($c->suspect_alias ?? null);
                                    $allNames = implode(' ', array_filter(array_column($sList, 'suspect_name')));
                                    $allAliases = implode(' ', array_filter(array_column($sList, 'suspect_alias')));
                                @endphp
                                <tr class="hover:bg-slate-50 case-register-row" data-date="{{ $cDateIso }}" data-caseref="{{ $c->case_number }}" data-suspect="{{ strtolower($allNames . ' ' . $allAliases) }}" data-user="{{ strtolower($allNames . ' ' . $allAliases) }}">
                                    <td class="py-3.5 px-5 font-bold font-mono-code text-emerald-700 whitespace-nowrap">
                                        <div>{{ $c->case_number }}</div>
                                    </td>
                                    <td class="py-3.5 px-5 whitespace-nowrap">
                                        <div class="font-bold text-[11px] text-slate-800">{{ $cDateDisplay }}</div>
                                    </td>
                                    <td class="py-3.5 px-5 min-w-[280px]">
                                        <div class="flex items-center gap-3">
                                            {{-- Suspect Front Mugshot Thumbnail --}}
                                            <div class="flex items-center {{ count($sList) > 1 ? '-space-x-3.5 hover:space-x-1.5 transition-all duration-200' : '' }} shrink-0 py-0.5">
                                                @foreach($sList as $sIdx => $sItem)
                                                    @php
                                                        $rawPic = $sItem['mugshot_front'] ?? ($sIdx === 0 ? $c->mugshot_front : null);
                                                        $sPic = null;
                                                        if (!empty($rawPic)) {
                                                            $rawPic = trim((string)$rawPic);
                                                            $sPic = (!str_starts_with($rawPic, 'data:image') && !str_starts_with($rawPic, 'http://') && !str_starts_with($rawPic, 'https://') && !str_starts_with($rawPic, '/'))
                                                                ? asset($rawPic)
                                                                : $rawPic;
                                                        }
                                                        $sItemName = $sItem['suspect_name'] ?? 'User';
                                                        $isAssoc = !empty($sItem['is_associate']);
                                                    @endphp
                                                    <div class="relative group cursor-pointer" style="z-index: {{ 10 - $sIdx }};" 
                                                         @if(!empty($sPic)) onclick="openMugshotModal('{{ addslashes($sPic) }}', '{{ addslashes($sItemName) }}')" @endif
                                                         title="{{ $sItemName }}{{ !empty($sPic) ? ' (Click to enlarge photo)' : '' }}">
                                                        @if($isAssoc)
                                                            <span class="absolute -top-1 -right-1 z-20 w-3.5 h-3.5 bg-amber-500 border border-white rounded-full flex items-center justify-center text-[7.5px] font-black text-white font-mono-code shadow-2xs" title="Associate User">A</span>
                                                        @endif
                                                        <div class="w-9 h-11 rounded-lg border border-slate-200 bg-slate-100 overflow-hidden shadow-2xs flex items-center justify-center group-hover:ring-2 group-hover:ring-emerald-500 transition-all">
                                                            @if(!empty($sPic))
                                                                <img src="{{ $sPic }}" alt="{{ $sItemName }}" class="w-full h-full object-cover">
                                                            @else
                                                                <div class="w-full h-full flex items-center justify-center bg-slate-100 text-slate-300" title="No front mugshot on file">
                                                                    <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                                    </svg>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-bold text-slate-900 font-sans text-xs leading-snug">{{ $c->title }}</div>
                                                <div class="text-[9.5px] text-emerald-700 font-mono-code font-bold mt-0.5 flex items-center gap-1">
                                                    <span>Digital &amp; Mobile Cyber Forensics HQ</span>
                                                </div>
                                                <div class="text-[10px] text-slate-500 font-sans mt-0.5 truncate max-w-[220px]" title="{{ $primaryName }}">
                                                    <span class="font-medium text-slate-400">User:</span> <span class="font-bold text-slate-800 font-mono-code uppercase">{{ $primaryName }}</span>
                                                    @if(!empty($primaryAlias) && strtoupper($primaryAlias) !== 'N/A')
                                                        <span class="italic text-slate-400">({{ $primaryAlias }})</span>
                                                    @endif
                                                    @if(count($sList) > 1)
                                                        <span class="inline-block px-1 py-0.2 rounded text-[7.5px] font-bold bg-slate-100 text-slate-600 border border-slate-200 ml-1 font-mono-code" title="{{ count($sList) - 1 }} Co-User(s)">+{{ count($sList) - 1 }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-5 whitespace-nowrap">
                                        <div class="space-y-1">
                                            @foreach ($sDispListDfu as $sObj)
                                                @php
                                                    $dispVal = $sObj['suspect_disposition'] ?? 'Active — Under Investigation';
                                                    $dispReason = $sObj['disposition_reason'] ?? '';
                                                    $dispBadge = match (true) {
                                                        str_contains($dispVal, 'Cleared') => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                                        str_contains($dispVal, 'Released') => 'bg-blue-100 text-blue-800 border-blue-300',
                                                        str_contains($dispVal, 'Reclassified') => 'bg-teal-100 text-teal-800 border-teal-300',
                                                        str_contains($dispVal, 'Referred') => 'bg-amber-100 text-amber-800 border-amber-300',
                                                        default => 'bg-slate-100 text-slate-700 border-slate-300',
                                                    };
                                                @endphp
                                                <div class="flex items-center gap-1.5" title="{{ !empty($dispReason) ? 'Reason: ' . $dispReason : $dispVal }}">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[9.5px] font-bold font-mono-code border {{ $dispBadge }}">
                                                        @if (count($sDispListDfu) > 1)
                                                            <span class="font-extrabold text-slate-900 mr-1">{{ $sObj['suspect_name'] }}:</span>
                                                        @endif
                                                        {{ $dispVal }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-5 text-right">
                                        <a href="{{ url('/cases/' . $c->case_number . '/review?from=Digital_Forensics_Register') }}" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] rounded-lg shadow-xs transition-colors">Review Extraction File</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-slate-500 font-mono-code text-xs">
                                        <div class="flex flex-col items-center justify-center space-y-2">
                                            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            <p class="font-bold text-slate-700">No digital forensics cases registered yet</p>
                                            <p class="text-[11px] text-slate-400">When digital forensic dockets are logged or assigned, they will appear here.</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                                <tr id="regNoRecordsRow" class="hidden">
                                    <td colspan="5" class="py-8 text-center text-slate-400 font-semibold font-mono-code">
                                        <div class="space-y-2">
                                            <p>No matching digital forensic cases found.</p>
                                            <button type="button" onclick="resetRegDateFilters()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-bold border border-emerald-200 text-xs transition-colors cursor-pointer">
                                                <span>Reset to All Time</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    @if(method_exists($cases, 'links') && $cases->hasPages())
                        <div class="border-t border-slate-200">
                            {{ $cases->links() }}
                        </div>
                    @endif