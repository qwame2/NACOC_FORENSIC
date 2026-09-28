<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

\App\Models\SingleSuspectCase::where('case_number', 'DIG-2026-0001')->delete();

// Test case for Digital Forensics
$digitalCase = \App\Models\SingleSuspectCase::create([
    'case_number' => 'DIG-2026-0001',
    'title' => 'Republic vs Cyber Suspect',
    'unit_name' => 'Digital & Physical Forensics',
    'commanding_officer' => 'Cmdr. Digital',
    'submitting_officer' => 'Ofori Graham',
    'suspect_name' => 'Cyber Suspect',
    'status' => 'Pending Approval',
    'docket_data' => [
        'seizures' => [
            [
                'category' => 'Electronic Devices',
                'seizure_name' => 'iPhone 15 Pro Max',
                'associated_person' => 'Cyber Suspect',
                'quantity' => 1
            ]
        ]
    ]
]);

$wrapped = \App\Models\ForensicCase::wrapRecord($digitalCase);
$user = \App\Models\User::where('fullname', 'Madam Diana')->first();
auth()->login($user);

// Now test with user Ofori Graham (Digital & Physical) and Madam Diana (Evidence)
foreach (['Digital & Physical Forensics', 'Evidence Management'] as $from) {
    request()->merge(['from' => $from]);
    $html = view('cases.show_review', ['case' => $wrapped])->render();
    preg_match('/id="unitOfficerBox-labanalysis"[^>]*>([\s\S]*?)<\/div>/', $html, $mOff);
    preg_match('/id="unitBadge-labanalysis"[^>]*>([\s\S]*?)<\/span>/', $html, $mBadge);
    preg_match('/id="unitNotesBox-labanalysis"[^>]*>([\s\S]*?)<\/div>/', $html, $mNotes);
    
    $off = isset($mOff[1]) ? trim(preg_replace('/\s+/', ' ', strip_tags($mOff[1]))) : 'N/A';
    $badge = isset($mBadge[1]) ? trim(preg_replace('/\s+/', ' ', strip_tags($mBadge[1]))) : 'N/A';
    $notes = isset($mNotes[1]) ? trim(preg_replace('/\s+/', ' ', strip_tags($mNotes[1]))) : 'N/A';
    
    echo "From $from => [$badge] $off | $notes\n";
}

$digitalCase->delete();
