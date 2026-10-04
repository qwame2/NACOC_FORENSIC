<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ForensicCase;

$c = ForensicCase::where('case_number', 'CSS-2026-1187')->first();
echo "Title: {$c->title}\n";
echo "Exhibit Cat: {$c->exhibit_category}\n";
echo "Seizure Name: {$c->seizure_name}\n";
echo "Seizure Name Drug: {$c->seizure_name_drug}\n";
echo "Offense Desc: {$c->offense_description}\n";
echo "Suspect Name: {$c->suspect_name}\n";
