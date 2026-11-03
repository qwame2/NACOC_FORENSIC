<?php

$file = 'resources/views/cases/show_review.blade.php';
$lines = file($file);

echo "=== 1. 'COMPLETED' occurrences between lines 960 and 1760 ===\n";
foreach ($lines as $idx => $line) {
    $lineNum = $idx + 1;
    if ($lineNum >= 960 && $lineNum <= 1760 && stripos($line, 'COMPLETED') !== false) {
        echo "Line {$lineNum}: " . trim($line) . "\n";
    }
}

echo "\n=== 2. Check for \$isEvidenceManagement in unitProgress (lines 960 to 1760) ===\n";
$foundEm = false;
foreach ($lines as $idx => $line) {
    $lineNum = $idx + 1;
    if ($lineNum >= 960 && $lineNum <= 1760 && strpos($line, 'isEvidenceManagement') !== false) {
        echo "Line {$lineNum}: " . trim($line) . "\n";
        $foundEm = true;
    }
}
if (!$foundEm) {
    echo "CLEAN: No \$isEvidenceManagement found between lines 960 and 1760.\n";
}

echo "\n=== 3. Check for \$isDrugCase / \$hasDrugSeizureInDocket in unitProgress (lines 960 to 1760) ===\n";
$foundDrug = false;
foreach ($lines as $idx => $line) {
    $lineNum = $idx + 1;
    if ($lineNum >= 960 && $lineNum <= 1760 && (strpos($line, 'isDrugCase') !== false || strpos($line, 'hasDrugSeizureInDocket') !== false)) {
        // Exclude line 1359 which is in the exhibit summary fallback outside unitProgress
        echo "Line {$lineNum}: " . trim($line) . "\n";
        $foundDrug = true;
    }
}
if (!$foundDrug) {
    echo "CLEAN: No \$isDrugCase / \$hasDrugSeizureInDocket found in unitProgress status blocks.\n";
}

echo "\n=== 4. Check for case-model columns in unitProgress status blocks (lines 960-1090 & 1690-1760) ===\n";
$targetRanges = [[960, 1090], [1690, 1760]];
$keywords = [
    'confirmatory_test_result',
    'presumptive_test_result',
    'confirmatory_substance_identification',
    'laboratory_analytical_findings',
    'detected_substances',
    'confirmatory_test_methods',
    'confirmatory_specifications',
    'confirmatory_remark',
    'sample_weight_'
];
$foundModelCols = false;
foreach ($lines as $idx => $line) {
    $lineNum = $idx + 1;
    foreach ($targetRanges as $range) {
        if ($lineNum >= $range[0] && $lineNum <= $range[1]) {
            foreach ($keywords as $kw) {
                if (strpos($line, $kw) !== false) {
                    echo "Line {$lineNum}: " . trim($line) . "\n";
                    $foundModelCols = true;
                }
            }
        }
    }
}
if (!$foundModelCols) {
    echo "CLEAN: No case-model column used as proof of completion in unitProgress blocks.\n";
}

echo "\n=== 5. Final arrays and counters check ===\n";
foreach ($lines as $idx => $line) {
    $lineNum = $idx + 1;
    if ($lineNum >= 1730 && $lineNum <= 1765) {
        if (strpos($line, 'totalUnitsCount') !== false || strpos($line, 'completedUnitsCount') !== false) {
            echo "Line {$lineNum}: " . trim($line) . "\n";
        }
    }
}
