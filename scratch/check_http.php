<?php
$ch = curl_init('http://127.0.0.1:8000/cases/CSS-2026-5584/pdf');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$res = curl_exec($ch);
curl_close($ch);

echo $res;
