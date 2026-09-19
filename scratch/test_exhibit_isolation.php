<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;

$case = new ForensicCase([
    'id' => 9999,
    'case_number' => 'TEST-2026-999',
    'title' => 'Republic vs Kwame Mensah & Co',
    'suspect_name' => 'Kwame Mensah',
    'unit_name' => 'Crime Scene Support',
    'status' => 'Pending Approval',
    'docket_data' => [
        'suspects' => [
            [
                'suspect_name' => 'Kwame Mensah',
                'association_role' => 'Primary Suspect',
                'is_associate' => false,
                'suspect_id' => 'PRIM-001'
            ],
            [
                'suspect_name' => 'Lydia Manes',
                'association_role' => 'ASSOCIATE',
                'is_associate' => true,
                'associate_id' => 'ASC-002',
                'suspect_id' => 'ASC-002'
            ],
        ],
        'seizures' => [
            [
                'category' => 'Drugs',
                'seizure_name' => 'Heroin Powder',
                'is_associate' => true,
                'associate_id' => 'ASC-002',
                'suspect_id' => 'ASC-002',
                'associated_person' => 'Lydia Manes'
            ]
        ]
    ]
]);

$htmlReview = view('Forensic_Head.show_review', ['case' => $case])->render();
if (str_contains($htmlReview, '$assocExhibits = array_filter')) {
    echo "ERROR: Unrendered PHP text found in Review View!\n";
} else {
    echo "SUCCESS: Review View rendered cleanly without raw PHP code!\n";
}

if (str_contains($htmlReview, 'Heroin Powder')) {
    echo "SUCCESS: Heroin Powder exhibit rendered under Lydia Manes!\n";
} else {
    echo "ERROR: Heroin Powder NOT found under Lydia Manes!\n";
}

