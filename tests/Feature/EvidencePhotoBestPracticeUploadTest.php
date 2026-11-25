<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EvidencePhotoBestPracticeUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function createOfficer(string $unit = 'Digital Forensics'): User
    {
        return User::factory()->create([
            'fullname' => 'Agent Inspector Mensah',
            'unit' => $unit,
            'is_head' => true,
            'is_approved' => true,
            'is_staff' => true,
        ]);
    }

    protected function makeValidImage(string $filename = 'photo.png', int $sizeKb = 100, string $mime = 'image/png'): UploadedFile
    {
        $pngHeader = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $padBytes = max(0, ($sizeKb * 1024) - strlen($pngHeader));
        $content = $pngHeader . str_repeat("\0", $padBytes);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_img_');
        file_put_contents($tempPath, $content);

        return new UploadedFile($tempPath, $filename, $mime, null, true);
    }

    #[Test]
    public function test_create_views_include_optimizer_script_and_best_practice_guidance(): void
    {
        $urls = [
            'Digital Forensics' => '/Digital_Forensics/create',
            'Forensics' => '/cases/create',
            'Evidence Management' => '/Evidence_Management/create',
            'Laboratory Analysis (LAU)' => '/Lab_Analysis/create',
        ];

        foreach ($urls as $unit => $url) {
            $officer = $this->createOfficer($unit);
            $response = $this->actingAs($officer)->get($url);

            $response->assertStatus(200);
            $html = $response->getContent();

            $this->assertStringContainsString('forensic-image-optimizer.js', $html, "Missing optimizer script in {$url}");
            $this->assertStringContainsString('Upload 10–15 photos per batch (under 3–5MB each, auto-optimized for fast upload)', $html, "Missing best practice text in {$url}");
        }
    }

    #[Test]
    public function test_evidence_photos_endpoint_accepts_valid_batch_under_5mb(): void
    {
        $officer = $this->createOfficer();

        $photo1 = $this->makeValidImage('exhibit_1.png', 1500); // 1.5 MB
        $photo2 = $this->makeValidImage('exhibit_2.png', 2500); // 2.5 MB

        $response = $this->actingAs($officer)->postJson('/upload-evidence-photos', [
            'photos' => [$photo1, $photo2],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertCount(2, $response->json('urls'));
    }

    #[Test]
    public function test_evidence_photos_endpoint_rejects_batch_exceeding_15_photos(): void
    {
        $officer = $this->createOfficer();

        $photos = [];
        for ($i = 0; $i < 16; $i++) {
            $photos[] = $this->makeValidImage("exhibit_{$i}.png", 50);
        }

        $response = $this->actingAs($officer)->postJson('/upload-evidence-photos', [
            'photos' => $photos,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['photos']);
        $this->assertStringContainsString('15', $response->json('errors.photos.0'));
    }

    #[Test]
    public function test_evidence_photos_endpoint_rejects_photo_exceeding_5mb(): void
    {
        $officer = $this->createOfficer();

        // 6 MB photo (6144 KB > 5120 KB)
        $largePhoto = $this->makeValidImage('huge_exhibit.png', 6144);

        $response = $this->actingAs($officer)->postJson('/upload-evidence-photos', [
            'photos' => [$largePhoto],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['photos.0']);
        $errorMsg = $response->json('errors')['photos.0'][0] ?? '';
        $this->assertStringContainsString('5 MB', $errorMsg);
    }
}
