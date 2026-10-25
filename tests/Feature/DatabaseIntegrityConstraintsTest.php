<?php

namespace Tests\Feature;

use App\Models\Exhibit;
use App\Models\LaboratoryExaminationResult;
use App\Models\SingleSuspectCase;
use App\Models\MultipleSuspectCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabaseIntegrityConstraintsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function eloquent_trait_prevents_duplicate_case_id_overlap_on_exhibits()
    {
        $single = SingleSuspectCase::create([
            'case_number' => 'CSS-2026-INT01',
            'title' => 'Single Case Integrity Test',
            'unit_name' => 'Crime Scene Support',
            'suspect_name' => 'Single Suspect A',
        ]);

        $multi = MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-INT02',
            'title' => 'Multi Case Integrity Test',
            'unit_name' => 'Crime Scene Support',
        ]);

        // Scenario 1: Case type is SINGLE, but someone attempted to pass both IDs as the same value
        $exSingle = Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $single->id,
            'multiple_suspect_case_id' => $single->id, // identical ID passed
            'exhibit_number' => 1,
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Cannabis Sativa',
        ]);

        $this->assertEquals($single->id, $exSingle->single_suspect_case_id);
        $this->assertNull($exSingle->multiple_suspect_case_id);
        $this->assertEquals('SINGLE', $exSingle->case_type);

        // Scenario 2: Case type is MULTIPLE, but someone attempted to pass both IDs as the same value
        $exMulti = Exhibit::create([
            'case_type' => 'MULTIPLE',
            'single_suspect_case_id' => $multi->id, // identical ID passed
            'multiple_suspect_case_id' => $multi->id,
            'exhibit_number' => 2,
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Cocaine Powder',
        ]);

        $this->assertNull($exMulti->single_suspect_case_id);
        $this->assertEquals($multi->id, $exMulti->multiple_suspect_case_id);
        $this->assertEquals('MULTIPLE', $exMulti->case_type);
    }

    #[Test]
    public function eloquent_trait_prevents_overlap_on_laboratory_examination_results()
    {
        $multi = MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-INT03',
            'title' => 'Multi Lab Integrity Test',
            'unit_name' => 'Forensic Laboratory',
        ]);

        $lab = LaboratoryExaminationResult::create([
            'case_type' => 'MULTIPLE',
            'single_suspect_case_id' => 99,
            'multiple_suspect_case_id' => $multi->id,
            'laboratory_number' => 'Lab No. 99/FS01/2026',
            'presumptive_result' => 'YES',
            'confirmatory_result' => 'YES',
            'substance_identification' => 'Heroin Hydrochloride',
        ]);

        $this->assertNull($lab->single_suspect_case_id);
        $this->assertEquals($multi->id, $lab->multiple_suspect_case_id);
    }

    #[Test]
    public function database_check_constraint_rejects_raw_insert_with_overlapping_case_ids()
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('Database-level ALTER TABLE CHECK constraints are enforced by MariaDB/MySQL in production.');
        }

        $this->expectException(\Illuminate\Database\QueryException::class);

        // Attempt direct SQL bypass inserting both single and multiple suspect case IDs
        DB::table('exhibits')->insert([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => 50,
            'multiple_suspect_case_id' => 50,
            'exhibit_number' => 'EXH-99',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Illegal Overlap Exhibit',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    #[Test]
    public function database_check_constraint_rejects_negative_counts_on_multiple_suspect_cases()
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('Database-level ALTER TABLE CHECK constraints are enforced by MariaDB/MySQL in production.');
        }

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('multiple_suspect_cases')->insert([
            'case_number' => 'CSS-2026-NEG01',
            'title' => 'Negative Count Case',
            'unit_name' => 'Crime Scene Support',
            'total_suspects_count' => -5, // Invalid negative count
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    #[Test]
    public function database_check_constraint_rejects_impossible_age_on_case_suspects()
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('Database-level ALTER TABLE CHECK constraints are enforced by MariaDB/MySQL in production.');
        }

        $multi = MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-INT04',
            'title' => 'Age Constraint Test Case',
            'unit_name' => 'Crime Scene Support',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('case_suspects')->insert([
            'multiple_suspect_case_id' => $multi->id,
            'suspect_number' => 1,
            'suspect_name' => 'Impossible Age Suspect',
            'age' => 999, // Impossible age exceeding 150
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
