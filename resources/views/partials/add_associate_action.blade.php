@php
    $variant = $variant ?? 'menu';
    $hasUnitRouteSegment = request()->is('Crime_Scene_Support/*')
        || request()->is('Lab_Analysis/*')
        || request()->is('Evidence_Management/*')
        || request()->is('Digital_Forensics/*');
    $permContext = $hasUnitRouteSegment && !empty($unitLabel)
        ? $unitLabel
        : (request()->input('from') ?: ($unitLabel ?? null));
    $canAddAssociate = !auth()->user()
        || auth()->user()->is_head
        || (method_exists(auth()->user(), 'hasPermission') ? auth()->user()->hasPermission('add_associate', $permContext) : true);
    $associateFrom = $associateFrom ?? ($sidebarUnit ?? '');
    $associateUrl = '/cases/' . $case->case_number . '/associate' . (!empty($associateFrom) ? '?from=' . urlencode($associateFrom) : '');
    $associateLockedTooltip = 'Locked: Add Associate permission is disabled by your Head of Unit';
@endphp
@if ($variant === 'button')
    @php
        $enabledClasses = 'bg-blue-50 hover:bg-blue-100 text-blue-700 border-blue-200 cursor-pointer';
        $disabledClasses = 'bg-slate-200 text-slate-400 border-slate-300 select-none';
    @endphp
    <a @if ($canAddAssociate) href="{{ $associateUrl }}" @endif
        data-staff-associate-btn="button"
        data-associate-url="{{ $associateUrl }}"
        data-enabled-class="{{ $enabledClasses }}"
        data-disabled-class="{{ $disabledClasses }}"
        aria-disabled="{{ $canAddAssociate ? 'false' : 'true' }}"
        @if (!$canAddAssociate)
            tabindex="0"
            title="{{ $associateLockedTooltip }}"
            data-locked-tooltip="{{ $associateLockedTooltip }}"
            onclick="event.preventDefault(); event.stopPropagation(); return false;"
            style="opacity: 0.55; cursor: not-allowed; filter: grayscale(100%);"
        @endif
        class="inline-flex items-center gap-1.5 px-3.5 py-2 border font-bold text-xs rounded-xl transition-colors no-underline {{ $canAddAssociate ? $enabledClasses : $disabledClasses }}">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
        </svg>
        <span>Add Associate</span>
        <svg data-lock-badge class="w-3.5 h-3.5 text-slate-400 shrink-0 {{ $canAddAssociate ? 'hidden' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
    </a>
@else
    @php
        $useEmeraldTheme = ($menuTheme ?? '') === 'emerald';
        $enabledClasses = $useEmeraldTheme
            ? 'text-slate-800 hover:bg-emerald-50 hover:text-emerald-700'
            : 'text-slate-800 hover:bg-blue-50 hover:text-blue-700';
        $disabledClasses = 'text-slate-400 bg-slate-100 select-none';
        $iconBadgeClasses = $useEmeraldTheme
            ? 'bg-emerald-100 text-emerald-700 group-hover:bg-emerald-600'
            : 'bg-blue-100 text-blue-700 group-hover:bg-blue-600';
    @endphp
    <a @if ($canAddAssociate) href="{{ $associateUrl }}" @endif
        data-staff-associate-btn="menu"
        data-associate-url="{{ $associateUrl }}"
        data-enabled-class="{{ $enabledClasses }}"
        data-disabled-class="{{ $disabledClasses }}"
        aria-disabled="{{ $canAddAssociate ? 'false' : 'true' }}"
        @if (!$canAddAssociate)
            tabindex="0"
            title="{{ $associateLockedTooltip }}"
            data-locked-tooltip="{{ $associateLockedTooltip }}"
            onclick="event.preventDefault(); event.stopPropagation(); return false;"
            style="opacity: 0.55; cursor: not-allowed; filter: grayscale(100%);"
        @endif
        class="w-full text-left p-2.5 text-xs font-bold rounded-xl flex items-center space-x-2.5 transition-colors group no-underline {{ $canAddAssociate ? $enabledClasses : $disabledClasses }}">
        <div class="w-7 h-7 rounded-lg {{ $iconBadgeClasses }} flex items-center justify-center shrink-0 group-hover:text-white transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
        </div>
        <div class="min-w-0 flex-1">
            <div class="font-extrabold leading-tight">Add Associate</div>
            <div class="text-[10px] text-slate-400 font-normal mt-0.5">Link accomplices &amp; exhibits</div>
        </div>
        <svg data-lock-badge class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-auto {{ $canAddAssociate ? 'hidden' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
    </a>
@endif

