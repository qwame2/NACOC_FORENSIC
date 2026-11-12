<?php

namespace Tests\Feature;

use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SearchWarrantDocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function createUnitOfficer(string $unit = 'Laboratory Analysis (LAU)', bool $isHead = true): User
    {
        return User::factory()->create([
            'fullname' => 'Test Unit Officer',
            'unit' => $unit,
            'is_head' => $isHead,
            'is_approved' => true,
            'is_staff' => true,
        ]);
    }

    #[Test]
    public function database_tables_have_search_warrant_document_column(): void
    {
        $this->assertTrue(Schema::hasColumn('single_suspect_cases', 'search_warrant_document'));
        $this->assertTrue(Schema::hasColumn('multiple_suspect_cases', 'search_warrant_document'));
    }

    #[Test]
    public function all_unit_create_views_render_search_warrant_upload_section_above_section_2_mugshot(): void
    {
        $officer = $this->createUnitOfficer('Crime Scene Support');

        $routes = [
            '/cases/create',
            '/Lab_Analysis/create',
            '/Digital_Forensics/create',
            '/Evidence_Management/create',
            '/Crime_Scene_Support/create',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($officer)->get($route);
            $response->assertOk();
            $html = $response->getContent();

            $warrantPos = strpos($html, 'id="searchWarrantUploadSection"');
            $mugshotPos = strpos($html, 'Section 2: Mugshot');

            $this->assertNotFalse($warrantPos, "Missing #searchWarrantUploadSection in {$route}");
            $this->assertNotFalse($mugshotPos, "Missing Section 2: Mugshot in {$route}");
            $this->assertLessThan($mugshotPos, $warrantPos, "Search Warrant section must be above Section 2: Mugshot in {$route}");

            $this->assertStringContainsString('id="searchWarrantDocumentInput"', $html);
            $this->assertStringContainsString('window.toggleSearchWarrantUploadSection', $html);
            $this->assertStringContainsString('uploadStagedSearchWarrantFile', $html);
        }

        $partialContent = file_get_contents(resource_path('views/partials/search_warrant_upload_section.blade.php'));
        $this->assertIsString($partialContent);
        $this->assertStringNotContainsString('console.', $partialContent);
    }

    #[Test]
    public function saving_single_suspect_search_warrant_case_with_multipart_file_persists_document(): void
    {
        Storage::fake('public');
        $officer = $this->createUnitOfficer('Crime Scene Support');

        $warrantFile = UploadedFile::fake()->create('signed_search_warrant.pdf', 150, 'application/pdf');

        $response = $this->actingAs($officer)->post('/cases', [
            'case_number' => 'NCC-2026-SW-001',
            'title' => 'Republic vs Kojo Mensah',
            'unit_name' => 'Crime Scene Support',
            'suspect_docket_mode' => 'SINGLE',
            'suspect_name' => 'Kojo Mensah',
            'operation_code' => 'Search Warrant',
            'operation_code_name' => 'Search Warrant',
            'search_warrant_document' => $warrantFile,
        ], ['Accept' => 'application/json']);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $record = SingleSuspectCase::where('case_number', 'NCC-2026-SW-001')->first();
        $this->assertNotNull($record);
        $this->assertNotEmpty($record->search_warrant_document);
        $this->assertTrue(Storage::disk('public')->exists($record->search_warrant_document));

        $reviewResponse = $this->actingAs($officer)->get('/cases/' . $record->id . '/review');
        $reviewResponse->assertOk();
        $reviewResponse->assertSee('Search Warrant Document');
        $reviewResponse->assertSee('View / Download Warrant');
    }

    #[Test]
    public function uploading_search_warrant_document_for_multiple_suspect_case_after_json_save_persists_to_database(): void
    {
        Storage::fake('public');
        $officer = $this->createUnitOfficer('Laboratory Analysis (LAU)');

        $createResponse = $this->actingAs($officer)->postJson('/cases', [
            'case_number' => 'NCC-2026-SW-MULTI-01',
            'title' => 'Republic vs Kwaku Owusu & Anor',
            'unit_name' => 'Lab Analysis',
            'suspect_docket_mode' => 'MULTIPLE',
            'suspect_name' => 'Kwaku Owusu',
            'operation_code' => 'Search Warrant',
            'operation_code_name' => 'Search Warrant',
            'docket_data' => [
                'suspects' => [
                    ['suspect_name' => 'Kwaku Owusu', 'gender' => 'Male'],
                    ['suspect_name' => 'Ama Serwaa', 'gender' => 'Female'],
                ],
            ],
        ]);

        $createResponse->assertOk();
        $createResponse->assertJsonPath('success', true);
        $caseId = $createResponse->json('case.id');
        $this->assertNotEmpty($caseId);

        $warrantFile = UploadedFile::fake()->create('multi_suspect_warrant.pdf', 200, 'application/pdf');
        $uploadResponse = $this->actingAs($officer)->post('/cases/' . $caseId . '/upload-search-warrant', [
            'search_warrant_document' => $warrantFile,
        ], ['Accept' => 'application/json']);

        $uploadResponse->assertOk();
        $uploadResponse->assertJsonPath('success', true);

        $storedPath = $uploadResponse->json('search_warrant_document');
        $this->assertNotEmpty($storedPath);
        $this->assertTrue(Storage::disk('public')->exists($storedPath));

        $multiRecord = MultipleSuspectCase::find($caseId);
        $this->assertNotNull($multiRecord);
        $this->assertSame($storedPath, $multiRecord->search_warrant_document);
    }

    #[Test]
    public function non_search_warrant_case_does_not_store_search_warrant_document(): void
    {
        Storage::fake('public');
        $officer = $this->createUnitOfficer('Digital Forensics');

        $staleFile = UploadedFile::fake()->create('stale.pdf', 50, 'application/pdf');

        $response = $this->actingAs($officer)->post('/cases', [
            'case_number' => 'NCC-2026-RAID-001',
            'title' => 'Republic vs Yaw Boateng',
            'unit_name' => 'Digital Forensics',
            'suspect_docket_mode' => 'SINGLE',
            'suspect_name' => 'Yaw Boateng',
            'operation_code' => 'Raid',
            'operation_code_name' => 'Raid',
            'search_warrant_document' => $staleFile,
        ], ['Accept' => 'application/json']);

        $response->assertOk();
        $record = SingleSuspectCase::where('case_number', 'NCC-2026-RAID-001')->first();
        $this->assertNotNull($record);
        $this->assertNull($record->search_warrant_document);
    }
}

