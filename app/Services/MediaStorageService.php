<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaStorageService
{
    /**
     * Check if a string is a base64 encoded image data URI.
     */
    public function isBase64Image(?string $value): bool
    {
        if (empty($value) || !is_string($value)) {
            return false;
        }
        $val = trim($value);
        return str_starts_with($val, 'data:image/') && str_contains($val, ';base64,');
    }

    /**
     * Store a base64 image onto disk and return the relative storage path.
     * Files are partitioned by year/month to ensure high-performance directory lookups at massive scale.
     */
    public function storeBase64Image(
        string $base64Data,
        string $folder = 'suspects',
        ?string $caseNumber = null,
        ?string $prefix = null
    ): ?string {
        if (!$this->isBase64Image($base64Data)) {
            return $base64Data;
        }

        // 1. Extract MIME type and extension
        if (!preg_match('/^data:image\/([a-zA-Z0-9\+\-]+);base64,/', $base64Data, $matches)) {
            return null;
        }

        $rawMime = strtolower($matches[1]);
        $extension = match ($rawMime) {
            'jpeg', 'jpg' => 'jpg',
            'png' => 'png',
            'webp' => 'webp',
            'gif' => 'gif',
            default => 'jpg',
        };

        // 2. Decode binary content
        $encoded = substr($base64Data, strpos($base64Data, ',') + 1);
        $binary = base64_decode($encoded, true);
        if ($binary === false || strlen($binary) === 0) {
            return null;
        }

        // 3. Partitioned path structure: folder/YYYY/MM/case_prefix_uuid.ext
        $yearMonth = date('Y/m');
        $cleanCase = $caseNumber ? preg_replace('/[^a-zA-Z0-9_\-]/', '_', $caseNumber) : 'case';
        $cleanPrefix = $prefix ? preg_replace('/[^a-zA-Z0-9_\-]/', '_', $prefix) : 'img';
        $uniqueToken = Str::lower(Str::random(10));
        $filename = "{$cleanCase}_{$cleanPrefix}_{$uniqueToken}.{$extension}";

        $relativePath = "{$folder}/{$yearMonth}/{$filename}";

        // 4. Save to public disk
        Storage::disk('public')->put($relativePath, $binary);

        return $relativePath;
    }

    /**
     * Process a single suspect mugshot value.
     */
    public function processMugshot(
        ?string $value,
        ?string $caseNumber = null,
        string $angle = 'front',
        int $suspectIndex = 0
    ): ?string {
        if (empty($value) || !is_string($value)) {
            return $value;
        }
        if ($this->isBase64Image($value)) {
            return $this->storeBase64Image($value, 'suspects', $caseNumber, "{$angle}_s{$suspectIndex}");
        }
        return $value;
    }

    /**
     * Process a single evidence photo value.
     */
    public function processEvidencePhoto(
        ?string $value,
        ?string $caseNumber = null,
        int|string $index = 0
    ): ?string {
        if (empty($value) || !is_string($value)) {
            return $value;
        }
        if ($this->isBase64Image($value)) {
            return $this->storeBase64Image($value, 'evidence_photos', $caseNumber, "exhibit_{$index}");
        }
        return $value;
    }

    /**
     * Scan and convert all Base64 images in an array/payload to disk files,
     * replacing base64 strings with clean relative paths.
     */
    public function processPayloadImages(array $payload, ?string $caseNumber = null): array
    {
        $caseNo = $caseNumber ?? ($payload['case_number'] ?? ($payload['case_number_preview'] ?? null));

        // 1. Root mugshots
        foreach (['mugshot_front', 'mugshot_left', 'mugshot_right', 'mugshot_back', 'photo'] as $angleKey) {
            if (!empty($payload[$angleKey]) && is_string($payload[$angleKey])) {
                $payload[$angleKey] = $this->processMugshot($payload[$angleKey], $caseNo, $angleKey, 0);
            }
        }

        // 2. Root evidence photos
        if (!empty($payload['evidence_photos']) && is_array($payload['evidence_photos'])) {
            foreach ($payload['evidence_photos'] as $idx => $photoItem) {
                if (is_string($photoItem)) {
                    $payload['evidence_photos'][$idx] = $this->processEvidencePhoto($photoItem, $caseNo, $idx);
                } elseif (is_array($photoItem) && !empty($photoItem['data']) && is_string($photoItem['data'])) {
                    $payload['evidence_photos'][$idx]['data'] = $this->processEvidencePhoto($photoItem['data'], $caseNo, $idx);
                    $payload['evidence_photos'][$idx]['url'] = $payload['evidence_photos'][$idx]['data'];
                }
            }
        }

        // 3. Process docket_data nested structure
        if (!empty($payload['docket_data']) && is_array($payload['docket_data'])) {
            $payload['docket_data'] = $this->processDocketDataImages($payload['docket_data'], $caseNo);
        }

        return $payload;
    }

    /**
     * Scan and convert all Base64 images inside docket_data.
     */
    public function processDocketDataImages(array $docketData, ?string $caseNumber = null): array
    {
        // Top-level mugshots in docket_data
        foreach (['mugshot_front', 'mugshot_left', 'mugshot_right', 'photo'] as $mKey) {
            if (!empty($docketData[$mKey]) && is_string($docketData[$mKey])) {
                $docketData[$mKey] = $this->processMugshot($docketData[$mKey], $caseNumber, $mKey, 0);
            }
        }
        if (!empty($docketData['mugshots']) && is_array($docketData['mugshots'])) {
            foreach ($docketData['mugshots'] as $mKey => $mVal) {
                if (is_string($mVal) && $this->isBase64Image($mVal)) {
                    $docketData['mugshots'][$mKey] = $this->processMugshot($mVal, $caseNumber, $mKey, 0);
                }
            }
        }

        // Evidence photos in docket_data
        if (!empty($docketData['evidence_photos']) && is_array($docketData['evidence_photos'])) {
            foreach ($docketData['evidence_photos'] as $idx => $photoItem) {
                if (is_string($photoItem)) {
                    $docketData['evidence_photos'][$idx] = $this->processEvidencePhoto($photoItem, $caseNumber, $idx);
                } elseif (is_array($photoItem) && !empty($photoItem['data']) && is_string($photoItem['data'])) {
                    $docketData['evidence_photos'][$idx]['data'] = $this->processEvidencePhoto($photoItem['data'], $caseNumber, $idx);
                    $docketData['evidence_photos'][$idx]['url'] = $docketData['evidence_photos'][$idx]['data'];
                }
            }
        }

        // Suspects array
        if (!empty($docketData['suspects']) && is_array($docketData['suspects'])) {
            foreach ($docketData['suspects'] as $sIdx => $suspect) {
                if (!is_array($suspect)) {
                    continue;
                }
                foreach (['mugshot_front', 'mugshot_left', 'mugshot_right', 'photo'] as $mKey) {
                    if (!empty($suspect[$mKey]) && is_string($suspect[$mKey])) {
                        $docketData['suspects'][$sIdx][$mKey] = $this->processMugshot(
                            $suspect[$mKey],
                            $caseNumber,
                            $mKey,
                            $sIdx
                        );
                    }
                }
                if (!empty($suspect['mugshots']) && is_array($suspect['mugshots'])) {
                    foreach ($suspect['mugshots'] as $mKey => $mVal) {
                        if (is_string($mVal) && $this->isBase64Image($mVal)) {
                            $docketData['suspects'][$sIdx]['mugshots'][$mKey] = $this->processMugshot(
                                $mVal,
                                $caseNumber,
                                $mKey,
                                $sIdx
                            );
                        }
                    }
                }
            }
        }

        // Seizures array
        if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
            foreach ($docketData['seizures'] as $szIdx => $sz) {
                if (!is_array($sz)) {
                    continue;
                }
                foreach (['photos', 'evidence_photos'] as $pListKey) {
                    if (!empty($sz[$pListKey]) && is_array($sz[$pListKey])) {
                        foreach ($sz[$pListKey] as $pIdx => $pItem) {
                            if (is_string($pItem) && $this->isBase64Image($pItem)) {
                                $docketData['seizures'][$szIdx][$pListKey][$pIdx] = $this->processEvidencePhoto(
                                    $pItem,
                                    $caseNumber,
                                    $szIdx . '_' . $pIdx
                                );
                            }
                        }
                    }
                }
                foreach (['photo', 'exhibit_photo'] as $pSingleKey) {
                    if (!empty($sz[$pSingleKey]) && is_string($sz[$pSingleKey]) && $this->isBase64Image($sz[$pSingleKey])) {
                        $docketData['seizures'][$szIdx][$pSingleKey] = $this->processEvidencePhoto(
                            $sz[$pSingleKey],
                            $caseNumber,
                            $szIdx
                        );
                    }
                }
            }
        }

        return $docketData;
    }
}
