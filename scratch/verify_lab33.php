<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$r = Illuminate\Support\Facades\DB::table('laboratory_examination_results')->where('id', 33)->first();
echo "description:  " . $r->sample_weight_description . PHP_EOL;
echo "units:        " . $r->sample_weight_units . PHP_EOL;
echo "gross:        " . $r->sample_weight_gross . PHP_EOL;
echo "taken:        " . ($r->sample_weight_taken ?: '(empty - needs user input)') . PHP_EOL;
echo "reserved:     " . ($r->sample_weight_reserved ?: '(empty - needs user input)') . PHP_EOL;
