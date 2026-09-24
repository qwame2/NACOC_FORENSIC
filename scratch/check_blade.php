<?php
$content = file_get_contents('resources/views/cases/show_review.blade.php');
preg_match_all('/@(?:if|unless|can|cannot|auth|guest|hasSection|section|push|component|slot|forelse|foreach|while)\b|@endif\b|@endunless\b|@endcan\b|@endcannot\b|@endauth\b|@endguest\b|@endsection\b|@endpush\b|@endcomponent\b|@endslot\b|@endforelse\b|@endforeach\b|@endwhile\b/', $content, $matches, PREG_OFFSET_CAPTURE);

$stack = [];

foreach ($matches[0] as $m) {
    $tag = $m[0];
    $offset = $m[1];
    $line = substr_count(substr($content, 0, $offset), "\n") + 1;
    if (str_starts_with($tag, '@end')) {
        $expected = '@' . substr($tag, 4);
        if (empty($stack)) {
            echo "Unmatched closing tag {$tag} on line {$line}\n";
        } else {
            $last = array_pop($stack);
            if ($last['tag'] !== $expected) {
                echo "Mismatched closing tag {$tag} on line {$line}, expected closing for {$last['tag']} from line {$last['line']}\n";
            }
        }
    } else {
        $stack[] = ['tag' => $tag, 'line' => $line];
    }
}

echo "Remaining unclosed tags count: " . count($stack) . "\n";
foreach ($stack as $s) {
    echo "  {$s['tag']} on line {$s['line']}\n";
}

$lines = explode("\n", $content);
$divStack = [];
for ($i = 0; $i < count($lines); $i++) {
    $lineNum = $i + 1;
    $line = $lines[$i];
    preg_match_all('/<div\b[^>]*>|<\/div>/i', $line, $m, PREG_OFFSET_CAPTURE);
    foreach ($m[0] as $match) {
        $tag = $match[0];
        if (str_starts_with(strtolower($tag), '<div')) {
            $divStack[] = $lineNum;
        } else {
            if (empty($divStack)) {
                echo "Extra </div> on line {$lineNum}\n";
            } else {
                array_pop($divStack);
            }
        }
    }
}
echo "Unclosed <div> count: " . count($divStack) . "\n";
