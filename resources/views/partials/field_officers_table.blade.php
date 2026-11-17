<div id="fieldOfficersTableMeta"
     class="hidden"
     data-first-item="{{ $officers->firstItem() ?? 0 }}"
     data-last-item="{{ $officers->lastItem() ?? 0 }}"
     data-total="{{ $officers->total() }}"
     data-total-all="{{ $totalStaffCount ?? $officers->total() }}"
     data-approved-count="{{ $approvedStaffCount ?? 0 }}"
     data-pending-count="{{ $pendingStaffCount ?? 0 }}"
     data-unit-css="{{ $unitCounts['css'] ?? 0 }}"
     data-unit-em="{{ $unitCounts['em'] ?? 0 }}"
     data-unit-lab="{{ $unitCounts['lab'] ?? 0 }}"
     data-unit-dpf="{{ $unitCounts['dpf'] ?? 0 }}"></div>

<div class="overflow-x-auto">
    <table id="fieldOfficersTable" class="w-full text-left border-collapse">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50/90 text-[10px] font-bold font-mono-code text-slate-500 uppercase tracking-wider">
                <th class="py-3.5 px-5">Service ID / Enrolled</th>
                <th class="py-3.5 px-5">Staff Officer</th>
                <th class="py-3.5 px-5">Assigned Unit</th>
                <th class="py-3.5 px-5">Contact Details</th>
                <th class="py-3.5 px-5">Created Date &amp; Time</th>
                <th class="py-3.5 px-5 text-center">Online Status</th>
                <th class="py-3.5 px-5 text-center">Account Status</th>
                <th class="py-3.5 px-5 text-center">Unit Head Authorization</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-xs">
            @forelse($officers as $officer)
                @php
                    $resolvedUnit = \App\Enums\ForensicUnit::resolve($officer->unit);
                    $unitCode = $resolvedUnit?->code() ?? 'UNIT';
                    $unitColorClass = match($resolvedUnit) {
                        \App\Enums\ForensicUnit::CRIME_SCENE_SUPPORT => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        \App\Enums\ForensicUnit::EVIDENCE_MANAGEMENT => 'bg-amber-50 text-amber-800 border-amber-200',
                        \App\Enums\ForensicUnit::LAB_ANALYSIS => 'bg-teal-50 text-teal-800 border-teal-200',
                        \App\Enums\ForensicUnit::DIGITAL_PHYSICAL_FORENSICS => 'bg-purple-50 text-purple-800 border-purple-200',
                        default => 'bg-slate-100 text-slate-700 border-slate-200',
                    };

                    $nameParts = preg_split('/\s+/', trim((string) ($officer->fullname ?? 'O')));
                    $initials = count($nameParts) >= 2
                        ? strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1))
                        : strtoupper(substr((string) ($officer->fullname ?? 'OF'), 0, 2));

                    $canManage = \App\Http\Controllers\FieldOfficersController::canApproveStaff($viewer ?? auth()->user(), $officer);
                @endphp
                <tr class="hover:bg-slate-50/80 transition-colors" data-officer-id="{{ $officer->id }}">
                    <td class="py-3.5 px-5 whitespace-nowrap">
                        <div class="font-mono-code font-extrabold text-slate-900 text-xs">{{ $officer->service_id ?? 'N/A' }}</div>
                        <div class="text-[10px] text-slate-400 font-mono-code mt-0.5">
                            Enrolled {{ $officer->created_at ? $officer->created_at->format('Y-m-d') : 'N/A' }}
                        </div>
                    </td>

                    <td class="py-3.5 px-5">
                        <div class="flex items-center space-x-3">
                            @if(!empty($officer->avatar))
                                <img src="{{ $officer->avatar }}" alt="{{ $officer->fullname }}" class="w-9 h-9 rounded-xl object-cover border border-slate-200 shrink-0 shadow-2xs">
                            @else
                                <div class="w-9 h-9 rounded-xl bg-slate-900 text-white flex items-center justify-center font-extrabold text-xs shrink-0 shadow-2xs">
                                    {{ $initials }}
                                </div>
                            @endif
                            <div class="min-w-0">
                                <div class="font-bold text-slate-900 text-xs truncate">{{ $officer->fullname }}</div>
                                <div class="text-[11px] text-slate-500 font-mono-code truncate">{{ '@' . ($officer->username ?? 'unknown') }}</div>
                            </div>
                        </div>
                    </td>

                    <td class="py-3.5 px-5 whitespace-nowrap">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border text-[11px] font-bold {{ $unitColorClass }}">
                            <span class="font-mono-code uppercase">{{ $unitCode }}</span>
                            <span class="opacity-50" aria-hidden="true">|</span>
                            <span>{{ $officer->unit ?? 'Unassigned Unit' }}</span>
                        </span>
                    </td>

                    <td class="py-3.5 px-5 whitespace-nowrap">
                        <div class="font-mono-code font-semibold text-slate-800 text-[11px]">{{ $officer->phone_number ?? 'N/A' }}</div>
                        <div class="text-[11px] text-slate-500 truncate max-w-[200px]">{{ $officer->email ?? 'No email registered' }}</div>
                    </td>

                    <td class="py-3.5 px-5 whitespace-nowrap">
                        <div class="font-mono-code font-bold text-slate-800 text-[11px]">
                            {{ $officer->created_at ? $officer->created_at->format('d M Y') : 'N/A' }}
                        </div>
                        <div class="text-[10px] text-slate-500 font-mono-code mt-0.5">
                            {{ $officer->created_at ? $officer->created_at->format('h:i A') : 'N/A' }}
                        </div>
                    </td>

                    <td class="py-3.5 px-5 text-center whitespace-nowrap">
                        @php
                            $isOfficerOnline = method_exists($officer, 'isOnline') ? $officer->isOnline() : false;
                        @endphp
                        @if($isOfficerOnline)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-mono-code font-extrabold uppercase">
                                Online
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200 text-[10px] font-mono-code font-extrabold uppercase">
                                Offline
                            </span>
                        @endif
                    </td>

                    <td class="py-3.5 px-5 text-center whitespace-nowrap">
                        @if($officer->is_approved)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-mono-code font-extrabold uppercase">
                                Approved
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200 text-[10px] font-mono-code font-extrabold uppercase">
                                Pending Unit Head
                            </span>
                        @endif
                    </td>

                    <td class="py-3.5 px-5 text-center whitespace-nowrap">
                        @if($canManage)
                            @if(!$officer->is_approved)
                                <button type="button"
                                        onclick="handleStaffApproval('{{ url('/field-officers/' . $officer->id . '/approve') }}', 'approve', this)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold font-mono-code shadow-xs transition-all cursor-pointer border-none">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    <span>Approve</span>
                                </button>
                            @else
                                <button type="button"
                                        onclick="handleStaffApproval('{{ url('/field-officers/' . $officer->id . '/revoke') }}', 'revoke', this)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white hover:bg-rose-50 text-rose-700 border border-rose-200 text-[11px] font-bold font-mono-code transition-all cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    <span>Revoke</span>
                                </button>
                            @endif
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-100 text-slate-500 border border-slate-200 text-[10px] font-mono-code font-bold uppercase" title="Only this officer's Head of Unit can approve or revoke account access">
                                Unit Head Only
                            </span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="py-12 px-6 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div class="text-sm font-bold text-slate-800">No Matching Field Officers Found</div>
                        <p class="text-xs text-slate-500 mt-1">Adjust your unit, approval status, or search filter parameters.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($officers->hasPages())
    <div class="px-5 py-3.5 border-t border-slate-200 bg-slate-50/70 flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="text-xs text-slate-500 font-mono-code">
            Showing <span class="font-bold text-slate-900">{{ $officers->firstItem() ?? 0 }}</span> to <span class="font-bold text-slate-900">{{ $officers->lastItem() ?? 0 }}</span> of <span class="font-bold text-slate-900">{{ $officers->total() }}</span> field officers
        </div>
        <div>
            {{ $officers->links() }}
        </div>
    </div>
@endif

