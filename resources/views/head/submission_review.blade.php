@php
    $presenter = ($presenter ?? null) instanceof \App\Services\CaseShowReviewPresenter
        ? $presenter
        : app(\App\Services\CaseShowReviewPresenter::class)->presentForUnit($case, $unitContext['key'] ?? 'css', request(), auth()->user());
    extract($presenter->toViewData(), EXTR_SKIP);

    $unitKey = $unitContext['key'] ?? 'crime_scene_support';
    $reviewsIndexUrl = match ($unitKey) {
        'lab_analysis' => url('/Lab_Analysis/reviews-approvals'),
        'evidence_management' => url('/Evidence_Management/reviews-approvals'),
        'digital_forensics' => url('/Digital_Forensics/reviews-approvals'),
        'forensic_head' => url('/Forensic_Head/reviews-approvals'),
        default => url('/Crime_Scene_Support/reviews-approvals'),
    };

    $statusLower = strtolower($submission->status ?? $case->status ?? 'pending');
    $isRolledBack = in_array($statusLower, ['rolled back', 'rollback requested']);
    $isPending = in_array($statusLower, ['pending', 'pending approval', 'submitted']);
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Staff Submission Review — {{ $case->case_number }} | NACOC Forensic Department</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Tailwind / Vite -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    @endif

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #F8FAFC;
            color: #0F172A;
        }

        .font-mono-code {
            font-family: 'JetBrains Mono', monospace;
        }

        .top-header {
            background: #FFFFFF;
            border-bottom: 1px solid #E2E8F0;
        }

        .btn-primary {
            background: #059669;
            border: 1px solid #10b981;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
            color: white;
            font-weight: 700;
            font-size: 12px;
            padding: 10px 20px;
            border-radius: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            background: #047857;
            box-shadow: 0 6px 18px rgba(16, 185, 129, 0.35);
            transform: translateY(-1px);
        }

        .btn-decline {
            background: #dc2626;
            border: 1px solid #f43f5e;
            box-shadow: 0 4px 12px rgba(225, 29, 72, 0.25);
            color: white;
            font-weight: 700;
            font-size: 12px;
            padding: 10px 20px;
            border-radius: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-decline:hover {
            background: #b91c1c;
            box-shadow: 0 6px 18px rgba(225, 29, 72, 0.35);
            transform: translateY(-1px);
        }

        .btn-rollback {
            background: #d97706;
            border: 1px solid #f59e0b;
            box-shadow: 0 4px 12px rgba(217, 119, 6, 0.25);
            color: white;
            font-weight: 700;
            font-size: 12px;
            padding: 10px 20px;
            border-radius: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-rollback:hover {
            background: #b45309;
            box-shadow: 0 6px 18px rgba(217, 119, 6, 0.35);
            transform: translateY(-1px);
        }

        .rollback-interactive {
            position: relative;
            cursor: pointer !important;
            transition: all 0.2s ease-in-out;
            outline: 2px dashed #10b981 !important;
            outline-offset: 3px;
        }

        .rollback-interactive:hover {
            background-color: #f0fdf4 !important;
            outline-color: #059669 !important;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.18) !important;
        }

        .rollback-selected {
            outline: 2px solid #059669 !important;
            outline-offset: 3px;
            background-color: #f0fdf4 !important;
            box-shadow: 0 6px 18px rgba(5, 150, 105, 0.22) !important;
        }

        .status-rollback {
            background: #FFFBEB;
            color: #B45309;
            border: 1px solid #FCD34D;
        }

        .btn-back {
            background: #FFFFFF;
            border: 1px solid #CBD5E1;
            color: #334155;
            font-weight: 700;
            font-size: 12px;
            padding: 9px 16px;
            border-radius: 10px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-back:hover {
            background: #F1F5F9;
            color: #0F172A;
            border-color: #94A3B8;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
        }

        .status-await {
            background: #FEF3C7;
            color: #D97706;
            border: 1px solid #FCD34D;
        }

        .status-done {
            background: #ECFDF5;
            color: #059669;
            border: 1px solid #A7F3D0;
        }

        .status-declined {
            background: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FCA5A5;
        }

        .dossier-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
            overflow: hidden;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col antialiased">
    @include('partials.flash')

    <div class="flex h-screen overflow-hidden">

        <!-- LEFT SIDEBAR -->
        @include('Crime_Scene_Support.partials.sidebar')

        <!-- MAIN WORKSPACE -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-50">

            <!-- TOP NAVBAR -->
            <header class="top-header h-16 px-6 flex items-center justify-between shrink-0 z-20 gap-4">
                <div class="flex items-center gap-4 shrink-0">
                    <a href="{{ $reviewsIndexUrl }}" class="btn-back">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Reviews &amp; Approvals
                    </a>
                    <div class="h-6 w-px bg-slate-200"></div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-mono-code font-extrabold text-slate-700">{{ $case->case_number }}</span>
                        <span class="text-[10px] font-mono-code font-bold text-amber-800">
                            SUBMISSION #{{ $submission->id }}
                        </span>
                        @if(!empty($isEditSubmission))
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-mono-code font-bold bg-amber-100 text-amber-950 border border-amber-300">
                                <svg class="w-3 h-3 text-amber-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                <span>Edited Case</span>
                            </span>
                        @endif
                    </div>
                </div>

                <!-- SUBMISSION BANNER / SUBMITTER DETAILS -->
                <div class="flex items-center gap-3">
                    <div class="text-right hidden sm:block">
                        <div class="text-xs font-bold text-slate-800">
                            Submitted by: <span class="text-blue-600">{{ $submission->submitted_by_name ?? 'Unit Staff Officer' }}</span>
                        </div>
                        <div class="text-[10px] font-mono-code text-slate-500">
                            {{ $submission->created_at ? $submission->created_at->format('d M Y, H:i') : 'Pending Review' }} &bull; {{ $submission->unit_name }}
                        </div>
                    </div>
                </div>
            </header>

            <!-- SCROLLABLE CONTENT -->
            <main class="flex-1 overflow-y-auto p-6 space-y-6">

                @include('partials.flash')

                <!-- HERO DOSSIER HEADER BANNER (MATCHES SHOW_REVIEW) -->
                <div class="relative overflow-hidden rounded-2xl p-6 sm:p-8 bg-gray-500 bg-grey-500 text-white shadow-xl border border-gray-400">
                    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        <div class="space-y-3 flex-1">
                            <div class="flex items-center gap-3 flex-wrap">
                                <span class="text-[11px] font-bold font-mono-code text-red-400">
                                    {{ \App\Models\ForensicCase::normalizePriority($case->priority ?? 'HIGH') }} PRIORITY
                                </span>
                                <span class="text-gray-300">•</span>
                                <span id="headerStatusBadge">
                                    @if ($submission->status === 'Approved')
                                        <span class="text-emerald-400 font-mono-code font-bold text-xs inline-flex items-center">
                                            <svg class="w-3.5 h-3.5 mr-1 text-emerald-400" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Approved &amp; Saved
                                        </span>
                                    @elseif($submission->status === 'Rolled Back')
                                        <span class="text-amber-400 font-mono-code font-bold text-xs inline-flex items-center">
                                            <svg class="w-3.5 h-3.5 mr-1 text-amber-400" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2.5" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                            </svg>
                                            Rolled Back to Staff
                                        </span>
                                    @elseif($submission->status === 'Declined')
                                        <span class="text-red-400 font-mono-code font-bold text-xs inline-flex items-center">
                                            Declined
                                        </span>
                                    @else
                                        <span class="text-amber-300 font-mono-code font-bold text-xs inline-flex items-center">
                                            Pending Unit Head Review
                                        </span>
                                    @endif
                                </span>
                                @if(!empty($isEditSubmission))
                                    <span class="text-gray-300">•</span>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-[10px] font-mono-code font-extrabold uppercase bg-amber-400/20 text-amber-200 border border-amber-400/40 shadow-xs">
                                        <svg class="w-3 h-3 text-amber-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        <span>Edited Case</span>
                                    </span>
                                @endif
                                @if(!empty($submission->approved_case_number))
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono-code font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                                        Case Docket: {{ $submission->approved_case_number }}
                                    </span>
                                @endif
                            </div>

                            <div>
                                <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight flex items-center gap-3 flex-wrap">
                                    {{ $case->title }}
                                    <span class="text-base text-white font-normal">| {{ $case->case_number }}</span>
                                </h1>
                                <p class="text-xs sm:text-sm text-gray-100 mt-1 max-w-3xl leading-relaxed">
                                    Staff Case Submission awaiting Unit Head review &amp; authorization. Review all suspect bio-data, mugshot captures, and seized exhibits below before approving or declining.
                                </p>
                            </div>
                        </div>

                        <!-- HERO ACTIONS: APPROVE / CASE ROLLBACK DIRECT BUTTONS -->
                        @if($isPending)
                        <div class="flex items-center gap-3 shrink-0 flex-wrap">
                            <button type="button" onclick="startRollbackMode()" id="heroRollbackBtn"
                                class="px-4 py-2.5 bg-amber-600 hover:bg-amber-500 text-white text-xs font-extrabold rounded-xl shadow-md transition-all flex items-center gap-2 cursor-pointer border-none">
                                <svg class="w-4 h-4 text-amber-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                </svg>
                                <span>Case Rollback</span>
                            </button>

                            <button type="button" onclick="openApproveModal()"
                                class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-extrabold rounded-xl shadow-md transition-all flex items-center gap-2 cursor-pointer border-none">
                                <svg class="w-4 h-4 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Approve &amp; Save Case</span>
                            </button>
                        </div>
                        @elseif($isRolledBack)
                        <div class="flex items-center gap-3 shrink-0 flex-wrap">
                            <button type="button" disabled
                                title="Case is currently rolled back to staff. Locked until staff completes corrections and resubmits."
                                class="px-4 py-2.5 bg-slate-700/60 text-slate-400 text-xs font-extrabold rounded-xl shadow-none flex items-center gap-2 cursor-not-allowed border border-slate-600/60 opacity-60">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                <span>Case Rollback</span>
                            </button>

                            <button type="button" disabled
                                title="Case is currently rolled back to staff. Locked until staff completes corrections and resubmits."
                                class="px-5 py-2.5 bg-slate-700/60 text-slate-400 text-xs font-extrabold rounded-xl shadow-none flex items-center gap-2 cursor-not-allowed border border-slate-600/60 opacity-60">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                <span>Approve &amp; Save Case</span>
                            </button>

                            <span class="text-amber-300 font-mono-code font-bold text-xs">
                                Awaiting Staff Corrections
                            </span>
                        </div>
                        @else
                        <div class="flex items-center gap-3 shrink-0">
                            <span class="px-4 py-2 rounded-xl bg-gray-600 text-gray-200 text-xs font-mono-code border border-gray-500">
                                Reviewed by {{ $submission->reviewed_by_name ?? 'Unit Head' }} on {{ $submission->reviewed_at ? $submission->reviewed_at->format('d M Y, H:i') : 'N/A' }}
                            </span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- STICKY ROLLBACK MODE ACTIVE BANNER -->
                <div id="rollbackActiveBar" class="hidden sticky top-3 z-50 p-4 rounded-2xl bg-green-100 text-slate-800 shadow-xl mb-6 transition-all">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-green-200 text-green-800 border border-green-300 flex items-center justify-center font-black shrink-0">
                                <svg class="w-5 h-5 text-green-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-sm font-black tracking-wide text-green-950">CASE ROLLBACK MODE ACTIVE</h3>
                                    <span id="rollbackCountBadge" class="text-xs text-green-900 font-mono-code font-bold">0 Sections Selected</span>
                                </div>
                                <p class="text-xs text-green-900 mt-0.5">
                                    Click on any suspect detail container below to request a correction from staff. You can click and annotate multiple sections.
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2.5 shrink-0">
                            <button type="button" onclick="exitRollbackMode()" class="px-3.5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs transition border border-red-700 shadow-sm cursor-pointer">
                                Cancel Rollback
                            </button>
                            <button type="button" onclick="openRollbackSubmitModal()" class="px-4 py-2 rounded-xl bg-green-700 hover:bg-green-800 text-white font-black text-xs shadow-md transition flex items-center gap-1.5 border border-green-800 cursor-pointer">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                                <span>Send Rollback to Staff</span>
                            </button>
                        </div>
                    </div>
                </div>

                @if($isRolledBack && !empty($submission->rollback_corrections) && is_array($submission->rollback_corrections) && count($submission->rollback_corrections) > 0)
                <!-- EXISTING ROLLBACK CORRECTIONS DISPLAY -->
                <div class="p-5 rounded-2xl bg-green-100 shadow-sm space-y-3 mb-6 border-none">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-green-700 text-white flex items-center justify-center font-bold shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-green-950">Active Case Rollback &mdash; Requested Corrections</h3>
                                <p class="text-xs text-green-800">Returned to {{ $submission->submitted_by_name ?? 'Staff Officer' }} on {{ optional($submission->rolled_back_at)->format('d M Y, H:i') }}</p>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full bg-green-200 text-green-950 font-mono-code font-bold text-xs shrink-0 self-start sm:self-auto border-none">
                            {{ count($submission->rollback_corrections) }} Section Correction{{ count($submission->rollback_corrections) === 1 ? '' : 's' }}
                        </span>
                    </div>
                    @if(!empty($submission->review_notes))
                        <p class="text-xs font-semibold text-green-950 bg-white/90 p-3 rounded-xl border-none">
                            <strong>Unit Head Remarks:</strong> {{ $submission->review_notes }}
                        </p>
                    @endif
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 pt-1">
                        @foreach($submission->rollback_corrections as $corr)
                            <div class="p-2.5 rounded-xl bg-white border-none shadow-2xs text-xs">
                                <div class="font-bold text-green-950 font-mono-code text-[11px] flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-600"></span>
                                    {{ $corr['field'] ?? 'Field Correction' }}
                                </div>
                                <div class="text-slate-700 mt-1 font-sans text-xs">{{ $corr['note'] ?? '' }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                @if(!empty($isEditSubmission))
                <!-- STAFF CASE EDIT SUBMISSION ACTIVE BANNER -->
                <div id="staffEditNoticeBanner" class="p-5 rounded-2xl bg-amber-50 border border-amber-300 shadow-sm space-y-3 mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-amber-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-500 text-slate-950 flex items-center justify-center font-black shadow-xs shrink-0">
                                <svg class="w-5 h-5 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-black text-amber-950 text-sm flex items-center gap-2">
                                    <span>Staff Case Edit Submission &mdash; Review &amp; Approval Required</span>
                                    <span class="font-mono-code text-xs px-2 py-0.5 rounded bg-amber-200 text-amber-950 border border-amber-300">
                                        {{ $submission->case_number_preview ?: ('SUB-' . str_pad((string)$submission->id, 4, '0', STR_PAD_LEFT)) }}
                                    </span>
                                </h3>
                                <p class="text-xs text-amber-900 font-medium">
                                    Submitted by <strong>{{ $submission->submitted_by_name ?? 'Staff Officer' }}</strong> ({{ $submission->unit_name ?? 'Crime Scene Support' }}). 
                                    Review the edited sections highlighted below before approving changes into the official database.
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="px-3 py-1 rounded-full text-xs font-mono-code font-bold bg-amber-200 text-amber-950 border border-amber-300">
                                {{ count($editedFieldLabels ?? []) }} Field{{ count($editedFieldLabels ?? []) === 1 ? '' : 's' }} Edited
                            </span>
                        </div>
                    </div>
                    @if(!empty($editedFieldLabels) && count($editedFieldLabels) > 0)
                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        <span class="text-xs font-bold text-amber-950">Jump to edited section:</span>
                        @foreach($editedFieldLabels as $idx => $label)
                            @php $fKey = $editedFields[$idx] ?? ''; @endphp
                            <button type="button" onclick="scrollToEditedSection('{{ $fKey }}')" class="px-2.5 py-1 rounded-lg text-xs font-mono-code font-bold bg-white text-amber-950 hover:bg-amber-100 border border-amber-300 shadow-2xs transition-all cursor-pointer flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                <span>{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endif

                <!-- WRAPPER FOR ALL DOSSIER CONTENT FOR INTERACTIVE ROLLBACK -->
                <div id="reviewDossierContent" class="space-y-8">

                <!-- SECTION 1: MASTER CASE TITLE & CLASSIFICATION (EXACTLY AS SHOW_REVIEW) -->
                <div class="dossier-card p-6 border border-slate-200" data-field-group="title" data-rollback-target="Case Legal Title">
                    <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-200">
                        <div class="flex items-center gap-3">
                            <div>
                                <h2 class="text-base font-black text-slate-900">Case Title &amp; Classification</h2>
                                <p class="text-xs text-slate-500">Case title, reference, priority, and division assignment</p>
                            </div>
                        </div>
                        @if (!empty($case->unit_name))
                            <span class="px-3 py-1 rounded-lg text-xs font-mono-code font-bold {{ $unitBadgeColor ?? 'bg-sky-50 text-sky-700 border border-sky-200' }}">
                                {{ $case->unit_name }}
                            </span>
                        @endif
                    </div>
                    @php
                        $dispLabNumber = !empty($case->laboratory_number) && $case->laboratory_number !== 'Pending Assignment'
                            ? $case->laboratory_number
                            : (!empty($savedLabNumber) && $savedLabNumber !== 'N/A' && $savedLabNumber !== 'Pending Assignment' ? $savedLabNumber : null);
                    @endphp
                    <div class="grid grid-cols-1 md:grid-cols-{{ !empty($dispLabNumber) ? '4' : '3' }} gap-4 text-xs">
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                            <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Case Name / Legal Title</div>
                            <div class="font-extrabold text-slate-900 text-sm mt-1">{{ $case->title ?: 'N/A' }}</div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200" data-no-rollback="true">
                            <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Case Reference Number</div>
                            <div class="font-extrabold text-blue-600 font-mono-code text-sm mt-1">
                                {{ $case->case_number ?: 'N/A' }}
                            </div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                            <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Operation Type</div>
                            <div class="font-bold text-slate-800 text-sm mt-1">
                                {{ $case->operation_code_name ?: $case->operation_code ?? 'N/A' }}
                            </div>
                        </div>
                        @if (!empty($case->search_warrant_document))
                            <div class="p-3.5 rounded-xl bg-blue-50/70 border border-blue-200">
                                <div class="text-[10px] font-mono-code font-bold text-blue-700 uppercase">Search Warrant Document</div>
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($case->search_warrant_document) }}" target="_blank" rel="noopener"
                                    class="inline-flex items-center space-x-1.5 mt-1 text-xs font-extrabold text-blue-700 hover:text-blue-900 underline">
                                    <span>View / Download Warrant</span>
                                </a>
                            </div>
                        @endif
                        @if (!empty($dispLabNumber))
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Laboratory Number</div>
                                <div class="font-extrabold text-emerald-700 font-mono-code text-sm mt-1">{{ $dispLabNumber }}</div>
                            </div>
                        @endif
                        @if (!empty($case->description_of_exhibits))
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 md:col-span-{{ !empty($dispLabNumber) ? '4' : '3' }}">
                                <div class="text-[10px] font-mono-code font-bold text-slate-500 uppercase">Description of Exhibit(s)</div>
                                <div class="font-bold text-slate-900 text-sm mt-1 leading-relaxed">{{ $case->description_of_exhibits }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- SUSPECTS MUGSHOTS & DOSSIER CARDS (EXACTLY AS SHOW_REVIEW) --}}
                @foreach ($suspectsList as $sIdx => $sItem)
                    @include('partials.suspect-mugshots', [
                        'suspect' => $sItem,
                        'index' => $sIdx,
                    ])

                    @include('partials.suspect-dossier', [
                        'suspect' => $sItem,
                        'case' => $case,
                    ])
                @endforeach

                @php
                    $isMultiple = !empty($isMultipleCase)
                        || (!empty($suspectsList) && count($suspectsList) > 1);

                    $jointDossierHeadingName = !empty($primarySuspectsCombinedNames)
                        ? $primarySuspectsCombinedNames
                        : ($isMultiple ? 'Primary & Co-Suspect' : ($case->suspect_name ?? 'Primary Suspect'));

                    $dossierBadgeText = $isMultiple
                        ? 'PRIMARY & CO-SUSPECT EXHIBIT DOSSIER'
                        : 'PRIMARY EXHIBIT DOSSIER';

                    $dossierHeadingPrefix = $isMultiple
                        ? 'Primary & Co-Suspect Seizures & Exhibits'
                        : 'Primary Suspect Seizures & Exhibits';

                    $dossierHeadingText = !empty($jointDossierHeadingName) && $jointDossierHeadingName !== 'Primary Suspect' && $jointDossierHeadingName !== 'Primary & Co-Suspect'
                        ? "{$dossierHeadingPrefix} — {$jointDossierHeadingName}"
                        : "{$dossierHeadingPrefix}" . (!empty($jointDossierHeadingName) ? " — {$jointDossierHeadingName}" : '');
                @endphp

                {{-- PRIMARY SUSPECT SEIZURES & EXHIBITS (PART OF CASE ROLLBACK MODE) --}}
                @if (!empty($isJointExhibitCase) || !empty($jointCaseExhibits) || !empty($allCaseExhibits))
                    <div class="primary-exhibit-dossier" data-exhibit-dossier="true" data-field-group="seizures" data-rollback-target="Seized Exhibits">
                        @include('partials.exhibit_dossier_card', [
                            'dossierBadgeText' => $dossierBadgeText,
                            'dossierHeadingText' => $dossierHeadingText,
                            'dossierHeadingName' => $jointDossierHeadingName,
                            'dossierExhibits' => $jointCaseExhibits ?? ($allCaseExhibits ?? []),
                            'dossierPhotos' => $allCaseExhibitPhotos ?? [],
                            'formatPhotoUrlHelper' => $formatPhotoUrlHelper,
                            'extractAllPhotosHelper' => $extractAllPhotosHelper,
                            'case' => $case,
                        ])
                    </div>

                    {{-- LABORATORY EXAMINATION RESULT (JOINT OWNERSHIP SECTION ON TOP OF SEIZURES INVENTORY) --}}
                    @if (!empty($savedJointLabExamCards) && count($savedJointLabExamCards) > 0)
                        <div data-field-group="lab_examination" data-rollback-target="Laboratory Examination">
                        @foreach ($savedJointLabExamCards as $labCard)
                            @include('partials.lab-examination-card', [
                                'labExam' => $labCard,
                                'labHeadingName' => $labCard['labHeadingName'] ?? ($savedJointLabHeadingName ?? $jointDossierHeadingName),
                                'case' => $case,
                            ])
                        @endforeach
                        </div>
                    @elseif (!empty($savedJointLabExam) && !empty($savedJointLabExam['has_saved']))
                        <div data-field-group="lab_examination" data-rollback-target="Laboratory Examination">
                        @include('partials.lab-examination-card', [
                            'labExam' => $savedJointLabExamCard ?? $savedJointLabExam,
                            'labHeadingName' => $savedJointLabHeadingName ?? $jointDossierHeadingName,
                            'case' => $case,
                        ])
                        </div>
                    @endif
                @endif

                {{-- ATTACHED REPORT FILES CONTAINER ALWAYS ON TOP OF SEIZURES (COMMODITIES INVENTORY) --}}
                @php
                    $allCaseReportFiles = collect($allGsaForReview ?? [])
                        ->merge(collect($allNlrForReview ?? []))
                        ->merge(collect($jointCaseReportFiles ?? []))
                        ->unique(function ($item) {
                            return !empty($item->file_path) ? (string) $item->file_path : (get_class($item) . '_' . ($item->id ?? spl_object_id($item)));
                        })
                        ->values();
                @endphp
                @if ($allCaseReportFiles->count() > 0)
                    <div data-no-rollback="true" class="no-rollback-section">
                        @include('partials.gsa_report_files', [
                            'gsaFiles' => $allCaseReportFiles,
                            'gsaForName' => null,
                        ])
                    </div>
                @endif

                {{-- MASTER EXHIBITS TABLE: SEIZURES (COMMODITIES INVENTORY) - EXCLUDED FROM ROLLBACK --}}
                <div data-no-rollback="true" class="no-rollback-section">
                    @include('partials.exhibit-table', [
                        'exhibits' => $allCaseExhibits,
                        'defaultOwnerName' => $case->suspect_name ?? 'Primary Suspect',
                        'case' => $case,
                        'suspects' => $suspectsList,
                    ])
                </div>

                </div> <!-- /reviewDossierContent -->

                {{-- REVIEW DECISION CALLOUT / BOTTOM ACTION PANEL --}}
                <div class="dossier-card p-6 border border-slate-200 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-200 gap-4">
                        <div>
                            <span class="text-xs font-mono-code font-extrabold text-emerald-600 uppercase tracking-wider">
                                EXECUTIVE REVIEW &amp; SIGN-OFF
                            </span>
                            <h3 class="text-base font-black text-slate-900 mt-1">Review &amp; Approval Decision</h3>
                            <p class="text-xs text-slate-500">Unit Head review decision for staff submission SUB-{{ str_pad((string)$submission->id, 4, '0', STR_PAD_LEFT) }}</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="text-xs font-mono-code font-bold text-slate-500">Current Status:</span>
                            @if ($submission->status === 'Approved')
                                <span class="text-xs font-mono-code font-bold text-emerald-600">
                                    {{ $submission->status }}
                                </span>
                            @elseif($submission->status === 'Rolled Back')
                                <span class="text-xs font-mono-code font-bold text-amber-600">
                                    {{ $submission->status }}
                                </span>
                            @elseif($submission->status === 'Declined')
                                <span class="text-xs font-mono-code font-bold text-red-600">
                                    {{ $submission->status }}
                                </span>
                            @else
                                <span class="text-xs font-mono-code font-bold text-amber-600">
                                    {{ $submission->status }}
                                </span>
                            @endif
                        </div>
                    </div>



                    @if($isPending)
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-2">
                            <p class="text-xs text-slate-600">
                                When approved, this case will be formally registered in the forensic database and assigned official case number <strong class="text-slate-900 font-mono-code">{{ $case->case_number }}</strong>.
                            </p>
                            <div class="flex items-center gap-3 shrink-0">
                                <button type="button" onclick="startRollbackMode()" class="btn-rollback">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                    </svg>
                                    Case Rollback
                                </button>
                                <button type="button" onclick="openApproveModal()" class="btn-primary">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Approve &amp; Save to Case Database
                                </button>
                            </div>
                        </div>
                    @elseif($isRolledBack)
                        <div class="space-y-3 pt-2">
                            <div class="p-3.5 bg-green-100 border-none rounded-xl text-xs text-green-950 font-medium flex items-center justify-between flex-wrap gap-3 shadow-2xs">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-green-700 text-white flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    </div>
                                    <div>
                                        <div class="font-bold text-green-950">Awaiting Staff Corrections &amp; Resubmission</div>
                                        <div class="text-[11px] text-green-800 mt-0.5">This case docket was returned to <strong>{{ $submission->submitted_by_name ?? 'Staff Officer' }}</strong> on {{ optional($submission->rolled_back_at)->format('d M Y, H:i') }}. Case Rollback and Approval buttons are locked until the staff officer updates the docket and resubmits to your unit.</div>
                                    </div>
                                </div>
                                <a href="{{ $reviewsIndexUrl }}" class="px-3.5 py-1.5 rounded-lg bg-white border-none text-green-900 hover:bg-green-200 font-bold text-xs transition shadow-2xs shrink-0">
                                    &larr; Return to Reviews
                                </a>
                            </div>

                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-1">
                                <p class="text-xs text-slate-400 italic">
                                    Docket actions disabled while case is in staff correction queue.
                                </p>
                                <div class="flex items-center gap-3 shrink-0">
                                    <button type="button" disabled
                                        title="Case is currently rolled back to staff. Locked until staff resubmits."
                                        class="px-4 py-2.5 bg-slate-200 text-slate-400 font-extrabold text-xs rounded-xl cursor-not-allowed flex items-center gap-2 border border-slate-300 shadow-none">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                        Case Rollback
                                    </button>
                                    <button type="button" disabled
                                        title="Case is currently rolled back to staff. Locked until staff resubmits."
                                        class="px-4 py-2.5 bg-slate-200 text-slate-400 font-extrabold text-xs rounded-xl cursor-not-allowed flex items-center gap-2 border border-slate-300 shadow-none">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                        Approve &amp; Save to Case Database
                                    </button>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-900 font-semibold flex items-center justify-between">
                            <span>This submission was finalized by <strong>{{ $submission->reviewed_by_name ?? 'Unit Head' }}</strong> on {{ $submission->reviewed_at ? $submission->reviewed_at->format('d M Y, H:i') : 'N/A' }}.</span>
                            <a href="{{ $reviewsIndexUrl }}" class="text-xs font-bold text-emerald-800 hover:text-emerald-950 underline">Return to Reviews Directory &rarr;</a>
                        </div>
                    @endif
                </div>

            </main>
        </div>
    </div>

    <!-- APPROVE CONFIRMATION MODAL -->
    <div id="approveModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-5 shadow-2xl border border-slate-200">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-base font-black text-slate-900">Approve Staff Submission</h4>
                        <p class="text-xs text-slate-500">Save permanently into Forensic Case Database</p>
                    </div>
                </div>
                <button type="button" onclick="closeApproveModal()" class="text-slate-400 hover:text-slate-600 p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed">
                You are about to approve submission <strong>SUB-{{ str_pad((string)$submission->id, 4, '0', STR_PAD_LEFT) }}</strong> submitted by <strong>{{ $submission->submitted_by_name ?? 'Staff' }}</strong>.
                This will create a new official case record (<strong class="font-mono-code text-blue-600">{{ $case->case_number }}</strong>) with all suspects and seized exhibits.
            </p>

            <form method="POST" action="{{ url($reviewsIndexUrl . '/' . $submission->id . '/approve') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Approval Notes / Remarks (Optional)</label>
                    <textarea name="review_notes" rows="3" placeholder="Enter any instructions, unit directives, or confirmation remarks..."
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"></textarea>
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" onclick="closeApproveModal()" class="btn-back">Cancel</button>
                    <button type="submit" class="btn-primary">Confirm &amp; Save Case</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ROLLBACK SUBMISSION CONFIRMATION MODAL -->
    <div id="rollbackSubmitModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-xl w-full p-6 space-y-5 shadow-2xl border border-slate-200 max-h-[90vh] flex flex-col">
            <div class="flex items-start justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-green-100 text-green-700 flex items-center justify-center font-bold">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-base font-black text-slate-900">Send Case Rollback to Staff</h4>
                        <p class="text-xs text-slate-500">Return case submission to staff officer for corrections</p>
                    </div>
                </div>
                <button type="button" onclick="closeRollbackSubmitModal()" class="text-slate-400 hover:text-slate-600 p-1 border-none bg-transparent cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ url($reviewsIndexUrl . '/' . $submission->id . '/rollback') }}" class="space-y-4 flex-1 flex flex-col min-h-0" id="rollbackSubmitForm">
                @csrf
                <input type="hidden" name="corrections" id="rollbackCorrectionsInput" value="[]">

                <div class="text-xs text-slate-600 leading-relaxed shrink-0">
                    You are about to roll back submission <strong>SUB-{{ str_pad((string)$submission->id, 4, '0', STR_PAD_LEFT) }}</strong> to <strong>{{ $submission->submitted_by_name ?? 'Staff Officer' }}</strong>.
                    Staff will be notified and can view your requested corrections on their <strong>Case Rollback</strong> page to make updates and resubmit.
                </div>

                <!-- SUMMARY OF ANNOTATED CORRECTIONS -->
                <div class="flex-1 overflow-y-auto space-y-2 pr-1 min-h-[120px] max-h-[220px]">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-slate-700">Annotated Section Corrections (<span id="rollbackModalCount">0</span>)</label>
                        <span class="text-[10px] text-amber-700 font-mono-code font-bold">Multiple sections selectable</span>
                    </div>
                    <div id="rollbackModalCorrectionList" class="space-y-2">
                        <!-- Populated dynamically -->
                    </div>
                </div>

                <div class="shrink-0">
                    <label class="block text-xs font-bold text-slate-700 mb-1">Overall Unit Head Directives / Instructions (Optional)</label>
                    <textarea name="review_notes" rows="2.5" placeholder="e.g. Please correct all highlighted suspect bio-data and resubmit before end of day..."
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2 shrink-0 border-t border-slate-100">
                    <button type="button" onclick="closeRollbackSubmitModal()" class="btn-back">Return to Review</button>
                    <button type="submit" class="btn-rollback">Confirm &amp; Send Rollback</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ENLARGED PHOTO LIGHTBOX MODAL -->
    <div id="imageLightboxModal" class="fixed inset-0 z-[99999] bg-slate-900/85 backdrop-blur-md hidden flex items-center justify-center p-4" onclick="closeImageLightbox()">
        <div class="relative max-w-3xl w-full bg-white rounded-3xl overflow-hidden shadow-2xl border border-slate-200" onclick="event.stopPropagation()">
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <span id="imageLightboxTitle" class="font-extrabold text-xs uppercase tracking-wider font-mono-code">Biometric Inspection</span>
                <button type="button" onclick="closeImageLightbox()" class="text-slate-400 hover:text-white p-1 rounded-lg transition-colors cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 bg-slate-950 flex items-center justify-center min-h-[350px] max-h-[75vh]">
                <img id="imageLightboxImg" src="" alt="Enlarged photo" class="max-w-full max-h-[70vh] object-contain rounded-xl shadow-2xl">
            </div>
        </div>
    </div>

    <script>
        function openApproveModal() {
            @if($isRolledBack)
                if (typeof window.showFlashToast === 'function') {
                    window.showFlashToast('This case is currently rolled back to staff for corrections. Approval is locked until staff resubmits.', 'warning', 'Case Locked');
                } else {
                    alert('This case is currently rolled back to staff for corrections. Approval is locked until staff resubmits.');
                }
                return;
            @endif
            document.getElementById('approveModal')?.classList.remove('hidden');
        }
        function closeApproveModal() {
            document.getElementById('approveModal')?.classList.add('hidden');
        }

        // ROLLBACK MODE CONTROLS
        let isRollbackMode = false;
        let rollbackTargets = [];

        function isRollbackBlocked(el) {
            // Dedicated Arresting Officer item inside audit section: ALWAYS ALLOW
            if (el.hasAttribute('data-rollback-allowed') || el.classList.contains('rollback-allowed-item')) {
                return false;
            }

            // Primary Suspect Seizures & Exhibits section: ALWAYS ALLOW detail boxes
            if (el.closest('.primary-exhibit-dossier') || el.closest('[data-exhibit-dossier="true"]')) {
                return false;
            }

            // 1. Explicitly marked as no-rollback
            if (el.hasAttribute('data-no-rollback') && el.getAttribute('data-no-rollback') === 'true') {
                return true;
            }
            if (el.closest('[data-no-rollback="true"]')) {
                return true;
            }
            if (el.closest('.no-rollback-section') || el.closest('#exhibitsTableSection')) {
                return true;
            }

            const rawText = (el.innerText || '').toLowerCase();

            // 1. Block CASE REFERENCE NUMBER
            if (rawText.includes('case reference number') || rawText.includes('case reference #')) {
                return true;
            }

            // 2. Block Seizures (Commodities Inventory) Master Table
            if (
                rawText.includes('seizures (commodities inventory)') ||
                rawText.includes('commodities inventory')
            ) {
                return true;
            }

            // 3. Block Case Created By and Status Updated By in audit section
            if (rawText.includes('case created by:') || rawText.includes('status updated by:')) {
                return true;
            }

            return false;
        }

        function startRollbackMode() {
            @if($isRolledBack)
                if (typeof window.showFlashToast === 'function') {
                    window.showFlashToast('This case is already rolled back to staff. Actions are locked until the staff officer completes corrections and resubmits.', 'warning', 'Case Locked');
                } else {
                    alert('This case is already rolled back to staff. Actions are locked until the staff officer completes corrections and resubmits.');
                }
                return;
            @endif
            isRollbackMode = true;
            const bar = document.getElementById('rollbackActiveBar');
            if (bar) {
                bar.classList.remove('hidden');
                bar.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            const root = document.getElementById('reviewDossierContent');
            if (!root) return;

            // Target suspect detail boxes & specifically allowed items
            const allCandidates = Array.from(root.querySelectorAll('.rounded-xl.border, .rounded-xl[class*="border"], .dossier-card, .rollback-allowed-item'));
            
            // Filter according to allowed/blocked rules
            rollbackTargets = allCandidates.filter(el => {
                if (el.classList.contains('rollback-inline-box') || el.closest('.rollback-inline-box')) return false;
                if (el.id === 'rollbackActiveBar') return false;

                // Dedicated Arresting Officer item inside audit section: ALWAYS ALLOW
                if (el.hasAttribute('data-rollback-allowed') || el.classList.contains('rollback-allowed-item')) {
                    return true;
                }

                // If inside Primary Suspect Seizures & Exhibits: allow detail boxes!
                if (el.closest('.primary-exhibit-dossier') || el.closest('[data-exhibit-dossier="true"]')) {
                    const innerBoxes = el.querySelectorAll('.rounded-xl.border, .rounded-xl[class*="border"]');
                    return innerBoxes.length <= 1;
                }

                // Check blocked sections (Case Reference Number, Seizures Table, etc.)
                if (isRollbackBlocked(el)) {
                    return false;
                }

                // If container contains the audit bar or exhibits table, don't target the parent container
                if (el.querySelector('.rollback-allowed-item') || el.querySelector('.no-rollback-section')) {
                    return false;
                }

                const innerBoxes = el.querySelectorAll('.rounded-xl.border, .rounded-xl[class*="border"]');
                return innerBoxes.length <= 1;
            });

            rollbackTargets.forEach(container => {
                container.classList.add('rollback-interactive');
                container.setAttribute('title', 'Click to add requested correction for this suspect detail');
                
                if (!container.dataset.rollbackHandlerBound) {
                    container.dataset.rollbackHandlerBound = 'true';
                    container.addEventListener('click', function(e) {
                        if (!isRollbackMode) return;
                        
                        // Ignore clicks inside inline box or action buttons
                        if (e.target.closest('.rollback-inline-box') || e.target.closest('button') || e.target.closest('a')) {
                            return;
                        }
                        
                        e.stopPropagation();
                        toggleContainerCorrection(container);
                    });
                }
            });

            updateRollbackCounter();
        }

        function exitRollbackMode() {
            isRollbackMode = false;
            const bar = document.getElementById('rollbackActiveBar');
            if (bar) bar.classList.add('hidden');

            document.querySelectorAll('.rollback-interactive').forEach(el => {
                el.classList.remove('rollback-interactive');
            });
        }

        function extractContainerLabel(container) {
            if (container.dataset.rollbackTarget) {
                return container.dataset.rollbackTarget;
            }
            const labelEl = container.querySelector('[class*="text-[10px]"], .font-mono-code, h2, h3, h4, span.font-bold');
            if (labelEl) {
                const text = labelEl.innerText.trim().replace(/Click to request correction/gi, '').trim();
                if (text.length > 0 && text.length < 60) return text;
            }
            
            const cardHeading = container.closest('.dossier-card')?.querySelector('h2, h3')?.innerText.trim();
            const textLines = container.innerText.trim().split('\n').map(s => s.trim()).filter(Boolean);
            const firstLine = textLines[0] || 'Suspect Detail';
            
            return cardHeading ? `${cardHeading}: ${firstLine.substring(0, 30)}` : firstLine.substring(0, 40);
        }

        function toggleContainerCorrection(container) {
            const existingBox = container.querySelector('.rollback-inline-box');
            if (existingBox) {
                const textarea = existingBox.querySelector('.rollback-note-input');
                if (textarea && textarea.value.trim() === '') {
                    existingBox.remove();
                    container.classList.remove('rollback-selected');
                    updateRollbackCounter();
                } else {
                    textarea?.focus();
                }
                return;
            }

            container.classList.add('rollback-selected');
            const fieldLabel = extractContainerLabel(container);

            const inlineBox = document.createElement('div');
            inlineBox.className = 'rollback-inline-box mt-3 pt-3 border-t-2 border-emerald-400 bg-emerald-50/95 rounded-xl p-3 shadow-inner space-y-2';
            inlineBox.innerHTML = `
                <div class="flex items-center justify-between text-xs font-bold text-emerald-950">
                    <div class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <span>Correction Needed: <strong class="text-emerald-800 underline">${fieldLabel}</strong></span>
                    </div>
                    <button type="button" class="rollback-remove-btn w-5 h-5 rounded-full bg-red-600 hover:bg-red-700 text-white font-black text-[11px] inline-flex items-center justify-center transition shadow-2xs cursor-pointer border-none shrink-0" title="Remove correction">
                        ✕
                    </button>
                </div>
                <textarea rows="2" class="rollback-note-input w-full p-2.5 bg-white border border-emerald-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 font-sans shadow-2xs"
                    placeholder="Enter specific correction instruction for staff regarding this suspect detail..."></textarea>
            `;

            inlineBox.addEventListener('click', function(e) {
                e.stopPropagation();
            });

            inlineBox.querySelector('.rollback-remove-btn').addEventListener('click', function(e) {
                e.stopPropagation();
                inlineBox.remove();
                container.classList.remove('rollback-selected');
                updateRollbackCounter();
            });

            container.appendChild(inlineBox);
            updateRollbackCounter();

            setTimeout(() => {
                inlineBox.querySelector('textarea')?.focus();
            }, 60);
        }

        function updateRollbackCounter() {
            const selected = document.querySelectorAll('#reviewDossierContent .rollback-selected');
            const count = selected.length;
            const badge = document.getElementById('rollbackCountBadge');
            if (badge) {
                badge.textContent = `${count} Section${count === 1 ? '' : 's'} Selected`;
            }
        }

        function openRollbackSubmitModal() {
            const selectedContainers = Array.from(document.querySelectorAll('#reviewDossierContent .rollback-selected'));
            const listEl = document.getElementById('rollbackModalCorrectionList');
            const countEl = document.getElementById('rollbackModalCount');
            const inputEl = document.getElementById('rollbackCorrectionsInput');

            if (selectedContainers.length === 0) {
                const warningMsg = '<span class="text-black font-semibold" style="color: #000000 !important;">Please select at least one section for correction before sending a rollback to staff.</span>';
                const warningTitle = '<span class="text-black font-bold" style="color: #000000 !important;">Select Section for Correction</span>';
                if (typeof window.showFlashToast === 'function') {
                    window.showFlashToast(warningMsg, 'warning', warningTitle);
                } else {
                    alert('Please select at least one section for correction before sending a rollback to staff.');
                }

                // Highlight and pulse the count badge as a visual cue
                const badge = document.getElementById('rollbackCountBadge');
                if (badge) {
                    badge.classList.add('ring-2', 'ring-amber-500', 'bg-amber-100', 'text-amber-950', 'animate-pulse');
                    setTimeout(() => {
                        badge.classList.remove('ring-2', 'ring-amber-500', 'bg-amber-100', 'text-amber-950', 'animate-pulse');
                    }, 3000);
                }

                const dossier = document.getElementById('reviewDossierContent');
                if (dossier) {
                    dossier.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
                return;
            }

            const corrections = [];
            let html = '';
            selectedContainers.forEach((container, idx) => {
                const label = extractContainerLabel(container);
                const note = container.querySelector('.rollback-note-input')?.value.trim() || 'Correction requested by Unit Head.';
                corrections.push({
                    field: label,
                    note: note
                });

                html += `
                    <div class="p-2.5 bg-green-100 border-none rounded-xl text-xs space-y-1">
                        <div class="flex items-center justify-between font-bold text-green-950 font-mono-code text-[11px]">
                            <span>#${idx + 1}. ${label}</span>
                        </div>
                        <div class="text-slate-700 font-sans">${note}</div>
                    </div>
                `;
            });

            if (listEl) listEl.innerHTML = html;
            if (countEl) countEl.textContent = String(corrections.length);
            if (inputEl) inputEl.value = JSON.stringify(corrections);

            document.getElementById('rollbackSubmitModal')?.classList.remove('hidden');
        }

        function closeRollbackSubmitModal() {
            document.getElementById('rollbackSubmitModal')?.classList.add('hidden');
        }

        function openDeclineModal() {
            startRollbackMode();
        }
        function closeDeclineModal() {
            closeRollbackSubmitModal();
        }

        function openImageLightbox(src, title) {
            const modal = document.getElementById('imageLightboxModal');
            const img = document.getElementById('imageLightboxImg');
            const titleEl = document.getElementById('imageLightboxTitle');
            if (!modal || !img) return;

            img.src = src;
            if (titleEl && title) titleEl.textContent = title;
            modal.classList.remove('hidden');
        }

        function closeImageLightbox() {
            const modal = document.getElementById('imageLightboxModal');
            if (modal) modal.classList.add('hidden');
        }

        // Global bridge so suspect-mugshots partial onclick="openMugshotModal(...)" opens the lightbox seamlessly
        window.openMugshotModal = function(imgSrc, titleText) {
            if (!imgSrc) return;
            openImageLightbox(imgSrc, titleText || 'Suspect Biometric Mugshot');
        };

        // Attach click listeners to mugshot images and AJAX submit for Rollback form
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('img[alt*="Mugshot"], img[alt*="Exhibit"]').forEach(img => {
                img.style.cursor = 'zoom-in';
                img.addEventListener('click', function() {
                    openImageLightbox(this.src, this.alt || 'Biometric / Exhibit Photo');
                });
            });

            const rollbackForm = document.getElementById('rollbackSubmitForm');
            if (rollbackForm) {
                rollbackForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const correctionsRaw = document.getElementById('rollbackCorrectionsInput')?.value;
                    let parsedCorrections = [];
                    try {
                        parsedCorrections = JSON.parse(correctionsRaw || '[]');
                    } catch (err) {
                        parsedCorrections = [];
                    }

                    if (!Array.isArray(parsedCorrections) || parsedCorrections.length === 0) {
                        const warningMsg = '<span class="text-black font-semibold" style="color: #000000 !important;">Please select at least one section for correction before sending a rollback to staff.</span>';
                        const warningTitle = '<span class="text-black font-bold" style="color: #000000 !important;">Select Section for Correction</span>';
                        if (typeof window.showFlashToast === 'function') {
                            window.showFlashToast(warningMsg, 'warning', warningTitle);
                        } else {
                            alert('Please select at least one section for correction before sending a rollback to staff.');
                        }
                        return;
                    }

                    const submitBtn = rollbackForm.querySelector('button[type="submit"]');
                    const originalBtnHtml = submitBtn ? submitBtn.innerHTML : 'Confirm &amp; Send Rollback';
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = `
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            Sending Rollback...
                        `;
                    }

                    const formData = new FormData(rollbackForm);
                    fetch(rollbackForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(async (res) => {
                        const data = await res.json().catch(() => null);
                        if (!res.ok) {
                            throw new Error(data?.message || 'Server error occurred during rollback submission (' + res.status + ')');
                        }
                        return data;
                    })
                    .then(data => {
                        if (data && data.success) {
                            closeRollbackSubmitModal();
                            if (typeof window.showFlashToast === 'function') {
                                window.showFlashToast(data.message || 'Case submission has been rolled back to staff officer for corrections.', 'success', 'Case Rollback Dispatched');
                            }
                            setTimeout(() => {
                                window.location.href = data.redirect || '{{ $reviewsIndexUrl }}';
                            }, 800);
                        } else {
                            throw new Error(data?.message || 'Rollback could not be processed.');
                        }
                    })
                    .catch(err => {
                        console.error('Rollback submission error:', err);
                        if (typeof window.showFlashToast === 'function') {
                            window.showFlashToast(err.message || 'Unable to complete rollback. Please try again.', 'error', 'Rollback Failed');
                        } else {
                            alert(err.message || 'Unable to complete rollback.');
                        }
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalBtnHtml;
                        }
                    });
                });
            }

            // STAFF CASE EDIT: AUTOMATIC SCROLL TO EDITED PLACE & VISUAL HIGHLIGHTING
            const isEditSubmission = @json($isEditSubmission ?? false);
            const editedFields = @json($editedFields ?? []);
            const firstSelector = @json($firstEditedSelector ?? null);
            const firstField = @json($firstEditedField ?? null);

            window.scrollToEditedSection = function(fKey) {
                if (!fKey) return;
                const el = document.querySelector(`[data-field-group="${fKey}"]`)
                    || document.querySelector(`[data-rollback-target*="${fKey}" i]`)
                    || document.getElementById(fKey);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    el.classList.add('ring-4', 'ring-amber-500', 'shadow-xl');
                    setTimeout(() => {
                        el.classList.remove('ring-4', 'shadow-xl');
                        el.classList.add('ring-2', 'ring-amber-400');
                    }, 2500);
                }
            };

            if (isEditSubmission && (editedFields.length > 0 || firstSelector || firstField)) {
                // Highlight all edited fields with distinct badge and ring
                editedFields.forEach(f => {
                    const matchedEls = document.querySelectorAll(`[data-field-group="${f}"]`);
                    matchedEls.forEach(el => {
                        el.classList.add('ring-2', 'ring-amber-400', 'bg-amber-50/70', 'transition-all');
                        if (!el.querySelector('.staff-edit-badge')) {
                            const badge = document.createElement('span');
                            badge.className = 'staff-edit-badge inline-flex items-center gap-1 text-[10px] font-mono-code font-extrabold uppercase px-2 py-0.5 rounded bg-amber-500 text-slate-950 ml-2 shadow-xs';
                            badge.innerHTML = '<svg class="w-3 h-3 text-slate-950 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg> Edited by Staff';
                            const titleEl = el.querySelector('h2, h3, .font-bold, .font-extrabold, div');
                            if (titleEl) {
                                titleEl.appendChild(badge);
                            }
                        }
                    });
                });

                // Find initial element to scroll to
                let scrollTarget = null;
                if (firstSelector) {
                    scrollTarget = document.querySelector(firstSelector);
                }
                if (!scrollTarget && firstField) {
                    scrollTarget = document.querySelector(`[data-field-group="${firstField}"]`)
                        || document.querySelector(`[data-rollback-target*="${firstField}" i]`)
                        || document.getElementById(firstField);
                }
                if (!scrollTarget && editedFields.length > 0) {
                    for (const f of editedFields) {
                        scrollTarget = document.querySelector(`[data-field-group="${f}"]`)
                            || document.querySelector(`[data-rollback-target*="${f}" i]`);
                        if (scrollTarget) break;
                    }
                }

                if (scrollTarget) {
                    setTimeout(() => {
                        scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        scrollTarget.classList.add('ring-4', 'ring-amber-500', 'shadow-xl');
                        setTimeout(() => {
                            scrollTarget.classList.remove('ring-4', 'shadow-xl');
                            scrollTarget.classList.add('ring-2', 'ring-amber-400');
                        }, 2500);
                    }, 500);
                }
            }
        });
    </script>

</body>
</html>
