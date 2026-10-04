<?php

$path = __DIR__ . '/../resources/views/cases/show_review.blade.php';
$content = file_get_contents($path);

// 1. In Primary Suspect Exhibits block, replace $aEx in electronic device card with $pEx
$posPrimaryHeader = strpos($content, 'PRIMARY SUSPECT EXHIBIT DOSSIER');
if ($posPrimaryHeader !== false) {
    $before = substr($content, 0, $posPrimaryHeader);
    $after = substr($content, $posPrimaryHeader);

    $afterReplaced = str_replace(
        [
            '$dModel = $aEx[\'dev_model\']',
            '$dSerial = $aEx[\'dev_serial_no\']',
            '$dImei1 = $aEx[\'dev_imei1\']',
            '$dImei2 = $aEx[\'dev_imei2\']',
            '$dMac = $aEx[\'dev_mac_address\']',
            '$dStorage = $aEx[\'dev_storage\']',
            '$dColor = $aEx[\'dev_colour\']',
            '$dMarks = $aEx[\'dev_marks\']',
            '($aEx[\'seizure_name\'] ?? ($aEx[\'commodity_name\'] ?? \'Electronic Device Exhibit\'))'
        ],
        [
            '$dModel = $pEx[\'dev_model\']',
            '$dSerial = $pEx[\'dev_serial_no\']',
            '$dImei1 = $pEx[\'dev_imei1\']',
            '$dImei2 = $pEx[\'dev_imei2\']',
            '$dMac = $pEx[\'dev_mac_address\']',
            '$dStorage = $pEx[\'dev_storage\']',
            '$dColor = $pEx[\'dev_colour\']',
            '$dMarks = $pEx[\'dev_marks\']',
            '($pEx[\'seizure_name\'] ?? ($pEx[\'commodity_name\'] ?? \'Electronic Device Exhibit\'))'
        ],
        $after
    );
    $content = $before . $afterReplaced;
    echo "Updated Primary Suspect Exhibits device card variables to \$pEx\n";
}

// 2. Add Associate Suspect Exhibits device card before ammunition block in Associate Suspect Exhibits section
$assocTarget = '$aAmmo = $aEx[\'ammunition_details\'] ?? null;';
$deviceCardAssocHtml = <<<HTML
@if(\$isDevEx)
                                     @php
                                         \$dModel = \$aEx['dev_model'] ?? (\$aEx['device_model'] ?? 'N/A');
                                         \$dSerial = \$aEx['dev_serial_no'] ?? (\$aEx['device_serial'] ?? (\$aEx['serial_number'] ?? 'N/A'));
                                         \$dImei1 = \$aEx['dev_imei1'] ?? (\$aEx['dev_imei_1'] ?? (\$aEx['imei1'] ?? 'N/A'));
                                         \$dImei2 = \$aEx['dev_imei2'] ?? (\$aEx['dev_imei_2'] ?? (\$aEx['imei2'] ?? 'N/A'));
                                         \$dMac = \$aEx['dev_mac_address'] ?? (\$aEx['mac_address'] ?? 'N/A');
                                         \$dStorage = \$aEx['dev_storage'] ?? (\$aEx['dev_storage_capacity'] ?? (\$aEx['storage'] ?? 'N/A'));
                                         \$dColor = \$aEx['dev_colour'] ?? (\$aEx['dev_color'] ?? (\$aEx['colour'] ?? 'N/A'));
                                         \$dMarks = \$aEx['dev_marks'] ?? (\$aEx['dev_other_marks'] ?? (\$aEx['marks'] ?? 'N/A'));
                                     @endphp
                                     <div class="col-span-full bg-slate-900 text-white p-4 rounded-xl border border-blue-500/40 space-y-3 mt-2 shadow-md">
                                         <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                                             <div class="flex items-center space-x-2">
                                                 <span class="px-2 py-0.5 bg-blue-600 text-white font-mono-code font-extrabold text-[10px] rounded uppercase tracking-wider">ELECTRONIC DEVICE FORENSIC SPECIFICATIONS</span>
                                                 <span class="text-xs font-black text-slate-100 font-mono-code">{{ strtoupper(\$dModel !== 'N/A' ? \$dModel : (\$aEx['seizure_name'] ?? (\$aEx['commodity_name'] ?? 'Electronic Device Exhibit'))) }}</span>
                                             </div>
                                             <div class="flex items-center gap-2">
                                                 @if(!empty(\$dSerial) && \$dSerial !== 'N/A')
                                                     <span class="text-[10px] font-mono-code bg-slate-800 px-2.5 py-1 rounded text-amber-400 font-extrabold border border-amber-500/30">SERIAL #: {{ strtoupper(\$dSerial) }}</span>
                                                 @endif
                                                 @if(!empty(\$dImei1) && \$dImei1 !== 'N/A')
                                                     <span class="text-[10px] font-mono-code bg-slate-800 px-2.5 py-1 rounded text-blue-300 font-extrabold border border-blue-500/30">IMEI 1: {{ strtoupper(\$dImei1) }}</span>
                                                 @endif
                                             </div>
                                         </div>

                                         <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                                             <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                 <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">MODEL / MAKE</div>
                                                 <div class="font-black text-white text-xs mt-0.5">{{ \$dModel }}</div>
                                             </div>

                                             <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                 <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">SERIAL NUMBER</div>
                                                 <div class="font-extrabold text-amber-400 font-mono-code text-xs mt-0.5">{{ \$dSerial }}</div>
                                             </div>

                                             <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                 <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">IMEI 1</div>
                                                 <div class="font-extrabold text-blue-300 font-mono-code text-xs mt-0.5">{{ \$dImei1 }}</div>
                                             </div>

                                             <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                 <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">IMEI 2</div>
                                                 <div class="font-extrabold text-blue-300 font-mono-code text-xs mt-0.5">{{ \$dImei2 }}</div>
                                             </div>

                                             <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                 <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">MAC ADDRESS</div>
                                                 <div class="font-mono-code font-bold text-slate-200 text-xs mt-0.5">{{ \$dMac }}</div>
                                             </div>

                                             <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                 <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">STORAGE CAPACITY</div>
                                                 <div class="font-bold text-emerald-400 text-xs mt-0.5">{{ \$dStorage }}</div>
                                             </div>

                                             <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                 <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">COLOUR / FINISH</div>
                                                 <div class="font-bold text-slate-200 text-xs mt-0.5">{{ \$dColor }}</div>
                                             </div>

                                             <div class="bg-slate-800/90 p-2.5 rounded-lg border border-slate-700">
                                                 <div class="text-[9px] font-mono-code font-extrabold text-slate-400 uppercase">OTHER MARKS / CONDITION</div>
                                                 <div class="font-bold text-slate-200 text-xs mt-0.5">{{ \$dMarks }}</div>
                                             </div>
                                         </div>
                                     </div>
                                     @endif

                                     @php
                                         \$aAmmo = \$aEx['ammunition_details'] ?? null;
HTML;

if (strpos($content, '$aEx[\'ammunition_details\']') !== false && substr_count($content, '@if($isDevEx)') === 1) {
    $content = str_replace('$aAmmo = $aEx[\'ammunition_details\'] ?? null;', $deviceCardAssocHtml, $content);
    echo "Added Associate Suspect Exhibits device card UI\n";
}

file_put_contents($path, $content);
echo "Updated show_review.blade.php successfully\n";
