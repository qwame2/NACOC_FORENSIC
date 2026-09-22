<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== BACKFILLING EXISTING CASES IN DATABASE ===\n\n";

$singleCases = DB::table('single_suspect_cases')->get();
$multiCases  = DB::table('multiple_suspect_cases')->get();

$allCases = $singleCases->concat($multiCases);
$updatedCount = 0;

foreach ($allCases as $c) {
    $tableName = isset($c->single_suspect_case_number) || property_exists($c, 'suspect_name') ? 'single_suspect_cases' : 'multiple_suspect_cases';
    $rawDocket = property_exists($c, 'docket_data') ? $c->docket_data : null;
    $dData = is_array($rawDocket) ? $rawDocket : (json_decode($rawDocket ?? '[]', true) ?: []);

    $isLabCreated = !empty($c->unit_name) && (
        strtolower(trim($c->unit_name)) === 'lab analysis' ||
        str_contains(strtolower($c->unit_name), 'lab analysis') ||
        str_contains(strtolower($c->unit_name), 'laboratory')
    );

    $exCat = strtolower($c->exhibit_category ?? '');
    $sDrug = $c->seizure_name_drug ?? '';
    $sName = strtolower($c->seizure_name ?? '');
    $exSum = strtolower($c->exhibits_summary ?? '');

    $isDrugCase = (!empty($sDrug) || 
        str_contains($exCat, 'drug') || str_contains($exCat, 'narcotic') || str_contains($exCat, 'substance') || str_contains($exCat, 'cannabis') || str_contains($exCat, 'cocaine') || str_contains($exCat, 'heroin') || str_contains($exCat, 'meth') || str_contains($exCat, 'tramadol') ||
        str_contains($sName, 'cocaine') || str_contains($sName, 'heroin') || str_contains($sName, 'cannabis') || str_contains($sName, 'weed') || str_contains($sName, 'meth') || str_contains($sName, 'tramadol') || str_contains($sName, 'drug') || str_contains($sName, 'narcotic') ||
        str_contains($exSum, 'cocaine') || str_contains($exSum, 'heroin') || str_contains($exSum, 'cannabis') || str_contains($exSum, 'weed') || str_contains($exSum, 'meth') || str_contains($exSum, 'tramadol') || str_contains($exSum, 'drug') || str_contains($exSum, 'narcotic')
    );

    if (!$isDrugCase) {
        $seizures = $dData['seizures'] ?? [];
        if (is_array($seizures)) {
            foreach ($seizures as $sz) {
                $cat = strtolower($sz['category'] ?? ($sz['exhibit_category'] ?? ''));
                $dName = $sz['seizure_name_drug'] ?? '';
                $szName = strtolower($sz['seizure_name'] ?? '');
                if (!empty($dName) || str_contains($cat, 'drug') || str_contains($cat, 'narcotic') || str_contains($szName, 'cocaine') || str_contains($szName, 'heroin') || str_contains($szName, 'cannabis') || str_contains($szName, 'drug') || str_contains($szName, 'narcotic')) {
                    $isDrugCase = true;
                    break;
                }
            }
        }
    }

    if ($isLabCreated) {
        $allowedUnits = ['Evidence Management'];
        if ($isDrugCase) {
            $allowedUnits[] = 'Lab Analysis';
        }
    } else {
        $allowedUnits = ['Crime Scene Support', 'Evidence Management'];
        if ($isDrugCase) {
            $allowedUnits[] = 'Lab Analysis';
        }
        $allowedUnits[] = 'Digital & Physical Forensics';
    }

    $existingProgress = $dData['unit_progress'] ?? [];
    $newUnitProgress = [];

    foreach ($allowedUnits as $uKey) {
        if (isset($existingProgress[$uKey])) {
            $newUnitProgress[$uKey] = $existingProgress[$uKey];
        } else {
            $st = 'PENDING';
            $off = null;
            $dt = null;
            if ($uKey === 'Crime Scene Support' || ($isLabCreated && $uKey === 'Evidence Management')) {
                $st = 'COMPLETED';
                $off = $c->submitting_officer ?? 'System Officer';
                $dt = $c->created_at ?? now()->toDateTimeString();
            }
            $newUnitProgress[$uKey] = ['status' => $st, 'officer' => $off, 'updated_at' => $dt];
        }
    }

    $dData['unit_progress'] = $newUnitProgress;

    DB::table($tableName)->where('id', $c->id)->update([
        'docket_data' => json_encode($dData),
    ]);

    $updatedCount++;
    echo "  Updated Case #{$caseNum} (Unit: {$c->unit_name}, Drug: " . ($isDrugCase ? 'YES' : 'NO') . ") -> Allowed Units: " . implode(', ', $allowedUnits) . "\n";
}

echo "\nCompleted backfill for {$updatedCount} cases in database!\n";
