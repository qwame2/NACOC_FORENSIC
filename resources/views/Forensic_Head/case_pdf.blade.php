@php
    $authUser = auth()->user();
    $officerName = $authUser->fullname ?? session('user_name') ?? (\App\Models\User::where('is_head', true)->first()->fullname ?? 'Officer');
    $officerRole = ($authUser && $authUser->is_head) ? 'Forensic Head' : 'Commanding Officer';

    $status = $case->status ?? 'Approved';
    $priority = strtoupper($case->priority ?? 'NORMAL');
    
    $docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
    $evidencePhotos = is_array($case->evidence_photos) ? $case->evidence_photos : (json_decode($case->evidence_photos ?? '[]', true) ?: ($docketData['evidence_photos'] ?? []));

    if (empty($evidencePhotos) && ($case->has_seizures || !empty($case->seizure_name) || !empty($case->exhibits_summary) || !empty($case->exhibit_category))) {
        $cat = strtolower($case->exhibit_category ?? '');
        $sName = strtolower(($case->seizure_name ?: $case->exhibits_summary) ?? '');
        
        if (str_contains($cat, 'money') || str_contains($sName, 'cedi') || str_contains($sName, 'dollar') || str_contains($sName, 'cash') || str_contains($sName, 'naira')) {
            $evidencePhotos = [
                'https://images.unsplash.com/photo-1561414927-6d86591d0c4f?q=80&w=800&auto=format&fit=crop',
                'https://images.unsplash.com/photo-1526304640581-d334cdbbf45e?q=80&w=800&auto=format&fit=crop'
            ];
        } elseif (str_contains($cat, 'gold') || str_contains($sName, 'gold') || str_contains($sName, 'bullion')) {
            $evidencePhotos = [
                'https://images.unsplash.com/photo-1610375461246-83df859d849d?q=80&w=800&auto=format&fit=crop',
                'https://images.unsplash.com/photo-1605792657660-596af9009e82?q=80&w=800&auto=format&fit=crop'
            ];
        } elseif (str_contains($cat, 'vehicle') || str_contains($sName, 'car') || str_contains($sName, 'truck') || !empty($case->veh_make)) {
            $evidencePhotos = [
                'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?q=80&w=800&auto=format&fit=crop',
                'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?q=80&w=800&auto=format&fit=crop'
            ];
        } else {
            $evidencePhotos = [
                'https://images.unsplash.com/photo-1584467735871-8e85353a8413?q=80&w=800&auto=format&fit=crop',
                'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=800&auto=format&fit=crop'
            ];
        }
    }

    $getImgSrc = function($path) {
        if (empty($path)) return null;
        if (str_starts_with($path, 'data:') || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return asset($path);
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Forensic Case File — {{ $case->case_number }} | NACOC Ghana</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;800;900&family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@600;700;800&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #0f172a;
            color: #0f172a;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
        .font-official { font-family: 'Cinzel', serif; }

        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print { display: none !important; }
            .print-container {
                box-shadow: none !important;
                border: none !important;
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                border-radius: 0 !important;
                overflow: visible !important;
            }
            .pdf-watermark-container {
                position: fixed !important;
                top: 50% !important;
                left: 50% !important;
                transform: translate(-50%, -50%) !important;
                width: 480px !important;
                height: 480px !important;
                opacity: 0.08 !important;
                z-index: 0 !important;
                pointer-events: none !important;
                user-select: none !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            @page {
                size: A4 portrait;
                margin: 12mm 15mm 15mm 15mm;
            }
            .page-break {
                page-break-before: always;
            }
            .avoid-break {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
        .pdf-watermark-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 520px;
            height: 520px;
            pointer-events: none;
            user-select: none;
            z-index: 0;
            opacity: 0.07;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body class="p-4 sm:p-8 min-h-screen flex flex-col items-center justify-start bg-slate-900 text-slate-900">

    <!-- TOP TOOLBAR (EXCLUDED FROM PRINT) -->
    <div class="w-full max-w-4xl flex items-center justify-between mb-6 no-print">
        <a href="/cases/{{ $case->case_number }}/review" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white rounded-xl text-xs font-bold border border-slate-700 transition-all flex items-center gap-2 cursor-pointer no-underline shadow-md">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back to Case Review</span>
        </a>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-extrabold shadow-lg transition-all flex items-center gap-2 cursor-pointer border border-emerald-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Print Official A4 Case File</span>
            </button>
        </div>
    </div>

    <!-- MAIN A4 PRINT CONTAINER -->
    <div class="print-container w-full max-w-4xl bg-white rounded-3xl p-8 sm:p-10 border border-slate-300 shadow-2xl space-y-6 text-slate-900 relative overflow-hidden">

        <!-- OFFICIAL NACOC WATERMARK LOGO BACKGROUND -->
        <div class="pdf-watermark-container pointer-events-none select-none" aria-hidden="true">
            <img src="{{ asset('nacoc.jfif') }}" 
                 onerror="this.onerror=null; this.src='{{ asset('NACOC LOGO.jfif') }}';" 
                 class="w-full h-full object-contain" 
                 alt="NACOC Official Watermark Logo">
        </div>

        <!-- OFFICIAL GHANA EMBLEM & LETTERHEAD (TOP CENTER) -->
        <div class="text-center space-y-2 border-b-2 border-slate-900 pb-4 relative z-10">
            
            <!-- TOP MIDDLE FORENSIC / NACOC CREST -->
            <div class="flex justify-center mb-2">
                <div class="relative w-24 h-24 flex items-center justify-center">
                    <img src="{{ asset('NACOC LOGO.jfif') }}" 
                         onerror="this.onerror=null; this.src='{{ asset('nacoc.jfif') }}';" 
                         class="max-h-24 max-w-24 object-contain mx-auto" 
                         alt="NACOC Emblem Logo">
                </div>
            </div>

            <!-- REPUBLIC & DIRECTORATE HEADING -->
            <div class="space-y-1">
                <div class="text-xs font-extrabold font-mono-code tracking-[0.2em] text-slate-700 uppercase">REPUBLIC OF GHANA</div>
                <div class="text-lg font-black font-official tracking-wide text-slate-950 uppercase">NARCOTICS CONTROL COMMISSION (NACOC)</div>
                <div class="text-sm font-extrabold text-blue-900 font-mono-code uppercase tracking-wider">FORENSIC DEPARTMENT</div>

            </div>

            <!-- DOUBLE LINE ACCENT DIVIDER -->
            <div class="pt-2">
                <div class="w-full h-0.5 bg-slate-900"></div>
                <div class="w-full h-px bg-slate-400 mt-0.5"></div>
            </div>
        </div>

        <!-- CASE TITLE & LEGAL REFERENCE BANNER -->
        <div class="p-4 bg-slate-100/95 rounded-2xl border border-slate-300 flex flex-col sm:flex-row items-center justify-between gap-4 avoid-break relative z-10">
            <div>
                <div class="text-[10px] font-mono-code font-bold text-blue-900 uppercase tracking-widest">OFFICIAL CASE FILE • CONFIDENTIAL</div>
                <h1 class="text-xl font-black text-slate-900 uppercase mt-0.5 tracking-tight">
                    {{ $case->title }}
                </h1>
                <div class="text-xs font-mono-code text-slate-600 mt-0.5 font-bold">
                    CASE REF NO: <span class="text-blue-700">{{ $case->case_number }}</span>
                    @if(!empty($case->operation_code_name) || !empty($case->operation_code))
                        <span class="mx-2">•</span> OPERATION TYPE: <span class="text-slate-900">{{ $case->operation_code_name ?: $case->operation_code }}</span>
                    @endif
                </div>
            </div>

            <div class="text-right shrink-0">
                <div class="inline-flex items-center px-3 py-1 rounded-lg bg-emerald-100 border border-emerald-300 text-emerald-900 font-mono-code font-extrabold text-xs uppercase">
                    <svg class="w-3.5 h-3.5 mr-1 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    OFFICIALLY AUTHORIZED
                </div>
                <div class="text-[10px] font-mono-code text-slate-500 mt-1 font-bold">
                    DATE: {{ date('Y-m-d H:i') }} UTC
                </div>
            </div>
        </div>

        <!-- MUGSHOT PHOTOS (3-ANGLE BIOMETRIC CAPTURE) -->
        <div class="space-y-3 avoid-break relative z-10">
            <div class="text-xs font-extrabold text-slate-900 uppercase font-mono-code border-b border-slate-300 pb-1 flex items-center justify-between">
                <div>
                    <span>SUSPECT BIOMETRIC MUGSHOT RECORD</span>
                </div>
                <span class="text-[10px] text-slate-500 font-semibold">BOOKING FACILITY CAPTURE</span>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <!-- Frontal View -->
                <div class="p-2 bg-slate-50 border border-slate-300 rounded-xl text-center space-y-1.5 shadow-sm">
                    <div class="flex items-center justify-between text-[9px] font-mono-code font-bold">
                        <span class="px-1.5 py-0.5 rounded bg-slate-800 text-white">ANGLE 1</span>
                        <span class="text-slate-700 uppercase">Frontal View</span>
                    </div>
                    <div class="w-full h-44 bg-white rounded-lg overflow-hidden border border-slate-300 flex items-center justify-center">
                        @if(!empty($case->mugshot_front))
                            <img src="{{ $getImgSrc($case->mugshot_front) }}" class="w-full h-full object-contain object-center" alt="Frontal Mugshot">
                        @else
                            <div class="flex flex-col items-center justify-center text-slate-400 text-xs font-bold">
                                <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <span>No Front Photo</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Left Profile -->
                <div class="p-2 bg-slate-50 border border-slate-300 rounded-xl text-center space-y-1.5 shadow-sm">
                    <div class="flex items-center justify-between text-[9px] font-mono-code font-bold">
                        <span class="px-1.5 py-0.5 rounded bg-slate-800 text-white">ANGLE 2</span>
                        <span class="text-slate-700 uppercase">Left Profile</span>
                    </div>
                    <div class="w-full h-44 bg-white rounded-lg overflow-hidden border border-slate-300 flex items-center justify-center">
                        @if(!empty($case->mugshot_left))
                            <img src="{{ $getImgSrc($case->mugshot_left) }}" class="w-full h-full object-contain object-center" alt="Left Profile Mugshot">
                        @else
                            <div class="flex flex-col items-center justify-center text-slate-400 text-xs font-bold">
                                <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                <span>No Left Photo</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Right Profile -->
                <div class="p-2 bg-slate-50 border border-slate-300 rounded-xl text-center space-y-1.5 shadow-sm">
                    <div class="flex items-center justify-between text-[9px] font-mono-code font-bold">
                        <span class="px-1.5 py-0.5 rounded bg-slate-800 text-white">ANGLE 3</span>
                        <span class="text-slate-700 uppercase">Right Profile</span>
                    </div>
                    <div class="w-full h-44 bg-white rounded-lg overflow-hidden border border-slate-300 flex items-center justify-center">
                        @if(!empty($case->mugshot_right))
                            <img src="{{ $getImgSrc($case->mugshot_right) }}" class="w-full h-full object-contain object-center" alt="Right Profile Mugshot">
                        @else
                            <div class="flex flex-col items-center justify-center text-slate-400 text-xs font-bold">
                                <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                <span>No Right Photo</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- SUSPECT PERSONAL BIO DATA & IDENTITY -->
        <div class="space-y-3 avoid-break relative z-10">
            <div class="text-xs font-extrabold text-slate-900 uppercase font-mono-code border-b border-slate-300 pb-1">
                <span>SUSPECT BOOKING &amp; PERSONAL IDENTITY</span>
            </div>

            <table class="w-full text-xs border-collapse border border-slate-300 rounded-xl overflow-hidden">
                <tbody>
                    <tr class="bg-slate-100 border-b border-slate-300">
                        <td class="p-2.5 font-mono-code font-bold text-slate-600 uppercase border-r border-slate-300 w-1/4">Full Name:</td>
                        <td class="p-2.5 font-extrabold text-slate-900 w-1/4 border-r border-slate-300">{{ $case->suspect_name ?: 'N/A' }}</td>
                        <td class="p-2.5 font-mono-code font-bold text-slate-600 uppercase border-r border-slate-300 w-1/4">Alias / Street Name:</td>
                        <td class="p-2.5 font-bold text-slate-800 w-1/4">{{ $case->suspect_alias ?? ($docketData['alias'] ?? 'N/A') }}</td>
                    </tr>
                    <tr class="border-b border-slate-300">
                        <td class="p-2.5 font-mono-code font-bold text-slate-600 uppercase border-r border-slate-300">Ghana Card / ID #:</td>
                        <td class="p-2.5 font-mono-code font-bold text-blue-800 border-r border-slate-300">{{ $case->ghana_card ?? ($docketData['id_card'] ?? 'N/A') }}</td>
                        <td class="p-2.5 font-mono-code font-bold text-slate-600 uppercase border-r border-slate-300">Gender &amp; Age / DOB:</td>
                        <td class="p-2.5 font-bold text-slate-800">
                            {{ $case->gender ?? ($docketData['gender'] ?? 'N/A') }}
                            {{ !empty($case->age) ? "• Age: {$case->age}" : (!empty($docketData['age']) ? "• Age: {$docketData['age']}" : '') }}
                            {{ !empty($case->dob) ? "• DOB: {$case->dob}" : '' }}
                            {{ !empty($case->height) ? "• Height: {$case->height}" : (!empty($docketData['height']) ? "• Height: {$docketData['height']}" : '') }}
                            {{ !empty($case->eye_color) ? "• Eyes: {$case->eye_color}" : (!empty($docketData['eye_color']) ? "• Eyes: {$docketData['eye_color']}" : '') }}
                        </td>
                    </tr>
                    <tr class="bg-slate-100 border-b border-slate-300">
                        <td class="p-2.5 font-mono-code font-bold text-slate-600 uppercase border-r border-slate-300">Nationality:</td>
                        <td class="p-2.5 font-bold text-slate-800 border-r border-slate-300">{{ $case->nationality ?? ($docketData['nationality'] ?? 'Ghanaian') }}</td>
                        <td class="p-2.5 font-mono-code font-bold text-slate-600 uppercase border-r border-slate-300">Hometown / POB:</td>
                        <td class="p-2.5 font-bold text-slate-800">{{ $case->pob ?? ($docketData['pob'] ?? 'N/A') }}</td>
                    </tr>
                    <tr class="border-b border-slate-300">
                        <td class="p-2.5 font-mono-code font-bold text-slate-600 uppercase border-r border-slate-300">Contact Phone:</td>
                        <td class="p-2.5 font-bold text-slate-800 border-r border-slate-300">{{ $case->phone ?? ($docketData['phone'] ?? 'N/A') }}</td>
                        <td class="p-2.5 font-mono-code font-bold text-slate-600 uppercase border-r border-slate-300">Residential Address / GPS:</td>
                        <td class="p-2.5 font-bold text-slate-800">{{ $case->address ?? ($docketData['address'] ?? 'N/A') }}</td>
                    </tr>
                    <tr class="bg-slate-100">
                        <td class="p-2.5 font-mono-code font-bold text-slate-600 uppercase border-r border-slate-300">Marital Status &amp; Family:</td>
                        <td class="p-2.5 font-bold text-slate-800 border-r border-slate-300" colspan="3">
                            Status: {{ $case->marital_status ?? 'N/A' }} 
                            @if(!empty($case->spouse_name)) • Spouse: {{ $case->spouse_name }} ({{ $case->spouse_phone }}) @endif
                            @if(!empty($case->dependents)) • Dependents: {{ $case->dependents }} @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- OFFENCE CHARGES & CUSTODY DETAILS -->
        <div class="space-y-3 avoid-break relative z-10">
            <div class="text-xs font-extrabold text-slate-900 uppercase font-mono-code border-b border-slate-300 pb-1">
                <span>OFFENCE CHARGES &amp; ARREST DETAILS</span>
            </div>

            <div class="grid grid-cols-3 gap-3 text-xs">
                <div class="p-3 bg-slate-50 border border-slate-300 rounded-xl space-y-0.5">
                    <div class="text-[9px] font-mono-code font-bold text-slate-500 uppercase">Arrest / Raid Scene Location</div>
                    <div class="font-extrabold text-slate-900">{{ $case->raid_location ?: ($case->location ?: 'N/A') }}</div>
                </div>
                <div class="p-3 bg-slate-50 border border-slate-300 rounded-xl space-y-0.5">
                    <div class="text-[9px] font-mono-code font-bold text-slate-500 uppercase">Arrest Date &amp; Time</div>
                    <div class="font-bold text-slate-900">
                        {{ $case->arrest_date ? \Carbon\Carbon::parse($case->arrest_date)->format('M d, Y') : 'N/A' }} {{ $case->arrest_time ? "@ {$case->arrest_time}" : '' }}
                    </div>
                </div>
                <div class="p-3 bg-slate-50 border border-slate-300 rounded-xl space-y-0.5">
                    <div class="text-[9px] font-mono-code font-bold text-slate-500 uppercase">Assigned Custody Facility</div>
                    <div class="font-bold text-slate-900">{{ $case->holding_facility ?? 'N/A' }}</div>
                </div>
            </div>

            <div class="p-3.5 bg-slate-50 border border-slate-300 rounded-xl text-xs space-y-1">
                <div class="text-[9px] font-mono-code font-bold text-slate-500 uppercase">Brief Facts of Case &amp; Offence Description</div>
                <p class="font-semibold text-slate-800 leading-relaxed">
                    {{ $case->offense_description ?: ($case->description ?: 'N/A') }}
                </p>
            </div>
        </div>

        <!-- SEIZURES (COMMODITIES & VAULT INVENTORY) -->
        <div class="space-y-3 avoid-break relative z-10">
            <div class="text-xs font-extrabold text-slate-900 uppercase font-mono-code border-b border-slate-300 pb-1">
                <span>CONFISCATED COMMODITIES &amp; VAULT INVENTORY</span>
            </div>

            <div class="grid grid-cols-4 gap-3 text-xs">
                <div class="p-3 bg-slate-50 border border-slate-300 rounded-xl">
                    <div class="text-[9px] font-mono-code font-bold text-slate-500 uppercase">Exhibit Category</div>
                    <div class="font-extrabold font-mono-code text-amber-800 mt-0.5">{{ $case->exhibit_category ?? 'N/A' }}</div>
                </div>
                <div class="p-3 bg-slate-50 border border-slate-300 rounded-xl">
                    <div class="text-[9px] font-mono-code font-bold text-slate-500 uppercase">Primary Seizure Name</div>
                    <div class="font-extrabold text-slate-900 mt-0.5">{{ $case->seizure_name ?: ($case->exhibits_summary ?: 'N/A') }}</div>
                </div>
                <div class="p-3 bg-slate-50 border border-slate-300 rounded-xl">
                    <div class="text-[9px] font-mono-code font-bold text-slate-500 uppercase">Quantity / Weight</div>
                    <div class="font-extrabold font-mono-code text-slate-900 mt-0.5">
                        @if($case->seizure_quantity || $case->seizure_weight)
                            {{ implode(' • ', array_filter([$case->seizure_quantity ? "Qty: {$case->seizure_quantity}" : null, $case->seizure_weight ? "Wt: {$case->seizure_weight} {$case->seizure_unit}" : ($case->seizure_unit ? "Unit: {$case->seizure_unit}" : null)])) }}
                        @else
                            {{ $case->exhibits_summary ?: 'N/A' }}
                        @endif
                    </div>
                </div>
                <div class="p-3 bg-slate-50 border border-slate-300 rounded-xl">
                    <div class="text-[9px] font-mono-code font-bold text-slate-500 uppercase">Estimated Valuation</div>
                    <div class="font-extrabold font-mono-code text-emerald-700 mt-0.5">{{ $case->seizure_value ?: ($case->valuation ?? 'N/A') }}</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="p-3 bg-slate-50 border border-slate-300 rounded-xl">
                    <div class="text-[9px] font-mono-code font-bold text-slate-500 uppercase">Concealment Notes</div>
                    <div class="font-bold text-slate-900 mt-0.5">{{ $case->seizure_concealment ?: 'N/A' }}</div>
                </div>
                <div class="p-3 bg-slate-50 border border-slate-300 rounded-xl">
                    <div class="text-[9px] font-mono-code font-bold text-slate-500 uppercase">Seizing Officer</div>
                    <div class="font-bold text-slate-900 mt-0.5">{{ $case->seizure_officer ?: 'N/A' }}</div>
                </div>
            </div>

            @if(!empty($case->veh_make) || !empty($case->veh_reg_no) || !empty($docketData['vehicle']['make']) || !empty($docketData['vehicle']['reg']))
            <div class="p-3.5 bg-slate-900 text-white rounded-xl space-y-2 text-xs">
                <div class="flex items-center justify-between border-b border-slate-700 pb-1.5">
                    <span class="text-[10px] font-mono-code font-bold text-amber-400 uppercase">Seized Vehicle Specification Record</span>
                    <span class="font-mono-code text-[10px] font-bold text-emerald-400">REG: {{ $case->veh_reg_no ?: ($docketData['vehicle']['reg'] ?? 'N/A') }}</span>
                </div>
                <div class="grid grid-cols-4 gap-2 text-[11px]">
                    <div><span class="text-slate-400 block font-mono-code text-[9px]">Make/Model:</span> <span class="font-bold text-white">{{ $case->veh_make ?: ($docketData['vehicle']['make'] ?? 'N/A') }} {{ $case->veh_model ?? '' }}</span></div>
                    <div><span class="text-slate-400 block font-mono-code text-[9px]">Chassis/VIN:</span> <span class="font-mono-code font-bold text-slate-200">{{ $case->veh_vin ?: ($docketData['vehicle']['chassis'] ?? 'N/A') }}</span></div>
                    <div><span class="text-slate-400 block font-mono-code text-[9px]">Owner:</span> <span class="font-bold text-slate-200">{{ $case->veh_owner ?: ($docketData['vehicle']['owner'] ?? 'N/A') }}</span></div>
                    <div><span class="text-slate-400 block font-mono-code text-[9px]">Driver:</span> <span class="font-bold text-slate-200">{{ $case->veh_driver ?: ($docketData['vehicle']['driver'] ?? 'N/A') }}</span></div>
                </div>
            </div>
            @endif

            @if(!empty($case->wpn_model) || !empty($case->wpn_serial_no) || !empty($docketData['weapon']['model']))
            <div class="p-3.5 bg-slate-900 text-white rounded-xl space-y-2 text-xs">
                <div class="flex items-center justify-between border-b border-slate-700 pb-1.5">
                    <span class="text-[10px] font-mono-code font-bold text-red-400 uppercase">Seized Firearm / Weapon Specification Record</span>
                    <span class="font-mono-code text-[10px] font-bold text-emerald-400">SERIAL: {{ $case->wpn_serial_no ?: ($docketData['weapon']['serial_no'] ?? 'N/A') }}</span>
                </div>
                <div class="grid grid-cols-4 gap-2 text-[11px]">
                    <div><span class="text-slate-400 block font-mono-code text-[9px]">Model/Firearm:</span> <span class="font-bold text-white">{{ $case->wpn_model ?: ($docketData['weapon']['model'] ?? 'N/A') }}</span></div>
                    <div><span class="text-slate-400 block font-mono-code text-[9px]">Calibre/Gauge:</span> <span class="font-bold text-slate-200">{{ $case->wpn_calibre ?: ($docketData['weapon']['calibre'] ?? 'N/A') }}</span></div>
                    <div><span class="text-slate-400 block font-mono-code text-[9px]">Loaded Status:</span> <span class="font-bold text-slate-200">{{ $case->wpn_loaded_status ?: ($docketData['weapon']['loaded_status'] ?? 'N/A') }}</span></div>
                    <div><span class="text-slate-400 block font-mono-code text-[9px]">Condition:</span> <span class="font-bold text-slate-200">{{ $case->wpn_condition ?: ($docketData['weapon']['condition'] ?? 'N/A') }}</span></div>
                    <div><span class="text-slate-400 block font-mono-code text-[9px]">Colour/Size:</span> <span class="font-bold text-slate-200">{{ $case->wpn_colour ?: ($docketData['weapon']['colour'] ?? 'N/A') }} {{ $case->wpn_size ? "({$case->wpn_size})" : '' }}</span></div>
                    <div><span class="text-slate-400 block font-mono-code text-[9px]">Location Found:</span> <span class="font-bold text-slate-200">{{ $case->wpn_location_found ?: ($docketData['weapon']['location_found'] ?? 'N/A') }}</span></div>
                    <div class="col-span-2"><span class="text-slate-400 block font-mono-code text-[9px]">Marks/Features:</span> <span class="font-semibold text-slate-200">{{ $case->wpn_marks ?: ($docketData['weapon']['marks'] ?? 'None') }}</span></div>
                </div>
            </div>
            @endif
        </div>

        <!-- EVIDENCE PHOTO GALLERY (IF AVAILABLE) -->
        @if(!empty($evidencePhotos) && count($evidencePhotos) > 0)
        <div class="space-y-3 avoid-break relative z-10">
            <div class="text-xs font-extrabold text-slate-900 uppercase font-mono-code border-b border-slate-300 pb-1 flex items-center justify-between">
                <div>
                    <span>SEIZURE &amp; EXHIBIT EVIDENCE GALLERY</span>
                </div>
                <span class="text-[10px] font-mono-code font-bold text-slate-600">{{ count($evidencePhotos) }} ATTACHED EXHIBITS</span>
            </div>

            <div class="grid grid-cols-4 gap-3">
                @foreach($evidencePhotos as $idx => $photoUrl)
                    @if(!empty($photoUrl))
                    <div class="p-1.5 bg-slate-50 border border-slate-300 rounded-xl space-y-1 text-center">
                        <div class="w-full h-28 bg-slate-200 rounded-lg overflow-hidden border border-slate-300">
                            <img src="{{ $photoUrl }}" class="w-full h-full object-cover" alt="Exhibit Photo {{ $idx + 1 }}">
                        </div>
                        <div class="text-[8px] font-mono-code font-bold text-slate-600 uppercase">Exhibit Photo #{{ $idx + 1 }}</div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
        @endif

        <!-- EXECUTIVE CERTIFICATION & OFFICIAL SIGN-OFF -->
        <div class="pt-4 border-t-2 border-slate-900 space-y-4 avoid-break relative z-10">
            <div class="text-xs font-extrabold text-slate-900 uppercase font-mono-code">
                <span>EXECUTIVE AUTHORIZATION &amp; CERTIFICATE OF ACCURACY</span>
            </div>

            <div class="p-4 bg-slate-50 border border-slate-300 rounded-2xl">
                <!-- SIGNATURE BLOCK -->
                <div class="space-y-3 text-xs">
                    <div>
                        <div class="text-[9px] font-mono-code font-bold text-slate-500 uppercase">Authorized Executive Sign-off:</div>
                        <div class="border-b-2 border-slate-900 pb-1 mt-4 flex items-end justify-between">
                            <span class="font-extrabold text-slate-900 uppercase text-sm font-mono-code">{{ $officerName }}</span>
                            <span class="text-[10px] font-mono-code font-bold text-slate-500">SIGNATURE</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-[10px] font-mono-code text-slate-600 font-semibold">
                        <div>TITLE: <strong class="text-slate-900">{{ strtoupper($officerRole) }}</strong></div>
                        <div>DATE: <strong class="text-slate-900">{{ date('d F Y') }}</strong></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FOOTER SECURITY BAR -->
        <div class="pt-2 text-center border-t border-slate-300 text-[9px] font-mono-code text-slate-500 flex items-center justify-between relative z-10">
            <div>NACOC FORENSIC DEPARTMENT • CONFIDENTIAL LAW ENFORCEMENT RECORD</div>
            <div>PAGE 1 OF 1 • A4 OFFICIAL FORMAT</div>
        </div>

    </div>

</body>
</html>
