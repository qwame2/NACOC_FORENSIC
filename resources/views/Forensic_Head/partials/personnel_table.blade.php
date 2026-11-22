@php
    $users = $users ?? collect();
@endphp

<div id="personnelTableMeta"
     class="hidden"
     data-first-item="{{ method_exists($users, 'firstItem') ? ($users->firstItem() ?? 0) : ($users->count() > 0 ? 1 : 0) }}"
     data-last-item="{{ method_exists($users, 'lastItem') ? ($users->lastItem() ?? 0) : $users->count() }}"
     data-total="{{ method_exists($users, 'total') ? $users->total() : $users->count() }}"
     data-total-all="{{ $totalUsersCount ?? (method_exists($users, 'total') ? $users->total() : $users->count()) }}"
     data-head-count="{{ $unitHeadsCount ?? 0 }}"
     data-approved-count="{{ $approvedUsersCount ?? 0 }}"
     data-pending-count="{{ $pendingUsersCount ?? 0 }}">
</div>

<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse text-xs" id="personnelTable">
        <thead>
            <tr class="bg-slate-100 border-b border-slate-200 text-slate-700 font-mono-code uppercase text-[11px] tracking-wider select-none">
                <th class="py-3.5 px-5 font-extrabold">Service ID</th>
                <th class="py-3.5 px-5 font-extrabold">Officer / Personnel</th>
                <th class="py-3.5 px-5 font-extrabold">Assigned Unit</th>
                <th class="py-3.5 px-5 font-extrabold text-center">Command Role</th>
                <th class="py-3.5 px-5 font-extrabold">Contact Details</th>
                <th class="py-3.5 px-5 font-extrabold text-center">Account Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200" id="personnelTableBody">
            @forelse($users as $personnel)
                @php
                    $isDG = ($personnel->unit === 'Office of the Director-General' || $personnel->unit === 'Director General' || str_contains(strtolower((string) $personnel->unit), 'director general') || str_contains(strtolower((string) $personnel->unit), 'director-general'));
                    $resolvedUnit = \App\Enums\ForensicUnit::resolve($personnel->unit);
                    $unitCode = $isDG ? 'DG' : ($resolvedUnit?->code() ?? 'UNIT');
                    $unitBadgeClass = $isDG ? 'bg-purple-900 text-white border-purple-800' : match($resolvedUnit) {
                        \App\Enums\ForensicUnit::CRIME_SCENE_SUPPORT => 'bg-amber-50 text-amber-800 border-amber-200',
                        \App\Enums\ForensicUnit::EVIDENCE_MANAGEMENT => 'bg-indigo-50 text-indigo-800 border-indigo-200',
                        \App\Enums\ForensicUnit::LAB_ANALYSIS => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                        \App\Enums\ForensicUnit::DIGITAL_PHYSICAL_FORENSICS => 'bg-cyan-50 text-cyan-800 border-cyan-200',
                        \App\Enums\ForensicUnit::FORENSIC_DIRECTORATE => 'bg-slate-900 text-white border-slate-800',
                        null => 'bg-slate-100 text-slate-700 border-slate-200',
                    };

                    $avatarUrl = null;
                    if (!empty($personnel->avatar)) {
                        $rawAvatar = $personnel->avatar;
                        $avatarUrl = (str_starts_with($rawAvatar, 'data:') || str_starts_with($rawAvatar, 'http://') || str_starts_with($rawAvatar, 'https://'))
                            ? $rawAvatar
                            : asset($rawAvatar);
                    }

                    $parts = preg_split('/\s+/', trim($personnel->fullname ?? 'Officer'));
                    $initials = count($parts) >= 2
                        ? strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1))
                        : strtoupper(substr($personnel->fullname ?? 'OF', 0, 2));
                @endphp
                <tr class="table-row-hover transition-colors personnel-row">
                    <td class="py-4 px-5 whitespace-nowrap">
                        <div class="font-extrabold font-mono-code text-blue-700 text-xs">{{ $personnel->service_id }}</div>
                        <div class="text-[10px] text-slate-500 font-mono-code mt-0.5">
                            Joined {{ $personnel->created_at ? $personnel->created_at->format('Y-m-d') : 'N/A' }}
                        </div>
                    </td>
                    <td class="py-3.5 px-5">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center font-extrabold text-slate-700 text-xs overflow-hidden shrink-0">
                                @if($avatarUrl)
                                    <img src="{{ $avatarUrl }}" alt="{{ $personnel->fullname }}" class="w-full h-full object-cover">
                                @else
                                    <span>{{ $initials }}</span>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="font-extrabold text-slate-900 text-xs truncate max-w-[210px]" title="{{ $personnel->fullname }}">
                                    {{ $personnel->fullname }}
                                </div>
                                <div class="text-[10px] text-slate-500 font-mono-code truncate max-w-[210px]">
                                    {{ '@' . ($personnel->username ?? 'n/a') }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="py-4 px-5 whitespace-nowrap">
                        @php
                            $unitLabel = $isDG ? 'Office of the Director-General' : (($resolvedUnit === \App\Enums\ForensicUnit::FORENSIC_DIRECTORATE && strcasecmp(trim((string) $personnel->unit), 'Forensic Directorate') === 0)
                                ? 'Forensic Department'
                                : $personnel->unit);
                        @endphp
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10.5px] font-bold font-mono-code border {{ $unitBadgeClass }}">
                            <span class="font-black">{{ $unitCode }}</span>
                            <span>&bull;</span>
                            <span>{{ $unitLabel }}</span>
                        </span>
                    </td>
                    <td class="py-4 px-5 text-center whitespace-nowrap">
                        @if($isDG)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold font-mono-code bg-purple-50 text-purple-800 border border-purple-200">
                                Director-General
                            </span>
                        @elseif($personnel->is_head)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold font-mono-code bg-blue-50 text-blue-800 border border-blue-200">
                                {{ $resolvedUnit === \App\Enums\ForensicUnit::FORENSIC_DIRECTORATE ? 'Department Head' : 'Unit Head' }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold font-mono-code bg-slate-100 text-slate-700 border border-slate-200">
                                Forensic Officer
                            </span>
                        @endif
                    </td>
                    <td class="py-4 px-5">
                        <div class="font-mono-code font-bold text-slate-800 text-[11px]">{{ $personnel->phone_number ?: 'N/A' }}</div>
                        <div class="text-[10.5px] text-slate-500 font-mono-code truncate max-w-[220px]" title="{{ $personnel->email ?? '' }}">
                            {{ $personnel->email ?: 'No official email recorded' }}
                        </div>
                    </td>
                    <td class="py-4 px-5 text-center whitespace-nowrap">
                        @if($personnel->is_approved)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold font-mono-code bg-emerald-50 text-emerald-800 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                <span>Approved</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold font-mono-code bg-amber-50 text-amber-800 border border-amber-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                <span>Pending Verification</span>
                            </span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-12 text-center text-slate-500 font-mono-code text-xs">
                        <div class="flex flex-col items-center justify-center space-y-2">
                            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <p class="font-bold text-slate-700">No personnel accounts found</p>
                            <p class="text-[11px] text-slate-400">Try adjusting your search query or unit/role/status filters.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if(method_exists($users, 'links') && $users->hasPages())
    <div class="border-t border-slate-200">
        {{ $users->links() }}
    </div>
@endif

