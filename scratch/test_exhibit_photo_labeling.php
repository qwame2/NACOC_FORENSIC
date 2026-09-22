<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\ForensicCase;
use App\Http\Controllers\CaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "=== TEST EXHIBIT PHOTO LABELING (PRIMARY VS ASSOCIATE) ===\n\n";

$user = User::first();
if ($user) Auth::login($user);

$controller = new CaseController();

$caseRef = 'TEST-LABEL-' . time();
$req = Request::create('/cases', 'POST', [
    'case_number' => $caseRef,
    'title' => 'Test Exhibit Labeling Case',
    'unit_name' => 'Crime Scene Support',
    'suspect_name' => 'Kofi Primary',
    'exhibit_category' => 'DRUGS',
    'seizure_name_drug' => 'Cocaine Brick',
    'evidence_photos' => ['/storage/exhibits/primary_cocaine.jpg'],
    'docket_data' => [
        'suspects' => [
            [
                'suspect_name' => 'Kofi Primary',
                'association_role' => 'Primary Suspect',
            ],
            [
                'suspect_name' => 'Atta Associate',
                'association_role' => 'Associate Suspect',
                'is_associate' => true,
            ]
        ],
        'seizures' => [
            [
                'category' => 'DRUGS',
                'seizure_name' => 'Cocaine Brick',
                'associated_person' => 'Kofi Primary',
                'association_role' => 'Primary Suspect',
                'is_associate' => false,
                'evidence_photos' => ['/storage/exhibits/primary_cocaine.jpg'],
            ],
            [
                'category' => 'DRUGS',
                'seizure_name' => 'Heroin Pouch',
                'associated_person' => 'Atta Associate',
                'association_role' => 'Associate Suspect',
                'is_associate' => true,
                'evidence_photos' => ['/storage/exhibits/assoc_heroin.jpg'],
            ]
        ]
    ]
]);
$controller->store($req);
$case = ForensicCase::findByRef($caseRef);

$renderedHtml = view('Forensic_Head.show_review', [
    'case' => $case,
    'status' => $case->status ?? 'Under Multi-Unit Processing',
    'priority' => $case->priority ?? 'NORMAL',
    'docketData' => is_array($case->docket_data) ? $case->docket_data : json_decode($case->docket_data ?? '[]', true),
])->render();

// Check if primary_cocaine.jpg is labeled as (Primary Exhibit)
if (str_contains($renderedHtml, 'primary_cocaine.jpg') && str_contains($renderedHtml, '(Primary Exhibit)')) {
    echo "✓ Primary Exhibit Photo correctly labeled as (Primary Exhibit)!\n";
} else {
    echo "❌ FAILED: Primary Exhibit Photo was not labeled as (Primary Exhibit)!\n";
}

// Check if assoc_heroin.jpg is labeled as (Associate Exhibit)
if (str_contains($renderedHtml, 'assoc_heroin.jpg') && str_contains($renderedHtml, '(Associate Exhibit)')) {
    echo "✓ Associate Exhibit Photo correctly labeled as (Associate Exhibit)!\n";
} else {
    echo "❌ FAILED: Associate Exhibit Photo was not labeled as (Associate Exhibit)!\n";
}
