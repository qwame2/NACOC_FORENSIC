<?php

namespace Tests\Feature;

use App\Models\Exhibit;
use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvidenceManagementReportExhibitSeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_evidence_management_report_pdf_separates_exhibit_logs_into_dedicated_tables(): void
    {
        $officer = User::factory()->create([
            'unit'    => 'Evidence Management Unit',
            'is_head' => true,
        ]);

        $case = SingleSuspectCase::create([
            'case_number'  => 'CR-EMU-SEP-2026-001',
            'title'        => 'Separation Test Case',
            'unit_name'    => 'Evidence Management',
            'suspect_name' => 'John Doe Suspect',
            'created_by'   => $officer->id,
        ]);

        // 1. Drug exhibit -> Strictly Drug Exhibit Log table
        Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number'         => 'EXH-DRUG-001',
            'commodity_name'         => 'Pure Cocaine Brick',
            'exhibit_category'       => 'Drugs',
            'drug_classification'    => 'Narcotic Drug',
            'seizure_quantity'       => '2',
            'seizure_packaging'      => 'Slabs',
            'seizure_weight'         => '2.5',
            'seizure_unit'           => 'kg',
            'date_seized'            => '2026-11-05',
        ]);

        // 2. Vehicles exhibit -> Physical Assets & Non-Drug Seized Property Log table
        Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number'         => 'EXH-VEH-001',
            'commodity_name'         => 'Toyota Hilux 4x4',
            'exhibit_category'       => 'Vehicles',
            'veh_vin'                => 'VIN-HILUX-987654321',
            'veh_reg_no'             => 'GR-2026-26',
            'date_seized'            => '2026-11-05',
        ]);

        // 3. Electronic Devices exhibit -> Physical Assets & Non-Drug Seized Property Log table
        Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number'         => 'EXH-ELEC-001',
            'commodity_name'         => 'Apple iPhone 15 Pro Max',
            'exhibit_category'       => 'Electronic Devices',
            'dev_serial_no'          => 'SN-IPHONE-554433',
            'dev_model'              => 'A2849',
            'date_seized'            => '2026-11-05',
        ]);

        // 4. Other Exhibits exhibit -> Physical Assets & Non-Drug Seized Property Log table
        Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number'         => 'EXH-OTHER-001',
            'commodity_name'         => 'Hydraulic Metal Safe Vault',
            'exhibit_category'       => 'Other Exhibits',
            'property_id'            => 'PROP-SAFE-01',
            'date_seized'            => '2026-11-05',
        ]);

        // 5. Money exhibit -> Strictly Seized Currency & Money Received Register
        Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number'         => 'EXH-MONEY-001',
            'commodity_name'         => 'US Dollar Banknotes',
            'exhibit_category'       => 'Money',
            'seizure_value'          => 75000.00,
            'seizure_location'       => 'Terminal 3 VIP Lounge',
            'date_seized'            => '2026-11-05',
        ]);

        // 6. Weapons exhibit -> Dynamic dedicated "Weapons Exhibit Log" table
        Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number'         => 'EXH-WPN-001',
            'commodity_name'         => 'Beretta 92FS Pistol',
            'exhibit_category'       => 'Weapons',
            'wpn_model'              => 'Beretta 92FS',
            'wpn_calibre'            => '9x19mm',
            'wpn_serial_no'          => 'BER-887766-GH',
            'seizure_quantity'       => '1',
            'seizure_packaging'      => 'Unit',
            'date_seized'            => '2026-11-05',
        ]);

        // 7. Ammunition exhibit -> Dynamic dedicated "Ammunition Exhibit Log" table
        Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number'         => 'EXH-AMMO-001',
            'commodity_name'         => 'Live 9mm Hollow Point Rounds',
            'exhibit_category'       => 'Ammunition',
            'seizure_quantity'       => '100',
            'seizure_packaging'      => 'Rounds',
            'date_seized'            => '2026-11-05',
        ]);

        // 8. Precious Stones exhibit -> Dynamic dedicated "Precious Stones Exhibit Log" table
        Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number'         => 'EXH-GEMS-001',
            'commodity_name'         => 'Uncut Diamonds & Gold Bullion',
            'exhibit_category'       => 'Precious Stones',
            'seizure_quantity'       => '15',
            'seizure_packaging'      => 'Stones',
            'seizure_weight'         => '350.5',
            'seizure_unit'           => 'Carats',
            'date_seized'            => '2026-11-05',
        ]);

        // 9. Custom Officer-Added Category exhibit -> Dynamic dedicated table for that category
        Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number'         => 'EXH-CUSTOM-001',
            'commodity_name'         => 'Endangered African Rosewood Timber Logs',
            'exhibit_category'       => 'Contraband Timber',
            'seizure_quantity'       => '40',
            'seizure_packaging'      => 'Logs',
            'seizure_weight'         => '12.8',
            'seizure_unit'           => 'Tons',
            'date_seized'            => '2026-11-05',
        ]);

        $response = $this->actingAs($officer)->get('/Evidence_Management/report_pdf?period=weekly');
        $response->assertStatus(200);

        // 1. Drug Exhibit Log table strictly contains Cocaine
        $response->assertSee('Drug exhibit Log', false);
        $response->assertSee('Pure Cocaine Brick');

        // 2. Physical Assets table strictly contains vehicles, electronic devices, other exhibits
        $response->assertSee('Physical Assets &amp; Non-Drug Seized Property Log', false);
        $response->assertSee('Toyota Hilux 4x4');
        $response->assertSee('VIN-HILUX-987654321');
        $response->assertSee('A2849');
        $response->assertSee('Hydraulic Metal Safe Vault');

        // 3. Seized Currency & Money Received Register strictly contains money
        $response->assertSee('Seized Currency &amp; Money Received Register', false);
        $response->assertSee('US Dollar Banknotes');
        $response->assertSee('GH₵ 75,000.00');

        // 4. Dynamic table for Weapons
        $response->assertSee('Weapons Exhibit Log', false);
        $response->assertSee('Beretta 92FS Pistol');
        $response->assertSee('BER-887766-GH');

        // 5. Dynamic table for Ammunition
        $response->assertSee('Ammunition Exhibit Log', false);
        $response->assertSee('Live 9mm Hollow Point Rounds');
        $response->assertSee('100 Rounds');

        // 6. Dynamic table for Precious Stones
        $response->assertSee('Precious Stones Exhibit Log', false);
        $response->assertSee('Uncut Diamonds &amp; Gold Bullion', false);
        $response->assertSee('350.5 Carats');

        // 7. Dynamic table for custom category "Contraband Timber"
        $response->assertSee('Contraband Timber Exhibit Log', false);
        $response->assertSee('Endangered African Rosewood Timber Logs');
        $response->assertSee('40 Logs');

        // 8. Strict isolation check: Verify each section strictly isolates its exhibits
        $html = $response->getContent();

        // Extract section-01 (Drugs)
        preg_match('/<div class="section-01">(.*?)<\/table>/s', $html, $sec1Match);
        $sec1Html = $sec1Match[1] ?? '';
        $this->assertStringContainsString('Pure Cocaine Brick', $sec1Html);
        $this->assertStringNotContainsString('Toyota Hilux', $sec1Html);
        $this->assertStringNotContainsString('iPhone', $sec1Html);
        $this->assertStringNotContainsString('Dollar', $sec1Html);
        $this->assertStringNotContainsString('Beretta', $sec1Html);
        $this->assertStringNotContainsString('Hollow Point', $sec1Html);
        $this->assertStringNotContainsString('Diamonds', $sec1Html);
        $this->assertStringNotContainsString('Rosewood', $sec1Html);

        // Extract section-02 (Physical Assets)
        preg_match('/<div class="section-02[^>]*>(.*?)<\/table>/s', $html, $sec2Match);
        $sec2Html = $sec2Match[1] ?? '';
        $this->assertStringContainsString('Toyota Hilux 4x4', $sec2Html);
        // Electronic Devices display DEVICE MODEL in Asset / Exhibit Type column, not Commodity Name
        $this->assertStringContainsString('A2849', $sec2Html);
        $this->assertStringNotContainsString('Apple iPhone 15 Pro Max', $sec2Html);
        $this->assertStringContainsString('Hydraulic Metal Safe Vault', $sec2Html);
        // Chassis Number column strictly displays exhibits with Chassis Number only; all others display N/A
        $this->assertStringContainsString('VIN-HILUX-987654321', $sec2Html);
        $this->assertStringNotContainsString('SN-IPHONE-554433', $sec2Html);
        $this->assertStringNotContainsString('PROP-SAFE-01', $sec2Html);
        $this->assertStringNotContainsString('Pure Cocaine Brick', $sec2Html);
        $this->assertStringNotContainsString('Dollar', $sec2Html);
        $this->assertStringNotContainsString('Beretta', $sec2Html);
        $this->assertStringNotContainsString('Hollow Point', $sec2Html);
        $this->assertStringNotContainsString('Diamonds', $sec2Html);
        $this->assertStringNotContainsString('Rosewood', $sec2Html);

        // Extract section-03 (Money)
        preg_match('/<div class="section-03[^>]*>(.*?)<\/table>/s', $html, $sec3Match);
        $sec3Html = $sec3Match[1] ?? '';
        $this->assertStringContainsString('US Dollar Banknotes', $sec3Html);
        $this->assertStringNotContainsString('Pure Cocaine Brick', $sec3Html);
        $this->assertStringNotContainsString('Toyota Hilux', $sec3Html);
        $this->assertStringNotContainsString('iPhone', $sec3Html);
        $this->assertStringNotContainsString('Beretta', $sec3Html);
        $this->assertStringNotContainsString('Hollow Point', $sec3Html);
        $this->assertStringNotContainsString('Diamonds', $sec3Html);
        $this->assertStringNotContainsString('Rosewood', $sec3Html);
    }

    public function test_evidence_management_report_pdf_consolidates_all_drug_logs_into_unified_table_and_never_renders_separate_drug_section(): void
    {
        $officer = User::factory()->create([
            'unit'    => 'Evidence Management Unit',
            'is_head' => true,
        ]);

        $case = SingleSuspectCase::create([
            'case_number'  => 'CR-EMU-CONSOL-2026-002',
            'title'        => 'Consolidation Test Case',
            'unit_name'    => 'Evidence Management',
            'suspect_name' => 'Jane Drug Suspect',
            'created_by'   => $officer->id,
        ]);

        // Drug Exhibit 1: Standard drug entry
        Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number'         => 'EXH-DRUG-CON-001',
            'commodity_name'         => 'Pure Cocaine Brick',
            'exhibit_category'       => 'Drugs',
            'drug_classification'    => 'Narcotic Drug',
            'seizure_quantity'       => '1',
            'seizure_packaging'      => 'Slab',
            'seizure_weight'         => '1.0',
            'seizure_unit'           => 'kg',
            'date_seized'            => '2026-11-05',
        ]);

        // Drug Exhibit 2: Category is Drugs, but exhibit_type is 'Non-Drug Exhibit' (e.g. form default)
        Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number'         => 'EXH-DRUG-CON-002',
            'commodity_name'         => 'Compressed Cannabis Slabs',
            'exhibit_category'       => 'Drugs',
            'exhibit_type'           => 'Non-Drug Exhibit',
            'seizure_quantity'       => '5',
            'seizure_packaging'      => 'Slabs',
            'seizure_weight'         => '5.2',
            'seizure_unit'           => 'kg',
            'date_seized'            => '2026-11-05',
        ]);

        // Drug Exhibit 3: Category is Narcotics
        Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number'         => 'EXH-DRUG-CON-003',
            'commodity_name'         => 'Heroin Powder Packets',
            'exhibit_category'       => 'Narcotics',
            'seizure_quantity'       => '10',
            'seizure_packaging'      => 'Packets',
            'seizure_weight'         => '500',
            'seizure_unit'           => 'g',
            'date_seized'            => '2026-11-05',
        ]);

        // Monetary Funds Exhibit
        Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'exhibit_number'         => 'EXH-MONEY-CON-001',
            'commodity_name'         => 'Seized Ghana Cedis Cash',
            'exhibit_category'       => 'Money',
            'seizure_value'          => 45000.00,
            'date_seized'            => '2026-11-05',
        ]);

        $response = $this->actingAs($officer)->get('/Evidence_Management/report_pdf?period=weekly');
        $response->assertStatus(200);

        $html = $response->getContent();

        // 1. All drug log entries are consolidated into the primary Table 1 (Drug exhibit Log)
        $this->assertStringContainsString('Pure Cocaine Brick', $html);
        $this->assertStringContainsString('Compressed Cannabis Slabs', $html);
        $this->assertStringContainsString('Heroin Powder Packets', $html);

        // Section 1 header should show REGISTERED SEIZURE • 3 ENTRIES
        $this->assertMatchesRegularExpression('/REGISTERED SEIZURE\s*&bull;\s*<span>\s*3\s*<\/span>\s*ENTRIES/i', $html);

        // 2. Crucial Invariant: Safe removal of separate "Drugs Exhibit Log" section
        // There must be NO separate "Drugs Exhibit Log" dynamic table or "DRUGS • X ENTRIES" dynamic badge
        $this->assertStringNotContainsString('Drugs Exhibit Log', $html);
        $this->assertStringNotContainsString('DRUGS &bull;', $html);
        $this->assertStringNotContainsString('DRUGS •', $html);

        // 3. Section 1 (Drug exhibit Log) must contain all 3 drug exhibits
        preg_match('/<div class="section-01">(.*?)<\/table>/s', $html, $sec1Match);
        $sec1Html = $sec1Match[1] ?? '';
        $this->assertStringContainsString('Pure Cocaine Brick', $sec1Html);
        $this->assertStringContainsString('Compressed Cannabis Slabs', $sec1Html);
        $this->assertStringContainsString('Heroin Powder Packets', $sec1Html);
        $this->assertStringNotContainsString('Seized Ghana Cedis Cash', $sec1Html);

        // 4. Section 3 (Money) must contain the money exhibit and NOT any drug exhibits
        preg_match('/<div class="section-03[^>]*>(.*?)<\/table>/s', $html, $sec3Match);
        $sec3Html = $sec3Match[1] ?? '';
        $this->assertStringContainsString('Seized Ghana Cedis Cash', $sec3Html);
        $this->assertStringNotContainsString('Pure Cocaine Brick', $sec3Html);
        $this->assertStringNotContainsString('Compressed Cannabis Slabs', $sec3Html);
        $this->assertStringNotContainsString('Heroin Powder Packets', $sec3Html);
    }
}
