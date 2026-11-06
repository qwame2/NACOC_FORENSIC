<?php

namespace App\Services;

use Closure;

class CasePhotoResolver
{
    /**
     * Extract all photo paths/URLs from an exhibit or seizure item.
     */
    public function extractAllPhotos(mixed $item): array
    {
        $found = [];
        if (empty($item)) {
            return $found;
        }
        if (is_object($item)) {
            $item = (array) $item;
        }
        if (!is_array($item)) {
            return $found;
        }

        $keys = [
            'photos',
            'exhibit_photos',
            'evidence_photos',
            'photo',
            'exhibit_photo',
            'photo_url',
            'image',
            'image_path',
            'image_url',
            'file_path',
            'attachment',
            'attachments',
        ];
        foreach ($keys as $k) {
            if (!empty($item[$k])) {
                $val = $item[$k];
                if (is_string($val)) {
                    $val = trim($val);
                    if (str_starts_with($val, '[') || str_starts_with($val, '{')) {
                        $dec = json_decode($val, true);
                        if (is_array($dec)) {
                            foreach ($dec as $p) {
                                if (!empty($p) && is_string($p)) {
                                    $found[] = $p;
                                }
                            }
                        } else {
                            $found[] = $val;
                        }
                    } else {
                        $found[] = $val;
                    }
                } elseif (is_array($val)) {
                    foreach ($val as $p) {
                        if (!empty($p) && is_string($p)) {
                            $found[] = $p;
                        }
                    }
                }
            }
        }
        return array_values(array_unique($found));
    }

    /**
     * Format an exhibit photo path into a full URL.
     */
    public function formatPhotoUrl(mixed $p): string
    {
        if (empty($p)) {
            return '';
        }
        $p = trim((string) $p);
        if (str_starts_with($p, 'http://') || str_starts_with($p, 'https://') || str_starts_with($p, 'data:')) {
            return $p;
        }
        $clean = ltrim($p, '/');
        try {
            if (str_starts_with($clean, 'storage/')) {
                return asset($clean);
            }
            if (file_exists(public_path($clean))) {
                return asset($clean);
            }
            return asset('storage/' . $clean);
        } catch (\Throwable) {
            return str_starts_with($clean, 'storage/') ? '/' . $clean : '/storage/' . $clean;
        }
    }

    /**
     * Format a suspect mugshot source string into a full URL or null.
     */
    public function formatMugshotUrl(mixed $src): ?string
    {
        if (empty($src) || !is_string($src)) {
            return null;
        }
        $src = trim($src);
        if ($src === '' || $src === 'null' || $src === 'undefined') {
            return null;
        }
        if (str_starts_with($src, 'data:image/') || str_starts_with($src, 'http://') || str_starts_with($src, 'https://')) {
            return $src;
        }
        if (str_starts_with($src, '/storage/')) {
            return asset(ltrim($src, '/'));
        }
        if (str_starts_with($src, 'storage/')) {
            return asset($src);
        }
        return asset('storage/' . ltrim($src, '/'));
    }

    /**
     * Format an image source path into an asset URL or null.
     */
    public function getImgSrc(mixed $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        if (
            str_starts_with($path, 'data:') ||
            str_starts_with($path, 'http://') ||
            str_starts_with($path, 'https://')
        ) {
            return $path;
        }
        return asset($path);
    }

    public function getExtractAllPhotosClosure(): Closure
    {
        return fn(mixed $item): array => $this->extractAllPhotos($item);
    }

    public function getFormatPhotoUrlClosure(): Closure
    {
        return fn(mixed $p): string => $this->formatPhotoUrl($p);
    }

    public function getFormatMugshotUrlClosure(): Closure
    {
        return fn(mixed $src): ?string => $this->formatMugshotUrl($src);
    }

    public function getImgSrcClosure(): Closure
    {
        return fn(mixed $path): ?string => $this->getImgSrc($path);
    }

