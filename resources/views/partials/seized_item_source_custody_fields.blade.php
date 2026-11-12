{{-- Shared Seized Item source & custody fields so all unit forms render Source of Exhibits, Investigating Officer, and Source/Requesting Unit consistently once per block --}}
@if($showSourceOfExhibits ?? true)
<!-- SOURCE OF EXHIBITS -->
<div class="col-span-full md:col-span-2">
    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
        Source of Exhibits @if(!empty($sourceRequired))<span class="text-red-600 font-bold">*</span>@endif
    </label>
    <div class="relative flex items-center group">
        <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors pointer-events-none">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
        </span>
        <input type="text" id="newSeizureSourceOfExhibits" name="source_of_exhibits" @if(!empty($sourceRequired)) required @endif
            placeholder="e.g. Tema Port Warehouse, Vessel Compartment, Suspect Residence..."
            class="seizure-source-of-exhibits-input w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
    </div>
</div>
@endif

@if($showCustodyOfficerFields ?? true)
<!-- INVESTIGATING OFFICER -->
<div class="col-span-full md:col-span-2">
    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
        Investigating Officer
    </label>
    <div class="relative flex items-center group">
        <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors pointer-events-none">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </span>
        <input type="text" id="newSeizureInvestigatingOfficer" name="investigating_officer"
            placeholder="e.g. Insp. Emmanuel Mensah / Service ID SD-0199"
            class="seizure-investigating-officer-input w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
    </div>
</div>

<!-- SOURCE / REQUESTING UNIT -->
<div class="col-span-full md:col-span-2">
    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1">
        Source/Requesting Unit
    </label>
    <div class="relative flex items-center group">
        <span class="absolute left-3 text-slate-400 group-focus-within:text-black transition-colors pointer-events-none">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
        </span>
        <input type="text" id="newSeizureSourceRequestingUnit" name="source_requesting_unit"
            placeholder="e.g. Enforcement and Operations Unit, KIA Command, DLEU..."
            class="seizure-source-requesting-unit-input w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 placeholder:text-slate-400">
    </div>
</div>
@endif

