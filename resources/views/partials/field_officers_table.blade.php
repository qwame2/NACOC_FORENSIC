@php
    $hasActiveStaffFilters = !empty($search)
        || (($statusFilter ?? 'all') !== 'all')
        || (empty($isUnitScoped) && ($unitFilter ?? 'all') !== 'all');
@endphp

<div id="fieldOfficersTableMeta"
     class="hidden"
     data-first-item="{{ $officers->firstItem() ?? 0 }}"
     data-last-item="{{ $officers->lastItem() ?? 0 }}"
     data-total="{{ $officers->total() }}"
     data-total-all="{{ $totalStaffCount ?? $officers->total() }}"
     data-approved-count="{{ $approvedStaffCount ?? 0 }}"
     data-pending-count="{{ $pendingStaffCount ?? 0 }}"
     data-online-count="{{ $onlineStaffCount ?? 0 }}"
     data-unit-css="{{ $unitCounts['css'] ?? 0 }}"
     data-unit-em="{{ $unitCounts['em'] ?? 0 }}"
     data-unit-lab="{{ $unitCounts['lab'] ?? 0 }}"
     data-unit-dpf="{{ $unitCounts['dpf'] ?? 0 }}"></div>

<div class="overflow-x-auto">
    <table id="fieldOfficersTable" class="w-full text-left border-collapse text-xs text-slate-700">
        <thead>
            <tr class="border-b border-slate-200/90 bg-slate-50/95 text-[10.5px] font-bold font-mono-code text-slate-500 uppercase tracking-wider select-none">
                <th class="py-3.5 px-5 whitespace-nowrap">Service ID / Enrolled</th>
                <th class="py-3.5 px-4">Staff Officer</th>
                <th class="py-3.5 px-4 whitespace-nowrap">Assigned Unit</th>
                <th class="py-3.5 px-4">Contact Details</th>
                <th class="py-3.5 px-4 whitespace-nowrap">Created Date &amp; Time</th>
                <th class="py-3.5 px-4 text-center whitespace-nowrap">Online Status</th>
                <th class="py-3.5 px-4 text-center whitespace-nowrap">Account Status</th>
                <th class="py-3.5 px-5 text-right whitespace-nowrap">Actions &amp; Authorization</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white">
            @forelse($officers as $officer)
                @php
                    $resolvedUnit = \App\Enums\ForensicUnit::resolve($officer->unit);
                    $unitCode = $resolvedUnit?->code() ?? 'UNIT';
                    $unitColorClass = match ($resolvedUnit) {
                        \App\Enums\ForensicUnit::CRIME_SCENE_SUPPORT        => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                        \App\Enums\ForensicUnit::EVIDENCE_MANAGEMENT        => 'bg-amber-50 text-amber-800 border-amber-200',
                        \App\Enums\ForensicUnit::LAB_ANALYSIS               => 'bg-teal-50 text-teal-800 border-teal-200',
                        \App\Enums\ForensicUnit::DIGITAL_PHYSICAL_FORENSICS => 'bg-sky-50 text-sky-800 border-sky-200',
                        default                                             => 'bg-slate-100 text-slate-700 border-slate-200',
                    };

                    $nameParts = preg_split('/\s+/', trim((string) ($officer->fullname ?? 'O'))) ?: [];
                    $initials = count($nameParts) >= 2
                        ? strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1))
                        : strtoupper(substr((string) ($officer->fullname ?? 'OF'), 0, 2));

                    $canManage = \App\Http\Controllers\FieldOfficersController::canApproveStaff($viewer ?? auth()->user(), $officer);
                    $isOfficerOnline = method_exists($officer, 'isOnline') ? $officer->isOnline() : false;
                    $revokedReason = !$officer->is_approved
                        ? \Illuminate\Support\Facades\Cache::get(\App\Http\Controllers\AuthController::staffRevokedReasonCacheKey($officer))
                        : null;

                    $accountStatusLabel = $officer->is_approved
                        ? 'Approved'
                        : ($revokedReason === 'failed_login'
                            ? 'Revoked (5 Failed Logins)'
                            : ($revokedReason === 'manual' ? 'Revoked' : 'Pending Unit Head'));

                    $enrolledDate = $officer->created_at ? $officer->created_at->format('d M Y') : 'N/A';
                    $enrolledTime = $officer->created_at ? $officer->created_at->format('h:i A') : 'N/A';
                    $lastSeenDisplay = $isOfficerOnline
                        ? 'Active now'
                        : ($officer->last_seen_at ? $officer->last_seen_at->diffForHumans() : 'No recent session');

                    $modalPayload = [
                        'id'            => $officer->id,
                        'fullname'      => $officer->fullname ?? 'Officer',
                        'username'      => $officer->username ?? 'unknown',
                        'service_id'    => $officer->service_id ?? 'N/A',
                        'unit'          => $officer->unit ?? 'Unassigned Unit',
                        'unit_code'     => $unitCode,
                        'phone'         => $officer->phone_number ?? 'Not provided',
                        'email'         => $officer->email ?? 'No email registered',
                        'avatar'        => $officer->avatar ?? '',
                        'initials'      => $initials,
                        'enrolled'      => $enrolledDate . ($enrolledTime !== 'N/A' ? ' at ' . $enrolledTime : ''),
                        'is_online'     => $isOfficerOnline,
                        'last_seen'     => $lastSeenDisplay,
                        'is_approved'   => (bool) $officer->is_approved,
                        'status_label'  => $accountStatusLabel,
                        'revoked'       => (string) ($revokedReason ?? ''),
                        'can_manage'    => $canManage,
                        'approve_url'   => url('/field-officers/' . $officer->id . '/approve'),
                        'revoke_url'    => url('/field-officers/' . $officer->id . '/revoke'),
                    ];
                @endphp
                <tr class="group hover:bg-slate-50/90 transition-colors" data-officer-id="{{ $officer->id }}">
                    {{-- COL 1: SERVICE ID / ENROLLED --}}
                    <td class="py-4 px-5 align-middle whitespace-nowrap">
                        <div class="flex items-center gap-1.5">
                            <span class="font-mono-code font-extrabold text-slate-900 text-xs tracking-tight">
                                {{ $officer->service_id ?? 'N/A' }}
                            </span>
                            @if(!empty($officer->service_id))
                                <button type="button"
                                        onclick="copyOfficerServiceId('{{ addslashes($officer->service_id) }}')"
                                        class="opacity-0 group-hover:opacity-100 focus:opacity-100 p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-200/70 transition-all"
                                        title="Copy Service ID {{ $officer->service_id }}"
                                        aria-label="Copy Service ID {{ $officer->service_id }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>
                            @endif
                        </div>
                        <div class="text-[10.5px] text-slate-500 font-mono-code mt-0.5">
                            Enrolled {{ $officer->created_at ? $officer->created_at->format('Y-m-d') : 'N/A' }}
                        </div>
                    </td>

                    {{-- COL 2: STAFF OFFICER --}}
                    <td class="py-3.5 px-4 align-middle min-w-[200px]">
                        <div class="flex items-center gap-3">
                            @if(!empty($officer->avatar))
                                <img src="{{ $officer->avatar }}"
                                     alt="{{ $officer->fullname }}"
                                     width="38"
                                     height="38"
                                     class="w-9.5 h-9.5 rounded-xl object-cover border border-slate-200 shrink-0 shadow-2xs">
                            @else
                                <div class="w-9.5 h-9.5 rounded-xl bg-slate-200 border border-slate-300/80 text-slate-800 flex items-center justify-center font-mono-code font-extrabold text-xs shrink-0">
                                    {{ $initials }}
                                </div>
                            @endif
                            <div class="min-w-0">
                                <button type="button"
                                        data-officer-modal='@json($modalPayload)'
                                        onclick="openOfficerDetailsModalFromBtn(this)"
                                        class="font-extrabold text-slate-900 hover:text-emerald-700 text-xs truncate block text-left transition-colors">
                                    {{ $officer->fullname }}
                                </button>
                                <div class="text-[11px] text-slate-500 font-mono-code truncate">
                                    {{ '@' . ($officer->username ?? 'unknown') }}
                                </div>
                            </div>
                        </div>
                    </td>

                    {{-- COL 3: ASSIGNED UNIT --}}
                    <td class="py-4 px-4 align-middle whitespace-nowrap">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border text-[11px] font-bold {{ $unitColorClass }}">
                            <span class="font-mono-code uppercase font-extrabold">{{ $unitCode }}</span>
                            <span class="opacity-40" aria-hidden="true">|</span>
                            <span>{{ $officer->unit ?? 'Unassigned Unit' }}</span>
                        </span>
                    </td>

                    {{-- COL 4: CONTACT DETAILS --}}
                    <td class="py-4 px-4 align-middle whitespace-nowrap">
                        <div class="font-mono-code font-semibold text-slate-800 text-[11px]">
                            {{ $officer->phone_number ?? 'N/A' }}
                        </div>
                        <div class="text-[11px] text-slate-500 truncate max-w-[200px]">
                            {{ $officer->email ?? 'No email registered' }}
                        </div>
                    </td>

                    {{-- COL 5: CREATED DATE & TIME --}}
                    <td class="py-4 px-4 align-middle whitespace-nowrap">
                        <div class="font-mono-code font-bold text-slate-800 text-[11px]">
                            {{ $enrolledDate }}
                        </div>
                        <div class="text-[10.5px] text-slate-500 font-mono-code mt-0.5">
                            {{ $enrolledTime }}
                        </div>
                    </td>

                    {{-- COL 6: ONLINE STATUS (NO DOTS) --}}
                    <td class="py-4 px-4 align-middle text-center whitespace-nowrap">
                        @if($isOfficerOnline)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-mono-code font-extrabold uppercase">
                                Online
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200 text-[10px] font-mono-code font-extrabold uppercase">
                                Offline
                            </span>
                        @endif
                    </td>

                    {{-- COL 7: ACCOUNT STATUS (NO DOTS) --}}
                    <td class="py-4 px-4 align-middle text-center whitespace-nowrap">
                        @if($officer->is_approved)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-mono-code font-extrabold uppercase">
                                Approved
                            </span>
                        @elseif($revokedReason === 'failed_login')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200 text-[10px] font-mono-code font-extrabold uppercase"
                                  title="Automatically revoked after 5 consecutive failed sign-in attempts">
                                Revoked (5 Failed Logins)
                            </span>
                        @elseif($revokedReason === 'manual')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200 text-[10px] font-mono-code font-extrabold uppercase">
                                Revoked
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200 text-[10px] font-mono-code font-extrabold uppercase">
                                Pending Unit Head
                            </span>
                        @endif
                    </td>

                    {{-- COL 8: ACTIONS & AUTHORIZATION --}}
                    <td class="py-4 px-5 align-middle text-right whitespace-nowrap">
                        <div class="inline-flex items-center justify-end gap-1.5">
                            <button type="button"
                                    data-officer-modal='@json($modalPayload)'
                                    onclick="openOfficerDetailsModalFromBtn(this)"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/90 text-[11px] font-bold transition-colors"
                                    title="View full staff officer profile">
                                <span>Details</span>
                            </button>

                            @if($canManage)
                                @if(!$officer->is_approved)
                                    <button type="button"
                                            onclick="handleStaffApproval('{{ url('/field-officers/' . $officer->id . '/approve') }}', 'approve', this)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold font-mono-code shadow-2xs transition-colors">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span>{{ $revokedReason ? 'Activate' : 'Approve' }}</span>
                                    </button>
                                @else
                                    <button type="button"
                                            onclick="handleStaffApproval('{{ url('/field-officers/' . $officer->id . '/revoke') }}', 'revoke', this)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white hover:bg-rose-50 text-rose-700 border border-rose-200 text-[11px] font-bold font-mono-code transition-colors">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                        <span>Revoke</span>
                                    </button>
                                @endif
                            @else
                                <span class="inline-flex items-center px-2.5 py-1.5 rounded-xl bg-slate-100 text-slate-500 border border-slate-200 text-[10px] font-mono-code font-bold uppercase"
                                      title="Only this officer's Head of Unit can approve or revoke account access">
                                    Unit Head Only
                                </span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="py-16 px-6 text-center bg-white">
                        <div class="max-w-md mx-auto space-y-3">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 border border-slate-200 text-slate-400 flex items-center justify-center mx-auto">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div class="text-sm font-extrabold text-slate-900">No Matching Field Officers Found</div>
                            @if($hasActiveStaffFilters)
                                <p class="text-xs text-slate-500 leading-relaxed">
                                    No staff officer records match your current search query, assigned unit, or approval status filter.
                                </p>
                                <div class="pt-1">
                                    <button type="button"
                                            onclick="resetFieldOfficerFilters()"
                                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-800 border border-slate-300 text-xs font-bold transition-colors">
                                        <span>Clear active filters</span>
                                    </button>
                                </div>
                            @else
                                <p class="text-xs text-slate-500 leading-relaxed">
                                    Enrolled unit staff officers and pending account clearance requests will appear here automatically.
                                </p>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($officers->hasPages())
    <div class="px-5 py-3.5 border-t border-slate-200/80 bg-slate-50/70 flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="text-[11px] text-slate-600 font-mono-code">
            Showing <span class="font-bold text-slate-900">{{ $officers->firstItem() ?? 0 }}</span> to <span class="font-bold text-slate-900">{{ $officers->lastItem() ?? 0 }}</span> of <span class="font-bold text-slate-900">{{ $officers->total() }}</span> field officers
        </div>
        <div>
            {{ $officers->links() }}
        </div>
    </div>
@endif
