<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-slate-50/90 border-b border-slate-200/80 text-[10.5px] font-mono-code font-bold uppercase tracking-wider text-slate-500">
                <th class="py-3.5 px-5">Staff Officer</th>
                <th class="py-3.5 px-4">Service ID &amp; Unit</th>
                <th class="py-3.5 px-4">Registered Gmail (OTP Target)</th>
                <th class="py-3.5 px-4">Requested</th>
                <th class="py-3.5 px-4">Approval &amp; OTP Status</th>
                <th class="py-3.5 px-5 text-right">Head of Unit Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-xs">
            @forelse ($requests as $req)
                @php
                    $nameParts = preg_split('/\s+/', trim((string) $req->fullname)) ?: [];
                    $initials = count($nameParts) >= 2
                        ? strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1))
                        : strtoupper(substr((string) $req->fullname, 0, 2));

                    $isPending = $req->status === 'pending';
                    $isApproved = $req->status === 'approved';
                    $isCompleted = $req->status === 'completed';
                    $isDeclined = $req->status === 'declined';

                    $otpWindowActive = $isApproved
                        && $req->otp_sent_at
                        && $req->otp_sent_at->copy()->addMinutes(\App\Http\Controllers\PasswordResetController::OTP_EXPIRY_MINUTES)->isFuture();
                @endphp
                <tr class="transition-colors {{ $isPending ? 'bg-amber-50/35 hover:bg-amber-50/60' : 'hover:bg-slate-50/80' }}" data-request-row="{{ $req->id }}">
                    <!-- STAFF OFFICER -->
                    <td class="py-4 px-5 align-middle">
                        <div class="flex items-center gap-3 min-w-[180px]">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center font-extrabold text-xs shrink-0 {{ $isPending ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200/70' }}">
                                {{ $initials ?: 'OF' }}
                            </div>
                            <div class="min-w-0">
                                <div class="font-extrabold text-slate-900 text-xs truncate">
                                    {{ $req->fullname }}
                                </div>
                                <div class="text-[11px] text-slate-500 font-mono-code truncate">
                                    {{ $req->username ? '@' . $req->username : 'Staff Account' }}
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- SERVICE ID & UNIT -->
                    <td class="py-4 px-4 align-middle">
                        <div class="space-y-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 border border-slate-200/90 font-mono-code text-[11px] font-bold text-slate-800">
                                {{ $req->service_id ?: 'N/A' }}
                            </span>
                            <div class="text-[11px] font-semibold text-slate-600">
                                {{ $req->unit }}
                            </div>
                        </div>
                    </td>

                    <!-- REGISTERED GMAIL -->
                    <td class="py-4 px-4 align-middle">
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-teal-50/70 border border-teal-200/80 text-teal-900 font-mono-code text-[11px] font-semibold max-w-[240px]">
                            <svg class="w-3.5 h-3.5 text-teal-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span class="truncate" title="{{ $req->email }}">{{ $req->email }}</span>
                        </div>
                        @if ($req->phone_number)
                            <div class="text-[10.5px] font-mono-code text-slate-400 mt-1">
                                Tel: {{ $req->phone_number }}
                            </div>
                        @endif
                    </td>

                    <!-- REQUESTED TIMESTAMP -->
                    <td class="py-4 px-4 align-middle whitespace-nowrap">
                        <div class="font-bold text-slate-800 text-xs">
                            {{ optional($req->updated_at ?? $req->created_at)->diffForHumans() ?? 'Just now' }}
                        </div>
                        <div class="text-[10.5px] font-mono-code text-slate-500 mt-0.5">
                            {{ optional($req->updated_at ?? $req->created_at)->format('d M Y, H:i') }}
                        </div>
                        @if ($req->ip_address)
                            <div class="text-[10px] font-mono-code text-slate-400">
                                IP: {{ $req->ip_address }}
                            </div>
                        @endif
                    </td>

                    <!-- STATUS -->
                    <td class="py-4 px-4 align-middle">
                        @if ($isPending)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-amber-100/90 border border-amber-300 text-amber-900 font-mono-code text-[10.5px] font-extrabold">
                                <span>Pending Approval</span>
                            </span>
                            <div class="text-[10.5px] text-slate-500 mt-1">
                                OTP held until approved
                            </div>
                        @elseif ($isApproved)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full {{ $otpWindowActive ? 'bg-teal-100/90 border-teal-300 text-teal-900' : 'bg-slate-100 border-slate-300 text-slate-700' }} border font-mono-code text-[10.5px] font-extrabold">
                                <span>{{ $otpWindowActive ? 'Approved • OTP Active' : 'Approved • OTP Expired' }}</span>
                            </span>
                            <div class="text-[10.5px] text-slate-500 mt-1">
                                By {{ $req->reviewed_by_name ?: 'Unit Head' }}
                                @if ($req->otp_sent_at)
                                    &bull; {{ $req->otp_sent_at->diffForHumans() }}
                                @endif
                            </div>
                        @elseif ($isCompleted)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-100/90 border border-emerald-300 text-emerald-900 font-mono-code text-[10.5px] font-extrabold">
                                <svg class="w-3 h-3 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" d="M5 13l4 4L19 7"/></svg>
                                <span>Password Reset Done</span>
                            </span>
                            <div class="text-[10.5px] text-slate-500 mt-1">
                                {{ $req->completed_at ? $req->completed_at->diffForHumans() : 'Completed' }}
                            </div>
                        @elseif ($isDeclined)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-rose-100/90 border border-rose-300 text-rose-900 font-mono-code text-[10.5px] font-extrabold">
                                <span>Declined</span>
                            </span>
                            <div class="text-[10.5px] text-slate-500 mt-1">
                                By {{ $req->reviewed_by_name ?: 'Unit Head' }}
                            </div>
                        @endif
                    </td>

                    <!-- UNIT HEAD ACTIONS -->
                    <td class="py-4 px-5 align-middle text-right whitespace-nowrap">
                        @if ($isPending)
                            <div class="inline-flex items-center justify-end gap-2">
                                <button type="button"
                                    onclick="handlePasswordRequestAction({{ $req->id }}, 'approve', this)"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs shadow-emerald-600/20 transition-all">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Approve &amp; Send OTP</span>
                                </button>
                                <button type="button"
                                    onclick="handlePasswordRequestAction({{ $req->id }}, 'decline', this)"
                                    class="inline-flex items-center gap-1 px-3 py-2 rounded-xl bg-white hover:bg-rose-50 text-slate-700 hover:text-rose-700 border border-slate-200 hover:border-rose-300 font-bold text-xs transition-all">
                                    <span>Decline</span>
                                </button>
                            </div>
                        @elseif ($isApproved)
                            <div class="inline-flex items-center justify-end gap-2">
                                <button type="button"
                                    onclick="handlePasswordRequestAction({{ $req->id }}, 'approve', this)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-teal-50 hover:bg-teal-100 text-teal-800 border border-teal-200 font-bold text-xs transition-all"
                                    title="Send a fresh 5-minute 6-digit OTP code to {{ $req->email }}">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    <span>Resend OTP</span>
                                </button>
                                <button type="button"
                                    onclick="handlePasswordRequestAction({{ $req->id }}, 'decline', this)"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-white hover:bg-rose-50 text-slate-500 hover:text-rose-700 border border-slate-200 hover:border-rose-300 font-semibold text-[11px] transition-all">
                                    <span>Revoke</span>
                                </button>
                            </div>
                        @else
                            <span class="text-[11px] font-mono-code text-slate-400">
                                Closed
                            </span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-14 px-6 text-center">
                        <div class="max-w-sm mx-auto space-y-2">
                            <div class="w-11 h-11 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                            </div>
                            <div class="text-sm font-extrabold text-slate-800">
                                No staff password reset requests found
                            </div>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                When a staff officer in your unit requests a password reset on the FIMS sign-in portal, it will appear here immediately for your approval before their 6-digit Gmail OTP is sent.
                            </p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($requests->hasPages())
    <div class="px-5 py-3.5 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between gap-4 flex-wrap text-xs">
        <div class="text-slate-500 font-medium">
            Showing <strong class="text-slate-800">{{ $requests->firstItem() }}</strong> to <strong class="text-slate-800">{{ $requests->lastItem() }}</strong> of <strong class="text-slate-800">{{ $requests->total() }}</strong> requests
        </div>
        <div>
            {{ $requests->links() }}
        </div>
    </div>
@endif

