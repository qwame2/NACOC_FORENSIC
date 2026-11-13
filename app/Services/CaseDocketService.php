<?php

namespace App\Services;

use App\Enums\CaseStatus;
use App\Enums\UnitName;
use App\Enums\WorkflowStatus;
use App\Http\Controllers\Concerns\CaseControllerHelpers;
use App\Models\CaseSuspect;
use App\Models\Exhibit;
use App\Models\ForensicCase;
use App\Models\LaboratoryExaminationResult;
use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Models\SuspectGsaReport;
use App\Models\SuspectNlrReport;
use App\Models\Unit;
use App\Services\AuditLogger;
use App\Services\CaseDuplicateDetector;
use App\Services\CaseNumberService;
use App\Services\LaboratoryExamService;
use App\Services\SchemaCache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * CaseDocketService
 *
 * Dedicated domain service for core forensic case lifecycle operations:
 * - Case docket creation & multi-suspect registration (store)
 * - Multi-unit case dossier corrections & laboratory results synchronization (update)
 * - Association of co-suspects and forensic exhibits (associate)
 *
 * Extracted from CaseController to adhere to Single Responsibility Principle
 * and enforce a <400 line controller architecture.
 */
class CaseDocketService
{
    use CaseControllerHelpers;

    /**
     * Retrieve formatted cases listing with real-time workflow progress.
     */
    public function getCaseListing()
    {
        $cases = ForensicCase::with(['user', 'unit'])->orderBy('created_at', 'desc')->get();

        return $cases->map(function ($case) {
            $units = $case->getWorkflowUnits();
            $docket = $this->decodeDocket($case);
            $unitProg = $docket['unit_progress'] ?? [];
            $completed = 0;
            foreach ($units as $u) {
                if (isset($unitProg[$u]['status']) && strtoupper($unitProg[$u]['status']) === WorkflowStatus::COMPLETED->value) {
                    $completed++;
                }
            }
            $case->workflow_units = $units;
            $case->workflow_total = count($units);
            $case->workflow_completed = $completed;

            $suspectsList = $case->getSuspectsList();
            if (!empty($suspectsList)) {
                $case->suspects_list = $suspectsList;
                if (empty($docket['suspects'])) {
                    $docket['suspects'] = $suspectsList;
                    $case->docket_data = $docket;
                }
            }
            return $case;
        });
    }

    /**
     * Reassign an exhibit to an associate or primary suspect.
     */
    public function reassignExhibit(ForensicCase $case, Request $request): array
    {
        $exIndex      = $request->input('exhibit_index');
        $newOwnerName = $request->input('new_owner_name');
        $newOwnerId   = $request->input('new_owner_id');

        $docketData = $this->decodeDocket($case);

        if (isset($docketData['seizures']) && is_array($docketData['seizures'])) {
            foreach ($docketData['seizures'] as $idx => &$sz) {
                $szName = $sz['seizure_name'] ?? ($sz['name'] ?? '');
                $reqName = $request->input('exhibit_name');
                if ((string)$idx === (string)$exIndex || (!empty($reqName) && strtolower(trim($szName)) === strtolower(trim($reqName)))) {
                    $sz['associated_person'] = $newOwnerName;
                    $sz['associate_id']      = $newOwnerId;
                    $sz['suspect_id']        = $newOwnerId;
                    $sz['is_associate']      = (!empty($newOwnerId) && str_contains(strtoupper($newOwnerId), 'ASC'));
                }
            }
            unset($sz);

            $case->docket_data = $docketData;
            $case->save();

            return [
                'success' => true,
                'message' => "Exhibit item successfully re-assigned to {$newOwnerName}" . ($newOwnerId ? " [ID: {$newOwnerId}]" : '') . ".",
            ];
        }

        return ['success' => false, 'message' => 'Exhibit index not found.', 'code' => 404];
    }

