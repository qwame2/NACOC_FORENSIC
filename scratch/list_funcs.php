<?php
$content = file_get_contents('app/Http/Controllers/CaseController.php');
preg_match_all('/function\s+([a-zA-Z0-9_]+)/', $content, $m);
print_r($m[1]);
