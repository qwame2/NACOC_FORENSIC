@php
    $authUser = auth()->user();
    $sidebarUnit = $sidebarUnit ?? ($unitContext['sidebar'] ?? 'Crime Scene Support');
    $unitKey = $unitContext['key'] ?? 'crime_scene_support';
    $backUrl = match($unitKey) {
        'lab_analysis' => url('/Lab_Analysis/staff/case-rollback'),
        'evidence_management' => url('/Evidence_Management/staff/case-rollback'),
        'digital_forensics' => url('/Digital_Forensics/staff/case-rollback'),
        'forensic_head' => url('/Forensic_Head/staff/case-rollback'),
        default => url('/Crime_Scene_Support/staff/case-rollback'),
    };

    $corrections = is_array($corrections) ? $corrections : [];
    $payload = is_array($payload) ? $payload : [];

    // Helper to find correction note for any field label/keyword
    $getCorrection = function(...$keywords) use ($corrections) {
        foreach ($corrections as $c) {
            $f = strtolower($c['field'] ?? '');
            foreach ($keywords as $kw) {
                if (str_contains($f, strtolower($kw))) {
                    return $c['note'] ?? 'Correction requested by Unit Head.';
                }
            }
        }
        return null;
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edit &amp; Correct Rolled Back Case | SUB-{{ str_pad((string)$submission->id, 4, '0', STR_PAD_LEFT) }} | NACOC Forensic</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    @endif

    <style>
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background-color: #F8FAFC; color: #0F172A; }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #F1F5F9; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94A3B8; }
        .correction-highlight {
            border: 2px solid #f59e0b !important;
            background-color: #fffbeb !important;
        }
    </style>
</head>
<body class="min-h-screen flex antialiased bg-slate-50 text-slate-900 selection:bg-amber-600 selection:text-white">

    <div class="flex h-screen w-full overflow-hidden">
        {{-- STANDALONE STAFF SIDEBAR --}}
        @include('staff.partials.sidebar')

        <div class="flex-1 flex flex-col min-w-0 bg-slate-50 overflow-hidden">

            <!-- MODERN FLOATING TOPBAR -->
            <header class="sticky top-2.5 z-40 mx-4 mt-2 mb-1 px-5 py-3 rounded-2xl bg-white/95 backdrop-blur-md border border-slate-200/90 shadow-[0_10px_25px_-5px_rgba(15,23,42,0.08),0_4px_10px_-2px_rgba(15,23,42,0.04)] flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <a href="{{ $backUrl }}" class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition font-bold text-xs flex items-center gap-1.5 text-decoration-none">
                        <span>&larr;</span>
                        <span class="hidden sm:inline">Back to Rollback List</span>
                    </a>
                    <span class="text-slate-300">|</span>
                    <div>
                        <h1 class="text-sm font-black text-slate-900 tracking-tight flex items-center gap-2">
                            <span>Fix Case Corrections</span>
                            <span class="font-mono-code text-amber-700 text-xs">SUB-{{ str_pad((string)$submission->id, 4, '0', STR_PAD_LEFT) }}</span>
                        </h1>
                        <p class="text-[11px] text-slate-500 font-medium">Review Unit Head directives, update details, and resubmit</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 rounded-full text-xs font-mono-code font-bold bg-green-200 text-green-950 border-none">
                        {{ count($corrections) }} Correction{{ count($corrections) === 1 ? '' : 's' }} Required
                    </span>
                </div>
            </header>

            <!-- MAIN CONTENT AREA -->
            <main class="flex-1 overflow-y-auto p-4 md:p-6 space-y-6">
                @include('partials.flash')

                <!-- ROLLBACK DIRECTIVE CALLOUT -->
                <div class="rounded-3xl bg-green-100 border-none p-6 sm:p-7 shadow-sm space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-green-200/60">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-green-700 text-white flex items-center justify-center font-black shrink-0 shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                            </div>
                            <div>
                                <span class="px-2.5 py-0.5 rounded text-[10px] font-mono-code font-black bg-green-200 text-green-950 uppercase tracking-wider border-none">
                                    CASE ROLLED BACK BY UNIT HEAD
                                </span>
                                <h2 class="text-base font-black text-green-950 mt-0.5">
                                    {{ $submission->reviewed_by_name ?? 'Unit Head' }} requested changes before approval
                                </h2>
                                <p class="text-xs text-green-800">
                                    Rolled back on {{ optional($submission->rolled_back_at ?? $submission->reviewed_at)->format('d M Y, H:i') }} <span class="text-green-600 mx-1">|</span> Submitter: {{ $submission->submitted_by_name }}
                                </p>
                            </div>
                        </div>

                        <span class="px-3 py-1 bg-green-200 text-green-950 rounded-full font-mono-code font-bold text-xs shrink-0 self-start sm:self-auto border-none">
                            Status: Rolled Back
                        </span>
                    </div>

                    @if(!empty($submission->review_notes))
                        <div class="p-3.5 bg-white/90 rounded-2xl border-none text-xs">
                            <span class="text-[10px] font-mono-code font-bold uppercase text-green-800 block mb-1">Overall Unit Head Feedback:</span>
                            <p class="text-slate-900 font-semibold leading-relaxed">{{ $submission->review_notes }}</p>
                        </div>
                    @endif

                    <!-- CHECKLIST OF REQUESTED CORRECTIONS -->
                    @if(count($corrections) > 0)
                        <div class="space-y-2">
                            <div class="text-xs font-black text-green-950 uppercase tracking-wider font-mono-code flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                <span>Specific Sections Requiring Your Correction:</span>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($corrections as $idx => $c)
                                    <div class="p-3 rounded-xl bg-white border-none shadow-2xs space-y-1">
                                        <div class="flex items-center justify-between font-mono-code text-[11px] font-black text-green-950">
                                            <span>#{{ $idx + 1 }}. {{ $c['field'] ?? 'Suspect Detail' }}</span>
                                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-green-200 text-green-950 border-none font-bold">Action Required</span>
                                        </div>
                                        <p class="text-slate-800 text-xs font-medium leading-relaxed">
                                            {{ $c['note'] ?? 'Please correct and verify this detail.' }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- EDIT / CORRECTION FORM -->
                <form method="POST" action="{{ url('/staff/case-rollback/' . $submission->id . '/resubmit') }}" class="space-y-6">
                    @csrf

                    <!-- SECTION 1: CASE CLASSIFICATION & TITLE -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div>
                                <h3 class="text-sm font-black text-slate-900">Case Title &amp; Classification</h3>
                                <p class="text-xs text-slate-500">Legal reference and case classification</p>
                            </div>
                            <span class="px-2.5 py-1 rounded text-xs font-mono-code font-bold bg-slate-100 text-slate-700">
                                {{ $submission->unit_name }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="sm:col-span-2">
                                @if($note = $getCorrection('case title', 'legal title', 'case name'))
                                    <div class="mb-1 p-2 bg-green-100 border-none rounded-lg text-green-950 text-xs font-semibold">
                                        ⚠️ <strong>Head Note:</strong> {{ $note }}
                                    </div>
                                @endif
                                <label class="block text-xs font-bold text-slate-700 mb-1">Case Title / Legal Name</label>
                                <input type="text" name="title" value="{{ old('title', $submission->title ?? ($payload['title'] ?? '')) }}"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold focus:bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            </div>

                            <div>
                                @if($note = $getCorrection('priority'))
                                    <div class="mb-1 p-2 bg-green-100 border-none rounded-lg text-green-950 text-xs font-semibold">
                                        ⚠️ <strong>Head Note:</strong> {{ $note }}
                                    </div>
                                @endif
                                <label class="block text-xs font-bold text-slate-700 mb-1">Priority</label>
                                <select name="priority" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold focus:bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                    @php $currPri = strtoupper($submission->priority ?? ($payload['priority'] ?? 'MEDIUM')); @endphp
                                    <option value="LOW" {{ $currPri === 'LOW' ? 'selected' : '' }}>LOW</option>
                                    <option value="MEDIUM" {{ $currPri === 'MEDIUM' ? 'selected' : '' }}>MEDIUM</option>
                                    <option value="HIGH" {{ $currPri === 'HIGH' ? 'selected' : '' }}>HIGH</option>
                                    <option value="CRITICAL" {{ $currPri === 'CRITICAL' ? 'selected' : '' }}>CRITICAL</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: SUSPECT LEGAL IDENTITY -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
                        <div class="pb-3 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-black text-slate-900">Suspect Bio-Data &amp; Legal Identity</h3>
                                <p class="text-xs text-slate-500">Legal names, nationality, identification card, and contact info</p>
                            </div>
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-mono-code font-black bg-blue-100 text-blue-800 uppercase">
                                PRIMARY SUSPECT
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                            <!-- Suspect Full Name -->
                            <div class="sm:col-span-2">
                                @if($note = $getCorrection('suspect full name', 'suspect name', 'legal name'))
                                    <div class="mb-1 p-2 bg-green-100 border-none rounded-lg text-green-950 text-xs font-semibold">
                                        ⚠️ <strong>Head Note:</strong> {{ $note }}
                                    </div>
                                @endif
                                <label class="block font-bold text-slate-700 mb-1">Suspect Full Name <span class="text-rose-500">*</span></label>
                                <input type="text" name="suspect_name" required value="{{ old('suspect_name', $submission->suspect_name ?? ($payload['suspect_name'] ?? '')) }}"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            </div>

                            <!-- Alias -->
                            <div>
                                @if($note = $getCorrection('alias', 'street name'))
                                    <div class="mb-1 p-2 bg-green-100 border-none rounded-lg text-green-950 text-xs font-semibold">
                                        ⚠️ <strong>Head Note:</strong> {{ $note }}
                                    </div>
                                @endif
                                <label class="block font-bold text-slate-700 mb-1">Alias / Street Name</label>
                                <input type="text" name="suspect_alias" value="{{ old('suspect_alias', $payload['suspect_alias'] ?? '') }}"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-semibold focus:bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            </div>

                            <!-- Ghana Card -->
                            <div>
                                @if($note = $getCorrection('ghana card', 'card #', 'national id'))
                                    <div class="mb-1 p-2 bg-green-100 border-none rounded-lg text-green-950 text-xs font-semibold">
                                        ⚠️ <strong>Head Note:</strong> {{ $note }}
                                    </div>
                                @endif
                                <label class="block font-bold text-slate-700 mb-1">Ghana Card #</label>
                                <input type="text" name="ghana_card" value="{{ old('ghana_card', $payload['ghana_card'] ?? '') }}"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-mono-code font-bold focus:bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            </div>

                            <!-- Nationality -->
                            <div>
                                @if($note = $getCorrection('nationality', 'citizen'))
                                    <div class="mb-1 p-2 bg-green-100 border-none rounded-lg text-green-950 text-xs font-semibold">
                                        ⚠️ <strong>Head Note:</strong> {{ $note }}
                                    </div>
                                @endif
                                <label class="block font-bold text-slate-700 mb-1">Nationality</label>
                                <input type="text" name="nationality" value="{{ old('nationality', $payload['nationality'] ?? 'Ghanaian') }}"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-semibold focus:bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            </div>

                            <!-- Hometown / POB -->
                            <div>
                                @if($note = $getCorrection('hometown', 'pob', 'place of birth'))
                                    <div class="mb-1 p-2 bg-green-100 border-none rounded-lg text-green-950 text-xs font-semibold">
                                        ⚠️ <strong>Head Note:</strong> {{ $note }}
                                    </div>
                                @endif
                                <label class="block font-bold text-slate-700 mb-1">Hometown / POB</label>
                                <input type="text" name="pob" value="{{ old('pob', $payload['pob'] ?? '') }}"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-semibold focus:bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            </div>

                            <!-- Phone -->
                            <div>
                                @if($note = $getCorrection('phone', 'contact'))
                                    <div class="mb-1 p-2 bg-green-100 border-none rounded-lg text-green-950 text-xs font-semibold">
                                        ⚠️ <strong>Head Note:</strong> {{ $note }}
                                    </div>
                                @endif
                                <label class="block font-bold text-slate-700 mb-1">Primary Phone</label>
                                <input type="text" name="phone" value="{{ old('phone', $payload['phone'] ?? '') }}"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-mono-code font-bold focus:bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            </div>

                            <!-- Address -->
                            <div>
                                @if($note = $getCorrection('address', 'gps', 'residential'))
                                    <div class="mb-1 p-2 bg-green-100 border-none rounded-lg text-green-950 text-xs font-semibold">
                                        ⚠️ <strong>Head Note:</strong> {{ $note }}
                                    </div>
                                @endif
                                <label class="block font-bold text-slate-700 mb-1">Residential Address / GPS</label>
                                <input type="text" name="address" value="{{ old('address', $payload['address'] ?? ($payload['location'] ?? '')) }}"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-semibold focus:bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            </div>

                            <!-- Gender -->
                            <div>
                                @if($note = $getCorrection('gender', 'sex'))
                                    <div class="mb-1 p-2 bg-green-100 border-none rounded-lg text-green-950 text-xs font-semibold">
                                        ⚠️ <strong>Head Note:</strong> {{ $note }}
                                    </div>
                                @endif
                                <label class="block font-bold text-slate-700 mb-1">Gender</label>
                                <select name="gender" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-semibold focus:bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                    @php $currGen = strtoupper($payload['gender'] ?? 'MALE'); @endphp
                                    <option value="MALE" {{ $currGen === 'MALE' ? 'selected' : '' }}>MALE</option>
                                    <option value="FEMALE" {{ $currGen === 'FEMALE' ? 'selected' : '' }}>FEMALE</option>
                                    <option value="OTHER" {{ $currGen === 'OTHER' ? 'selected' : '' }}>OTHER</option>
                                </select>
                            </div>

                            <!-- Age / DOB -->
                            <div>
                                @if($note = $getCorrection('age', 'dob', 'birth'))
                                    <div class="mb-1 p-2 bg-green-100 border-none rounded-lg text-green-950 text-xs font-semibold">
                                        ⚠️ <strong>Head Note:</strong> {{ $note }}
                                    </div>
                                @endif
                                <label class="block font-bold text-slate-700 mb-1">Age / DOB</label>
                                <input type="text" name="age" value="{{ old('age', $payload['age'] ?? '') }}" placeholder="e.g. 34"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-semibold focus:bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            </div>

                            <!-- Height -->
                            <div>
                                @if($note = $getCorrection('height', 'stature'))
                                    <div class="mb-1 p-2 bg-green-100 border-none rounded-lg text-green-950 text-xs font-semibold">
                                        ⚠️ <strong>Head Note:</strong> {{ $note }}
                                    </div>
                                @endif
                                <label class="block font-bold text-slate-700 mb-1">Height (e.g. 5'10")</label>
                                <input type="text" name="height" value="{{ old('height', $payload['height'] ?? '') }}"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-semibold focus:bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            </div>

                            <!-- Complexion -->
                            <div>
                                @if($note = $getCorrection('complexion', 'skin'))
                                    <div class="mb-1 p-2 bg-green-100 border-none rounded-lg text-green-950 text-xs font-semibold">
                                        ⚠️ <strong>Head Note:</strong> {{ $note }}
                                    </div>
                                @endif
                                <label class="block font-bold text-slate-700 mb-1">Complexion</label>
                                <input type="text" name="complexion" value="{{ old('complexion', $payload['complexion'] ?? '') }}" placeholder="e.g. Dark, Fair"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-semibold focus:bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: ARREST & CUSTODY DETAILS -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
                        <div class="pb-3 border-b border-slate-100">
                            <h3 class="text-sm font-black text-slate-900">Arrest &amp; Custodial Information</h3>
                            <p class="text-xs text-slate-500">Arrest date, location, facility, and arresting officers</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
                            <!-- Location -->
                            <div>
                                @if($note = $getCorrection('arrest location', 'raid location', 'location'))
                                    <div class="mb-1 p-2 bg-green-100 border-none rounded-lg text-green-950 text-xs font-semibold">
                                        ⚠️ <strong>Head Note:</strong> {{ $note }}
                                    </div>
                                @endif
                                <label class="block font-bold text-slate-700 mb-1">Arrest / Raid Location</label>
                                <input type="text" name="raid_location" value="{{ old('raid_location', $payload['raid_location'] ?? ($payload['location'] ?? '')) }}"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-semibold focus:bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            </div>

                            <!-- Holding Facility -->
                            <div>
                                @if($note = $getCorrection('facility', 'custody', 'cell', 'holding'))
                                    <div class="mb-1 p-2 bg-green-100 border-none rounded-lg text-green-950 text-xs font-semibold">
                                        ⚠️ <strong>Head Note:</strong> {{ $note }}
                                    </div>
                                @endif
                                <label class="block font-bold text-slate-700 mb-1">Holding Facility / Cell</label>
                                <input type="text" name="holding_facility" value="{{ old('holding_facility', $payload['holding_facility'] ?? '') }}"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-semibold focus:bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            </div>

                            <!-- Arresting Officer -->
                            <div>
                                @if($note = $getCorrection('arresting officer'))
                                    <div class="mb-1 p-2 bg-green-100 border-none rounded-lg text-green-950 text-xs font-semibold">
                                        ⚠️ <strong>Head Note:</strong> {{ $note }}
                                    </div>
                                @endif
                                <label class="block font-bold text-slate-700 mb-1">Arresting Officer</label>
                                <input type="text" name="arresting_officer" value="{{ old('arresting_officer', $payload['arresting_officer'] ?? '') }}"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-semibold focus:bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            </div>

                            <!-- Offense Description -->
                            <div class="sm:col-span-2 lg:col-span-3">
                                @if($note = $getCorrection('offense', 'charge', 'description of exhibits', 'narrative'))
                                    <div class="mb-1 p-2 bg-green-100 border-none rounded-lg text-green-950 text-xs font-semibold">
                                        ⚠️ <strong>Head Note:</strong> {{ $note }}
                                    </div>
                                @endif
                                <label class="block font-bold text-slate-700 mb-1">Offense Narrative &amp; Legal Charges</label>
                                <textarea name="offense_description" rows="3"
                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium focus:bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none">{{ old('offense_description', $payload['offense_description'] ?? ($payload['description'] ?? '')) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 4: RESUBMISSION NOTES & ACTION -->
                    <div class="rounded-3xl bg-white border-2 border-slate-200 p-6 sm:p-7 shadow-sm space-y-4">
                        <div class="flex items-center gap-2.5 pb-2 border-b border-slate-100">
                            <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-slate-900">Resubmission to Unit Head</h3>
                                <p class="text-xs text-slate-500">Provide a summary of the corrections made to help your Head verify the changes</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Staff Correction Summary / Remarks (Optional)</label>
                            <textarea name="resubmit_notes" rows="2.5" placeholder="e.g. Corrected suspect Ghana card number, verified primary phone, and updated residential address per Unit Head directive..."
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none"></textarea>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-3 border-t border-slate-100">
                            <a href="{{ $backUrl }}" class="p-2.5 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-100 text-xs font-bold text-center text-decoration-none">
                                Cancel &amp; Return
                            </a>

                            <button type="submit" class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-lg shadow-emerald-600/25 transition-all hover:scale-[1.02] flex items-center justify-center gap-2 cursor-pointer border-none">
                                <svg class="w-4 h-4 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                                <span>Resubmit Case for Unit Head Approval</span>
                            </button>
                        </div>
                    </div>

                </form>

            </main>
        </div>
    </div>

</body>
</html>

