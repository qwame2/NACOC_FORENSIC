<?php

namespace Tests\Feature;

use App\Models\ForensicCase;
use App\Models\SingleSuspectCase;
use App\Models\MultipleSuspectCase;
use App\Models\Exhibit;
use App\Models\DpfReport;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\DigitalForensicsCaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigitalForensicsCaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_digital_forensics_case_seeder_populates_system_with_digital_cases(): void
    {
        // Setup initial user for creator/officer attribution
        User::factory()->create([
            'fullname' => 'Grey Essen',
            'username' => 'grey',
            'unit'     => 'Digital & Physical Forensic Unit',
            'is_head'  => true,
            'is_staff' => false,
        ]);

        $this->seed(DigitalForensicsCaseSeeder::class);

        // Verify cases seeded
        $seededCases = [
            'NCC-2026-8102',
            'NCC-2026-8215',
            'NCC-2026-8340',
            'NCC-2026-8472',
            'NCC-2026-8591',
            'NCC-2026-8654',
            'NCC-2026-8799',
            'NCC-2026-8888',
        ];

        foreach ($seededCases as $ref) {
            $case = ForensicCase::find($ref);
            $this->assertNotNull($case, "Case {$ref} should be found");
            $this->assertTrue($case->isDigitalCase(), "Case {$ref} should be recognized as a digital case");

            $elecSuspects = $case->getElectronicDeviceSuspects();
            $this->assertNotEmpty($elecSuspects, "Case {$ref} should have electronic device suspects");

            // Verify exhibits
            $exhibits = Exhibit::whereHas('singleCase', fn($q) => $q->where('case_number', $ref))
                ->orWhereHas('multipleCase', fn($q) => $q->where('case_number', $ref))
                ->get();

            $this->assertGreaterThanOrEqual(3, $exhibits->count(), "Case {$ref} should have at least 3 exhibits");
            foreach ($exhibits as $ex) {
                $this->assertEquals('Electronic Devices', $ex->exhibit_category);
                $this->assertNotEmpty($ex->dev_model);
            }

            // Verify DPF report
            $dpfReport = DpfReport::where('case_number', $ref)->first();
            $this->assertNotNull($dpfReport, "Case {$ref} should have an associated DpfReport");
            $this->assertNotEmpty($dpfReport->dpf_code);
            $this->assertEquals('YES', $dpfReport->extraction_analysis_conducted);
            $this->assertNotEmpty($dpfReport->hash_checksum);

            // Verify Audit Logs
            $auditLogs = AuditLog::where('case_number', $ref)->get();
            $this->assertGreaterThanOrEqual(1, $auditLogs->count(), "Case {$ref} should have audit logs");
        }
    }

    public function test_seeder_is_idempotent_and_does_not_create_duplicates(): void
    {
        User::factory()->create([
            'fullname' => 'Grey Essen',
            'username' => 'grey',
            'unit'     => 'Digital & Physical Forensic Unit',
            'is_head'  => true,
            'is_staff' => false,
        ]);

        $this->seed(DigitalForensicsCaseSeeder::class);
        $initialCount = SingleSuspectCase::count() + MultipleSuspectCase::count();

        // Run seeder a second time
        $this->seed(DigitalForensicsCaseSeeder::class);
        $secondCount = SingleSuspectCase::count() + MultipleSuspectCase::count();

        $this->assertEquals($initialCount, $secondCount, 'Seeding again should not create duplicate cases');
    }
}

