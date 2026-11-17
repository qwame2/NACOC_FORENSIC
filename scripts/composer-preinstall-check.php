<?php

/**
 * Pre-Installation Security Guard for Composer (PHP) packages.
 *
 * Usage:
 *   php scripts/composer-preinstall-check.php <vendor/package> [--dev] [--dry-run]
 *   composer safe-require <vendor/package>
 */

if ($argc < 2) {
    echo "🛡️  NACOC Forensic Composer Pre-Install Security Scanner\n";
    echo "Usage:\n";
    echo "  php scripts/composer-preinstall-check.php <vendor/package> [--dev] [--dry-run]\n";
    exit(0);
}

$rawArgs = array_slice($argv, 1);
$packages = [];
$isDev = false;
$isDryRun = false;

foreach ($rawArgs as $arg) {
    if ($arg === '--dev') {
        $isDev = true;
    } elseif ($arg === '--dry-run' || $arg === '-d') {
        $isDryRun = true;
    } elseif (!str_starts_with($arg, '-')) {
        $packages[] = $arg;
    }
}

if (empty($packages)) {
    echo "❌ No package specified.\n";
    exit(1);
}

const POPULAR_PHP_PACKAGES = [
    'laravel/framework', 'laravel/tinker', 'laravel/sanctum', 'laravel/breeze',
    'guzzlehttp/guzzle', 'guzzlehttp/psr7', 'symfony/console', 'symfony/http-foundation',
    'symfony/routing', 'monolog/monolog', 'predis/predis', 'phpunit/phpunit',
    'mockery/mockery', 'fakerphp/faker', 'nesbot/carbon', 'ramsey/uuid'
];

function checkComposerTyposquat(string $candidate): ?string
{
    $candidate = strtolower(trim($candidate));
    foreach (POPULAR_PHP_PACKAGES as $pop) {
        if ($candidate === $pop) return null;
        $lev = levenshtein($candidate, $pop);
        if ($lev >= 1 && $lev <= 2 && abs(strlen($candidate) - strlen($pop)) <= 2) {
            return "Potential typosquatting detected: '{$candidate}' closely resembles trusted package '{$pop}' (Distance: {$lev}).";
        }
    }
    return null;
}

function fetchJsonUrl(string $url): ?array
{
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: NACOC-Forensic-Security-Scanner/1.0\r\n",
            'timeout' => 8,
            'ignore_errors' => true,
        ],
    ]);
    $res = @file_get_contents($url, false, $ctx);
    if (!$res) return null;
    $data = json_decode($res, true);
    return is_array($data) ? $data : null;
}

echo "🛡️  NACOC Forensic Composer Pre-Install Security Check\n";
echo "   Checking " . count($packages) . " package(s) on Packagist...\n\n";

$allAllowed = true;

foreach ($packages as $pkgSpec) {
    $parts = explode(':', $pkgSpec);
    $pkgName = $parts[0];
    $version = $parts[1] ?? null;

    if (!str_contains($pkgName, '/')) {
        echo "❌ BLOCKED [{$pkgName}]: Composer packages must be in 'vendor/package' format.\n";
        $allAllowed = false;
        continue;
    }

    $typoAlert = checkComposerTyposquat($pkgName);
    if ($typoAlert) {
        echo "⚠️  ADVISORY WARNING [{$pkgName}]: {$typoAlert}\n";
    }

    echo "🔎 Checking Packagist repository for [{$pkgName}]...\n";
    $url = "https://packagist.org/packages/{$pkgName}.json";
    $packagistData = fetchJsonUrl($url);

    if (!$packagistData || !isset($packagistData['package'])) {
        echo "❌ BLOCKED [{$pkgName}]: Package not found on Packagist. Check for typos or unofficial sources.\n";
        $allAllowed = false;
        continue;
    }

    $pkg = $packagistData['package'];
    $abandoned = $pkg['abandoned'] ?? false;
    $replacement = is_string($abandoned) ? $abandoned : null;

    if ($abandoned) {
        echo "⚠️  WARNING [{$pkgName}]: Package is ABANDONED by its maintainer" . ($replacement ? " (Use: {$replacement})" : "") . ".\n";
    }

    // Check advisories on Packagist API
    $securityUrl = "https://packagist.org/api/security-advisories/?packages[]=" . urlencode($pkgName);
    $advisoryData = fetchJsonUrl($securityUrl);
    $advisories = $advisoryData['advisories'][$pkgName] ?? [];

    if (!empty($advisories)) {
        echo "⚠️  ADVISORIES FOUND for [{$pkgName}] (" . count($advisories) . " advisory/advisories):\n";
        foreach ($advisories as $adv) {
            echo "   • {$adv['title']} (Affected: {$adv['affectedVersions']})\n";
            echo "     Link: {$adv['link']}\n";
        }
    } else {
        echo "✅ [{$pkgName}] has no active public advisories listed on Packagist.\n";
    }
}

if (!$allAllowed) {
    echo "\n🚨 Installation aborted by Composer Security Guard.\n";
    exit(1);
}

if ($isDryRun) {
    echo "\n📋 Dry-run complete. Package(s) passed preliminary verification.\n";
    exit(0);
}

echo "\n📦 Proceeding with Composer require...\n";
$cmd = ['composer', 'require'];
if ($isDev) $cmd[] = '--dev';
foreach ($packages as $p) $cmd[] = $p;

$proc = proc_open($cmd, [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes);
$exitCode = proc_close($proc);

if ($exitCode === 0) {
    echo "\n🔍 Running post-install Composer audit...\n";
    passthru('composer audit --locked');
}

exit($exitCode);