    /**
     * Store a new forensic case docket and its associated suspects/exhibits.
     * Fix Issue 2: Uses CaseNumberService with crypto-random suffix & withUniqueRetry.
     * Fix Issue 3: SchemaCache::hasColumn.
     * Fix Issue 4: Decode docket_data once.
     * Fix Issue 9 & 15: Null guard on underlyingRecord.
     * Fix Issue 11: Centralized lab exam handling via LaboratoryExamService.
     * Fix Issue 18: Use WorkflowStatus / UnitName enums.
     * Fix Issue 20: Dead code removed ($exCatStore / $isMultipleCasePdf).
     * Fix Issue 21: Null-safe Auth::user() audit logging.
     */
    public function storeCase(Request $request): array
    {
        $noSuspect = filter_var($request->input('no_suspect'), FILTER_VALIDATE_BOOLEAN);

        $validated = Validator::make($request->all(), [
            'no_suspect'                     => ['nullable', 'boolean'],
            'link_suspect_to_locked_exhibit' => ['nullable', 'boolean'],
            'case_number'                    => ['nullable', 'string', 'max:50'],
            'title'                => [$noSuspect ? 'nullable' : 'required', 'string', 'max:255'],
            'unit_name'            => ['nullable', 'string', 'max:255'],
            'arresting_officer'    => ['nullable', 'string', 'max:255'],
            'submitting_officer'   => ['nullable', 'string', 'max:255'],
            'suspect_name'         => [$noSuspect ? 'nullable' : 'required', 'string', 'max:255'],
            'associated_person'    => ['nullable', 'string', 'max:255'],
            'suspect_alias'        => ['nullable', 'string', 'max:255'],
            'ghana_card'           => ['nullable', 'string', 'max:255'],
            'gender'               => ['nullable', 'string', 'max:50'],
            'dob'                  => ['nullable', 'string', 'max:50'],
            'age'                  => ['nullable'],
            'height'               => ['nullable', 'string', 'max:100'],
            'eye_color'            => ['nullable', 'string', 'max:100'],
            'nationality'          => ['nullable', 'string', 'max:100'],
            'pob'                  => ['nullable', 'string', 'max:255'],
            'phone'                => ['nullable', 'string', 'max:50'],
            'phone_label'          => ['nullable', 'string', 'max:50'],
            'phone_labels'         => ['nullable', 'array'],
            'additional_phones'    => ['nullable', 'array'],
            'additional_phones.*'  => ['nullable', 'string', 'max:50'],
            'additional_phone_labels'  => ['nullable', 'array'],
            'additional_phone_labels.*'=> ['nullable', 'string'],
            'phones'               => ['nullable'],
            'address'              => ['nullable', 'string'],
            'marital_status'       => ['nullable', 'string', 'max:100'],
            'has_children'         => ['nullable'],
            'children_count'       => ['nullable'],
            'spouse_info'          => ['nullable', 'string'],
            'spouse_name'          => ['nullable', 'string', 'max:255'],
            'spouse_phone'         => ['nullable', 'string', 'max:100'],
            'partner_name'         => ['nullable', 'string', 'max:255'],
            'partner_phone'        => ['nullable', 'string', 'max:100'],
            'partner_info'         => ['nullable', 'string'],
            'dependents'           => ['nullable', 'string', 'max:100'],
            'holding_facility'     => ['nullable', 'string', 'max:255'],
            'exhibit_category'     => ['nullable', 'string', 'max:255'],
            'valuation'            => ['nullable', 'string', 'max:255'],
            'operation_code'       => ['nullable', 'string', 'max:255'],
            'operation_code_name'  => ['nullable', 'string', 'max:255'],
            'operation_type'       => ['nullable', 'string', 'max:255'],
            'search_warrant_document' => $request->hasFile('search_warrant_document')
                ? ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx']
                : ['nullable', 'string', 'max:500'],
            'suspect_docket_mode'  => ['nullable', 'string', 'max:50'],
            'raid_location'        => ['nullable', 'string', 'max:255'],
            'offense_description'  => ['nullable', 'string'],
            'arrest_date'          => ['nullable'],
            'arrest_time'          => ['nullable', 'string', 'max:50'],
            'has_seizures'         => ['nullable'],
            'seizure_name'         => ['nullable', 'string', 'max:255'],
            'seizure_name_drug'    => ['nullable', 'string', 'max:255'],
            'seizure_name_currency'=> ['nullable', 'string', 'max:255'],
            'seizure_name_ammo'    => ['nullable', 'string', 'max:255'],
            'seizure_name_device'  => ['nullable', 'string', 'max:255'],
            'dev_model'            => ['nullable', 'string', 'max:255'],
            'dev_serial_no'        => ['nullable', 'string', 'max:255'],
            'dev_imei1'            => ['nullable', 'string', 'max:255'],
            'dev_imei2'            => ['nullable', 'string', 'max:255'],
            'dev_mac_address'      => ['nullable', 'string', 'max:255'],
            'dev_storage'          => ['nullable', 'string', 'max:255'],
            'dev_colour'           => ['nullable', 'string', 'max:255'],
            'dev_marks'            => ['nullable', 'string'],
            'amount'               => ['nullable', 'string', 'max:255'],
            'seizure_quantity'     => ['nullable', 'string', 'max:100'],
            'seizure_packaging'    => ['nullable', 'string', 'max:255'],
            'seizure_weight'       => ['nullable', 'string', 'max:100'],
            'seizure_unit'         => ['nullable', 'string', 'max:100'],
            'seizure_value'        => ['nullable', 'string', 'max:255'],
            'seizure_concealment'  => ['nullable', 'string', 'max:255'],
            'seizure_date'         => ['nullable'],
            'seizure_time'         => ['nullable', 'string', 'max:50'],
            'seizure_location'     => ['nullable', 'string', 'max:255'],
            'seizure_officer'      => ['nullable', 'string'],
            'investigating_officer'=> ['nullable', 'string', 'max:255'],
            'source_requesting_unit'=> ['nullable', 'string', 'max:255'],
            'seizure_reason'       => ['nullable', 'string'],
            'source_of_exhibits'   => ['nullable', 'string'],
            'exhibit_remark'       => ['nullable', 'string'],
            'lab_remark'           => ['nullable', 'string'],
            'lab_exhibit_no'       => ['nullable', 'string', 'max:255'],
            'lab_exhibit_number'   => ['nullable', 'string', 'max:255'],
            'lab_exhibit_nos'      => ['nullable', 'array'],
            'exhibit_numbers'      => ['nullable', 'array'],
            'confirmatory_remark'  => ['nullable', 'string'],
            'exhibit_type'         => ['nullable', 'string', 'max:255'],
            'drug_classification'  => ['nullable', 'string', 'max:50'],
            'contents'             => ['nullable', 'string'],
            'description_of_exhibits' => ['nullable', 'string'],
            'other_exhibit_description' => ['nullable', 'string'],
            'veh_make'             => ['nullable', 'string', 'max:255'],
            'veh_model'            => ['nullable', 'string', 'max:255'],
            'veh_type'             => ['nullable', 'string', 'max:255'],
            'veh_year'             => ['nullable', 'string', 'max:50'],
            'veh_colour'           => ['nullable', 'string', 'max:100'],
            'veh_reg_no'           => ['nullable', 'string', 'max:100'],
            'veh_vin'              => ['nullable', 'string', 'max:100'],
            'veh_engine_no'        => ['nullable', 'string', 'max:100'],
            'veh_country_reg'      => ['nullable', 'string', 'max:100'],
            'veh_owner'            => ['nullable', 'string', 'max:255'],
            'veh_driver'           => ['nullable', 'string', 'max:255'],
            'veh_owner_contact'    => ['nullable', 'string', 'max:100'],
            'veh_docs_available'   => ['nullable', 'string', 'max:50'],
            'veh_doc_ref'          => ['nullable', 'string', 'max:255'],
            'veh_condition'        => ['nullable', 'string', 'max:100'],
            'veh_fuel'             => ['nullable', 'string', 'max:100'],
            'veh_keys_recv'        => ['nullable', 'string', 'max:50'],
            'veh_key_count'        => ['nullable'],
            'veh_damage'           => ['nullable', 'string'],
            'veh_mods'             => ['nullable', 'string'],
            'veh_features'         => ['nullable', 'string'],
            'wpn_model'            => ['nullable', 'string', 'max:255'],
            'wpn_serial_no'        => ['nullable', 'string', 'max:255'],
            'wpn_calibre'          => ['nullable', 'string', 'max:255'],
            'wpn_colour'           => ['nullable', 'string', 'max:255'],
            'wpn_size'             => ['nullable', 'string', 'max:255'],
            'wpn_marks'            => ['nullable', 'string'],
            'wpn_location_found'   => ['nullable', 'string', 'max:255'],
            'wpn_loaded_status'    => ['nullable', 'string', 'max:100'],
            'wpn_condition'        => ['nullable', 'string', 'max:100'],
            'evidence_photos'      => ['nullable'],
            'mugshot_front'        => ['nullable', 'string'],
            'mugshot_left'         => ['nullable', 'string'],
            'mugshot_right'        => ['nullable', 'string'],
            'docket_data'          => ['nullable'],
            'priority'             => ['nullable', 'string', 'max:50'],
            'status'               => ['nullable', 'string', 'max:50'],
            'location'             => ['nullable', 'string', 'max:255'],
            'exhibits_summary'     => ['nullable', 'string'],
            'presumptive_result'                => ['nullable', 'string'],
            'presumptive_presence'              => ['nullable'],
            'presumptive_percentage'            => ['nullable'],
            'presumptive_test_result'           => ['nullable', 'string'],
            'presumptive_reaction_result_input' => ['nullable', 'string'],
            'confirmatory_test_result'          => ['nullable', 'string'],
            'sample_weight_description'         => ['nullable', 'string'],
            'sample_weight_units'               => ['nullable', 'string'],
            'sample_weight_gross'               => ['nullable', 'string'],
            'sample_weight_taken'               => ['nullable', 'string'],
            'sample_weight_reserved'            => ['nullable', 'string'],
        ], [
            'suspect_name.required' => 'Suspect Full Legal Name is required to save a case docket.',
        ])->validate();

        $user = Auth::user();

        // Extract suspects from docket_data
        $inputDocket = $this->decodeDocket($request->input('docket_data'));

        $normalizePhonesHelper = function ($suspectData, $fallbackPhone = null, $fallbackAddPhones = [], $fallbackAddLabels = [], $fallbackPhoneLabel = 'Primary') {
            $primaryPhone = trim((string)($suspectData['phone'] ?? ($fallbackPhone ?? '')));
            $primaryLabel = trim((string)($suspectData['phone_label'] ?? ($fallbackPhoneLabel ?? 'Primary'))) ?: 'Primary';
            $structured = $suspectData['phones'] ?? null;
            if (is_string($structured)) {
                $structured = json_decode($structured, true);
            }
            $list = [];
            if (!empty($structured) && is_array($structured)) {
                $hasPrimary = false;
                foreach ($structured as $item) {
                    if (is_array($item)) {
                        $num = trim((string)($item['number'] ?? ($item['phone'] ?? '')));
                        if ($num !== '') {
                            $lbl = trim((string)($item['label'] ?? 'Alternate')) ?: 'Alternate';
                            if ($num === $primaryPhone) $hasPrimary = true;
                            $list[] = ['label' => $lbl, 'number' => $num];
                        }
                    } elseif (is_string($item) && trim($item) !== '') {
                        $trimNum = trim($item);
                        if ($trimNum === $primaryPhone) $hasPrimary = true;
                        $list[] = ['label' => 'Alternate', 'number' => $trimNum];
                    }
                }
                if (!$hasPrimary && !empty($primaryPhone)) {
                    array_unshift($list, ['label' => $primaryLabel, 'number' => $primaryPhone]);
                }
                if (!empty($list)) return $list;
            }

            if (!empty($primaryPhone)) {
                $list[] = ['label' => $primaryLabel, 'number' => $primaryPhone];
            }
            $rawAdd = $suspectData['additional_phones'] ?? $fallbackAddPhones;
            $rawLbl = $suspectData['additional_phone_labels'] ?? $fallbackAddLabels;
            if (is_array($rawAdd)) {
                foreach ($rawAdd as $idx => $num) {
                    if (is_array($num)) {
                        $n = trim((string)($num['number'] ?? ($num['phone'] ?? '')));
                        if ($n !== '') {
                            $list[] = ['label' => trim((string)($num['label'] ?? 'Alternate')) ?: 'Alternate', 'number' => $n];
                        }
                    } elseif (is_string($num) && trim($num) !== '') {
                        $lbl = (is_array($rawLbl) && isset($rawLbl[$idx])) ? trim((string)$rawLbl[$idx]) : 'Alternate';
                        $list[] = ['label' => $lbl ?: 'Alternate', 'number' => trim($num)];
                    }
                }
            }
            return !empty($list) ? $list : null;
        };

        $rawSeizuresInput = is_array($request->input('seizures')) ? $request->input('seizures') : ($inputDocket['seizures'] ?? []);
        if (!is_array($rawSeizuresInput)) {
            $rawSeizuresInput = [];
        }

        if ($noSuspect) {
            $hasExplicitTopSeizure = ForensicCase::hasTopLevelExhibitData(array_merge(
                $validated,
                ['commodity_name' => $request->input('commodity_name')]
            ));

            if (count($rawSeizuresInput) === 0 && !$hasExplicitTopSeizure) {
                return [
                    'success' => false,
                    'message' => 'Please record at least one seized exhibit or commodity in Section 4 when logging a case without a suspect.',
                    'errors'  => [
                        'seizures' => ['At least one seized commodity in Section 4 is required when Sections 1 to 3 (Suspect Details) are skipped.'],
                    ],
                    'code'    => 422,
                ];
            }

            $firstSeizure = is_array($rawSeizuresInput[0] ?? null) ? $rawSeizuresInput[0] : [];
            $firstCommodity = ForensicCase::firstNonEmptyString([
                $firstSeizure['commodity_name'] ?? null,
                $firstSeizure['seizure_name'] ?? null,
                $firstSeizure['seizure_name_drug'] ?? null,
                $firstSeizure['seizure_name_currency'] ?? null,
                $firstSeizure['seizure_name_weapon'] ?? null,
                $firstSeizure['seizure_name_ammo'] ?? null,
                $firstSeizure['seizure_name_device'] ?? null,
                $firstSeizure['dev_model'] ?? null,
                $firstSeizure['exhibit_category'] ?? null,
                $request->input('commodity_name'),
                $validated['seizure_name'] ?? null,
                $validated['seizure_name_drug'] ?? null,
                $validated['seizure_name_device'] ?? null,
                $validated['dev_model'] ?? null,
                $validated['exhibit_category'] ?? null,
            ], 'Contraband');

            $rawTitle = trim((string) ($validated['title'] ?? ''));
            if ($rawTitle === '' || preg_match('/^republic\s+vs\.?$/i', $rawTitle)) {
                $validated['title'] = "Seized Exhibit: {$firstCommodity} (Awaiting Suspect)";
            }

            $validated['suspect_name'] = 'Unassigned (No Suspect)';
            $validated['suspect_docket_mode'] = 'SINGLE';
            $validated['associated_person'] = 'Unassigned (No Suspect)';
            $validated['has_seizures'] = true;

            $sanitizedRaid = ForensicCase::resolveRaidLocationForDisplay($validated['raid_location'] ?? null, true);
            $validated['raid_location'] = $sanitizedRaid === 'N/A' ? null : $sanitizedRaid;
            if (!empty($validated['location'])) {
                $sanitizedLoc = ForensicCase::resolveRaidLocationForDisplay($validated['location'], true);
                $validated['location'] = $sanitizedLoc === 'N/A' ? null : $sanitizedLoc;
            }

            $allSuspects = [[
                'suspect_number' => 1,
                'suspect_name'   => 'Unassigned (No Suspect)',
            ]];
        } else {
            $allSuspects = (isset($inputDocket['suspects']) && is_array($inputDocket['suspects']))
                ? array_values(array_filter($inputDocket['suspects'], function($s) {
                    $n = trim($s['suspect_name'] ?? '');
                    return !empty($n) && $n !== 'Unnamed Suspect' && $n !== 'Unknown';
                }))
                : [];
        }

        if (empty($allSuspects)) {
            $suspectName = trim($validated['suspect_name'] ?? '');
            if (empty($suspectName) || $suspectName === 'Unnamed Suspect' || $suspectName === 'Unknown') {
                return [
                    'success' => false,
                    'message' => 'The suspect name field is required. You cannot save a case docket without entering suspect details.',
                    'errors'  => [
                        'suspect_name' => ['Suspect Full Legal Name is required to create a docket case.']
                    ],
                    'code'    => 422,
                ];
            }
            $allSuspects = [[
                'suspect_number'   => 1,
                'suspect_name'     => $suspectName,
                'suspect_alias'    => $validated['suspect_alias'] ?? null,
                'ghana_card'       => $validated['ghana_card'] ?? null,
                'gender'           => $validated['gender'] ?? null,
                'dob'              => $validated['dob'] ?? null,
                'age'              => $validated['age'] ?? null,
                'height'           => $validated['height'] ?? null,
                'eye_color'        => $validated['eye_color'] ?? null,
                'nationality'      => $validated['nationality'] ?? null,
                'pob'              => $validated['pob'] ?? null,
                'phone'            => $validated['phone'] ?? null,
                'phones'           => $normalizePhonesHelper($request->all(), $validated['phone'] ?? null, $request->input('additional_phones', []), $request->input('additional_phone_labels', []), $request->input('phone_label') ?? ($request->input('phone_labels.0') ?? 'Primary')),
                'additional_phones'=> $request->input('additional_phones', []),
                'additional_phone_labels'=> $request->input('additional_phone_labels', []),
                'address'          => $validated['address'] ?? null,
                'marital_status'   => $validated['marital_status'] ?? null,
                'has_children'     => !empty($validated['has_children']),
                'children_count'   => $validated['children_count'] ?? null,
                'spouse_name'      => $validated['spouse_name'] ?? null,
                'spouse_phone'     => $validated['spouse_phone'] ?? null,
                'spouse_info'      => $validated['spouse_info'] ?? null,
                'partner_name'     => $validated['partner_name'] ?? null,
                'partner_phone'    => $validated['partner_phone'] ?? null,
                'partner_info'     => $validated['partner_info'] ?? null,
                'holding_facility' => $validated['holding_facility'] ?? null,
                'raid_location'    => $validated['raid_location'] ?? null,
                'offense'          => $validated['offense_description'] ?? null,
                'date_of_arrest'   => $validated['arrest_date'] ?? null,
                'time_of_arrest'   => $validated['arrest_time'] ?? null,
            ]];
        }

        foreach ($allSuspects as $sIdx => &$sp) {
            $sp['phones'] = $normalizePhonesHelper(
                $sp,
                $sIdx === 0 ? ($validated['phone'] ?? $request->input('phone')) : null,
                $sIdx === 0 ? $request->input('additional_phones', []) : [],
                $sIdx === 0 ? $request->input('additional_phone_labels', []) : [],
                $sIdx === 0 ? ($request->input('phone_label') ?? ($request->input('phone_labels.0') ?? 'Primary')) : 'Primary'
            );
            if ($sIdx === 0 && empty($sp['phone']) && !empty($validated['phone'])) {
                $sp['phone'] = $validated['phone'];
            }
            if ($sIdx === 0) {
                if (empty($sp['mugshot_front']) && !empty($validated['mugshot_front'])) {
                    $sp['mugshot_front'] = $validated['mugshot_front'];
                }
                if (empty($sp['mugshot_left']) && !empty($validated['mugshot_left'])) {
                    $sp['mugshot_left'] = $validated['mugshot_left'];
                }
                if (empty($sp['mugshot_right']) && !empty($validated['mugshot_right'])) {
                    $sp['mugshot_right'] = $validated['mugshot_right'];
                }
            }
        }
        unset($sp);

        $allNames = array_filter(array_column($allSuspects, 'suspect_name'));
        if (empty($allNames)) {
            return [
                'success' => false,
                'message' => 'The suspect name field is required. You cannot save a case docket without entering suspect details.',
                'errors'  => [
                    'suspect_name' => ['Suspect Full Legal Name is required to create a docket case.']
                ],
                'code'    => 422,
            ];
        }
        $jointSuspectNameLabel = count($allNames) > 1
            ? implode(', ', $allNames)
            : $allNames[0];

        // Double-Click & Rapid-Fire Prevention (Backend Cache Lock)
        $userId     = $user ? $user->id : ($request->ip() ?: 'guest');
        $sigSuspect = strtolower(trim((string)($validated['suspect_name'] ?? $request->input('suspect_name', ''))));
        $sigTitle   = strtolower(trim((string)($validated['title'] ?? $request->input('title', ''))));
        $sigDate    = strtolower(trim((string)($validated['arrest_date'] ?? ($request->input('arrest_date') ?? ($request->input('incident_date', ''))))));
        $sigSeizure = $noSuspect ? md5(json_encode($rawSeizuresInput)) : '';

        $requestHash = $noSuspect
            ? md5("{$sigSuspect}|{$sigTitle}|{$sigDate}|{$sigSeizure}")
            : md5("{$sigSuspect}|{$sigTitle}|{$sigDate}");
        $lockKey     = "case_creation_lock_{$userId}_{$requestHash}";

        if (Cache::has($lockKey)) {
            return [
                'success' => false,
                'message' => 'A submission for this case is already being processed. Please wait a moment.',
                'error'   => 'Duplicate request detected. Case submission is already in progress.',
                'code'    => 429,
            ];
        }
        Cache::put($lockKey, true, now()->addSeconds(10));

        // Smart Duplicate Case Detection (skip when logging seizure without a suspect)
        if (!$noSuspect && !$request->boolean('confirm_duplicate') && !$request->boolean('ignore_duplicate')) {
            $detector = new CaseDuplicateDetector();
            $dupAnalysis = $detector->checkCaseDuplicate($validated, $allSuspects);
            if (!empty($dupAnalysis['duplicate_detected'])) {
                Cache::forget($lockKey);
                $dupAnalysis['code'] = 409;
                return $dupAnalysis;
            }
        }

        $prefix = 'NCC';
        $caseNumberService = app(CaseNumberService::class);

        $unit = Unit::where('name', $validated['unit_name'] ?? null)->first();

        $exhibitsSummary = $validated['exhibits_summary']
            ?? (!empty($validated['seizure_name'])
                ? implode(' ', array_filter([
                    !empty($validated['seizure_quantity']) ? "Qty: {$validated['seizure_quantity']}" : null,
                    !empty($validated['seizure_weight'])   ? "Wt: {$validated['seizure_weight']} " . ($validated['seizure_unit'] ?? '') : ($validated['seizure_unit'] ?? null),
                    $validated['seizure_name']
                  ]))
                : 'Logged Exhibits & Dossier');

        $docketData = $this->decodeDocket($request->input('docket_data'));
        $docketData['no_suspect'] = $noSuspect;
        $docketData['suspects'] = $noSuspect ? [] : $allSuspects;
        $docketData['suspects_count'] = $noSuspect ? 0 : count($allSuspects);
        $docketData['full_name'] = $jointSuspectNameLabel;
        $docketData['phones'] = $noSuspect ? null : ($allSuspects[0]['phones'] ?? ($docketData['phones'] ?? null));

        $descOfExhibits = $validated['description_of_exhibits']
            ?? ($request->input('description_of_exhibits')
            ?? ($docketData['description_of_exhibits'] ?? null));

        if (empty($docketData['seizures']) && $request->has('seizures') && is_array($request->input('seizures'))) {
            $docketData['seizures'] = $request->input('seizures');
        }

        $inputLinkedExhibitIds = $request->input('linked_exhibit_ids', []);
        $linkSuspectMode = $request->boolean('link_suspect_to_locked_exhibit');
        $linkedExhibitsCollection = collect();
        if (!empty($inputLinkedExhibitIds)) {
            $parsedLinkedIds = is_array($inputLinkedExhibitIds)
                ? $inputLinkedExhibitIds
                : array_filter(explode(',', (string) $inputLinkedExhibitIds));
            $docketData['linked_exhibit_ids'] = array_values(array_unique(array_filter(array_map('intval', $parsedLinkedIds))));

            if (!empty($docketData['linked_exhibit_ids'])) {
                $linkedExhibitsCollection = Exhibit::with(['singleSuspectCase', 'multipleSuspectCase', 'labResult'])
                    ->whereIn('id', $docketData['linked_exhibit_ids'])
                    ->get();

                $lockedExhibit = $linkedExhibitsCollection->first(fn (Exhibit $ex) => $ex->isLockedByReport());
                if ($lockedExhibit) {
                    if ($linkSuspectMode && $noSuspect) {
                        Cache::forget($lockKey);
                        $msg = "Uncheck 'No Suspect' and enter the suspect's biodata before linking a suspect to a locked exhibit.";
                        return [
                            'success' => false,
                            'message' => $msg,
                            'error'   => $msg,
                            'code'    => 422,
                        ];
                    }

                    if (!$linkSuspectMode) {
                        Cache::forget($lockKey);
                        $lockedLabel = $lockedExhibit->property_id ?: ($lockedExhibit->exhibit_number ?: ('Exhibit #' . $lockedExhibit->id));
                        $attachedReport = $lockedExhibit->resolveAttachedUnitReport();
                        $reportLabel = ($attachedReport['report_type'] ?? '') === 'dpf_status_log'
                            ? 'Extraction & Investigation Status Log Report PDF'
                            : 'Preliminary Exhibit Analysis Report PDF';
                        $msg = "{$lockedLabel} already has an attached {$reportLabel} and cannot be selected again.";
                        return [
                            'success' => false,
                            'message' => $msg,
                            'error'   => $msg,
                            'code'    => 422,
                        ];
                    }
                }
            }
        } elseif ($linkSuspectMode) {
            if ($noSuspect) {
                Cache::forget($lockKey);
                $msg = "Uncheck 'No Suspect' and enter the suspect's biodata before linking a suspect to a locked exhibit.";
                return [
                    'success' => false,
                    'message' => $msg,
                    'error'   => $msg,
                    'code'    => 422,
                ];
            }
        }

        if ($descOfExhibits !== null) {
            $docketData['description_of_exhibits'] = $descOfExhibits;
        }

        $labExhibitNosInput = $request->input('lab_exhibit_nos', []);
        $legacyExhibitNosInput = $request->input('exhibit_numbers', []);
        if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
            foreach ($docketData['seizures'] as $sIdx => $sz) {
                if ($linkedExhibitsCollection->isNotEmpty() && empty($docketData['seizures'][$sIdx]['linked_exhibit_id'])) {
                    $szIdVal = !empty($sz['id']) ? (int) $sz['id'] : 0;
                    $szPropVal = trim((string) ($sz['property_id'] ?? ''));
                    $szNameVal = strtolower(trim((string) ($sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? ($sz['commodity_name'] ?? '')))));

                    $matchedLinkedEx = $linkedExhibitsCollection->first(function (Exhibit $lex) use ($szIdVal, $szPropVal) {
                        if ($szIdVal > 0 && (int) $lex->id === $szIdVal) {
                            return true;
                        }
                        if ($szPropVal !== '' && $lex->property_id && trim((string) $lex->property_id) === $szPropVal) {
                            return true;
                        }
                        return false;
                    });
                    if (!$matchedLinkedEx && $linkedExhibitsCollection->count() === 1 && count($docketData['seizures']) === 1) {
                        $onlyEx = $linkedExhibitsCollection->first();
                        $onlyName = strtolower(trim((string) ($onlyEx->commodity_name ?? '')));
                        if ($szNameVal === '' || $onlyName === '' || $szNameVal === $onlyName) {
                            $matchedLinkedEx = $onlyEx;
                        }
                    }
                    if ($matchedLinkedEx) {
                        $docketData['seizures'][$sIdx]['id'] = $matchedLinkedEx->id;
                        $docketData['seizures'][$sIdx]['linked_exhibit_id'] = $matchedLinkedEx->id;
                        if (empty($docketData['seizures'][$sIdx]['property_id']) && $matchedLinkedEx->property_id) {
                            $docketData['seizures'][$sIdx]['property_id'] = $matchedLinkedEx->property_id;
                        }
                    }
                }
                if ($descOfExhibits !== null && !isset($sz['description_of_exhibits'])) {
                    $docketData['seizures'][$sIdx]['description_of_exhibits'] = $descOfExhibits;
                }
                if (empty($docketData['seizures'][$sIdx]['lab_exhibit_no'])) {
                    $cardLabExNo = is_array($labExhibitNosInput) && isset($labExhibitNosInput[$sIdx])
                        ? trim((string) $labExhibitNosInput[$sIdx])
                        : (is_array($legacyExhibitNosInput) && isset($legacyExhibitNosInput[$sIdx]) ? trim((string) $legacyExhibitNosInput[$sIdx]) : '');
                    if ($cardLabExNo !== '') {
                        $docketData['seizures'][$sIdx]['lab_exhibit_no'] = $cardLabExNo;
                    } elseif ($sIdx === 0) {
                        $topLabExNo = trim((string) ($request->input('lab_exhibit_no') ?? ($request->input('lab_exhibit_number') ?? '')));
                        if ($topLabExNo !== '' && !preg_match('/^EXH-\d{4}-\d{3}-NFC$/i', $topLabExNo)) {
                            $docketData['seizures'][$sIdx]['lab_exhibit_no'] = $topLabExNo;
                        }
                    }
                }
                $szCat = strtolower(trim((string)($sz['exhibit_category'] ?? ($sz['category'] ?? ''))));
                $isMoney = in_array($szCat, ['money', 'currency', 'cash']);
                if ($isMoney && empty($docketData['seizures'][$sIdx]['amount'])) {
                    $amountCandidate = $sz['amount'] ?? ($sz['seizure_quantity'] ?? ($sz['seizure_value'] ?? null));
                    if ($amountCandidate !== null && $amountCandidate !== '' && $amountCandidate !== 'N/A') {
                        $docketData['seizures'][$sIdx]['amount'] = $amountCandidate;
                    }
                }
                $ownership = $sz['suspect_ownership'] ?? null;
                $assocPerson = $sz['associated_person'] ?? null;
                if ($noSuspect) {
                    $docketData['seizures'][$sIdx]['associated_person'] = 'Unassigned (No Suspect)';
                    $docketData['seizures'][$sIdx]['suspect_ownership'] = 'Unassigned (No Suspect)';
                } elseif ($ownership === 'All Suspects' || $assocPerson === 'All Suspects' || str_contains(strtolower((string)$ownership), 'all suspect') || str_contains(strtolower((string)$assocPerson), 'all suspect')) {
                    $docketData['seizures'][$sIdx]['associated_person'] = count($allNames) > 1 ? $jointSuspectNameLabel : 'All Suspects';
                    $docketData['seizures'][$sIdx]['suspect_ownership'] = 'All Suspects';
                } elseif (empty($assocPerson) || strtolower(trim($assocPerson)) === 'primary suspect') {
                    $docketData['seizures'][$sIdx]['associated_person'] = count($allNames) > 1 ? $jointSuspectNameLabel : $allNames[0];
                }
            }
        }

        $topAmount = $validated['amount'] ?? ($request->input('amount') ?? null);
        if (empty($topAmount) && !empty($docketData['seizures'][0]['amount'])) {
            $topAmount = $docketData['seizures'][0]['amount'];
        }
        if (empty($topAmount) && !empty($validated['exhibit_category']) && in_array(strtolower(trim($validated['exhibit_category'])), ['money', 'currency', 'cash'])) {
            $topAmount = $validated['seizure_quantity'] ?? ($validated['seizure_value'] ?? null);
        }

        $resolvedOpCode = $validated['operation_code'] ?? ($validated['operation_type'] ?? null);
        $resolvedOpCodeName = $validated['operation_code_name'] ?? ($validated['operation_type'] ?? null);
        $effectiveOpType = trim((string) ($resolvedOpCodeName ?: $resolvedOpCode));
        $isSearchWarrantOp = strcasecmp($effectiveOpType, 'Search Warrant') === 0;
        $searchWarrantDocumentPath = null;
        if ($isSearchWarrantOp) {
            if ($request->hasFile('search_warrant_document')) {
                $searchWarrantDocumentPath = app(CaseDocumentService::class)->storeSearchWarrantFile(
                    $request->file('search_warrant_document')
                );
            } elseif (!empty($validated['search_warrant_document']) && is_string($validated['search_warrant_document'])) {
                $searchWarrantDocumentPath = trim($validated['search_warrant_document']);
            }
        }

        $sharedFields = [
            'title'                    => $validated['title'],
            'unit_name'                => $validated['unit_name'] ?? ($user ? $user->unit : 'Forensic Directorate'),
            'unit_id'                  => $unit ? $unit->id : null,
            'user_id'                  => ($user instanceof \App\Models\User) ? $user->id : null,
            'arresting_officer'        => $validated['arresting_officer'] ?? ($validated['submitting_officer'] ?? ($user ? $user->fullname : 'Field Arresting Officer')),
            'submitting_officer'       => $validated['arresting_officer'] ?? ($validated['submitting_officer'] ?? ($user ? $user->fullname : 'Field Arresting Officer')),
            'suspect_docket_mode'      => $validated['suspect_docket_mode'] ?? null,
            'operation_code'           => $resolvedOpCode,
            'operation_code_name'      => $resolvedOpCodeName,
            'search_warrant_document'  => $searchWarrantDocumentPath,
            'has_seizures'             => !empty($validated['has_seizures']),
            'exhibit_category'         => $validated['exhibit_category'] ?? null,
            'associated_person'        => $validated['associated_person'] ?? ($docketData['seizures'][0]['associated_person'] ?? $jointSuspectNameLabel),
            'valuation'                => $validated['valuation'] ?? null,
            'seizure_name'             => $validated['seizure_name'] ?? null,
            'seizure_name_drug'        => $validated['seizure_name_drug'] ?? null,
            'seizure_name_currency'    => $validated['seizure_name_currency'] ?? null,
            'seizure_name_ammo'        => $validated['seizure_name_ammo'] ?? null,
            'seizure_name_device'      => $validated['seizure_name_device'] ?? null,
            'dev_model'                => $validated['dev_model'] ?? null,
            'dev_serial_no'            => $validated['dev_serial_no'] ?? null,
            'dev_imei1'                => $validated['dev_imei1'] ?? null,
            'dev_imei2'                => $validated['dev_imei2'] ?? null,
            'dev_mac_address'          => $validated['dev_mac_address'] ?? null,
            'dev_storage'              => $validated['dev_storage'] ?? null,
            'dev_colour'               => $validated['dev_colour'] ?? null,
            'dev_marks'                => $validated['dev_marks'] ?? null,
            'amount'                   => $topAmount,
            'seizure_quantity'         => $validated['seizure_quantity'] ?? null,
            'seizure_packaging'        => $validated['seizure_packaging'] ?? null,
            'seizure_weight'           => $validated['seizure_weight'] ?? null,
            'seizure_unit'             => $validated['seizure_unit'] ?? null,
            'seizure_value'            => $validated['seizure_value'] ?? null,
            'seizure_concealment'      => $validated['seizure_concealment'] ?? null,
            'seizure_date'             => $validated['seizure_date'] ?? null,
            'seizure_time'             => $validated['seizure_time'] ?? null,
            'seizure_location'         => $validated['seizure_location'] ?? ($request->input('seizure_location') ?? ($docketData['seizures'][0]['seizure_location'] ?? null)),
            'seizure_officer'          => $validated['seizure_officer'] ?? ($request->input('seizure_officer') ?? ($docketData['seizures'][0]['seizure_officer'] ?? null)),
            'investigating_officer'    => $validated['investigating_officer'] ?? ($request->input('investigating_officer') ?? ($docketData['seizures'][0]['investigating_officer'] ?? null)),
            'source_requesting_unit'   => $validated['source_requesting_unit'] ?? ($request->input('source_requesting_unit') ?? ($docketData['seizures'][0]['source_requesting_unit'] ?? null)),
            'seizure_reason'           => $validated['seizure_reason'] ?? ($request->input('seizure_reason') ?? ($docketData['seizures'][0]['seizure_reason'] ?? null)),
            'source_of_exhibits'       => $validated['source_of_exhibits'] ?? ($request->input('source_of_exhibits') ?? ($docketData['seizures'][0]['source_of_exhibits'] ?? ($docketData['seizures'][0]['source'] ?? null))),
            'exhibit_remark'           => $validated['exhibit_remark'] ?? ($request->input('exhibit_remark') ?? null),
            'exhibit_type'             => $validated['exhibit_type'] ?? null,
            'drug_classification'      => $validated['drug_classification'] ?? null,
            'contents'                 => $validated['contents'] ?? ($request->input('contents') ?? null),
            'description_of_exhibits'  => $descOfExhibits,
            'veh_make'                 => $validated['veh_make'] ?? null,
            'veh_model'                => $validated['veh_model'] ?? null,
            'veh_type'                 => $validated['veh_type'] ?? null,
            'veh_year'                 => $validated['veh_year'] ?? null,
            'veh_colour'               => $validated['veh_colour'] ?? null,
            'veh_reg_no'               => $validated['veh_reg_no'] ?? null,
            'veh_vin'                  => $validated['veh_vin'] ?? null,
            'veh_engine_no'            => $validated['veh_engine_no'] ?? null,
            'veh_country_reg'          => $validated['veh_country_reg'] ?? null,
            'veh_owner'                => $validated['veh_owner'] ?? null,
            'veh_driver'               => $validated['veh_driver'] ?? null,
            'veh_owner_contact'        => $validated['veh_owner_contact'] ?? null,
            'veh_docs_available'       => $validated['veh_docs_available'] ?? null,
            'veh_doc_ref'              => $validated['veh_doc_ref'] ?? null,
            'veh_condition'            => $validated['veh_condition'] ?? null,
            'veh_fuel'                 => $validated['veh_fuel'] ?? null,
            'veh_keys_recv'            => $validated['veh_keys_recv'] ?? null,
            'veh_key_count'            => isset($validated['veh_key_count']) && $validated['veh_key_count'] !== '' ? (int)$validated['veh_key_count'] : null,
            'veh_damage'               => $validated['veh_damage'] ?? null,
            'veh_mods'                 => $validated['veh_mods'] ?? null,
            'veh_features'             => $validated['veh_features'] ?? null,
            'wpn_model'                => $validated['wpn_model'] ?? null,
            'wpn_serial_no'            => $validated['wpn_serial_no'] ?? null,
            'wpn_calibre'              => $validated['wpn_calibre'] ?? null,
            'wpn_colour'               => $validated['wpn_colour'] ?? null,
            'wpn_size'                 => $validated['wpn_size'] ?? null,
            'wpn_marks'                => $validated['wpn_marks'] ?? null,
            'wpn_location_found'       => $validated['wpn_location_found'] ?? null,
            'wpn_loaded_status'        => $validated['wpn_loaded_status'] ?? null,
            'wpn_condition'            => $validated['wpn_condition'] ?? null,
            'evidence_photos'          => $validated['evidence_photos'] ?? null,
            'docket_data'              => $docketData,
            'priority'                 => $validated['priority'] ?? 'HIGH',
            'status'                   => $validated['status'] ?? CaseStatus::UNDER_MULTI_UNIT_PROCESSING->value,
            'location'                 => $validated['location'] ?? ($validated['raid_location'] ?? null),
            'description'              => $validated['description'] ?? ($validated['offense_description'] ?? null),
            'exhibits_summary'         => $exhibitsSummary,
            'presumptive_test_result'  => $request->input('presumptive_result')
                ?? ($validated['presumptive_result'] ?? ($validated['presumptive_test_result'] ?? ($request->input('presumptive_reaction_result_input') ?? ($request->input('docket_data.lab_examination.presumptive_result') ?? null)))),
            'laboratory_number'        => $request->input('laboratory_number') ?? ($request->input('lab_number') ?? null),
            'confirmatory_test_result' => $validated['confirmatory_test_result'] ?? ($request->input('confirmatory_test_result') ?? null),
            'sample_weight_description'=> $request->input('sample_weight_description') ?? null,
            'sample_weight_gross'      => $request->input('sample_weight_gross') ?? null,
            'sample_weight_taken'      => $request->input('sample_weight_taken') ?? null,
            'sample_weight_reserved'   => $request->input('sample_weight_reserved') ?? null,
            'confirmatory_substance_identification' => $request->input('confirmatory_substance_identification') ?? null,
            'detected_substances'      => $request->input('detected_substances')
                ?? ($request->input('docket_data.lab_examination.detected_substances')
                ?? ($request->input('lab_examination.detected_substances') ?? null)),
            'confirmatory_test_methods'=> $request->input('confirmatory_test_methods') ?? null,
            'confirmatory_specifications' => $request->input('confirmatory_specifications') ?? null,
            'confirmatory_remark'        => $request->input('confirmatory_remark') ?? ($request->input('remark') ?? null),
            'lab_remark'                 => $request->input('confirmatory_remark') ?? ($request->input('remark') ?? null),
            'lab_exhibit_no'             => $request->input('lab_exhibit_no') ?? ($request->input('lab_exhibit_number') ?? ($docketData['seizures'][0]['lab_exhibit_no'] ?? null)),
            'purpose_of_examination'     => $request->input('purpose_of_examination') ?? null,
            'preliminary_exam_procedure' => $request->input('preliminary_exam_procedure') ?? null,
            'screening_method'           => $request->input('screening_method') ?? ($request->input('confirmatory_screening_method') ?? null),
            'disposition_of_exhibit'     => $request->input('disposition_of_exhibit') ?? ($request->input('confirmatory_disposition_of_exhibit') ?? null),
            'incident_date'            => $validated['arrest_date'] ?? now(),
        ];

        // Fix Issue 3: SchemaCache::hasColumn
        if (SchemaCache::hasColumn('cases', 'sample_weight_units')) {
            $sharedFields['sample_weight_units'] = $request->input('sample_weight_units') ?? null;
        }

        // Enforce GSA & NLR Mutual Exclusivity and Section 5 Completion Guard before creating the case
        $labWorkflowMode = strtolower(trim((string) $request->input('lab_workflow_mode', '')));
        $gsaFilesUploadedInput = $request->input('gsa_files_uploaded');
        $hasGsaFilesFlag = is_array($gsaFilesUploadedInput)
            ? count(array_filter($gsaFilesUploadedInput, fn ($v) => !empty($v) && $v !== '0' && $v !== false)) > 0
            : (filter_var($gsaFilesUploadedInput, FILTER_VALIDATE_BOOLEAN) || ((int) $gsaFilesUploadedInput > 0));

        $hasGsaFilesUploadedOuter = $request->hasFile('gsa_report_images')
            || $hasGsaFilesFlag
            || $labWorkflowMode === 'gsa'
            || !empty($request->input('gsa_lab_number'))
            || !empty($request->input('docket_data.lab_examination.gsa_lab_number'));

        $seizureDrugNameForCompare = strtolower(trim((string) ($request->input('seizure_name_drug') ?: ($request->input('seizure_name') ?: ''))));
        $submittedSubstanceId = strtolower(trim((string) $request->input('confirmatory_substance_identification', '')));
        $hasDistinctSubstanceId = $submittedSubstanceId !== '' && $submittedSubstanceId !== $seizureDrugNameForCompare;

        $detectedSubsInput = $sharedFields['detected_substances'] ?? [];
        $hasValidDetectedSubstances = is_array($detectedSubsInput) && count(array_filter($detectedSubsInput, function ($d) {
            if (is_array($d)) {
                return !empty($d['substance']) || !empty($d['name']);
            }
            return is_string($d) && trim($d) !== '';
        })) > 0;

        $hasSubmittedNonGsaLabInputs = !empty($docketData['lab_examination']['has_saved'])
            || !empty($request->input('docket_data.lab_examination.has_saved'))
            || !empty($request->input('has_saved_lab'))
            || !empty($request->input('presumptive_result'))
            || !empty($request->input('presumptive_test_result'))
            || !empty($request->input('presumptive_reaction_result_input'))
            || !empty($request->input('confirmatory_test_result'))
            || !empty($request->input('sample_weight_taken'))
            || !empty($request->input('sample_weight_reserved'))
            || !empty($request->input('confirmatory_test_methods'))
            || !empty($request->input('confirmatory_specifications'))
            || !empty($request->input('confirmatory_remark'))
            || !empty($request->input('purpose_of_examination'))
            || !empty($request->input('lab_purpose_of_examination'))
            || !empty($request->input('preliminary_exam_procedure'))
            || !empty($request->input('lab_preliminary_exam_procedure'))
            || !empty($request->input('screening_method'))
            || !empty($request->input('confirmatory_screening_method'))
            || !empty($request->input('disposition_of_exhibit'))
            || !empty($request->input('confirmatory_disposition_of_exhibit'))
            || !empty($request->input('laboratory_analytical_findings'))
            || $hasValidDetectedSubstances
            || $hasDistinctSubstanceId
            || !empty($docketData['lab_examinations_list']);

        $section5ExplicitlyNotCompleted = $hasGsaFilesUploadedOuter
            || $labWorkflowMode === 'gsa'
            || $labWorkflowMode === 'none'
            || ($request->has('section5_completed') && !filter_var($request->input('section5_completed'), FILTER_VALIDATE_BOOLEAN) && !$hasSubmittedNonGsaLabInputs);

        $hasCompletedSection5Outer = !$section5ExplicitlyNotCompleted && (
            filter_var($request->input('section5_completed'), FILTER_VALIDATE_BOOLEAN)
            || $labWorkflowMode === 'nlr'
            || $hasSubmittedNonGsaLabInputs
        );

        if (!$hasCompletedSection5Outer) {
            $sharedFields['presumptive_test_result'] = null;
            $sharedFields['laboratory_number'] = null;
            $sharedFields['confirmatory_test_result'] = null;
            $sharedFields['sample_weight_description'] = null;
            $sharedFields['sample_weight_gross'] = null;
            $sharedFields['sample_weight_taken'] = null;
            $sharedFields['sample_weight_reserved'] = null;
            $sharedFields['confirmatory_substance_identification'] = null;
            $sharedFields['detected_substances'] = null;
            $sharedFields['confirmatory_test_methods'] = null;
            $sharedFields['confirmatory_specifications'] = null;
            $sharedFields['confirmatory_remark'] = null;
            $sharedFields['lab_remark'] = null;
            if (array_key_exists('sample_weight_units', $sharedFields)) {
                $sharedFields['sample_weight_units'] = null;
            }
            if (is_array($sharedFields['docket_data'] ?? null)) {
                unset($sharedFields['docket_data']['lab_examination']);
            }
        }

        $suspectFields = function (array $suspect) use ($validated, $normalizePhonesHelper, $request): array {
            $marital = !empty($suspect['marital_status']) && $suspect['marital_status'] !== 'Unspecified'
                ? $suspect['marital_status'] : null;

            $spouseInfo = $suspect['spouse_info'] ?? null;
            if (empty($spouseInfo) && $marital === 'Married') {
                $spouseInfo = trim(($suspect['spouse_name'] ?? '') . ' (' . ($suspect['spouse_phone'] ?? '') . ')');
            } elseif (empty($spouseInfo) && $marital === 'Dating / In Relationship') {
                $spouseInfo = trim(($suspect['partner_name'] ?? '') . ' (' . ($suspect['partner_phone'] ?? '') . ')');
            }

            return [
                'suspect_name'     => $suspect['suspect_name'] ?? null,
                'suspect_alias'    => $suspect['suspect_alias'] ?? null,
                'ghana_card'       => $suspect['ghana_card'] ?? null,
                'gender'           => $suspect['gender'] ?? null,
                'dob'              => $suspect['dob'] ?? null,
                'age'              => isset($suspect['age']) && $suspect['age'] !== '' ? (int)$suspect['age'] : null,
                'height'           => $suspect['height'] ?? null,
                'eye_color'        => $suspect['eye_color'] ?? null,
                'nationality'      => $suspect['nationality'] ?? null,
                'pob'              => $suspect['pob'] ?? null,
                'phone'            => $suspect['phone'] ?? ($validated['phone'] ?? null),
                'phones'           => $suspect['phones'] ?? $normalizePhonesHelper(
                    $suspect,
                    $validated['phone'] ?? null,
                    $request->input('additional_phones', []),
                    $request->input('additional_phone_labels', []),
                    $request->input('phone_label') ?? ($request->input('phone_labels.0') ?? 'Primary')
                ),
                'address'          => $suspect['address'] ?? null,
                'marital_status'   => $marital,
                'has_children'     => !empty($suspect['has_children']),
                'children_count'   => isset($suspect['children_count']) && $suspect['children_count'] !== '' ? (int)$suspect['children_count'] : null,
                'spouse_info'      => $spouseInfo,
                'spouse_name'      => $suspect['spouse_name'] ?? null,
                'spouse_phone'     => $suspect['spouse_phone'] ?? null,
                'partner_name'     => $suspect['partner_name'] ?? null,
                'partner_phone'    => $suspect['partner_phone'] ?? null,
                'partner_info'     => $suspect['partner_info'] ?? null,
                'dependents'       => isset($suspect['children_count']) && (int)($suspect['children_count'] ?? 0) > 0
                                       ? $suspect['children_count'] . ' Kids' : 'N/A',
                'raid_location'    => $suspect['raid_location']    ?? ($validated['raid_location'] ?? null),
                'offense_description' => $suspect['offense']       ?? ($validated['offense_description'] ?? null),
                'arrest_date'      => $suspect['date_of_arrest']   ?? ($validated['arrest_date'] ?? null),
                'arrest_time'      => $suspect['time_of_arrest']   ?? ($validated['arrest_time'] ?? null),
                'holding_facility' => $suspect['holding_facility'] ?? ($validated['holding_facility'] ?? null),
            ];
        };

        $existingNoSuspectSingleCase = null;
        if ($linkSuspectMode && !$noSuspect && $linkedExhibitsCollection->isNotEmpty()) {
            foreach ($linkedExhibitsCollection as $lex) {
                $candidateSingle = $lex->singleSuspectCase;
                if ($candidateSingle && (
                    ForensicCase::isUnassignedSuspectName($candidateSingle->suspect_name)
                    || str_contains(strtolower((string) ($candidateSingle->title ?? '')), 'awaiting suspect')
                    || $lex->isLockedByReport()
                )) {
                    $existingNoSuspectSingleCase = $candidateSingle;
                    break;
                }
            }
        }

        if ($existingNoSuspectSingleCase) {
            return $this->linkSuspectToExistingExhibitCase(
                $request,
                $existingNoSuspectSingleCase,
                $linkedExhibitsCollection,
                $allSuspects,
                $suspectFields,
                $sharedFields,
                $docketData,
                $validated,
                $jointSuspectNameLabel,
                $user,
                $lockKey,
                $hasGsaFilesUploadedOuter,
                $hasCompletedSection5Outer,
                $descOfExhibits
            );
        }

        // Fix Issue 2: Wrap transaction in CaseNumberService::withUniqueRetry to handle race conditions
        try {
            $preferredRef = $request->filled('case_number') ? trim($request->input('case_number')) : null;
            $case = $caseNumberService->withUniqueRetry(function (string $generatedRef) use (
                $allSuspects, $suspectFields, $sharedFields, $validated, $user, $request,
                $hasGsaFilesUploadedOuter, $hasCompletedSection5Outer, $descOfExhibits
            ) {
                $caseRef = $generatedRef;

                return DB::transaction(function () use (
                    $allSuspects, $suspectFields, $sharedFields, $caseRef, $validated,
                    $user, $request, $hasGsaFilesUploadedOuter, $hasCompletedSection5Outer, $descOfExhibits
                ) {
                    $primarySuspect = $allSuspects[0];
                    $primaryPersonalFields = $suspectFields($primarySuspect);

                    $case = ForensicCase::create(array_merge($sharedFields, $primaryPersonalFields, [
                        'case_number'    => $caseRef,
                        'suspect_number' => 1,
                        'parent_case_id' => null,
                        'mugshot_front'  => !empty($validated['mugshot_front']) ? $validated['mugshot_front'] : ($primarySuspect['mugshot_front'] ?? null),
                        'mugshot_left'   => !empty($validated['mugshot_left']) ? $validated['mugshot_left'] : ($primarySuspect['mugshot_left'] ?? null),
                        'mugshot_right'  => !empty($validated['mugshot_right']) ? $validated['mugshot_right'] : ($primarySuspect['mugshot_right'] ?? null),
                        'hash_log'       => 'HASH-' . strtoupper(substr(md5(uniqid()), 0, 8)) . '-VERIFIED',
                    ]));

                    // Fix Issue 21: Guard AuditLogger with null-safe $user check
                    if ($user) {
                        AuditLogger::log(
                            'CASE_CREATED',
                            "New forensic case created with reference {$case->case_number} by " . $user->fullname . " ({$case->unit_name})",
                            [
                                'case_number'    => $case->case_number,
                                'auditable_type' => get_class($case->underlyingRecord ?? $case),
                                'auditable_id'   => $case->id,
                                'new_values'     => [
                                    'case_number'        => $case->case_number,
                                    'title'              => $case->title,
                                    'unit_name'          => $case->unit_name,
                                    'controller_user_id' => $case->controller_user_id,
                                ],
                                'user'           => $user,
                            ]
                        );
                    }

                    // Fix Issue 9 & 15: Add missing null guard on underlyingRecord
                    $underlying = $case->underlyingRecord;
                    if (!$underlying) {
                        Log::warning("Case {$case->case_number} has no underlyingRecord after creation — checking fallback.", [
                            'case_id' => $case->id,
                            'context' => __METHOD__,
                        ]);
                    }

                    // Sync CaseSuspect models for multi-suspect case
                    if ($underlying instanceof MultipleSuspectCase) {
                        foreach ($allSuspects as $sIdx => $spItem) {
                            $spNum = $sIdx + 1;
                            $isAssocSp = $sIdx > 0 || !empty($spItem['is_associate']) || (!empty($spItem['role']) && str_contains(strtolower($spItem['role']), 'associate'));
                            $roleVal = $spItem['role'] ?? ($spItem['association_role'] ?? ($isAssocSp ? 'Co-Suspect' : 'Primary Suspect'));
                            $assocIdVal = $spItem['associate_id'] ?? ($isAssocSp ? ('ASC-' . str_pad($sIdx, 3, '0', STR_PAD_LEFT)) : null);

                            CaseSuspect::updateOrCreate(
                                [
                                    'multiple_suspect_case_id' => $case->id,
                                    'suspect_name'             => $spItem['suspect_name'],
                                ],
                                [
                                    'suspect_number'           => $spNum,
                                    'role'                     => $roleVal,
                                    'associate_id'             => $assocIdVal,
                                    'suspect_alias'            => $spItem['suspect_alias'] ?? null,
                                    'ghana_card'               => $spItem['ghana_card'] ?? null,
                                    'gender'                   => $spItem['gender'] ?? null,
                                    'dob'                      => $spItem['dob'] ?? null,
                                    'age'                      => isset($spItem['age']) && $spItem['age'] !== '' ? (int)$spItem['age'] : null,
                                    'height'                   => $spItem['height'] ?? null,
                                    'eye_color'                => $spItem['eye_color'] ?? null,
                                    'nationality'              => $spItem['nationality'] ?? null,
                                    'pob'                      => $spItem['pob'] ?? ($spItem['place_of_birth'] ?? ($spItem['hometown'] ?? ($sIdx === 0 ? ($primaryPersonalFields['pob'] ?? null) : null))),
                                    'phone'                    => $spItem['phone'] ?? ($sIdx === 0 ? ($validated['phone'] ?? null) : null),
                                    'phones'                   => $spItem['phones'] ?? ($sIdx === 0 ? ($primaryPersonalFields['phones'] ?? null) : null),
                                    'address'                  => $spItem['address'] ?? ($spItem['residential_address'] ?? ($spItem['gps'] ?? ($sIdx === 0 ? ($primaryPersonalFields['address'] ?? null) : null))),
                                    'marital_status'           => $spItem['marital_status'] ?? ($sIdx === 0 ? ($primaryPersonalFields['marital_status'] ?? null) : null),
                                    'has_children'             => !empty($spItem['has_children']) || (!empty($primaryPersonalFields['has_children']) && $sIdx === 0),
                                    'children_count'           => isset($spItem['children_count']) && $spItem['children_count'] !== '' ? (int)$spItem['children_count'] : ($sIdx === 0 ? ($primaryPersonalFields['children_count'] ?? null) : null),
                                    'spouse_info'              => $spItem['spouse_info'] ?? ($sIdx === 0 ? ($primaryPersonalFields['spouse_info'] ?? null) : null),
                                    'spouse_name'              => $spItem['spouse_name'] ?? ($sIdx === 0 ? ($primaryPersonalFields['spouse_name'] ?? null) : null),
                                    'spouse_phone'             => $spItem['spouse_phone'] ?? ($sIdx === 0 ? ($primaryPersonalFields['spouse_phone'] ?? null) : null),
                                    'partner_name'             => $spItem['partner_name'] ?? ($sIdx === 0 ? ($primaryPersonalFields['partner_name'] ?? null) : null),
                                    'partner_phone'            => $spItem['partner_phone'] ?? ($sIdx === 0 ? ($primaryPersonalFields['partner_phone'] ?? null) : null),
                                    'partner_info'             => $spItem['partner_info'] ?? ($sIdx === 0 ? ($primaryPersonalFields['partner_info'] ?? null) : null),
                                    'dependents'               => $spItem['dependents'] ?? ($sIdx === 0 ? ($primaryPersonalFields['dependents'] ?? null) : null),
                                    'height_unit'              => $spItem['height_unit'] ?? null,
                                    'holding_facility'         => $spItem['holding_facility'] ?? ($spItem['facility'] ?? ($validated['holding_facility'] ?? null)),
                                    'raid_location'            => $spItem['raid_location'] ?? ($spItem['location'] ?? ($validated['raid_location'] ?? null)),
                                    'offense_description'      => $spItem['offense'] ?? ($spItem['offense_description'] ?? ($validated['offense_description'] ?? null)),
                                    'arrest_date'              => $spItem['date_of_arrest'] ?? ($spItem['arrest_date'] ?? ($validated['arrest_date'] ?? null)),
                                    'arrest_time'              => $spItem['time_of_arrest'] ?? ($spItem['arrest_time'] ?? ($validated['arrest_time'] ?? null)),
                                    'mugshot_front'            => $spItem['mugshot_front'] ?? ($spItem['mugshots']['front'] ?? ($sIdx === 0 ? $case->mugshot_front : null)),
                                    'mugshot_left'             => $spItem['mugshot_left'] ?? ($spItem['mugshots']['left'] ?? ($sIdx === 0 ? $case->mugshot_left : null)),
                                    'mugshot_right'            => $spItem['mugshot_right'] ?? ($spItem['mugshots']['right'] ?? ($sIdx === 0 ? $case->mugshot_right : null)),
                                ]
                            );
                        }
                    }

                    $isLabCreatedCaseStore = !empty($case->unit_name) && (
                        strtolower(trim($case->unit_name)) === 'lab analysis' ||
                        str_contains(strtolower($case->unit_name), 'lab analysis') ||
                        str_contains(strtolower($case->unit_name), 'laboratory')
                    );
                    $isCssCreatedCaseStore = !empty($case->unit_name) && (
                        str_contains(strtolower($case->unit_name), 'crime') ||
                        str_contains(strtolower($case->unit_name), 'scene')
                    );
                    $isDpfCreatedCaseStore = !empty($case->unit_name) && (
                        str_contains(strtolower($case->unit_name), 'digital') ||
                        str_contains(strtolower($case->unit_name), 'physical')
                    );
                    $isEvidenceCreatedCaseStore = !empty($case->unit_name) && (
                        str_contains(strtolower($case->unit_name), 'evidence')
                    );

                    $isDrugStore = $case->isDrugCase();
                    $isElectronicStore = method_exists($case, 'isElectronicCase') ? $case->isElectronicCase() : false;

                    // Check if GSA files are uploaded or present (mutual exclusivity with NLR)
                    $hasGsaFilesUploaded = $hasGsaFilesUploadedOuter;

                    // Only genuine lab submissions by the Lab Analysis unit can complete lab analysis during creation
                    // Mutual Exclusivity: When GSA files are uploaded or Section 5 was not completed, NLR examination is NOT created.
                    $hasSubmittedLabData = $isLabCreatedCaseStore && !$hasGsaFilesUploaded && $hasCompletedSection5Outer;

                    $labStatus = ($hasSubmittedLabData || $hasGsaFilesUploaded) ? WorkflowStatus::COMPLETED->value : WorkflowStatus::PENDING->value;
                    $labOfficer = ($hasSubmittedLabData || $hasGsaFilesUploaded) ? ($user ? $user->fullname : ($case->arresting_officer ?? ($case->submitting_officer ?: 'Lab Analyst'))) : null;
                    $labUpdatedAt = ($hasSubmittedLabData || $hasGsaFilesUploaded) ? now()->toDateTimeString() : null;

                    $unitProgressBlock = [];

                    // Creator unit always appears first and is marked COMPLETED upon logging the case
                    if ($isLabCreatedCaseStore) {
                        $unitProgressBlock[UnitName::LAB_ANALYSIS->value] = [
                            'status'     => $labStatus,
                            'officer'    => $labOfficer,
                            'updated_at' => $labUpdatedAt,
                        ];
                    } elseif ($isCssCreatedCaseStore) {
                        $unitProgressBlock[UnitName::CRIME_SCENE_SUPPORT->value] = [
                            'status'     => WorkflowStatus::COMPLETED->value,
                            'officer'    => $case->arresting_officer ?? ($case->submitting_officer ?: 'Field Officer'),
                            'updated_at' => now()->toDateTimeString(),
                        ];
                    } elseif ($isDpfCreatedCaseStore) {
                        $unitProgressBlock[UnitName::DIGITAL_PHYSICAL_FORENSICS->value] = [
                            'status'     => WorkflowStatus::COMPLETED->value,
                            'officer'    => $case->arresting_officer ?? ($case->submitting_officer ?: 'Forensic Analyst'),
                            'updated_at' => now()->toDateTimeString(),
                        ];
                    } elseif ($isEvidenceCreatedCaseStore) {
                        $unitProgressBlock[UnitName::EVIDENCE_MANAGEMENT->value] = [
                            'status'     => WorkflowStatus::COMPLETED->value,
                            'officer'    => $case->arresting_officer ?? ($case->submitting_officer ?: 'Evidence Custodian'),
                            'updated_at' => now()->toDateTimeString(),
                        ];
                    } else {
                        $unitProgressBlock[UnitName::CRIME_SCENE_SUPPORT->value] = [
                            'status'     => WorkflowStatus::COMPLETED->value,
                            'officer'    => $case->arresting_officer ?? ($case->submitting_officer ?: 'Field Officer'),
                            'updated_at' => now()->toDateTimeString(),
                        ];
                    }

                    // Involved specialist units in canonical sequence
                    if ($isDrugStore && !isset($unitProgressBlock[UnitName::LAB_ANALYSIS->value])) {
                        $unitProgressBlock[UnitName::LAB_ANALYSIS->value] = [
                            'status'     => WorkflowStatus::PENDING->value,
                            'officer'    => null,
                            'updated_at' => null,
                        ];
                    }
                    if ($isElectronicStore && !isset($unitProgressBlock[UnitName::DIGITAL_PHYSICAL_FORENSICS->value])) {
                        $unitProgressBlock[UnitName::DIGITAL_PHYSICAL_FORENSICS->value] = [
                            'status'     => WorkflowStatus::PENDING->value,
                            'officer'    => null,
                            'updated_at' => null,
                        ];
                    }

                    // Evidence Management is always present as the final operational stage
                    if (!isset($unitProgressBlock[UnitName::EVIDENCE_MANAGEMENT->value])) {
                        $unitProgressBlock[UnitName::EVIDENCE_MANAGEMENT->value] = [
                            'status'     => WorkflowStatus::PENDING->value,
                            'officer'    => null,
                            'updated_at' => null,
                        ];
                    }

                    $caseDocketData = $this->decodeDocket($case);
                    $caseDocketData['unit_progress'] = $unitProgressBlock;

                    if ($descOfExhibits !== null) {
                        $caseDocketData['description_of_exhibits'] = $descOfExhibits;
                        $case->description_of_exhibits = $descOfExhibits;
                        $underlyingRecord = $case->underlyingRecord;
                        if ($underlyingRecord && SchemaCache::hasColumn($underlyingRecord->getTable(), 'description_of_exhibits')) {
                            $underlyingRecord->description_of_exhibits = $descOfExhibits;
                            $underlyingRecord->save();
                        }
                    }

                    // Link any unassigned exhibits selected during case creation BEFORE saving lab exam
                    // so LaboratoryExamService and Preliminary Report sync see the newly attached exhibits.
                    $rawLinkedIds = $request->input('linked_exhibit_ids', []);
                    if (!empty($rawLinkedIds)) {
                        $linkedIds = is_array($rawLinkedIds) ? $rawLinkedIds : array_filter(explode(',', (string)$rawLinkedIds));
                        $isMultiCase = ($underlying instanceof MultipleSuspectCase);

                        $targetSuspectId = null;
                        $primarySuspectName = $case->suspect_name;
                        if ($isMultiCase) {
                            $firstSuspect = CaseSuspect::where('multiple_suspect_case_id', $underlying->id)->orderBy('suspect_number')->first();
                            if ($firstSuspect) {
                                $targetSuspectId = $firstSuspect->id;
                                $primarySuspectName = $firstSuspect->suspect_name;
                            }
                        } else {
                            $targetSingleRecord = ($underlying instanceof SingleSuspectCase)
                                ? $underlying
                                : SingleSuspectCase::where('case_number', $case->case_number)->first();
                            $firstSuspect = $targetSingleRecord ? CaseSuspect::where('single_suspect_case_id', $targetSingleRecord->id)->first() : null;
                            if ($firstSuspect) {
                                $targetSuspectId = $firstSuspect->id;
                            }
                        }

                        if (!isset($caseDocketData['seizures']) || !is_array($caseDocketData['seizures'])) {
                            $caseDocketData['seizures'] = [];
                        }
                        $linkedPhotosPool = [];
                        $drugClassifier = app(DrugExhibitClassifier::class);

                        foreach ($linkedIds as $lId) {
                            $exRecord = Exhibit::find($lId);
                            if ($exRecord) {
                                $oldSingleCaseId = $exRecord->single_suspect_case_id;

                                $matchedSzIdx = null;
                                foreach ($caseDocketData['seizures'] as $szIdx => $szCandidate) {
                                    if ((!empty($szCandidate['property_id']) && $exRecord->property_id && $szCandidate['property_id'] === $exRecord->property_id)
                                        || (!empty($szCandidate['id']) && (int) $szCandidate['id'] === (int) $exRecord->id)
                                        || (!empty($szCandidate['linked_exhibit_id']) && (int) $szCandidate['linked_exhibit_id'] === (int) $exRecord->id)) {
                                        $matchedSzIdx = $szIdx;
                                        break;
                                    }
                                }
                                if ($matchedSzIdx === null && count($linkedIds) === 1 && count($caseDocketData['seizures']) === 1) {
                                    $matchedSzIdx = array_key_first($caseDocketData['seizures']);
                                }

                                $submittedExhibitFields = $this->extractSubmittedExhibitFields(
                                    $matchedSzIdx !== null && is_array($caseDocketData['seizures'][$matchedSzIdx])
                                        ? $caseDocketData['seizures'][$matchedSzIdx]
                                        : [],
                                    $sharedFields
                                );

                                $exRecord->update(array_merge($submittedExhibitFields, [
                                    'case_type'                => $isMultiCase ? 'MULTIPLE' : 'SINGLE',
                                    'single_suspect_case_id'   => $isMultiCase ? null : $case->id,
                                    'multiple_suspect_case_id' => $isMultiCase ? $case->id : null,
                                    'suspect_id'               => $targetSuspectId,
                                    'associated_person'        => $primarySuspectName,
                                ]));

                                if ($drugClassifier->isDrugExhibit($exRecord)) {
                                    if (empty($case->exhibit_category)) {
                                        $case->exhibit_category = $exRecord->exhibit_category ?: 'Drugs';
                                    }
                                    if (empty($case->seizure_name_drug)) {
                                        $case->seizure_name_drug = $exRecord->commodity_name;
                                    }
                                    if (!isset($caseDocketData['unit_progress'][UnitName::LAB_ANALYSIS->value])) {
                                        $caseDocketData['unit_progress'][UnitName::LAB_ANALYSIS->value] = [
                                            'status'     => WorkflowStatus::PENDING->value,
                                            'officer'    => null,
                                            'updated_at' => null,
                                        ];
                                    }
                                }

                                if ($oldSingleCaseId && $oldSingleCaseId !== $case->id) {
                                    $oldCase = SingleSuspectCase::find($oldSingleCaseId);
                                    if ($oldCase && (str_contains(strtolower($oldCase->suspect_name), 'unassigned') || str_contains(strtolower($oldCase->suspect_name), 'awaiting'))) {
                                        $remainingCount = Exhibit::where('single_suspect_case_id', $oldSingleCaseId)->count();
                                        if ($remainingCount === 0) {
                                            $oldCase->delete();
                                        }
                                    }
                                }

                                // Check if already represented in seizures array by property_id, id, linked_exhibit_id, or single-item match
                                $alreadyPresent = false;
                                $resolvedPhotos = $exRecord->resolveEvidencePhotosArray();
                                foreach ($resolvedPhotos as $rp) {
                                    if ($rp && !in_array($rp, $linkedPhotosPool, true)) {
                                        $linkedPhotosPool[] = $rp;
                                    }
                                }
                                if ($matchedSzIdx !== null && isset($caseDocketData['seizures'][$matchedSzIdx])) {
                                    $alreadyPresent = true;
                                    $caseDocketData['seizures'][$matchedSzIdx]['id']                = $exRecord->id;
                                    $caseDocketData['seizures'][$matchedSzIdx]['property_id']       = $exRecord->property_id;
                                    $caseDocketData['seizures'][$matchedSzIdx]['linked_exhibit_id'] = $exRecord->id;
                                    $caseDocketData['seizures'][$matchedSzIdx]['associated_person'] = $primarySuspectName;
                                    $caseDocketData['seizures'][$matchedSzIdx]['suspect_ownership'] = $primarySuspectName;
                                    $caseDocketData['seizures'][$matchedSzIdx]['suspect_id']        = $targetSuspectId;
                                    if (empty($caseDocketData['seizures'][$matchedSzIdx]['evidence_photos']) && !empty($resolvedPhotos)) {
                                        $caseDocketData['seizures'][$matchedSzIdx]['evidence_photos'] = $resolvedPhotos;
                                    }
                                }

                                if (!$alreadyPresent) {
                                    $caseDocketData['seizures'][] = [
                                        'id'                      => $exRecord->id,
                                        'property_id'             => $exRecord->property_id,
                                        'exhibit_number'          => $exRecord->exhibit_number,
                                        'lab_exhibit_no'          => $exRecord->lab_exhibit_no,
                                        'exhibit_category'        => $exRecord->exhibit_category,
                                        'exhibit_type'            => $exRecord->exhibit_type,
                                        'drug_classification'     => $exRecord->drug_classification,
                                        'commodity_name'          => $exRecord->commodity_name,
                                        'seizure_name'            => $exRecord->commodity_name,
                                        'name'                    => $exRecord->commodity_name,
                                        'seizure_quantity'        => $exRecord->seizure_quantity,
                                        'amount'                  => $exRecord->amount,
                                        'seizure_weight'          => $exRecord->seizure_weight,
                                        'seizure_unit'            => $exRecord->seizure_unit,
                                        'seizure_packaging'       => $exRecord->seizure_packaging,
                                        'seizure_value'           => $exRecord->seizure_value,
                                        'seizure_location'        => $exRecord->seizure_location,
                                        'seizure_date'            => $exRecord->seizure_date,
                                        'seizure_time'            => $exRecord->seizure_time,
                                        'seizure_officer'         => $exRecord->seizure_officer,
                                        'investigating_officer'   => $exRecord->investigating_officer,
                                        'source_requesting_unit'  => $exRecord->source_requesting_unit,
                                        'seizure_reason'          => $exRecord->seizure_reason,
                                        'source_of_exhibits'      => $exRecord->source_of_exhibits,
                                        'exhibit_remark'          => $exRecord->exhibit_remark,
                                        'evidence_photos'         => $resolvedPhotos,
                                        'associated_person'       => $primarySuspectName,
                                        'suspect_ownership'       => $primarySuspectName,
                                        'suspect_id'              => $targetSuspectId,
                                        'linked_from_vault'       => true,
                                        'linked_exhibit_id'       => $exRecord->id,
                                    ];
                                }
                            }
                        }

                        if (!empty($linkedPhotosPool)) {
                            $existingCasePhotos = Exhibit::decodePhotosValue($case->evidence_photos ?? []);
                            if (empty($existingCasePhotos)) {
                                $case->evidence_photos = $linkedPhotosPool;
                            }
                            $existingDocketPhotos = Exhibit::decodePhotosValue($caseDocketData['evidence_photos'] ?? []);
                            if (empty($existingDocketPhotos)) {
                                $caseDocketData['evidence_photos'] = $linkedPhotosPool;
                            }
                        }
                    }

                    $case->docket_data = $caseDocketData;
                    $case->save();

                    // Fix Issue 11: Delegate lab exam building and persistence to LaboratoryExamService only for Lab Analysis unit creations
                    // Mutual Exclusivity: Only invoke saveExam if Section 5 lab data was genuinely completed AND GSA files were NOT uploaded
                    if ($hasSubmittedLabData && $isLabCreatedCaseStore && !$hasGsaFilesUploaded) {
                        $primarySuspectName = $primarySuspect['suspect_name'] ?? $case->suspect_name;
                        $labService = app(LaboratoryExamService::class);
                        $examPayload = $request->all();
                        if (empty($examPayload['exhibit_id']) && !empty($docketData['linked_exhibit_ids'])) {
                            $examPayload['exhibit_id'] = (int) $docketData['linked_exhibit_ids'][0];
                        }
                        $caseDocketData = $labService->saveExam($case, $examPayload, $primarySuspectName, false, $labOfficer);

                        $primarySavedExId = (int) ($examPayload['exhibit_id'] ?? ($caseDocketData['lab_examination']['exhibit_id'] ?? 0));
                        $submittedLabList = is_array($docketData['lab_examinations_list'] ?? null)
                            ? $docketData['lab_examinations_list']
                            : [];
                        if (count($submittedLabList) > 1) {
                            $case->docket_data = $caseDocketData;
                            $linkedIdsList = array_values(array_map('intval', $docketData['linked_exhibit_ids'] ?? []));
                            foreach ($submittedLabList as $lIdx => $lItem) {
                                if (!is_array($lItem)) {
                                    continue;
                                }
                                $itemExId = (int) ($lItem['linked_exhibit_id'] ?? ($lItem['exhibit_id'] ?? ($linkedIdsList[$lIdx] ?? 0)));
                                if ($lIdx === 0 && ($itemExId === 0 || $itemExId === $primarySavedExId)) {
                                    continue;
                                }
                                if ($itemExId > 0 && $itemExId === $primarySavedExId) {
                                    continue;
                                }
                                $itemPayload = array_merge($examPayload, array_filter([
                                    'exhibit_id'                          => $itemExId > 0 ? $itemExId : null,
                                    'lab_exhibit_no'                      => $lItem['lab_exhibit_no'] ?? null,
                                    'purpose_of_examination'              => $lItem['purpose_of_examination'] ?? null,
                                    'preliminary_exam_procedure'          => $lItem['preliminary_exam_procedure'] ?? null,
                                    'screening_method'                    => $lItem['screening_method'] ?? null,
                                    'presumptive_result'                  => $lItem['presumptive_result'] ?? null,
                                    'presumptive_test_result'             => $lItem['presumptive_result'] ?? null,
                                    'presumptive_presence'                => $lItem['presumptive_presence'] ?? null,
                                    'presumptive_percentage'              => $lItem['presumptive_percentage'] ?? null,
                                    'confirmatory_test_result'            => $lItem['confirmatory_result'] ?? null,
                                    'sample_weight_description'           => $lItem['sample_weight_description'] ?? ($lItem['sample_weight']['description'] ?? null),
                                    'sample_weight_units'                 => $lItem['sample_weight_units'] ?? ($lItem['sample_weight']['units_packaging'] ?? null),
                                    'sample_weight_gross'                 => $lItem['sample_weight_gross'] ?? ($lItem['sample_weight']['gross_weight'] ?? null),
                                    'sample_weight_taken'                 => $lItem['sample_weight_taken'] ?? ($lItem['sample_weight']['sample_taken'] ?? null),
                                    'sample_weight_reserved'              => $lItem['sample_weight_reserved'] ?? ($lItem['sample_weight']['reserved_samples'] ?? null),
                                    'confirmatory_substance_identification' => $lItem['substance_identification'] ?? null,
                                    'detected_substances'                 => $lItem['detected_substances'] ?? null,
                                    'confirmatory_test_methods'           => $lItem['test_methods'] ?? null,
                                    'confirmatory_specifications'         => $lItem['specifications'] ?? null,
                                    'confirmatory_remark'                 => $lItem['remark'] ?? null,
                                    'disposition_of_exhibit'              => $lItem['disposition_of_exhibit'] ?? null,
                                    'findings'                            => $lItem['findings'] ?? null,
                                ], fn ($v) => $v !== null && $v !== ''));
                                $caseDocketData = $labService->saveExam($case, $itemPayload, $primarySuspectName, false, $labOfficer);
                                $case->docket_data = $caseDocketData;
                            }
                        }
                    } else {
                        // Ensure no uncompleted or conflicting NLR lab examination data exists
                        unset($caseDocketData['lab_examination']);
                    }

                    $case->docket_data = $caseDocketData;
                    $case->save();

                    if ($isLabCreatedCaseStore && !$hasGsaFilesUploaded && !$hasSubmittedLabData) {
                        try {
                            app(\App\Http\Controllers\CaseReportController::class)->storeOrSyncPreliminaryReport($case, null, $caseDocketData);
                        } catch (\Throwable $e) {
                            // Fail gracefully without interrupting case creation
                        }
                    }

                    // If GSA report files were uploaded during case creation, attach them automatically
                    if ($request->hasFile('gsa_report_images')) {
                        $gsaFiles = $request->file('gsa_report_images');
                        if (!is_array($gsaFiles)) {
                            $gsaFiles = [$gsaFiles];
                        }
                        $targetMultiCase = null;
                        $targetSingleCase = null;
                        if ($underlying instanceof MultipleSuspectCase) {
                            $targetMultiCase = $underlying;
                            $targetSuspect = CaseSuspect::where('multiple_suspect_case_id', $targetMultiCase->id)->first();
                            if (!$targetSuspect) {
                                $targetSuspect = CaseSuspect::create([
                                    'multiple_suspect_case_id' => $targetMultiCase->id,
                                    'suspect_name'             => $case->suspect_name ?? 'Primary Suspect',
                                    'suspect_number'           => 1,
                                    'role'                     => 'Primary Suspect',
                                ]);
                            }
                        } else {
                            $targetSingleCase = ($underlying instanceof SingleSuspectCase)
                                ? $underlying
                                : SingleSuspectCase::where('case_number', $case->case_number)->first();
                            $targetSuspect = $targetSingleCase ? CaseSuspect::where('single_suspect_case_id', $targetSingleCase->id)->first() : null;
                            if (!$targetSuspect && $targetSingleCase) {
                                $targetSuspect = CaseSuspect::create([
                                    'single_suspect_case_id'   => $targetSingleCase->id,
                                    'suspect_name'             => $case->suspect_name ?? 'Primary Suspect',
                                    'suspect_number'           => 1,
                                    'role'                     => 'Primary Suspect',
                                ]);
                            }
                        }
                        $folder = 'gsa_reports/' . now()->format('Y/m');
                        $rawDocketExhibitType = $request->input('exhibit_type');
                        $docketExhibitType = null;
                        if (is_array($rawDocketExhibitType)) {
                            $filtered = array_filter(array_map('trim', $rawDocketExhibitType), fn($v) => $v !== '');
                            $docketExhibitType = !empty($filtered) ? implode(', ', $filtered) : null;
                        } elseif (!empty($rawDocketExhibitType)) {
                            $docketExhibitType = trim((string)$rawDocketExhibitType) ?: null;
                        }

                        foreach ($gsaFiles as $file) {
                            if ($file instanceof \Illuminate\Http\UploadedFile) {
                                $ext    = strtolower($file->getClientOriginalExtension());
                                $stored = \Illuminate\Support\Str::uuid() . '.' . $ext;
                                $path   = $file->storeAs($folder, $stored, 'public');

                                SuspectGsaReport::create([
                                    'case_suspect_id'          => $targetSuspect ? $targetSuspect->id : null,
                                    'multiple_suspect_case_id' => $targetMultiCase ? $targetMultiCase->id : null,
                                    'single_suspect_case_id'   => $targetSingleCase ? $targetSingleCase->id : null,
                                    'uploaded_by'              => auth()->id(),
                                    'original_filename'        => $file->getClientOriginalName(),
                                    'stored_filename'          => $stored,
                                    'file_path'                => $path,
                                    'file_type'                => $file->getMimeType(),
                                    'file_extension'           => $ext,
                                    'file_size'                => $file->getSize(),
                                    'lab_number'               => $request->input('laboratory_number') ?? $request->input('lab_number'),
                                    'exhibit_type'             => $docketExhibitType,
                                    'case_number'              => $case->case_number,
                                    'status'                   => 'active',
                                ]);
                            }
                        }
                    }

                    return $case;
                });
            }, 'NCC', 10, $preferredRef);

            Cache::forget($lockKey);
        } catch (\Throwable $e) {
            Cache::forget($lockKey);
            Log::error('Case creation failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            throw $e;
        }

        $preliminaryReport = null;
        $createdUnitLower = strtolower(trim((string) ($case->unit_name ?? '')));
        $isLabCreatedCase = $createdUnitLower !== ''
            && (str_contains($createdUnitLower, 'lab') || str_contains($createdUnitLower, 'laboratory'));

        if ($isLabCreatedCase && !$hasGsaFilesUploadedOuter) {
            $prelimRecord = \App\Models\SuspectPreliminaryReport::forCase($case->case_number, $case->id)->first();
            if ($prelimRecord) {
                $preliminaryReport = [
                    'id'          => $prelimRecord->id,
                    'filename'    => $prelimRecord->original_filename,
                    'url'         => $prelimRecord->url,
                    'file_path'   => $prelimRecord->file_path,
                    'notes'       => $prelimRecord->notes,
                    'case_number' => $case->case_number,
                ];
            }
        }

        return [
            'success'            => true,
            'message'            => $noSuspect
                ? "Case {$case->case_number} logged with seized exhibit(s) (no suspect assigned yet)."
                : "Case {$case->case_number} created with " . count($allSuspects) . " suspect(s) saved under single Case ID.",
            'case'               => $case,
            'suspects_count'     => $noSuspect ? 0 : count($allSuspects),
            'preliminary_report' => $preliminaryReport,
        ];
    }

    /**
     * Link suspect biodata and form updates to an existing exhibit-without-suspect case,
     * reusing its existing case reference and preserving prior laboratory/preliminary records.
     */
    private function linkSuspectToExistingExhibitCase(
        Request $request,
        SingleSuspectCase $targetSingleCase,
        $linkedExhibitsCollection,
        array $allSuspects,
        \Closure $suspectFields,
        array $sharedFields,
        array $docketData,
        array $validated,
        string $jointSuspectNameLabel,
        $user,
        string $lockKey,
        bool $hasGsaFilesUploadedOuter,
        bool $hasCompletedSection5Outer,
        ?string $descOfExhibits
    ): array {
        $existingCaseNumber = $targetSingleCase->case_number;
        $isMultiTarget = count($allSuspects) > 1
            || strtoupper((string) ($validated['suspect_docket_mode'] ?? 'SINGLE')) === 'MULTIPLE';

        try {
            $case = DB::transaction(function () use (
                $request,
                $targetSingleCase,
                $linkedExhibitsCollection,
                $allSuspects,
                $suspectFields,
                $sharedFields,
                $docketData,
                $validated,
                $jointSuspectNameLabel,
                $user,
                $hasGsaFilesUploadedOuter,
                $hasCompletedSection5Outer,
                $descOfExhibits,
                $existingCaseNumber,
                $isMultiTarget
            ) {
                $primarySuspect = $allSuspects[0];
                $primaryPersonalFields = $suspectFields($primarySuspect);
                $primarySuspectName = $primaryPersonalFields['suspect_name']
                    ?? ($primarySuspect['suspect_name'] ?? $jointSuspectNameLabel);

                $rawTitle = trim((string) ($validated['title'] ?? ''));
                $resolvedTitle = ($rawTitle !== '' && !str_contains(strtolower($rawTitle), 'awaiting suspect'))
                    ? $rawTitle
                    : ('Republic vs ' . ($isMultiTarget ? $jointSuspectNameLabel : $primarySuspectName));

                $mugFront = !empty($validated['mugshot_front']) ? $validated['mugshot_front'] : ($primarySuspect['mugshot_front'] ?? $targetSingleCase->mugshot_front);
                $mugLeft = !empty($validated['mugshot_left']) ? $validated['mugshot_left'] : ($primarySuspect['mugshot_left'] ?? $targetSingleCase->mugshot_left);
                $mugRight = !empty($validated['mugshot_right']) ? $validated['mugshot_right'] : ($primarySuspect['mugshot_right'] ?? $targetSingleCase->mugshot_right);

                $resolvedStatus = in_array(strtolower(trim((string) ($targetSingleCase->status ?? ''))), ['', 'awaiting suspect'], true)
                    ? ($validated['status'] ?? 'Approved')
                    : $targetSingleCase->status;

                $submittedUnitName = $validated['unit_name'] ?? ($user ? $user->unit : null);
                $submittedUnitLower = strtolower(trim((string) $submittedUnitName));
                $isLabSubmitter = $submittedUnitLower !== ''
                    && (str_contains($submittedUnitLower, 'lab') || str_contains($submittedUnitLower, 'laboratory'));

                $targetMultiCase = null;
                $targetSuspectId = null;

                if ($isMultiTarget) {
                    $targetMultiCase = MultipleSuspectCase::create([
                        'case_number'             => $existingCaseNumber,
                        'laboratory_number'       => $targetSingleCase->laboratory_number,
                        'title'                   => $resolvedTitle,
                        'unit_name'               => $targetSingleCase->unit_name ?: ($submittedUnitName ?: 'Forensic Directorate'),
                        'unit_id'                 => $targetSingleCase->unit_id ?? ($sharedFields['unit_id'] ?? null),
                        'user_id'                 => $targetSingleCase->user_id ?? ($sharedFields['user_id'] ?? null),
                        'arresting_officer'       => !empty($validated['arresting_officer']) ? $validated['arresting_officer'] : ($targetSingleCase->arresting_officer ?: ($sharedFields['arresting_officer'] ?? null)),
                        'submitting_officer'      => !empty($validated['submitting_officer']) ? $validated['submitting_officer'] : ($targetSingleCase->submitting_officer ?: ($sharedFields['submitting_officer'] ?? null)),
                        'operation_code'          => $validated['operation_code'] ?? $targetSingleCase->operation_code,
                        'operation_code_name'     => $validated['operation_code_name'] ?? $targetSingleCase->operation_code_name,
                        'search_warrant_document' => $sharedFields['search_warrant_document'] ?? $targetSingleCase->search_warrant_document,
                        'raid_location'           => !empty($validated['raid_location']) ? $validated['raid_location'] : $targetSingleCase->raid_location,
                        'offense_description'     => !empty($validated['offense_description']) ? $validated['offense_description'] : $targetSingleCase->offense_description,
                        'description_of_exhibits' => $targetSingleCase->description_of_exhibits,
                        'incident_date'           => $targetSingleCase->incident_date ?? now(),
                        'total_suspects_count'    => count($allSuspects),
                        'total_exhibits_count'    => max(1, Exhibit::where('single_suspect_case_id', $targetSingleCase->id)->count()),
                        'priority'                => $validated['priority'] ?? ($targetSingleCase->priority ?: 'HIGH'),
                        'status'                  => $resolvedStatus,
                        'case_status'             => $targetSingleCase->case_status ?: 'Under Investigation',
                        'controller_user_id'      => $targetSingleCase->controller_user_id ?? (($user instanceof \App\Models\User) ? $user->id : null),
                        'hash_log'                => $targetSingleCase->hash_log ?: ('HASH-' . strtoupper(substr(md5(uniqid()), 0, 8)) . '-VERIFIED'),
                    ]);

                    foreach ($allSuspects as $sIdx => $spItem) {
                        $spNum = $sIdx + 1;
                        $isAssocSp = $sIdx > 0 || !empty($spItem['is_associate']) || (!empty($spItem['role']) && str_contains(strtolower($spItem['role']), 'associate'));
                        $roleVal = $spItem['role'] ?? ($spItem['association_role'] ?? ($isAssocSp ? 'Co-Suspect' : 'Primary Suspect'));
                        $assocIdVal = $spItem['associate_id'] ?? ($isAssocSp ? ('ASC-' . str_pad($sIdx, 3, '0', STR_PAD_LEFT)) : null);

                        $createdSp = CaseSuspect::updateOrCreate(
                            [
                                'multiple_suspect_case_id' => $targetMultiCase->id,
                                'suspect_name'             => $spItem['suspect_name'],
                            ],
                            [
                                'suspect_number'      => $spNum,
                                'role'                => $roleVal,
                                'associate_id'        => $assocIdVal,
                                'suspect_alias'       => $spItem['suspect_alias'] ?? null,
                                'ghana_card'          => $spItem['ghana_card'] ?? null,
                                'gender'              => $spItem['gender'] ?? null,
                                'dob'                 => $spItem['dob'] ?? null,
                                'age'                 => isset($spItem['age']) && $spItem['age'] !== '' ? (int) $spItem['age'] : null,
                                'height'              => $spItem['height'] ?? null,
                                'eye_color'           => $spItem['eye_color'] ?? null,
                                'nationality'         => $spItem['nationality'] ?? null,
                                'pob'                 => $spItem['pob'] ?? ($sIdx === 0 ? ($primaryPersonalFields['pob'] ?? null) : null),
                                'phone'               => $spItem['phone'] ?? ($sIdx === 0 ? ($validated['phone'] ?? null) : null),
                                'phones'              => $spItem['phones'] ?? ($sIdx === 0 ? ($primaryPersonalFields['phones'] ?? null) : null),
                                'address'             => $spItem['address'] ?? ($sIdx === 0 ? ($primaryPersonalFields['address'] ?? null) : null),
                                'marital_status'      => $spItem['marital_status'] ?? ($sIdx === 0 ? ($primaryPersonalFields['marital_status'] ?? null) : null),
                                'has_children'        => !empty($spItem['has_children']) || (!empty($primaryPersonalFields['has_children']) && $sIdx === 0),
                                'children_count'      => isset($spItem['children_count']) && $spItem['children_count'] !== '' ? (int) $spItem['children_count'] : ($sIdx === 0 ? ($primaryPersonalFields['children_count'] ?? null) : null),
                                'spouse_info'         => $spItem['spouse_info'] ?? ($sIdx === 0 ? ($primaryPersonalFields['spouse_info'] ?? null) : null),
                                'spouse_name'         => $spItem['spouse_name'] ?? ($sIdx === 0 ? ($primaryPersonalFields['spouse_name'] ?? null) : null),
                                'spouse_phone'        => $spItem['spouse_phone'] ?? ($sIdx === 0 ? ($primaryPersonalFields['spouse_phone'] ?? null) : null),
                                'partner_name'        => $spItem['partner_name'] ?? ($sIdx === 0 ? ($primaryPersonalFields['partner_name'] ?? null) : null),
                                'partner_phone'       => $spItem['partner_phone'] ?? ($sIdx === 0 ? ($primaryPersonalFields['partner_phone'] ?? null) : null),
                                'partner_info'        => $spItem['partner_info'] ?? ($sIdx === 0 ? ($primaryPersonalFields['partner_info'] ?? null) : null),
                                'dependents'          => $spItem['dependents'] ?? ($sIdx === 0 ? ($primaryPersonalFields['dependents'] ?? null) : null),
                                'holding_facility'    => $spItem['holding_facility'] ?? ($validated['holding_facility'] ?? null),
                                'raid_location'       => $spItem['raid_location'] ?? ($validated['raid_location'] ?? null),
                                'offense_description' => $spItem['offense'] ?? ($validated['offense_description'] ?? null),
                                'arrest_date'         => $spItem['date_of_arrest'] ?? ($validated['arrest_date'] ?? null),
                                'arrest_time'         => $spItem['time_of_arrest'] ?? ($validated['arrest_time'] ?? null),
                                'mugshot_front'       => $spItem['mugshot_front'] ?? ($sIdx === 0 ? $mugFront : null),
                                'mugshot_left'        => $spItem['mugshot_left'] ?? ($sIdx === 0 ? $mugLeft : null),
                                'mugshot_right'       => $spItem['mugshot_right'] ?? ($sIdx === 0 ? $mugRight : null),
                            ]
                        );
                        if ($sIdx === 0) {
                            $targetSuspectId = $createdSp->id;
                        }
                    }

                    // Move existing children off SingleSuspectCase BEFORE deleting it to avoid FK cascade loss
                    Exhibit::where('single_suspect_case_id', $targetSingleCase->id)->update([
                        'case_type'                => 'MULTIPLE',
                        'single_suspect_case_id'   => null,
                        'multiple_suspect_case_id' => $targetMultiCase->id,
                        'suspect_id'               => $targetSuspectId,
                        'associated_person'        => $jointSuspectNameLabel,
                    ]);
                    LaboratoryExaminationResult::where('single_suspect_case_id', $targetSingleCase->id)->update([
                        'case_type'                => 'MULTIPLE',
                        'single_suspect_case_id'   => null,
                        'multiple_suspect_case_id' => $targetMultiCase->id,
                        'suspect_id'               => $targetSuspectId,
                    ]);
                    \App\Models\SuspectPreliminaryReport::where('single_suspect_case_id', $targetSingleCase->id)->update([
                        'single_suspect_case_id'   => null,
                        'multiple_suspect_case_id' => $targetMultiCase->id,
                        'case_suspect_id'          => $targetSuspectId,
                    ]);
                    SuspectGsaReport::where('single_suspect_case_id', $targetSingleCase->id)->update([
                        'single_suspect_case_id'   => null,
                        'multiple_suspect_case_id' => $targetMultiCase->id,
                        'case_suspect_id'          => $targetSuspectId,
                    ]);
                    SuspectNlrReport::where('single_suspect_case_id', $targetSingleCase->id)->update([
                        'single_suspect_case_id'   => null,
                        'multiple_suspect_case_id' => $targetMultiCase->id,
                        'case_suspect_id'          => $targetSuspectId,
                    ]);
                    \App\Models\DpfReport::where(function ($q) use ($targetSingleCase, $existingCaseNumber) {
                        $q->where('single_suspect_case_id', $targetSingleCase->id)
                          ->orWhere('case_number', $existingCaseNumber);
                    })->update([
                        'single_suspect_case_id'   => null,
                        'multiple_suspect_case_id' => $targetMultiCase->id,
                        'suspect_id'               => $targetSuspectId,
                    ]);
                    CaseSuspect::where('single_suspect_case_id', $targetSingleCase->id)->delete();
                    $targetSingleCase->delete();
                } else {
                    $updatePayload = [
                        'title'                   => $resolvedTitle,
                        'suspect_name'            => $primarySuspectName,
                        'suspect_alias'           => $primaryPersonalFields['suspect_alias'] ?? $targetSingleCase->suspect_alias,
                        'ghana_card'              => $primaryPersonalFields['ghana_card'] ?? $targetSingleCase->ghana_card,
                        'gender'                  => $primaryPersonalFields['gender'] ?? $targetSingleCase->gender,
                        'dob'                     => $primaryPersonalFields['dob'] ?? $targetSingleCase->dob,
                        'age'                     => $primaryPersonalFields['age'] ?? $targetSingleCase->age,
                        'height'                  => $primaryPersonalFields['height'] ?? $targetSingleCase->height,
                        'eye_color'               => $primaryPersonalFields['eye_color'] ?? $targetSingleCase->eye_color,
                        'nationality'             => $primaryPersonalFields['nationality'] ?? $targetSingleCase->nationality,
                        'pob'                     => $primaryPersonalFields['pob'] ?? $targetSingleCase->pob,
                        'phone'                   => $primaryPersonalFields['phone'] ?? $targetSingleCase->phone,
                        'phones'                  => !empty($primaryPersonalFields['phones']) ? $primaryPersonalFields['phones'] : $targetSingleCase->phones,
                        'address'                 => $primaryPersonalFields['address'] ?? $targetSingleCase->address,
                        'marital_status'          => $primaryPersonalFields['marital_status'] ?? $targetSingleCase->marital_status,
                        'has_children'            => $primaryPersonalFields['has_children'] ?? $targetSingleCase->has_children,
                        'children_count'          => $primaryPersonalFields['children_count'] ?? $targetSingleCase->children_count,
                        'spouse_info'             => $primaryPersonalFields['spouse_info'] ?? $targetSingleCase->spouse_info,
                        'spouse_name'             => $primaryPersonalFields['spouse_name'] ?? $targetSingleCase->spouse_name,
                        'spouse_phone'            => $primaryPersonalFields['spouse_phone'] ?? $targetSingleCase->spouse_phone,
                        'partner_name'            => $primaryPersonalFields['partner_name'] ?? $targetSingleCase->partner_name,
                        'partner_phone'           => $primaryPersonalFields['partner_phone'] ?? $targetSingleCase->partner_phone,
                        'partner_info'            => $primaryPersonalFields['partner_info'] ?? $targetSingleCase->partner_info,
                        'dependents'              => $primaryPersonalFields['dependents'] ?? $targetSingleCase->dependents,
                        'holding_facility'        => $primaryPersonalFields['holding_facility'] ?? $targetSingleCase->holding_facility,
                        'arrest_date'             => $primaryPersonalFields['arrest_date'] ?? $targetSingleCase->arrest_date,
                        'arrest_time'             => $primaryPersonalFields['arrest_time'] ?? $targetSingleCase->arrest_time,
                        'operation_code'          => $validated['operation_code'] ?? $targetSingleCase->operation_code,
                        'operation_code_name'     => $validated['operation_code_name'] ?? $targetSingleCase->operation_code_name,
                        'search_warrant_document' => $sharedFields['search_warrant_document'] ?? $targetSingleCase->search_warrant_document,
                        'raid_location'           => !empty($primaryPersonalFields['raid_location']) ? $primaryPersonalFields['raid_location'] : $targetSingleCase->raid_location,
                        'offense_description'     => !empty($primaryPersonalFields['offense_description']) ? $primaryPersonalFields['offense_description'] : $targetSingleCase->offense_description,
                        'description_of_exhibits' => $targetSingleCase->description_of_exhibits,
                        'height_unit'             => $primaryPersonalFields['height_unit'] ?? $targetSingleCase->height_unit,
                        'arresting_officer'       => !empty($validated['arresting_officer']) ? $validated['arresting_officer'] : $targetSingleCase->arresting_officer,
                        'submitting_officer'      => !empty($validated['submitting_officer']) ? $validated['submitting_officer'] : $targetSingleCase->submitting_officer,
                        'mugshot_front'           => $mugFront,
                        'mugshot_left'            => $mugLeft,
                        'mugshot_right'           => $mugRight,
                        'priority'                => $validated['priority'] ?? ($targetSingleCase->priority ?: 'HIGH'),
                        'status'                  => $resolvedStatus,
                    ];

                    $targetSingleCase->update($updatePayload);

                    CaseSuspect::where('single_suspect_case_id', $targetSingleCase->id)->update([
                        'suspect_name'        => $primarySuspectName,
                        'suspect_alias'       => $updatePayload['suspect_alias'],
                        'ghana_card'          => $updatePayload['ghana_card'],
                        'gender'              => $updatePayload['gender'],
                        'dob'                 => $updatePayload['dob'],
                        'age'                 => $updatePayload['age'],
                        'height'              => $updatePayload['height'],
                        'height_unit'         => $updatePayload['height_unit'],
                        'eye_color'           => $updatePayload['eye_color'],
                        'nationality'         => $updatePayload['nationality'],
                        'pob'                 => $updatePayload['pob'],
                        'phone'               => $updatePayload['phone'],
                        'phones'              => $updatePayload['phones'],
                        'address'             => $updatePayload['address'],
                        'marital_status'      => $updatePayload['marital_status'],
                        'has_children'        => $updatePayload['has_children'],
                        'children_count'      => $updatePayload['children_count'],
                        'spouse_info'         => $updatePayload['spouse_info'],
                        'spouse_name'         => $updatePayload['spouse_name'],
                        'spouse_phone'        => $updatePayload['spouse_phone'],
                        'partner_name'        => $updatePayload['partner_name'],
                        'partner_phone'       => $updatePayload['partner_phone'],
                        'partner_info'        => $updatePayload['partner_info'],
                        'dependents'          => $updatePayload['dependents'],
                        'holding_facility'    => $updatePayload['holding_facility'],
                        'raid_location'       => $updatePayload['raid_location'],
                        'offense_description' => $updatePayload['offense_description'],
                        'arrest_date'         => $updatePayload['arrest_date'],
                        'arrest_time'         => $updatePayload['arrest_time'],
                        'mugshot_front'       => $updatePayload['mugshot_front'],
                        'mugshot_left'        => $updatePayload['mugshot_left'],
                        'mugshot_right'       => $updatePayload['mugshot_right'],
                    ]);
                    $targetSuspectId = CaseSuspect::where('single_suspect_case_id', $targetSingleCase->id)->value('id');
                }

                $targetCaseModel = $isMultiTarget ? $targetMultiCase : $targetSingleCase;
                $targetForeignCol = $isMultiTarget ? 'multiple_suspect_case_id' : 'single_suspect_case_id';
                $resolvedAssocOwner = $isMultiTarget ? $jointSuspectNameLabel : $primarySuspectName;

                // Ensure all exhibits on the existing case and any selected unassigned exhibits point here
                // while strictly maintaining existing Exhibit and LaboratoryExaminationResult rows.
                $linkedIdsList = array_values(array_unique(array_filter(array_map('intval', $docketData['linked_exhibit_ids'] ?? []))));
                foreach ($linkedIdsList as $lId) {
                    $lex = Exhibit::find($lId);
                    if (!$lex) {
                        continue;
                    }
                    $oldSingleId = $lex->single_suspect_case_id;
                    $lex->update([
                        'case_type'                => $isMultiTarget ? 'MULTIPLE' : 'SINGLE',
                        'single_suspect_case_id'   => $isMultiTarget ? null : $targetSingleCase->id,
                        'multiple_suspect_case_id' => $isMultiTarget ? $targetMultiCase->id : null,
                        'suspect_id'               => $targetSuspectId,
                        'associated_person'        => $resolvedAssocOwner,
                    ]);
                    if ($oldSingleId && !$isMultiTarget && (int) $oldSingleId !== (int) $targetSingleCase->id) {
                        $oldPlaceholder = SingleSuspectCase::find($oldSingleId);
                        if ($oldPlaceholder
                            && ForensicCase::isUnassignedSuspectName($oldPlaceholder->suspect_name)
                            && Exhibit::where('single_suspect_case_id', $oldSingleId)->count() === 0
                        ) {
                            $oldPlaceholder->delete();
                        }
                    }
                }

                Exhibit::where($targetForeignCol, $targetCaseModel->id)->update([
                    'associated_person' => $resolvedAssocOwner,
                    'suspect_id'        => $targetSuspectId,
                ]);

                if (!$isMultiTarget && $targetSuspectId) {
                    LaboratoryExaminationResult::where('single_suspect_case_id', $targetSingleCase->id)
                        ->whereNull('suspect_id')
                        ->update(['suspect_id' => $targetSuspectId]);
                    \App\Models\DpfReport::where(function ($q) use ($targetSingleCase, $existingCaseNumber) {
                        $q->where('single_suspect_case_id', $targetSingleCase->id)
                          ->orWhere('case_number', $existingCaseNumber);
                    })->update([
                        'single_suspect_case_id' => $targetSingleCase->id,
                        'suspect_id'             => $targetSuspectId,
                    ]);
                }

                $wrappedCase = ForensicCase::where('case_number', $existingCaseNumber)->first();

                if ($user && $wrappedCase) {
                    AuditLogger::log(
                        'CASE_UPDATED',
                        "Suspect(s) linked and Case {$existingCaseNumber} updated by " . $user->fullname . ($submittedUnitName ? " ({$submittedUnitName})" : ''),
                        [
                            'case_number'    => $existingCaseNumber,
                            'auditable_type' => get_class($wrappedCase->underlyingRecord ?? $wrappedCase),
                            'auditable_id'   => $wrappedCase->id,
                            'new_values'     => [
                                'case_number'  => $existingCaseNumber,
                                'title'        => $wrappedCase->title,
                                'suspect_name' => $wrappedCase->suspect_name,
                            ],
                            'user'           => $user,
                        ]
                    );
                }

                return $wrappedCase;
            });

            Cache::forget($lockKey);
        } catch (\Throwable $e) {
            Cache::forget($lockKey);
            Log::error('Linking suspect to existing exhibit case failed: ' . $e->getMessage(), [
                'case_number' => $existingCaseNumber,
                'exception'   => $e,
            ]);
            throw $e;
        }

        $preliminaryReport = null;
        if ($case) {
            $prelimRecord = \App\Models\SuspectPreliminaryReport::forCase($case->case_number, $case->id)->first();
            if ($prelimRecord) {
                $preliminaryReport = [
                    'id'          => $prelimRecord->id,
                    'filename'    => $prelimRecord->original_filename,
                    'url'         => $prelimRecord->url,
                    'file_path'   => $prelimRecord->file_path,
                    'notes'       => $prelimRecord->notes,
                    'case_number' => $case->case_number,
                ];
            }
        }

        return [
            'success'              => true,
            'message'              => "Suspect linked to Case {$case->case_number} and existing case updated.",
            'case'                 => $case,
            'suspects_count'       => count($allSuspects),
            'reused_existing_case' => true,
            'preliminary_report'   => $preliminaryReport,
        ];
    }

    /**
     * Update case data and correct mistakes with strict Unit RBAC permission guards.
     * Fix Issue 3: SchemaCache::hasColumn.
     * Fix Issue 4: Decode docket_data once.
     * Fix Issue 11: LaboratoryExamService.
     * Fix Issue 28: authorizeCaseWrite check.
     */
    public function updateCase(ForensicCase $case, Request $request): array
    {
        $user = Auth::user();
        $userUnit = strtolower(trim($user->unit ?? ''));
        $isHead = !empty($user->is_head);

        $isCrimeSceneUnit = $isHead || str_contains($userUnit, 'crime') || str_contains($userUnit, 'scene') || str_contains($userUnit, 'support') || empty($userUnit);
        $isLabUnit = $isHead || str_contains($userUnit, 'lab') || str_contains($userUnit, 'chemistry') || str_contains($userUnit, 'narcotics');

        $docketData = $this->decodeDocket($case);

        // Reject unauthorized cross-unit edits
        if (!$isLabUnit && ($request->filled('confirmatory_test_result') || $request->filled('confirmatory_substance_identification'))) {
            return [
                'success' => false,
                'message' => 'Access Denied: Crime Scene Support officers cannot modify Laboratory Analysis examination results.',
                'code'    => 403,
            ];
        }

        if (!$isCrimeSceneUnit && ($request->filled('suspect_name') || $request->filled('arrest_date') || $request->filled('seizure_name_drug'))) {
            return [
                'success' => false,
                'message' => 'Access Denied: Laboratory Analysts cannot modify Crime Scene arrest and seizure records.',
                'code'    => 403,
            ];
        }

        // Snapshot existing suspect names before modifications
        $oldSuspectNamesMap = [];
        $rawSuspectsCount = !empty($docketData['suspects']) && is_array($docketData['suspects']) ? count($docketData['suspects']) : 0;
        if ($rawSuspectsCount > 0) {
            foreach ($docketData['suspects'] as $idx => $s) {
                if (!empty($s['suspect_name'])) {
                    $nVal = trim($s['suspect_name']);
                    if (str_contains($nVal, ',') && $rawSuspectsCount > 1) {
                        $nVal = trim(explode(',', $nVal)[0]);
                    }
                    $oldSuspectNamesMap[$idx] = $nVal;
                }
            }
        }
        $rawPrimary = trim($case->suspect_name ?? ($oldSuspectNamesMap[0] ?? ''));
        if (str_contains($rawPrimary, ',') && $rawSuspectsCount > 1) {
            $rawPrimary = trim(explode(',', $rawPrimary)[0]);
        }
        $oldPrimaryName = $rawPrimary ?: ($oldSuspectNamesMap[0] ?? '');

        // 1. CRIME SCENE SUPPORT FIELDS
        if ($isCrimeSceneUnit) {
            if ($request->filled('title')) $case->title = trim($request->input('title'));
            if ($request->filled('priority')) $case->priority = trim($request->input('priority'));
            if ($request->filled('location')) $case->location = trim($request->input('location'));
            if ($request->filled('operation_type')) {
                $opVal = trim($request->input('operation_type'));
                $case->operation_type = $opVal;
                $case->operation_code_name = $opVal;
                $case->operation_code = $opVal;
                $docketData['operation_type'] = $opVal;
            }
            if ($request->filled('arresting_officer')) {
                $case->arresting_officer = trim($request->input('arresting_officer'));
                $case->submitting_officer = $case->arresting_officer;
            } elseif ($request->filled('submitting_officer')) {
                $case->arresting_officer = trim($request->input('submitting_officer'));
                $case->submitting_officer = $case->arresting_officer;
            }

            if ($request->has('suspect_name')) {
                $newSingleName = trim($request->input('suspect_name'));
                if (!empty($oldPrimaryName) && !empty($newSingleName) && $oldPrimaryName !== $newSingleName) {
                    $curTitle = $case->title ?? '';
                    if (!empty($curTitle)) {
                        $pattern = '/\b' . preg_quote($oldPrimaryName, '/') . '\b/u';
                        if (preg_match($pattern, $curTitle)) {
                            $curTitle = preg_replace($pattern, $newSingleName, $curTitle);
                        } elseif (str_contains($curTitle, $oldPrimaryName)) {
                            $curTitle = str_replace($oldPrimaryName, $newSingleName, $curTitle);
                        }
                        $case->title = $curTitle;
                    }
                }
                $case->suspect_name = $newSingleName;
                if (!isset($docketData['suspects'][0])) $docketData['suspects'][0] = [];
                $docketData['suspects'][0]['suspect_name'] = $newSingleName;
            }
            if ($request->has('suspect_alias')) $case->suspect_alias = $request->input('suspect_alias');
            if ($request->has('ghana_card')) $case->ghana_card = $request->input('ghana_card');
            if ($request->has('gender')) $case->gender = $request->input('gender');
            if ($request->has('age')) $case->age = $request->input('age') !== '' ? (int)$request->input('age') : null;
            if ($request->has('phone')) $case->phone = $request->input('phone');
            if ($request->has('phones') || $request->has('additional_phones')) {
                $rawPh = $request->input('phones');
                if (is_string($rawPh)) {
                    $rawPh = json_decode($rawPh, true);
                }
                if (empty($rawPh) && $request->has('additional_phones')) {
                    $rawPh = [];
                    $pNum = $request->input('phone') ?? $case->phone;
                    $pLbl = $request->input('phone_label') ?? ($request->input('phone_labels.0') ?? 'Primary');
                    if (!empty($pNum)) {
                        $rawPh[] = ['label' => $pLbl ?: 'Primary', 'number' => trim((string)$pNum)];
                    }
                    $addP = $request->input('additional_phones', []);
                    $addL = $request->input('additional_phone_labels', []);
                    if (is_array($addP)) {
                        foreach ($addP as $idx => $num) {
                            if (!empty($num)) {
                                $rawPh[] = ['label' => $addL[$idx] ?? 'Alternate', 'number' => trim((string)$num)];
                            }
                        }
                    }
                }
                $case->phones = !empty($rawPh) ? $rawPh : null;
                $docketData['phones'] = $case->phones;
                if (!isset($docketData['suspects'][0])) $docketData['suspects'][0] = [];
                $docketData['suspects'][0]['phone'] = $case->phone;
                $docketData['suspects'][0]['phones'] = $case->phones;
            }
            if ($request->has('address')) $case->address = $request->input('address');
            if ($request->has('offense_description')) $case->offense_description = $request->input('offense_description');
            if ($request->has('arrest_date')) $case->arrest_date = $request->input('arrest_date');
            if ($request->has('arrest_time')) $case->arrest_time = $request->input('arrest_time');
            if ($request->has('raid_location')) $case->raid_location = $request->input('raid_location');
            if ($request->has('holding_facility')) $case->holding_facility = $request->input('holding_facility');

            if ($request->has('dob')) {
                $dobInputVal = trim($request->input('dob'));
                $case->dob = $dobInputVal;
                $docketData['suspects'][0]['dob'] = $dobInputVal;
                $docketData['dob'] = $dobInputVal;

                $calcAgeFromDob = null;
                if (!empty($dobInputVal)) {
                    try {
                        $calcAgeFromDob = Carbon::parse($dobInputVal)->age;
                    } catch (\Exception $e) {
                        $calcAgeFromDob = null;
                    }
                }
                if ($calcAgeFromDob !== null) {
                    $case->age = $calcAgeFromDob;
                    $docketData['suspects'][0]['age'] = $calcAgeFromDob;
                    $docketData['age'] = $calcAgeFromDob;
                }
            }
            if ($request->has('height')) {
                $case->height = $request->input('height');
                $docketData['suspects'][0]['height'] = $request->input('height');
            }
            if ($request->has('height_unit')) {
                $case->height_unit = $request->input('height_unit');
                $docketData['suspects'][0]['height_unit'] = $request->input('height_unit');
            }
            if ($request->has('eye_color')) {
                $case->eye_color = $request->input('eye_color');
                $docketData['suspects'][0]['eye_color'] = $request->input('eye_color');
            }
            if ($request->has('nationality')) {
                $case->nationality = $request->input('nationality');
                $docketData['suspects'][0]['nationality'] = $request->input('nationality');
            }
            if ($request->has('place_of_birth')) {
                $pob = $request->input('place_of_birth');
                $case->place_of_birth = $pob;
                $case->pob = $pob;
                $docketData['suspects'][0]['place_of_birth'] = $pob;
                $docketData['suspects'][0]['pob'] = $pob;
                $docketData['place_of_birth'] = $pob;
                $docketData['pob'] = $pob;
            }
            if ($request->has('hometown')) {
                $ht = $request->input('hometown');
                $case->hometown = $ht;
                $docketData['suspects'][0]['hometown'] = $ht;
                $docketData['hometown'] = $ht;
            }
            if ($request->has('marital_status')) {
                $case->marital_status = $request->input('marital_status');
                $docketData['suspects'][0]['marital_status'] = $request->input('marital_status');
            }
            if ($request->has('children_count')) {
                $case->children_count = $request->input('children_count');
                $docketData['suspects'][0]['children_count'] = $request->input('children_count');
            }

            // Mugshots
            $mugshotsObj = [];
            if ($request->has('mugshot_front')) {
                $mFront = $request->input('mugshot_front');
                $mugshotsObj['front'] = $mFront;
                $mugshotsObj['front-profile'] = $mFront;
                $case->mugshot_front = $mFront;
            }
            if ($request->has('mugshot_left')) {
                $mLeft = $request->input('mugshot_left');
                $mugshotsObj['left'] = $mLeft;
                $mugshotsObj['left-profile'] = $mLeft;
                $case->mugshot_left = $mLeft;
            }
            if ($request->has('mugshot_right')) {
                $mRight = $request->input('mugshot_right');
                $mugshotsObj['right'] = $mRight;
                $mugshotsObj['right-profile'] = $mRight;
                $case->mugshot_right = $mRight;
            }
            if ($request->has('mugshot_front') || $request->has('mugshot_left') || $request->has('mugshot_right')) {
                $docketData['mugshots'] = array_merge($docketData['mugshots'] ?? [], $mugshotsObj);
                if (empty($docketData['suspects'])) {
                    $docketData['suspects'] = [['suspect_name' => $case->suspect_name]];
                }
                $docketData['suspects'][0]['mugshots'] = array_merge($docketData['suspects'][0]['mugshots'] ?? [], $mugshotsObj);
                if ($request->has('mugshot_front')) {
                    $docketData['mugshots']['front'] = $mugshotsObj['front'];
                    $docketData['mugshots']['front-profile'] = $mugshotsObj['front'];
                    $docketData['mugshot_front'] = $mugshotsObj['front'];
                    $docketData['suspects'][0]['mugshots']['front'] = $mugshotsObj['front'];
                    $docketData['suspects'][0]['mugshots']['front-profile'] = $mugshotsObj['front'];
                    $docketData['suspects'][0]['mugshot_front'] = $mugshotsObj['front'];
                }
                if ($request->has('mugshot_left')) {
                    $docketData['mugshots']['left'] = $mugshotsObj['left'];
                    $docketData['mugshots']['left-profile'] = $mugshotsObj['left'];
                    $docketData['mugshot_left'] = $mugshotsObj['left'];
                    $docketData['suspects'][0]['mugshots']['left'] = $mugshotsObj['left'];
                    $docketData['suspects'][0]['mugshots']['left-profile'] = $mugshotsObj['left'];
                    $docketData['suspects'][0]['mugshot_left'] = $mugshotsObj['left'];
                }
                if ($request->has('mugshot_right')) {
                    $docketData['mugshots']['right'] = $mugshotsObj['right'];
                    $docketData['mugshots']['right-profile'] = $mugshotsObj['right'];
                    $docketData['mugshot_right'] = $mugshotsObj['right'];
                    $docketData['suspects'][0]['mugshots']['right'] = $mugshotsObj['right'];
                    $docketData['suspects'][0]['mugshots']['right-profile'] = $mugshotsObj['right'];
                    $docketData['suspects'][0]['mugshot_right'] = $mugshotsObj['right'];
                }
            }

            if ($request->has('evidence_photos')) {
                $evPhotos = $request->input('evidence_photos');
                if (is_string($evPhotos)) {
                    $evPhotos = json_decode($evPhotos, true) ?: [];
                }
                if (!is_array($evPhotos)) $evPhotos = [];
                $case->evidence_photos = array_values($evPhotos);
                $docketData['evidence_photos'] = array_values($evPhotos);
                if (!empty($docketData['seizures'][0])) {
                    $docketData['seizures'][0]['evidence_photos'] = array_values($evPhotos);
                }
            }

            if ($request->has('seizure_name_drug')) $case->seizure_name_drug = $request->input('seizure_name_drug');
            if ($request->has('seizure_name')) $case->seizure_name = $request->input('seizure_name');
            if ($request->has('exhibit_category')) $case->exhibit_category = $request->input('exhibit_category');
            if ($request->has('seizure_quantity')) $case->seizure_quantity = $request->input('seizure_quantity');
            if ($request->has('seizure_weight')) $case->seizure_weight = $request->input('seizure_weight');
            if ($request->has('seizure_unit')) $case->seizure_unit = $request->input('seizure_unit');
            if ($request->has('seizure_packaging')) $case->seizure_packaging = $request->input('seizure_packaging');
            if ($request->has('seizure_reason')) $case->seizure_reason = $request->input('seizure_reason');
            if ($request->has('investigating_officer')) $case->investigating_officer = $request->input('investigating_officer');
            if ($request->has('source_requesting_unit')) $case->source_requesting_unit = $request->input('source_requesting_unit');
            if ($request->has('contents')) {
                $case->contents = $request->input('contents');
                if (!empty($docketData['seizures'][0])) {
                    $docketData['seizures'][0]['contents'] = $request->input('contents');
                }
            }
            if ($request->has('description_of_exhibits')) {
                $case->description_of_exhibits = $request->input('description_of_exhibits');
                $docketData['description_of_exhibits'] = $case->description_of_exhibits;
                if ($case->underlyingRecord && SchemaCache::hasColumn($case->underlyingRecord->getTable(), 'description_of_exhibits')) {
                    $case->underlyingRecord->description_of_exhibits = $case->description_of_exhibits;
                }
            }

            if (empty($docketData['suspects']) || !is_array($docketData['suspects'])) {
                $docketData['suspects'] = [['suspect_name' => $case->suspect_name]];
            }
            if ($request->has('suspect_name')) $docketData['suspects'][0]['suspect_name'] = $case->suspect_name;
            if ($request->has('suspect_alias')) $docketData['suspects'][0]['suspect_alias'] = $request->input('suspect_alias');
            if ($request->has('ghana_card')) $docketData['suspects'][0]['ghana_card'] = $request->input('ghana_card');
            if ($request->has('phone')) $docketData['suspects'][0]['phone'] = $request->input('phone');
            if ($request->has('address')) $docketData['suspects'][0]['address'] = $request->input('address');
            if ($request->has('offense_description')) $docketData['suspects'][0]['offense'] = $request->input('offense_description');
            if ($request->has('arrest_date')) $docketData['suspects'][0]['date_of_arrest'] = $request->input('arrest_date');
            if ($request->has('arrest_time')) $docketData['suspects'][0]['time_of_arrest'] = $request->input('arrest_time');

            $szDrugName = $case->seizure_name_drug ?: $case->seizure_name;
            $hasSeizureInfo = !empty($szDrugName) || !empty($request->input('seizure_name')) || !empty($request->input('seizure_name_drug')) || $request->filled('exhibit_category');
            if ($hasSeizureInfo || (!empty($docketData['seizures']) && is_array($docketData['seizures']) && count($docketData['seizures']) > 0)) {
                if (empty($docketData['seizures']) || !is_array($docketData['seizures'])) {
                    $docketData['seizures'] = [[]];
                }
                if (!empty($szDrugName)) {
                    $docketData['seizures'][0]['seizure_name'] = $szDrugName;
                    $docketData['seizures'][0]['seizure_name_drug'] = $szDrugName;
                }
                if ($request->has('exhibit_category')) $docketData['seizures'][0]['category'] = $request->input('exhibit_category');
                if ($request->has('seizure_quantity')) $docketData['seizures'][0]['seizure_quantity'] = $request->input('seizure_quantity');
                if ($request->has('seizure_weight')) $docketData['seizures'][0]['seizure_weight'] = $request->input('seizure_weight');
                if ($request->has('seizure_unit')) $docketData['seizures'][0]['seizure_unit'] = $request->input('seizure_unit');
                if ($request->has('presumptive_result')) {
                    $case->presumptive_test_result = $request->input('presumptive_result');
                    $docketData['seizures'][0]['presumptive_result'] = $request->input('presumptive_result');
                }
            }
        }

        // 2. LAB ANALYSIS FIELDS
        if ($isLabUnit || $request->has('confirmatory_test_result') || $request->has('confirmatory_result') || $request->has('laboratory_number') || $request->has('confirmatory_substance_identification')) {
            if ($request->has('laboratory_number')) $case->laboratory_number = $request->input('laboratory_number');
            if ($request->has('presumptive_result')) $case->presumptive_test_result = $request->input('presumptive_result');
            if ($request->has('confirmatory_test_result') || $request->has('confirmatory_result')) {
                $cVal = $request->input('confirmatory_test_result') ?? $request->input('confirmatory_result');
                if (!empty($cVal)) {
                    $case->confirmatory_test_result = $cVal;
                }
            }
            if ($request->has('sample_weight_description')) $case->sample_weight_description = $request->input('sample_weight_description');
            // Fix Issue 3: SchemaCache::hasColumn
            if ($request->has('sample_weight_units') && SchemaCache::hasColumn('cases', 'sample_weight_units')) {
                $case->sample_weight_units = $request->input('sample_weight_units');
            }
            if ($request->has('sample_weight_gross')) $case->sample_weight_gross = $request->input('sample_weight_gross');
            if ($request->has('sample_weight_taken')) $case->sample_weight_taken = $request->input('sample_weight_taken');
            if ($request->has('sample_weight_reserved')) $case->sample_weight_reserved = $request->input('sample_weight_reserved');
            if ($request->has('confirmatory_substance_identification')) $case->confirmatory_substance_identification = $request->input('confirmatory_substance_identification');
            if ($request->has('confirmatory_test_methods')) $case->confirmatory_test_methods = $request->input('confirmatory_test_methods');
            if ($request->has('confirmatory_specifications')) $case->confirmatory_specifications = $request->input('confirmatory_specifications');
            if ($request->has('confirmatory_remark')) $case->confirmatory_remark = $request->input('confirmatory_remark');
            if ($request->has('remark')) $case->confirmatory_remark = $request->input('remark');
            if ($request->has('findings') || $request->has('notes')) {
                $case->laboratory_analytical_findings = $request->input('findings') ?? $request->input('notes');
            }

            if (!isset($docketData['lab_examination'])) $docketData['lab_examination'] = [];
            if ($request->has('laboratory_number')) $docketData['lab_examination']['laboratory_number'] = $request->input('laboratory_number');
            if ($request->has('presumptive_result')) $docketData['lab_examination']['presumptive_result'] = $request->input('presumptive_result');
            if ($request->has('confirmatory_test_result') || $request->has('confirmatory_result')) {
                $cVal = $request->input('confirmatory_test_result') ?? $request->input('confirmatory_result');
                if (!empty($cVal)) {
                    $docketData['lab_examination']['confirmatory_result'] = $cVal;
                    if (isset($docketData['seizures']) && is_array($docketData['seizures'])) {
                        foreach ($docketData['seizures'] as &$sz) {
                            $sz['confirmatory_result'] = $cVal;
                        }
                    }
                }
            }
            if ($request->has('sample_weight_description')) $docketData['lab_examination']['sample_weight']['description'] = $request->input('sample_weight_description');
            if ($request->has('sample_weight_units')) $docketData['lab_examination']['sample_weight']['units_packaging'] = $request->input('sample_weight_units');
            if ($request->has('sample_weight_gross')) $docketData['lab_examination']['sample_weight']['gross_weight'] = $request->input('sample_weight_gross');
            if ($request->has('sample_weight_taken')) $docketData['lab_examination']['sample_weight']['sample_taken'] = $request->input('sample_weight_taken');
            if ($request->has('sample_weight_reserved')) $docketData['lab_examination']['sample_weight']['reserved_samples'] = $request->input('sample_weight_reserved');
            if ($request->has('confirmatory_substance_identification')) $docketData['lab_examination']['substance_identification'] = $request->input('confirmatory_substance_identification');
            if ($request->has('confirmatory_test_methods')) $docketData['lab_examination']['test_methods'] = $request->input('confirmatory_test_methods');
            if ($request->has('confirmatory_specifications')) $docketData['lab_examination']['specifications'] = $request->input('confirmatory_specifications');
            if ($request->has('confirmatory_remark')) $docketData['lab_examination']['remark'] = $request->input('confirmatory_remark');
            if ($request->has('remark')) $docketData['lab_examination']['remark'] = $request->input('remark');
            if ($request->has('purpose_of_examination')) $docketData['lab_examination']['purpose_of_examination'] = $request->input('purpose_of_examination');
            if ($request->has('preliminary_exam_procedure')) $docketData['lab_examination']['preliminary_exam_procedure'] = $request->input('preliminary_exam_procedure');
            if ($request->has('screening_method')) $docketData['lab_examination']['screening_method'] = $request->input('screening_method');
            if ($request->has('disposition_of_exhibit')) $docketData['lab_examination']['disposition_of_exhibit'] = $request->input('disposition_of_exhibit');
            if ($request->has('findings') || $request->has('notes')) {
                $docketData['lab_examination']['findings'] = $request->input('findings') ?? $request->input('notes');
            }
        }

        // 3. MULTI-SUSPECT DATA SYNC
        if ($request->filled('suspects_data_json')) {
            $payload = json_decode($request->input('suspects_data_json'), true);
            $suspectsArray = isset($payload['suspects']) ? $payload['suspects'] : (is_array($payload) ? $payload : []);
            $exhibitsArray = $payload['exhibits'] ?? [];
            $labsArray     = $payload['labs'] ?? [];

            if (empty($docketData['suspects']) || !is_array($docketData['suspects'])) {
                $docketData['suspects'] = [];
            }
            if (empty($docketData['seizures']) || !is_array($docketData['seizures'])) {
                $docketData['seizures'] = [];
            }
            if (empty($docketData['lab_examinations_list']) || !is_array($docketData['lab_examinations_list'])) {
                $docketData['lab_examinations_list'] = [];
            }

            // A. Update Suspect Personal Records (Crime Scene Support)
            if ($isCrimeSceneUnit && !empty($suspectsArray)) {
                $curTitle = $request->filled('title') ? trim($request->input('title')) : trim($case->title ?? '');
                foreach ($suspectsArray as $sIdx => $spData) {
                    $sName = trim($spData['suspect_name'] ?? '');
                    if (empty($sName)) continue;

                    $oldSName = $oldSuspectNamesMap[$sIdx] ?? ($sIdx === 0 ? $oldPrimaryName : '');
                    if (str_contains($oldSName, ',') && count($suspectsArray) > 1) {
                        $oldSName = trim(explode(',', $oldSName)[0]);
                    }

                    if (!isset($docketData['suspects'][$sIdx])) {
                        $docketData['suspects'][$sIdx] = [];
                    }

                    if (!empty($oldSName) && $oldSName !== $sName && !empty($curTitle)) {
                        $pattern = '/\b' . preg_quote($oldSName, '/') . '\b/u';
                        if (preg_match($pattern, $curTitle)) {
                            $curTitle = preg_replace($pattern, $sName, $curTitle);
                        }
                        $case->title = $curTitle;
                    }

                    $docketData['suspects'][$sIdx]['suspect_name']        = $sName;
                    $docketData['suspects'][$sIdx]['suspect_alias']       = $spData['suspect_alias'] ?? '';
                    $docketData['suspects'][$sIdx]['alias']               = $spData['suspect_alias'] ?? '';
                    $docketData['suspects'][$sIdx]['ghana_card']          = $spData['ghana_card'] ?? '';
                    $docketData['suspects'][$sIdx]['gender']              = $spData['gender'] ?? 'Male';
                    $docketData['suspects'][$sIdx]['age']                 = $spData['age'] ?? '';
                    $docketData['suspects'][$sIdx]['phone']               = $spData['phone'] ?? '';
                    $docketData['suspects'][$sIdx]['address']             = $spData['address'] ?? '';
                    $docketData['suspects'][$sIdx]['offense']             = $spData['offense_description'] ?? '';
                    $docketData['suspects'][$sIdx]['date_of_arrest']      = $spData['arrest_date'] ?? '';
                    $docketData['suspects'][$sIdx]['time_of_arrest']      = $spData['arrest_time'] ?? '';
                    $docketData['suspects'][$sIdx]['location_of_arrest']  = $spData['raid_location'] ?? '';
                    $docketData['suspects'][$sIdx]['holding_facility']    = $spData['holding_facility'] ?? '';
                    $spDob = trim($spData['dob'] ?? '');
                    $spCalcAge = null;
                    if (!empty($spDob)) {
                        try {
                            $spCalcAge = Carbon::parse($spDob)->age;
                        } catch (\Exception $e) {
                            $spCalcAge = null;
                        }
                    }
                    if ($spCalcAge === null && isset($spData['age']) && $spData['age'] !== '') {
                        $spCalcAge = (int)$spData['age'];
                    }

                    $docketData['suspects'][$sIdx]['dob']                  = $spDob;
                    $docketData['suspects'][$sIdx]['age']                  = $spCalcAge !== null ? $spCalcAge : ($spData['age'] ?? '');
                    $docketData['suspects'][$sIdx]['height']               = $spData['height'] ?? '';
                    $docketData['suspects'][$sIdx]['height_unit']          = $spData['height_unit'] ?? 'CM';
                    $docketData['suspects'][$sIdx]['eye_color']            = $spData['eye_color'] ?? '';
                    $docketData['suspects'][$sIdx]['nationality']          = $spData['nationality'] ?? '';
                    $docketData['suspects'][$sIdx]['place_of_birth']       = $spData['place_of_birth'] ?? ($spData['pob'] ?? '');
                    $docketData['suspects'][$sIdx]['pob']                  = $spData['place_of_birth'] ?? ($spData['pob'] ?? '');
                    $docketData['suspects'][$sIdx]['hometown']             = $spData['hometown'] ?? '';
                    $docketData['suspects'][$sIdx]['marital_status']       = $spData['marital_status'] ?? '';
                    $docketData['suspects'][$sIdx]['children_count']       = $spData['children_count'] ?? '';
                    $docketData['suspects'][$sIdx]['has_children']         = !empty($spData['has_children']) || ((int)($spData['children_count'] ?? 0) > 0);
                    $docketData['suspects'][$sIdx]['spouse_name']          = $spData['spouse_name'] ?? ($spData['partner_name'] ?? '');
                    $docketData['suspects'][$sIdx]['spouse_phone']         = $spData['spouse_phone'] ?? ($spData['partner_phone'] ?? '');
                    $docketData['suspects'][$sIdx]['spouse_info']          = $spData['spouse_info'] ?? ($spData['partner_info'] ?? '');
                    $docketData['suspects'][$sIdx]['spouse_summary']       = $spData['spouse_summary'] ?? '';

                    $exMFront = $docketData['suspects'][$sIdx]['mugshot_front'] ?? ($sIdx === 0 ? ($case->mugshot_front ?? '') : '');
                    $exMLeft  = $docketData['suspects'][$sIdx]['mugshot_left']  ?? ($sIdx === 0 ? ($case->mugshot_left  ?? '') : '');
                    $exMRight = $docketData['suspects'][$sIdx]['mugshot_right'] ?? ($sIdx === 0 ? ($case->mugshot_right ?? '') : '');

                    $rawMFront = $spData['mugshots']['front'] ?? ($spData['mugshots']['front-profile'] ?? ($spData['mugshot_front'] ?? ''));
                    $rawMLeft  = $spData['mugshots']['left-profile'] ?? ($spData['mugshots']['left'] ?? ($spData['mugshot_left'] ?? ''));
                    $rawMRight = $spData['mugshots']['right-profile'] ?? ($spData['mugshots']['right'] ?? ($spData['mugshot_right'] ?? ''));

                    $mFront = !empty($rawMFront) ? $rawMFront : $exMFront;
                    $mLeft  = !empty($rawMLeft)  ? $rawMLeft  : $exMLeft;
                    $mRight = !empty($rawMRight) ? $rawMRight : $exMRight;

                    if (!empty($mFront) || !empty($mLeft) || !empty($mRight)) {
                        $docketData['suspects'][$sIdx]['mugshots'] = [
                            'front'         => $mFront,
                            'front-profile' => $mFront,
                            'left'          => $mLeft,
                            'left-profile'  => $mLeft,
                            'right'         => $mRight,
                            'right-profile' => $mRight,
                        ];
                        if (!empty($mFront)) $docketData['suspects'][$sIdx]['mugshot_front'] = $mFront;
                        if (!empty($mLeft))  $docketData['suspects'][$sIdx]['mugshot_left']  = $mLeft;
                        if (!empty($mRight)) $docketData['suspects'][$sIdx]['mugshot_right'] = $mRight;

                        if ($sIdx === 0) {
                            if (!empty($mFront)) $case->mugshot_front = $mFront;
                            if (!empty($mLeft))  $case->mugshot_left  = $mLeft;
                            if (!empty($mRight)) $case->mugshot_right = $mRight;

                            $docketData['mugshots'] = $docketData['suspects'][0]['mugshots'];
                            if (!empty($mFront)) $docketData['mugshot_front'] = $mFront;
                            if (!empty($mLeft))  $docketData['mugshot_left']  = $mLeft;
                            if (!empty($mRight)) $docketData['mugshot_right'] = $mRight;
                        }
                    }

                    if ($sIdx === 0) {
                        $case->suspect_name = $sName;
                        if (isset($spData['suspect_alias'])) $case->suspect_alias = $spData['suspect_alias'];
                        if (isset($spData['ghana_card'])) $case->ghana_card = $spData['ghana_card'];
                        if (isset($spData['gender'])) $case->gender = $spData['gender'];

                        if (!empty($spDob)) {
                            $case->dob = $spDob;
                            $docketData['dob'] = $spDob;
                        }
                        if ($spCalcAge !== null) {
                            $case->age = $spCalcAge;
                            $docketData['age'] = $spCalcAge;
                        }

                        if (isset($spData['height'])) $case->height = $spData['height'];
                        if (isset($spData['height_unit'])) $case->height_unit = $spData['height_unit'];
                        if (isset($spData['eye_color'])) $case->eye_color = $spData['eye_color'];
                        if (isset($spData['nationality'])) $case->nationality = $spData['nationality'];
                        if (isset($spData['place_of_birth'])) {
                            $case->place_of_birth = $spData['place_of_birth'];
                            $case->pob = $spData['place_of_birth'];
                        }
                        if (isset($spData['marital_status'])) $case->marital_status = $spData['marital_status'];
                        if (isset($spData['children_count'])) $case->children_count = $spData['children_count'];
                        if (isset($spData['spouse_name'])) {
                            $case->spouse_name = $spData['spouse_name'];
                            $case->partner_name = $spData['spouse_name'];
                            $docketData['spouse_name'] = $spData['spouse_name'];
                        }
                        if (isset($spData['spouse_phone'])) {
                            $case->spouse_phone = $spData['spouse_phone'];
                            $case->partner_phone = $spData['spouse_phone'];
                            $docketData['spouse_phone'] = $spData['spouse_phone'];
                        }
                        if (isset($spData['spouse_info'])) {
                            $case->spouse_info = $spData['spouse_info'];
                            $case->partner_info = $spData['spouse_info'];
                            $docketData['spouse_info'] = $spData['spouse_info'];
                        }
                        if (isset($spData['dependents'])) {
                            $case->dependents = $spData['dependents'];
                            $docketData['dependents'] = $spData['dependents'];
                        }

                        if (isset($spData['phone'])) $case->phone = $spData['phone'];
                        if (isset($spData['address'])) $case->address = $spData['address'];
                        if (isset($spData['offense_description'])) $case->offense_description = $spData['offense_description'];
                        if (isset($spData['arrest_date'])) $case->arrest_date = $spData['arrest_date'];
                        if (isset($spData['arrest_time'])) $case->arrest_time = $spData['arrest_time'];
                        if (isset($spData['raid_location'])) $case->raid_location = $spData['raid_location'];
                        if (isset($spData['holding_facility'])) $case->holding_facility = $spData['holding_facility'];
                    }

                    if ($case->underlyingRecord instanceof MultipleSuspectCase) {
                        $existingSuspects = $case->underlyingRecord->suspects()->orderBy('suspect_number')->get();
                        if (isset($existingSuspects[$sIdx])) {
                            $existingSuspects[$sIdx]->update([
                                'suspect_name'        => $sName,
                                'suspect_alias'       => $spData['suspect_alias'] ?? $existingSuspects[$sIdx]->suspect_alias,
                                'ghana_card'          => $spData['ghana_card'] ?? $existingSuspects[$sIdx]->ghana_card,
                                'gender'              => $spData['gender'] ?? $existingSuspects[$sIdx]->gender,
                                'dob'                 => $spDob ?: $existingSuspects[$sIdx]->dob,
                                'age'                 => $spCalcAge !== null ? $spCalcAge : $existingSuspects[$sIdx]->age,
                                'height'              => $spData['height'] ?? $existingSuspects[$sIdx]->height,
                                'height_unit'         => $spData['height_unit'] ?? $existingSuspects[$sIdx]->height_unit,
                                'eye_color'           => $spData['eye_color'] ?? $existingSuspects[$sIdx]->eye_color,
                                'nationality'         => $spData['nationality'] ?? $existingSuspects[$sIdx]->nationality,
                                'pob'                 => $spData['place_of_birth'] ?? ($spData['pob'] ?? $existingSuspects[$sIdx]->pob),
                                'phone'               => $spData['phone'] ?? $existingSuspects[$sIdx]->phone,
                                'address'             => $spData['address'] ?? $existingSuspects[$sIdx]->address,
                                'marital_status'      => $spData['marital_status'] ?? $existingSuspects[$sIdx]->marital_status,
                                'children_count'      => $spData['children_count'] ?? $existingSuspects[$sIdx]->children_count,
                                'has_children'        => !empty($spData['has_children']) || ((int)($spData['children_count'] ?? 0) > 0),
                                'spouse_name'         => $spData['spouse_name'] ?? $existingSuspects[$sIdx]->spouse_name,
                                'spouse_phone'        => $spData['spouse_phone'] ?? $existingSuspects[$sIdx]->spouse_phone,
                                'spouse_info'         => $spData['spouse_info'] ?? $existingSuspects[$sIdx]->spouse_info,
                                'holding_facility'    => $spData['holding_facility'] ?? $existingSuspects[$sIdx]->holding_facility,
                                'raid_location'       => $spData['raid_location'] ?? $existingSuspects[$sIdx]->raid_location,
                                'offense_description' => $spData['offense_description'] ?? $existingSuspects[$sIdx]->offense_description,
                                'arrest_date'         => $spData['arrest_date'] ?? $existingSuspects[$sIdx]->arrest_date,
                                'arrest_time'         => $spData['arrest_time'] ?? $existingSuspects[$sIdx]->arrest_time,
                                'mugshot_front'       => !empty($mFront) ? $mFront : $existingSuspects[$sIdx]->mugshot_front,
                                'mugshot_left'        => !empty($mLeft)  ? $mLeft  : $existingSuspects[$sIdx]->mugshot_left,
                                'mugshot_right'       => !empty($mRight) ? $mRight : $existingSuspects[$sIdx]->mugshot_right,
                            ]);
                        }
                    }
                }

                $allCurrentSuspectNames = array_values(array_filter(array_map(function($s) {
                    return trim($s['suspect_name'] ?? '');
                }, $docketData['suspects'])));

                if (!empty($allCurrentSuspectNames)) {
                    $docketData['full_name'] = implode(', ', $allCurrentSuspectNames);
                    if ($case->underlyingRecord instanceof MultipleSuspectCase) {
                        $case->underlyingRecord->update(['title' => 'Republic vs ' . implode(', ', $allCurrentSuspectNames)]);
                    }
                }
            }

            // B. Update Exhibits Manifest (Crime Scene Support)
            if ($isCrimeSceneUnit) {
                if (empty($exhibitsArray) && !empty($suspectsArray)) {
                    foreach ($suspectsArray as $spData) {
                        if (!empty($spData['exhibit'])) {
                            $exIdx = $spData['exhibit_index'] ?? count($exhibitsArray);
                            $exhibitsArray[$exIdx] = $spData['exhibit'];
                        }
                    }
                }

                if (!empty($exhibitsArray)) {
                    foreach ($exhibitsArray as $eIdx => $exObj) {
                        $szName = $exObj['seizure_name_drug'] ?? ($exObj['seizure_name'] ?? '');
                        if (!isset($docketData['seizures'][$eIdx])) {
                            $docketData['seizures'][$eIdx] = [];
                        }

                        if (!empty($szName)) {
                            $docketData['seizures'][$eIdx]['seizure_name']      = $szName;
                            $docketData['seizures'][$eIdx]['seizure_name_drug'] = $szName;
                        }
                        if (isset($exObj['associated_person'])) $docketData['seizures'][$eIdx]['associated_person'] = $exObj['associated_person'];
                        if (isset($exObj['exhibit_category']))  $docketData['seizures'][$eIdx]['category']          = $exObj['exhibit_category'];
                        if (isset($exObj['seizure_quantity']))  $docketData['seizures'][$eIdx]['seizure_quantity']  = $exObj['seizure_quantity'];
                        if (isset($exObj['seizure_weight']))    $docketData['seizures'][$eIdx]['seizure_weight']    = $exObj['seizure_weight'];
                        if (isset($exObj['seizure_unit']))      $docketData['seizures'][$eIdx]['seizure_unit']      = $exObj['seizure_unit'];
                        if (isset($exObj['seizure_packaging'])) $docketData['seizures'][$eIdx]['seizure_packaging'] = $exObj['seizure_packaging'];
                        if (isset($exObj['seizure_reason']))    $docketData['seizures'][$eIdx]['seizure_reason']    = $exObj['seizure_reason'];
                        if (isset($exObj['investigating_officer'])) $docketData['seizures'][$eIdx]['investigating_officer'] = $exObj['investigating_officer'];
                        if (isset($exObj['source_requesting_unit'])) $docketData['seizures'][$eIdx]['source_requesting_unit'] = $exObj['source_requesting_unit'];
                        if (isset($exObj['lab_exhibit_no'])) $docketData['seizures'][$eIdx]['lab_exhibit_no'] = $exObj['lab_exhibit_no'];
                        if (isset($exObj['source_of_exhibits'])) $docketData['seizures'][$eIdx]['source_of_exhibits'] = $exObj['source_of_exhibits'];
                        if (isset($exObj['other_exhibit_description']) || isset($exObj['other_description'])) $docketData['seizures'][$eIdx]['other_exhibit_description'] = $exObj['other_exhibit_description'] ?? $exObj['other_description'];
                        if (isset($exObj['presumptive_result'])) $docketData['seizures'][$eIdx]['presumptive_result'] = $exObj['presumptive_result'];
                        if (isset($exObj['evidence_photos']))   $docketData['seizures'][$eIdx]['evidence_photos']   = is_array($exObj['evidence_photos']) ? array_values($exObj['evidence_photos']) : [];

                        if ($eIdx === 0) {
                            if (!empty($szName)) $case->seizure_name_drug = $szName;
                            if (isset($exObj['exhibit_category'])) $case->exhibit_category = $exObj['exhibit_category'];
                            if (isset($exObj['seizure_quantity'])) $case->seizure_quantity = $exObj['seizure_quantity'];
                            if (isset($exObj['seizure_weight'])) $case->seizure_weight = $exObj['seizure_weight'];
                            if (isset($exObj['seizure_unit'])) $case->seizure_unit = $exObj['seizure_unit'];
                            if (isset($exObj['seizure_packaging'])) $case->seizure_packaging = $exObj['seizure_packaging'];
                            if (isset($exObj['seizure_reason'])) $case->seizure_reason = $exObj['seizure_reason'];
                            if (isset($exObj['source_of_exhibits']) && trim((string) $exObj['source_of_exhibits']) !== '') {
                                $case->source_of_exhibits = trim((string) $exObj['source_of_exhibits']);
                            }
                            if (isset($exObj['source_requesting_unit']) && trim((string) $exObj['source_requesting_unit']) !== '') {
                                $case->source_requesting_unit = trim((string) $exObj['source_requesting_unit']);
                            }
                            if (isset($exObj['investigating_officer']) && trim((string) $exObj['investigating_officer']) !== '') {
                                $case->investigating_officer = trim((string) $exObj['investigating_officer']);
                            }
                            if (isset($exObj['evidence_photos'])) {
                                $case->evidence_photos = $docketData['seizures'][$eIdx]['evidence_photos'];
                                $docketData['evidence_photos'] = $docketData['seizures'][$eIdx]['evidence_photos'];
                            }
                        }

                        $isMultiCaseRec = ($case->underlyingRecord instanceof MultipleSuspectCase)
                            || (!empty($case->docket_data['suspects']) && count($case->docket_data['suspects']) > 1);
                        $exQuery = Exhibit::query();
                        if ($isMultiCaseRec) {
                            $exQuery->where('multiple_suspect_case_id', $case->id);
                        } else {
                            $exQuery->where('single_suspect_case_id', $case->id);
                        }
                        $targetExId = (int) ($exObj['id'] ?? ($exObj['linked_exhibit_id'] ?? 0));
                        $exRecord = $targetExId > 0
                            ? (clone $exQuery)->where('id', $targetExId)->first()
                            : null;
                        if (!$exRecord) {
                            $exRecord = $exQuery->skip($eIdx)->first();
                        }
                        if ($exRecord) {
                            $submittedUpdates = $this->extractSubmittedExhibitFields($exObj, [], $szName ?: null);
                            if (!empty($submittedUpdates)) {
                                $exRecord->update($submittedUpdates);
                            }
                        }
                    }
                }
            }

            // C. Update Laboratory Examinations (Lab Unit)
            if ($isLabUnit) {
                if (empty($labsArray) && !empty($suspectsArray)) {
                    foreach ($suspectsArray as $spData) {
                        if (!empty($spData['lab'])) {
                            $labIdx = $spData['lab_index'] ?? count($labsArray);
                            $labsArray[$labIdx] = $spData['lab'];
                        }
                    }
                }

                // Fix Issue 11: LaboratoryExamService centralizes exam builds & model syncs
                $labService = app(LaboratoryExamService::class);
                if (!empty($labsArray)) {
                    foreach ($labsArray as $lIdx => $labObj) {
                        $ownerName = $labObj['exhibit_owner_name'] ?? ($docketData['lab_examinations_list'][$lIdx]['exhibit_owner_name'] ?? null);
                        if (!$ownerName && !empty($suspectsArray)) {
                            foreach ($suspectsArray as $sCandidate) {
                                if (($sCandidate['lab_index'] ?? null) == $lIdx) {
                                    $ownerName = $sCandidate['suspect_name'] ?? null;
                                    break;
                                }
                            }
                        }

                        $isPrimaryOwner = ($lIdx === 0) || (
                            !empty($ownerName) && (
                                strtolower(trim($ownerName)) === 'primary suspect' ||
                                strtolower(trim($ownerName)) === strtolower(trim($case->suspect_name ?? '')) ||
                                (!empty($oldPrimaryName) && strtolower(trim($ownerName)) === strtolower(trim($oldPrimaryName)))
                            )
                        );

                        $docketData = $labService->saveExam($case, $labObj, $ownerName, !$isPrimaryOwner, $user ? $user->fullname : 'Lab Analyst');
                    }
                }
            }
        }

        $case->docket_data = $docketData;
        $case->save();

        return [
            'success' => true,
            'message' => "Case {$case->case_number} details updated successfully.",
            'case'    => $case,
        ];
    }

    /**
     * Associate additional persons/suspects and exhibits to an existing case file.
     * Fix Issue 3: SchemaCache::hasColumn.
     * Fix Issue 4: Decode docket_data once.
     * Fix Issue 28: authorizeCaseWrite check.
     * Hardening C: isDrugExhibit helper.
     */
    public function associateCase(ForensicCase $case, Request $request): array
    {
        $newPersons = $request->input('associated_persons', []);
        if (is_string($newPersons)) {
            $newPersons = json_decode($newPersons, true) ?: [];
        }

        $newExhibits = $request->input('associated_exhibits', []);
        if (is_string($newExhibits)) {
            $newExhibits = json_decode($newExhibits, true) ?: [];
        }

        // 1. If single suspect case, promote to MultipleSuspectCase only when new suspects are being added
        if ($case->underlyingRecord instanceof SingleSuspectCase && count($newPersons) > 0) {
            $single = $case->underlyingRecord;
            $multi = MultipleSuspectCase::where('case_number', $single->case_number)->first();
            if (!$multi) {
                $multi = MultipleSuspectCase::create([
                    'case_number'          => $single->case_number,
                    'laboratory_number'    => $single->laboratory_number,
                    'title'                => $single->title,
                    'unit_name'            => $single->unit_name,
                    'unit_id'              => $single->unit_id,
                    'user_id'              => $single->user_id,
                    'arresting_officer'    => $single->arresting_officer ?? $single->submitting_officer,
                    'submitting_officer'   => $single->arresting_officer ?? $single->submitting_officer,
                    'operation_code'       => $single->operation_code,
                    'operation_code_name'  => $single->operation_code_name,
                    'search_warrant_document' => $single->search_warrant_document,
                    'raid_location'        => $single->raid_location,
                    'offense_description'  => $single->offense_description,
                    'description_of_exhibits' => $single->description_of_exhibits,
                    'incident_date'        => $single->incident_date,
                    'priority'             => $single->priority ?? 'HIGH',
                    'status'               => $single->status ?? CaseStatus::APPROVED->value,
                    'docket_data'          => $single->docket_data,
                    'hash_log'             => $single->hash_log,
                ]);

                $primarySuspectModel = CaseSuspect::where('single_suspect_case_id', $single->id)->first();
                if ($primarySuspectModel) {
                    $primarySuspectModel->update([
                        'single_suspect_case_id'   => null,
                        'multiple_suspect_case_id' => $multi->id,
                        'role'                     => 'Primary Suspect',
                    ]);
                } else {
                    $primarySuspectModel = CaseSuspect::create([
                        'multiple_suspect_case_id' => $multi->id,
                        'suspect_number'           => 1,
                        'role'                     => 'Primary Suspect',
                        'associate_id'             => null,
                        'suspect_name'             => $single->suspect_name,
                        'suspect_alias'            => $single->suspect_alias,
                        'ghana_card'               => $single->ghana_card,
                        'gender'                   => $single->gender,
                        'dob'                      => $single->dob,
                        'age'                      => $single->age,
                        'height'                   => $single->height,
                        'height_unit'              => $single->height_unit ?? 'CM',
                        'eye_color'                => $single->eye_color,
                        'nationality'              => $single->nationality,
                        'pob'                      => $single->pob,
                        'phone'                    => $single->phone,
                        'address'                  => $single->address,
                        'marital_status'           => $single->marital_status,
                        'has_children'             => $single->has_children ?? false,
                        'children_count'           => $single->children_count ?? '0',
                        'spouse_info'              => $single->spouse_info,
                        'spouse_name'              => $single->spouse_name,
                        'spouse_phone'             => $single->spouse_phone,
                        'partner_name'             => $single->partner_name,
                        'partner_phone'            => $single->partner_phone,
                        'partner_info'             => $single->partner_info,
                        'dependents'               => $single->dependents,
                        'holding_facility'         => $single->holding_facility,
                        'raid_location'            => $single->raid_location,
                        'offense_description'      => $single->offense_description,
                        'arrest_date'              => $single->arrest_date,
                        'arrest_time'              => $single->arrest_time,
                        'mugshot_front'            => $single->mugshot_front,
                        'mugshot_left'             => $single->mugshot_left,
                        'mugshot_right'            => $single->mugshot_right,
                    ]);
                }

                Exhibit::where('single_suspect_case_id', $single->id)->update([
                    'case_type'                => 'MULTIPLE',
                    'single_suspect_case_id'   => null,
                    'multiple_suspect_case_id' => $multi->id,
                    'suspect_id'               => $primarySuspectModel->id,
                ]);

                LaboratoryExaminationResult::where('single_suspect_case_id', $single->id)->update([
                    'case_type'                => 'MULTIPLE',
                    'single_suspect_case_id'   => null,
                    'multiple_suspect_case_id' => $multi->id,
                    'suspect_id'               => $primarySuspectModel->id,
                ]);

                SuspectGsaReport::where('single_suspect_case_id', $single->id)->update([
                    'single_suspect_case_id'   => null,
                    'multiple_suspect_case_id' => $multi->id,
                ]);

                SuspectNlrReport::where('single_suspect_case_id', $single->id)->update([
                    'single_suspect_case_id'   => null,
                    'multiple_suspect_case_id' => $multi->id,
                ]);

                $single->delete();
            }

            $case->underlyingRecord = $multi;
        }

        $targetRecord = $case->underlyingRecord;
        $descOfExhibits = $request->input('description_of_exhibits');
        if ($descOfExhibits !== null) {
            $case->description_of_exhibits = $descOfExhibits;
            $docketData['description_of_exhibits'] = $descOfExhibits;
            if ($targetRecord && SchemaCache::hasColumn($targetRecord->getTable(), 'description_of_exhibits')) {
                $targetRecord->description_of_exhibits = $descOfExhibits;
                $targetRecord->save();
            }
        }
        $isMultiple = $targetRecord instanceof MultipleSuspectCase;
        $addedPersonsCount = 0;
        $addedExhibitsCount = 0;

        if ($isMultiple) {
            $currentSuspectsCount = CaseSuspect::where('multiple_suspect_case_id', $targetRecord->id)->count();
            $currentAssocCount = CaseSuspect::where('multiple_suspect_case_id', $targetRecord->id)->where('role', 'LIKE', '%associate%')->count();
        } else {
            $currentSuspectsCount = 1;
            $currentAssocCount = 0;
        }

        // 2. Process new associated persons
        if (!empty($newPersons) && is_array($newPersons)) {
            foreach ($newPersons as $person) {
                $suspectName = $person['suspect_name'] ?? ($person['full_name'] ?? ($person['name'] ?? null));
                if (empty($suspectName)) continue;

                $currentSuspectsCount++;
                $currentAssocCount++;

                $assocId  = $person['associate_id'] ?? ($person['suspect_id'] ?? ('ASC-' . str_pad($currentAssocCount, 3, '0', STR_PAD_LEFT)));
                $pobVal   = $person['place_of_birth'] ?? ($person['hometown'] ?? ($person['pob'] ?? null));
                $phoneVal = $person['phone_number'] ?? ($person['contact_phone'] ?? ($person['phone'] ?? null));
                $addrVal  = $person['residential_address'] ?? ($person['address'] ?? ($person['gps'] ?? ($person['residential_address_gps'] ?? null)));
                $roleVal  = $person['association_role'] ?? ($person['role'] ?? 'Associate Suspect');

                CaseSuspect::create([
                    'multiple_suspect_case_id' => $isMultiple ? $targetRecord->id : null,
                    'single_suspect_case_id'   => $isMultiple ? null : $targetRecord->id,
                    'suspect_number'           => $currentSuspectsCount,
                    'role'                     => $roleVal,
                    'associate_id'             => $assocId,
                    'suspect_name'             => $suspectName,
                    'suspect_alias'            => $person['suspect_alias'] ?? null,
                    'ghana_card'               => $person['ghana_card'] ?? null,
                    'gender'                   => $person['gender'] ?? null,
                    'dob'                      => $person['dob'] ?? ($person['date_of_birth'] ?? null),
                    'age'                      => isset($person['age']) && $person['age'] !== '' ? (int)$person['age'] : null,
                    'height'                   => $person['height'] ?? null,
                    'eye_color'                => $person['eye_color'] ?? ($person['eye'] ?? null),
                    'nationality'              => $person['nationality'] ?? null,
                    'pob'                      => $pobVal,
                    'phone'                    => $phoneVal,
                    'address'                  => $addrVal,
                    'marital_status'           => $person['marital_status'] ?? null,
                    'spouse_info'              => $person['spouse_info'] ?? null,
                    'spouse_name'              => $person['spouse_name'] ?? null,
                    'spouse_phone'             => $person['spouse_phone'] ?? null,
                    'partner_name'             => $person['partner_name'] ?? null,
                    'partner_phone'            => $person['partner_phone'] ?? null,
                    'partner_info'             => $person['partner_info'] ?? null,
                    'dependents'               => $person['dependents'] ?? null,
                    'holding_facility'         => $person['holding_facility'] ?? null,
                    'raid_location'            => $person['raid_location'] ?? ($person['location'] ?? null),
                    'offense_description'      => $person['offense'] ?? ($person['offense_description'] ?? null),
                    'arrest_date'              => $person['date_of_arrest'] ?? ($person['arrest_date'] ?? null),
                    'arrest_time'              => $person['time_of_arrest'] ?? ($person['arrest_time'] ?? null),
                    'mugshot_front'            => $person['mugshot_front'] ?? ($person['mugshots']['front'] ?? null),
                    'mugshot_left'             => $person['mugshot_left'] ?? ($person['mugshots']['left'] ?? null),
                    'mugshot_right'            => $person['mugshot_right'] ?? ($person['mugshots']['right'] ?? null),
                ]);

                $addedPersonsCount++;
            }
        }

        // 3. Process new associated exhibits
        if ($isMultiple) {
            $allSuspectsInCase = CaseSuspect::where('multiple_suspect_case_id', $targetRecord->id)->get();
            $currentExhibitsCount = Exhibit::where('multiple_suspect_case_id', $targetRecord->id)->count();
        } else {
            $primarySuspectModel = CaseSuspect::where('single_suspect_case_id', $targetRecord->id)->first();
            $allSuspectsInCase = $primarySuspectModel ? collect([$primarySuspectModel]) : collect();
            $currentExhibitsCount = Exhibit::where('single_suspect_case_id', $targetRecord->id)->count();
        }

        $docketData = $this->decodeDocket($targetRecord);
        if (!isset($docketData['seizures']) || !is_array($docketData['seizures'])) {
            $docketData['seizures'] = [];
        }

        if (!empty($newExhibits) && is_array($newExhibits)) {
            foreach ($newExhibits as $exIdx => $exhibit) {
                $exName = $exhibit['seizure_name'] ?? ($exhibit['name'] ?? ($exhibit['commodity_name'] ?? null));
                if (empty($exName)) continue;

                $currentExhibitsCount++;

                $assocPersonName = !empty($exhibit['associated_person'])
                    ? $exhibit['associated_person']
                    : (!empty($exhibit['suspect_name'])
                        ? $exhibit['suspect_name']
                        : (!empty($exhibit['associated_suspect'])
                            ? $exhibit['associated_suspect']
                            : (!empty($newPersons[$exIdx]['suspect_name'])
                                ? $newPersons[$exIdx]['suspect_name']
                                : null)));

                $targetSuspectId = null;
                if ($assocPersonName) {
                    foreach ($allSuspectsInCase as $sp) {
                        if (strtolower(trim($sp->suspect_name)) === strtolower(trim($assocPersonName))) {
                            $targetSuspectId = $sp->id;
                            $assocPersonName = $sp->suspect_name;
                            break;
                        }
                    }
                }
                if (!$targetSuspectId && !empty($exhibit['associate_id'])) {
                    foreach ($allSuspectsInCase as $sp) {
                        if ($sp->associate_id === $exhibit['associate_id']) {
                            $targetSuspectId = $sp->id;
                            $assocPersonName = $sp->suspect_name;
                            break;
                        }
                    }
                }
                if (!$targetSuspectId && isset($allSuspectsInCase[$exIdx])) {
                    $targetSuspectId = $allSuspectsInCase[$exIdx]->id;
                    $assocPersonName = $allSuspectsInCase[$exIdx]->suspect_name;
                }

                // WHY: Prior code accepted raw base64 in evidence_photos hidden inputs,
                // bloating docket_data JSON and risking post_max_size failures.
                // All photos must first be uploaded via /upload-evidence-photos and
                // referenced by URL only.
                $rawPhotos = !empty($exhibit['photos']) && is_array($exhibit['photos'])
                    ? $exhibit['photos']
                    : (!empty($exhibit['photo']) ? [$exhibit['photo']] : (!empty($exhibit['exhibit_photo']) ? [$exhibit['exhibit_photo']] : []));

                $photosList = collect($rawPhotos)
                    ->filter(fn($p) => is_string($p) && !str_starts_with($p, 'data:') && preg_match('/^(https?:\/\/|\/)/', $p))
                    ->values()
                    ->all();

                $exCat = $exhibit['category'] ?? ($exhibit['exhibit_category'] ?? 'General Exhibit');

                $vehMake       = $exhibit['veh_make'] ?? ($exhibit['veh']['make'] ?? ($exhibit['vehicle_make'] ?? ($exhibit['vehicle_make_model'] ?? null)));
                $vehModel      = $exhibit['veh_model'] ?? ($exhibit['veh']['model'] ?? ($exhibit['vehicle_model'] ?? ($exhibit['vehicle_make_model'] ?? null)));
                $vehType       = $exhibit['veh_type'] ?? ($exhibit['veh']['type'] ?? ($exhibit['vehicle_type'] ?? null));
                $vehYear       = $exhibit['veh_year'] ?? ($exhibit['veh']['year'] ?? ($exhibit['vehicle_year'] ?? null));
                $vehColour     = $exhibit['veh_colour'] ?? ($exhibit['veh']['colour'] ?? ($exhibit['vehicle_colour'] ?? ($exhibit['vehicle_color_type'] ?? null)));
                $vehReg        = $exhibit['veh_reg_no'] ?? ($exhibit['veh']['reg_no'] ?? ($exhibit['vehicle_reg_no'] ?? ($exhibit['vehicle_registration'] ?? null)));
                $vehVin        = $exhibit['veh_vin'] ?? ($exhibit['veh']['vin'] ?? ($exhibit['vehicle_vin'] ?? ($exhibit['vehicle_chassis_vin'] ?? null)));
                $vehEngineNo   = $exhibit['veh_engine_no'] ?? ($exhibit['veh']['engine_no'] ?? ($exhibit['engine_number'] ?? null));
                $vehCountryReg = $exhibit['veh_country_reg'] ?? ($exhibit['veh']['country_reg'] ?? ($exhibit['country_of_registration'] ?? null));
                $vehOwner      = $exhibit['veh_owner'] ?? ($exhibit['veh']['owner'] ?? ($exhibit['vehicle_owner'] ?? null));
                $vehDriver     = $exhibit['veh_driver'] ?? ($exhibit['veh']['driver'] ?? ($exhibit['vehicle_driver'] ?? null));
                $vehOwnerContact   = $exhibit['veh_owner_contact'] ?? ($exhibit['veh']['owner_contact'] ?? null);
                $vehDocsAvailable  = $exhibit['veh_docs_available'] ?? ($exhibit['veh']['docs_available'] ?? null);
                $vehDocRef         = $exhibit['veh_doc_ref'] ?? ($exhibit['veh']['doc_ref'] ?? null);
                $vehCondition      = $exhibit['veh_condition'] ?? ($exhibit['veh']['condition'] ?? null);
                $vehFuel           = $exhibit['veh_fuel'] ?? ($exhibit['veh']['fuel'] ?? null);
                $vehKeysRecv       = $exhibit['veh_keys_recv'] ?? ($exhibit['veh']['keys_recv'] ?? null);
                $vehKeyCount       = $exhibit['veh_key_count'] ?? ($exhibit['veh']['key_count'] ?? null);
                $vehDamage         = $exhibit['veh_damage'] ?? ($exhibit['veh']['damage'] ?? null);
                $vehMods           = $exhibit['veh_mods'] ?? ($exhibit['veh']['mods'] ?? null);
                $vehFeatures       = $exhibit['veh_features'] ?? ($exhibit['veh']['features'] ?? null);

                $wpnModel   = $exhibit['wpn_model'] ?? ($exhibit['wpn']['model'] ?? ($exhibit['weapon_model'] ?? ($exhibit['weapon_type'] ?? null)));
                $wpnSerial  = $exhibit['wpn_serial_no'] ?? ($exhibit['wpn']['serial_no'] ?? ($exhibit['weapon_serial'] ?? ($exhibit['serial_number'] ?? null)));
                $wpnCalibre = $exhibit['wpn_calibre'] ?? ($exhibit['wpn']['calibre'] ?? ($exhibit['weapon_calibre'] ?? ($exhibit['calibre'] ?? null)));
                $wpnColour  = $exhibit['wpn_colour'] ?? ($exhibit['wpn']['colour'] ?? ($exhibit['weapon_colour'] ?? null));
                $wpnSize    = $exhibit['wpn_size'] ?? ($exhibit['wpn']['size'] ?? ($exhibit['weapon_size'] ?? null));
                $wpnMarks   = $exhibit['wpn_marks'] ?? ($exhibit['wpn']['marks'] ?? null);
                $wpnLocationFound = $exhibit['wpn_location_found'] ?? ($exhibit['wpn']['location_found'] ?? null);
                $wpnLoadedStatus  = $exhibit['wpn_loaded_status'] ?? ($exhibit['wpn']['loaded_status'] ?? null);
                $wpnCondition     = $exhibit['wpn_condition'] ?? ($exhibit['wpn']['condition'] ?? null);

                $devModel  = $exhibit['dev_model'] ?? ($exhibit['device_model'] ?? ($exhibit['device_make_model'] ?? null));
                $devSerial = $exhibit['dev_serial_no'] ?? ($exhibit['device_serial'] ?? ($exhibit['serial_number'] ?? null));
                $devImei1  = $exhibit['dev_imei1'] ?? ($exhibit['dev_imei_1'] ?? ($exhibit['imei_1'] ?? ($exhibit['imei1'] ?? null)));
                $devImei2  = $exhibit['dev_imei2'] ?? ($exhibit['dev_imei_2'] ?? ($exhibit['imei_2'] ?? ($exhibit['imei2'] ?? null)));
                $devMac    = $exhibit['dev_mac_address'] ?? ($exhibit['mac_address'] ?? null);
                $devStorage= $exhibit['dev_storage'] ?? ($exhibit['storage_capacity'] ?? ($exhibit['dev_storage_capacity'] ?? ($exhibit['storage'] ?? null)));
                $devColour = $exhibit['dev_colour'] ?? ($exhibit['dev_color'] ?? ($exhibit['device_color'] ?? ($exhibit['colour'] ?? ($exhibit['color'] ?? null))));
                $devMarks  = $exhibit['dev_marks'] ?? ($exhibit['dev_other_marks'] ?? ($exhibit['distinguishing_marks'] ?? ($exhibit['marks'] ?? ($exhibit['other_marks'] ?? null))));

                $ammoDetails = $exhibit['ammunition_details'] ?? ($exhibit['ammo'] ?? null);
                if (empty($ammoDetails)) {
                    $hasAmmoFields = !empty($exhibit['ammo_cartridge_appearance']) || !empty($exhibit['ammo_projectile_type']) || !empty($exhibit['ammo_case_material']) || !empty($exhibit['ammo_colour_markings']) || !empty($exhibit['ammo_condition']);
                    if ($hasAmmoFields) {
                        $ammoDetails = [
                            'cartridge_appearance' => $exhibit['ammo_cartridge_appearance'] ?? null,
                            'projectile_bullet_type' => $exhibit['ammo_projectile_type'] ?? null,
                            'cartridge_case_material' => $exhibit['ammo_case_material'] ?? null,
                            'colour_markings' => $exhibit['ammo_colour_markings'] ?? null,
                            'condition' => $exhibit['ammo_condition'] ?? null,
                            'other_condition' => $exhibit['ammo_other_condition'] ?? null,
                            'packaging_type' => $exhibit['ammo_packaging_type'] ?? null,
                            'seal_tamper_evident_condition' => $exhibit['ammo_seal_condition'] ?? null,
                            'package_reference_number' => $exhibit['ammo_package_reference_number'] ?? null,
                            'condition_when_received' => $exhibit['ammo_condition_when_received'] ?? null,
                        ];
                    }
                }

                if (!$targetSuspectId && !$isMultiple && isset($primarySuspectModel) && $primarySuspectModel) {
                    $targetSuspectId = $primarySuspectModel->id;
                }
                if (!$assocPersonName && !$isMultiple) {
                    $assocPersonName = $targetRecord->suspect_name ?? null;
                }

                    $isMoneySz = in_array(strtolower(trim((string)$exCat)), ['money', 'currency', 'cash']);
                    $amtSz = !empty($exhibit['amount']) ? $exhibit['amount'] : ($isMoneySz ? (!empty($exhibit['seizure_quantity']) ? $exhibit['seizure_quantity'] : (!empty($exhibit['quantity']) ? $exhibit['quantity'] : ((!empty($exhibit['seizure_value']) && $exhibit['seizure_value'] !== 'N/A') ? $exhibit['seizure_value'] : null))) : null);

                    $createdExhibit = Exhibit::create([
                    'case_type'                => $isMultiple ? 'MULTIPLE' : 'SINGLE',
                    'single_suspect_case_id'   => $isMultiple ? null : $targetRecord->id,
                    'multiple_suspect_case_id' => $isMultiple ? $targetRecord->id : null,
                    'suspect_id'               => $targetSuspectId,
                    'associated_person'        => $assocPersonName,
                    'exhibit_number'           => $exhibit['exhibit_number'] ?? null,
                    'lab_exhibit_no'           => $exhibit['lab_exhibit_no'] ?? null,
                    'exhibit_category'         => $exCat,
                    'commodity_name'           => $exName,
                    'contents'                 => $exhibit['contents'] ?? ($exhibit['seizure_contents'] ?? null),
                    'description_of_exhibits'  => $exhibit['description_of_exhibits'] ?? ($targetRecord->description_of_exhibits ?? null),
                    'seizure_quantity'         => $exhibit['seizure_quantity'] ?? ($exhibit['quantity'] ?? null),
                    'amount'                   => $amtSz,
                    'seizure_packaging'        => $exhibit['seizure_packaging'] ?? ($exhibit['packaging'] ?? null),
                    'seizure_weight'           => $exhibit['seizure_weight'] ?? ($exhibit['weight'] ?? null),
                    'seizure_unit'             => $exhibit['seizure_unit'] ?? ($exhibit['unit'] ?? null),
                    'seizure_value'            => $exhibit['seizure_value'] ?? ($exhibit['value'] ?? null),
                    'seizure_location'         => $exhibit['seizure_location'] ?? ($exhibit['location'] ?? null),
                    'seizure_concealment'      => $exhibit['seizure_concealment'] ?? ($exhibit['concealment'] ?? null),
                    'seizure_date'             => $exhibit['seizure_date'] ?? ($exhibit['date_seized'] ?? null),
                    'seizure_time'             => $exhibit['seizure_time'] ?? ($exhibit['time_seized'] ?? null),
                    'seizure_officer'          => $exhibit['seizure_officer'] ?? ($exhibit['seizing_officer'] ?? null),
                    'investigating_officer'    => $exhibit['investigating_officer'] ?? null,
                    'source_requesting_unit'   => $exhibit['source_requesting_unit'] ?? null,
                    'seizure_reason'           => $exhibit['seizure_reason'] ?? ($exhibit['reason'] ?? null),
                    'source_of_exhibits'       => $exhibit['source_of_exhibits'] ?? ($exhibit['source'] ?? null),
                    'exhibit_remark'           => $exhibit['exhibit_remark'] ?? null,
                    'lab_remark'               => $exhibit['lab_remark'] ?? ($exhibit['confirmatory_remark'] ?? null),
                    'veh_make'                 => $vehMake,
                    'veh_model'                => $vehModel,
                    'veh_type'                 => $vehType,
                    'veh_year'                 => $vehYear,
                    'veh_colour'               => $vehColour,
                    'veh_reg_no'               => $vehReg,
                    'veh_vin'                  => $vehVin,
                    'veh_engine_no'            => $vehEngineNo,
                    'veh_country_reg'          => $vehCountryReg,
                    'veh_owner'                => $vehOwner,
                    'veh_driver'               => $vehDriver,
                    'veh_owner_contact'        => $vehOwnerContact,
                    'veh_docs_available'       => $vehDocsAvailable,
                    'veh_doc_ref'              => $vehDocRef,
                    'veh_condition'            => $vehCondition,
                    'veh_fuel'                 => $vehFuel,
                    'veh_keys_recv'            => $vehKeysRecv,
                    'veh_key_count'            => $vehKeyCount,
                    'veh_damage'               => $vehDamage,
                    'veh_mods'                 => $vehMods,
                    'veh_features'             => $vehFeatures,
                    'wpn_model'                => $wpnModel,
                    'wpn_serial_no'            => $wpnSerial,
                    'wpn_calibre'              => $wpnCalibre,
                    'wpn_colour'               => $wpnColour,
                    'wpn_size'                 => $wpnSize,
                    'wpn_marks'                => $wpnMarks,
                    'wpn_location_found'       => $wpnLocationFound,
                    'wpn_loaded_status'        => $wpnLoadedStatus,
                    'wpn_condition'            => $wpnCondition,
                    'dev_model'                => $devModel,
                    'dev_serial_no'            => $devSerial,
                    'dev_imei1'                => $devImei1,
                    'dev_imei2'                => $devImei2,
                    'dev_mac_address'          => $devMac,
                    'dev_storage'              => $devStorage,
                    'dev_colour'               => $devColour,
                    'dev_marks'                => $devMarks,
                    'ammunition_details'       => $ammoDetails,
                    'other_exhibit_description' => $exhibit['other_exhibit_description'] ?? ($exhibit['other_description'] ?? null),
                    'evidence_photos'          => $photosList,
                ]);

                // Hardening C: Use canonical isDrugExhibit helper
                $isDrug = $this->isDrugExhibit($exhibit);

                $docketData['seizures'][] = [
                    'id'                 => $createdExhibit->id,
                    'exhibit_number'     => $createdExhibit->exhibit_number,
                    'lab_exhibit_no'     => $createdExhibit->lab_exhibit_no,
                    'exhibit_category'   => $exCat,
                    'category'           => $exCat,
                    'seizure_name'       => $exName,
                    'commodity_name'     => $exName,
                    'name'               => $exName,
                    'contents'           => $exhibit['contents'] ?? ($exhibit['seizure_contents'] ?? null),
                    'description_of_exhibits' => $exhibit['description_of_exhibits'] ?? ($targetRecord->description_of_exhibits ?? null),
                    'seizure_name_drug'  => $isDrug ? $exName : null,
                    'seizure_quantity'   => $exhibit['seizure_quantity'] ?? ($exhibit['quantity'] ?? null),
                    'amount'             => $exhibit['amount'] ?? (in_array(strtolower(trim((string)$exCat)), ['money', 'currency', 'cash']) ? ($exhibit['seizure_quantity'] ?? ($exhibit['quantity'] ?? null)) : null),
                    'seizure_weight'     => $exhibit['seizure_weight'] ?? ($exhibit['weight'] ?? null),
                    'seizure_unit'       => $exhibit['seizure_unit'] ?? ($exhibit['unit'] ?? null),
                    'seizure_packaging'  => $exhibit['seizure_packaging'] ?? ($exhibit['packaging'] ?? null),
                    'seizure_value'      => $exhibit['seizure_value'] ?? ($exhibit['value'] ?? null),
                    'seizure_location'   => $exhibit['seizure_location'] ?? ($exhibit['location'] ?? null),
                    'seizure_concealment' => $exhibit['seizure_concealment'] ?? ($exhibit['concealment'] ?? null),
                    'seizure_date'       => $exhibit['seizure_date'] ?? ($exhibit['date_seized'] ?? null),
                    'seizure_time'       => $exhibit['seizure_time'] ?? ($exhibit['time_seized'] ?? null),
                    'seizure_officer'    => $exhibit['seizure_officer'] ?? ($exhibit['seizing_officer'] ?? null),
                    'investigating_officer' => $exhibit['investigating_officer'] ?? null,
                    'source_requesting_unit' => $exhibit['source_requesting_unit'] ?? null,
                    'seizure_reason'     => $exhibit['seizure_reason'] ?? ($exhibit['reason'] ?? null),
                    'source_of_exhibits' => $exhibit['source_of_exhibits'] ?? ($exhibit['source'] ?? null),
                    'exhibit_remark'     => $exhibit['exhibit_remark'] ?? null,
                    'lab_remark'         => $exhibit['lab_remark'] ?? ($exhibit['confirmatory_remark'] ?? null),
                    'veh_make'           => $vehMake,
                    'veh_model'          => $vehModel,
                    'veh_type'           => $vehType,
                    'veh_year'           => $vehYear,
                    'veh_colour'         => $vehColour,
                    'veh_reg_no'         => $vehReg,
                    'veh_vin'            => $vehVin,
                    'veh_engine_no'      => $vehEngineNo,
                    'veh_country_reg'    => $vehCountryReg,
                    'veh_owner'          => $vehOwner,
                    'veh_driver'         => $vehDriver,
                    'veh_owner_contact'  => $vehOwnerContact,
                    'veh_docs_available' => $vehDocsAvailable,
                    'veh_doc_ref'        => $vehDocRef,
                    'veh_condition'      => $vehCondition,
                    'veh_fuel'           => $vehFuel,
                    'veh_keys_recv'      => $vehKeysRecv,
                    'veh_key_count'      => $vehKeyCount,
                    'veh_damage'         => $vehDamage,
                    'veh_mods'           => $vehMods,
                    'veh_features'       => $vehFeatures,
                    'vehicle_make_model' => $vehMake ? ($vehModel ? "$vehMake $vehModel" : $vehMake) : $vehModel,
                    'vehicle_registration' => $vehReg,
                    'vehicle_chassis_vin'  => $vehVin,
                    'vehicle_color_type'   => $vehColour,
                    'vehicle_type'         => $vehType,
                    'vehicle_year'         => $vehYear,
                    'engine_number'        => $vehEngineNo,
                    'country_of_registration' => $vehCountryReg,
                    'vehicle_owner'        => $vehOwner,
                    'vehicle_driver'       => $vehDriver,
                    'veh' => [
                        'make' => $vehMake,
                        'model' => $vehModel,
                        'type' => $vehType,
                        'year' => $vehYear,
                        'colour' => $vehColour,
                        'reg_no' => $vehReg,
                        'vin' => $vehVin,
                        'engine_no' => $vehEngineNo,
                        'country_reg' => $vehCountryReg,
                        'owner' => $vehOwner,
                        'driver' => $vehDriver,
                        'owner_contact' => $vehOwnerContact,
                        'docs_available' => $vehDocsAvailable,
                        'doc_ref' => $vehDocRef,
                        'condition' => $vehCondition,
                        'fuel' => $vehFuel,
                        'keys_recv' => $vehKeysRecv,
                        'key_count' => $vehKeyCount,
                        'damage' => $vehDamage,
                        'mods' => $vehMods,
                        'features' => $vehFeatures,
                    ],
                    'wpn_model'          => $wpnModel,
                    'wpn_serial_no'      => $wpnSerial,
                    'wpn_calibre'        => $wpnCalibre,
                    'wpn_colour'         => $wpnColour,
                    'wpn_size'           => $wpnSize,
                    'wpn_marks'          => $wpnMarks,
                    'wpn_location_found' => $wpnLocationFound,
                    'wpn_loaded_status'  => $wpnLoadedStatus,
                    'wpn_condition'      => $wpnCondition,
                    'dev_model'          => $devModel,
                    'dev_serial_no'      => $devSerial,
                    'dev_imei1'          => $devImei1,
                    'dev_imei2'          => $devImei2,
                    'dev_mac_address'    => $devMac,
                    'dev_storage'        => $devStorage,
                    'dev_colour'         => $devColour,
                    'dev_marks'          => $devMarks,
                    'ammunition_details' => $ammoDetails,
                    'ammo'               => $ammoDetails,
                    'other_exhibit_description' => $exhibit['other_exhibit_description'] ?? ($exhibit['other_description'] ?? null),
                    'other_description'  => $exhibit['other_exhibit_description'] ?? ($exhibit['other_description'] ?? null),
                    'associated_person'  => $assocPersonName,
                    'suspect_name'       => $assocPersonName,
                    'photos'             => $photosList,
                ];

                $addedExhibitsCount++;
            }
        }

        // Handle linked existing exhibits (unassigned / manifest-linked via unassigned_exhibits_selector)
        $inputLinkedExhibitIds = $request->input('linked_exhibit_ids', []);
        if (!empty($inputLinkedExhibitIds)) {
            $parsedLinkedIds = is_array($inputLinkedExhibitIds)
                ? $inputLinkedExhibitIds
                : array_filter(explode(',', (string) $inputLinkedExhibitIds));
            $linkedIds = array_values(array_unique(array_filter(array_map('intval', $parsedLinkedIds))));

            if (!empty($linkedIds)) {
                $docketData['linked_exhibit_ids'] = array_values(array_unique(array_merge(
                    array_map('intval', $docketData['linked_exhibit_ids'] ?? []),
                    $linkedIds
                )));

                $defaultAssocSuspect = null;
                if (!empty($newPersons[0]['suspect_name'])) {
                    $defaultAssocSuspect = $allSuspectsInCase->first(
                        fn ($sp) => strtolower(trim((string) $sp->suspect_name)) === strtolower(trim((string) $newPersons[0]['suspect_name']))
                    );
                }
                if (!$defaultAssocSuspect) {
                    $defaultAssocSuspect = $allSuspectsInCase->last() ?? $allSuspectsInCase->first();
                }

                $linkedExhibits = Exhibit::whereIn('id', $linkedIds)->get();
                foreach ($linkedExhibits as $linkedEx) {
                    $wasUnassigned = empty($linkedEx->single_suspect_case_id) && empty($linkedEx->multiple_suspect_case_id);
                    if ($wasUnassigned) {
                        $linkedEx->update([
                            'case_type'                => $isMultiple ? 'MULTIPLE' : 'SINGLE',
                            'single_suspect_case_id'   => $isMultiple ? null : $targetRecord->id,
                            'multiple_suspect_case_id' => $isMultiple ? $targetRecord->id : null,
                            'suspect_id'               => $defaultAssocSuspect?->id ?? $linkedEx->suspect_id,
                            'associated_person'        => $defaultAssocSuspect?->suspect_name ?? $linkedEx->associated_person,
                        ]);
                        $currentExhibitsCount++;
                        $addedExhibitsCount++;
                    } elseif ($defaultAssocSuspect && empty($linkedEx->suspect_id)) {
                        $linkedEx->update([
                            'suspect_id'        => $defaultAssocSuspect->id,
                            'associated_person' => $defaultAssocSuspect->suspect_name,
                        ]);
                    }

                    $alreadyInDocket = collect($docketData['seizures'] ?? [])->contains(
                        fn ($sz) => (int) ($sz['id'] ?? $sz['linked_exhibit_id'] ?? 0) === (int) $linkedEx->id
                    );
                    if (!$alreadyInDocket) {
                        $docketData['seizures'][] = [
                            'id'                => $linkedEx->id,
                            'linked_exhibit_id' => $linkedEx->id,
                            'linked_from_vault' => true,
                            'exhibit_number'    => $linkedEx->exhibit_number,
                            'lab_exhibit_no'    => $linkedEx->lab_exhibit_no,
                            'exhibit_category'  => $linkedEx->exhibit_category,
                            'category'          => $linkedEx->exhibit_category,
                            'seizure_name'      => $linkedEx->commodity_name,
                            'commodity_name'    => $linkedEx->commodity_name,
                            'name'              => $linkedEx->commodity_name,
                            'contents'          => $linkedEx->contents,
                            'seizure_quantity'  => $linkedEx->seizure_quantity,
                            'seizure_weight'    => $linkedEx->seizure_weight,
                            'seizure_unit'      => $linkedEx->seizure_unit,
                            'seizure_packaging' => $linkedEx->seizure_packaging,
                            'seizure_value'     => $linkedEx->seizure_value,
                            'associated_person' => $linkedEx->associated_person,
                            'suspect_name'      => $linkedEx->associated_person,
                        ];
                    }
                }
            }
        }

        if ($isMultiple) {
            $allNames = CaseSuspect::where('multiple_suspect_case_id', $targetRecord->id)->pluck('suspect_name')->toArray();
            $cleanNames = array_map(fn($n) => preg_replace('/^Republic\s+vs\s+/i', '', trim($n)), $allNames);
            $jointSuspectNameLabel = !empty($cleanNames) ? implode(', ', array_filter($cleanNames)) : preg_replace('/^Republic\s+vs\s+/i', '', trim($targetRecord->title));

            if (SchemaCache::hasColumn($targetRecord->getTable(), 'docket_data')) {
                $targetRecord->docket_data = $docketData;
                $targetRecord->save();
            }

            $refreshedForCalc = ForensicCase::wrapRecord($targetRecord->fresh(['suspects', 'exhibits', 'labResults']));
            $reqUnits = $refreshedForCalc->getWorkflowUnits();
            $unitProgress = $docketData['unit_progress'] ?? [];
            foreach ($reqUnits as $reqU) {
                if (!isset($unitProgress[$reqU])) {
                    $unitProgress[$reqU] = [
                        'status'     => WorkflowStatus::PENDING->value,
                        'officer'    => null,
                        'updated_at' => null,
                    ];
                }
            }
            $docketData['unit_progress'] = $unitProgress;

            $updatePayload = [
                'total_suspects_count' => count($allNames),
                'total_exhibits_count' => $currentExhibitsCount,
                'title'                => 'Republic vs ' . $jointSuspectNameLabel,
            ];
            if ($descOfExhibits !== null && SchemaCache::hasColumn($targetRecord->getTable(), 'description_of_exhibits')) {
                $updatePayload['description_of_exhibits'] = $descOfExhibits;
            }
            if (SchemaCache::hasColumn($targetRecord->getTable(), 'docket_data')) {
                $updatePayload['docket_data'] = $docketData;
            }

            $targetRecord->update($updatePayload);
            $refreshedCase = ForensicCase::wrapRecord($targetRecord->fresh(['suspects', 'exhibits', 'labResults']));
        } else {
            if (SchemaCache::hasColumn($targetRecord->getTable(), 'docket_data')) {
                $targetRecord->docket_data = $docketData;
                $targetRecord->save();
            }

            $refreshedForCalc = ForensicCase::wrapRecord($targetRecord->fresh(['exhibits']));
            $reqUnits = $refreshedForCalc->getWorkflowUnits();
            $unitProgress = $docketData['unit_progress'] ?? [];
            foreach ($reqUnits as $reqU) {
                if (!isset($unitProgress[$reqU])) {
                    $unitProgress[$reqU] = [
                        'status'     => WorkflowStatus::PENDING->value,
                        'officer'    => null,
                        'updated_at' => null,
                    ];
                }
            }
            $docketData['unit_progress'] = $unitProgress;

            $updatePayload = [];
            if ($descOfExhibits !== null && SchemaCache::hasColumn($targetRecord->getTable(), 'description_of_exhibits')) {
                $updatePayload['description_of_exhibits'] = $descOfExhibits;
            }
            if (SchemaCache::hasColumn($targetRecord->getTable(), 'docket_data')) {
                $updatePayload['docket_data'] = $docketData;
            }
            if (SchemaCache::hasColumn($targetRecord->getTable(), 'total_exhibits_count')) {
                $updatePayload['total_exhibits_count'] = $currentExhibitsCount;
            }
            if (!empty($updatePayload)) {
                $targetRecord->update($updatePayload);
            }

            $refreshedCase = ForensicCase::wrapRecord($targetRecord->fresh(['exhibits']));
        }

        // Handle Lab Analysis Report & Laboratory Examination Modules (GSA Upload vs NLR Exam)
        $labWorkflowMode = strtolower(trim((string) $request->input('lab_workflow_mode', '')));
        $gsaFiles = $request->file('gsa_report_images', []);
        if ($gsaFiles instanceof \Illuminate\Http\UploadedFile) {
            $gsaFiles = [$gsaFiles];
        }
        $hasGsaFiles = is_array($gsaFiles) && count(array_filter($gsaFiles, fn ($f) => $f instanceof \Illuminate\Http\UploadedFile)) > 0;

        $targetModuleSuspect = null;
        if (!empty($newPersons[0]['suspect_name'])) {
            $targetModuleSuspect = $allSuspectsInCase->first(
                fn ($sp) => strtolower(trim((string) $sp->suspect_name)) === strtolower(trim((string) $newPersons[0]['suspect_name']))
            );
        }
        if (!$targetModuleSuspect) {
            $targetModuleSuspect = $allSuspectsInCase->last() ?? $allSuspectsInCase->first();
        }

        if ($labWorkflowMode === 'gsa' || $hasGsaFiles) {
            $folder = 'gsa_reports/' . now()->format('Y/m');
            $rawExhibitType = $request->input('exhibit_type');
            $docketExhibitType = null;
            if (is_array($rawExhibitType)) {
                $filtered = array_filter(array_map('trim', $rawExhibitType), fn ($v) => $v !== '');
                $docketExhibitType = !empty($filtered) ? implode(', ', $filtered) : null;
            } elseif (!empty($rawExhibitType)) {
                $docketExhibitType = trim((string) $rawExhibitType) ?: null;
            }

            foreach ($gsaFiles as $file) {
                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    $ext = strtolower($file->getClientOriginalExtension());
                    $stored = \Illuminate\Support\Str::uuid() . '.' . $ext;
                    $path = $file->storeAs($folder, $stored, 'public');

                    SuspectGsaReport::create([
                        'case_suspect_id'          => $targetModuleSuspect?->id,
                        'multiple_suspect_case_id' => $isMultiple ? $targetRecord->id : null,
                        'single_suspect_case_id'   => $isMultiple ? null : $targetRecord->id,
                        'uploaded_by'              => auth()->id(),
                        'original_filename'        => $file->getClientOriginalName(),
                        'stored_filename'          => $stored,
                        'file_path'                => $path,
                        'file_type'                => $file->getMimeType(),
                        'file_extension'           => $ext,
                        'file_size'                => $file->getSize(),
                        'lab_number'               => $request->input('gsa_lab_number') ?? $request->input('laboratory_number') ?? $request->input('lab_number'),
                        'exhibit_type'             => $docketExhibitType,
                        'case_number'              => $refreshedCase->case_number,
                        'status'                   => $request->input('gsa_status') ?: 'active',
                    ]);
                }
            }
        } else {
            $hasNlrSubmission = $labWorkflowMode === 'nlr'
                || !empty($request->input('purpose_of_examination'))
                || !empty($request->input('confirmatory_test_result'))
                || !empty($request->input('presumptive_result'))
                || !empty($request->input('presumptive_test_result'))
                || !empty($request->input('confirmatory_substance_identification'))
                || !empty($request->input('findings'));

            if ($hasNlrSubmission) {
                $labService = app(LaboratoryExamService::class);
                $examPayload = $request->all();
                if (empty($examPayload['exhibit_id']) && !empty($docketData['linked_exhibit_ids'])) {
                    $examPayload['exhibit_id'] = (int) $docketData['linked_exhibit_ids'][0];
                }
                $labOfficer = auth()->user()?->name ?? ($request->input('lab_officer_name') ?: null);
                $suspectNameForLab = $targetModuleSuspect?->suspect_name ?? $refreshedCase->suspect_name ?? 'Suspect';
                $updatedDocket = $labService->saveExam($refreshedCase, $examPayload, $suspectNameForLab, false, $labOfficer);
                if (SchemaCache::hasColumn($targetRecord->getTable(), 'docket_data')) {
                    $targetRecord->docket_data = $updatedDocket;
                    $targetRecord->save();
                }
            }
        }

        $fromUnit = $request->input('from_unit') ?? $request->input('unit') ?? $request->query('unit') ?? $request->query('from') ?? '';
        $userUnit = auth()->user()->unit ?? session('user_unit') ?? '';
        $referer  = $request->headers->get('referer') ?? '';

        $isLab = str_contains(strtolower($fromUnit), 'lab') 
                 || str_contains(strtolower($userUnit), 'lab')
                 || str_contains(strtolower($referer), 'lab');

        $redirectUrl = url('/cases') . ($isLab ? '?unit=Lab+Analysis' : '');

        return [
            'success'  => true,
            'message'  => "Successfully associated {$addedPersonsCount} person(s) and {$addedExhibitsCount} exhibit(s) with Case {$refreshedCase->case_number}.",
            'case'     => $refreshedCase,
            'redirect' => $redirectUrl,
        ];
    }

    /**
     * Extract non-empty exhibit and source/custody fields from a submitted seizure payload
     * so linked or updated Exhibit rows persist user-entered values (e.g. Lab Analysis overrides).
     */
    private function extractSubmittedExhibitFields(array $szItem, array $fallbackFields = [], ?string $fallbackName = null): array
    {
        $rawAmount = $szItem['amount'] ?? null;

        $candidates = [
            'commodity_name'            => $szItem['commodity_name'] ?? ($szItem['seizure_name_currency'] ?? ($szItem['seizure_name_drug'] ?? ($szItem['seizure_name'] ?? ($szItem['name'] ?? $fallbackName)))),
            'exhibit_category'          => $szItem['exhibit_category'] ?? ($szItem['category'] ?? null),
            'exhibit_type'              => $szItem['exhibit_type'] ?? null,
            'drug_classification'       => $szItem['drug_classification'] ?? null,
            'contents'                  => $szItem['contents'] ?? ($szItem['seizure_contents'] ?? null),
            'description_of_exhibits'   => $szItem['description_of_exhibits'] ?? ($fallbackFields['description_of_exhibits'] ?? null),
            'other_exhibit_description' => $szItem['other_exhibit_description'] ?? ($szItem['other_description'] ?? null),
            'ammunition_details'        => $szItem['ammunition_details'] ?? null,
            'lab_exhibit_no'            => $szItem['lab_exhibit_no'] ?? ($fallbackFields['lab_exhibit_no'] ?? null),
            'seizure_quantity'          => $szItem['seizure_quantity'] ?? ($szItem['quantity'] ?? null),
            'amount'                    => ($rawAmount !== null && $rawAmount !== 'N/A') ? $rawAmount : null,
            'seizure_weight'            => $szItem['seizure_weight'] ?? ($szItem['weight'] ?? ($szItem['sample_gross'] ?? null)),
            'seizure_unit'              => $szItem['seizure_unit'] ?? ($szItem['unit'] ?? null),
            'seizure_packaging'         => $szItem['seizure_packaging'] ?? ($szItem['packaging'] ?? null),
            'seizure_value'             => $szItem['seizure_value'] ?? ($szItem['value'] ?? ($szItem['valuation'] ?? null)),
            'seizure_concealment'       => $szItem['seizure_concealment'] ?? ($szItem['concealment'] ?? null),
            'seizure_date'              => $szItem['seizure_date'] ?? ($szItem['date'] ?? null),
            'seizure_time'              => $szItem['seizure_time'] ?? ($szItem['time'] ?? null),
            'seizure_location'          => $szItem['seizure_location'] ?? ($szItem['location'] ?? null),
            'seizure_officer'           => $szItem['seizure_officer'] ?? ($szItem['seizing_officer'] ?? null),
            'seizure_reason'            => $szItem['seizure_reason'] ?? ($szItem['reason'] ?? ($fallbackFields['seizure_reason'] ?? null)),
            'investigating_officer'     => $szItem['investigating_officer'] ?? ($fallbackFields['investigating_officer'] ?? null),
            'source_requesting_unit'    => $szItem['source_requesting_unit'] ?? ($fallbackFields['source_requesting_unit'] ?? null),
            'source_of_exhibits'        => $szItem['source_of_exhibits'] ?? ($szItem['source'] ?? ($fallbackFields['source_of_exhibits'] ?? null)),
            'exhibit_remark'            => $szItem['exhibit_remark'] ?? ($fallbackFields['exhibit_remark'] ?? null),
            'lab_remark'                => $szItem['lab_remark'] ?? ($szItem['confirmatory_remark'] ?? null),
            'evidence_photos'           => (!empty($szItem['evidence_photos']) && is_array($szItem['evidence_photos']))
                ? array_values($szItem['evidence_photos'])
                : null,
        ];

        $normalized = [];
        foreach ($candidates as $column => $value) {
            if ($value === null) {
                continue;
            }
            if (is_string($value)) {
                $trimmed = trim($value);
                if ($trimmed === '') {
                    continue;
                }
                $normalized[$column] = $trimmed;
                continue;
            }
            if (is_array($value)) {
                if (count($value) === 0) {
                    continue;
                }
                $normalized[$column] = $value;
                continue;
            }
            $normalized[$column] = $value;
        }

        return $normalized;
    }
}
