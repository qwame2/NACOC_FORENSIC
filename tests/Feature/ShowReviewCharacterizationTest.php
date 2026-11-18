<?php

namespace Tests\Feature;

use App\Models\AssociateLabExamination;
use App\Models\AuditLog;
use App\Models\CaseSuspect;
use App\Models\DrugScreening;
use App\Models\Exhibit;
use App\Models\ForensicCase;
use App\Models\LaboratoryExaminationResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ShowReviewCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    protected string $snapshotDir;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-03-15 10:30:00'));
        $this->snapshotDir = base_path('tests/Fixtures/snapshots');
        if (!is_dir($this->snapshotDir)) {
            mkdir($this->snapshotDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Build a deterministic, feature-rich forensic case for a given unit context.
     */
    protected function seedCaseForUnit(string $unitKey): array
    {
        $creator = User::factory()->create([
            'fullname' => 'Officer Creator',
            'username' => 'officer.creator',
            'email' => 'creator@nacoc.gov.gh',
            'service_id' => 'SD-100',
            'phone_number' => '0244000100',
            'unit' => 'Crime Scene Support',
            'is_approved' => true,
            'is_staff' => true,
            'is_head' => false,
        ]);

        $unitMap = [
            'css' => ['is_head' => false, 'unit' => 'Crime Scene Support', 'email' => 'css@nacoc.gov.gh', 'fullname' => 'CSS Officer', 'session_role' => 'officer'],
            'lab' => ['is_head' => false, 'unit' => 'Lab Analysis', 'email' => 'lab@nacoc.gov.gh', 'fullname' => 'Lab Analyst', 'session_role' => 'analyst'],
            'dpf' => ['is_head' => false, 'unit' => 'Digital & Physical Forensics', 'email' => 'dpf@nacoc.gov.gh', 'fullname' => 'DPF Officer', 'session_role' => 'officer'],
            'em'  => ['is_head' => false, 'unit' => 'Evidence Management', 'email' => 'em@nacoc.gov.gh', 'fullname' => 'EM Officer', 'session_role' => 'officer'],
            'hof' => ['is_head' => true,  'unit' => 'Forensic Directorate', 'email' => 'hof@nacoc.gov.gh', 'fullname' => 'Director HOF', 'session_role' => 'forensic_head'],
        ];

        $uMeta = $unitMap[$unitKey];
        $viewer = User::factory()->create([
            'fullname' => $uMeta['fullname'],
            'username' => 'viewer.' . $unitKey,
            'email' => $uMeta['email'],
            'service_id' => 'SD-200',
            'phone_number' => '0244000200',
            'unit' => $uMeta['unit'],
            'is_approved' => true,
            'is_staff' => true,
            'is_head' => $uMeta['is_head'],
        ]);

        $docketData = [
            'suspect' => [
                'name' => 'Kwame Mensah',
                'gender' => 'Male',
                'dob' => '1990-05-12',
                'age' => 35,
                'nationality' => 'Ghanaian',
                'occupation' => 'Trader',
                'id_type' => 'Ghana Card',
                'id_number' => 'GHA-123456789-0',
                'address' => '12 Liberation Road, Accra',
                'phone' => '0244112233',
                'mugshots' => [
                    'front' => 'mugshots/kwame_front.jpg',
                    'left' => 'mugshots/kwame_left.jpg',
                    'right' => 'mugshots/kwame_right.jpg',
                ],
                'drug_screening' => [
                    'result' => 'Positive',
                    'purpose' => 'Routine Arrest Screening',
                    'screening_date' => '2026-03-10',
                    'substances_found' => 'THC, Cocaine',
                    'notes' => 'Confirmed rapid urine screen.',
                ],
            ],
            'suspects' => [
                [
                    'id' => 1,
                    'name' => 'Kwame Mensah',
                    'is_primary' => true,
                    'role' => 'Primary Suspect',
                    'gender' => 'Male',
                    'dob' => '1990-05-12',
                    'age' => 35,
                    'nationality' => 'Ghanaian',
                    'mugshots' => [
                        'front' => 'mugshots/kwame_front.jpg',
                    ],
                ],
                [
                    'id' => 2,
                    'name' => 'Abena Owusu',
                    'is_primary' => false,
                    'role' => 'Associate Suspect',
                    'gender' => 'Female',
                    'dob' => '1994-08-20',
                    'age' => 31,
                    'nationality' => 'Ghanaian',
                    'mugshots' => [
                        'front' => 'mugshots/abena_front.jpg',
                    ],
                    'seizures' => [
                        [
                            'exhibit_number' => 'EXH/2026/0002',
                            'exhibit_type' => 'Drugs',
                            'drug_classification' => 'Narcotic',
                            'commodity_name' => 'Cannabis Sativa',
                            'seizure_quantity' => '2 slabs',
                            'seizure_weight' => '150.50',
                            'seizure_unit' => 'g',
                            'associated_person' => 'Abena Owusu',
                            'evidence_photos' => ['exhibits/cannabis_slab.jpg'],
                        ],
                    ],
                ],
            ],
            'seizures' => [
                [
                    'exhibit_number' => 'EXH/2026/0001',
                    'exhibit_type' => 'Drugs',
                    'drug_classification' => 'Narcotic',
                    'commodity_name' => 'Cocaine Hydrochloride',
                    'seizure_quantity' => '5 parcels',
                    'seizure_packaging' => 'Brown cello tape',
                    'seizure_weight' => '500.00',
                    'seizure_unit' => 'g',
                    'seizure_location' => 'Airport Cargo Village',
                    'associated_person' => 'Kwame Mensah',
                    'evidence_photos' => ['exhibits/cocaine_parcel.jpg'],
                ],
                [
                    'exhibit_number' => 'EXH/2026/0003',
                    'exhibit_type' => 'Digital Forensics (Phones/Laptops)',
                    'commodity_name' => 'iPhone 15 Pro',
                    'dev_model' => 'iPhone 15 Pro',
                    'dev_serial_no' => 'SN-IPHONE-9988',
                    'dev_imei1' => '359988776655443',
                    'associated_person' => 'Joint Exhibit',
                ],
            ],
            'unit_involvement' => [
                'Crime Scene Support' => true,
                'Lab Analysis' => true,
                'Digital & Physical Forensics' => true,
                'Evidence Management' => true,
            ],
            'unit_progress' => [
                'Crime Scene Support' => [
                    'status' => 'completed',
                    'officer' => 'CSS Officer',
                    'updated_at' => '2026-03-11 09:00:00',
                    'notes' => 'Scene processed and exhibits bagged.',
                ],
                'Lab Analysis' => [
                    'status' => 'in_progress',
                    'officer' => 'Lab Analyst',
                    'updated_at' => '2026-03-12 14:20:00',
                    'notes' => 'GC-MS analysis underway.',
                ],
                'Digital & Physical Forensics' => [
                    'status' => 'in_progress',
                    'officer' => 'DPF Officer',
                    'updated_at' => '2026-03-13 11:15:00',
                    'notes' => 'Cellebrite extraction started.',
                ],
                'Evidence Management' => [
                    'status' => 'completed',
                    'officer' => 'EM Officer',
                    'updated_at' => '2026-03-11 16:00:00',
                    'notes' => 'Vault storage logged.',
                ],
            ],
            'lab_examination' => [
                'has_saved' => true,
                'laboratory_number' => 'Lab No. 042/2026',
                'exam_reference' => 'NACOC/FSD/LAB/2026/0042',
                'exhibit_number' => 'EXH/2026/0001',
                'analyst' => 'Lab Analyst',
                'date_received' => '2026-03-11',
                'date_started' => '2026-03-12',
                'date_completed' => '2026-03-14',
                'presumptive_result' => '1. Parcel A | Marquis: Positive | Scott: Positive (Blue)',
                'confirmatory_result' => 'GC-MS confirmed Cocaine Hydrochloride (88% purity)',
                'sample_weight_description' => 'Off-white compressed powder',
                'sample_weight_units' => 'g',
                'sample_weight_gross' => '500.00',
                'sample_weight_taken' => '5.00',
                'sample_weight_reserved' => '495.00',
                'substance_identification' => 'Positive',
                'detected_substances' => ['Cocaine'],
                'test_methods' => 'Scott Test, GC-MS',
                'specifications' => 'USP Reference Standard matched',
                'remark' => 'High purity sample.',
                'findings' => 'The submitted exhibit tested positive for Cocaine Hydrochloride.',
            ],
        ];

        $case = ForensicCase::create([
            'case_number' => 'NACOC/2026/0500',
            'laboratory_number' => 'Lab No. 042/2026',
            'title' => 'Republic vs Kwame Mensah & Abena Owusu',
            'suspect_name' => 'Kwame Mensah',
            'exhibit_type' => 'Drugs',
            'exhibit_description' => '5 parcels of suspected Cocaine and 2 slabs of Cannabis',
            'unit_name' => 'Lab Analysis',
            'status' => 'Under Multi-Unit Processing',
            'priority' => 'HIGH',
            'user_id' => $creator->id,
            'docket_data' => $docketData,
            'created_at' => Carbon::parse('2026-03-10 08:00:00'),
            'updated_at' => Carbon::parse('2026-03-14 15:00:00'),
        ]);

        CaseSuspect::where('single_suspect_case_id', $case->id)
            ->orWhere('multiple_suspect_case_id', $case->id)
            ->delete();

        $fkColumn = $case->is_multiple ? 'multiple_suspect_case_id' : 'single_suspect_case_id';

        $suspect1 = CaseSuspect::create([
            $fkColumn => $case->id,
            'suspect_number' => 1,
            'suspect_name' => 'Kwame Mensah',
            'role' => 'Primary Suspect',
            'gender' => 'Male',
            'dob' => '1990-05-12',
            'age' => 35,
            'nationality' => 'Ghanaian',
        ]);

        $suspect2 = CaseSuspect::create([
            $fkColumn => $case->id,
            'suspect_number' => 2,
            'suspect_name' => 'Abena Owusu',
            'role' => 'Associate Suspect',
            'gender' => 'Female',
            'dob' => '1994-08-20',
            'age' => 31,
            'nationality' => 'Ghanaian',
        ]);

        Exhibit::where('single_suspect_case_id', $case->id)
            ->orWhere('multiple_suspect_case_id', $case->id)
            ->delete();

        $exhibit1 = Exhibit::create([
            'case_type' => $case->is_multiple ? 'multiple' : 'single',
            $fkColumn => $case->id,
            'suspect_id' => $suspect1->id,
            'associated_person' => 'Kwame Mensah',
            'exhibit_number' => 'EXH/2026/0001',
            'exhibit_category' => 'Drugs',
            'exhibit_type' => 'Drugs',
            'drug_classification' => 'Narcotic',
            'commodity_name' => 'Cocaine Hydrochloride',
            'seizure_quantity' => '5 parcels',
            'seizure_packaging' => 'Brown cello tape',
            'seizure_weight' => '500.00',
            'seizure_unit' => 'g',
            'seizure_location' => 'Airport Cargo Village',
            'evidence_photos' => ['exhibits/cocaine_parcel.jpg'],
            'qr_token' => 'fixed-qr-token-exh-0001',
        ]);

        Exhibit::create([
            'case_type' => $case->is_multiple ? 'multiple' : 'single',
            $fkColumn => $case->id,
            'suspect_id' => $suspect2->id,
            'associated_person' => 'Abena Owusu',
            'exhibit_number' => 'EXH/2026/0002',
            'exhibit_category' => 'Drugs',
            'exhibit_type' => 'Drugs',
            'drug_classification' => 'Narcotic',
            'commodity_name' => 'Cannabis Sativa',
            'seizure_quantity' => '2 slabs',
            'seizure_weight' => '150.50',
            'seizure_unit' => 'g',
            'evidence_photos' => ['exhibits/cannabis_slab.jpg'],
            'qr_token' => 'fixed-qr-token-exh-0002',
        ]);

        LaboratoryExaminationResult::where('single_suspect_case_id', $case->id)
            ->orWhere('multiple_suspect_case_id', $case->id)
            ->delete();

        LaboratoryExaminationResult::create([
            'case_type' => $case->is_multiple ? 'multiple' : 'single',
            $fkColumn => $case->id,
            'suspect_id' => $suspect1->id,
            'exhibit_id' => $exhibit1->id,
            'laboratory_number' => 'Lab No. 042/2026',
            'exam_reference' => 'NACOC/FSD/LAB/2026/0042',
            'analyst' => 'Lab Analyst',
            'date_received' => '2026-03-11',
            'date_started' => '2026-03-12',
            'date_completed' => '2026-03-14',
            'presumptive_result' => '1. Parcel A | Marquis: Positive | Scott: Positive (Blue)',
            'confirmatory_result' => 'GC-MS confirmed Cocaine Hydrochloride (88% purity)',
            'sample_weight_description' => 'Off-white compressed powder',
            'sample_weight_units' => 'g',
            'sample_weight_gross' => '500.00',
            'sample_weight_taken' => '5.00',
            'sample_weight_reserved' => '495.00',
            'substance_identification' => 'Positive',
            'detected_substances' => ['Cocaine'],
            'test_methods' => 'Scott Test, GC-MS',
            'specifications' => 'USP Reference Standard matched',
            'remark' => 'High purity sample.',
            'findings' => 'The submitted exhibit tested positive for Cocaine Hydrochloride.',
            'has_saved' => true,
        ]);

        AssociateLabExamination::where('case_id', $case->id)->delete();

        AssociateLabExamination::create([
            'case_id' => $case->id,
            'suspect_name' => 'Abena Owusu',
            'associate_id' => (string) $suspect2->id,
            'exhibit_number' => 'EXH/2026/0002',
            'laboratory_number' => 'Lab No. 043/2026',
            'exam_reference' => 'NACOC/FSD/LAB/2026/0043',
            'analyst' => 'Lab Analyst',
            'date_received' => '2026-03-11',
            'date_started' => '2026-03-12',
            'date_completed' => '2026-03-14',
            'presumptive_result' => 'Exhibit 1 (EXH/2026/0002): Fast Blue B -> Positive (Purple-Red)',
            'confirmatory_result' => 'Delta-9-THC identified by GC-MS',
            'sample_weight_description' => 'Compressed plant material',
            'sample_weight_units' => 'g',
            'sample_weight_gross' => '150.50',
            'sample_weight_taken' => '2.50',
            'sample_weight_reserved' => '148.00',
            'substance_identification' => 'Positive',
            'detected_substances' => ['Cannabis (THC)'],
            'test_methods' => 'Fast Blue B, Microscopy, GC-MS',
            'specifications' => 'UNODC Manual matched',
            'remark' => 'Associate sample confirmed.',
            'findings' => 'Cannabis Sativa confirmed.',
            'has_saved' => true,
        ]);

        DrugScreening::where('suspect_id', $suspect1->id)->delete();

        DrugScreening::create([
            'suspect_id' => $suspect1->id,
            'purpose' => 'Routine Arrest Screening',
            'screening_date' => '2026-03-10',
            'result' => 'Positive',
            'substances_found' => 'THC, Cocaine',
            'notes' => 'Confirmed rapid urine screen.',
        ]);

        AuditLog::create([
            'user_id' => $creator->id,
            'user_name' => $creator->fullname,
            'user_unit' => $creator->unit,
            'action' => 'CASE_STATUS_UPDATE',
            'description' => 'Updated case status',
            'auditable_id' => (string) $case->id,
            'case_number' => $case->case_number,
            'ip_address' => '127.0.0.1',
            'created_at' => Carbon::parse('2026-03-12 10:00:00'),
        ]);

        return [$case, $viewer, $uMeta['session_role']];
    }

    protected function assertUnitSnapshotMatches(string $unitKey, string $uri): void
    {
        [$case, $viewer, $sessionRole] = $this->seedCaseForUnit($unitKey);

        $response = $this->followingRedirects()
            ->actingAs($viewer)
            ->withSession([
                '_token' => 'characterization-fixed-csrf-token',
                'user_role' => $sessionRole,
            ])
            ->get(str_replace('{id}', (string) $case->id, $uri));

        $response->assertStatus(200);
        $html = $response->getContent();

        $normalizeHtml = function (string $raw): string {
            // Normalize non-deterministic DB auto-increment IDs inside JSON data payloads
            $normalized = preg_replace('/"id":\s*\d+/', '"id":1', $raw);
            $normalized = preg_replace('/"suspect_id":\s*\d+/', '"suspect_id":1', $normalized);
            $normalized = preg_replace('/"controller_user_id":\s*\d+/', '"controller_user_id":1', $normalized);
            $normalized = preg_replace('/"user_id":\s*\d+/', '"user_id":1', $normalized);
            $normalized = preg_replace('/"multiple_suspect_case_id":\s*\d+/', '"multiple_suspect_case_id":1', $normalized);
            $normalized = preg_replace('/HASH-[A-F0-9]{8}-VERIFIED/', 'HASH-FIXED-VERIFIED', $normalized);
            $normalized = preg_replace('/"phones":\s*null/', '"phones":[]', $normalized);
            $normalized = preg_replace('/"phones":\s*"\[\]"/', '"phones":[]', $normalized);
            return $normalized;
        };

        $snapshotFile = $this->snapshotDir . "/show_review_{$unitKey}.html";
        if (!file_exists($snapshotFile) || getenv('UPDATE_SNAPSHOTS')) {
            file_put_contents($snapshotFile, $normalizeHtml($html));
        }

        $expectedHtml = file_get_contents($snapshotFile);
        $this->assertSame(
            $expectedHtml,
            $normalizeHtml($html),
            "Characterization HTML snapshot mismatch for unit [{$unitKey}] at URI [{$uri}]."
        );
    }

    public function test_css_show_review_matches_snapshot(): void
    {
        $this->assertUnitSnapshotMatches('css', '/cases/{id}/review?from=Crime_Scene_Support');
    }

    public function test_lab_show_review_matches_snapshot(): void
    {
        $this->assertUnitSnapshotMatches('lab', '/cases/{id}/review?from=Lab_Analysis');
    }

    public function test_dpf_show_review_matches_snapshot(): void
    {
        $this->assertUnitSnapshotMatches('dpf', '/cases/{id}/review?from=Digital_Forensics');
    }

    public function test_em_show_review_matches_snapshot(): void
    {
        $this->assertUnitSnapshotMatches('em', '/cases/{id}/review?from=Evidence_Management');
    }

    public function test_hof_show_review_matches_snapshot(): void
    {
        $this->assertUnitSnapshotMatches('hof', '/Forensic_Head/cases/{id}/review');
    }
}
