<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;

$case = ForensicCase::where('case_number', 'CSS-2026-2209')->first();
$docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data, true) ?: []);

echo "--- TESTING PRINCILLA ADDO EXHIBIT FILTER ---\n";
$sName = 'Princilla Addo';
$sId   = 'ASC-003';

$assocExhibits = array_filter($docketData['seizures'] ?? [], function($sz) use ($sName, $sId, $docketData) {
    $szAssocId = $sz['associate_id'] ?? ($sz['suspect_id'] ?? null);
    if (!empty($szAssocId)) {
        return !empty($sId) && (strtolower(trim($szAssocId)) === strtolower(trim($sId)));
    }
    $szOwnerName = $sz['associated_person'] ?? ($sz['suspect_name'] ?? ($sz['associated_suspect'] ?? null));
    if (!empty($szOwnerName) && strtolower(trim($szOwnerName)) !== 'associate suspect' && strtolower(trim($szOwnerName)) !== 'primary suspect') {
        return strtolower(trim($szOwnerName)) === strtolower(trim($sName));
    }
    if (!empty($sz['is_associate'])) {
        $assocSuspects = array_values(array_filter($docketData['suspects'] ?? [], function($s) {
            return !empty($s['suspect_name']) && (!empty($s['is_associate']) || (!empty($s['association_role']) && strtolower($s['association_role']) !== 'primary suspect'));
        }));
        if (count($assocSuspects) > 0) {
            $firstAssocName = $assocSuspects[0]['suspect_name'] ?? '';
            return strtolower(trim($sName)) === strtolower(trim($firstAssocName));
        }
    }
    return false;
});

print_r($assocExhibits);

