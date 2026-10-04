<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;

$c = ForensicCase::where('case_number', 'CSS-2026-1187')->first();

$drugKeywords = [
    'drug', 'narcotic', 'substance', 'cannabis', 'cocaine', 'heroin', 
    'meth', 'tramadol', 'weed', 'hemp', 'hashish', 'ephedrine', 
    'precursor', 'opioid', 'fentanyl', 'amphetamine', 'mdma', 'ecstasy',
    'morphine', 'opium', 'diacetylmorphine'
];

$caseCat = strtolower($c->exhibit_category ?? '');
$caseSeizure = strtolower($c->seizure_name ?? '');
$caseOffense = strtolower($c->offense_description ?? '');

echo "caseCat: '$caseCat'\n";
echo "caseSeizure: '$caseSeizure'\n";
echo "caseOffense: '$caseOffense'\n";

$isDrugCase = false;
foreach ($drugKeywords as $kw) {
    if (str_contains($caseCat, $kw) || str_contains($caseSeizure, $kw) || str_contains($caseOffense, $kw)) {
        $isDrugCase = true;
        echo "Matched keyword '$kw'\n";
        break;
    }
}
echo "isDrugCase: " . ($isDrugCase ? 'true' : 'false') . "\n";
