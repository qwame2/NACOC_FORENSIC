<?php
$urls = [
    'http://localhost/Evidence_Management',
    'http://localhost:80/Evidence_Management',
    'http://127.0.0.1/Evidence_Management',
    'http://localhost/ForensicNacoc/public/Evidence_Management'
];
foreach ($urls as $u) {
    $ctx = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 3]]);
    $res = @file_get_contents($u, false, $ctx);
    $status = $http_response_header[0] ?? 'NO_RESPONSE';
    echo "$u => $status (len: " . strlen($res ?? '') . ")\n";
}
