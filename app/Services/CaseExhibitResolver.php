<?php

namespace App\Services;

use App\Models\Exhibit;
use App\Models\MultipleSuspectCase;
use Illuminate\Support\Collection;

class CaseExhibitResolver
{
    public function __construct(
        protected DrugExhibitClassifier $drugClassifier,
        protected ExhibitOwnershipResolver $ownershipResolver,
        protected CasePhotoResolver $photoResolver,
    ) {}

    /**
     * Resolve exhibits linked strictly to the active suspect (from top @php block).
     */
    public function resolveSuspectLinkedExhibits(
        mixed $case,
        array $docketData,
        string $activeSuspectNameLower,
        bool $isActiveSuspectPrimary,
    ): array {
        $suspectLinkedExhibits = [];
        $allSeizuresList = array_merge(
            $docketData['seizures'] ?? [],
            $docketData['associated_seizures'] ?? [],
            $docketData['associate_exhibits'] ?? [],
        );

        foreach ($allSeizuresList as $sz) {
            $assocPerson = strtolower(trim($sz['associated_person'] ?? ($sz['suspect_name'] ?? ($sz['owner_name'] ?? ''))));
            $isLinked = false;
            if (!empty($assocPerson)) {
                if (
                    $assocPerson === $activeSuspectNameLower ||
                    str_contains($assocPerson, $activeSuspectNameLower) ||
                    str_contains($activeSuspectNameLower, $assocPerson)
                ) {
                    $isLinked = true;
                } elseif (
                    str_contains($assocPerson, 'all suspect') ||
                    str_contains($assocPerson, 'joint') ||
                    ($isActiveSuspectPrimary && ($assocPerson === 'primary suspect' || $assocPerson === 'primary'))
                ) {
                    $isLinked = true;
                }
            } elseif ($isActiveSuspectPrimary) {
                $isLinked = true;
            }

            if ($isLinked) {
                $suspectLinkedExhibits[] = $sz;
            }
        }

        if (
            $isActiveSuspectPrimary &&
            (!empty($case->exhibit_category) || !empty($case->seizure_name_drug) || !empty($case->seizure_name))
        ) {
            $suspectLinkedExhibits[] = [
                'category' => $case->exhibit_category ?? '',
                'seizure_name_drug' => $case->seizure_name_drug ?? '',
                'seizure_name' => $case->seizure_name ?? '',
            ];
        }

        return [
            'allSeizuresList' => $allSeizuresList,
            'suspectLinkedExhibits' => $suspectLinkedExhibits,
        ];
    }

