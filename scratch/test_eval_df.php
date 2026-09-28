<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::where('unit', 'LIKE', '%Digital%')->first();
auth()->login($user);
request()->merge(['from' => 'Digital & Physical Forensics']);

$cases = \App\Models\ForensicCase::allCases();
foreach ($cases as $c) {
    $view = (new \App\Http\Controllers\CaseController)->showReview($c->case_number);
    $html = $view->render();
    preg_match('/id="unitOfficerBox-labanalysis"[^>]*>([\s\S]*?)<\/div>/', $html, $mOff);
    preg_match('/id="unitBadge-labanalysis"[^>]*>([\s\S]*?)<\/span>/', $html, $mBadge);
    preg_match('/id="unitNotesBox-labanalysis"[^>]*>([\s\S]*?)<\/div>/', $html, $mNotes);
    
    $off = isset($mOff[1]) ? trim(preg_replace('/\s+/', ' ', strip_tags($mOff[1]))) : 'N/A';
    $badge = isset($mBadge[1]) ? trim(preg_replace('/\s+/', ' ', strip_tags($mBadge[1]))) : 'N/A';
    $notes = isset($mNotes[1]) ? trim(preg_replace('/\s+/', ' ', strip_tags($mNotes[1]))) : 'N/A';
    
    // Also check digital forensics card
    preg_match('/id="unitOfficerBox-digitalphysicalforensics"[^>]*>([\s\S]*?)<\/div>/', $html, $mDigOff);
    preg_match('/id="unitBadge-digitalphysicalforensics"[^>]*>([\s\S]*?)<\/span>/', $html, $mDigBadge);
    $digOff = isset($mDigOff[1]) ? trim(preg_replace('/\s+/', ' ', strip_tags($mDigOff[1]))) : 'N/A';
    $digBadge = isset($mDigBadge[1]) ? trim(preg_replace('/\s+/', ' ', strip_tags($mDigBadge[1]))) : 'N/A';

    echo $c->case_number . ' (' . $c->unit_name . ') => LAB: [' . $badge . '] ' . $off . ' | ' . $notes . ' | DIG: [' . $digBadge . '] ' . $digOff . "\n";
}
