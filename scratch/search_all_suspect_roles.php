<?php
function searchDir($dir, &$results) {
    $files = scandir($dir);
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        $path = $dir . '/' . $f;
        if (is_dir($path)) {
            searchDir($path, $results);
        } else if (str_ends_with($path, '.blade.php') || str_ends_with($path, '.php') || str_ends_with($path, '.js')) {
            $content = file_get_contents($path);
            if (preg_match_all('/(associate|co-suspect|co_suspect|joint_suspect|ASSOCIATE)/i', $content, $m, PREG_OFFSET_CAPTURE)) {
                $lines = explode("\n", $content);
                foreach ($lines as $lineNum => $line) {
                    if (preg_match('/(associate|co-suspect)/i', $line)) {
                        $results[] = [
                            'file' => $path,
                            'line' => $lineNum + 1,
                            'content' => trim($line)
                        ];
                    }
                }
            }
        }
    }
}

$results = [];
searchDir('resources/views', $results);
searchDir('app/Http/Controllers', $results);

echo "Found " . count($results) . " occurrences:\n\n";
foreach (array_slice($results, 0, 50) as $r) {
    echo "{$r['file']}:{$r['line']} -> {$r['content']}\n";
}
