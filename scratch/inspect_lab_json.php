<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$case = App\Models\ForensicCase::where('case_number', 'CSS-2026-2209')->first();
$d = is_array($case->docket_data) ? $case->docket_data : json_decode($case->docket_data, true);

echo json_encode([
    'lab_examination' => $d['lab_examination'] ?? null,
    'seizures' => array_map(function($s) {
        return [
            'name' => $s['seizure_name_drug'] ?? ($s['seizure_name'] ?? null),
            'exhibit_number' => $s['exhibit_number'] ?? null,
            'associated_person' => $s['associated_person'] ?? null,
            'suspect_id' => $s['suspect_id'] ?? null,
            'associate_id' => $s['associate_id'] ?? null,
            'is_associate' => $s['is_associate'] ?? null,
        ];
    }, $d['seizures'] ?? [])
], JSON_PRETTY_PRINT);
