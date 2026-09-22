<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$case = App\Models\ForensicCase::find(69);

$reviewHtml = view('Forensic_Head.show_review', ['case' => $case])->render();
if (preg_match_all('/Presumptive Reaction:<\/td>\s*<td[^>]*>(.*?>\s*<\/table>)/is', $reviewHtml, $matches)) {
    foreach ($matches[1] as $idx => $m) {
        echo "=== REVIEW TABLE $idx ===\n";
        if (preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $m, $trs)) {
            foreach ($trs[1] as $rIdx => $tr) {
                $cells = array_map(function($c) { return trim(preg_replace('/\s+/', ' ', strip_tags($c))); }, explode('</td>', $tr));
                $cells = array_filter($cells);
                if (!empty($cells)) {
                    echo "   Row [$rIdx]: " . implode(' | ', $cells) . "\n";
                }
            }
        }
    }
}

echo "\n=== PDF VIEW ===\n";
$pdfHtml = view('Forensic_Head.case_pdf', ['case' => $case])->render();
if (preg_match_all('/Presumptive Reaction:<\/td>\s*<td[^>]*>(.*?>\s*<\/table>)/is', $pdfHtml, $matchesPdf)) {
    foreach ($matchesPdf[1] as $idx => $m) {
        echo "=== PDF TABLE $idx ===\n";
        if (preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $m, $trsPdf)) {
            foreach ($trsPdf[1] as $rIdx => $tr) {
                $cells = array_map(function($c) { return trim(preg_replace('/\s+/', ' ', strip_tags($c))); }, explode('</td>', $tr));
                $cells = array_filter($cells);
                if (!empty($cells)) {
                    echo "   Row [$rIdx]: " . implode(' | ', $cells) . "\n";
                }
            }
        }
    }
}
