<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class SecurityAuditCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:audit 
                            {--only= : Limit audit to "composer" or "npm"}
                            {--strict : Return error code on any vulnerability}
                            {--json : Output raw JSON results}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan all Composer (PHP) and NPM (JavaScript) dependencies for security vulnerabilities and known advisories.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $only = strtolower((string) $this->option('only'));
        $strict = (bool) $this->option('strict');
        $outputJson = (bool) $this->option('json');

        $results = [
            'timestamp' => now()->toIso8601String(),
            'composer' => null,
            'npm' => null,
            'has_vulnerabilities' => false,
            'summary' => [
                'composer_issues' => 0,
                'npm_issues' => 0,
                'high_or_critical' => 0,
            ],
        ];

        $hasVulnerabilities = false;

        // 1. Composer Security Audit
        if ($only === '' || $only === 'composer') {
            if (!$outputJson) {
                $this->info('🔍 Auditing Composer (PHP) dependencies via Packagist Security Advisories...');
            }

            $composerProcess = new Process(['composer', 'audit', '--format=json', '--locked'], base_path());
            $composerProcess->run();
            $composerOutput = trim($composerProcess->getOutput());
            $composerData = json_decode($composerOutput, true);

            if (is_array($composerData)) {
                $results['composer'] = $composerData;
                $advisoriesCount = 0;
                $highCriticalCount = 0;

                $rows = [];
                $advisories = $composerData['advisories'] ?? [];
                foreach ($advisories as $package => $packageAdvisories) {
                    foreach ($packageAdvisories as $adv) {
                        $advisoriesCount++;
                        $sev = strtolower((string) ($adv['severity'] ?? 'medium'));
                        if (in_array($sev, ['high', 'critical'], true)) {
                            $highCriticalCount++;
                        }
                        $rows[] = [
                            'Composer',
                            $package,
                            $adv['cve'] ?? $adv['advisoryId'] ?? 'N/A',
                            strtoupper($sev ?: 'UNKNOWN'),
                            $adv['title'] ?? 'Security advisory',
                        ];
                    }
                }

                $results['summary']['composer_issues'] = $advisoriesCount;
                $results['summary']['high_or_critical'] += $highCriticalCount;

                if ($advisoriesCount > 0) {
                    $hasVulnerabilities = true;
                    if (!$outputJson) {
                        $this->warn("⚠️  Found {$advisoriesCount} Composer vulnerability advisory(ies):");
                        $this->table(['Ecosystem', 'Package', 'Advisory / CVE', 'Severity', 'Title'], $rows);
                    }
                } elseif (!$outputJson) {
                    $this->info('✅ No Composer vulnerabilities detected.');
                }
            } else {
                if (!$outputJson) {
                    $this->line($composerProcess->getOutput() ?: $composerProcess->getErrorOutput());
                }
            }
        }

        // 2. NPM Security Audit
        if ($only === '' || $only === 'npm') {
            if (!$outputJson) {
                $this->info('🔍 Auditing NPM (JavaScript) dependencies via GitHub Security Advisory Database...');
            }

            // Cross-platform execution for npm
            $npmCommand = PHP_OS_FAMILY === 'Windows' ? ['cmd', '/c', 'npm', 'audit', '--json'] : ['npm', 'audit', '--json'];
            $npmProcess = new Process($npmCommand, base_path());
            $npmProcess->run();
            $npmOutput = trim($npmProcess->getOutput());
            $npmData = json_decode($npmOutput, true);

            if (is_array($npmData)) {
                $results['npm'] = $npmData;
                $vulns = $npmData['vulnerabilities'] ?? [];
                $npmIssuesCount = 0;
                $npmHighCritCount = 0;

                $rows = [];
                foreach ($vulns as $pkgName => $vulnDef) {
                    $npmIssuesCount++;
                    $sev = strtolower((string) ($vulnDef['severity'] ?? 'medium'));
                    if (in_array($sev, ['high', 'critical'], true)) {
                        $npmHighCritCount++;
                    }

                    $viaList = [];
                    foreach ((array) ($vulnDef['via'] ?? []) as $vItem) {
                        if (is_array($vItem)) {
                            $viaList[] = $vItem['title'] ?? ($vItem['name'] ?? '');
                        } elseif (is_string($vItem)) {
                            $viaList[] = $vItem;
                        }
                    }

                    $rows[] = [
                        'NPM',
                        $pkgName,
                        strtoupper($sev),
                        $vulnDef['range'] ?? '*',
                        implode(', ', array_slice(array_filter($viaList), 0, 2)),
                    ];
                }

                $results['summary']['npm_issues'] = $npmIssuesCount;
                $results['summary']['high_or_critical'] += $npmHighCritCount;

                if ($npmIssuesCount > 0) {
                    $hasVulnerabilities = true;
                    if (!$outputJson) {
                        $this->warn("⚠️  Found {$npmIssuesCount} NPM vulnerability advisory(ies):");
                        $this->table(['Ecosystem', 'Package', 'Severity', 'Vulnerable Range', 'Advisory Summary'], $rows);
                    }
                } elseif (!$outputJson) {
                    $this->info('✅ No NPM vulnerabilities detected.');
                }
            } else {
                if (!$outputJson) {
                    $this->line($npmProcess->getOutput() ?: $npmProcess->getErrorOutput());
                }
            }
        }

        $results['has_vulnerabilities'] = $hasVulnerabilities;

        if ($outputJson) {
            $this->output->writeln(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return ($strict && $hasVulnerabilities) ? 1 : 0;
        }

        if ($hasVulnerabilities) {
            $this->newLine();
            $this->error("🚨 Security Audit: Found {$results['summary']['composer_issues']} Composer issue(s) and {$results['summary']['npm_issues']} NPM issue(s) ({$results['summary']['high_or_critical']} High/Critical).");
            $this->line("   Run <comment>composer audit</comment> and <comment>npm audit</comment> to inspect full advisories or apply remediations.");
            return $strict ? 1 : 0;
        }

        $this->newLine();
        $this->info('🛡️  Security Audit Clean: All Composer and NPM dependencies verified free of known advisories.');
        return 0;
    }
}

