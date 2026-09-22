<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;

$case = ForensicCase::where('case_number', 'CSS-2026-5584')->first();
$docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data, true) ?: []);

$aSpName = "Wakanda Asiedu";
$aSpId = "ASC-001";

echo "Seizures:\n";
foreach ($docketData['seizures'] ?? [] as $i => $sz) {
    $szOwnerName = $sz['associated_person'] ?? ($sz['suspect_name'] ?? ($sz['owner_name'] ?? null));
    $szAssocId = $sz['associate_id'] ?? ($sz['suspect_id'] ?? null);
    echo "Seizure #$i: OwnerName='$szOwnerName', AssocId='$szAssocId'\n";
    
    // Check match condition:
    if (!empty($szOwnerName) && strtolower(trim($szOwnerName)) !== 'associate suspect' && strtolower(trim($szOwnerName)) !== 'primary suspect') {
        if (strtolower(trim($szOwnerName)) === strtolower(trim($aSpName)) || str_contains(strtolower($szOwnerName), strtolower(trim($aSpName)))) {
            echo "  -> Matched owner name!\n";
        }
    }
    if (!empty($szAssocId) && !empty($aSpId)) {
        if (strtolower(trim($szAssocId)) === strtolower(trim($aSpId))) {
            echo "  -> Matched assoc ID ($szAssocId === $aSpId)!\n";
        }
    }
}
