@php
    $revokedUser = $revokedUser ?? auth()->user();
    $isInitiallyRevoked = $isInitiallyRevoked ?? ($revokedUser && !$revokedUser->is_head && !$revokedUser->is_approved);
    $resolvedUnit = $revokedUser ? \App\Enums\ForensicUnit::resolve($revokedUser->unit) : null;
    $unitLabel = $resolvedUnit?->value ?? ($revokedUser->unit ?? 'Operational Unit');
    $unitHead = $resolvedUnit
        ? \App\Models\User::where('is_head', true)
            ->where('is_approved', true)
            ->get()
            ->first(fn ($u) => \App\Enums\ForensicUnit::resolve($u->unit) === $resolvedUnit)
        : null;
@endphp

<!-- ACCOUNT REVOKED FULL-SCREEN BLURRED BACKDROP POPOVER OVERLAY -->
<div id="staffAccountRevokedOverlay"
     role="dialog"
     aria-modal="true"
     aria-labelledby="staffRevokedModalTitle"
     data-revoked="{{ $isInitiallyRevoked ? '1' : '0' }}"
     class="{{ $isInitiallyRevoked ? 'flex' : 'hidden' }} fixed inset-0 z-[999999] items-center justify-center p-4 sm:p-6 bg-slate-900/60 backdrop-blur-md select-none"
     style="backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);">
    <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl border border-rose-200 overflow-hidden">
        <!-- HEADER BANNER -->
        <div class="px-6 py-5 bg-gradient-to-r from-rose-700 via-rose-600 to-red-700 text-white flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-white/15 border border-white/25 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <div>
                <div class="text-[10px] font-mono-code font-extrabold uppercase tracking-widest text-rose-100">
                    Access Suspended &bull; Account Revoked
                </div>
                <h2 id="staffRevokedModalTitle" class="text-base font-extrabold tracking-tight mt-0.5">
                    Staff Account Disabled
                </h2>
            </div>
        </div>

        <!-- BODY -->
        <div class="p-6 space-y-4 text-slate-700">
            <p class="text-xs leading-relaxed font-medium text-slate-700">
                Your staff officer account (<span class="font-mono-code font-bold text-slate-900">{{ $revokedUser->service_id ?? $revokedUser->username ?? 'Staff Account' }}</span>)
                for <strong class="text-slate-900" id="revokedUnitName">{{ $unitLabel }}</strong> has been <strong class="text-rose-700">revoked</strong>.
                All workspace pages and system actions are disabled until your account is reactivated.
            </p>

            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 space-y-1.5">
                <div class="text-[10px] font-mono-code font-extrabold uppercase tracking-wider text-amber-800">
                    Action Required to Restore Access
                </div>
                <p class="text-xs text-amber-950 font-semibold leading-snug" id="revokedUnitHeadInstruction">
                    Please contact your Head of Unit
                    @if($unitHead)
                        (<span class="font-bold">{{ $unitHead->fullname }}</span> &mdash; Head of {{ $unitLabel }})
                    @else
                        (Head of {{ $unitLabel }})
                    @endif
                    to reactivate your account.
                </p>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-slate-100 gap-3">
                <div class="inline-flex items-center gap-2 text-[11px] font-mono-code font-semibold text-slate-500">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    <span>Awaiting unit head reactivation...</span>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold font-mono-code transition-colors cursor-pointer border-none">
                        Sign Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const OVERLAY_ID = 'staffAccountRevokedOverlay';
        const STATUS_ENDPOINT = '{{ url('/api/auth/account-status') }}';
        let isCurrentlyRevoked = {{ $isInitiallyRevoked ? 'true' : 'false' }};
        let isCheckingAccountStatus = false;

        function setUnderlyingPageDisabled(disabled) {
            if (!document.body) return;

            document.body.style.overflow = disabled ? 'hidden' : '';

            Array.from(document.body.children).forEach(function (child) {
                if (!child || child.id === OVERLAY_ID || child.tagName === 'SCRIPT' || child.tagName === 'STYLE') {
                    return;
                }
                if (disabled) {
                    child.setAttribute('aria-hidden', 'true');
                    child.setAttribute('inert', '');
                    child.dataset.revokedPrevPointer = child.style.pointerEvents || '';
                    child.style.pointerEvents = 'none';
                } else {
                    child.removeAttribute('aria-hidden');
                    child.removeAttribute('inert');
                    child.style.pointerEvents = child.dataset.revokedPrevPointer || '';
                }
            });
        }

        function applyRevokedOverlayState(revoked, payload) {
            const overlay = document.getElementById(OVERLAY_ID);
            if (!overlay) return;

            if (revoked) {
                if (payload && payload.unit) {
                    const unitEl = document.getElementById('revokedUnitName');
                    const instrEl = document.getElementById('revokedUnitHeadInstruction');
                    if (unitEl) unitEl.textContent = payload.unit;
                    if (instrEl) {
                        instrEl.textContent = payload.unit_head_name
                            ? `Please contact your Head of Unit (${payload.unit_head_name} — Head of ${payload.unit}) to reactivate your account.`
                            : `Please contact your Head of Unit (Head of ${payload.unit}) to reactivate your account.`;
                    }
                }
                overlay.classList.remove('hidden');
                overlay.classList.add('flex');
                overlay.setAttribute('data-revoked', '1');
                setUnderlyingPageDisabled(true);
            } else {
                overlay.classList.add('hidden');
                overlay.classList.remove('flex');
                overlay.setAttribute('data-revoked', '0');
                setUnderlyingPageDisabled(false);
            }

            isCurrentlyRevoked = revoked;
        }

        async function checkStaffAccountStatus() {
            if (isCheckingAccountStatus) return;
            isCheckingAccountStatus = true;

            try {
                const res = await fetch(STATUS_ENDPOINT, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                });
                if (!res.ok) return;

                const data = await res.json();
                if (!data || data.is_head) return;

                const shouldLock = data.is_approved === false || data.is_revoked === true;
                if (shouldLock && !isCurrentlyRevoked) {
                    applyRevokedOverlayState(true, data);
                } else if (!shouldLock && isCurrentlyRevoked) {
                    applyRevokedOverlayState(false, data);
                }
            } catch (e) {
                // Silent catch during background status watchdog
            } finally {
                isCheckingAccountStatus = false;
            }
        }

        if (isCurrentlyRevoked) {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function () {
                    setUnderlyingPageDisabled(true);
                });
            } else {
                setUnderlyingPageDisabled(true);
            }
        }

        // Fast 3-second polling so mid-session revocations and reactivations take effect immediately
        setInterval(checkStaffAccountStatus, 3000);
        window.addEventListener('focus', checkStaffAccountStatus);
    })();
</script>

