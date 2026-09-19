<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$_GET['from'] = 'Lab Analysis';
request()->query->set('from', 'Lab Analysis');

$case = \App\Models\ForensicCase::where('case_number', 'CSS-2026-2209')->first();
$view = view('Forensic_Head.show_review', [
    'case' => $case,
    'unitProgress' => [],
    'docketData' => $case->docket_data ?? [],
    'primaryPhotos' => [],
    'allAssocPhotos' => [],
    'defaultLabHeadName' => 'Samuel Atta',
])->render();

file_put_contents(__DIR__ . '/review_rendered_2209.html', $view);

echo "Rendered file saved to scratch/review_rendered_2209.html\n";

// Check labExhibitSelect options
if (preg_match('/<select[^>]*id="labExhibitSelect"[^>]*>(.*?)<\/select>/s', $view, $m)) {
    echo "=== DROPDOWN OPTIONS ===\n";
    echo trim($m[1]) . "\n";
} else {
    echo "labExhibitSelect NOT FOUND!\n";
}

// Check section5BadgeStatus
if (preg_match('/<span[^>]*id="section5BadgeStatus"[^>]*>(.*?)<\/span>/s', $view, $m2)) {
    echo "=== BADGE STATUS ===\n";
    echo trim(strip_tags($m2[1])) . "\n";
}

// Check section5ReadOnlyDisplay
if (preg_match('/<div[^>]*id="section5ReadOnlyDisplay"[^>]*>(.*?)<\/table>/s', $view, $m3)) {
    echo "=== READ ONLY REPORT DISPLAY ===\n";
    echo trim(strip_tags($m3[0])) . "\n";
} else {
    echo "section5ReadOnlyDisplay NOT FOUND!\n";
}
