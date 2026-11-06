@php
    $users = $users ?? collect();
@endphp
<!-- TABLE CONTENT FETCHED DYNAMICALLY FROM DATABASE -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs" id="usersTable">
                            <thead>
                                <tr class="bg-slate-200/90 border-b border-slate-300 text-slate-800 font-mono-code uppercase text-[11px] tracking-wider select-none">
                                    <th class="py-3.5 px-5 font-extrabold">User Officer &amp; Service ID #</th>
                                    <th class="py-3.5 px-5 font-extrabold">Account Role</th>
                                    <th class="py-3.5 px-5 font-extrabold">Assigned Unit</th>
                                    <th class="py-3.5 px-5 font-extrabold">Phone &amp; Username</th>
                                    <th class="py-3.5 px-5 font-extrabold">Registration Date</th>
                                    <th class="py-3.5 px-5 font-extrabold">Account Status</th>
                                    <th class="py-3.5 px-5 font-extrabold text-right">Head Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200" id="usersTableBody">
                                
                                @forelse($users ?? [] as $user)
                                    @php
                                        $nameParts = preg_split('/\s+/', trim($user->fullname ?? 'Officer'));
                                        $initials = count($nameParts) >= 2 
                                            ? strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1))
                                            : strtoupper(substr($user->fullname ?? 'OF', 0, 2));

                                        $unitColors = [
                                            'Evidence Management' => 'bg-indigo-600',
                                            'Digital & Physical Forensics' => 'bg-amber-600',
                                            'Crime Scene Support' => 'bg-emerald-600',
                                            'LAB Analysis' => 'bg-emerald-600',
                                            'Forensic Directorate' => 'bg-slate-900',
                                        ];
                                        $avatarBg = $unitColors[$user->unit] ?? 'bg-emerald-600';

                                        $isForensicHeadUser = $user->is_head && (
                                            $user->unit === 'Forensic Directorate' ||
                                            $user->unit === 'Forensic Head' ||
                                            $user->unit === 'Directorate Head' ||
                                            str_contains($user->unit, 'Forensic Directorate')
                                        );
                                    @endphp

                                    <tr class="table-row-hover transition-colors user-row" 
                                        data-status="{{ $user->is_approved ? 'ACTIVE' : 'PENDING' }}" 
                                        data-role="{{ $user->is_head ? 'HEAD' : 'STAFF' }}" 
                                        data-unit="{{ $user->unit }}">
                                        
                                        <td class="py-4 px-5 whitespace-nowrap">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-10 h-10 rounded-xl {{ $avatarBg }} text-white font-extrabold flex items-center justify-center text-xs shadow-sm">
                                                    {{ $initials }}
                                                </div>
                                                <div>
                                                    <div class="font-black text-slate-900 text-sm">{{ $user->fullname }}</div>
                                                    <div class="font-mono-code text-[11px] text-blue-600 font-bold">SVC ID: {{ $user->service_id }}</div>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="py-4 px-5 whitespace-nowrap">
                                            @if($isForensicHeadUser)
                                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold font-mono-code bg-indigo-100 text-indigo-800 border border-indigo-200 flex items-center gap-1.5 w-fit">
                                                    <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                                    DIRECTORATE HEAD
                                                </span>
                                            @elseif($user->is_head)
                                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold font-mono-code bg-indigo-100 text-indigo-800 border border-indigo-200 flex items-center gap-1.5 w-fit">
                                                    <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                                    UNIT HEAD (COMMAND)
                                                </span>
                                            @else
                                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold font-mono-code bg-slate-100 text-slate-700 border border-slate-300 flex items-center gap-1.5 w-fit">
                                                    <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                    STAFF OFFICER
                                                </span>
                                            @endif
                                        </td>

                                        <td class="py-4 px-5">
                                            <div class="font-extrabold text-slate-900 text-xs">{{ $user->unit }}</div>
                                            <div class="text-[10px] text-slate-500 font-medium">NACOC Directorate Unit</div>
                                        </td>

                                        <td class="py-4 px-5 font-mono-code">
                                            <div class="font-bold text-slate-900 text-xs">{{ $user->phone_number }}</div>
                                            <div class="text-[10px] text-slate-500">@ {{ $user->username }}</div>
                                        </td>

                                        <td class="py-4 px-5 whitespace-nowrap font-mono-code text-[11px] text-slate-600">
                                            {{ $user->created_at ? $user->created_at->format('Y-m-d H:i') : '2026-09-08 00:00' }}
                                        </td>

                                        <td class="py-4 px-5 whitespace-nowrap">
                                            @if($user->is_approved)
                                                <span class="status-badge status-active" id="status-{{ $user->id }}">
                                                    <svg class="w-3 h-3 mr-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    Active &amp; Authorized
                                                </span>
                                            @else
                                                <span class="status-badge status-pending" id="status-{{ $user->id }}">
                                                    Pending Approval
                                                </span>
                                            @endif
                                        </td>

                                        <td class="py-4 px-5 text-right whitespace-nowrap space-x-1.5" id="actions-{{ $user->id }}">
                                            <button onclick="inspectUser('{{ $user->id }}', '{{ addslashes($user->fullname) }}', '{{ $user->is_head ? 'UNIT HEAD (COMMAND)' : 'STAFF OFFICER' }}', '{{ addslashes($user->unit) }}', '{{ $user->phone_number }}', '{{ $user->username }}', '{{ $user->created_at ? $user->created_at->format('Y-m-d H:i') : '' }}', '{{ $user->is_head ? 'Level 4 Security Clearance' : 'Level 2 Security Clearance' }}', '{{ $user->is_approved ? 'ACTIVE' : 'PENDING' }}')" class="px-3 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-bold rounded-lg text-xs border border-emerald-200 transition-all cursor-pointer">Inspect</button>
                                            
                                            @if(!$isForensicHeadUser)
                                                @if($user->is_approved)
                                                    <button onclick="deactivateUser('{{ $user->id }}', '{{ addslashes($user->fullname) }}')" class="px-3 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold rounded-lg text-xs border border-slate-300 transition-all cursor-pointer">Deactivate</button>
                                                @else
                                                    <button onclick="activateUser('{{ $user->id }}', '{{ addslashes($user->fullname) }}')" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs border-none transition-all shadow-xs cursor-pointer">Activate</button>
                                                @endif
                                            @endif
                                        </td>

                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-12 px-6 text-center bg-slate-50">
                                            <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                            <div class="font-bold text-slate-800 text-sm">No Database User Records Found</div>
                                            <div class="text-xs text-slate-500 mt-1">When officers register, their accounts will automatically be stored in the database and appear here.</div>
                                        </td>
                                    </tr>
                                @endforelse

                            </tbody>
                        </table>
                    </div>

                    @if(method_exists($users, 'links'))
                        <div class="border-t border-slate-200">
                            {{ $users->links() }}
                        </div>
                    @endif