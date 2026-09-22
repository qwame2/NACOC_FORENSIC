<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MultipleSuspectCase;
use App\Models\CaseSuspect;
use App\Models\Exhibit;

$multi = MultipleSuspectCase::find(4);
echo "=== MULTI SUSPECT CASE #4 ===\n";
echo "Case #: {$multi->case_number} | Title: {$multi->title}\n";

$suspects = CaseSuspect::where('multiple_suspect_case_id', 4)->get();
echo "Suspects Count: " . count($suspects) . "\n";
foreach ($suspects as $s) {
    echo "  - Suspect #{$s->suspect_number}: {$s->suspect_name} ({$s->role})\n";
    echo "    Front Mugshot: " . (!empty($s->mugshot_front) ? 'YES (' . strlen($s->mugshot_front) . ' bytes)' : 'NO') . "\n";
    echo "    Left Mugshot: " . (!empty($s->mugshot_left) ? 'YES (' . strlen($s->mugshot_left) . ' bytes)' : 'NO') . "\n";
    echo "    Right Mugshot: " . (!empty($s->mugshot_right) ? 'YES (' . strlen($s->mugshot_right) . ' bytes)' : 'NO') . "\n";
}

$exhibits = Exhibit::where('multiple_suspect_case_id', 4)->get();
echo "\nExhibits Count: " . count($exhibits) . "\n";
foreach ($exhibits as $ex) {
    echo "  - Exhibit #{$ex->exhibit_number}: {$ex->commodity_name} ({$ex->exhibit_category})\n";
    echo "    Seizer: {$ex->seizure_officer}\n";
    echo "    Reason: {$ex->seizure_reason}\n";
    $photos = is_array($ex->evidence_photos) ? $ex->evidence_photos : json_decode($ex->evidence_photos ?? '[]', true);
    echo "    Photos: " . count($photos ?? []) . "\n";
}
