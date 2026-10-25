<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::first();
auth()->login($user);

$html = view('Evidence_Management.evidence_management')->render();

echo 'Contains tactical-region-hotspot: ' . (strpos($html, 'tactical-region-hotspot') !== false ? 'YES' : 'NO') . PHP_EOL;
echo 'Contains openTacticalMode: ' . (strpos($html, 'openTacticalMode') !== false ? 'YES' : 'NO') . PHP_EOL;
echo 'Contains tacDistrictNavHeader: ' . (strpos($html, 'tacDistrictNavHeader') !== false ? 'YES' : 'NO') . PHP_EOL;
echo 'Contains tacDistrictDeckDrawer: ' . (strpos($html, 'tacDistrictDeckDrawer') !== false ? 'YES' : 'NO') . PHP_EOL;
echo 'Contains onTacDistrictHover: ' . (strpos($html, 'onTacDistrictHover') !== false ? 'YES' : 'NO') . PHP_EOL;
echo 'Contains toggleTacDistrictDeck: ' . (strpos($html, 'toggleTacDistrictDeck') !== false ? 'YES' : 'NO') . PHP_EOL;
