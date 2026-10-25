<?php
$ctx = stream_context_create(['http' => ['ignore_errors' => true]]);
$res = @file_get_contents('http://localhost:8080/ForensicNacoc/public/', false, $ctx);
echo "Headers:\n" . implode("\n", $http_response_header ?? []) . "\n";
echo "Body length: " . strlen($res ?? '') . "\n";
