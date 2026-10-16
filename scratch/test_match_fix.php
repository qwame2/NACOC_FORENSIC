<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$case = \App\Models\ForensicCase::where('case_number', 'NCC-2026-9250')->first();

// Test matching logic for LINDA ADDO
$cleanTargetName = 'linda addo';
$ex = \App\Models\Exhibit::where('multiple_suspect_case_id', $case->id)->first();
$exOwners = array_filter([
    $ex->suspect ? $ex->suspect->suspect_name : null,
    $ex->veh_owner ?? null,
    $ex->wpn_owner ?? null,
    $ex->dev_owner ?? null,
    $ex->seizure_officer ?? null,
    $ex->associated_person ?? null,
    $ex->associated_suspect ?? null,
]);

echo "exOwners: " . implode(', ', $exOwners) . "\n";

$isMatch = false;
foreach ($exOwners as $ownerStr) {
    $cleanOwner = strtolower(trim($ownerStr));
    if ($cleanOwner === $cleanTargetName || str_contains($cleanOwner, $cleanTargetName) || str_contains($cleanTargetName, $cleanOwner)) {
        $isMatch = true;
        break;
    }
    if (str_contains($cleanOwner, 'all suspect') || str_contains($cleanOwner, 'joint') || str_contains($cleanOwner, 'both')) {
        $isMatch = true;
        break;
    }
}

echo "Matched for Linda Addo with fix: " . ($isMatch ? 'YES' : 'NO') . "\n";
