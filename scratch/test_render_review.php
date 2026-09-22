<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$case = App\Models\ForensicCase::where('case_number', 'CSS-2026-5584')->first();
$html = view('Forensic_Head.show_review', ['case' => $case])->render();

// Check for "Biometric Mugshots"
preg_match_all('/([^\n>]+)\s*—\s*Biometric Mugshots/', $html, $m1);
echo "Biometric Mugshots headers:\n";
print_r($m1[0] ?? []);

// Check for "Personal Booking Details"
preg_match_all('/([^\n>]+)\s*—\s*Personal Booking Details/', $html, $m2);
echo "\nPersonal Booking Details headers:\n";
print_r($m2[0] ?? []);

// Check for Suspect Full Name in the output
preg_match_all('/Suspect Full Name<\/div>\s*<div[^>]*>([^<]+)<\/div>/', $html, $m3);
echo "\nSuspect Full Name values:\n";
print_r($m3[1] ?? []);
