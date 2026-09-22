<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\ForensicCase;
use Illuminate\Support\Facades\Auth;

$user = User::first();
if (!$user) {
    echo "NO USER FOUND IN DB\n";
    exit;
}

Auth::login($user);

$case = ForensicCase::where('case_number', 'CSS-2026-5584')->first();
echo "Testing as user: {$user->email} for case: {$case->case_number}\n";

$response = $app->make('Illuminate\Contracts\Http\Kernel')->handle(
    $request = Illuminate\Http\Request::create('/cases/CSS-2026-5584/pdf', 'GET')
);

echo "Status Code: " . $response->getStatusCode() . "\n";
$content = $response->getContent();
file_put_contents('scratch/auth_pdf_response.html', $content);
echo "Content Length: " . strlen($content) . "\n";

echo "ATTA BUDO occurrences: " . substr_count($content, 'ATTA BUDO') . "\n";
echo "ASSOCIATE DOSSIER occurrences: " . substr_count($content, 'ASSOCIATE DOSSIER') . "\n";

$lines = explode("\n", $content);
foreach ($lines as $i => $line) {
    if (strpos($line, 'ATTA BUDO') !== false || strpos($line, 'ASSOCIATE DOSSIER') !== false) {
        echo "Line " . ($i + 1) . ": " . trim(strip_tags($line)) . "\n";
    }
}
