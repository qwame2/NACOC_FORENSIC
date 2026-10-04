@php
    $authUser = auth()->user();
    $dbUnitHead = \App\Models\User::where('unit', 'LIKE', '%Evidence%')->where('is_head', true)->first()
               ?? \App\Models\User::where('is_head', true)->first();
    $headName = $authUser->fullname ?? session('user_name') ?? ($dbUnitHead ? $dbUnitHead->fullname : 'Chief Vault Custodian');
    $cases = \App\Models\ForensicCase::allCases();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Evidence Management Command Center | NACOC Forensic</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; background-color: #F8FAFC; color: #0F172A; }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="min-h-screen flex antialiased selection:bg-amber-500 selection:text-white">

    <div class="flex h-screen w-full overflow-hidden">
        @include('Crime_Scene_Support.partials.sidebar')

        <div class="flex-1 flex flex-col min-w-0 bg-slate-50 overflow-hidden">
            <!-- TOP BAR -->
            <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 z-20">
                <div class="flex items-center gap-4 flex-1 max-w-2xl">
                    <div class="relative w-full">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input type="text" placeholder="Search Vault Item, Barcode #, Chain of Custody Tag..." class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-amber-500">
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1 bg-amber-50 border border-amber-200 text-amber-700 font-extrabold text-xs font-mono-code rounded-lg">EVIDENCE VAULT ONLINE</span>
                </div>
            </header>

            <!-- MAIN CONTENT -->
            <main class="flex-1 overflow-y-auto p-6 space-y-6">
                @include('partials.flash')

                <!-- HEADER HERO -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2.5 py-0.5 bg-amber-600 text-white text-[10px] font-extrabold font-mono-code rounded-md uppercase">EVIDENCE MANAGEMENT UNIT</span>
                            <span class="text-xs text-slate-400">&bull;</span>
                            <span class="text-xs font-bold text-slate-700 font-mono-code">VAULT COMMAND &amp; CHAIN OF CUSTODY</span>
                        </div>
                        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Evidence Vault &amp; Custody Headquarters</h1>
                        <p class="text-xs text-slate-500 mt-1">Commanding Officer: <strong class="text-slate-800">{{ $headName }}</strong> &bull; Total Cases in Vault: <strong class="text-amber-700 font-mono-code">{{ count($cases) }}</strong></p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ url('/Evidence_Management/create') }}" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors">
                            + Lodge New Exhibit Case
                        </a>
                        <a href="{{ url('/cases') }}?from=Evidence+Management" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-xl shadow-xs border border-slate-200 transition-colors">
                            View Master Vault Directory
                        </a>
                    </div>
                </div>

                <!-- KPIS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
                        <div class="text-xs font-bold text-slate-500 font-mono-code uppercase">Vault Items Secured</div>
                        <div class="text-2xl font-extrabold text-slate-900 font-mono-code mt-2">1,420 Bags</div>
                        <div class="text-[11px] text-amber-600 font-semibold mt-1">100% Barcode Verified</div>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
                        <div class="text-xs font-bold text-slate-500 font-mono-code uppercase">Pending Handover</div>
                        <div class="text-2xl font-extrabold text-amber-600 font-mono-code mt-2">3 Transfers</div>
                        <div class="text-[11px] text-slate-500 font-semibold mt-1">Awaiting Lab Sign-off</div>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
                        <div class="text-xs font-bold text-slate-500 font-mono-code uppercase">Court Dispatches</div>
                        <div class="text-2xl font-extrabold text-emerald-600 font-mono-code mt-2">48 Active</div>
                        <div class="text-[11px] text-slate-500 font-semibold mt-1">High Court Exhibits</div>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
                        <div class="text-xs font-bold text-slate-500 font-mono-code uppercase">Tamper Audits Passed</div>
                        <div class="text-2xl font-extrabold text-cyan-600 font-mono-code mt-2">99.8%</div>
                        <div class="text-[11px] text-slate-500 font-semibold mt-1">Quarterly Seal Inspection</div>
                    </div>
                </div>

                <!-- MASTER CASE TABLE -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                    <div class="p-5 border-b border-slate-200 flex justify-between items-center bg-slate-50/50">
                        <div>
                            <h3 class="font-extrabold text-slate-900 text-sm">Evidence Vault Docket Directory</h3>
                            <p class="text-xs text-slate-500">Track and manage tamper-evident exhibit bags and chain of custody logs</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-700">
                            <thead class="bg-slate-100/80 border-b border-slate-200 text-slate-500 font-mono-code text-[11px] uppercase">
                                <tr>
                                    <th class="py-3.5 px-5">Case #</th>
                                    <th class="py-3.5 px-5">Title</th>
                                    <th class="py-3.5 px-5">Suspect Name</th>
                                    <th class="py-3.5 px-5">Suspect Disposition</th>
                                    <th class="py-3.5 px-5">Status</th>
                                    <th class="py-3.5 px-5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                @forelse($cases as $c)
                                <tr class="hover:bg-slate-50">
                                    <td class="py-3.5 px-5 font-bold font-mono-code text-amber-700">{{ $c->case_number }}</td>
                                    <td class="py-3.5 px-5 font-semibold text-slate-900">{{ $c->title }}</td>
                                    <td class="py-3.5 px-5 text-slate-600">{{ $c->suspect_name ?? 'N/A' }}</td>
                                    <td class="py-3.5 px-5 whitespace-nowrap">
                                        @php
                                            $sDispListEmu = $c->getSuspectsList();
                                        @endphp
                                        <div class="space-y-1">
                                            @foreach ($sDispListEmu as $sObj)
                                                @php
                                                    $dispVal = $sObj['suspect_disposition'] ?? 'Active — Under Investigation';
                                                    $dispReason = $sObj['disposition_reason'] ?? '';
                                                    $dispBadge = match (true) {
                                                        str_contains($dispVal, 'Cleared') => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                                        str_contains($dispVal, 'Released') => 'bg-blue-100 text-blue-800 border-blue-300',
                                                        str_contains($dispVal, 'Reclassified') => 'bg-purple-100 text-purple-800 border-purple-300',
                                                        str_contains($dispVal, 'Referred') => 'bg-amber-100 text-amber-800 border-amber-300',
                                                        default => 'bg-slate-100 text-slate-700 border-slate-300',
                                                    };
                                                @endphp
                                                <div class="flex items-center gap-1.5" title="{{ !empty($dispReason) ? 'Reason: ' . $dispReason : $dispVal }}">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[9.5px] font-bold font-mono-code border {{ $dispBadge }}">
                                                        @if (count($sDispListEmu) > 1)
                                                            <span class="font-extrabold text-slate-900 mr-1">{{ $sObj['suspect_name'] }}:</span>
                                                        @endif
                                                        {{ $dispVal }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-5">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold font-mono-code bg-amber-50 text-amber-700 border border-amber-200">{{ $c->status }}</span>
                                    </td>
                                    <td class="py-3.5 px-5 text-right">
                                        <a href="{{ url('/cases/' . $c->case_number . '/review?from=Evidence+Management') }}" class="px-3 py-1.5 bg-slate-900 text-white font-bold text-[11px] rounded-lg hover:bg-slate-800">Review Vault Log</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-slate-400 font-semibold">No evidence cases registered yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>
</body>
</html>
