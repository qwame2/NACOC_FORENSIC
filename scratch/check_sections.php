<?php
$content = file_get_contents('scratch/pdf_output_5584.html');

echo "=== PRIMARY LAB EXAM SECTION ===\n";
if (preg_match('/PRIMARY &amp; CO-SUSPECT DOSSIER.*?<\/table>/s', $content, $m)) {
    echo strip_tags($m[0]) . "\n";
} else {
    echo "Primary section not matched\n";
}

echo "\n=== ASSOCIATE LAB EXAM SECTION (ATTA BUDO) ===\n";
if (preg_match('/ASSOCIATE DOSSIER &bull; SUSPECT #3: ATTA BUDO.*?<\/table>/s', $content, $m2)) {
    echo strip_tags($m2[0]) . "\n";
} else {
    echo "Atta Budo associate section not matched\n";
}

echo "\n=== WAKANDA ASIEDU LAB EXAM SECTION ===\n";
if (strpos($content, 'WAKANDA ASIEDU &bull; LABORATORY') !== false || strpos($content, 'LABORATORY EXAMINATION RESULT — WAKANDA ASIEDU') !== false) {
    echo "FOUND WAKANDA ASIEDU LAB EXAM (ERROR!)\n";
} else {
    echo "PASSED: Wakanda Asiedu has no lab exam section (weapon exhibit correctly omitted).\n";
}
