@php
    $authUser = auth()->user();
    $dbUnitHead = \App\Models\User::where('unit', 'LIKE', '%Evidence%')->where('is_head', true)->first()
               ?? \App\Models\User::where('is_head', true)->first();
    $headName = $dbUnitHead ? $dbUnitHead->fullname : 'Chief Vault Custodian';
    $staffName = $authUser->fullname ?? session('user_name') ?? 'Vault Staff Custodian';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Evidence Vault Staff Workstation | NACOC Forensic</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
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
<body class="min-h-screen flex flex-col bg-slate-50 text-slate-900 antialiased">
    <div class="flex h-screen overflow-hidden">
        @include('Crime_Scene_Support.partials.sidebar')

        <div class="flex-1 flex flex-col min-w-0 bg-slate-50 overflow-hidden">
            <!-- TOP BAR -->
            <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 shadow-2xs">
                <div class="flex items-center flex-1 max-w-lg">
                    <div class="relative w-full">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input type="text" placeholder="Scan Barcode or Search Shelf Lot..." class="w-full pl-10 pr-4 py-2 bg-slate-100/70 border border-slate-200 rounded-xl text-xs font-mono-code text-slate-900 focus:outline-none focus:bg-white focus:border-amber-600">
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="alert('Barcode Scanner Engine Active')" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-xl shadow-xs">
                        + Log Vault Intake Bag
                    </button>
                </div>
            </header>

            <!-- MAIN WORKSPACE -->
            <main class="flex-1 overflow-y-auto p-6 space-y-6">
                @include('partials.flash')

                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs flex justify-between items-center">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2.5 py-0.5 bg-amber-600 text-white text-[10px] font-extrabold font-mono-code rounded-md uppercase">STAFF WORKSTATION</span>
                            <span class="text-xs text-slate-400">&bull;</span>
                            <span class="text-xs font-bold text-slate-700 font-mono-code">VAULT INTAKE &amp; BARCODE AUDIT</span>
                        </div>
                        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Evidence Vault Staff Operations</h1>
                        <p class="text-xs text-slate-500 mt-1">LoggedIn Custodian: <strong class="text-slate-800">{{ $staffName }}</strong> &bull; Supervisor: <strong>{{ $headName }}</strong></p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
                        <div class="text-xs font-bold text-slate-500 font-mono-code uppercase">Intake Logs Today</div>
                        <div class="text-2xl font-extrabold text-amber-600 font-mono-code mt-2">24 Bags</div>
                        <div class="text-[11px] text-slate-500 mt-1">Logged into Vault Zone A</div>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
                        <div class="text-xs font-bold text-slate-500 font-mono-code uppercase">Shelf Assignment</div>
                        <div class="text-2xl font-extrabold text-slate-900 font-mono-code mt-2">Rack 14-B</div>
                        <div class="text-[11px] text-emerald-600 font-semibold mt-1">Biometric Lock Active</div>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
                        <div class="text-xs font-bold text-slate-500 font-mono-code uppercase">Handover Verification</div>
                        <div class="text-2xl font-extrabold text-cyan-600 font-mono-code mt-2">100% Match</div>
                        <div class="text-[11px] text-slate-500 mt-1">Chain of Custody Clean</div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
