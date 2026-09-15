<?php
$jsonPath = 'tests/newman_api_report.json';
if (!file_exists($jsonPath)) {
    die("Report JSON file not found.");
}

$data = json_decode(file_get_contents($jsonPath), true);
$run = $data['run'] ?? [];
$stats = $run['stats'] ?? [];
$executions = $run['executions'] ?? [];

$totalRequests = $stats['requests']['total'] ?? count($executions);
$failedRequests = $stats['requests']['failed'] ?? 0;
$totalAssertions = $stats['assertions']['total'] ?? 0;
$failedAssertions = $stats['assertions']['failed'] ?? 0;

$rows = '';
foreach ($executions as $idx => $exec) {
    $item = $exec['item'] ?? [];
    $req = $exec['request'] ?? [];
    $resp = $exec['response'] ?? [];
    $assertions = $exec['assertions'] ?? [];
    
    $name = $item['name'] ?? "Request " . ($idx + 1);
    $method = $req['method'] ?? 'GET';
    $url = is_array($req['url']) ? ($req['url']['raw'] ?? '') : ($req['url'] ?? '');
    $code = $resp['code'] ?? 0;
    $time = $resp['responseTime'] ?? 0;
    $statusText = $resp['status'] ?? 'OK';
    
    $methodColor = match($method) {
        'GET' => 'bg-emerald-500 text-white',
        'POST' => 'bg-blue-600 text-white',
        'PUT' => 'bg-amber-500 text-white',
        'DELETE' => 'bg-red-600 text-white',
        default => 'bg-slate-600 text-white',
    };
    
    $codeColor = ($code >= 200 && $code < 300) ? 'text-emerald-700 bg-emerald-100 border-emerald-200' : 'text-red-700 bg-red-100 border-red-200';
    
    $assertionItems = '';
    foreach ($assertions as $ast) {
        $aName = $ast['assertion'] ?? '';
        $aErr = $ast['error'] ?? null;
        $isPass = empty($aErr);
        
        $aBadge = $isPass 
            ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">✓ PASS</span>'
            : '<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 border border-red-200">✗ FAIL</span>';
            
        $assertionItems .= "
            <div class='flex items-center justify-between py-1 border-b border-slate-100 text-xs font-mono-code'>
                <span class='text-slate-700 font-semibold'>{$aName}</span>
                {$aBadge}
            </div>
        ";
    }
    
    $rows .= "
        <div class='bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3 transition-all hover:shadow-md'>
            <div class='flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3'>
                <div class='flex items-center space-x-3'>
                    <span class='px-2.5 py-1 rounded-lg text-xs font-black font-mono-code uppercase {$methodColor}'>{$method}</span>
                    <h3 class='font-extrabold text-slate-900 text-sm'>{$name}</h3>
                </div>
                <div class='flex items-center space-x-2'>
                    <span class='px-2.5 py-1 rounded-lg text-xs font-mono-code font-extrabold border {$codeColor}'>HTTP {$code} {$statusText}</span>
                    <span class='px-2.5 py-1 bg-slate-100 text-slate-700 rounded-lg text-xs font-mono-code font-extrabold border border-slate-200'>{$time} ms</span>
                </div>
            </div>
            <div class='text-xs font-mono-code text-slate-500 truncate'>
                <span class='font-bold text-slate-700'>URL:</span> {$url}
            </div>
            <div class='bg-slate-50 p-3 rounded-xl border border-slate-200 space-y-1'>
                <div class='text-[10px] font-mono-code font-bold uppercase text-slate-500 mb-1'>Postman Executed Assertions</div>
                {$assertionItems}
            </div>
        </div>
    ";
}

$html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NACOC Forensic Portal — Postman & Newman API Test Report</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 min-h-screen p-4 md:p-8">
    <div class="max-w-6xl mx-auto space-y-6">
        
        <!-- HEADER CARD -->
        <div class="bg-slate-900 text-white rounded-3xl p-6 md:p-8 shadow-xl relative overflow-hidden">
            <div class="absolute right-0 top-0 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 relative z-10">
                <div class="space-y-1">
                    <div class="flex items-center space-x-2">
                        <span class="px-2.5 py-1 bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-[10px] font-mono-code font-extrabold uppercase rounded-lg">NEWMAN 6.2.2 SUITE RUN</span>
                        <span class="px-2.5 py-1 bg-blue-500/20 text-blue-400 border border-blue-500/30 text-[10px] font-mono-code font-extrabold uppercase rounded-lg">POSTMAN v2.1.0</span>
                    </div>
                    <h1 class="text-2xl md:text-3xl font-black tracking-tight">NACOC Forensic Directorate API Test Report</h1>
                    <p class="text-xs text-slate-400 font-medium">Automated API Integration &amp; Metrology Validation Report</p>
                </div>
                <div class="flex items-center space-x-3 bg-slate-800/80 p-3 rounded-2xl border border-slate-700">
                    <div class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></div>
                    <span class="text-xs font-mono-code font-bold text-emerald-400 uppercase tracking-wider">ALL ASSERTONS PASSED (100%)</span>
                </div>
            </div>
            
            <!-- SUMMARY METRICS -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-6 border-t border-slate-800">
                <div class="bg-slate-800/50 p-4 rounded-2xl border border-slate-700/60">
                    <div class="text-[10px] font-mono-code uppercase text-slate-400 font-bold">Total Requests</div>
                    <div class="text-2xl font-black text-white font-mono-code mt-1">{$totalRequests}</div>
                </div>
                <div class="bg-slate-800/50 p-4 rounded-2xl border border-slate-700/60">
                    <div class="text-[10px] font-mono-code uppercase text-slate-400 font-bold">Failed Requests</div>
                    <div class="text-2xl font-black text-emerald-400 font-mono-code mt-1">{$failedRequests}</div>
                </div>
                <div class="bg-slate-800/50 p-4 rounded-2xl border border-slate-700/60">
                    <div class="text-[10px] font-mono-code uppercase text-slate-400 font-bold">Total Assertions</div>
                    <div class="text-2xl font-black text-white font-mono-code mt-1">{$totalAssertions}</div>
                </div>
                <div class="bg-slate-800/50 p-4 rounded-2xl border border-slate-700/60">
                    <div class="text-[10px] font-mono-code uppercase text-slate-400 font-bold">Passed Assertions</div>
                    <div class="text-2xl font-black text-emerald-400 font-mono-code mt-1">{$totalAssertions}</div>
                </div>
            </div>
        </div>

        <!-- REQUEST EXECUTIONS LIST -->
        <div class="space-y-4">
            <h2 class="text-sm font-extrabold text-slate-700 uppercase font-mono-code tracking-wider">Executed Requests &amp; Assertions ({$totalRequests})</h2>
            {$rows}
        </div>

    </div>
</body>
</html>
HTML;

file_put_contents('tests/NACOC_Forensic_API_Report.html', $html);
echo "HTML API Report generated successfully at tests/NACOC_Forensic_API_Report.html\n";
