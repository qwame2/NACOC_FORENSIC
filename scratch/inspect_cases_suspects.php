<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cases = \App\Models\ForensicCase::allCases();
foreach ($cases as $c) {
    echo "====================================\n";
    $underlying = $c->underlyingRecord ?? $c;
    echo "Case ID: {$c->id} | Number: {$c->case_number} | Type: " . get_class($underlying) . "\n";
    echo "Title: {$c->title}\n";
    echo "Suspect Name: {$c->suspect_name}\n";
    
    // Check suspects table if exists
    if ($underlying instanceof \App\Models\MultipleSuspectCase) {
        echo "Is MultipleSuspectCase:\n";
        echo "  raw title: {$underlying->title}\n";
        echo "  suspects relation exists: " . (method_exists($underlying, 'suspects') ? 'YES' : 'NO') . "\n";
        if (method_exists($underlying, 'suspects')) {
            $relSuspects = $underlying->suspects()->get();
            echo "  rel suspects count: " . $relSuspects->count() . "\n";
            foreach ($relSuspects as $rs) {
                echo "    -> [ID {$rs->id}] {$rs->name} (Role: {$rs->role})\n";
            }
        }
    }
    
    // Check docket_data
    $docket = is_array($c->docket_data) ? $c->docket_data : (json_decode($c->docket_data ?? '[]', true) ?: []);
    if (!empty($docket['suspects'])) {
        echo "Docket Suspects count: " . count($docket['suspects']) . "\n";
        foreach ($docket['suspects'] as $sIdx => $s) {
            $sName = $s['suspect_name'] ?? ($s['name'] ?? 'N/A');
            $sRole = $s['role'] ?? ($s['suspect_role'] ?? 'N/A');
            echo "   [{$sIdx}] Name: {$sName} | Role: {$sRole} | ID: " . ($s['id'] ?? 'N/A') . "\n";
        }
    }
    
    // Check exhibits
    $exs = \App\Models\Exhibit::where('single_suspect_case_id', $c->id)
        ->orWhere('multiple_suspect_case_id', $c->id)->get();
    echo "Exhibits count: " . $exs->count() . "\n";
    foreach ($exs as $e) {
        $name = $e->commodity_name ?: ($e->dev_model ?: ($e->exhibit_category ?: 'Item'));
        echo "   - Exhibit ID: {$e->id} | Name: {$name} | associated_person: " . var_export($e->associated_person, true) . " | suspect_id: " . var_export($e->suspect_id ?? null, true) . " | single_case_id: {$e->single_suspect_case_id} | multi_case_id: {$e->multiple_suspect_case_id}\n";
    }
}
