<?php
$content = file_get_contents('resources/views/Crime_Scene_Support/staff/events/arrest_person.blade.php');

// Keep only HTML before <script>
$parts = explode('<script>', $content);
$htmlContent = $parts[0];

$lines = explode("\n", $htmlContent);
$stack = [];

foreach ($lines as $lineNum => $lineText) {
    preg_match_all('/<\/?(div|form|main|body|html|table|tbody|thead|tr|th|td)\b[^>]*>/i', $lineText, $matches, PREG_OFFSET_CAPTURE);
    foreach ($matches[0] as $match) {
        $tag = $match[0];
        $isClose = (strpos($tag, '</') === 0);
        $tagName = strtolower(preg_replace('/[<\/>\s].*/', '', $tag));
        
        if (!$isClose) {
            $stack[] = ['tag' => $tagName, 'line' => $lineNum + 1, 'text' => trim($lineText)];
        } else {
            if (empty($stack)) {
                echo "Unexpected closing tag </$tagName> at line " . ($lineNum + 1) . "\n";
            } else {
                $last = array_pop($stack);
                if ($last['tag'] !== $tagName) {
                    echo "Mismatched tag </$tagName> at line " . ($lineNum + 1) . " (expected </{$last['tag']}> opened at line {$last['line']}: '{$last['text']}')\n";
                }
            }
        }
    }
}

echo "=== REMAINING UNCLOSED TAGS ===\n";
foreach ($stack as $unclosed) {
    echo "<{$unclosed['tag']}> opened at line {$unclosed['line']}: {$unclosed['text']}\n";
}
