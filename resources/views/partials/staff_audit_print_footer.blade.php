@php
    $printAuthUser = $printAuthUser ?? auth()->user();
    $printGeneratedBy = $printGeneratedBy ?? (($printAuthUser?->fullname) ? ($printAuthUser->fullname . ' (' . ($printAuthUser->service_id ?? 'Unit Head') . ')') : 'Authorized Forensic Unit Head');
@endphp

<!-- OFFICIAL NACOC PRINT-ONLY SIGN-OFF FOOTER -->
<div class="mt-6 pt-3 border-t-2 border-slate-900 page-break-inside-avoid print-footer-container">
    <div class="grid grid-cols-3 gap-6 text-[8pt] font-mono-code mb-3">
        <div>
            <span class="text-[7pt] font-bold text-slate-500 uppercase block mb-1">Generated &amp; Certified By:</span>
            <div class="font-black text-slate-950">{{ $printGeneratedBy }}</div>
            <div class="text-slate-600 text-[7.5pt]">Head of Unit / Oversight Officer</div>
            <div class="mt-4 border-b border-dashed border-slate-400 w-48"></div>
            <span class="text-[7pt] text-slate-500 uppercase">Officer Signature &amp; Date</span>
        </div>
        <div>
            <span class="text-[7pt] font-bold text-slate-500 uppercase block mb-1">Directorate Verification:</span>
            <div class="font-black text-slate-950">Forensic Services Directorate</div>
            <div class="text-slate-600 text-[7.5pt]">Command &amp; Standards Compliance</div>
            <div class="mt-4 border-b border-dashed border-slate-400 w-48"></div>
            <span class="text-[7pt] text-slate-500 uppercase">Directorate Seal / Signature</span>
        </div>
        <div class="text-right flex flex-col justify-between items-end">
            <div>
                <span class="text-[7pt] font-bold text-slate-500 uppercase block mb-1">Official Document Seal</span>
                <div class="w-24 h-14 border-2 border-slate-300 rounded-lg flex items-center justify-center text-[7pt] font-black text-slate-400 uppercase tracking-widest text-center px-1">
                    NACOC SEAL
                </div>
            </div>
            <div class="text-[7.5pt] text-slate-600 font-bold mt-1">
                Generated: {{ now()->format('d/m/Y H:i:s') }}
            </div>
        </div>
    </div>

    <div class="pt-2 border-t border-slate-200 flex items-center justify-between text-[7pt] text-slate-500 font-medium">
        <span>NARCOTICS CONTROL COMMISSION ACT, 2020 (ACT 1019) &bull; IMMUTABLE SYSTEM AUDIT TRAIL</span>
        <span>CONFIDENTIAL &bull; FOR INTERNAL LAW ENFORCEMENT AUDIT &amp; COURT PROCEEDINGS ONLY</span>
        <span>Page &mdash; Official Archive Copy</span>
    </div>
</div>

