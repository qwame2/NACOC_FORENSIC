<?php

namespace Tests\Feature;

use App\Models\AssociateLabExamination;
use App\Models\ForensicCase;
use App\Models\LaboratoryExaminationResult;
use App\Models\ReferenceRegistry;
use App\Models\SingleSuspectCase;
use App\Models\User;
use App\Services\CaseDocumentService;
use App\Services\CaseNumberService;
use App\Services\CaseReferenceService;
use App\Services\LaboratoryExamService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReferenceNumberSystemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function case_reference_follows_ncc_dynamic_year_random_four_digit_format()
    {
        $service = app(CaseNumberService::class);
        $currentYear = (int)date('Y');

        $caseNumber = $service->generate();

        // Must match NCC-YYYY-NNNN exactly
        $this->assertMatchesRegularExpression('/^NCC-' . $currentYear . '-\d{4}$/', $caseNumber);

        // Prefix must be NCC
        $this->assertStringStartsWith('NCC-', $caseNumber);

        // Year must be dynamic current year
        $parts = explode('-', $caseNumber);
        $this->assertEquals(3, count($parts));
        $this->assertEquals((string)$currentYear, $parts[1]);

        // Suffix must be four digits
        $this->assertEquals(4, strlen($parts[2]));
        $this->assertTrue(is_numeric($parts[2]));
    }

    #[Test]
    public function case_reference_generates_random_non_sequential_numbers()
    {
        $service = app(CaseNumberService::class);
        $generated = [];
        $sampleSize = 25;

        for ($i = 0; $i < $sampleSize; $i++) {
            $ref = $service->generate();
            $generated[] = $ref;
        }

        // All generated references must be unique
        $this->assertCount($sampleSize, array_unique($generated));

        // Verify references are not strictly sequential (+1)
        $suffixes = array_map(function ($ref) {
            $p = explode('-', $ref);
            return (int)$p[2];
        }, $generated);

        $sequentialIncrements = 0;
        for ($i = 1; $i < count($suffixes); $i++) {
            if ($suffixes[$i] === $suffixes[$i - 1] + 1) {
                $sequentialIncrements++;
            }
        }

        // Out of 25 random numbers, strict +1 sequence should not predominate
        $this->assertLessThan(5, $sequentialIncrements);
    }

    #[Test]
    public function case_reference_collision_detection_and_automatic_retry()
    {
        $service = app(CaseNumberService::class);
        $currentYear = date('Y');

        // Seed a known reference into the registry and single suspect cases
        $collidingRef = "NCC-{$currentYear}-5555";
        ReferenceRegistry::reserve($collidingRef, 'CASE');
        SingleSuspectCase::create([
            'case_number' => $collidingRef,
            'title'       => 'Collision Test Case',
            'status'      => 'Active',
        ]);

        $this->assertTrue($service->caseNumberExists($collidingRef));

        // Generate multiple case numbers and ensure none collide with the pre-seeded one
        for ($i = 0; $i < 10; $i++) {
            $newRef = $service->generate();
            $this->assertNotEquals($collidingRef, $newRef);
            $this->assertMatchesRegularExpression('/^NCC-' . $currentYear . '-\d{4}$/', $newRef);
        }
    }

    #[Test]
    public function case_number_with_unique_retry_recovers_from_concurrency_race_condition()
    {
        $service = app(CaseNumberService::class);
        $attempts = 0;

        $result = $service->withUniqueRetry(function (string $ref) use (&$attempts) {
            $attempts++;
            if ($attempts === 1) {
                // Simulate race condition: another concurrent process inserted this reference
                throw new QueryException(
                    'mysql',
                    'insert into `single_suspect_cases` (`case_number`) values (?)',
                    [$ref],
                    new \Exception("Duplicate entry '{$ref}' for key 'single_suspect_cases_case_number_unique'", 1062)
                );
            }

            return SingleSuspectCase::create([
                'case_number' => $ref,
                'title'       => 'Retry Test Case',
                'status'      => 'Active',
            ]);
        });

        $this->assertEquals(2, $attempts);
        $this->assertInstanceOf(SingleSuspectCase::class, $result);
        $this->assertDatabaseHas('single_suspect_cases', [
            'id' => $result->id,
        ]);
        $this->assertTrue(ReferenceRegistry::isReserved($result->case_number));
    }

    #[Test]
    public function exam_reference_preserves_prefix_and_generates_random_identifier()
    {
        $service = app(CaseReferenceService::class);

        // Lab Analysis Unit -> NLA-FM-T{RANDOM}-F
        $nlaRef = $service->generateExamReference('Forensic Laboratory');
        $this->assertMatchesRegularExpression('/^NLA-FM-T\d{4}-F$/', $nlaRef);
        $this->assertNotEquals('NLR-FM-T35-F', $nlaRef);
        $this->assertNotEquals('NLA-FM-T35-F', $nlaRef);

        // Digital Forensics -> DPF-FM-T{RANDOM}-F
        $dpfRef = $service->generateExamReference('Digital & Physical Forensic Unit');
        $this->assertMatchesRegularExpression('/^DPF-FM-T\d{4}-F$/', $dpfRef);

        // Crime Scene Support -> prefix is ALWAYS NLA- (never CSS-)
        $cssRef = $service->generateExamReference('Crime Scene Support');
        $this->assertMatchesRegularExpression('/^NLA-FM-T\d{4}-F$/', $cssRef);
        $this->assertStringStartsNotWith('CSS-', $cssRef);

        // Evidence Management -> prefix is ALWAYS NLA- (never EMU-)
        $emuRef = $service->generateExamReference('Evidence Management');
        $this->assertMatchesRegularExpression('/^NLA-FM-T\d{4}-F$/', $emuRef);
        $this->assertStringStartsNotWith('EMU-', $emuRef);
    }

    #[Test]
    public function exam_references_are_guaranteed_unique_across_batch()
    {
        $service = app(CaseReferenceService::class);
        $generated = [];
        $sampleSize = 30;

        for ($i = 0; $i < $sampleSize; $i++) {
            $ref = $service->generateExamReference('Forensic Laboratory');
            $generated[] = $ref;
        }

        $this->assertCount($sampleSize, array_unique($generated));

        foreach ($generated as $ref) {
            $this->assertMatchesRegularExpression('/^NLA-FM-T\d{4}-F$/', $ref);
            $this->assertTrue(ReferenceRegistry::isReserved($ref));
        }
    }

    #[Test]
    public function exam_reference_collision_detection_and_automatic_retry()
    {
        $service = app(CaseReferenceService::class);

        $collidingRef = 'NLA-FM-T4444-F';
        ReferenceRegistry::reserve($collidingRef, 'EXAM');
        LaboratoryExaminationResult::create([
            'case_type'      => 'SINGLE',
            'exam_reference' => $collidingRef,
            'lab_unit'       => 'Chemistry & Narcotics Unit',
        ]);

        $this->assertTrue($service->examReferenceExists($collidingRef));

        for ($i = 0; $i < 10; $i++) {
            $newRef = $service->generateExamReference('Forensic Laboratory');
            $this->assertNotEquals($collidingRef, $newRef);
            $this->assertMatchesRegularExpression('/^NLA-FM-T\d{4}-F$/', $newRef);
        }
    }

    #[Test]
    public function laboratory_exam_service_regenerates_static_nlr_fm_t35_f()
    {
        $user = User::factory()->create(['unit' => 'Lab Analysis']);
        $case = ForensicCase::create([
            'case_number' => app(CaseNumberService::class)->generate(),
            'title'       => 'Narcotic Test Case',
            'unit_name'   => 'Lab Analysis',
            'status'      => 'Active',
            'docket_data' => [],
        ]);

        $examService = app(LaboratoryExamService::class);

        // Pass static NLR-FM-T35-F explicitly in payload
        $payload = [
            'exam_reference'            => 'NLR-FM-T35-F',
            'lab_unit'                  => 'Chemistry & Narcotics Unit',
            'presumptive_result'        => 'POSITIVE',
            'confirmatory_result'       => 'YES',
            'substance_identification'  => 'Cocaine Hydrochloride',
        ];

        $docket = $examService->saveExam($case, $payload, 'Kwame Mensah', false, $user->fullname);

        $savedRef = $docket['lab_examination']['exam_reference'];

        // Must NOT be the static NLR-FM-T35-F
        $this->assertNotEquals('NLR-FM-T35-F', $savedRef);
        $this->assertMatchesRegularExpression('/^NLA-FM-T\d{4}-F$/', $savedRef);

        // Database record must match the generated unique reference
        $this->assertDatabaseHas('laboratory_examination_results', [
            'exam_reference' => $savedRef,
        ]);
        $this->assertTrue(ReferenceRegistry::isReserved($savedRef));
    }

    #[Test]
    public function case_document_service_generates_dynamic_random_nlr_code()
    {
        $user = User::factory()->create(['unit' => 'Lab Analysis']);
        $case = ForensicCase::create([
            'case_number' => app(CaseNumberService::class)->generate(),
            'title'       => 'NLR Document Test Case',
            'unit_name'   => 'Lab Analysis',
            'status'      => 'Active',
            'docket_data' => [],
        ]);

        $docService = app(CaseDocumentService::class);
        $request = new Request([
            'case_id'                      => $case->id,
            'case_number'                  => $case->case_number,
            'detailed_exhibit_description' => 'White powder package seized.',
            'condition_of_reserved_sample' => 'sealed',
        ]);

        $result = $docService->processNlrReport($request, $case);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['nlr_code']);
        $this->assertNotEquals('NLR-FM-T35-F', $result['nlr_code']);
        $this->assertMatchesRegularExpression('/^NLA-FM-T\d{4}-F$/', $result['nlr_code']);

        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('nlr_reports'));
        $this->assertDatabaseHas('suspect_preliminary_reports', [
            'case_number' => $case->case_number,
        ]);
        $this->assertTrue(ReferenceRegistry::isReserved($result['nlr_code']));
    }

    #[Test]
    public function database_level_unique_constraint_on_reference_registry()
    {
        ReferenceRegistry::create([
            'reference' => 'NCC-TEST-UNIQUE-001',
            'category'  => 'CASE',
            'prefix'    => 'NCC',
            'year'      => 2026,
        ]);

        $this->expectException(QueryException::class);

        ReferenceRegistry::create([
            'reference' => 'NCC-TEST-UNIQUE-001',
            'category'  => 'CASE',
            'prefix'    => 'NCC',
            'year'      => 2026,
        ]);
    }

    #[Test]
    public function legacy_nlr_reports_table_is_dropped_from_schema()
    {
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('nlr_reports'));
    }

    #[Test]
    public function existing_historical_records_retain_their_reference_numbers()
    {
        $historicalCaseNo = 'NCC-2025-HIST-999';
        $historicalExamRef = 'NLR-FM-T35-F';

        $singleCase = SingleSuspectCase::create([
            'case_number' => $historicalCaseNo,
            'title'       => 'Historical Retained Case',
            'unit_name'   => 'Lab Analysis',
            'status'      => 'Closed',
        ]);

        $labResult = LaboratoryExaminationResult::create([
            'single_suspect_case_id' => $singleCase->id,
            'case_type'              => 'SINGLE',
            'exam_reference'         => $historicalExamRef,
            'lab_unit'               => 'Chemistry & Narcotics Unit',
            'has_saved'              => true,
        ]);

        $case = ForensicCase::wrapRecord($singleCase->fresh(['exhibits', 'labResults']));

        $refService = app(CaseReferenceService::class);

        // Resolving exam reference must retain the historical reference exactly
        $resolved = $refService->resolveExamReference($case, $case->docket_data);
        $this->assertEquals($historicalExamRef, $resolved);

        // Case number on model is completely unchanged
        $this->assertEquals($historicalCaseNo, $case->case_number);
    }

    #[Test]
    public function analysis_examination_reference_prefix_is_always_nla_never_emu_or_css()
    {
        $service = app(CaseReferenceService::class);

        // 1. Generation with Crime Scene Support context must be NLA-
        $cssRef = $service->generateExamReference('Crime Scene Support');
        $this->assertStringStartsWith('NLA-', $cssRef);
        $this->assertStringStartsNotWith('CSS-', $cssRef);

        // 2. Generation with Evidence Management context must be NLA-
        $emuRef = $service->generateExamReference('Evidence Management');
        $this->assertStringStartsWith('NLA-', $emuRef);
        $this->assertStringStartsNotWith('EMU-', $emuRef);

        // 3. Generation with default or Lab Analysis context must be NLA-
        $labRef = $service->generateExamReference('Lab Analysis');
        $this->assertStringStartsWith('NLA-', $labRef);

        // 4. Normalizing legacy EMU- or CSS- reference converts prefix to NLA-
        $this->assertEquals('NLA-FM-T1234-F', $service->normalizeExamReference('EMU-FM-T1234-F'));
        $this->assertEquals('NLA-FM-T5678-F', $service->normalizeExamReference('CSS-FM-T5678-F'));
        $this->assertEquals('NLA-FM-T9999-F', $service->normalizeExamReference('GSA-FM-T9999-F'));
    }
}

