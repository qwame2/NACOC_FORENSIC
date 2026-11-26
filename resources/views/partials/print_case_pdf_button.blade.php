@php
    $cStatus = strtolower(trim((string)($case->status ?? '')));
    $cCaseStatus = strtolower(trim((string)($case->case_status ?? '')));
    $isCasePendingApproval = method_exists($case, 'isPendingApproval')
        ? $case->isPendingApproval()
        : (in_array($cStatus, ['pending approval', 'awaiting approval', 'pending review', 'awaiting sign-off', 'pending'], true)
            || in_array($cCaseStatus, ['pending approval', 'awaiting approval', 'pending review', 'awaiting sign-off'], true));

    if (!$isCasePendingApproval && !empty($case->case_number)) {
        $isCasePendingApproval = \App\Models\StaffCaseSubmission::where('case_number_preview', $case->case_number)
            ->whereIn('status', ['Pending Approval', 'Awaiting Approval', 'Pending Review'])
            ->exists();
    }

    $pdfUrl = $pdfUrl ?? ('/cases/' . ($case->case_number ?? '') . '/pdf');
    $canPrintPdf = !$isCasePendingApproval;
    $printLockedTooltip = 'Locked: This case is currently awaiting review and approval from the Head of Unit. Printing Case PDF is disabled until approved.';
    $extraClasses = $extraClasses ?? '';
@endphp

@if ($canPrintPdf)
    <a href="{{ $pdfUrl }}" target="_blank"
        class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-xl border border-slate-700 transition-all flex items-center gap-2 cursor-pointer no-underline {{ $extraClasses }}">
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
        </svg>
        <span>Print Case PDF</span>
    </a>
@else
    <a aria-disabled="true"
        tabindex="0"
        title="{{ $printLockedTooltip }}"
        data-locked-tooltip="{{ $printLockedTooltip }}"
        onclick="event.preventDefault(); event.stopPropagation(); return false;"
        style="opacity: 0.55; cursor: not-allowed; filter: grayscale(100%);"
        class="px-4 py-2.5 bg-slate-200 text-slate-400 border border-slate-300 rounded-xl text-xs font-bold transition-all flex items-center gap-2 select-none no-underline {{ $extraClasses }}">
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
        </svg>
        <span>Print Case PDF</span>
        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
    </a>
@endif

