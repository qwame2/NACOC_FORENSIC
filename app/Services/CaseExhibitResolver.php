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
        if (isset($case->exhibits) && $case->exhibits instanceof Collection && $case->exhibits->isNotEmpty()) {
            $dbExsList = $case->exhibits;
        } elseif (!empty($case->id) && empty($case->is_staff_submission)) {
            try {
                $uId = isset($case->underlyingRecord) ? $case->underlyingRecord->id : $case->id;
                $dbExsList = Exhibit::where(function ($q) use ($case, $uId) {
                    $q->where('single_suspect_case_id', $case->id)
                        ->orWhere('multiple_suspect_case_id', $case->id)
                        ->orWhere('single_suspect_case_id', $uId)
                        ->orWhere('multiple_suspect_case_id', $uId);
                })->get();
            } catch (\Throwable $e) {
                $dbExsList = new Collection();
            }
        }
        if ($dbExsList->isEmpty() && !empty($case->case_number)) {
            try {
                $dbExsList = Exhibit::whereHas('singleSuspectCase', fn($q) => $q->where('case_number', $case->case_number))
                    ->orWhereHas('multipleSuspectCase', fn($q) => $q->where('case_number', $case->case_number))
                    ->get();
            } catch (\Throwable $e) {}
        }

        $allDocketSeizures = array_merge(
            $docketData['seizures'] ?? [],
            $docketData['associated_seizures'] ?? [],
            $docketData['associate_exhibits'] ?? [],
        );

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

            // Ensure scalar hygiene on exhibits to prevent array injection into string fields
            foreach (['wpn_condition', 'wpn_serial_no', 'wpn_calibre', 'wpn_loaded_status', 'wpn_marks', 'wpn_location_found', 'veh_condition', 'veh_reg_no', 'veh_vin', 'veh_fuel', 'veh_damage', 'veh_doc_ref', 'veh_owner_contact', 'veh_mods', 'veh_features', 'dev_serial_no', 'dev_imei1', 'dev_imei2', 'dev_storage', 'dev_colour', 'dev_marks', 'dev_mac_address'] as $sf) {
                if (isset($item[$sf]) && !is_scalar($item[$sf])) {
                    $item[$sf] = null;
                }
            }
            if (isset($item['wpn']) && is_array($item['wpn'])) {
                foreach (['condition', 'serial_no', 'calibre', 'loaded_status', 'marks', 'location_found'] as $subWpn) {
                    if (isset($item['wpn'][$subWpn]) && !is_scalar($item['wpn'][$subWpn])) {
                        $item['wpn'][$subWpn] = null;
                    }
                }
            }

            $exNo = $item['exhibit_number'] ?? null;
            $dbId = $item['id'] ?? null;
            $cat = $item['category'] ?? ($item['exhibit_category'] ?? 'General Exhibit');
            $rawSeizureName = $item['seizure_name'] ?? null;
            $rawCommodity = $item['commodity_name'] ?? ($item['seizure_name_currency'] ?? null);
            if (!empty($rawCommodity) && (empty($rawSeizureName) || in_array(strtolower(trim((string)$rawSeizureName)), ['exhibit', 'exhibits']))) {
                $item['seizure_name'] = $rawCommodity;
            }
            if (empty($item['commodity_name']) && !empty($rawCommodity)) {
                $item['commodity_name'] = $rawCommodity;
            }
            if (!isset($item['amount']) && in_array(strtolower(trim((string)$cat)), ['money', 'currency', 'cash'])) {
                $item['amount'] = $item['seizure_quantity'] ?? ($item['quantity'] ?? null);
            }

            // Ensure device fields are fully resolved on all exhibits
            $item['dev_model'] = $item['dev_model'] ?? ($item['dev']['model'] ?? ($item['device_model'] ?? ($item['model'] ?? ($case->dev_model ?? null))));
            $item['dev_serial_no'] = $item['dev_serial_no'] ?? ($item['dev']['serial_no'] ?? ($item['device_serial'] ?? ($item['serial_no'] ?? ($item['serial_number'] ?? ($case->dev_serial_no ?? null)))));
            $item['dev_imei1'] = $item['dev_imei1'] ?? ($item['dev']['imei1'] ?? ($item['dev_imei_1'] ?? ($item['imei1'] ?? ($item['imei_1'] ?? ($case->dev_imei1 ?? null)))));
            $item['dev_imei2'] = $item['dev_imei2'] ?? ($item['dev']['imei2'] ?? ($item['dev_imei_2'] ?? ($item['imei2'] ?? ($item['imei_2'] ?? ($case->dev_imei2 ?? null)))));
            $item['dev_mac_address'] = $item['dev_mac_address'] ?? ($item['dev']['mac_address'] ?? ($item['mac_address'] ?? ($case->dev_mac_address ?? null)));
            $item['dev_storage'] = $item['dev_storage'] ?? ($item['dev']['storage'] ?? ($item['storage_capacity'] ?? ($item['dev_storage_capacity'] ?? ($item['storage'] ?? ($case->dev_storage ?? null)))));
            $item['dev_colour'] = $item['dev_colour'] ?? ($item['dev']['colour'] ?? ($item['dev_color'] ?? ($item['colour'] ?? ($item['color'] ?? ($item['device_color'] ?? ($case->dev_colour ?? null))))));
            $item['dev_marks'] = $item['dev_marks'] ?? ($item['dev']['marks'] ?? ($item['dev_other_marks'] ?? ($item['marks'] ?? ($item['distinguishing_marks'] ?? ($item['other_marks'] ?? ($case->dev_marks ?? null))))));
            $item['dev'] = [
                'model'       => $item['dev_model'],
                'serial_no'   => $item['dev_serial_no'],
                'imei1'       => $item['dev_imei1'],
                'imei2'       => $item['dev_imei2'],
                'mac_address' => $item['dev_mac_address'],
                'storage'     => $item['dev_storage'],
                'colour'      => $item['dev_colour'],
                'marks'       => $item['dev_marks'],
            ];
            $item['device_model'] = $item['dev_model'];
            $item['device_serial'] = $item['dev_serial_no'];

            $name =
                $item['seizure_name'] ??
                ($item['commodity_name'] ??
                    ($item['name'] ?? ($item['weapon_model'] ?? 'Confiscated Exhibit')));

            if ((str_contains(strtolower($cat), 'electronic') || str_contains(strtolower($cat), 'device')) && in_array(strtolower(trim((string)$name)), ['electronic devices', 'electronic device', 'device', 'devices', 'general exhibit', 'confiscated exhibit', 'exhibit', 'exhibits', 'n/a']) && !empty($item['dev_model'])) {
                $name = $item['dev_model'];
                $item['seizure_name'] = $item['dev_model'];
                $item['commodity_name'] = $item['dev_model'];
            }

            $possibleKeys = [];
            if (!empty($dbId)) {
                $possibleKeys[] = 'db_' . $dbId;
            }
            if (!empty($exNo)) {
                $possibleKeys[] = 'ex_' . strtolower(trim((string)$exNo));
            }
            if (!empty($name)) {
                $possibleKeys[] = 'cn_' . strtolower(trim((string)$cat)) . '_' . strtolower(trim((string)$name));
            }

            $matchedExistingIdx = null;
            foreach ($possibleKeys as $pk) {
                if (isset($seenExhibitKeys[$pk])) {
                    $matchedExistingIdx = $seenExhibitKeys[$pk];
                    break;
                }
            }

            if ($matchedExistingIdx !== null) {
                if (isset($allCaseExhibits[$matchedExistingIdx])) {
                    foreach (['investigating_officer', 'source_requesting_unit', 'seizure_reason', 'source_of_exhibits', 'seizure_location', 'seizure_officer', 'seizure_packaging', 'seizure_concealment', 'seizure_date', 'seizure_time', 'exhibit_remark', 'other_exhibit_description', 'dev_model', 'dev_serial_no', 'dev_imei1', 'dev_imei2', 'dev_mac_address', 'dev_storage', 'dev_colour', 'dev_marks'] as $df) {
                        if (empty($allCaseExhibits[$matchedExistingIdx][$df]) && !empty($item[$df])) {
                            $allCaseExhibits[$matchedExistingIdx][$df] = $item[$df];
                        }
                    }
                    if (isset($allCaseExhibits[$matchedExistingIdx]['dev'])) {
                        foreach (['model', 'serial_no', 'imei1', 'imei2', 'mac_address', 'storage', 'colour', 'marks'] as $subF) {
                            if (empty($allCaseExhibits[$matchedExistingIdx]['dev'][$subF]) && !empty($item['dev'][$subF])) {
                                $allCaseExhibits[$matchedExistingIdx]['dev'][$subF] = $item['dev'][$subF];
                            }
                        }
                    }
                }
                return;
            }

            $currIdx = count($allCaseExhibits);
            foreach ($possibleKeys as $pk) {
                $seenExhibitKeys[$pk] = $currIdx;
            }

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

                // Ensure investigating_officer is authoritatively populated from DB exhibit record if empty
                if (empty($item['investigating_officer']) && $dbExsList->isNotEmpty()) {
                    $dbMatch = $dbExsList->first(function ($dbE) use ($item) {
                        if (!empty($item['id']) && (int)$dbE->id === (int)$item['id']) return true;
                        if (!empty($item['exhibit_number']) && (string)$dbE->exhibit_number === (string)$item['exhibit_number']) return true;
                        if (!empty($item['commodity_name']) && strtolower(trim((string)$dbE->commodity_name)) === strtolower(trim((string)$item['commodity_name']))) return true;
                        if (!empty($item['seizure_name']) && strtolower(trim((string)$dbE->commodity_name)) === strtolower(trim((string)$item['seizure_name']))) return true;
                        return false;
                    });
                    if ($dbMatch && !empty($dbMatch->investigating_officer)) {
                        $item['investigating_officer'] = $dbMatch->investigating_officer;
                    }
                }
                if (empty($item['investigating_officer']) && !empty($item['id'])) {
                    try {
                        $item['investigating_officer'] = Exhibit::where('id', $item['id'])->value('investigating_officer');
                    } catch (\Throwable $e) {}
                }
                if (empty($item['investigating_officer']) && !empty($item['exhibit_number'])) {
                    try {
                        $item['investigating_officer'] = Exhibit::where('exhibit_number', $item['exhibit_number'])->value('investigating_officer');
                    } catch (\Throwable $e) {}
                }
                if (empty($item['investigating_officer']) && !empty($case->investigating_officer)) {
                    $item['investigating_officer'] = $case->investigating_officer;
                }

                $allCaseExhibits[] = $item;

                foreach ($photos as $p) {
                    if ($p && !in_array($p, $allCaseExhibitPhotos)) {
                        $allCaseExhibitPhotos[] = $p;
                    }
                }
            };

        foreach ($dbExsList as $dbE) {
            $arr = $dbE->toArray();
            $arr['category'] = $dbE->exhibit_category ?? 'General Exhibit';
            $arr['commodity_name'] = $dbE->commodity_name;
            $arr['amount'] = $dbE->amount ?? (in_array(strtolower(trim((string)$dbE->exhibit_category)), ['money', 'currency', 'cash']) ? $dbE->seizure_quantity : null);
            $arr['seizure_name'] = (!empty($dbE->commodity_name) && !in_array(strtolower(trim((string)$dbE->commodity_name)), ['exhibit', 'exhibits']))
                ? $dbE->commodity_name
                : ($dbE->wpn_model ?? ($dbE->commodity_name ?? 'Confiscated Exhibit'));
            $arr['owner_name'] = $dbE->suspect
                ? $dbE->suspect->suspect_name
                : ($dbE->associated_person ?? ($dbE->veh_owner ?? ($dbE->wpn_owner ?? ($dbE->dev_owner ?? null))));
            $arr['weapon_model'] = $dbE->wpn_model;
            $arr['weapon_serial_number'] = $dbE->wpn_serial_no;
            $arr['weapon_calibre'] = $dbE->wpn_calibre;
            $arr['weapon_color'] = $dbE->wpn_colour;
            $arr['weapon_size_length'] = $dbE->wpn_size;

            // Ensure custody & investigating officer attributes are mapped from DB model
            $arr['investigating_officer']  = $dbE->investigating_officer;
            $arr['source_requesting_unit'] = $dbE->source_requesting_unit;
            $arr['seizure_reason']         = $dbE->seizure_reason;
            $arr['source_of_exhibits']     = $dbE->source_of_exhibits ?? $dbE->source_of_exhibit;
            $arr['exhibit_remark']         = $dbE->exhibit_remark ?? $dbE->lab_remark;
            $arr['seizure_location']       = $dbE->seizure_location;
            $arr['seizure_officer']        = $dbE->seizure_officer;
            $arr['seizure_packaging']      = $dbE->seizure_packaging;
            $arr['seizure_concealment']    = $dbE->seizure_concealment;
            $arr['seizure_date']           = $dbE->seizure_date;
            $arr['seizure_time']           = $dbE->seizure_time;

            // Ensure device fields are fully mapped from DB model
            $arr['dev_model']       = $dbE->dev_model;
            $arr['dev_serial_no']   = $dbE->dev_serial_no;
            $arr['dev_imei1']       = $dbE->dev_imei1;
            $arr['dev_imei2']       = $dbE->dev_imei2;
            $arr['dev_mac_address'] = $dbE->dev_mac_address;
            $arr['dev_storage']     = $dbE->dev_storage;
            $arr['dev_colour']      = $dbE->dev_colour;
            $arr['dev_marks']       = $dbE->dev_marks;

            $hasMissingDbDev = empty(trim((string)$arr['dev_model']))
                || empty(trim((string)$arr['dev_serial_no']))
                || empty(trim((string)$arr['dev_imei1']))
                || empty(trim((string)$arr['dev_imei2']))
                || empty(trim((string)$arr['dev_storage']))
                || empty(trim((string)$arr['dev_colour']))
                || empty(trim((string)$arr['dev_marks']))
                || empty(trim((string)$arr['dev_mac_address']));

            if ($hasMissingDbDev && !empty($allDocketSeizures)) {
                $matchDocket = collect($allDocketSeizures)->first(function ($dSz) use ($dbE) {
                    if (!is_array($dSz)) return false;
                    if (!empty($dSz['id']) && (int)$dSz['id'] === (int)$dbE->id) return true;
                    if (!empty($dSz['exhibit_number']) && !empty($dbE->exhibit_number) && (string)$dSz['exhibit_number'] === (string)$dbE->exhibit_number) return true;
                    if (!empty($dSz['commodity_name']) && !empty($dbE->commodity_name) && strtolower(trim($dSz['commodity_name'])) === strtolower(trim($dbE->commodity_name))) return true;
                    if (!empty($dSz['seizure_name']) && !empty($dbE->commodity_name) && strtolower(trim($dSz['seizure_name'])) === strtolower(trim($dbE->commodity_name))) return true;
                    return false;
                });
                if ($matchDocket) {
                    $arr['dev_model']       = $arr['dev_model'] ?: ($matchDocket['dev_model'] ?? ($matchDocket['dev']['model'] ?? ($matchDocket['device_model'] ?? ($matchDocket['model'] ?? null))));
                    $arr['dev_serial_no']   = $arr['dev_serial_no'] ?: ($matchDocket['dev_serial_no'] ?? ($matchDocket['dev']['serial_no'] ?? ($matchDocket['device_serial'] ?? ($matchDocket['serial_no'] ?? ($matchDocket['serial_number'] ?? null)))));
                    $arr['dev_imei1']       = $arr['dev_imei1'] ?: ($matchDocket['dev_imei1'] ?? ($matchDocket['dev']['imei1'] ?? ($matchDocket['dev_imei_1'] ?? ($matchDocket['imei1'] ?? ($matchDocket['imei_1'] ?? null)))));
                    $arr['dev_imei2']       = $arr['dev_imei2'] ?: ($matchDocket['dev_imei2'] ?? ($matchDocket['dev']['imei2'] ?? ($matchDocket['dev_imei_2'] ?? ($matchDocket['imei2'] ?? ($matchDocket['imei_2'] ?? null)))));
                    $arr['dev_mac_address'] = $arr['dev_mac_address'] ?: ($matchDocket['dev_mac_address'] ?? ($matchDocket['dev']['mac_address'] ?? ($matchDocket['mac_address'] ?? null)));
                    $arr['dev_storage']     = $arr['dev_storage'] ?: ($matchDocket['dev_storage'] ?? ($matchDocket['dev']['storage'] ?? ($matchDocket['storage_capacity'] ?? ($matchDocket['dev_storage_capacity'] ?? ($matchDocket['storage'] ?? null)))));
                    $arr['dev_colour']      = $arr['dev_colour'] ?: ($matchDocket['dev_colour'] ?? ($matchDocket['dev']['colour'] ?? ($matchDocket['dev_color'] ?? ($matchDocket['colour'] ?? ($matchDocket['color'] ?? ($matchDocket['device_color'] ?? null))))));
                    $arr['dev_marks']       = $arr['dev_marks'] ?: ($matchDocket['dev_marks'] ?? ($matchDocket['dev']['marks'] ?? ($matchDocket['dev_other_marks'] ?? ($matchDocket['marks'] ?? ($matchDocket['distinguishing_marks'] ?? ($matchDocket['other_marks'] ?? null))))));
                }
            }

            // Ensure vehicle fields are fully mapped from DB model
            $arr['veh_make']        = $dbE->veh_make;
            $arr['veh_model']       = $dbE->veh_model;
            $arr['veh_type']        = $dbE->veh_type;
            $arr['veh_year']        = $dbE->veh_year;
            $arr['veh_colour']      = $dbE->veh_colour;
            $arr['veh_reg_no']      = $dbE->veh_reg_no;
            $arr['veh_vin']         = $dbE->veh_vin;
            $arr['veh_engine_no']   = $dbE->veh_engine_no;
            $arr['veh_country_reg'] = $dbE->veh_country_reg;
            $arr['veh_owner']       = $dbE->veh_owner;
            $arr['veh_driver']      = $dbE->veh_driver;
            $arr['veh_owner_contact']  = $dbE->veh_owner_contact;
            $arr['veh_docs_available'] = $dbE->veh_docs_available;
            $arr['veh_doc_ref']        = $dbE->veh_doc_ref;
            $arr['veh_condition']      = $dbE->veh_condition;
            $arr['veh_fuel']           = $dbE->veh_fuel;
            $arr['veh_keys_recv']      = $dbE->veh_keys_recv;
            $arr['veh_key_count']      = $dbE->veh_key_count;
            $arr['veh_damage']         = $dbE->veh_damage;
            $arr['veh_mods']           = $dbE->veh_mods;
            $arr['veh_features']       = $dbE->veh_features;

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
            $owner = $cEx['owner_name'] ?? ($cEx['suspect_name'] ?? ($cEx['associated_person'] ?? ($cEx['owner'] ?? '')));
            $isJoint = $this->ownershipResolver->isJointExhibit($cEx, $primaryAndCoNamesList);
            $isPrimaryCo = !$isMultiCase || $this->ownershipResolver->isPrimaryOrCoSuspectName($owner, $primaryAndCoNamesList);
            if ($isJoint || $isPrimaryCo) {
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
        $isJointExhibitCase = !empty($jointCaseExhibits) || !empty($allCaseExhibits);
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
            $owner = $cEx['owner_name'] ?? ($cEx['suspect_name'] ?? ($cEx['associated_person'] ?? ($cEx['owner'] ?? '')));
            $isJoint = $this->ownershipResolver->isJointExhibit($cEx, $primaryAndCoNamesList);
            $isPrimaryCo = !$isMultiCase || $this->ownershipResolver->isPrimaryOrCoSuspectName($owner, $primaryAndCoNamesList);
            if ($isJoint || $isPrimaryCo) {
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
        $isJointExhibitCase = !empty($jointCaseExhibits) || !empty($allCaseExhibits);
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

