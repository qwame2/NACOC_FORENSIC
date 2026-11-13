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
                const currentPath = window.location ? window.location.pathname : '';
                const url = STATUS_ENDPOINT + (STATUS_ENDPOINT.indexOf('?') === -1 ? '?' : '&') + 'path=' + encodeURIComponent(currentPath);
                const res = await fetch(url, {
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

                if (typeof data.can_create_cases === 'boolean') {
                    applyCreateCasesButtonState(data.can_create_cases);
                }
            } catch (e) {
                // Silent catch during background status watchdog
            } finally {
                isCheckingAccountStatus = false;
            }
        }

        function applyCreateCasesButtonState(canCreate) {
            const lockedMsg = 'Locked: Create New Cases permission is disabled by your Head of Unit';
            const createButtons = document.querySelectorAll('[data-staff-create-btn]');
            createButtons.forEach(function (btn) {
                const targetUrl = btn.getAttribute('data-create-url') || btn.getAttribute('href') || '';
                if (targetUrl && !btn.getAttribute('data-create-url')) {
                    btn.setAttribute('data-create-url', targetUrl);
                }

                const lockBadge = btn.querySelector('[data-lock-badge]');

                if (!canCreate) {
                    btn.removeAttribute('href');
                    btn.setAttribute('aria-disabled', 'true');
                    btn.setAttribute('tabindex', '0');
                    btn.setAttribute('title', lockedMsg);
                    btn.setAttribute('data-locked-tooltip', lockedMsg);
                    btn.style.pointerEvents = '';
                    btn.style.opacity = '0.55';
                    btn.style.cursor = 'not-allowed';
                    btn.style.filter = 'grayscale(100%)';
                    if (lockBadge) lockBadge.classList.remove('hidden');
                } else {
                    if (targetUrl) {
                        btn.setAttribute('href', targetUrl);
                    }
                    btn.setAttribute('aria-disabled', 'false');
                    btn.removeAttribute('tabindex');
                    btn.removeAttribute('title');
                    btn.removeAttribute('data-locked-tooltip');
                    btn.style.pointerEvents = '';
                    btn.style.opacity = '';
                    btn.style.cursor = '';
                    btn.style.filter = '';
                    if (lockBadge) lockBadge.classList.add('hidden');
                }
            });

            if (!canCreate && /\/(staff\/)?create(\b|\/|$)/i.test(window.location.pathname)) {
                window.location.reload();
            }
        }

        // Global custom tooltip for locked/disabled buttons across staff and command views
        function initLockedButtonsTooltip() {
            if (document.getElementById('nacocLockedBtnTooltip')) return;

            const tip = document.createElement('div');
            tip.id = 'nacocLockedBtnTooltip';
            tip.setAttribute('role', 'tooltip');
            tip.className = 'hidden fixed z-[999990] max-w-xs px-3 py-2 rounded-xl bg-slate-900 text-white text-[11px] font-semibold leading-snug shadow-xl border border-slate-700 pointer-events-none flex items-center gap-2';
            tip.innerHTML = '<svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg><span id="nacocLockedBtnTooltipText"></span>';
            document.body.appendChild(tip);

            function findLockedTarget(el) {
                if (!el || !el.closest) return null;
                return el.closest('[data-locked-tooltip], [aria-disabled="true"][title], button:disabled[title]');
            }

            function showTipFor(target) {
                const msg = target.getAttribute('data-locked-tooltip') || target.getAttribute('title');
                if (!msg) return;
                const textEl = document.getElementById('nacocLockedBtnTooltipText');
                if (textEl) textEl.textContent = msg;

                tip.classList.remove('hidden');
                const rect = target.getBoundingClientRect();
                const tipRect = tip.getBoundingClientRect();

                let top = rect.top - tipRect.height - 8;
                if (top < 8) {
                    top = rect.bottom + 8;
                }
                let left = rect.left + (rect.width / 2) - (tipRect.width / 2);
                left = Math.max(8, Math.min(left, window.innerWidth - tipRect.width - 8));

                tip.style.top = Math.round(top) + 'px';
                tip.style.left = Math.round(left) + 'px';
            }

            function hideTip() {
                tip.classList.add('hidden');
            }

            document.addEventListener('mouseover', function (e) {
                const target = findLockedTarget(e.target);
                if (target) {
                    showTipFor(target);
                } else {
                    hideTip();
                }
            });

            document.addEventListener('focusin', function (e) {
                const target = findLockedTarget(e.target);
                if (target) showTipFor(target);
            });

            document.addEventListener('focusout', hideTip);
            document.addEventListener('scroll', hideTip, true);

            document.addEventListener('click', function (e) {
                const target = findLockedTarget(e.target);
                if (target && (target.getAttribute('aria-disabled') === 'true' || target.hasAttribute('data-locked-tooltip'))) {
                    e.preventDefault();
                    e.stopPropagation();
                    showTipFor(target);
                }
            }, true);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initLockedButtonsTooltip);
        } else {
            initLockedButtonsTooltip();
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

