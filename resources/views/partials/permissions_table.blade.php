<div id="permissionsTableMeta"
     class="hidden"
     data-first-item="{{ $officers->firstItem() ?? 0 }}"
     data-last-item="{{ $officers->lastItem() ?? 0 }}"
     data-total="{{ $officers->total() }}"
     data-total-all="{{ $totalStaffCount ?? $officers->total() }}"
     data-approved-count="{{ $approvedCount ?? 0 }}"
     data-customized-count="{{ $customizedCount ?? 0 }}"
     data-my-unit-count="{{ $myUnitCount ?? 0 }}"
     data-unit-css="{{ $unitCounts['css'] ?? 0 }}"
     data-unit-em="{{ $unitCounts['em'] ?? 0 }}"
     data-unit-lab="{{ $unitCounts['lab'] ?? 0 }}"
     data-unit-dpf="{{ $unitCounts['dpf'] ?? 0 }}"></div>

<div class="overflow-x-auto">
    <table id="permissionsTable" class="w-full text-left border-collapse">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold font-mono-code text-slate-700 uppercase tracking-wider">
                <th class="py-4 px-5 whitespace-nowrap w-[280px] lg:w-[320px]">Staff Officer</th>
                <th class="py-4 px-5">Operational Capabilities &amp; Permissions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 text-xs">
            @forelse($officers as $officer)
                @php
                    $nameParts = preg_split('/\s+/', trim((string) ($officer->fullname ?? 'O')));
                    $initials = count($nameParts) >= 2
                        ? strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1))
                        : strtoupper(substr((string) ($officer->fullname ?? 'OF'), 0, 2));

                    $canManage = \App\Http\Controllers\FieldOfficersController::canApproveStaff($viewer ?? auth()->user(), $officer);
                    $officerPermDefs = \App\Models\User::permissionsForUnit($officer->unit ?? null, request()->path());
                    if (isset($permissionDefs) && is_array($permissionDefs) && count($permissionDefs) < count($officerPermDefs)) {
                        $officerPermDefs = $permissionDefs;
                    }
                    $effectivePerms = array_intersect_key($officer->effectivePermissions(), $officerPermDefs);
                    $enabledTotal = count(array_filter($effectivePerms));
                    $permCount = count($officerPermDefs);
                    $progressPct = $permCount > 0 ? round(($enabledTotal / $permCount) * 100) : 0;
                    $isApproved = (bool) ($officer->is_approved ?? false);
                @endphp
                <tr class="hover:bg-slate-50/60 transition-colors align-top" data-officer-row="{{ $officer->id }}">
                    <!-- OFFICER PROFILE & CLEARANCE METER -->
                    <td class="py-5 px-5 align-top bg-slate-50/30 border-r border-slate-200/70">
                        <div class="flex flex-col gap-3.5">
                            <div class="flex items-start gap-3.5">
                                @if(!empty($officer->avatar))
                                    <img src="{{ $officer->avatar }}" alt="{{ $officer->fullname }}" width="44" height="44" class="w-11 h-11 rounded-xl object-cover border border-slate-200 shrink-0">
                                @else
                                    <div class="w-11 h-11 rounded-xl bg-slate-900 text-white flex items-center justify-center font-extrabold text-xs shrink-0 border border-slate-800" aria-hidden="true">
                                        {{ $initials }}
                                    </div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2 flex-wrap">
                                        <div class="font-extrabold text-slate-900 text-sm leading-snug truncate max-w-[180px]">
                                            {{ $officer->fullname }}
                                        </div>
                                        @if($isApproved)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 border border-emerald-200 text-emerald-800 font-mono-code text-[10px] font-bold">
                                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                Approved
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-100 border border-amber-300 text-amber-800 font-mono-code text-[10px] font-bold">
                                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l2.5 2.5"/>
                                                </svg>
                                                Pending
                                            </span>
                                        @endif
                                    </div>

                                    <div class="text-[11px] text-slate-600 font-mono-code mt-1 truncate">
                                        {{ $officer->service_id ?? 'N/A' }} · {{ '@' . ($officer->username ?? 'staff') }}
                                    </div>
                                </div>
                            </div>

                            <!-- Capability Allocation Progress Bar -->
                            <div class="rounded-xl bg-white border border-slate-200/90 p-2.5">
                                <div class="flex items-center justify-between gap-2 mb-1.5">
                                    <span class="text-[10px] font-mono-code font-bold uppercase tracking-wider text-slate-600">Module Access</span>
                                    <span class="px-2 py-0.5 rounded bg-emerald-50 border border-emerald-200 text-[11px] font-mono-code text-emerald-800 font-bold" data-officer-summary="{{ $officer->id }}">
                                        {{ $enabledTotal }}/{{ $permCount }} active
                                    </span>
                                </div>
                                <div class="w-full h-1.5 rounded-full bg-slate-200 overflow-hidden" aria-hidden="true">
                                    <div class="h-full rounded-full bg-emerald-700 transition-all duration-200"
                                         data-officer-bar="{{ $officer->id }}"
                                         style="width: {{ $progressPct }}%"></div>
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- OPERATIONAL CAPABILITY SWITCHES -->
                    <td class="py-5 px-5 align-top">
                        <form id="permForm-{{ $officer->id }}"
                              method="POST"
                              action="{{ url('/permissions/' . $officer->id . '/update') }}"
                              onsubmit="return submitOfficerPermissions(event, {{ $officer->id }})">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                                @foreach($officerPermDefs as $permKey => $permMeta)
                                    @php $checked = !empty($effectivePerms[$permKey]); @endphp
                                    <button type="button"
                                            role="switch"
                                            aria-checked="{{ $checked ? 'true' : 'false' }}"
                                            data-perm-toggle="{{ $officer->id }}"
                                            data-perm-key="{{ $permKey }}"
                                            @if(!$canManage)
                                                disabled
                                                title="Locked: Only this officer's Head of Unit can modify these permissions"
                                                data-locked-tooltip="Locked: Only this officer's Head of Unit can modify these permissions"
                                            @endif
                                            onclick="toggleOfficerCapability(this, {{ $officer->id }})"
                                            class="cap-tile w-full text-left flex items-start justify-between gap-3 p-3.5 rounded-xl border {{ $checked ? 'border-emerald-300 bg-emerald-50/50 hover:bg-emerald-50/90 hover:border-emerald-400' : 'border-slate-200 bg-slate-50/40 hover:bg-white hover:border-slate-300' }} {{ $canManage ? '' : 'opacity-70' }} min-h-[72px]">
                                        <input type="checkbox"
                                               name="permissions[{{ $permKey }}]"
                                               value="1"
                                               data-perm-checkbox="{{ $officer->id }}"
                                               {{ $checked ? 'checked' : '' }}
                                               {{ $canManage ? '' : 'disabled' }}
                                               class="sr-only">

                                        <span class="flex items-start gap-2.5 min-w-0 flex-1">
                                            <!-- Purpose-Specific Forensic Capability Icon -->
                                            <span data-cap-icon
                                                  aria-hidden="true"
                                                  class="w-8 h-8 rounded-lg border flex items-center justify-center shrink-0 mt-0.5 transition-colors {{ $checked ? 'bg-emerald-700 text-white border-emerald-700' : 'bg-white text-slate-600 border-slate-200' }}">
                                                @if($permKey === 'view_cases')
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                                    </svg>
                                                @elseif($permKey === 'create_cases')
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                    </svg>
                                                @elseif($permKey === 'edit_cases')
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                @elseif($permKey === 'manage_exhibits')
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                    </svg>
                                                @elseif($permKey === 'record_lab_analysis')
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                                    </svg>
                                                @elseif($permKey === 'record_digital_forensics')
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                                                    </svg>
                                                @elseif($permKey === 'generate_reports')
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                                    </svg>
                                                @endif
                                            </span>

                                            <span class="min-w-0 flex-1">
                                                <span data-cap-category class="block text-[10px] font-mono-code font-bold uppercase tracking-wider {{ $checked ? 'text-emerald-800' : 'text-slate-600' }}">
                                                    {{ $permMeta['category'] }}
                                                </span>
                                                <span class="block text-xs font-bold text-slate-900 leading-snug mt-0.5">
                                                    {{ $permMeta['label'] }}
                                                </span>
                                                @if(!empty($permMeta['description']))
                                                    <span class="block text-[11px] text-slate-600 leading-snug mt-1">
                                                        {{ $permMeta['description'] }}
                                                    </span>
                                                @endif
                                            </span>
                                        </span>

                                        <!-- Hardware-style Toggle Switch with Explicit Check/Minus State Glyph -->
                                        <span data-switch-track
                                              aria-hidden="true"
                                              class="relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-colors duration-150 mt-0.5 {{ $checked ? 'bg-emerald-700' : 'bg-slate-300' }}">
                                            <span data-switch-thumb
                                                  class="pointer-events-none inline-flex h-5 w-5 items-center justify-center rounded-full bg-white shadow-xs transition-transform duration-150 {{ $checked ? 'translate-x-5' : 'translate-x-0' }}">
                                                <svg data-switch-glyph-on class="w-3 h-3 text-emerald-800 {{ $checked ? '' : 'hidden' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                <svg data-switch-glyph-off class="w-3 h-3 text-slate-600 {{ $checked ? 'hidden' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M20 12H4"/>
                                                </svg>
                                            </span>
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="py-14 px-6 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 border border-slate-200 text-slate-600 flex items-center justify-center mx-auto mb-3" aria-hidden="true">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <div class="text-sm font-bold text-slate-900">No Matching Staff Officers Found</div>
                        <p class="text-xs text-slate-600 mt-1 max-w-md mx-auto">
                            No staff accounts match your current search or clearance filter. Clear the search filter or switch the clearance dropdown to view all unit staff.
                        </p>
                        <div class="mt-4">
                            <button type="button"
                                    onclick="clearPermissionsSearch(); setPermStatusFilter('all');"
                                    class="min-h-[44px] px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition-colors">
                                Reset Search &amp; Clearance Filters
                            </button>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($officers->hasPages())
    <div class="px-5 py-4 border-t border-slate-200 bg-slate-50 flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="text-xs text-slate-700 font-mono-code">
            Showing <span class="font-bold text-slate-900">{{ $officers->firstItem() ?? 0 }}</span> to <span class="font-bold text-slate-900">{{ $officers->lastItem() ?? 0 }}</span> of <span class="font-bold text-slate-900">{{ $officers->total() }}</span> staff accounts
        </div>
        <div>
            {{ $officers->links() }}
        </div>
    </div>
@endif
