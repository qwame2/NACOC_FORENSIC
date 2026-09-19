<?php

$filePath = "resources/views/Forensic_Head/show_review.blade.php";
$content = file_get_contents($filePath);
$lines = explode("\n", $content);

$inScript = false;
$scriptStartLine = 0;
$stack = [];

for ($i = 0; $i < count($lines); $i++) {
    $line = $lines[$i];
    $lineNum = $i + 1;

    if (preg_match('/<script\b[^>]*>/i', $line)) {
        $inScript = true;
        $scriptStartLine = $lineNum;
        $stack = [];
        continue;
    }

    if (strpos($line, '</script>') !== false) {
        $inScript = false;
        echo "Script starting at line $scriptStartLine ended at line $lineNum. Stack size: " . count($stack) . "\n";
        if (count($stack) > 0) {
            echo "Unclosed items:\n";
            foreach ($stack as $item) {
                echo "  Line {$item['line']}: '{$item['char']}' -> " . trim($item['content']) . "\n";
            }
        }
        continue;
    }

    if (!$inScript) continue;

    $len = strlen($line);
    $inString = false;
    $stringChar = '';
    $inRegex = false;
    $inComment = false;

    for ($j = 0; $j < $len; $j++) {
        $ch = $line[$j];
        $nextCh = ($j + 1 < $len) ? $line[$j+1] : '';

        if ($inComment) break;

        if ($inRegex) {
            if ($ch === '\\') {
                $j++; // skip escaped char in regex
                continue;
            }
            if ($ch === '[') {
                // inside character class
                while ($j + 1 < $len) {
                    $j++;
                    if ($line[$j] === '\\') { $j++; continue; }
                    if ($line[$j] === ']') break;
                }
                continue;
            }
            if ($ch === '/') {
                $inRegex = false;
            }
            continue;
        }

        if ($inString) {
            if ($ch === '\\') {
                $j++;
                continue;
            }
            if ($ch === $stringChar) {
                $inString = false;
            }
            continue;
        }

        if ($ch === '/' && $nextCh === '/') {
            $inComment = true;
            break;
        }

        if ($ch === "'" || $ch === '"' || $ch === '`') {
            $inString = true;
            $stringChar = $ch;
            continue;
        }

        // Detect regex literal start: '/' not preceded by alphanumeric or string/closing parens (simplified heuristic)
        if ($ch === '/') {
            // Check preceding non-whitespace character
            $k = $j - 1;
            while ($k >= 0 && preg_match('/\s/', $line[$k])) { $k--; }
            $prevChar = ($k >= 0) ? $line[$k] : '';
            if ($k < 0 || strpos('=(,:;!&|?{}[', $prevChar) !== false || preg_match('/(match|search|split|replace|return)$/', substr($line, 0, $j))) {
                $inRegex = true;
                continue;
            }
        }

        if ($ch === '{' || $ch === '(' || $ch === '[') {
            $stack[] = ['char' => $ch, 'line' => $lineNum, 'content' => $line];
        } else if ($ch === '}' || $ch === ')' || $ch === ']') {
            if (empty($stack)) {
                echo "Extra closing '$ch' at line $lineNum: " . trim($line) . "\n";
            } else {
                $last = end($stack);
                $expected = match($last['char']) {
                    '{' => '}',
                    '(' => ')',
                    '[' => ']',
                };
                if ($ch === $expected) {
                    array_pop($stack);
                } else {
                    echo "Mismatch '$ch' at line $lineNum (expected '$expected' for '{$last['char']}' from line {$last['line']}): " . trim($line) . "\n";
                }
            }
        }
    }
}
