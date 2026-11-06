<?php

namespace Tests\Unit\Services;

use App\Services\CasePhotoResolver;
use Tests\TestCase;

class CasePhotoResolverTest extends TestCase
{
    protected CasePhotoResolver $photoResolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->photoResolver = new CasePhotoResolver();
    }

    public function test_extracts_all_photos_from_arrays_json_and_multiple_keys_with_deduplication(): void
    {
        $item = [
            'evidence_photos' => ['exhibits/a.jpg', 'exhibits/b.jpg'],
            'photos' => json_encode(['exhibits/b.jpg', 'exhibits/c.jpg']),
            'photo' => 'exhibits/d.jpg',
        ];

        $photos = $this->photoResolver->extractAllPhotos($item);
        $this->assertSame(['exhibits/b.jpg', 'exhibits/c.jpg', 'exhibits/a.jpg', 'exhibits/d.jpg'], $photos);
    }

    public function test_formats_photo_and_mugshot_urls_appropriately(): void
    {
        $this->assertSame('', $this->photoResolver->formatPhotoUrl(''));
        $this->assertSame('https://example.com/p.jpg', $this->photoResolver->formatPhotoUrl('https://example.com/p.jpg'));
        $this->assertSame('data:image/png;base64,AAA', $this->photoResolver->formatPhotoUrl('data:image/png;base64,AAA'));
        $this->assertStringEndsWith('/storage/exhibits/item1.jpg', $this->photoResolver->formatPhotoUrl('exhibits/item1.jpg'));

        $this->assertNull($this->photoResolver->formatMugshotUrl(null));
        $this->assertNull($this->photoResolver->formatMugshotUrl('null'));
        $this->assertStringEndsWith('/storage/mugshots/front.jpg', $this->photoResolver->formatMugshotUrl('mugshots/front.jpg'));
    }

    public function test_resolves_primary_and_associate_case_photos_separately(): void
    {
        $case = (object) [
            'suspect_name' => 'Kwame Mensah',
            'evidence_photos' => ['exhibits/primary_case.jpg'],
        ];
        $docketData = [
            'seizures' => [
                [
                    'associated_person' => 'Kwame Mensah',
                    'evidence_photos' => ['exhibits/kwame_drug.jpg'],
                ],
                [
                    'associated_person' => 'Abena Owusu',
                    'is_associate' => true,
                    'evidence_photos' => ['exhibits/abena_drug.jpg'],
                ],
            ],
        ];

        $resolved = $this->photoResolver->resolveCasePhotos($case, $docketData);
        $this->assertSame(['exhibits/kwame_drug.jpg', 'exhibits/primary_case.jpg'], $resolved['primaryPhotos']);
        $this->assertSame(['exhibits/abena_drug.jpg'], $resolved['allAssocPhotos']);
        $this->assertCount(3, $resolved['evidenceGalleryItems']);
    }
}
