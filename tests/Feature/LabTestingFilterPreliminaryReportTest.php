<?php

namespace Tests\Feature;

use App\Models\ForensicCase;
use App\Models\SingleSuspectCase;
use App\Models\SuspectPreliminaryReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LabTestingFilterPreliminaryReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $headUser;
    protected User $dgUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->headUser = User::factory()->create([
            'fullname' => 'Director Kwame Mensah',
            'email' => 'head@nacoc.gov.gh',
            'is_head' => true,
            'is_approved' => true,
            'unit' => 'Forensic Directorate',
        ]);

        $this->dgUser = User::factory()->create([
            'fullname' => 'Director General',
            'email' => 'dg@nacoc.gov.gh',
            'is_head' => true,
            'is_approved' => true,
            'unit' => 'Director General',
        ]);
    }

    #[Test]
    public function forensic_head_cases_filter_lab_testing_displays_drug_cases_and_preliminary_report_pdf(): void
    {
        // 1. Create a drug-related case
        $drugCase = SingleSuspectCase::create([
            'case_number' => 'NACOC-2026-DRUG-001',
            'title' => 'Interception of Suspected Cocaine Shipment',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Under Investigation',
            'case_status' => 'Under Investigation',
            'priority' => 'High',
            'suspect_name' => 'Kwame Mensah',
            'submitting_officer' => 'Officer Evans',
            'docket_data' => [
                'seizures' => [
                    [
                        'seizure_name' => 'Cocaine Powder Brick',
                        'commodity_name' => 'Cocaine',
                        'seizure_name_drug' => 'Cocaine',
                        'sample_weight' => '1.5kg',
                    ]
                ]
            ],
        ]);

        // 2. Create a non-drug electronic case
        $nonDrugCase = SingleSuspectCase::create([
            'case_number' => 'NACOC-2026-PHONE-002',
            'title' => 'Seized Encrypted Mobile Phone Device',
            'unit_name' => 'Digital & Physical Forensics',
            'status' => 'Under Investigation',
            'case_status' => 'Under Investigation',
            'priority' => 'Medium',
            'suspect_name' => 'Yaw Mensah',
            'submitting_officer' => 'Officer Evans',
            'docket_data' => [
                'seizures' => [
                    [
                        'seizure_name' => 'iPhone 15 Pro',
                        'commodity_name' => 'Electronic Device',
                    ]
                ]
            ],
        ]);

        // Standard GET full page has Case Stat
        $pageResponse = $this->actingAs($this->headUser)->get('/Forensic_Head/cases');
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee("filterStatus('Lab Analysis', this)", false);
        $pageResponse->assertSee('Lab Testing');
        $pageResponse->assertSee('Case Stat');
        $pageResponse->assertSee('/Lab_Analysis/preliminary-report-pdf/NACOC-2026-DRUG-001', false);
        $pageResponse->assertSee('Preliminary Exhibit Analysis Report (PDF)');

        // AJAX request with status filter = Lab Analysis (matching "Lab Testing" tab click)
        // verifies Case Stat is removed/omitted from the Lab Testing filter table
        $ajaxResponse = $this->actingAs($this->headUser)->get('/Forensic_Head/cases?status=Lab+Analysis', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertSee('NACOC-2026-DRUG-001');
        $ajaxResponse->assertSee('/Lab_Analysis/preliminary-report-pdf/NACOC-2026-DRUG-001', false);
        $ajaxResponse->assertSee('Preliminary Exhibit Analysis Report (PDF)');
        $ajaxResponse->assertDontSee('NACOC-2026-PHONE-002');
        $ajaxResponse->assertDontSee('Case Stat');
    }

    #[Test]
    public function dg_cases_filter_lab_testing_displays_drug_cases_and_preliminary_report_pdf(): void
    {
        // 1. Create a drug-related case with preliminary report
        $drugCase = SingleSuspectCase::create([
            'case_number' => 'NACOC-2026-DRUG-003',
            'title' => 'Interception of Heroin Pellets',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Under Investigation',
            'case_status' => 'Under Investigation',
            'priority' => 'Critical',
            'suspect_name' => 'Ama Serwaa',
            'submitting_officer' => 'Officer Evans',
            'docket_data' => [
                'seizures' => [
                    [
                        'seizure_name' => 'Heroin Brown Powder',
                        'commodity_name' => 'Heroin',
                        'sample_weight' => '500g',
                    ]
                ]
            ],
        ]);

        // AJAX request with status filter = Lab Analysis (matching "Lab Testing" tab click)
        $ajaxResponse = $this->actingAs($this->dgUser)->get('/DG/cases?status=Lab+Analysis', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertSee('NACOC-2026-DRUG-003');
        $ajaxResponse->assertSee('/Lab_Analysis/preliminary-report-pdf/NACOC-2026-DRUG-003', false);
        $ajaxResponse->assertSee('Preliminary Exhibit Analysis Report (PDF)');
        $ajaxResponse->assertDontSee('Case Stat');
    }

    #[Test]
    public function preliminary_report_pdf_route_is_accessible_for_drug_case(): void
    {
        $drugCase = SingleSuspectCase::create([
            'case_number' => 'NACOC-2026-DRUG-004',
            'title' => 'Cocaine Transit Interception',
            'unit_name' => 'LAB Analysis',
            'status' => 'Under Investigation',
            'case_status' => 'Under Investigation',
            'priority' => 'High',
            'suspect_name' => 'Kojo Antwi',
            'submitting_officer' => 'Analyst Mensah',
            'docket_data' => [
                'seizures' => [
                    [
                        'seizure_name' => 'White Powder',
                        'commodity_name' => 'Cocaine',
                        'seizure_name_drug' => 'Cocaine Hydrochloride',
                        'sample_weight' => '2.0kg',
                    ]
                ]
            ],
        ]);

        $pdfResponse = $this->actingAs($this->headUser)->get('/Lab_Analysis/preliminary-report-pdf/' . $drugCase->case_number);
        $pdfResponse->assertStatus(200);
        $pdfResponse->assertSee('PRELIMINARY EXHIBIT ANALYSIS REPORT');
        $pdfResponse->assertSee('NACOC-2026-DRUG-004');
    }

    #[Test]
    public function vault_storage_filter_table_displays_all_case_exhibits_in_system(): void
    {
        // 1. Create a case with multiple logged exhibits via ForensicCase::create
        $case = ForensicCase::create([
            'case_number' => 'NACOC-2026-VAULT-001',
            'title' => 'Harbour Interception Operation',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Under Investigation',
            'case_status' => 'Under Investigation',
            'priority' => 'Critical',
            'suspect_name' => 'Kweku Mensah',
            'submitting_officer' => 'Officer Evans',
            'docket_data' => [
                'seizures' => [
                    [
                        'exhibit_number' => 'EXH-VAULT-101',
                        'commodity_name' => 'Cocaine Brick',
                        'exhibit_category' => 'Narcotics',
                        'seizure_weight' => '1.5kg',
                    ],
                    [
                        'exhibit_number' => 'EXH-VAULT-102',
                        'commodity_name' => 'Glock 19 Pistol',
                        'exhibit_category' => 'Weapons',
                        'seizure_quantity' => '1 Unit',
                    ],
                    [
                        'exhibit_number' => 'EXH-VAULT-103',
                        'commodity_name' => 'Encrypted iPhone 14',
                        'exhibit_category' => 'Electronics',
                        'seizure_quantity' => '1 Unit',
                    ],
                ]
            ],
        ]);

        // Forensic Head request with status = Vault Storage
        $ajaxResponse = $this->actingAs($this->headUser)->get('/Forensic_Head/cases?status=Vault+Storage', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertSee('NACOC-2026-VAULT-001');
        $ajaxResponse->assertSee('Vault Exhibits (3)');
        $ajaxResponse->assertSee('EXH-VAULT-101:');
        $ajaxResponse->assertSee('Cocaine Brick');
        $ajaxResponse->assertSee('EXH-VAULT-102:');
        $ajaxResponse->assertSee('Glock 19 Pistol');
        $ajaxResponse->assertSee('EXH-VAULT-103:');
        $ajaxResponse->assertSee('Encrypted iPhone 14');
        $ajaxResponse->assertSee('In Vault Custody');

        // DG request with status = Vault Storage
        $dgResponse = $this->actingAs($this->dgUser)->get('/DG/cases?status=Vault+Storage', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $dgResponse->assertStatus(200);
        $dgResponse->assertSee('NACOC-2026-VAULT-001');
        $dgResponse->assertSee('Vault Exhibits (3)');
        $dgResponse->assertSee('Cocaine Brick');
        $dgResponse->assertSee('Glock 19 Pistol');
        $dgResponse->assertSee('Encrypted iPhone 14');
    }
}