    /**
     * Resolve all case exhibits (DB + JSON seizures), deduplicated, with photos and joint exhibit filtering.
     */
    public function resolveAllCaseExhibits(
        mixed $case,
        array $docketData,
        bool $isMultiCase,
        array $primaryAndCoNamesList = [],
        string $primarySuspectsCombinedNames = '',
    ): array {
        $allCaseExhibits = [];
        $seenExhibitKeys = [];
        $allCaseExhibitPhotos = [];

        $dbExsList = new Collection();
        if (isset($case->id)) {
            try {
                $dbExsList = Exhibit::where(function ($q) use ($case, $isMultiCase) {
                    if ($isMultiCase) {
                        $q->where('multiple_suspect_case_id', $case->id);
                    } else {
                        $q->where('single_suspect_case_id', $case->id);
                    }
                })->get();
            } catch (\Throwable $e) {
                $dbExsList = new Collection();
            }
        }

        $addCaseExhibit = function ($item) use (
            &$allCaseExhibits,
            &$seenExhibitKeys,
            &$allCaseExhibitPhotos,
            $dbExsList,
            $case,
        ) {
            if (empty($item)) {
                return;
            }
            if (is_object($item)) {
                $item = (array) $item;
            }

            $exNo = $item['exhibit_number'] ?? null;
            $dbId = $item['id'] ?? null;
            $cat = $item['category'] ?? ($item['exhibit_category'] ?? 'General Exhibit');
            $name =
                $item['seizure_name'] ??
                ($item['commodity_name'] ??
                    ($item['name'] ?? ($item['weapon_model'] ?? 'Confiscated Exhibit')));

            $key = !empty($dbId)
                ? 'db_' . $dbId
                : (!empty($exNo)
                    ? 'ex_' . strtolower(trim($exNo))
                    : 'cn_' . strtolower(trim($cat)) . '_' . strtolower(trim($name)));

            if (!isset($seenExhibitKeys[$key])) {
                $seenExhibitKeys[$key] = true;

                $owner =
                    $item['owner_name'] ??
                    ($item['suspect_name'] ??
                        ($item['associated_person'] ??
                            ($item['veh_owner'] ??
                                ($item['wpn_owner'] ??
                                    ($item['dev_owner'] ?? null)))));
                if (
                    empty($owner) ||
                    strtolower(trim($owner)) === 'primary suspect' ||
                    strtolower(trim($owner)) === 'primary'
                ) {
                    $owner = $case->suspect_name ?? 'Primary Suspect';
                }
                $item['resolved_owner'] = $owner;

                $photos = $this->photoResolver->extractAllPhotos($item);
                if (empty($photos) && $dbExsList->isNotEmpty()) {
                    $dbMatch = $dbExsList->first(function ($dbE) use ($item) {
                        if (!empty($item['exhibit_number']) && $dbE->exhibit_number == $item['exhibit_number']) {
                            return true;
                        }
                        if (!empty($item['seizure_name']) && $dbE->commodity_name == $item['seizure_name']) {
                            return true;
                        }
                        if (!empty($item['name']) && $dbE->commodity_name == $item['name']) {
                            return true;
                        }
                        return false;
                    });
                    if ($dbMatch && !empty($dbMatch->evidence_photos)) {
                        $photos = is_array($dbMatch->evidence_photos)
                            ? $dbMatch->evidence_photos
                            : (json_decode($dbMatch->evidence_photos, true) ?: []);
                        $item['evidence_photos'] = $photos;
                    }
                }
                $item['resolved_item_photos'] = $photos;
                $item['formatted_item_photos'] = array_map(
                    fn($p) => $this->photoResolver->formatPhotoUrl($p),
                    $photos,
                );

                $allCaseExhibits[] = $item;

                foreach ($photos as $p) {
                    if ($p && !in_array($p, $allCaseExhibitPhotos)) {
                        $allCaseExhibitPhotos[] = $p;
                    }
                }
            }
        };

        foreach ($dbExsList as $dbE) {
            $arr = $dbE->toArray();
            $arr['category'] = $dbE->exhibit_category ?? 'General Exhibit';
            $arr['seizure_name'] = $dbE->commodity_name ?? ($dbE->wpn_model ?? 'Confiscated Exhibit');
            $arr['owner_name'] = $dbE->suspect
                ? $dbE->suspect->suspect_name
                : ($dbE->associated_person ?? ($dbE->veh_owner ?? ($dbE->wpn_owner ?? ($dbE->dev_owner ?? null))));
            $arr['weapon_model'] = $dbE->wpn_model;
            $arr['weapon_serial_number'] = $dbE->wpn_serial_no;
            $arr['weapon_calibre'] = $dbE->wpn_calibre;
            $arr['weapon_color'] = $dbE->wpn_colour;
            $arr['weapon_size_length'] = $dbE->wpn_size;
            $addCaseExhibit($arr);
        }

        $jsonSeizures = array_merge(
            $docketData['seizures'] ?? [],
            $docketData['associated_seizures'] ?? [],
            $docketData['associate_exhibits'] ?? [],
        );
        foreach ($jsonSeizures as $sz) {
            $addCaseExhibit($sz);
        }

        $caseRawPhotos = is_array($case->evidence_photos)
            ? $case->evidence_photos
            : (json_decode($case->evidence_photos ?? '[]', true) ?: $docketData['evidence_photos'] ?? []);
        if (is_array($caseRawPhotos)) {
            foreach ($caseRawPhotos as $p) {
                if ($p && is_string($p) && !in_array($p, $allCaseExhibitPhotos)) {
                    $allCaseExhibitPhotos[] = $p;
                }
            }
        }
        $allCaseExhibitPhotos = array_values(array_unique($allCaseExhibitPhotos));
        $formattedAllCaseExhibitPhotos = array_map(
            fn($p) => $this->photoResolver->formatPhotoUrl($p),
            $allCaseExhibitPhotos,
        );
        if (!empty($allCaseExhibits)) {
            $allCaseExhibits[0]['formatted_case_gallery_photos'] = $formattedAllCaseExhibitPhotos;
        }

        $jointCaseExhibits = [];
        $seenJointKeys = [];
        foreach ($allCaseExhibits as $cEx) {
            if ($this->ownershipResolver->isJointExhibit($cEx, $primaryAndCoNamesList)) {
                $jKey = ($cEx['id'] ?? '') . '_' . ($cEx['seizure_name'] ?? ($cEx['commodity_name'] ?? ''));
                if (!isset($seenJointKeys[$jKey])) {
                    $seenJointKeys[$jKey] = true;
                    $jointCaseExhibits[] = $cEx;
                }
            }
        }
        $isJointExhibitCase = !empty($jointCaseExhibits);
        $jointDossierHeadingName = !empty($primarySuspectsCombinedNames)
            ? $primarySuspectsCombinedNames
            : 'Primary & Co-Suspect';

        return [
            'capturedJointExhibits' => [],
            'savedJointLabExam' => null,
            'savedJointLabHeadingName' => null,
            'allCaseExhibits' => $allCaseExhibits,
            'seenExhibitKeys' => $seenExhibitKeys,
            'allCaseExhibitPhotos' => $allCaseExhibitPhotos,
            'jointCaseExhibits' => $jointCaseExhibits,
            'seenJointKeys' => $seenJointKeys,
            'isJointExhibitCase' => $isJointExhibitCase,
            'jointDossierHeadingName' => $jointDossierHeadingName,
        ];
    }

