@php
    $authUser = auth()->user();
    $unitKey = $unitContext['key'] ?? 'crime_scene_support';
    $unitLabel = $unitContext['label'] ?? 'Crime Scene Support Unit';
    $dashboardBackUrl = match ($unitKey) {
        'lab_analysis' => url('/Lab_Analysis'),
        'evidence_management' => url('/Evidence_Management'),
        'digital_forensics' => url('/Digital_Forensics'),
        'forensic_head' => url('/Forensic_Head'),
        default => url('/Crime_Scene_Support/head'),
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reviews &amp; Approvals | {{ $unitLabel }} | NACOC Forensic</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    @endif

    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">

    <div class="flex h-screen overflow-hidden">
        @include('Crime_Scene_Support.partials.sidebar')

        <div class="flex-1 flex flex-col min-w-0 bg-slate-50 overflow-hidden">
            <!-- TOPBAR -->
            <header class="h-16 bg-white border-b border-slate-200/90 px-6 flex items-center justify-between shrink-0 shadow-2xs z-20">
                <div class="flex items-center gap-3">
                    <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-extrabold font-mono-code uppercase tracking-wider">
                        {{ $unitLabel }}
                    </span>
                    <h1 class="text-sm font-extrabold text-slate-900 tracking-tight">Reviews &amp; Approvals</h1>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-mono-code font-bold bg-amber-50 text-amber-700 border border-amber-200">
                        {{ $pendingSubmissions->count() }} Pending
                    </span>
                </div>

                <div class="flex items-center gap-2.5">
                    <a href="{{ $dashboardBackUrl }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold rounded-xl transition-colors">
                        <span>&larr;</span>
                        <span>Back to Dashboard</span>
                    </a>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-6 space-y-6">
                @include('partials.flash')

                <!-- HEADER CARD -->
                <div class="bg-white rounded-2xl border border-slate-200/90 p-6 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="text-[11px] font-bold font-mono-code uppercase tracking-wider text-emerald-700 mb-1">
                            Pre-Database Staging Queue
                        </div>
                        <h2 class="text-xl font-black text-slate-900 tracking-tight">Staff Case Submissions Awaiting Approval</h2>
                        <p class="text-xs text-slate-500 mt-1">
                            Cases submitted by unit staff officers are held here for your review. Approving a submission saves it permanently into the forensic case database.
                        </p>
                    </div>
                </div>

                <!-- PENDING SUBMISSIONS TABLE -->
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-sm font-extrabold text-slate-900">Pending Staff Submissions ({{ $pendingSubmissions->count() }})</h3>
                    </div>

                    @if($pendingSubmissions->isEmpty())
                        <div class="p-10 text-center text-slate-500 text-xs">
                            No pending staff case submissions awaiting review for {{ $unitLabel }}.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead class="bg-slate-50 text-[10px] font-mono-code uppercase tracking-wider text-slate-500 border-b border-slate-200">
                                    <tr>
                                        <th class="py-3 px-5">Submission</th>
                                        <th class="py-3 px-5">Suspect &amp; Case Title</th>
                                        <th class="py-3 px-5">Submitted By</th>
                                        <th class="py-3 px-5">Unit</th>
                                        <th class="py-3 px-5">Priority</th>
                                        <th class="py-3 px-5 text-right">Review &amp; Decision</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($pendingSubmissions as $sub)
                                        <tr class="hover:bg-slate-50/80 transition-colors">
                                            <td class="py-4 px-5">
                                                <div class="font-mono-code font-bold text-slate-900">SUB-{{ str_pad((string)$sub->id, 4, '0', STR_PAD_LEFT) }}</div>
                                                <div class="text-[10px] text-slate-400 font-mono-code">{{ optional($sub->created_at)->format('d M Y, H:i') }}</div>
                                            </td>
                                            <td class="py-4 px-5">
                                                <div class="font-bold text-slate-900">{{ $sub->suspect_name ?: 'N/A' }}</div>
                                                <div class="text-[11px] text-slate-500">{{ $sub->title ?: 'Staff Case Submission' }}</div>
                                            </td>
                                            <td class="py-4 px-5">
                                                <div class="font-semibold text-slate-800">{{ $sub->submitted_by_name ?: 'Staff Officer' }}</div>
                                                <div class="text-[10px] text-slate-400">Staff Officer</div>
                                            </td>
                                            <td class="py-4 px-5">
                                                <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-semibold text-[11px]">{{ $sub->unit_name }}</span>
                                            </td>
                                            <td class="py-4 px-5">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold font-mono-code {{ strtoupper($sub->priority) === 'HIGH' || strtoupper($sub->priority) === 'CRITICAL' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                                    {{ strtoupper($sub->priority ?: 'MEDIUM') }}
                                                </span>
                                            </td>
                                            <td class="py-4 px-5 text-right">
                                                <div class="inline-flex items-center gap-2">
                                                    <button type="button" onclick='openSubmissionInspectModal(@json($sub))' class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-[11px] transition-colors">
                                                        Inspect
                                                    </button>
                                                    <form method="POST" action="{{ url('/reviews-approvals/' . $sub->id . '/approve') }}" class="inline">
                                                        @csrf
                                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] shadow-xs transition-colors">
                                                            Approve &amp; Save
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="{{ url('/reviews-approvals/' . $sub->id . '/decline') }}" class="inline">
                                                        @csrf
                                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-[11px] transition-colors">
                                                            Decline
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <!-- REVIEWED HISTORY -->
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-sm font-extrabold text-slate-900">Recently Reviewed Submissions</h3>
                    </div>
                    @if($reviewedSubmissions->isEmpty())
                        <div class="p-8 text-center text-slate-400 text-xs">
                            No reviewed submissions yet.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead class="bg-slate-50 text-[10px] font-mono-code uppercase tracking-wider text-slate-500 border-b border-slate-200">
                                    <tr>
                                        <th class="py-3 px-5">Submission</th>
                                        <th class="py-3 px-5">Suspect / Title</th>
                                        <th class="py-3 px-5">Submitted By</th>
                                        <th class="py-3 px-5">Decision</th>
                                        <th class="py-3 px-5">Saved Case #</th>
                                        <th class="py-3 px-5">Reviewed By</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($reviewedSubmissions as $rev)
                                        <tr>
                                            <td class="py-3 px-5 font-mono-code font-bold text-slate-700">SUB-{{ str_pad((string)$rev->id, 4, '0', STR_PAD_LEFT) }}</td>
                                            <td class="py-3 px-5">
                                                <div class="font-bold text-slate-900">{{ $rev->suspect_name ?: 'N/A' }}</div>
                                                <div class="text-[11px] text-slate-500">{{ $rev->title }}</div>
                                            </td>
                                            <td class="py-3 px-5 text-slate-700">{{ $rev->submitted_by_name }}</td>
                                            <td class="py-3 px-5">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $rev->status === 'Approved' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                                    {{ $rev->status }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-5 font-mono-code font-bold text-slate-800">
                                                {{ $rev->approved_case_number ?: '—' }}
                                            </td>
                                            <td class="py-3 px-5 text-slate-600">
                                                {{ $rev->reviewed_by_name ?: 'Unit Head' }}
                                                <div class="text-[10px] text-slate-400">{{ optional($rev->reviewed_at)->format('d M Y, H:i') }}</div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>

    <!-- INSPECT MODAL -->
    <div id="inspectSubmissionModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h4 id="inspectModalTitle" class="text-sm font-extrabold text-slate-900">Inspect Submission</h4>
                    <p id="inspectModalSub" class="text-[11px] text-slate-500"></p>
                </div>
                <button type="button" onclick="closeSubmissionInspectModal()" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-700">Close</button>
            </div>
            <div id="inspectModalBody" class="p-6 max-h-[65vh] overflow-y-auto space-y-2 text-xs"></div>
        </div>
    </div>

    <script>
        function openSubmissionInspectModal(sub) {
            const modal = document.getElementById('inspectSubmissionModal');
            document.getElementById('inspectModalTitle').textContent = `SUB-${String(sub.id).padStart(4, '0')} • ${sub.suspect_name || 'Pending Suspect'}`;
            document.getElementById('inspectModalSub').textContent = `Submitted by ${sub.submitted_by_name || 'Staff'} • ${sub.unit_name || ''}`;
            const body = document.getElementById('inspectModalBody');
            const payload = sub.payload || {};
            const entries = Object.entries(payload).filter(([k, v]) => v !== null && v !== '' && k !== 'mugshot_data' && k !== 'photo_data');
            body.innerHTML = entries.map(([k, v]) => {
                const val = typeof v === 'object' ? JSON.stringify(v) : String(v);
                return `<div class="grid grid-cols-3 gap-2 py-1.5 border-b border-slate-100">
                    <div class="font-mono-code font-bold text-slate-500 uppercase text-[10px]">${k.replace(/_/g, ' ')}</div>
                    <div class="col-span-2 text-slate-800 break-words">${val.replace(/</g, '&lt;')}</div>
                </div>`;
            }).join('') || '<div class="text-slate-400">No additional fields in submission payload.</div>';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeSubmissionInspectModal() {
            const modal = document.getElementById('inspectSubmissionModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
</body>
</html>

