<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SecurityAuditCommandTest extends TestCase
{
    public function test_security_audit_command_is_registered_and_runs(): void
    {
        $exitCode = Artisan::call('security:audit', [
            '--only' => 'composer',
            '--json' => true,
        ]);

        $output = Artisan::output();
        $this->assertJson($output);

        $decoded = json_decode($output, true);
        $this->assertArrayHasKey('timestamp', $decoded);
        $this->assertArrayHasKey('composer', $decoded);
        $this->assertArrayHasKey('summary', $decoded);
        $this->assertArrayHasKey('composer_issues', $decoded['summary']);
    }

    public function test_security_audit_command_runs_for_npm(): void
    {
        $exitCode = Artisan::call('security:audit', [
            '--only' => 'npm',
            '--json' => true,
        ]);

        $output = Artisan::output();
        $this->assertJson($output);

        $decoded = json_decode($output, true);
        $this->assertArrayHasKey('npm', $decoded);
        $this->assertArrayHasKey('npm_issues', $decoded['summary']);
    }
}