    /**
     * Resolve exhibits for a specific suspect and filter out joint exhibits ( capturing them into $capturedJointExhibits).
     */
    public function getExhibitsForSuspect(
        mixed $case,
        ?string $sName,
        array $sItem,
        int $sIdx,
        bool $isAssociate,
        array $primaryAndCoNamesList,
        array &$capturedJointExhibits = [],
    ): array {
        $rawSuspectExhibits = is_object($case) && method_exists($case, 'getExhibitsForSuspect')
            ? $case->getExhibitsForSuspect(
                $sName,
                $sItem['associate_id'] ?? ($sItem['suspect_id'] ?? null),
                $sIdx,
                $isAssociate,
            )
            : [];

        $suspectExhibits = [];
        foreach ($rawSuspectExhibits as $rEx) {
            if ($this->ownershipResolver->isJointExhibit($rEx, $primaryAndCoNamesList)) {
                $capturedJointExhibits[] = $rEx;
            } else {
                $suspectExhibits[] = $rEx;
            }
        }

        return [
            'rawSuspectExhibits' => $rawSuspectExhibits,
            'suspectExhibits' => $suspectExhibits,
        ];
    }

    /**
     * Resolve final joint case exhibits and heading metadata after suspect loop.
     */
    public function resolveFinalJointExhibitsSummary(
        mixed $case,
        array $allCaseExhibits,
        array $capturedJointExhibits,
        array $primaryAndCoNamesList,
        string $primarySuspectsCombinedNames,
        bool $isMultiCase,
        bool $hasCoSuspectsInCase,
        array $suspectsList,
        array $rawSuspects,
    ): array {
        $jointCaseExhibits = [];
        $seenJointKeys = [];
        foreach ($allCaseExhibits as $cEx) {
            if ($this->ownershipResolver->isJointExhibit($cEx, $primaryAndCoNamesList)) {
                $jKey = ($cEx['id'] ?? '') . '_' . ($cEx['seizure_name'] ?? ($cEx['commodity_name'] ?? ''));
                if (!isset($seenJointKeys[$jKey])) {
                    $seenJointKeys[$jKey] = true;
                    $jointCaseExhibits[] = $cEx;
                }
            }
        }
        if (empty($jointCaseExhibits) && !empty($capturedJointExhibits)) {
            foreach ($capturedJointExhibits as $cEx) {
                $jKey = ($cEx['id'] ?? '') . '_' . ($cEx['seizure_name'] ?? ($cEx['commodity_name'] ?? ''));
                if (!isset($seenJointKeys[$jKey])) {
                    $seenJointKeys[$jKey] = true;
                    $jointCaseExhibits[] = $cEx;
                }
            }
        }
        if (empty($jointCaseExhibits) && !empty($allCaseExhibits)) {
            $jointCaseExhibits = $allCaseExhibits;
        }
        $isJointExhibitCase = !empty($jointCaseExhibits);
        $isMultiple = !empty($isMultiCase)
            || !empty($hasCoSuspectsInCase)
            || !empty($case->is_multiple)
            || (($case->underlyingRecord ?? null) instanceof MultipleSuspectCase)
            || (count($suspectsList ?: $rawSuspects) > 1);

        $jointDossierHeadingName = !empty($primarySuspectsCombinedNames)
            ? $primarySuspectsCombinedNames
            : ($isMultiple ? 'Primary & Co-Suspect' : ($case->suspect_name ?? 'Primary Suspect'));

        $dossierBadgeText = $isMultiple
            ? 'PRIMARY & CO-SUSPECT EXHIBIT DOSSIER'
            : 'PRIMARY EXHIBIT DOSSIER';

        $dossierHeadingPrefix = $isMultiple
            ? 'Primary & Co-Suspect Seizures & Exhibits'
            : 'Primary Suspect Seizures & Exhibits';

        $dossierHeadingText = !empty($jointDossierHeadingName) && $jointDossierHeadingName !== 'Primary Suspect' && $jointDossierHeadingName !== 'Primary & Co-Suspect'
            ? "{$dossierHeadingPrefix} — {$jointDossierHeadingName}"
            : ($isMultiple && $jointDossierHeadingName === 'Primary & Co-Suspect'
                ? "Primary & Co-Suspect Seizures & Exhibits — {$jointDossierHeadingName}"
                : "{$dossierHeadingPrefix}" . (!empty($jointDossierHeadingName) ? " — {$jointDossierHeadingName}" : ''));

        return [
            'jointCaseExhibits' => $jointCaseExhibits,
            'seenJointKeys' => $seenJointKeys,
            'isJointExhibitCase' => $isJointExhibitCase,
            'isMultiple' => $isMultiple,
            'jointDossierHeadingName' => $jointDossierHeadingName,
            'dossierBadgeText' => $dossierBadgeText,
            'dossierHeadingPrefix' => $dossierHeadingPrefix,
            'dossierHeadingText' => $dossierHeadingText,
        ];
    }
}

