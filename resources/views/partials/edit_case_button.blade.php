@php
    $hasUnitRouteSegment = request()->is('Crime_Scene_Support/*')
        || request()->is('Lab_Analysis/*')
        || request()->is('Evidence_Management/*')
        || request()->is('Digital_Forensics/*')
        || request()->is('Forensic_Head/*');
    $permContext = $hasUnitRouteSegment && !empty($unitLabel)
        ? $unitLabel
        : (request()->input('from') ?: ($case->unit_name ?: null));
    $canEditCases = !auth()->user()
        || auth()->user()->is_head
        || (method_exists(auth()->user(), 'hasPermission') ? auth()->user()->hasPermission('edit_cases', $permContext) : true);
    $editCaseUrl = '/cases/' . $case->case_number . '/edit' . (!empty($editFrom) ? '?from=' . urlencode($editFrom) : '');
    $editLockedTooltip = 'Locked: Edit Case & Suspects permission is disabled by your Head of Unit';
    $enabledClasses = 'bg-amber-600 hover:bg-amber-500 text-white shadow-md cursor-pointer';
    $disabledClasses = 'bg-slate-200 text-slate-400 border border-slate-300 select-none';
@endphp
<a @if ($canEditCases) href="{{ $editCaseUrl }}" @endif
    data-staff-edit-btn="review"
    data-edit-url="{{ $editCaseUrl }}"
    data-enabled-class="{{ $enabledClasses }}"
    data-disabled-class="{{ $disabledClasses }}"
    aria-disabled="{{ $canEditCases ? 'false' : 'true' }}"
    @if (!$canEditCases)
        tabindex="0"
        title="{{ $editLockedTooltip }}"
        data-locked-tooltip="{{ $editLockedTooltip }}"
        onclick="event.preventDefault(); event.stopPropagation(); return false;"
        style="opacity: 0.55; cursor: not-allowed; filter: grayscale(100%);"
    @endif
    class="px-4 py-2.5 text-xs font-extrabold rounded-xl transition-all flex items-center gap-2 no-underline {{ $canEditCases ? $enabledClasses : $disabledClasses }}">
    <svg class="w-4 h-4 shrink-0 text-amber-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
    </svg>
    <span>Edit Case/Update Case Records</span>
    <svg data-lock-badge class="w-3.5 h-3.5 text-slate-400 shrink-0 {{ $canEditCases ? 'hidden' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
    </svg>
</a>
