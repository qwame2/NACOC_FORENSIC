<?php

function parsePresumptiveReactionRows($pRaw) {
    $pRaw = trim($pRaw ?? '');
    if (empty($pRaw) || $pRaw === 'N/A') {
        return [['result' => 'N/A', 'presence' => 'N/A', 'purity' => 'N/A']];
    }

    $overallResult = 'N/A';
    if (preg_match('/^(YES|NO|POS|NEG|POSITIVE|NEGATIVE)/i', $pRaw, $mRes)) {
        $overallResult = strtoupper($mRes[1]);
        if ($overallResult === 'POS' || $overallResult === 'POSITIVE') $overallResult = 'YES';
        if ($overallResult === 'NEG' || $overallResult === 'NEGATIVE') $overallResult = 'NO';
    }

    $cleanP = $pRaw;
    if (preg_match('/^[A-Z]+\s*\((.*)\)$/is', $pRaw, $mP)) {
        $cleanP = $mP[1];
    }

    $segments = preg_split('/;\s*(?=\[\d+\]|Presence of:)/i', $cleanP);
    if (empty($segments)) $segments = [$cleanP];

    $rows = [];
    foreach ($segments as $seg) {
        $seg = trim($seg);
        if (empty($seg)) continue;

        $presence = 'N/A';
        $purity = 'N/A';

        if (preg_match('/Presence of:\s*([^\-\;\)]+)/i', $seg, $mPres)) {
            $presence = trim($mPres[1]);
        } elseif (preg_match('/^\[\d+\]\s*([^\-\;\)]+)/i', $seg, $mPres)) {
            $presence = trim($mPres[1]);
        } elseif (!empty($seg) && !str_contains($seg, 'Purity')) {
            $presence = trim($seg);
        }

        if ($presence === 'YES' || $presence === 'NO' || $presence === 'POS' || $presence === 'NEG') {
            $presence = 'N/A';
        }

        if (preg_match('/Purity\/Percentage:\s*([^\;\)]+)/i', $seg, $mPur)) {
            $purity = trim($mPur[1]);
        }

        if ($presence !== 'N/A' || $purity !== 'N/A' || count($segments) === 1) {
            $rows[] = [
                'result'   => $overallResult,
                'presence' => $presence,
                'purity'   => $purity,
            ];
        }
    }

    if (empty($rows)) {
        $rows[] = ['result' => $overallResult, 'presence' => 'N/A', 'purity' => 'N/A'];
    }

    return $rows;
}

$testCases = [
    'YES ([1] Presence of: Tramadol - Purity/Percentage: 100%; [2] Presence of: Tramoo - Purity/Percentage: 50%)',
    'YES (Presence of: Cocaine - Purity/Percentage: 85.4%)',
    'YES',
    'NO',
    'N/A',
];

foreach ($testCases as $idx => $tc) {
    echo "--- Test [$idx]: $tc ---\n";
    print_r(parsePresumptiveReactionRows($tc));
}
