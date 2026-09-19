<?php
$html = file_get_contents(__DIR__ . '/review_output.html');

$dom = new DOMDocument();
@$dom->loadHTML($html);
$xpath = new DOMXPath($dom);

$tables = $xpath->query("//table");
echo "Total tables in review HTML: " . $tables->length . "\n";

foreach ($tables as $idx => $table) {
    $text = $table->textContent;
    if (strpos($text, 'Laboratory Number:') !== false || strpos($text, 'Presumptive Analysis:') !== false || strpos($text, 'Confirmatory Analysis:') !== false) {
        echo "================ TABLE " . ($idx + 1) . " ================\n";
        $rows = $table->getElementsByTagName('tr');
        foreach ($rows as $row) {
            $tds = $row->getElementsByTagName('td');
            $line = [];
            foreach ($tds as $td) {
                $line[] = trim(preg_replace('/\s+/', ' ', $td->textContent));
            }
            echo implode(' | ', $line) . "\n";
        }
        echo "\n";
    }
}
