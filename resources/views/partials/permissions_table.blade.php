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
            <tr class="border-b border-slate-200 bg-slate-50/90 text-[10px] font-bold font-mono-code text-slate-600 uppercase tracking-wider">
                <th class="py-3.5 px-4 whitespace-nowrap">Staff Officer</th>
                <th class="py-3.5 px-4">Operational Capabilities &amp; Permissions</th>
                <th class="py-3.5 px-4 text-right whitespace-nowrap">Quick Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200/70 text-xs">
            @forelse($officers as $officer)
                @php
                    $nameParts = preg_split('/\s+/', trim((string) ($officer->fullname ?? 'O')));
                    $initials = count($nameParts) >= 2
                        ? strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1))
                        : strtoupper(substr((string) ($officer->fullname ?? 'OF'), 0, 2));

                    $canManage = \App\Http\Controllers\FieldOfficersController::canApproveStaff($viewer ?? auth()->user(), $officer);
                    $effectivePerms = $officer->effectivePermissions();
                    $enabledTotal = count(array_filter($effectivePerms));
                    $permCount = count($permissionDefs);
                @endphp
                <tr class="hover:bg-slate-50/75 transition-colors align-top" data-officer-row="{{ $officer->id }}">
                    <td class="py-4 px-4 whitespace-nowrap">
                        <div class="flex items-center space-x-3">
                            @if(!empty($officer->avatar))
                                <img src="{{ $officer->avatar }}" alt="{{ $officer->fullname }}" width="36" height="36" class="w-9 h-9 rounded-xl object-cover border border-slate-200 shrink-0">
                            @else
                                <div class="w-9 h-9 rounded-xl bg-slate-900 text-white flex items-center justify-center font-extrabold text-xs shrink-0">
                                    {{ $initials }}
                                </div>
                            @endif
                            <div class="min-w-0">
                                <div class="font-bold text-slate-900 text-xs truncate max-w-[180px]">{{ $officer->fullname }}</div>
                                <div class="text-[11px] text-slate-600 font-mono-code">{{ $officer->service_id ?? 'N/A' }} : {{ '@' . ($officer->username ?? 'staff') }}</div>
                                <div class="text-[11px] font-mono-code text-emerald-800 font-semibold mt-0.5" data-officer-summary="{{ $officer->id }}">
                                    {{ $enabledTotal }}/{{ $permCount }} active
                                </div>
                            </div>
                        </div>
                    </td>

                    <td class="py-4 px-4">
                        <form id="permForm-{{ $officer->id }}"
                              method="POST"
                              action="{{ url('/permissions/' . $officer->id . '/update') }}"
                              onsubmit="return submitOfficerPermissions(event, {{ $officer->id }})">
                            @csrf
                            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-2">
                                @foreach($permissionDefs as $permKey => $permMeta)
                                    @php $checked = !empty($effectivePerms[$permKey]); @endphp
                                    <button type="button"
                                            role="switch"
                                            aria-checked="{{ $checked ? 'true' : 'false' }}"
                                            data-perm-toggle="{{ $officer->id }}"
                                            data-perm-key="{{ $permKey }}"
                                            {{ $canManage ? '' : 'disabled' }}
                                            onclick="toggleOfficerCapability(this, {{ $officer->id }})"
                                            class="w-full text-left flex items-center justify-between gap-2.5 p-2.5 rounded-xl border {{ $checked ? 'border-emerald-300 bg-emerald-50/60' : 'border-slate-200 bg-white' }} {{ $canManage ? 'cursor-pointer hover:border-emerald-400' : 'opacity-70 cursor-not-allowed' }} min-h-[48px] transition-colors">
                                        <input type="checkbox"
                                               name="permissions[{{ $permKey }}]"
                                               value="1"
                                               data-perm-checkbox="{{ $officer->id }}"
                                               {{ $checked ? 'checked' : '' }}
                                               {{ $canManage ? '' : 'disabled' }}
                                               class="sr-only">
                                        <span class="min-w-0">
                                            <span class="block text-[11px] font-bold text-slate-900 leading-tight">{{ $permMeta['label'] }}</span>
                                            <span class="block text-[10px] text-slate-600 leading-snug mt-0.5">{{ $permMeta['category'] }}</span>
                                        </span>
                                        <span data-switch-track
                                              class="relative inline-flex h-5 w-9 shrink-0 rounded-full border-2 border-transparent transition-colors duration-150 {{ $checked ? 'bg-emerald-600' : 'bg-slate-300' }}">
                                            <span data-switch-thumb
                                                  class="pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow-xs transition-transform duration-150 {{ $checked ? 'translate-x-4' : 'translate-x-0' }}"></span>
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </form>
                    </td>

                    <td class="py-4 px-4 text-right whitespace-nowrap">
                        @if($canManage)
                            <div class="inline-flex flex-col items-end gap-2">
                                <button type="submit"
                                        form="permForm-{{ $officer->id }}"
                                        id="saveBtn-{{ $officer->id }}"
                                        class="min-h-[44px] px-4 py-2 rounded-xl bg-slate-900 hover:bg-emerald-700 text-white text-[11px] font-bold font-mono-code shadow-2xs transition-colors">
                                    Save Policy
                                </button>
                                <div class="flex items-center gap-1.5">
                                    <button type="button"
                                            onclick="applyPermissionPreset({{ $officer->id }}, 'grant_all')"
                                            class="min-h-[36px] px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-900 border border-emerald-200 text-[10px] font-bold font-mono-code transition-colors">
                                        Grant All
                                    </button>
                                    <button type="button"
                                            onclick="applyPermissionPreset({{ $officer->id }}, 'restrict_all')"
                                            class="min-h-[36px] px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-900 border border-rose-200 text-[10px] font-bold font-mono-code transition-colors">
                                        Restrict All
                                    </button>
                                </div>
                            </div>
                        @else
                            <span class="inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 border border-slate-200 text-[10px] font-mono-code font-bold uppercase"
                                  title="Only this officer's Head of Unit can modify operational permissions">
                                Unit Head Only
                            </span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="py-12 px-6 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-500 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <div class="text-sm font-bold text-slate-900">No Matching Staff Officers Found</div>
                        <p class="text-xs text-slate-600 mt-1">Adjust unit, policy status, or search terms to configure permissions.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($officers->hasPages())
    <div class="px-5 py-3.5 border-t border-slate-200 bg-slate-50/70 flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="text-xs text-slate-600 font-mono-code">
            Showing <span class="font-bold text-slate-900">{{ $officers->firstItem() ?? 0 }}</span> to <span class="font-bold text-slate-900">{{ $officers->lastItem() ?? 0 }}</span> of <span class="font-bold text-slate-900">{{ $officers->total() }}</span> staff accounts
        </div>
        <div>
            {{ $officers->links() }}
        </div>
    </div>
@endif