    /**
     * Resolve case-level primary photos, associate photos, and evidence gallery items.
     */
    public function resolveCasePhotos(mixed $case, array $docketData): array
    {
        $primarySuspectName = strtolower(trim($case->suspect_name ?? ''));

        $allAssocPhotos = [];
        if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
            foreach ($docketData['seizures'] as $szItem) {
                $assocPerson = strtolower(trim($szItem['associated_person'] ?? ($szItem['suspect_name'] ?? '')));
                $assocRole = strtolower(trim($szItem['association_role'] ?? ($szItem['role'] ?? '')));
                $isExplicitAssoc = !empty($szItem['is_associate']);

                $isPrimaryOwner =
                    empty($assocPerson) ||
                    (!empty($primarySuspectName) && $assocPerson === $primarySuspectName) ||
                    $assocPerson === 'primary suspect' ||
                    $assocPerson === 'primary' ||
                    str_contains($assocPerson, 'all suspect') ||
                    str_contains($assocPerson, 'joint');

                $isAssocRole = !empty($assocRole) && str_contains($assocRole, 'associate');
                $isAssocSz =
                    ($isExplicitAssoc || $isAssocRole || (!$isPrimaryOwner && !empty($assocPerson))) && !$isPrimaryOwner;

                if ($isAssocSz) {
                    $aPhotos = $szItem['evidence_photos'] ?? ($szItem['photos'] ?? []);
                    if (is_array($aPhotos)) {
                        foreach ($aPhotos as $ap) {
                            if ($ap && !in_array($ap, $allAssocPhotos)) {
                                $allAssocPhotos[] = $ap;
                            }
                        }
                    }
                    $apSingle = $szItem['photo'] ?? ($szItem['exhibit_photo'] ?? null);
                    if ($apSingle && !in_array($apSingle, $allAssocPhotos)) {
                        $allAssocPhotos[] = $apSingle;
                    }
                }
            }
        }
        if (!empty($docketData['associated_seizures']) && is_array($docketData['associated_seizures'])) {
            foreach ($docketData['associated_seizures'] as $aEx) {
                $aPhotos = $aEx['evidence_photos'] ?? ($aEx['photos'] ?? []);
                if (is_array($aPhotos)) {
                    foreach ($aPhotos as $ap) {
                        if ($ap && !in_array($ap, $allAssocPhotos)) {
                            $allAssocPhotos[] = $ap;
                        }
                    }
                }
                $apSingle = $aEx['photo'] ?? ($aEx['exhibit_photo'] ?? null);
                if ($apSingle && !in_array($apSingle, $allAssocPhotos)) {
                    $allAssocPhotos[] = $apSingle;
                }
            }
        }
        if (!empty($docketData['associate_exhibits']) && is_array($docketData['associate_exhibits'])) {
            foreach ($docketData['associate_exhibits'] as $aEx) {
                $aPhotos = $aEx['evidence_photos'] ?? ($aEx['photos'] ?? []);
                if (is_array($aPhotos)) {
                    foreach ($aPhotos as $ap) {
                        if ($ap && !in_array($ap, $allAssocPhotos)) {
                            $allAssocPhotos[] = $ap;
                        }
                    }
                }
                $apSingle = $aEx['photo'] ?? ($aEx['exhibit_photo'] ?? null);
                if ($apSingle && !in_array($apSingle, $allAssocPhotos)) {
                    $allAssocPhotos[] = $apSingle;
                }
            }
        }

        $rawPhotos = is_array($case->evidence_photos)
            ? $case->evidence_photos
            : (json_decode($case->evidence_photos ?? '[]', true) ?: $docketData['evidence_photos'] ?? []);
        if (!is_array($rawPhotos)) {
            $rawPhotos = [];
        }

        $primaryPhotos = [];
        if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
            foreach ($docketData['seizures'] as $szItem) {
                $assocPerson = strtolower(trim($szItem['associated_person'] ?? ($szItem['suspect_name'] ?? '')));
                $assocRole = strtolower(trim($szItem['association_role'] ?? ($szItem['role'] ?? '')));
                $isExplicitAssoc = !empty($szItem['is_associate']);

                $isPrimaryOwner =
                    empty($assocPerson) ||
                    (!empty($primarySuspectName) && $assocPerson === $primarySuspectName) ||
                    $assocPerson === 'primary suspect' ||
                    $assocPerson === 'primary' ||
                    str_contains($assocPerson, 'all suspect') ||
                    str_contains($assocPerson, 'joint');

                $isAssocRole = !empty($assocRole) && str_contains($assocRole, 'associate');
                $isAssocSz =
                    ($isExplicitAssoc || $isAssocRole || (!$isPrimaryOwner && !empty($assocPerson))) && !$isPrimaryOwner;

                if (!$isAssocSz) {
                    $pPhotos = $szItem['evidence_photos'] ?? ($szItem['photos'] ?? []);
                    if (is_array($pPhotos)) {
                        foreach ($pPhotos as $p) {
                            if ($p && !in_array($p, $primaryPhotos) && !in_array($p, $allAssocPhotos)) {
                                $primaryPhotos[] = $p;
                            }
                        }
                    }
                    $singleP = $szItem['photo'] ?? ($szItem['exhibit_photo'] ?? null);
                    if ($singleP && !in_array($singleP, $primaryPhotos) && !in_array($singleP, $allAssocPhotos)) {
                        $primaryPhotos[] = $singleP;
                    }
                }
            }
        }

        foreach ($rawPhotos as $p) {
            if ($p && !in_array($p, $primaryPhotos) && !in_array($p, $allAssocPhotos)) {
                $primaryPhotos[] = $p;
            }
        }
        if (!empty($docketData['evidence_photos']) && is_array($docketData['evidence_photos'])) {
            foreach ($docketData['evidence_photos'] as $p) {
                if ($p && !in_array($p, $primaryPhotos) && !in_array($p, $allAssocPhotos)) {
                    $primaryPhotos[] = $p;
                }
            }
        }

        $primaryPhotos = array_values(
            array_filter($primaryPhotos, function ($p) use ($allAssocPhotos) {
                return !in_array($p, $allAssocPhotos);
            }),
        );

        if (empty($primaryPhotos)) {
            $primaryPhotos = [];
        }

        $evidenceGalleryItems = [];
        foreach ($primaryPhotos as $p) {
            if (!empty($p)) {
                $evidenceGalleryItems[] = [
                    'url' => $p,
                    'label' => 'Primary Exhibit',
                    'type' => 'primary',
                ];
            }
        }
        foreach ($allAssocPhotos as $ap) {
            if (!empty($ap)) {
                $evidenceGalleryItems[] = [
                    'url' => $ap,
                    'label' => 'Associate Exhibit',
                    'type' => 'associate',
                ];
            }
        }

        $evidencePhotos = array_map(fn($item) => $item['url'], $evidenceGalleryItems);

        return [
            'primarySuspectName' => $primarySuspectName,
            'allAssocPhotos' => $allAssocPhotos,
            'rawPhotos' => $rawPhotos,
            'primaryPhotos' => $primaryPhotos,
            'evidenceGalleryItems' => $evidenceGalleryItems,
            'evidencePhotos' => $evidencePhotos,
        ];
    }
}

