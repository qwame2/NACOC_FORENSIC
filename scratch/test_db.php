<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$cases = \App\Models\ForensicCase::allCases();
foreach ($cases as $c) {
    echo "Case Number: {$c->case_number}\n";
    $exs = \App\Models\Exhibit::where('single_suspect_case_id', $c->id)->orWhere('multiple_suspect_case_id', $c->id)->get();
    echo "  Exhibit count direct: " . $exs->count() . "\n";
    foreach ($exs as $e) {
        echo "    - " . ($e->commodity_name ?: $e->exhibit_category) . " (" . ($e->seizure_quantity ?: '1') . " " . ($e->seizure_unit ?: '') . ")\n";
    }
    $docket = is_array($c->docket_data) ? $c->docket_data : json_decode($c->docket_data ?? '[]', true);
    if (!empty($docket['seizures'])) {
        echo "  Docket seizures count: " . count($docket['seizures']) . "\n";
        foreach ($docket['seizures'] as $s) {
            echo "    * " . ($s['seizure_name'] ?? $s['category'] ?? 'Item') . "\n";
        }
    }
}
