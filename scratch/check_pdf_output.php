<?php
$html = file_get_contents('scratch/rendered_pdf_69.html');
echo "Contains Atta Budo: " . (str_contains($html, 'Atta Budo') ? 'YES' : 'NO') . "\n";
echo "Contains Tramadol: " . (str_contains($html, 'Tramadol') ? 'YES' : 'NO') . "\n";
echo "Contains Heroin: " . (str_contains($html, 'Heroin') ? 'YES' : 'NO') . "\n";

preg_match_all('/LABORATORY EXAMINATION RESULT[^\<]*/i', $html, $matches);
print_r($matches[0]);
