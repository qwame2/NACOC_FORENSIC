<?php

namespace App\Http\Controllers;

use App\Models\ForensicCase;
use App\Models\Unit;
use App\Models\CaseHandover;
use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CaseController extends Controller
{
    /**
     * Display a listing of all cases stored in the database.
     */
    public function index()
    {
        $cases = ForensicCase::with(['user', 'unit'])->orderBy('created_at', 'desc')->get();

        // Append real-time workflow progress fields to each case so the
        // client-side table always shows the correct Multi-Unit (X/Y) count.
        $cases = $cases->map(function ($case) {
            $units      = $case->getWorkflowUnits();
            $docket     = is_array($case->docket_data)
                ? $case->docket_data
                : (json_decode($case->docket_data ?? '[]', true) ?: []);
            $unitProg   = $docket['unit_progress'] ?? [];
            $completed  = 0;
            foreach ($units as $u) {
                if (isset($unitProg[$u]['status']) && strtoupper($unitProg[$u]['status']) === 'COMPLETED') {
                    $completed++;
                }
            }
            $case->workflow_units     = $units;
            $case->workflow_total     = count($units);
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

        return response()->json($cases);
    }

    /**
     * Store a new case entered by a Unit Head into the database table.
     * Supports multiple suspects â€” creates one ForensicCase row per suspect
     * in a joint case, linked via parent_case_id back to the primary record.
     */
    public function store(Request $request)
    {
        $validated = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'case_number'          => ['nullable', 'string', 'max:50'],
            'title'                => ['required', 'string', 'max:255'],
            'unit_name'            => ['nullable', 'string', 'max:255'],
            'commanding_officer'   => ['nullable', 'string', 'max:255'],
            'submitting_officer'   => ['nullable', 'string', 'max:255'],
            'suspect_name'         => ['required', 'string', 'max:255'],
            'associated_person'    => ['nullable', 'string', 'max:255'],
            'suspect_alias'        => ['nullable', 'string', 'max:255'],
            'ghana_card'           => ['nullable', 'string', 'max:255'],
            'gender'               => ['nullable', 'string', 'max:50'],
            'dob'                  => ['nullable', 'string', 'max:50'],
            'age'                  => ['nullable'],
            'height'               => ['nullable', 'string', 'max:100'],
            'eye_color'            => ['nullable', 'string', 'max:100'],
            'nationality'          => ['nullable', 'string', 'max:100'],
            'pob'                      => ['nullable', 'string', 'max:255'],
            'phone'                    => ['nullable', 'string', 'max:50'],
            'phone_label'              => ['nullable', 'string', 'max:50'],
            'phone_labels'             => ['nullable', 'array'],
            'additional_phones'        => ['nullable', 'array'],
            'additional_phones.*'      => ['nullable', 'string', 'max:50'],
            'additional_phone_labels'  => ['nullable', 'array'],
            'additional_phone_labels.*'=> ['nullable', 'string'],
            'phones'                   => ['nullable'],
            'address'                  => ['nullable', 'string'],
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
            'seizure_reason'       => ['nullable', 'string'],
            'exhibit_type'         => ['nullable', 'string', 'max:255'],
            'drug_classification'  => ['nullable', 'string', 'max:50'],
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

        // -----------------------------------------------------------------------
        // Extract the full suspects array from docket_data sent by the frontend.
        // The JS collectAllSuspects() packs every suspect into docket_data.suspects[].
        // -----------------------------------------------------------------------
        $rawDocketInput = $request->input('docket_data');
        $inputDocket = is_array($rawDocketInput)
            ? $rawDocketInput
            : (json_decode($rawDocketInput ?? '{}', true) ?: []);

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

            // Fallback to additional_phones / additional_phone_labels
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

        $allSuspects = (isset($inputDocket['suspects']) && is_array($inputDocket['suspects']))
            ? array_values(array_filter($inputDocket['suspects'], function($s) {
                $n = trim($s['suspect_name'] ?? '');
                return !empty($n) && $n !== 'Unnamed Suspect' && $n !== 'Unknown';
            }))
            : [];

        // Fall back to flat suspect fields if no structured suspects array arrived
        if (empty($allSuspects)) {
            $suspectName = trim($validated['suspect_name'] ?? '');
            if (empty($suspectName) || $suspectName === 'Unnamed Suspect' || $suspectName === 'Unknown') {
                return response()->json([
                    'message' => 'The suspect name field is required. You cannot save a case docket without entering suspect details.',
                    'errors'  => [
                        'suspect_name' => ['Suspect Full Legal Name is required to create a docket case.']
                    ]
                ], 422);
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

        // Build the combined suspect name label for the primary case record title
        $allNames = array_filter(array_column($allSuspects, 'suspect_name'));
        if (empty($allNames)) {
            return response()->json([
                'message' => 'The suspect name field is required. You cannot save a case docket without entering suspect details.',
                'errors'  => [
                    'suspect_name' => ['Suspect Full Legal Name is required to create a docket case.']
                ]
            ], 422);
        }
        $jointSuspectNameLabel = count($allNames) > 1
            ? implode(', ', $allNames)
            : $allNames[0];

        // -----------------------------------------------------------------------
        // Smart Duplicate Case Detection (Server-side validation)
        // -----------------------------------------------------------------------
        if (!$request->boolean('confirm_duplicate') && !$request->boolean('ignore_duplicate')) {
            $detector = new \App\Services\CaseDuplicateDetector();
            $dupAnalysis = $detector->checkCaseDuplicate($validated, $allSuspects);
            if (!empty($dupAnalysis['duplicate_detected'])) {
                return response()->json($dupAnalysis, 409);
            }
        }

        // -----------------------------------------------------------------------
        // Auto-generate unique case reference number
        // -----------------------------------------------------------------------
        if ($request->filled('case_number')) {
            $caseRef = trim($request->input('case_number'));
            if (ForensicCase::where('case_number', $caseRef)->exists()) {
                $caseRef = "{$caseRef}-" . rand(100, 999);
            }
        } else {
            $prefix = ($request->filled('unit_name') && str_contains($request->input('unit_name'), 'Forensic')) ? 'FSD' : 'NFC';
            $year = date('Y');
            $randomNum = rand(1000, 9999);
            $caseRef = "{$prefix}-{$year}-{$randomNum}";
            while (ForensicCase::where('case_number', $caseRef)->exists()) {
                $randomNum = rand(1000, 9999);
                $caseRef = "{$prefix}-{$year}-{$randomNum}";
            }
        }

        $unit = Unit::where('name', $validated['unit_name'] ?? null)->first();

        // -----------------------------------------------------------------------
        // Shared (case-level) fields â€” seizures, exhibits, etc.
        // All suspect rows for the same joint case share these.
        // -----------------------------------------------------------------------
        $exhibitsSummary = $validated['exhibits_summary']
            ?? (!empty($validated['seizure_name'])
                ? implode(' ', array_filter([
                    !empty($validated['seizure_quantity']) ? "Qty: {$validated['seizure_quantity']}" : null,
                    !empty($validated['seizure_weight'])   ? "Wt: {$validated['seizure_weight']} " . ($validated['seizure_unit'] ?? '') : ($validated['seizure_unit'] ?? null),
                    $validated['seizure_name']
                  ]))
                : 'Logged Exhibits & Dossier');

        // Ensure docket_data array includes full suspects array and count
        $rawDocket = $request->input('docket_data');
        $docketData = is_array($rawDocket)
            ? $rawDocket
            : (json_decode($rawDocket ?? '{}', true) ?: []);
        $docketData['suspects'] = $allSuspects;
        $docketData['suspects_count'] = count($allSuspects);
        $docketData['full_name'] = $jointSuspectNameLabel;

        if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
            foreach ($docketData['seizures'] as $sIdx => $sz) {
                $assocPerson = $sz['associated_person'] ?? null;
                if (empty($assocPerson) || strtolower(trim($assocPerson)) === 'primary suspect' || $assocPerson === 'All Suspects') {
                    $docketData['seizures'][$sIdx]['associated_person'] = count($allNames) > 1 ? $jointSuspectNameLabel : $allNames[0];
                }
            }
        }

        $sharedFields = [
            'title'                    => $validated['title'],
            'unit_name'                => $validated['unit_name'] ?? ($user ? $user->unit : 'Forensic Directorate'),
            'unit_id'                  => $unit ? $unit->id : null,
            'user_id'                  => $user ? $user->id : null,
            'commanding_officer'       => $validated['commanding_officer'] ?? ($user ? $user->fullname : 'Cmdr. Directorate Head'),
            'submitting_officer'       => $validated['submitting_officer'] ?? ($user ? $user->fullname : 'Officer in Charge'),
            'suspect_docket_mode'      => $validated['suspect_docket_mode'] ?? null,
            'operation_code'           => $validated['operation_code'] ?? null,
            'operation_code_name'      => $validated['operation_code_name'] ?? null,
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
            'seizure_quantity'         => $validated['seizure_quantity'] ?? null,
            'seizure_packaging'        => $validated['seizure_packaging'] ?? null,
            'seizure_weight'           => $validated['seizure_weight'] ?? null,
            'seizure_unit'             => $validated['seizure_unit'] ?? null,
            'seizure_value'            => $validated['seizure_value'] ?? null,
            'seizure_concealment'      => $validated['seizure_concealment'] ?? null,
            'seizure_date'             => $validated['seizure_date'] ?? null,
            'seizure_time'             => $validated['seizure_time'] ?? null,
            'seizure_location'         => $validated['seizure_location'] ?? null,
            'seizure_officer'          => $validated['seizure_officer'] ?? null,
            'seizure_reason'           => $validated['seizure_reason'] ?? null,
            'exhibit_type'             => $validated['exhibit_type'] ?? null,
            'drug_classification'      => $validated['drug_classification'] ?? null,
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
            'status'                   => $validated['status'] ?? 'Under Multi-Unit Processing',
            'location'                 => $validated['location'] ?? ($validated['raid_location'] ?? 'NACOC Forensic Directorate Vault'),
            'description'              => $validated['description'] ?? ($validated['offense_description'] ?? 'Directorate Authorized Forensic Case File'),
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
            'detected_substances'      => $request->input('detected_substances') ?? null,
            'confirmatory_test_methods'=> $request->input('confirmatory_test_methods') ?? null,
            'confirmatory_specifications' => $request->input('confirmatory_specifications') ?? null,
            'confirmatory_remark'        => $request->input('confirmatory_remark') ?? ($request->input('remark') ?? null),
            'incident_date'            => $validated['arrest_date'] ?? now(),
        ];

        if (\Schema::hasColumn('cases', 'sample_weight_units')) {
            $sharedFields['sample_weight_units'] = $request->input('sample_weight_units') ?? null;
        }

        // -----------------------------------------------------------------------
        // Helper closure: map a suspect array to personal-detail DB columns
        // -----------------------------------------------------------------------
        $suspectFields = function (array $suspect) use ($validated, $normalizePhonesHelper, $request): array {
            $marital = !empty($suspect['marital_status']) && $suspect['marital_status'] !== 'Unspecified'
                ? $suspect['marital_status'] : null;

            // Build spouse/partner info string
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

        // -----------------------------------------------------------------------
        // CREATE SINGLE CASE RECORD (Supports multiple suspects in single row)
        // -----------------------------------------------------------------------
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

        AuditLogger::log(
            'CASE_CREATED',
            "New forensic case created with reference {$case->case_number} by " . ($user ? $user->fullname : 'System') . " ({$case->unit_name})",
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

        // Sync CaseSuspect models for multi-suspect case
        if ($case->underlyingRecord instanceof \App\Models\MultipleSuspectCase) {
            foreach ($allSuspects as $sIdx => $spItem) {
                $spNum = $sIdx + 1;
                $isAssocSp = $sIdx > 0 || !empty($spItem['is_associate']) || (!empty($spItem['role']) && str_contains(strtolower($spItem['role']), 'associate'));
                $roleVal = $spItem['role'] ?? ($spItem['association_role'] ?? ($isAssocSp ? 'Co-Suspect' : 'Primary Suspect'));
                $assocIdVal = $spItem['associate_id'] ?? ($isAssocSp ? ('ASC-' . str_pad($sIdx, 3, '0', STR_PAD_LEFT)) : null);

                \App\Models\CaseSuspect::updateOrCreate(
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
                        'pob'                      => $spItem['pob'] ?? null,
                        'phone'                    => $spItem['phone'] ?? null,
                        'phones'                   => $spItem['phones'] ?? null,
                        'address'                  => $spItem['address'] ?? null,
                        'marital_status'           => $spItem['marital_status'] ?? null,
                        'spouse_info'              => $spItem['spouse_info'] ?? null,
                        'spouse_name'              => $spItem['spouse_name'] ?? null,
                        'spouse_phone'             => $spItem['spouse_phone'] ?? null,
                        'partner_name'             => $spItem['partner_name'] ?? null,
                        'partner_phone'            => $spItem['partner_phone'] ?? null,
                        'partner_info'             => $spItem['partner_info'] ?? null,
                        'dependents'               => $spItem['dependents'] ?? null,
                        'holding_facility'         => $spItem['holding_facility'] ?? ($spItem['facility'] ?? ($validated['holding_facility'] ?? null)),
                        'raid_location'            => $spItem['raid_location'] ?? ($spItem['location'] ?? ($validated['raid_location'] ?? null)),
                        'offense_description'      => $spItem['offense'] ?? ($spItem['offense_description'] ?? ($validated['offense_description'] ?? null)),
                        'arrest_date'              => $spItem['date_of_arrest'] ?? ($spItem['arrest_date'] ?? ($validated['arrest_date'] ?? null)),
                        'arrest_time'              => $spItem['time_of_arrest'] ?? ($spItem['arrest_time'] ?? ($validated['arrest_time'] ?? null)),
                        'mugshot_front'            => $spItem['mugshot_front'] ?? ($sIdx === 0 ? $case->mugshot_front : null),
                        'mugshot_left'             => $spItem['mugshot_left'] ?? ($sIdx === 0 ? $case->mugshot_left : null),
                        'mugshot_right'            => $spItem['mugshot_right'] ?? ($sIdx === 0 ? $case->mugshot_right : null),
                    ]
                );
            }
        }

        // -----------------------------------------------------------------------
        // Initialize unit_progress & laboratory examination in docket_data
        // -----------------------------------------------------------------------
        $hasSubmittedLabData = !empty($request->input('presumptive_result'))
            || !empty($request->input('presumptive_test_result'))
            || !empty($request->input('presumptive_reaction_result_input'))
            || !empty($request->input('docket_data.lab_examination.presumptive_result'))
            || !empty($request->input('confirmatory_test_result'))
            || !empty($request->input('confirmatory_substance_identification'))
            || !empty($request->input('sample_weight_gross'))
            || !empty($request->input('sample_weight_taken'))
            || !empty($request->input('sample_weight_reserved'));

        $labStatus = $hasSubmittedLabData ? 'COMPLETED' : 'PENDING';
        $labOfficer = $hasSubmittedLabData ? ($user ? $user->fullname : ($case->submitting_officer ?: 'Lab Analyst')) : null;
        $labUpdatedAt = $hasSubmittedLabData ? now()->toDateTimeString() : null;

        $isLabCreatedCaseStore = !empty($case->unit_name) && (
            strtolower(trim($case->unit_name)) === 'lab analysis' ||
            str_contains(strtolower($case->unit_name), 'lab analysis') ||
            str_contains(strtolower($case->unit_name), 'laboratory')
        );

        $exCatStore = strtolower($validated['exhibit_category'] ?? ($request->input('exhibit_category') ?? ($case->exhibit_category ?? '')));
        $sDrugStore = $validated['seizure_name_drug'] ?? ($request->input('seizure_name_drug') ?? ($case->seizure_name_drug ?? ''));
        $sNameStore = strtolower($validated['seizure_name'] ?? ($request->input('seizure_name') ?? ($case->seizure_name ?? '')));

        $isDrugStore = !empty($sDrugStore) ||
            str_contains($exCatStore, 'drug') || str_contains($exCatStore, 'narcotic') || str_contains($exCatStore, 'substance') || str_contains($exCatStore, 'cannabis') || str_contains($exCatStore, 'cocaine') || str_contains($exCatStore, 'heroin') || str_contains($exCatStore, 'meth') || str_contains($exCatStore, 'tramadol') ||
            str_contains($sNameStore, 'cocaine') || str_contains($sNameStore, 'heroin') || str_contains($sNameStore, 'cannabis') || str_contains($sNameStore, 'weed') || str_contains($sNameStore, 'meth') || str_contains($sNameStore, 'tramadol') || str_contains($sNameStore, 'drug') || str_contains($sNameStore, 'narcotic');

        $isDrugStore = $case->isDrugCase();

        if ($isDrugStore) {
            $unitProgressBlock = [
                'Lab Analysis' => [
                    'status'     => $labStatus,
                    'officer'    => $labOfficer,
                    'updated_at' => $labUpdatedAt,
                ],
                'Evidence Management' => ['status' => 'PENDING', 'officer' => null, 'updated_at' => null],
            ];
        } elseif ($isLabCreatedCaseStore) {
            $unitProgressBlock = [
                'Evidence Management' => [
                    'status'     => 'COMPLETED',
                    'officer'    => $case->submitting_officer ?: 'Evidence Officer',
                    'updated_at' => now()->toDateTimeString(),
                ],
            ];
        } else {
            $unitProgressBlock = [
                'Crime Scene Support' => [
                    'status'     => 'COMPLETED',
                    'officer'    => $case->submitting_officer ?: 'Field Officer',
                    'updated_at' => now()->toDateTimeString(),
                ],
                'Evidence Management'          => ['status' => 'PENDING', 'officer' => null, 'updated_at' => null],
                'Digital & Physical Forensics' => ['status' => 'PENDING', 'officer' => null, 'updated_at' => null],
            ];
        }


        $caseDocketData = is_array($case->docket_data)
            ? $case->docket_data
            : (json_decode($case->docket_data ?? '[]', true) ?: []);

        $caseDocketData['unit_progress'] = $unitProgressBlock;

        if ($hasSubmittedLabData) {
            $presumptiveVal  = $request->input('presumptive_result')
                ?? ($validated['presumptive_result'] ?? ($validated['presumptive_test_result'] ?? ($request->input('presumptive_reaction_result_input') ?? ($request->input('docket_data.lab_examination.presumptive_result') ?? null))));
            $confirmatoryVal = $validated['confirmatory_test_result'] ?? ($request->input('confirmatory_test_result') ?? 'YES');
            $labNum          = $request->input('laboratory_number') ?? ($request->input('lab_number') ?? ('Lab No. ' . rand(10, 99) . '/FS' . date('m') . '/' . date('Y')));
            $labExamRef      = $request->input('lab_exam_reference') ?? ($request->input('exam_reference') ?? ('GSA-FM-T' . rand(10, 99) . '-F'));
            $labExhNo        = 'EXH-' . date('Y') . '-' . sprintf('%03d', $case->id);
            $substanceId     = $request->input('confirmatory_substance_identification') ?? ($request->input('docket_data.lab_examination.substance_identification') ?? null);
            $sampleDesc      = $request->input('sample_weight_description') ?? ($request->input('docket_data.lab_examination.sample_weight_description') ?? null);
            $sampleUnits     = $request->input('sample_weight_units') ?? ($request->input('docket_data.lab_examination.sample_weight_units') ?? null);
            $sampleGross     = $request->input('sample_weight_gross') ?? ($request->input('docket_data.lab_examination.sample_weight_gross') ?? null);
            $sampleTaken     = $request->input('sample_weight_taken') ?? ($request->input('sample_taken') ?? ($request->input('docket_data.lab_examination.sample_weight_taken') ?? ($request->input('docket_data.lab_examination.sample_taken') ?? null)));
            $sampleReserved  = $request->input('sample_weight_reserved') ?? ($request->input('sample_reserved') ?? ($request->input('docket_data.lab_examination.sample_weight_reserved') ?? ($request->input('docket_data.lab_examination.sample_reserved') ?? null)));

            // Auto-derive weight fields from the primary exhibit when the form did not submit them
            // (happens when JS didn't pre-populate the read-only manifest-synced fields)
            if (empty($sampleDesc) || empty($sampleGross) || empty($sampleUnits)) {
                $primaryExhForWeight = \App\Models\Exhibit::where(function($q) use ($case) {
                    if ($case->underlyingRecord instanceof \App\Models\SingleSuspectCase) {
                        $q->where('single_suspect_case_id', $case->id);
                    } else {
                        $q->where('multiple_suspect_case_id', $case->id);
                    }
                })->whereNotNull('seizure_weight')->first()
                ?? \App\Models\Exhibit::where(function($q) use ($case) {
                    if ($case->underlyingRecord instanceof \App\Models\SingleSuspectCase) {
                        $q->where('single_suspect_case_id', $case->id);
                    } else {
                        $q->where('multiple_suspect_case_id', $case->id);
                    }
                })->first();

                if ($primaryExhForWeight) {
                    if (empty($sampleDesc)) {
                        $sampleDesc = $primaryExhForWeight->commodity_name ?? null;
                    }
                    if (empty($sampleUnits)) {
                        $sampleUnits = trim(($primaryExhForWeight->seizure_quantity ?? '') . ' ' . ($primaryExhForWeight->seizure_packaging ?? '')) ?: null;
                    }
                    if (empty($sampleGross)) {
                        $sampleGross = trim(($primaryExhForWeight->seizure_weight ?? '') . ' ' . ($primaryExhForWeight->seizure_unit ?? '')) ?: null;
                    }
                }
            }
            $detSubs         = $request->input('detected_substances');
            if (is_string($detSubs)) {
                $detSubs = json_decode($detSubs, true) ?: [];
            }
            if (!is_array($detSubs)) {
                $detSubs = [];
            }
            $testMethods = $request->input('confirmatory_test_methods');
            $specs       = $request->input('confirmatory_specifications');
            $remark      = $request->input('confirmatory_remark') ?? $request->input('remark');
            $officer     = $labOfficer;

            $primarySuspectName = $primarySuspect['suspect_name'] ?? $case->suspect_name;

            $labExamRecord = [
                'exhibit_owner_name'       => $primarySuspectName,
                'exhibit_is_associate'     => false,
                'laboratory_number'        => $labNum,
                'exam_reference'           => $labExamRef,
                'exhibit_number'           => $labExhNo,
                'lab_unit'                 => 'Chemistry & Narcotics Unit',
                'analyst'                  => $officer,
                'date_received'            => $validated['arrest_date'] ?? date('Y-m-d'),
                'date_started'             => date('Y-m-d'),
                'date_completed'           => date('Y-m-d'),
                'presumptive_result'       => $presumptiveVal,
                'confirmatory_result'      => $confirmatoryVal,
                'sample_weight'            => [
                    'description'     => $sampleDesc,
                    'units_packaging' => $sampleUnits,
                    'gross_weight'    => $sampleGross,
                    'sample_taken'    => $sampleTaken,
                    'reserved_samples' => $sampleReserved,
                ],
                'substance_identification' => $substanceId,
                'detected_substances'      => $detSubs,
                'test_methods'             => $testMethods,
                'specifications'           => $specs,
                'remark'                   => $remark,
                'findings'                 => 'Submitted via Laboratory Analysis & Case Registration Workspace',
                'has_saved'                => true,
            ];

            $caseDocketData['lab_examination'] = $labExamRecord;
            $caseDocketData['lab_examinations_list'] = [$labExamRecord];

            if (!empty($caseDocketData['seizures']) && is_array($caseDocketData['seizures']) && count($caseDocketData['seizures']) > 0 && !empty($caseDocketData['seizures'][0])) {
                $caseDocketData['seizures'][0]['has_saved_lab'] = true;
                $caseDocketData['seizures'][0]['presumptive_result'] = $presumptiveVal;
                $caseDocketData['seizures'][0]['confirmatory_result'] = $confirmatoryVal;
                $caseDocketData['seizures'][0]['substance_identification'] = $substanceId;
                $caseDocketData['seizures'][0]['sample_description'] = $sampleDesc;
                $caseDocketData['seizures'][0]['sample_units'] = $sampleUnits;
                $caseDocketData['seizures'][0]['sample_gross'] = $sampleGross;
                $caseDocketData['seizures'][0]['sample_taken'] = $sampleTaken;
                $caseDocketData['seizures'][0]['sample_reserved'] = $sampleReserved;
                $caseDocketData['seizures'][0]['test_methods'] = $testMethods;
                $caseDocketData['seizures'][0]['specifications'] = $specs;
                $caseDocketData['seizures'][0]['remark'] = $remark;
                $caseDocketData['seizures'][0]['findings'] = 'Submitted via Laboratory Analysis & Case Registration Workspace';
                $caseDocketData['seizures'][0]['analyst'] = $officer;
                $caseDocketData['seizures'][0]['date_received'] = $validated['arrest_date'] ?? date('Y-m-d');
                $caseDocketData['seizures'][0]['date_started'] = date('Y-m-d');
                $caseDocketData['seizures'][0]['date_completed'] = date('Y-m-d');
            }

            $primaryExhModel = \App\Models\Exhibit::where('single_suspect_case_id', $case->id)
                ->orWhere('multiple_suspect_case_id', $case->id)
                ->first();

            \App\Models\LaboratoryExaminationResult::updateOrCreate(
                [
                    'single_suspect_case_id'  => $case->underlyingRecord instanceof \App\Models\SingleSuspectCase ? $case->id : null,
                    'multiple_suspect_case_id' => $case->underlyingRecord instanceof \App\Models\MultipleSuspectCase ? $case->id : null,
                ],
                [
                    'case_type'               => $case->underlyingRecord instanceof \App\Models\MultipleSuspectCase ? 'MULTIPLE' : 'SINGLE',
                    'single_suspect_case_id'  => $case->underlyingRecord instanceof \App\Models\SingleSuspectCase ? $case->id : null,
                    'multiple_suspect_case_id'=> $case->underlyingRecord instanceof \App\Models\MultipleSuspectCase ? $case->id : null,
                    'exhibit_id'              => $primaryExhModel ? $primaryExhModel->id : null,
                    'laboratory_number'       => $labNum,
                    'presumptive_result'      => $presumptiveVal,
                    'confirmatory_result'     => $confirmatoryVal,
                    'sample_weight_description'=> $sampleDesc,
                    'sample_weight_units'     => $sampleUnits,
                    'sample_weight_gross'     => $sampleGross,
                    'sample_weight_taken'     => $sampleTaken,
                    'sample_weight_reserved'  => $sampleReserved,
                    'substance_identification'=> $substanceId,
                    'detected_substances'     => $detSubs,
                    'test_methods'            => $testMethods,
                    'specifications'          => $specs,
                    'remark'                  => $remark,
                    'findings'                => 'Submitted via Laboratory Analysis & Case Registration Workspace',
                    'analyst'                 => $officer,
                    'has_saved'               => true,
                ]
            );

            \App\Models\AssociateLabExamination::where('case_id', $case->id)
                ->where(function($q) use ($primarySuspectName) {
                    $q->where('suspect_name', $primarySuspectName)
                      ->orWhere('suspect_name', 'Primary Suspect');
                })->delete();
        }

        $case->docket_data = $caseDocketData;
        $case->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success'        => true,
                'message'        => "Case {$case->case_number} created with " . count($allSuspects) . " suspect(s) saved under single Case ID.",
                'case'           => $case,
                'suspects_count' => count($allSuspects),
            ]);
        }

        return back()->with('status', "Case {$case->case_number} created and saved to database successfully.");
    }

    /**
     * Display dedicated full-page editor for updating case record & correcting data.
     */
    public function showEdit($id)
    {
        $case = $this->findCase($id);
        if (!$case) {
            return redirect('/cases')->with('error', 'Case file not found.');
        }
        $this->repairExhibitAssociations($case);
        return view('cases.edit', compact('case'));
    }

    /**
     * Update case data and correct mistakes with strict Unit RBAC permission guards.
     */
    public function updateCase($id, Request $request)
    {
        $case = $this->findCase($id);
        if (!$case) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Case not found.'], 404);
            }
            return redirect('/cases')->with('error', 'Case not found.');
        }

        $user = Auth::user();
        $userUnit = strtolower(trim($user->unit ?? ''));
        $isHead = !empty($user->is_head);

        $isCrimeSceneUnit = $isHead || str_contains($userUnit, 'crime') || str_contains($userUnit, 'scene') || str_contains($userUnit, 'support') || empty($userUnit);
        $isLabUnit = $isHead || str_contains($userUnit, 'lab') || str_contains($userUnit, 'chemistry') || str_contains($userUnit, 'narcotics');

        $docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);

        // Reject unauthorized cross-unit edits
        if (!$isLabUnit && ($request->filled('confirmatory_test_result') || $request->filled('confirmatory_substance_identification'))) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access Denied: Crime Scene Support officers cannot modify Laboratory Analysis examination results.'
                ], 403);
            }
            return back()->with('error', 'Access Denied: Crime Scene Support officers cannot modify Laboratory Analysis examination results.');
        }

        if (!$isCrimeSceneUnit && ($request->filled('suspect_name') || $request->filled('arrest_date') || $request->filled('seizure_name_drug'))) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access Denied: Laboratory Analysts cannot modify Crime Scene arrest and seizure records.'
                ], 403);
            }
            return back()->with('error', 'Access Denied: Laboratory Analysts cannot modify Crime Scene arrest and seizure records.');
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
            if ($request->filled('commanding_officer')) $case->commanding_officer = trim($request->input('commanding_officer'));
            if ($request->filled('submitting_officer')) $case->submitting_officer = trim($request->input('submitting_officer'));

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

            // Handle extended suspect attributes & DOB auto-calculation
            if ($request->has('dob')) {
                $dobInputVal = trim($request->input('dob'));
                $case->dob = $dobInputVal;
                $docketData['suspects'][0]['dob'] = $dobInputVal;
                $docketData['dob'] = $dobInputVal;

                $calcAgeFromDob = null;
                if (!empty($dobInputVal)) {
                    try {
                        $calcAgeFromDob = \Carbon\Carbon::parse($dobInputVal)->age;
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

            // Handle Mugshots
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

            // Sync docket_data
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
            if ($request->has('sample_weight_units') && \Schema::hasColumn('cases', 'sample_weight_units')) {
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
                $f = $request->input('findings') ?? $request->input('notes');
                $case->laboratory_analytical_findings = $f;
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

                    // Synchronize case title if suspect name changed
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
                    $docketData['suspects'][$sIdx]['ghana_card']           = $spData['ghana_card'] ?? '';
                    $docketData['suspects'][$sIdx]['gender']               = $spData['gender'] ?? 'Male';
                    $docketData['suspects'][$sIdx]['age']                  = $spData['age'] ?? '';
                    $docketData['suspects'][$sIdx]['phone']                = $spData['phone'] ?? '';
                    $docketData['suspects'][$sIdx]['address']              = $spData['address'] ?? '';
                    $docketData['suspects'][$sIdx]['offense']              = $spData['offense_description'] ?? '';
                    $docketData['suspects'][$sIdx]['date_of_arrest']      = $spData['arrest_date'] ?? '';
                    $docketData['suspects'][$sIdx]['time_of_arrest']      = $spData['arrest_time'] ?? '';
                    $docketData['suspects'][$sIdx]['location_of_arrest']   = $spData['raid_location'] ?? '';
                    $docketData['suspects'][$sIdx]['holding_facility']     = $spData['holding_facility'] ?? '';
                    $spDob = trim($spData['dob'] ?? '');
                    $spCalcAge = null;
                    if (!empty($spDob)) {
                        try {
                            $spCalcAge = \Carbon\Carbon::parse($spDob)->age;
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
                    $docketData['suspects'][$sIdx]['has_children']          = !empty($spData['has_children']) || ((int)($spData['children_count'] ?? 0) > 0);
                    $docketData['suspects'][$sIdx]['spouse_name']           = $spData['spouse_name'] ?? ($spData['partner_name'] ?? '');
                    $docketData['suspects'][$sIdx]['spouse_phone']          = $spData['spouse_phone'] ?? ($spData['partner_phone'] ?? '');
                    $docketData['suspects'][$sIdx]['spouse_info']           = $spData['spouse_info'] ?? ($spData['partner_info'] ?? '');
                    $docketData['suspects'][$sIdx]['spouse_summary']        = $spData['spouse_summary'] ?? '';
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

                    if ($case->underlyingRecord instanceof \App\Models\MultipleSuspectCase) {
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
                    if ($case->underlyingRecord instanceof \App\Models\MultipleSuspectCase) {
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
                            if (isset($exObj['evidence_photos'])) {
                                $case->evidence_photos = $docketData['seizures'][$eIdx]['evidence_photos'];
                                $docketData['evidence_photos'] = $docketData['seizures'][$eIdx]['evidence_photos'];
                            }
                        }

                        $exRecord = \App\Models\Exhibit::where('single_suspect_case_id', $case->id)
                            ->orWhere('multiple_suspect_case_id', $case->id)
                            ->skip($eIdx)->first();
                        if ($exRecord) {
                            $exRecord->update(array_filter([
                                'commodity_name'    => $szName ?: null,
                                'exhibit_category'   => $exObj['exhibit_category'] ?? null,
                                'seizure_quantity'   => $exObj['seizure_quantity'] ?? null,
                                'seizure_weight'     => $exObj['seizure_weight'] ?? null,
                                'seizure_unit'       => $exObj['seizure_unit'] ?? null,
                                'seizure_packaging'  => $exObj['seizure_packaging'] ?? null,
                                'seizure_reason'     => $exObj['seizure_reason'] ?? null,
                                'other_exhibit_description' => $exObj['other_exhibit_description'] ?? ($exObj['other_description'] ?? null),
                                'ammunition_details' => $exObj['ammunition_details'] ?? null,
                                'evidence_photos'    => !empty($exObj['evidence_photos']) ? array_values($exObj['evidence_photos']) : null,
                            ], fn($v) => !is_null($v)));
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

                if (!empty($labsArray)) {
                    foreach ($labsArray as $lIdx => $labObj) {
                        $labExamRecord = [
                            'laboratory_number'         => $labObj['laboratory_number'] ?? '',
                            'presumptive_result'        => $labObj['presumptive_result'] ?? '',
                            'confirmatory_result'       => $labObj['confirmatory_test_result'] ?? 'YES',
                            'sample_weight'             => [
                                'description'      => $labObj['sample_weight_description'] ?? '',
                                'units_packaging'  => $labObj['sample_weight_units'] ?? '',
                                'gross_weight'     => $labObj['sample_weight_gross'] ?? '',
                                'sample_taken'     => $labObj['sample_weight_taken'] ?? '',
                                'reserved_samples' => $labObj['sample_weight_reserved'] ?? '',
                            ],
                            'substance_identification'  => $labObj['confirmatory_substance_identification'] ?? ($labObj['substance_identification'] ?? ''),
                            'detected_substances'       => $labObj['detected_substances'] ?? [],
                            'test_methods'              => $labObj['confirmatory_test_methods'] ?? ($labObj['test_methods'] ?? ''),
                            'specifications'            => $labObj['confirmatory_specifications'] ?? ($labObj['specifications'] ?? ''),
                            'remark'                    => $labObj['confirmatory_remark'] ?? ($labObj['remark'] ?? ''),
                            'findings'                  => $labObj['laboratory_analytical_findings'] ?? ($labObj['findings'] ?? ''),
                            'has_saved'                 => 1,
                        ];

                        if (!isset($docketData['lab_examinations_list'][$lIdx])) {
                            $docketData['lab_examinations_list'][$lIdx] = [];
                        }
                        $docketData['lab_examinations_list'][$lIdx] = array_merge($docketData['lab_examinations_list'][$lIdx], $labExamRecord);

                        if ($lIdx === 0) {
                            $docketData['lab_examination'] = $labExamRecord;
                            $docketData['confirmatory_detected_substances'] = $labObj['detected_substances'] ?? [];

                            if (!empty($labObj['laboratory_number'])) $case->laboratory_number = $labObj['laboratory_number'];
                            if (!empty($labObj['presumptive_result'])) $case->presumptive_test_result = $labObj['presumptive_result'];
                            if (!empty($labObj['confirmatory_test_result'])) $case->confirmatory_test_result = $labObj['confirmatory_test_result'];
                            if (isset($labObj['sample_weight_description'])) $case->sample_weight_description = $labObj['sample_weight_description'];
                            if (isset($labObj['sample_weight_units']) && \Schema::hasColumn('cases', 'sample_weight_units')) $case->sample_weight_units = $labObj['sample_weight_units'];
                            if (isset($labObj['sample_weight_gross'])) $case->sample_weight_gross = $labObj['sample_weight_gross'];
                            if (isset($labObj['sample_weight_taken'])) $case->sample_weight_taken = $labObj['sample_weight_taken'];
                            if (isset($labObj['sample_weight_reserved'])) $case->sample_weight_reserved = $labObj['sample_weight_reserved'];
                            if (isset($labObj['substance_identification'])) $case->confirmatory_substance_identification = $labObj['substance_identification'];
                            if (isset($labObj['test_methods'])) $case->confirmatory_test_methods = $labObj['test_methods'];
                            if (isset($labObj['specifications'])) $case->confirmatory_specifications = $labObj['specifications'];
                            if (isset($labObj['confirmatory_remark'])) $case->confirmatory_remark = $labObj['confirmatory_remark'];
                            if (isset($labObj['remark'])) $case->confirmatory_remark = $labObj['remark'];
                            if (isset($labObj['findings'])) $case->laboratory_analytical_findings = $labObj['findings'];
                        }

                        // AssociateLabExamination table sync for associate/extra lab exams
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
                                (!empty($primarySuspectName) && strtolower(trim($ownerName)) === strtolower(trim($primarySuspectName)))
                            )
                        );

                        if ($isPrimaryOwner) {
                            $primaryExh = \App\Models\Exhibit::where('single_suspect_case_id', $case->id)
                                ->orWhere('multiple_suspect_case_id', $case->id)
                                ->first();

                            \App\Models\LaboratoryExaminationResult::updateOrCreate(
                                [
                                    'single_suspect_case_id'  => $case->underlyingRecord instanceof \App\Models\SingleSuspectCase ? $case->id : null,
                                    'multiple_suspect_case_id' => $case->underlyingRecord instanceof \App\Models\MultipleSuspectCase ? $case->id : null,
                                ],
                                [
                                    'case_type'               => $case->underlyingRecord instanceof \App\Models\MultipleSuspectCase ? 'MULTIPLE' : 'SINGLE',
                                    'single_suspect_case_id'  => $case->underlyingRecord instanceof \App\Models\SingleSuspectCase ? $case->id : null,
                                    'multiple_suspect_case_id'=> $case->underlyingRecord instanceof \App\Models\MultipleSuspectCase ? $case->id : null,
                                    'exhibit_id'              => $primaryExh ? $primaryExh->id : null,
                                    'laboratory_number'       => $labObj['laboratory_number'] ?? ($case->laboratory_number ?? null),
                                    'presumptive_result'      => $labObj['presumptive_result'] ?? null,
                                    'confirmatory_result'     => $labObj['confirmatory_test_result'] ?? ($labObj['confirmatory_result'] ?? 'YES'),
                                    'sample_weight_description'=> $labObj['sample_weight_description'] ?? ($labObj['sample_weight']['description'] ?? null),
                                    'sample_weight_units'     => $labObj['sample_weight_units'] ?? ($labObj['sample_weight']['units_packaging'] ?? null),
                                    'sample_weight_gross'     => $labObj['sample_weight_gross'] ?? ($labObj['sample_weight']['gross_weight'] ?? null),
                                    'sample_weight_taken'     => $labObj['sample_weight_taken'] ?? ($labObj['sample_taken'] ?? ($labObj['sample_weight']['sample_taken'] ?? null)),
                                    'sample_weight_reserved'  => $labObj['sample_weight_reserved'] ?? ($labObj['sample_reserved'] ?? ($labObj['sample_weight']['reserved_samples'] ?? null)),
                                    'substance_identification'=> $labObj['substance_identification'] ?? null,
                                    'detected_substances'     => $labObj['detected_substances'] ?? [],
                                    'test_methods'            => $labObj['test_methods'] ?? null,
                                    'specifications'          => $labObj['specifications'] ?? null,
                                    'remark'                  => $labObj['confirmatory_remark'] ?? ($labObj['remark'] ?? null),
                                    'findings'                => $labObj['findings'] ?? null,
                                    'has_saved'               => true,
                                ]
                            );

                            if ($case->underlyingRecord) {
                                $case->underlyingRecord->update([
                                    'sample_weight_taken' => $labObj['sample_weight_taken'] ?? ($labObj['sample_taken'] ?? ($labObj['sample_weight']['sample_taken'] ?? null)),
                                    'sample_weight_reserved' => $labObj['sample_weight_reserved'] ?? ($labObj['sample_reserved'] ?? ($labObj['sample_weight']['reserved_samples'] ?? null)),
                                    'confirmatory_test_result' => $labObj['confirmatory_test_result'] ?? ($labObj['confirmatory_result'] ?? 'YES'),
                                    'confirmatory_substance_identification' => $labObj['substance_identification'] ?? null,
                                    'confirmatory_test_methods' => $labObj['test_methods'] ?? null,
                                    'confirmatory_specifications' => $labObj['specifications'] ?? null,
                                    'confirmatory_remark' => $labObj['confirmatory_remark'] ?? ($labObj['remark'] ?? null),
                                ]);
                            }

                            if ($ownerName) {
                                \App\Models\AssociateLabExamination::where('case_id', $case->id)
                                    ->where(function($q) use ($ownerName) {
                                        $q->where('suspect_name', $ownerName)
                                          ->orWhere('suspect_name', 'Primary Suspect');
                                    })->delete();
                            }
                        } else {
                            $dbLabObj = \App\Models\AssociateLabExamination::where('case_id', $case->id)
                                ->where(function($q) use ($lIdx, $ownerName) {
                                    if ($ownerName) $q->where('suspect_name', $ownerName);
                                })->first();

                            \App\Models\AssociateLabExamination::updateOrCreate(
                                [
                                    'id'      => $dbLabObj ? $dbLabObj->id : null,
                                    'case_id' => $case->id,
                                ],
                                [
                                    'case_id'                   => $case->id,
                                    'suspect_name'              => $ownerName ?: 'Associate Suspect',
                                    'laboratory_number'         => $labObj['laboratory_number'] ?? null,
                                    'presumptive_result'        => $labObj['presumptive_result'] ?? null,
                                    'confirmatory_result'       => $labObj['confirmatory_test_result'] ?? ($labObj['confirmatory_result'] ?? 'YES'),
                                    'sample_weight_description' => $labObj['sample_weight_description'] ?? null,
                                    'sample_weight_units'       => $labObj['sample_weight_units'] ?? null,
                                    'sample_weight_gross'       => $labObj['sample_weight_gross'] ?? null,
                                    'sample_weight_taken'       => $labObj['sample_weight_taken'] ?? null,
                                    'sample_weight_reserved'    => $labObj['sample_weight_reserved'] ?? null,
                                    'substance_identification'  => $labObj['substance_identification'] ?? null,
                                    'detected_substances'       => $labObj['detected_substances'] ?? [],
                                    'test_methods'              => $labObj['test_methods'] ?? null,
                                    'specifications'            => $labObj['specifications'] ?? null,
                                    'findings'                  => $labObj['findings'] ?? null,
                                    'has_saved'                 => true,
                                ]
                            );
                        }
                    }
                }
            }
        }

        $case->docket_data = $docketData;
        $case->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Case {$case->case_number} details updated successfully.",
                'case'    => $case,
            ]);
        }

        return redirect()->route('cases.review', $case->case_number)->with('status', "Case {$case->case_number} details updated successfully.");
    }

    /**
     * Update unit findings and unit completion status for a case across the 4 specialized units.
     */
    public function updateUnitProgress($id, Request $request)
    {
        $case = $this->findCase($id);
        if (!$case) {
            return response()->json(['success' => false, 'message' => 'Case not found.'], 404);
        }

        $user = Auth::user();
        $unitName = $request->input('unit_name') ?? ($user ? $user->unit : 'Crime Scene Support');
        $unitNotes = $request->input('unit_notes') ?? $request->input('notes');
        $unitStatus = strtoupper($request->input('unit_status', 'COMPLETED'));
        if (!in_array($unitStatus, ['COMPLETED', 'IN_PROGRESS', 'PENDING'])) {
            $unitStatus = $request->boolean('mark_completed', true) ? 'COMPLETED' : 'IN_PROGRESS';
        }
        $officer = $request->input('officer') ?? ($user ? $user->fullname : 'Unit Officer');

        // Normalize unit name
        if (str_contains(strtolower($unitName), 'lab')) {
            $unitNameKey = 'Lab Analysis';
        } elseif (str_contains(strtolower($unitName), 'evidence')) {
            $unitNameKey = 'Evidence Management';
        } elseif (str_contains(strtolower($unitName), 'digital') || str_contains(strtolower($unitName), 'cyber')) {
            $unitNameKey = 'Digital & Physical Forensics';
        } else {
            $unitNameKey = 'Crime Scene Support';
        }

        $docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
        $exOwnerName = $request->input('exhibit_owner_name');

        if ($unitNameKey === 'Lab Analysis' || $request->has('lab_unit') || $request->has('presumptive_result')) {
            $unexaminedJointSuspects = $this->getUnexaminedLabSuspects($case, $docketData, $exOwnerName);
            if (!empty($unexaminedJointSuspects)) {
                $unitStatus = 'IN_PROGRESS';
            }
        }
        
        if (!isset($docketData['unit_progress'])) {
            $reqUnits = $case->getWorkflowUnits();
            $initProg = [];
            foreach ($reqUnits as $rU) {
                $initProg[$rU] = ['status' => 'PENDING', 'officer' => null, 'updated_at' => null];
            }
            if (isset($initProg['Crime Scene Support'])) {
                $initProg['Crime Scene Support'] = [
                    'status'     => 'COMPLETED',
                    'officer'    => $case->commanding_officer ?? 'Initial Field Officer',
                    'updated_at' => $case->created_at ? $case->created_at->toDateTimeString() : now()->toDateTimeString(),
                ];
            }
            $docketData['unit_progress'] = $initProg;
        }

        $docketData['unit_progress'][$unitNameKey] = [
            'status'     => $unitStatus,
            'officer'    => $officer,
            'notes'      => $unitNotes,
            'updated_at' => now()->toDateTimeString(),
        ];

        if ($unitNameKey === 'Lab Analysis') {
            $case->lab_status = $unitStatus;
        }

        if ($unitNameKey === 'Lab Analysis' || $request->has('lab_unit') || $request->has('presumptive_result')) {
            $submittedExhibitId = $request->input('exhibit_id');
            $submittedDrugType = $request->input('drug_type');

            if (!empty($submittedExhibitId)) {
                $exhibitModel = null;
                $exhibitDataInDocket = null;

                if (is_numeric($submittedExhibitId)) {
                    $exhibitModel = \App\Models\Exhibit::find((int)$submittedExhibitId);
                }

                if (!$exhibitModel && !empty($docketData['seizures']) && is_array($docketData['seizures'])) {
                    foreach ($docketData['seizures'] as $sIdx => $sz) {
                        if ((isset($sz['id']) && (string)$sz['id'] === (string)$submittedExhibitId) || (isset($sz['exhibit_number']) && (string)$sz['exhibit_number'] === (string)$submittedExhibitId) || (string)($sIdx + 1) === (string)$submittedExhibitId) {
                            $exhibitDataInDocket = $sz;
                            break;
                        }
                    }
                }

                // 1. Check exhibit exists
                if (!$exhibitModel && !$exhibitDataInDocket) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Backend validation failed: The selected exhibit does not exist.'
                    ], 422);
                }

                // 2. Check exhibit belongs to current case
                if ($exhibitModel) {
                    $belongsToCase = ($exhibitModel->case_type === 'SINGLE' && (int)$exhibitModel->single_suspect_case_id === (int)$case->id)
                        || ($exhibitModel->case_type === 'MULTIPLE' && (int)$exhibitModel->multiple_suspect_case_id === (int)$case->id);
                    if (!$belongsToCase) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Backend validation failed: Selected exhibit does not belong to the current case.'
                        ], 422);
                    }
                }

                // 3. Check exhibit belongs to selected suspect
                if (!empty($exOwnerName)) {
                    $exOwnerLower = strtolower(trim($exOwnerName));
                    $isJointOwner = str_contains($exOwnerLower, ',') || str_contains($exOwnerLower, '&') || str_contains($exOwnerLower, 'all suspect');
                    
                    if (!$isJointOwner) {
                        $actualExhibitOwner = null;
                        if ($exhibitModel) {
                            $exSuspect = $exhibitModel->suspect;
                            $actualExhibitOwner = $exSuspect ? $exSuspect->suspect_name : null;
                        } elseif ($exhibitDataInDocket) {
                            $actualExhibitOwner = $exhibitDataInDocket['associated_person'] ?? ($exhibitDataInDocket['suspect_name'] ?? ($exhibitDataInDocket['owner_name'] ?? null));
                        }

                        if (!empty($actualExhibitOwner)) {
                            $actualOwnerLower = strtolower(trim($actualExhibitOwner));
                            $isExhibitJoint = str_contains($actualOwnerLower, 'all suspect') || str_contains($actualOwnerLower, ',');
                            if (!$isExhibitJoint && $actualOwnerLower !== $exOwnerLower && !str_contains($actualOwnerLower, $exOwnerLower) && !str_contains($exOwnerLower, $actualOwnerLower)) {
                                return response()->json([
                                    'success' => false,
                                    'message' => 'Backend validation failed: Selected exhibit does not belong to the selected suspect (' . $exOwnerName . ').'
                                ], 422);
                            }
                        }
                    }
                }

                // 4. Check exhibit is a drug exhibit
                $exCat = strtolower(trim($exhibitModel ? ($exhibitModel->exhibit_category ?? '') : ($exhibitDataInDocket['category'] ?? ($exhibitDataInDocket['exhibit_category'] ?? ''))));
                $exName = strtolower(trim($exhibitModel ? ($exhibitModel->commodity_name ?? '') : ($exhibitDataInDocket['seizure_name_drug'] ?? ($exhibitDataInDocket['seizure_name'] ?? ($exhibitDataInDocket['commodity_name'] ?? '')))));
                
                $nonDrugKws = ['weapon', 'firearm', 'pistol', 'rifle', 'shotgun', 'ammunition', 'vehicle', 'truck', 'car', 'cash', 'currency', 'usd', 'ghs', 'laptop', 'phone', 'electronics', 'document'];
                $isNonDrug = false;
                foreach ($nonDrugKws as $kw) {
                    if (str_contains($exCat, $kw) || str_contains($exName, $kw)) {
                        $isNonDrug = true;
                        break;
                    }
                }
                if ($isNonDrug) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Backend validation failed: The selected exhibit is not a drug exhibit.'
                    ], 422);
                }

                // 5. Check selected drug type corresponds to that exhibit
                if (!empty($submittedDrugType)) {
                    $submittedDrugLower = strtolower(trim($submittedDrugType));
                    if ($submittedDrugLower !== 'other' && $submittedDrugLower !== 'other drug / unknown') {
                        $matchesDrugType = str_contains($exName, $submittedDrugLower)
                            || str_contains($exCat, $submittedDrugLower)
                            || str_contains(strtolower($exhibitModel ? ($exhibitModel->substance_identification ?? '') : ($exhibitDataInDocket['substance_identification'] ?? '')), $submittedDrugLower);
                        
                        if (!$matchesDrugType) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Backend validation failed: Selected drug type (' . $submittedDrugType . ') does not correspond to the selected exhibit.'
                            ], 422);
                        }
                    }
                }
            }

            $isAssoc = $request->boolean('exhibit_is_associate');
            $primaryName = $case->suspect_name ?? '';

            // Canonical Suspect Name Resolution
            $canonicalOwnerName = trim($exOwnerName ?? '');
            if (!empty($docketData['suspects']) && is_array($docketData['suspects'])) {
                $cleanInput = mb_strtolower(preg_replace('/[^a-z0-9]/', '', $canonicalOwnerName));
                foreach ($docketData['suspects'] as $sp) {
                    $spClean = mb_strtolower(preg_replace('/[^a-z0-9]/', '', $sp['suspect_name'] ?? ''));
                    if ($spClean === $cleanInput || str_contains($spClean, $cleanInput) || str_contains($cleanInput, $spClean)) {
                        $canonicalOwnerName = trim($sp['suspect_name']);
                        break;
                    }
                }
            }

            if (!empty($canonicalOwnerName)) {
                $exOwnerLower = strtolower(trim($canonicalOwnerName));
                $isJointName = str_contains($exOwnerLower, ',') || str_contains($exOwnerLower, '&') || str_contains($exOwnerLower, 'all suspect') || str_contains($exOwnerLower, 'all suspects');
                if ($isJointName) {
                    $isAssoc = false;
                } elseif (!empty($primaryName) && strtolower(trim($primaryName)) !== $exOwnerLower) {
                    $isCoSuspect = false;
                    if (!empty($docketData['suspects'])) {
                        foreach ($docketData['suspects'] as $sp) {
                            if (!empty($sp['suspect_name']) && strtolower(trim($sp['suspect_name'])) === $exOwnerLower) {
                                if (!empty($sp['association_role']) && str_contains(strtolower($sp['association_role']), 'co-suspect')) {
                                    $isCoSuspect = true;
                                }
                                break;
                            }
                        }
                    }
                    $isAssoc = !$isCoSuspect;
                }
            }

            $labNum = $request->input('laboratory_number') ?: ($request->input('lab_number') ?: ($case->laboratory_number ?: ($docketData['lab_examination']['laboratory_number'] ?? ('Lab No. ' . rand(10, 99) . '/FS' . date('m') . '/' . date('Y')))));
            if ($labNum) {
                $case->laboratory_number = $labNum;
            }

            $labExamRef = $request->input('lab_exam_reference') ?: ($request->input('exam_reference') ?: ($docketData['lab_examination']['exam_reference'] ?? ('GSA-FM-T' . rand(10, 99) . '-F')));
            $labExhNo = $request->input('lab_exhibit_number') ?: ($docketData['lab_examination']['exhibit_number'] ?? null);
            $labUnitVal = $request->input('lab_unit') ?: ($docketData['lab_examination']['lab_unit'] ?? null);

            $presumptiveVal = $request->input('presumptive_result') ?? $request->input('presumptive_reaction_result_input') ?? $request->input('presumptive_test_result');
            if ($presumptiveVal !== null && $presumptiveVal !== '') {
                $case->presumptive_test_result = $presumptiveVal;
            }

            $confirmatoryVal = $request->input('confirmatory_test_result') ?? ($request->input('confirmatory_result') ?? ($case->confirmatory_test_result ?: 'YES'));
            $case->confirmatory_test_result = $confirmatoryVal;

            $sampleDesc = $request->input('sample_weight_description');
            if ($sampleDesc !== null && $sampleDesc !== '') {
                $case->sample_weight_description = $sampleDesc;
            }
            $sampleUnits = $request->input('sample_weight_units');
            if ($sampleUnits !== null && $sampleUnits !== '') {
                if (\Schema::hasColumn('cases', 'sample_weight_units')) {
                    $case->sample_weight_units = $sampleUnits;
                }
            }
            $sampleGross = $request->input('sample_weight_gross');
            if ($sampleGross !== null && $sampleGross !== '') {
                $case->sample_weight_gross = $sampleGross;
            }
            $sampleTaken = $request->input('sample_weight_taken') ?? ($request->input('sample_taken') ?? ($request->input('docket_data.lab_examination.sample_weight_taken') ?? ($request->input('docket_data.lab_examination.sample_taken') ?? null)));
            if ($sampleTaken !== null && $sampleTaken !== '') {
                $case->sample_weight_taken = $sampleTaken;
            }
            $sampleReserved = $request->input('sample_weight_reserved') ?? ($request->input('sample_reserved') ?? ($request->input('docket_data.lab_examination.sample_weight_reserved') ?? ($request->input('docket_data.lab_examination.sample_reserved') ?? null)));
            if ($sampleReserved !== null && $sampleReserved !== '') {
                $case->sample_weight_reserved = $sampleReserved;
            }

            $substanceId = $request->input('confirmatory_substance_identification') ?? $request->input('substance_identification');
            if ($substanceId !== null && $substanceId !== '') {
                $case->confirmatory_substance_identification = $substanceId;
            }

            $detSubs = [];
            if ($request->has('detected_substances')) {
                $detSubs = $request->input('detected_substances');
                if (!empty($detSubs) && is_array($detSubs)) {
                    foreach ($detSubs as &$ds) {
                        if (isset($ds['percentage']) && $ds['percentage'] !== '') {
                            $pctVal = trim($ds['percentage']);
                            if (!str_ends_with($pctVal, '%')) {
                                $ds['percentage'] = $pctVal . '%';
                            }
                        }
                    }
                    $case->detected_substances = $detSubs;
                }
            }

            $testMethods = $request->input('confirmatory_test_methods') ?? $request->input('test_methods');
            if ($testMethods !== null && $testMethods !== '') {
                $case->confirmatory_test_methods = $testMethods;
            }

            $specs = $request->input('confirmatory_specifications') ?? $request->input('specifications');
            if ($specs !== null && $specs !== '') {
                $case->confirmatory_specifications = $specs;
            }

            $remark = $request->input('confirmatory_remark') ?? $request->input('remark');
            if ($remark !== null && $remark !== '') {
                $case->confirmatory_remark = $remark;
            }

            $seizVal = $request->input('seizure_value') ?? $request->input('valuation');
            if ($seizVal !== null && $seizVal !== '') {
                $case->seizure_value = $seizVal;
                $case->valuation = $seizVal;
            }

            $seizPkg = $request->input('seizure_packaging') ?? $request->input('packaging_type');
            if ($seizPkg !== null && $seizPkg !== '') {
                $case->seizure_packaging = $seizPkg;
            }

            if (!empty($unitNotes)) {
                $case->laboratory_analytical_findings = $unitNotes;
            }

            // Build single exam record dataset
            $currentExamRecord = [
                'exhibit_owner_name'       => $exOwnerName,
                'exhibit_is_associate'     => $isAssoc,
                'laboratory_number'        => $labNum,
                'exam_reference'           => $labExamRef,
                'exhibit_number'           => $labExhNo,
                'lab_unit'                 => $labUnitVal,
                'analyst'                  => $officer,
                'date_received'            => $request->input('lab_date_received'),
                'date_started'             => $request->input('lab_date_started'),
                'date_completed'           => $request->input('lab_date_completed'),
                'presumptive_result'       => $presumptiveVal,
                'confirmatory_result'      => $confirmatoryVal,
                'sample_weight'            => [
                    'description'     => $sampleDesc,
                    'units_packaging' => $sampleUnits,
                    'gross_weight'    => $sampleGross,
                    'sample_taken'    => $sampleTaken,
                    'reserved_samples' => $sampleReserved,
                ],
                'substance_identification' => $substanceId,
                'detected_substances'      => $detSubs,
                'test_methods'             => $testMethods,
                'specifications'           => $specs,
                'remark'                   => $remark,
                'findings'                 => $unitNotes,
                'has_saved'                => true,
            ];

            // 1. Update matching exhibit in $docketData['seizures']
            if (!isset($docketData['seizures']) || !is_array($docketData['seizures'])) {
                $docketData['seizures'] = [];
            }
            $matchedSeizureIdx = null;
            foreach ($docketData['seizures'] as $sIdx => $sz) {
                $szOwner = $sz['associated_person'] ?? ($sz['suspect_name'] ?? ($sz['owner_name'] ?? null));
                $szExhNo = $sz['exhibit_number'] ?? null;
                $szDrug = $sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? null);

                if (!empty($labExhNo) && !empty($szExhNo) && $szExhNo === $labExhNo) {
                    $matchedSeizureIdx = $sIdx;
                    break;
                }
                if (!empty($exOwnerName) && !empty($szOwner) && strtolower(trim($szOwner)) === strtolower(trim($exOwnerName))) {
                    $matchedSeizureIdx = $sIdx;
                    break;
                }
                if (!empty($sampleDesc) && !empty($szDrug) && strtolower(trim($szDrug)) === strtolower(trim($sampleDesc))) {
                    $matchedSeizureIdx = $sIdx;
                    break;
                }
            }

            if ($matchedSeizureIdx !== null) {
                $docketData['seizures'][$matchedSeizureIdx]['has_saved_lab'] = true;
                $docketData['seizures'][$matchedSeizureIdx]['presumptive_result'] = $presumptiveVal;
                $docketData['seizures'][$matchedSeizureIdx]['confirmatory_result'] = $confirmatoryVal;
                $docketData['seizures'][$matchedSeizureIdx]['substance_identification'] = $substanceId;
                $docketData['seizures'][$matchedSeizureIdx]['sample_description'] = $sampleDesc;
                $docketData['seizures'][$matchedSeizureIdx]['sample_units'] = $sampleUnits;
                $docketData['seizures'][$matchedSeizureIdx]['sample_gross'] = $sampleGross;
                $docketData['seizures'][$matchedSeizureIdx]['sample_taken'] = $sampleTaken;
                $docketData['seizures'][$matchedSeizureIdx]['sample_reserved'] = $sampleReserved;
                $docketData['seizures'][$matchedSeizureIdx]['detected_substances'] = $detSubs;
                $docketData['seizures'][$matchedSeizureIdx]['test_methods'] = $testMethods;
                $docketData['seizures'][$matchedSeizureIdx]['specifications'] = $specs;
                $docketData['seizures'][$matchedSeizureIdx]['remark'] = $remark;
                $docketData['seizures'][$matchedSeizureIdx]['findings'] = $unitNotes;
                $docketData['seizures'][$matchedSeizureIdx]['analyst'] = $officer;
                $docketData['seizures'][$matchedSeizureIdx]['laboratory_number'] = $labNum;
                $docketData['seizures'][$matchedSeizureIdx]['exam_reference'] = $labExamRef;
                $docketData['seizures'][$matchedSeizureIdx]['exhibit_number'] = $labExhNo;
                $docketData['seizures'][$matchedSeizureIdx]['date_received'] = $request->input('lab_date_received');
                $docketData['seizures'][$matchedSeizureIdx]['date_started'] = $request->input('lab_date_started');
                $docketData['seizures'][$matchedSeizureIdx]['date_completed'] = $request->input('lab_date_completed');
            }

            // 2. Add/update in $docketData['lab_examinations_list']
            if (!isset($docketData['lab_examinations_list']) || !is_array($docketData['lab_examinations_list'])) {
                $docketData['lab_examinations_list'] = [];
            }
            $listMatchIdx = null;
            foreach ($docketData['lab_examinations_list'] as $lIdx => $lItem) {
                if (!empty($exOwnerName) && !empty($lItem['exhibit_owner_name']) && strtolower(trim($lItem['exhibit_owner_name'])) === strtolower(trim($exOwnerName))) {
                    $listMatchIdx = $lIdx;
                    break;
                }
                if (!empty($labExhNo) && !empty($lItem['exhibit_number']) && $lItem['exhibit_number'] === $labExhNo) {
                    $listMatchIdx = $lIdx;
                    break;
                }
            }
            if ($listMatchIdx !== null) {
                $docketData['lab_examinations_list'][$listMatchIdx] = array_merge($docketData['lab_examinations_list'][$listMatchIdx], array_filter($currentExamRecord));
            } else {
                $docketData['lab_examinations_list'][] = $currentExamRecord;
            }

            // 3. Separate Associate Suspect examinations into dedicated AssociateLabExamination table and docketData
            if ($isAssoc) {
                // Find existing record using case-insensitive trimmed query to prevent duplicate records with trailing spaces
                $existingExam = \App\Models\AssociateLabExamination::where('case_id', $case->id)
                    ->where(function($q) use ($canonicalOwnerName) {
                        $q->where('suspect_name', $canonicalOwnerName)
                          ->orWhereRaw('LOWER(TRIM(suspect_name)) = ?', [strtolower($canonicalOwnerName)]);
                    })->first();

                $examRecordId = $existingExam ? $existingExam->id : null;

                \App\Models\AssociateLabExamination::updateOrCreate(
                    [
                        'id' => $examRecordId,
                    ],
                    [
                        'case_id'                   => $case->id,
                        'suspect_name'              => $canonicalOwnerName ?: 'Associate Suspect',
                        'associate_id'              => $request->input('associate_id'),
                        'exhibit_number'            => $labExhNo,
                        'laboratory_number'         => $labNum,
                        'exam_reference'            => $labExamRef,
                        'lab_unit'                  => $labUnitVal,
                        'analyst'                   => $officer,
                        'date_received'             => $request->input('lab_date_received'),
                        'date_started'              => $request->input('lab_date_started'),
                        'date_completed'            => $request->input('lab_date_completed'),
                        'presumptive_result'        => $presumptiveVal,
                        'confirmatory_result'       => $confirmatoryVal,
                        'sample_weight_description' => $sampleDesc,
                        'sample_weight_units'       => $sampleUnits,
                        'sample_weight_gross'       => $sampleGross,
                        'sample_weight_taken'       => $sampleTaken,
                        'sample_weight_reserved'   => $sampleReserved,
                        'substance_identification'  => $substanceId,
                        'detected_substances'       => $detSubs,
                        'test_methods'              => $testMethods,
                        'specifications'            => $specs,
                        'remark'                    => $remark,
                        'findings'                  => $unitNotes,
                        'has_saved'                 => true,
                    ]
                );

                if (!isset($docketData['associate_lab_examinations']) || !is_array($docketData['associate_lab_examinations'])) {
                    $docketData['associate_lab_examinations'] = [];
                }
                $docketData['associate_lab_examinations'][$canonicalOwnerName ?: 'Associate Suspect'] = $currentExamRecord;

                // Make sure primary suspect lab examination is NOT marked as associate
                if (isset($docketData['lab_examination'])) {
                    $docketData['lab_examination']['exhibit_is_associate'] = false;
                }
            } else {
                // Primary Suspect lab examination in $docketData['lab_examination']
                if (!isset($docketData['lab_examination'])) {
                    $docketData['lab_examination'] = [];
                }
                if ($exOwnerName !== null && $exOwnerName !== '') {
                    $docketData['lab_examination']['exhibit_owner_name'] = $exOwnerName;
                }
                $docketData['lab_examination']['exhibit_is_associate'] = false;
                if ($labNum) {
                    $docketData['lab_examination']['laboratory_number'] = $labNum;
                }
                if ($labExamRef) {
                    $docketData['lab_examination']['exam_reference'] = $labExamRef;
                }
                if ($labExhNo) {
                    $docketData['lab_examination']['exhibit_number'] = $labExhNo;
                }
                if ($labUnitVal) {
                    $docketData['lab_examination']['lab_unit'] = $labUnitVal;
                }
                if (!empty($officer)) {
                    $docketData['lab_examination']['analyst'] = $officer;
                }
                if ($request->filled('lab_date_received')) {
                    $docketData['lab_examination']['date_received'] = $request->input('lab_date_received');
                }
                if ($request->filled('lab_date_started')) {
                    $docketData['lab_examination']['date_started'] = $request->input('lab_date_started');
                }
                if ($request->filled('lab_date_completed')) {
                    $docketData['lab_examination']['date_completed'] = $request->input('lab_date_completed');
                }
                if ($presumptiveVal !== null && $presumptiveVal !== '') {
                    $docketData['lab_examination']['presumptive_result'] = $presumptiveVal;
                }
                if ($confirmatoryVal !== null && $confirmatoryVal !== '') {
                    $docketData['lab_examination']['confirmatory_result'] = $confirmatoryVal;
                }
                if (!isset($docketData['lab_examination']['sample_weight'])) {
                    $docketData['lab_examination']['sample_weight'] = [];
                }
                if ($sampleDesc !== null && $sampleDesc !== '') {
                    $docketData['lab_examination']['sample_weight']['description'] = $sampleDesc;
                }
                if ($sampleUnits !== null && $sampleUnits !== '') {
                    $docketData['lab_examination']['sample_weight']['units_packaging'] = $sampleUnits;
                }
                if ($sampleGross !== null && $sampleGross !== '') {
                    $docketData['lab_examination']['sample_weight']['gross_weight'] = $sampleGross;
                }
                if ($sampleTaken !== null && $sampleTaken !== '') {
                    $docketData['lab_examination']['sample_weight']['sample_taken'] = $sampleTaken;
                }
                if ($sampleReserved !== null && $sampleReserved !== '') {
                    $docketData['lab_examination']['sample_weight']['reserved_samples'] = $sampleReserved;
                }
                if ($substanceId !== null && $substanceId !== '') {
                    $docketData['lab_examination']['substance_identification'] = $substanceId;
                }
                if (!empty($detSubs)) {
                    $docketData['lab_examination']['detected_substances'] = $detSubs;
                }
                if ($testMethods !== null && $testMethods !== '') {
                    $docketData['lab_examination']['test_methods'] = $testMethods;
                }
                if ($specs !== null && $specs !== '') {
                    $docketData['lab_examination']['specifications'] = $specs;
                }
                if ($remark !== null && $remark !== '') {
                    $docketData['lab_examination']['remark'] = $remark;
                }
                $docketData['lab_examination']['has_saved'] = true;
            }

            // Sync LaboratoryExaminationResult model so exhibit relationship finds it
            $matchedExh = null;
            if (!empty($labExhNo) || !empty($exOwnerName) || !empty($sampleDesc)) {
                $matchedExh = \App\Models\Exhibit::where(function($q) use ($case) {
                    $q->where('multiple_suspect_case_id', $case->id)
                      ->orWhere('single_suspect_case_id', $case->id);
                })->where(function($q) use ($labExhNo, $exOwnerName, $sampleDesc) {
                    if ($labExhNo) {
                        $q->where('exhibit_number', $labExhNo);
                    } elseif ($exOwnerName) {
                        $q->whereHas('suspect', function($sq) use ($exOwnerName) {
                            $sq->where('suspect_name', $exOwnerName);
                        });
                    } elseif ($sampleDesc) {
                        $q->where('commodity_name', $sampleDesc);
                    }
                })->first();
            }

            if (!$isAssoc || $matchedExh) {
                $labMatchKey = $matchedExh ? ['exhibit_id' => $matchedExh->id] : [
                    'single_suspect_case_id'  => $case->underlyingRecord instanceof \App\Models\SingleSuspectCase ? $case->id : null,
                    'multiple_suspect_case_id' => $case->underlyingRecord instanceof \App\Models\MultipleSuspectCase ? $case->id : null,
                ];

                \App\Models\LaboratoryExaminationResult::updateOrCreate(
                    $labMatchKey,
                    [
                        'case_type'               => $case->underlyingRecord instanceof \App\Models\MultipleSuspectCase ? 'MULTIPLE' : 'SINGLE',
                        'single_suspect_case_id'  => $case->underlyingRecord instanceof \App\Models\SingleSuspectCase ? $case->id : null,
                        'multiple_suspect_case_id'=> $case->underlyingRecord instanceof \App\Models\MultipleSuspectCase ? $case->id : null,
                        'suspect_id'              => $matchedExh ? $matchedExh->suspect_id : null,
                        'exhibit_id'              => $matchedExh ? $matchedExh->id : null,
                        'laboratory_number'       => $labNum,
                        'analyst'                 => $officer,
                        'date_received'            => $request->input('lab_date_received'),
                        'date_started'             => $request->input('lab_date_started'),
                        'date_completed'           => $request->input('lab_date_completed'),
                        'presumptive_result'      => $presumptiveVal,
                        'confirmatory_result'     => $confirmatoryVal ?: 'YES',
                        'sample_weight_description'=> $sampleDesc,
                        'sample_weight_units'      => $sampleUnits,
                        'sample_weight_gross'      => $sampleGross,
                        'sample_weight_taken'      => $sampleTaken,
                        'sample_weight_reserved'   => $sampleReserved,
                        'substance_identification' => $substanceId,
                        'detected_substances'      => $detSubs,
                        'test_methods'             => $testMethods,
                        'specifications'           => $specs,
                        'remark'                   => $remark,
                        'findings'                 => $unitNotes,
                        'has_saved'                => true,
                    ]
                );

                if (!$isAssoc) {
                    if ($case->underlyingRecord) {
                        $case->underlyingRecord->update([
                            'sample_weight_taken' => $sampleTaken,
                            'sample_weight_reserved' => $sampleReserved,
                            'confirmatory_test_result' => $confirmatoryVal ?: 'YES',
                            'confirmatory_substance_identification' => $substanceId,
                            'confirmatory_test_methods' => $testMethods,
                            'confirmatory_specifications' => $specs,
                            'confirmatory_remark' => $remark,
                        ]);
                    }
                    $delName = $exOwnerName ?: $case->suspect_name;
                    if (!empty($delName)) {
                        \App\Models\AssociateLabExamination::where('case_id', $case->id)
                            ->where(function($q) use ($delName) {
                                $q->where('suspect_name', $delName)
                                  ->orWhere('suspect_name', 'Primary Suspect');
                            })->delete();
                    }
                }
            }
        }

        if (!empty($unitNotes)) {
            if (!isset($docketData['unit_notes'])) {
                $docketData['unit_notes'] = [];
            }
            $docketData['unit_notes'][$unitNameKey] = [
                'notes'      => $unitNotes,
                'officer'    => $officer,
                'updated_at' => now()->toDateTimeString(),
            ];
        }

        // Dynamically determine relevant units for this case
        $isLabCreatedCase = !empty($case->unit_name) && (
            strtolower(trim($case->unit_name)) === 'lab analysis' ||
            str_contains(strtolower($case->unit_name), 'lab analysis') ||
            str_contains(strtolower($case->unit_name), 'laboratory')
        );

        $exCatProg = strtolower($case->exhibit_category ?? '');
        $sDrugProg = $case->seizure_name_drug ?? '';
        $sNameProg = strtolower($case->seizure_name ?? '');
        $exSumProg = strtolower($case->exhibits_summary ?? '');
        $isDrugProg = (!empty($sDrugProg) || 
            str_contains($exCatProg, 'drug') || str_contains($exCatProg, 'narcotic') || str_contains($exCatProg, 'substance') || str_contains($exCatProg, 'cannabis') || str_contains($exCatProg, 'cocaine') || str_contains($exCatProg, 'heroin') || str_contains($exCatProg, 'meth') || str_contains($exCatProg, 'tramadol') ||
            str_contains($sNameProg, 'cocaine') || str_contains($sNameProg, 'heroin') || str_contains($sNameProg, 'cannabis') || str_contains($sNameProg, 'weed') || str_contains($sNameProg, 'meth') || str_contains($sNameProg, 'tramadol') || str_contains($sNameProg, 'drug') || str_contains($sNameProg, 'narcotic') ||
            str_contains($exSumProg, 'cocaine') || str_contains($exSumProg, 'heroin') || str_contains($exSumProg, 'cannabis') || str_contains($exSumProg, 'weed') || str_contains($exSumProg, 'meth') || str_contains($exSumProg, 'tramadol') || str_contains($exSumProg, 'drug') || str_contains($exSumProg, 'narcotic')
        );
        if (!$isDrugProg) {
            $dSeizuresProg = $docketData['seizures'] ?? [];
            if (is_array($dSeizuresProg) && count($dSeizuresProg) > 0) {
                foreach ($dSeizuresProg as $szP) {
                    $catP = strtolower($szP['category'] ?? ($szP['exhibit_category'] ?? ''));
                    $dNameP = $szP['seizure_name_drug'] ?? '';
                    $szNameP = strtolower($szP['seizure_name'] ?? '');
                    if (!empty($dNameP) || str_contains($catP, 'drug') || str_contains($catP, 'narcotic') || str_contains($szNameP, 'cocaine') || str_contains($szNameP, 'heroin') || str_contains($szNameP, 'cannabis') || str_contains($szNameP, 'drug') || str_contains($szNameP, 'narcotic')) {
                        $isDrugProg = true;
                        break;
                    }
                }
            }
        }

        $units = $case->getWorkflowUnits();

        $totalUnits = count($units);
        $completedCount = 0;
        foreach ($units as $u) {
            if (isset($docketData['unit_progress'][$u]) && strtoupper($docketData['unit_progress'][$u]['status'] ?? '') === 'COMPLETED') {
                $completedCount++;
            }
        }

        if ($completedCount >= $totalUnits) {
            $case->status = 'Awaiting Sign-off';
            $msg = "All {$totalUnits} specialized units have completed processing! Case {$case->case_number} has been forwarded to Head of Forensic Directorate for final sign-off.";
        } else {
            $case->status = 'Under Multi-Unit Processing';
            $msg = "{$unitNameKey} progress updated for Case {$case->case_number}. Currently {$completedCount}/{$totalUnits} units completed.";
        }

        $case->docket_data = $docketData;
        $case->save();

        AuditLogger::log(
            'WORKFLOW_UNIT_COMPLETED',
            "{$unitNameKey} unit progress updated to '{$unitStatus}' by " . ($user ? $user->fullname : $officer) . " ({$unitNameKey}) for Case {$case->case_number}",
            [
                'case_number'    => $case->case_number,
                'auditable_type' => get_class($case->underlyingRecord ?? $case),
                'auditable_id'   => $case->id,
                'new_values'     => [
                    'unit_name'   => $unitNameKey,
                    'unit_status' => $unitStatus,
                    'officer'     => $officer,
                ],
                'user'           => $user,
            ]
        );

        return response()->json([
            'success'         => true,
            'message'         => $msg,
            'completed_count' => $completedCount,
            'total_units'     => $totalUnits,
            'unit_name'       => $unitNameKey,
            'unit_progress'   => $docketData['unit_progress'],
            'status'          => $case->status,
            'case'            => $case,
        ]);
    }

    /**
     * Helper to list any unexamined suspects on a joint suspects case.
     */
    protected function getUnexaminedLabSuspects($case, $docketData, $currentExamOwner = null)
    {
        $allSuspects = [];

        $addSuspect = function($name, $isAssociate) use (&$allSuspects) {
            $cleaned = trim($name);
            if (empty($cleaned)) return;
            foreach ($allSuspects as $existing) {
                if (strtolower(trim($existing['name'])) === strtolower($cleaned)) {
                    return;
                }
            }
            $allSuspects[] = [
                'name'         => $cleaned,
                'is_associate' => $isAssociate,
            ];
        };

        // 1. Suspects from docket_data['suspects']
        if (!empty($docketData['suspects']) && is_array($docketData['suspects'])) {
            foreach ($docketData['suspects'] as $idx => $sp) {
                $sName = $sp['suspect_name'] ?? null;
                if ($sName) {
                    $isAssoc = !empty($sp['is_associate']) || (!empty($sp['association_role']) && str_contains(strtolower($sp['association_role']), 'associate')) || ($idx > 0);
                    $addSuspect($sName, $isAssoc);
                }
            }
        }

        // 2. Relational CaseSuspect models
        if (empty($allSuspects) && !empty($case->id)) {
            $dbSuspects = \App\Models\CaseSuspect::where('multiple_suspect_case_id', $case->id)->orderBy('suspect_number')->get();
            foreach ($dbSuspects as $idx => $sp) {
                if (!empty($sp->suspect_name)) {
                    $isAssoc = !empty($sp->associate_id) || str_contains(strtolower($sp->role ?? ''), 'associate') || ($idx > 0);
                    $addSuspect($sp->suspect_name, $isAssoc);
                }
            }
        }

        // 3. AssociateLabExamination relation
        if (\App\Models\AssociateLabExamination::where('case_id', $case->id)->exists()) {
            $dbAssocExams = \App\Models\AssociateLabExamination::where('case_id', $case->id)->get();
            foreach ($dbAssocExams as $aExam) {
                if (!empty($aExam->suspect_name)) {
                    $addSuspect($aExam->suspect_name, true);
                }
            }
        }

        // 4. Seizures list associated_person
        if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
            foreach ($docketData['seizures'] as $sz) {
                $owner = $sz['associated_person'] ?? ($sz['suspect_name'] ?? ($sz['owner_name'] ?? null));
                if ($owner && !in_array(strtolower(trim($owner)), ['primary suspect', 'associate suspect', 'under active investigation'])) {
                    $addSuspect($owner, true);
                }
            }
        }

        // If total suspect count <= 1, then this is NOT a joint suspects case
        if (count($allSuspects) <= 1) {
            return [];
        }

        // Check if an examination has been saved for "All Suspects" or joint ownership
        if (!empty($currentExamOwner) && (str_contains(strtolower($currentExamOwner), 'all suspect') || str_contains(strtolower($currentExamOwner), 'all suspects'))) {
            return [];
        }

        // Check if any saved examination in lab_examinations_list, associate_lab_examinations, or seizures is marked for "All Suspects"
        $hasAllSuspectsSaved = false;
        if (!empty($docketData['lab_examinations_list'])) {
            foreach ($docketData['lab_examinations_list'] as $lItem) {
                if (!empty($lItem['has_saved']) && !empty($lItem['exhibit_owner_name'])) {
                    $ownerLower = strtolower(trim($lItem['exhibit_owner_name']));
                    if (str_contains($ownerLower, 'all suspect') || str_contains($ownerLower, 'all suspects')) {
                        $hasAllSuspectsSaved = true;
                        break;
                    }
                }
            }
        }
        if (!$hasAllSuspectsSaved && !empty($docketData['associate_lab_examinations'])) {
            foreach ($docketData['associate_lab_examinations'] as $kAssoc => $vAssoc) {
                $kLower = strtolower(trim($kAssoc));
                if (!empty($vAssoc['has_saved']) && (str_contains($kLower, 'all suspect') || str_contains($kLower, 'all suspects'))) {
                    $hasAllSuspectsSaved = true;
                    break;
                }
            }
        }
        if (!$hasAllSuspectsSaved && !empty($docketData['seizures'])) {
            foreach ($docketData['seizures'] as $sz) {
                $szOwner = strtolower(trim($sz['associated_person'] ?? ($sz['suspect_name'] ?? ($sz['owner_name'] ?? ''))));
                if (!empty($sz['has_saved_lab']) && (str_contains($szOwner, 'all suspect') || str_contains($szOwner, 'all suspects'))) {
                    $hasAllSuspectsSaved = true;
                    break;
                }
            }
        }

        if ($hasAllSuspectsSaved) {
            return [];
        }

        $unexamined = [];
        foreach ($allSuspects as $sp) {
            $spName = $sp['name'];
            $isAssoc = $sp['is_associate'];

            $spLower = strtolower(trim($spName));

            // Skip current suspect being submitted in this active HTTP request
            if ($currentExamOwner) {
                $currLower = strtolower(trim($currentExamOwner));
                if ($currLower === $spLower || str_contains($currLower, $spLower) || str_contains($spLower, $currLower)) {
                    continue;
                }
            }

            $hasSaved = false;

            // Check DB table AssociateLabExamination
            $hasSavedInDb = \App\Models\AssociateLabExamination::where('case_id', $case->id)
                ->where(function($q) use ($spLower) {
                    $q->whereRaw('LOWER(trim(suspect_name)) = ?', [$spLower])
                      ->orWhereRaw('LOWER(trim(suspect_name)) LIKE ?', ['%' . $spLower . '%'])
                      ->orWhereRaw('LOWER(trim(suspect_name)) LIKE ?', ['%all suspect%']);
                })
                ->where('has_saved', true)
                ->exists();

            if ($hasSavedInDb) {
                $hasSaved = true;
            }

            // Check docket_data associate_lab_examinations
            if (!$hasSaved && !empty($docketData['associate_lab_examinations'])) {
                foreach ($docketData['associate_lab_examinations'] as $kAssoc => $vAssoc) {
                    $kLower = strtolower(trim($kAssoc));
                    if (( $kLower === $spLower || str_contains($kLower, $spLower) || str_contains($spLower, $kLower) || str_contains($kLower, 'all suspect') ) && !empty($vAssoc['has_saved'])) {
                        $hasSaved = true;
                        break;
                    }
                }
            }

            // Check docket_data lab_examinations_list
            if (!$hasSaved && !empty($docketData['lab_examinations_list'])) {
                foreach ($docketData['lab_examinations_list'] as $lItem) {
                    if (!empty($lItem['exhibit_owner_name']) && !empty($lItem['has_saved'])) {
                        $lOwnerLower = strtolower(trim($lItem['exhibit_owner_name']));
                        if ($lOwnerLower === $spLower || str_contains($lOwnerLower, $spLower) || str_contains($spLower, $lOwnerLower) || str_contains($lOwnerLower, 'all suspect')) {
                            $hasSaved = true;
                            break;
                        }
                    }
                }
            }

            // Check docket_data seizures
            if (!$hasSaved && !empty($docketData['seizures']) && is_array($docketData['seizures'])) {
                foreach ($docketData['seizures'] as $sz) {
                    if (!empty($sz['has_saved_lab'])) {
                        $szOwner = $sz['associated_person'] ?? ($sz['suspect_name'] ?? ($sz['owner_name'] ?? null));
                        if ($szOwner) {
                            $szOwnerLower = strtolower(trim($szOwner));
                            if ($szOwnerLower === $spLower || str_contains($szOwnerLower, $spLower) || str_contains($spLower, $szOwnerLower)) {
                                $hasSaved = true;
                                break;
                            }
                        }
                    }
                }
            }

            // Check primary suspect docket_data lab_examination
            if (!$hasSaved && !$isAssoc) {
                $hasSaved = !empty($docketData['lab_examination']['has_saved']);
            }

            if (!$hasSaved) {
                $unexamined[] = $spName;
            }
        }

        return $unexamined;
    }

    /**
     * Associate additional persons/suspects and exhibits to an existing case file.
     * Preserves existing case data as read-only and appends newly entered associate data.
     */
    public function associate($id, Request $request)
    {
        $case = $this->findCase($id);
        if (!$case) {
            return response()->json(['success' => false, 'message' => 'Case not found.'], 404);
        }

        $newPersons = $request->input('associated_persons', []);
        if (is_string($newPersons)) {
            $newPersons = json_decode($newPersons, true) ?: [];
        }

        $newExhibits = $request->input('associated_exhibits', []);
        if (is_string($newExhibits)) {
            $newExhibits = json_decode($newExhibits, true) ?: [];
        }

        // 1. If single suspect case, promote to MultipleSuspectCase
        if ($case->underlyingRecord instanceof \App\Models\SingleSuspectCase) {
            $single = $case->underlyingRecord;
            $multi = \App\Models\MultipleSuspectCase::where('case_number', $single->case_number)->first();
            if (!$multi) {
                $multi = \App\Models\MultipleSuspectCase::create([
                    'case_number'          => $single->case_number,
                    'laboratory_number'    => $single->laboratory_number,
                    'title'                => $single->title,
                    'unit_name'            => $single->unit_name,
                    'unit_id'              => $single->unit_id,
                    'user_id'              => $single->user_id,
                    'commanding_officer'   => $single->commanding_officer,
                    'submitting_officer'   => $single->submitting_officer,
                    'operation_code'       => $single->operation_code,
                    'operation_code_name'  => $single->operation_code_name,
                    'raid_location'        => $single->raid_location,
                    'offense_description'  => $single->offense_description,
                    'incident_date'        => $single->incident_date,
                    'priority'             => $single->priority ?? 'NORMAL',
                    'status'               => $single->status ?? 'Approved',
                    'docket_data'          => $single->docket_data,
                    'hash_log'             => $single->hash_log,
                ]);

                $primarySuspectModel = \App\Models\CaseSuspect::create([
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

                \App\Models\Exhibit::where('single_suspect_case_id', $single->id)->update([
                    'case_type'                => 'MULTIPLE',
                    'single_suspect_case_id'   => null,
                    'multiple_suspect_case_id' => $multi->id,
                    'suspect_id'               => $primarySuspectModel->id,
                ]);

                \App\Models\LaboratoryExaminationResult::where('single_suspect_case_id', $single->id)->update([
                    'case_type'                => 'MULTIPLE',
                    'single_suspect_case_id'   => null,
                    'multiple_suspect_case_id' => $multi->id,
                    'suspect_id'               => $primarySuspectModel->id,
                ]);

                $single->update(['status' => 'Promoted']);
            }

            $case->underlyingRecord = $multi;
        }

        $multiCase = $case->underlyingRecord;
        $addedPersonsCount = 0;
        $addedExhibitsCount = 0;

        $currentSuspectsCount = \App\Models\CaseSuspect::where('multiple_suspect_case_id', $multiCase->id)->count();
        $currentAssocCount = \App\Models\CaseSuspect::where('multiple_suspect_case_id', $multiCase->id)->where('role', 'LIKE', '%associate%')->count();

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
                $addrVal  = $person['residential_address'] ?? ($person['address'] ?? null);
                $roleVal  = $person['association_role'] ?? ($person['role'] ?? 'Associate Suspect');

                \App\Models\CaseSuspect::create([
                    'multiple_suspect_case_id' => $multiCase->id,
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
                    'partner_name'             => $person['spouse_name'] ?? null,
                    'partner_phone'            => $person['spouse_phone'] ?? null,
                    'partner_info'             => $person['spouse_info'] ?? null,
                    'dependents'               => $person['dependents'] ?? null,
                    'holding_facility'         => $person['holding_facility'] ?? null,
                    'raid_location'            => $person['raid_location'] ?? ($person['location'] ?? null),
                    'offense_description'      => $person['offense'] ?? ($person['offense_description'] ?? null),
                    'arrest_date'              => $person['date_of_arrest'] ?? ($person['arrest_date'] ?? null),
                    'arrest_time'              => $person['time_of_arrest'] ?? ($person['arrest_time'] ?? null),
                    'mugshot_front'            => $person['mugshot_front'] ?? null,
                    'mugshot_left'             => $person['mugshot_left'] ?? null,
                    'mugshot_right'            => $person['mugshot_right'] ?? null,
                ]);

                $addedPersonsCount++;
            }
        }

        // 3. Process new associated exhibits
        $allSuspectsInCase = \App\Models\CaseSuspect::where('multiple_suspect_case_id', $multiCase->id)->get();
        $currentExhibitsCount = \App\Models\Exhibit::where('multiple_suspect_case_id', $multiCase->id)->count();

        $docketData = is_array($multiCase->docket_data) ? $multiCase->docket_data : (json_decode($multiCase->docket_data ?? '[]', true) ?: []);
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

                $photosList = [];
                if (!empty($exhibit['photos']) && is_array($exhibit['photos'])) {
                    $photosList = array_values(array_filter($exhibit['photos']));
                } elseif (!empty($exhibit['photo'])) {
                    $photosList = [$exhibit['photo']];
                } elseif (!empty($exhibit['exhibit_photo'])) {
                    $photosList = [$exhibit['exhibit_photo']];
                }

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

                $wpnModel   = $exhibit['wpn_model'] ?? ($exhibit['wpn']['model'] ?? ($exhibit['weapon_model'] ?? ($exhibit['weapon_type'] ?? null)));
                $wpnSerial  = $exhibit['wpn_serial_no'] ?? ($exhibit['wpn']['serial_no'] ?? ($exhibit['weapon_serial'] ?? ($exhibit['serial_number'] ?? null)));
                $wpnCalibre = $exhibit['wpn_calibre'] ?? ($exhibit['wpn']['calibre'] ?? ($exhibit['weapon_calibre'] ?? ($exhibit['calibre'] ?? null)));
                $wpnColour  = $exhibit['wpn_colour'] ?? ($exhibit['wpn']['colour'] ?? ($exhibit['weapon_colour'] ?? null));
                $wpnSize    = $exhibit['wpn_size'] ?? ($exhibit['wpn']['size'] ?? ($exhibit['weapon_size'] ?? null));

                $devModel  = $exhibit['dev_model'] ?? ($exhibit['device_model'] ?? ($exhibit['device_make_model'] ?? null));
                $devSerial = $exhibit['dev_serial_no'] ?? ($exhibit['device_serial'] ?? ($exhibit['serial_number'] ?? null));
                $devImei1  = $exhibit['dev_imei1'] ?? ($exhibit['dev_imei_1'] ?? ($exhibit['imei_1'] ?? ($exhibit['imei1'] ?? null)));
                $devImei2  = $exhibit['dev_imei2'] ?? ($exhibit['dev_imei_2'] ?? ($exhibit['imei_2'] ?? ($exhibit['imei2'] ?? null)));
                $devMac    = $exhibit['dev_mac_address'] ?? ($exhibit['mac_address'] ?? null);
                $devStorage= $exhibit['dev_storage'] ?? ($exhibit['dev_storage_capacity'] ?? ($exhibit['storage'] ?? null));
                $devColour = $exhibit['dev_colour'] ?? ($exhibit['dev_color'] ?? ($exhibit['colour'] ?? ($exhibit['color'] ?? null)));
                $devMarks  = $exhibit['dev_marks'] ?? ($exhibit['dev_other_marks'] ?? ($exhibit['marks'] ?? null));

                \App\Models\Exhibit::create([
                    'case_type'                => 'MULTIPLE',
                    'multiple_suspect_case_id' => $multiCase->id,
                    'suspect_id'               => $targetSuspectId,
                    'exhibit_number'           => $exhibit['exhibit_number'] ?? ('EXH-' . sprintf('%02d', $currentExhibitsCount)),
                    'exhibit_category'         => $exCat,
                    'commodity_name'           => $exName,
                    'seizure_quantity'         => $exhibit['seizure_quantity'] ?? ($exhibit['quantity'] ?? null),
                    'seizure_packaging'        => $exhibit['seizure_packaging'] ?? ($exhibit['packaging'] ?? null),
                    'seizure_weight'           => $exhibit['seizure_weight'] ?? ($exhibit['weight'] ?? null),
                    'seizure_unit'             => $exhibit['seizure_unit'] ?? ($exhibit['unit'] ?? null),
                    'seizure_value'            => $exhibit['seizure_value'] ?? ($exhibit['value'] ?? null),
                    'seizure_officer'          => $exhibit['seizure_officer'] ?? ($exhibit['seizing_officer'] ?? null),
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
                    'wpn_model'                => $wpnModel,
                    'wpn_serial_no'            => $wpnSerial,
                    'wpn_calibre'              => $wpnCalibre,
                    'wpn_colour'               => $wpnColour,
                    'wpn_size'                 => $wpnSize,
                    'dev_model'                => $devModel,
                    'dev_serial_no'            => $devSerial,
                    'dev_imei1'                => $devImei1,
                    'dev_imei2'                => $devImei2,
                    'dev_mac_address'          => $devMac,
                    'dev_storage'              => $devStorage,
                    'dev_colour'               => $devColour,
                    'dev_marks'                => $devMarks,
                    'ammunition_details'       => $exhibit['ammunition_details'] ?? null,
                    'other_exhibit_description' => $exhibit['other_exhibit_description'] ?? ($exhibit['other_description'] ?? null),
                    'evidence_photos'          => $photosList,
                ]);

                $docketData['seizures'][] = [
                    'exhibit_category'   => $exCat,
                    'category'           => $exCat,
                    'seizure_name'       => $exName,
                    'commodity_name'     => $exName,
                    'name'               => $exName,
                    'seizure_name_drug'  => (str_contains(strtolower($exCat), 'drug') || str_contains(strtolower($exName), 'drug') || str_contains(strtolower($exName), 'cocaine') || str_contains(strtolower($exName), 'heroin') || str_contains(strtolower($exName), 'cannabis') || str_contains(strtolower($exName), 'tramadol')) ? $exName : null,
                    'seizure_quantity'   => $exhibit['seizure_quantity'] ?? ($exhibit['quantity'] ?? null),
                    'seizure_weight'     => $exhibit['seizure_weight'] ?? ($exhibit['weight'] ?? null),
                    'seizure_unit'       => $exhibit['seizure_unit'] ?? ($exhibit['unit'] ?? null),
                    'seizure_packaging'  => $exhibit['seizure_packaging'] ?? ($exhibit['packaging'] ?? null),
                    'seizure_value'      => $exhibit['seizure_value'] ?? ($exhibit['value'] ?? null),
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
                    'wpn_model'          => $wpnModel,
                    'wpn_serial_no'      => $wpnSerial,
                    'wpn_calibre'        => $wpnCalibre,
                    'wpn_colour'         => $wpnColour,
                    'wpn_size'           => $wpnSize,
                    'dev_model'          => $devModel,
                    'dev_serial_no'      => $devSerial,
                    'dev_imei1'          => $devImei1,
                    'dev_imei2'          => $devImei2,
                    'dev_mac_address'    => $devMac,
                    'dev_storage'        => $devStorage,
                    'dev_colour'         => $devColour,
                    'dev_marks'          => $devMarks,
                    'associated_person'  => $assocPersonName,
                    'suspect_name'       => $assocPersonName,
                    'photos'             => $photosList,
                ];

                $addedExhibitsCount++;
            }
        }

        // Update counts & title on MultipleSuspectCase
        $allNames = \App\Models\CaseSuspect::where('multiple_suspect_case_id', $multiCase->id)->pluck('suspect_name')->toArray();
        $cleanNames = array_map(fn($n) => preg_replace('/^Republic\s+vs\s+/i', '', trim($n)), $allNames);
        $jointSuspectNameLabel = !empty($cleanNames) ? implode(', ', array_filter($cleanNames)) : preg_replace('/^Republic\s+vs\s+/i', '', trim($multiCase->title));

        // Save updated seizures array to docket_data if column exists
        if (\Schema::hasColumn($multiCase->getTable(), 'docket_data')) {
            $multiCase->docket_data = $docketData;
            $multiCase->save();
        }

        // Recalculate unit progress using fresh model (loads new DB exhibits)
        $refreshedForCalc = \App\Models\ForensicCase::wrapRecord($multiCase->fresh(['suspects', 'exhibits', 'labResults']));
        $reqUnits = $refreshedForCalc->getWorkflowUnits();
        $unitProgress = $docketData['unit_progress'] ?? [];
        foreach ($reqUnits as $reqU) {
            if (!isset($unitProgress[$reqU])) {
                $unitProgress[$reqU] = [
                    'status'     => 'PENDING',
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
        if (\Schema::hasColumn($multiCase->getTable(), 'docket_data')) {
            $updatePayload['docket_data'] = $docketData;
        }

        $multiCase->update($updatePayload);

        $refreshedCase = \App\Models\ForensicCase::wrapRecord($multiCase->fresh(['suspects', 'exhibits', 'labResults']));

        $fromUnit = $request->input('from_unit') ?? $request->input('unit') ?? $request->query('unit') ?? $request->query('from') ?? '';
        $userUnit = auth()->user()->unit ?? session('user_unit') ?? '';
        $referer  = $request->headers->get('referer') ?? '';

        $isLab = str_contains(strtolower($fromUnit), 'lab') 
                 || str_contains(strtolower($userUnit), 'lab')
                 || str_contains(strtolower($referer), 'lab');

        $redirectUrl = url('/cases') . ($isLab ? '?unit=Lab+Analysis' : '');

        $msg = "Successfully associated {$addedPersonsCount} person(s) and {$addedExhibitsCount} exhibit(s) with Case {$refreshedCase->case_number}.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => $msg,
                'case'     => $refreshedCase,
                'redirect' => $redirectUrl,
            ]);
        }

        return redirect($redirectUrl)->with('status', $msg);
    }

    /**
     * Re-assign an exhibit item to a specific associate or primary suspect.
     */
    public function reassignExhibit($id, Request $request)
    {
        $case = $this->findCase($id);
        if (!$case) {
            return back()->with('error', 'Case file not found.');
        }

        $exIndex      = $request->input('exhibit_index');
        $newOwnerName = $request->input('new_owner_name');
        $newOwnerId   = $request->input('new_owner_id');

        $docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data, true) ?: []);

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

            $msg = "Exhibit item successfully re-assigned to {$newOwnerName}" . ($newOwnerId ? " [ID: {$newOwnerId}]" : '') . ".";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                ]);
            }

            return back()->with('status', $msg);
        }

        return back()->with('error', 'Exhibit index not found.');
    }

    /**
     * Display dedicated full page view for associating persons & exhibits to a case.
     */
    public function showAssociate($id)
    {
        $case = $this->findCase($id);
        if (!$case) {
            return redirect('/cases')->with('error', 'Case file not found.');
        }
        return view('cases.associate', compact('case'));
    }

    /**
     * Safely lookup a case by id or case_number without triggering PostgreSQL type casting errors.
     */
    protected function findCase($id)
    {
        if (is_numeric($id)) {
            return ForensicCase::where('id', (int)$id)->orWhere('case_number', (string)$id)->first();
        }
        return ForensicCase::where('case_number', (string)$id)->first();
    }

    /**
     * Approve or update case status in database.
     */
    public function approve($id)
    {
        $request = request();
        $case = $this->findCase($id);
        if (!$case) {
            $case = ForensicCase::create([
                'case_number'       => (string)$id,
                'title'             => 'Directorate Forensic Docket (' . $id . ')',
                'unit_name'         => 'Forensic Directorate',
                'commanding_officer'=> Auth::user()->fullname ?? 'Department Head',
                'submitting_officer'=> 'Handling Officer',
                'priority'          => 'HIGH',
                'status'            => 'Approved',
                'location'          => 'NACOC Central Vault Storage',
                'description'       => 'Directorate Approved Case File',
                'exhibits_summary'  => 'Verified Exhibits Dossier',
                'hash_log'          => 'HASH-' . strtoupper(substr(md5((string)$id), 0, 8)) . '-VERIFIED',
                'incident_date'     => now(),
            ]);
        } else {
            $case->status = 'Approved';
            $case->save();
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Case {$case->case_number} approved successfully.",
                'case'    => $case,
            ]);
        }

        return back()->with('status', "Case {$case->case_number} approved successfully.");
    }

    /**
     * Decline or reject case status in database.
     */
    public function decline($id)
    {
        $request = request();
        $case = $this->findCase($id);
        if (!$case) {
            $case = ForensicCase::create([
                'case_number'       => (string)$id,
                'title'             => 'Directorate Forensic Docket (' . $id . ')',
                'unit_name'         => 'Forensic Directorate',
                'commanding_officer'=> Auth::user()->fullname ?? 'Department Head',
                'submitting_officer'=> 'Handling Officer',
                'priority'          => 'HIGH',
                'status'            => 'Declined',
                'location'          => 'NACOC Central Vault Storage',
                'description'       => 'Directorate Declined Case File',
                'exhibits_summary'  => 'Exhibits Dossier',
                'hash_log'          => 'HASH-' . strtoupper(substr(md5((string)$id), 0, 8)) . '-VERIFIED',
                'incident_date'     => now(),
            ]);
        } else {
            $case->status = 'Declined';
            $case->save();
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Case {$case->case_number} has been declined.",
                'case'    => $case,
            ]);
        }

        return back()->with('status', "Case {$case->case_number} has been declined.");
    }

    /**
     * Batch approve all pending cases in database.
     */
    public function batchApprove()
    {
        $request = request();
        $updatedCount = ForensicCase::whereIn('status', ['Awaiting Sign-off', 'Pending Approval', 'Pending'])
            ->update(['status' => 'Approved']);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Successfully batch approved {$updatedCount} pending cases.",
                'count'   => $updatedCount,
            ]);
        }

        return back()->with('status', "Successfully batch approved {$updatedCount} pending cases.");
    }

    /**
     * Display dedicated full page case review.
     */
    public function showReview($id)
    {
        $case = $this->findCase($id);
        if (!$case) {
            $dbHead = \App\Models\User::where('is_head', true)->first();
            $headName = $dbHead ? $dbHead->fullname : 'Anita Johnson';
            $case = new ForensicCase([
                'case_number'       => $id,
                'title'             => 'Directorate High Priority Forensic Docket',
                'unit_name'         => 'Forensic Directorate',
                'commanding_officer'=> $headName,
                'submitting_officer'=> 'Insp. Eric Ofori',
                'suspect_name'      => 'Under Active Investigation',
                'priority'          => 'HIGH',
                'status'            => 'Awaiting Sign-off',
                'location'          => 'NACOC Central Vault Safe A-01',
                'description'       => 'Detailed forensic analysis, exhibits chain of custody log, and findings certificate awaiting director review.',
                'exhibits_summary'  => '3x Sealed Evidence Packages with SHA-256 Checksums',
                'hash_log'          => 'HASH-' . strtoupper(substr(md5($id), 0, 8)) . '-VERIFIED',
                'incident_date'     => now(),
            ]);
        }
        $this->repairExhibitAssociations($case);
        $gsaReports = \App\Models\SuspectGsaReport::forCase(
            $case->case_number ?: $id,
            $case->underlyingRecord instanceof \App\Models\MultipleSuspectCase ? $case->id : null
        );
        $nlrReports = \App\Models\SuspectNlrReport::forCase(
            $case->case_number ?: $id,
            $case->underlyingRecord instanceof \App\Models\MultipleSuspectCase ? $case->id : null
        );
        return view('cases.show_review', compact('case', 'gsaReports', 'nlrReports'));

    }

    /**
     * Auto-repair legacy or mis-assigned associate exhibit records.
     */
    protected function repairExhibitAssociations(&$case)
    {
        if (!$case || empty($case->docket_data)) return;
        $docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data, true) ?: []);

        // Backfill exhibit seizure_officer and seizure_reason from docket_data if missing in DB.
        // Each DB Exhibit is matched to its corresponding docket seizure by exhibit_number first,
        // then by index position. No default fallback to seizures[0] to avoid cross-suspect data leakage.
        if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
            $exList = \App\Models\Exhibit::where(function($q) use ($case) {
                if ($case->underlyingRecord instanceof \App\Models\SingleSuspectCase) {
                    $q->where('single_suspect_case_id', $case->id);
                } else {
                    $q->where('multiple_suspect_case_id', $case->id);
                }
            })->get();

            // Build an exhibit_number â†’ docket seizure lookup for accurate matching
            $docketByExNo = [];
            foreach ($docketData['seizures'] as $dIdx => $sz) {
                $exNo = strtolower(trim($sz['exhibit_number'] ?? ''));
                if (!empty($exNo)) {
                    $docketByExNo[$exNo] = $sz;
                }
            }

            foreach ($exList as $idx => $exItem) {
                // Match by exhibit_number first, then by positional index â€” never default to seizures[0]
                $dbExNo = strtolower(trim($exItem->exhibit_number ?? ''));
                $szDoc = (!empty($dbExNo) && isset($docketByExNo[$dbExNo]))
                    ? $docketByExNo[$dbExNo]
                    : ($docketData['seizures'][$idx] ?? null);

                if (empty($szDoc)) continue;

                $exUpdates = [];
                if (empty($exItem->seizure_officer) && !empty($szDoc['seizure_officer'])) {
                    $exUpdates['seizure_officer'] = $szDoc['seizure_officer'];
                }
                if (empty($exItem->seizure_reason) && !empty($szDoc['seizure_reason'])) {
                    $exUpdates['seizure_reason'] = $szDoc['seizure_reason'];
                }
                if (!empty($exUpdates)) {
                    $exItem->update($exUpdates);
                }
            }
        }
    }

    /**
     * Display dedicated PDF dossier view for printing and scanning.
     */
    public function showPdf($id)
    {
        $case = $this->findCase($id);
        if (!$case) {
            $dbHead = \App\Models\User::where('is_head', true)->first();
            $headName = $dbHead ? $dbHead->fullname : 'Anita Johnson';
            $case = new ForensicCase([
                'case_number'       => $id,
                'title'             => 'Directorate High Priority Forensic Docket',
                'unit_name'         => 'Forensic Directorate',
                'commanding_officer'=> $headName,
                'submitting_officer'=> 'Insp. Eric Ofori',
                'suspect_name'      => 'BEN ADU',
                'priority'          => 'HIGH',
                'status'            => 'Approved',
                'location'          => 'NACOC Central Vault Safe A-01',
                'description'       => 'Detailed forensic analysis, exhibits chain of custody log, and findings certificate awaiting director review.',
                'exhibits_summary'  => '45 Kilograms (kg) Cocaine (High-Purity Powder / Slabs)',
                'hash_log'          => 'HASH-' . strtoupper(substr(md5($id), 0, 8)) . '-VERIFIED',
                'incident_date'     => now(),
            ]);
        }
        $this->repairExhibitAssociations($case);
        return view('Forensic_Head.case_pdf', compact('case'));
    }

    /**
     * Dedicated Official Lab Analysis Certificate View
     */
    public function showLabCertificate($id)
    {
        $case = $this->findCase($id);
        if (!$case) {
            $dbHead = \App\Models\User::where('is_head', true)->first();
            $headName = $dbHead ? $dbHead->fullname : 'Anita Johnson';
            $case = new ForensicCase([
                'case_number'       => $id,
                'title'             => 'Directorate High Priority Forensic Docket',
                'unit_name'         => 'Forensic Directorate',
                'commanding_officer'=> $headName,
                'submitting_officer'=> 'Insp. Eric Ofori',
                'suspect_name'      => 'BEN ADU',
                'priority'          => 'HIGH',
                'status'            => 'Approved',
                'location'          => 'NACOC Central Vault Safe A-01',
                'description'       => 'Detailed forensic analysis, exhibits chain of custody log, and findings certificate.',
                'exhibits_summary'  => '45 Kilograms (kg) Cocaine (High-Purity Powder / Slabs)',
                'hash_log'          => 'HASH-' . strtoupper(substr(md5($id), 0, 8)) . '-VERIFIED',
                'incident_date'     => now(),
            ]);
        }
        $this->repairExhibitAssociations($case);
        return view('Lab_Analysis.certificate', compact('case'));
    }

    /**
     * Dedicated Laboratory Analytical Test Report View
     */
    public function showLabAnalytical($id)
    {
        $case = $this->findCase($id);
        if (!$case) {
            $dbHead = \App\Models\User::where('is_head', true)->first();
            $headName = $dbHead ? $dbHead->fullname : 'Anita Johnson';
            $case = new ForensicCase([
                'case_number'       => $id,
                'title'             => 'Directorate High Priority Forensic Docket',
                'unit_name'         => 'Forensic Directorate',
                'commanding_officer'=> $headName,
                'submitting_officer'=> 'Insp. Eric Ofori',
                'suspect_name'      => 'KOJO ADU',
                'priority'          => 'HIGH',
                'status'            => 'Approved',
                'location'          => 'NACOC Central Vault Safe A-01',
                'description'       => 'Detailed forensic analysis, exhibits chain of custody log, and analytical examination report.',
                'exhibits_summary'  => '90 Kilograms (kg) Heroin (Brown Sugar / Diacetylmorphine)',
                'hash_log'          => 'HASH-' . strtoupper(substr(md5($id), 0, 8)) . '-VERIFIED',
                'incident_date'     => now(),
            ]);
        }
        $this->repairExhibitAssociations($case);

        $gsaReports = \App\Models\SuspectGsaReport::forCase(
            $case->case_number ?: $id,
            $case->id ?: $id
        );
        $nlrReports = \App\Models\SuspectNlrReport::forCase(
            $case->case_number ?: $id,
            $case->id ?: $id
        );

        return view('Lab_Analysis.analytical', compact('case', 'gsaReports', 'nlrReports'));
    }

    /**
     * Dedicated NACOC Lab Result (NLR) Report PDF / Printable View
     */
    public function showNlrReportPdf($id)
    {
        $case = $this->findCase($id);
        if (!$case) {
            $dbHead = \App\Models\User::where('is_head', true)->first();
            $headName = $dbHead ? $dbHead->fullname : 'Anita Johnson';
            $case = new ForensicCase([
                'case_number'       => $id,
                'title'             => 'Directorate High Priority Forensic Docket',
                'unit_name'         => 'Forensic Directorate',
                'commanding_officer'=> $headName,
                'submitting_officer'=> 'Insp. Eric Ofori',
                'suspect_name'      => 'KOJO ADU',
                'priority'          => 'HIGH',
                'status'            => 'Approved',
                'location'          => 'NACOC Central Vault Safe A-01',
                'description'       => 'Detailed forensic analysis, exhibits chain of custody log, and analytical examination report.',
                'exhibits_summary'  => '90 Kilograms (kg) Heroin (Brown Sugar / Diacetylmorphine)',
                'hash_log'          => 'HASH-' . strtoupper(substr(md5($id), 0, 8)) . '-VERIFIED',
                'incident_date'     => now(),
            ]);
        }
        $this->repairExhibitAssociations($case);

        $gsaReports = \App\Models\SuspectGsaReport::forCase(
            $case->case_number ?: $id,
            $case->id ?: $id
        );
        $nlrReports = \App\Models\SuspectNlrReport::forCase(
            $case->case_number ?: $id,
            $case->id ?: $id
        );

        return view('Lab_Analysis.nlr_report_pdf', compact('case', 'gsaReports', 'nlrReports'));

    }

    /**
     * Store / Update NLR Report data (detailed_exhibit_description & condition_of_reserved_sample)
     */
    public function storeNlrReport(Request $request)
    {
        $validated = $request->validate([
            'case_id'                       => 'nullable',
            'case_number'                   => 'nullable|string',
            'laboratory_number'             => 'nullable|string',
            'detailed_exhibit_description'  => 'required|string',
            'condition_of_reserved_sample'  => 'nullable|string',
            'suspect_id'                    => 'nullable',
            'nlr_code'                      => 'nullable|string',
        ]);

        $caseId = $request->input('case_id');
        $caseNo = $request->input('case_number');
        $labNo  = $request->input('laboratory_number');

        $singleId = null;
        $multipleId = null;
        $case = null;

        if ($caseId) {
            $case = $this->findCase($caseId);
            if ($case && $case->underlyingRecord) {
                if ($case->underlyingRecord instanceof \App\Models\MultipleSuspectCase) {
                    $multipleId = $case->id;
                } else {
                    $singleId = $case->id;
                }
                $caseNo = $caseNo ?: $case->case_number;
                $labNo  = $labNo ?: $case->laboratory_number;
            }
        }

        $report = \App\Models\NlrReport::forCase($caseId, $caseNo, $labNo);

        if (!$report) {
            $report = new \App\Models\NlrReport();
        }

        // Determine / Generate NLR Code for the NLR report (never use GSA-FM-T51-F)
        $inputCode = $request->input('nlr_code');
        if (!empty($inputCode)) {
            $report->nlr_code = str_starts_with($inputCode, 'GSA-') ? str_replace('GSA-', 'NLR-', $inputCode) : $inputCode;
        } elseif (empty($report->nlr_code)) {
            $rawRef = $case ? $case->lab_exam_reference : null;
            if (!empty($rawRef)) {
                $report->nlr_code = str_starts_with($rawRef, 'GSA-') ? str_replace('GSA-', 'NLR-', $rawRef) : $rawRef;
            } else {
                $report->nlr_code = 'NLR-FM-T51-F';
            }
        }

        $report->single_suspect_case_id       = $singleId ?: $report->single_suspect_case_id;
        $report->multiple_suspect_case_id     = $multipleId ?: $report->multiple_suspect_case_id;
        $report->case_number                 = $caseNo ?: $report->case_number;
        $report->laboratory_number           = $labNo ?: $report->laboratory_number;
        $report->suspect_id                  = $request->input('suspect_id') ?: $report->suspect_id;
        $report->detailed_exhibit_description = $validated['detailed_exhibit_description'];
        $report->condition_of_reserved_sample = $validated['condition_of_reserved_sample'] ?? $report->condition_of_reserved_sample;
        $report->user_id                     = auth()->id();
        $report->analyst                     = auth()->user() ? auth()->user()->fullname : null;
        $report->save();

        if (isset($case) && $case && $case->underlyingRecord) {
            $rec = $case->underlyingRecord;
            if (\Illuminate\Support\Facades\Schema::hasColumn($rec->getTable(), 'docket_data')) {
                $dData = is_array($rec->docket_data) ? $rec->docket_data : (json_decode($rec->docket_data ?? '[]', true) ?: []);
                $dData['detailed_exhibit_description'] = $validated['detailed_exhibit_description'];
                if (!empty($validated['condition_of_reserved_sample'])) {
                    $dData['condition_of_reserved_sample'] = $validated['condition_of_reserved_sample'];
                }
                $rec->docket_data = $dData;
                $rec->save();
            }
        }

        // Attach NLR Report PDF to the suspect(s) record (same attachment style as GSA Report)
        $this->attachNlrReportToSuspects($case, $report, $request);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'NLR Report detailed exhibit description saved successfully.',
                'data'    => $report,
            ]);
        }

        return redirect()->back()->with('success', 'NLR Report detailed exhibit description saved successfully.');
    }

    /**
     * Attach/Link the NLR Report PDF to suspect(s) in suspect_gsa_reports table.
     */
    protected function attachNlrReportToSuspects($case, $report, Request $request)
    {
        $caseNo = $report->case_number ?: ($case ? $case->case_number : null);
        $caseId = $case ? $case->id : ($report->multiple_suspect_case_id ?: $report->single_suspect_case_id);

        $multiCase = null;
        if ($case && $case->underlyingRecord instanceof \App\Models\MultipleSuspectCase) {
            $multiCase = $case->underlyingRecord;
        } elseif ($report->multiple_suspect_case_id) {
            $multiCase = \App\Models\MultipleSuspectCase::find($report->multiple_suspect_case_id);
        }
        if (!$multiCase && $caseNo) {
            $multiCase = \App\Models\MultipleSuspectCase::where('case_number', (string) $caseNo)->first();
        }

        if (!$multiCase) {
            $cNum = (string) ($caseNo ?: ($case ? $case->case_number : ($caseId ?: 'CAS-UNKNOWN')));
            $sName = $case ? ($case->suspect_name ?? 'Primary Suspect') : 'Primary Suspect';
            $multiCase = \App\Models\MultipleSuspectCase::create([
                'case_number'        => $cNum,
                'title'              => $case->title ?? 'Forensic Case ' . $cNum,
                'unit_name'          => $case->unit_name ?? 'Forensic Laboratory',
                'suspect_name'       => $sName,
                'controller_user_id' => auth()->id(),
            ]);
        }

        // Collect all suspect names associated with this case
        $suspectNames = [];
        if ($case && !empty($case->docket_data)) {
            $dData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data, true) ?: []);
            if (!empty($dData['suspects']) && is_array($dData['suspects'])) {
                foreach ($dData['suspects'] as $sDoc) {
                    if (!empty($sDoc['suspect_name'])) {
                        $suspectNames[] = trim($sDoc['suspect_name']);
                    }
                }
            }
        }
        if (empty($suspectNames) && $case && !empty($case->suspect_name)) {
            $suspectNames[] = trim($case->suspect_name);
        }
        if (empty($suspectNames) && !empty($multiCase->suspect_name)) {
            $suspectNames[] = trim($multiCase->suspect_name);
        }
        if (empty($suspectNames)) {
            $suspectNames[] = 'Primary Suspect';
        }
        $suspectNames = array_values(array_unique($suspectNames));

        $caseSuspects = \App\Models\CaseSuspect::where('multiple_suspect_case_id', $multiCase->id)
            ->orderBy('suspect_number')
            ->get();

        // Only populate initial suspect records if none exist yet for this case
        if ($caseSuspects->count() === 0) {
            foreach ($suspectNames as $sIdx => $sName) {
                $norm = strtolower(preg_replace('/\s+/', ' ', $sName));
                $found = $caseSuspects->first(function ($s) use ($norm) {
                    return strtolower(preg_replace('/\s+/', ' ', (string) $s->suspect_name)) === $norm;
                });

                if (!$found) {
                    $created = \App\Models\CaseSuspect::create([
                        'multiple_suspect_case_id' => $multiCase->id,
                        'suspect_number'           => $sIdx + 1,
                        'role'                     => $sIdx === 0 ? 'Primary Suspect' : 'Co-Suspect',
                        'suspect_name'             => $sName,
                    ]);
                    $caseSuspects->push($created);
                }
            }
        }

        $targetSuspects = [];
        $reqSuspectId = $request->input('suspect_id') ?: $report->suspect_id;
        $reqSuspectName = $request->input('suspect_name');

        if ($reqSuspectId) {
            $found = \App\Models\CaseSuspect::find((int) $reqSuspectId) ?: $caseSuspects->firstWhere('id', (int) $reqSuspectId);
            if ($found) {
                $targetSuspects = [$found];
            }
        }

        if (empty($targetSuspects) && $reqSuspectName) {
            $normReq = strtolower(preg_replace('/\s+/', ' ', trim($reqSuspectName)));
            $found = $caseSuspects->first(function ($s) use ($normReq) {
                return strtolower(preg_replace('/\s+/', ' ', trim((string) $s->suspect_name))) === $normReq;
            });
            if ($found) {
                $targetSuspects = [$found];
            }
        }

        if (empty($targetSuspects)) {
            $primary = $caseSuspects->firstWhere('role', 'Primary Suspect') ?: $caseSuspects->first();
            if ($primary) {
                $targetSuspects = [$primary];
            } else {
                $targetSuspects = $caseSuspects->all();
            }
        }

        $pdfUrlPath   = 'Lab_Analysis/nlr-report-pdf/' . ($caseId ?: $multiCase->id);
        $filename     = 'NLR_Report_' . ($report->nlr_code ?: ($report->laboratory_number ?: 'NACOC')) . '.pdf';
        $finalCaseNo  = $caseNo ?: ($case ? $case->case_number : ($multiCase ? $multiCase->case_number : null));
        $singleCaseId = ($case && !($case->underlyingRecord instanceof \App\Models\MultipleSuspectCase)) ? $case->id : ($report->single_suspect_case_id ?: null);

        if (empty($targetSuspects)) {
            $existingReport = \App\Models\SuspectNlrReport::where(function ($q) use ($multiCase, $singleCaseId, $finalCaseNo) {
                if ($multiCase) $q->orWhere('multiple_suspect_case_id', $multiCase->id);
                if ($singleCaseId) $q->orWhere('single_suspect_case_id', $singleCaseId);
                if ($finalCaseNo) $q->orWhere('case_number', $finalCaseNo);
            })
            ->where(function ($q) use ($pdfUrlPath) {
                $q->where('file_path', $pdfUrlPath)
                  ->orWhere('file_path', '/' . ltrim($pdfUrlPath, '/'))
                  ->orWhere('original_filename', 'LIKE', 'NLR_Report%');
            })
            ->first();

            if ($existingReport) {
                $existingReport->update([
                    'multiple_suspect_case_id' => $multiCase ? $multiCase->id : null,
                    'single_suspect_case_id'   => $singleCaseId,
                    'lab_number'               => $report->laboratory_number ?: $finalCaseNo,
                    'case_number'              => $finalCaseNo,
                    'original_filename'        => $filename,
                    'file_path'                => $pdfUrlPath,
                    'notes'                    => 'Attached NLR Report PDF (' . ($report->nlr_code ?: 'NLR-FM-T51-F') . ')',
                    'status'                   => 'active',
                ]);
            } else {
                \App\Models\SuspectNlrReport::create([
                    'case_suspect_id'          => null,
                    'multiple_suspect_case_id' => $multiCase ? $multiCase->id : null,
                    'single_suspect_case_id'   => $singleCaseId,
                    'uploaded_by'              => auth()->id(),
                    'original_filename'        => $filename,
                    'stored_filename'          => 'nlr_report_' . $report->id . '.pdf',
                    'file_path'                => $pdfUrlPath,
                    'file_type'                => 'application/pdf',
                    'file_extension'           => 'pdf',
                    'file_size'                => 0,
                    'lab_number'               => $report->laboratory_number ?: $finalCaseNo,
                    'case_number'              => $finalCaseNo,
                    'notes'                    => 'Attached NLR Report PDF (' . ($report->nlr_code ?: 'NLR-FM-T51-F') . ')',
                    'status'                   => 'active',
                ]);
            }
        } else {
            foreach ($targetSuspects as $s) {
                $existingReport = \App\Models\SuspectNlrReport::where('case_suspect_id', $s->id)
                    ->where(function ($q) use ($pdfUrlPath) {
                        $q->where('file_path', $pdfUrlPath)
                          ->orWhere('file_path', '/' . ltrim($pdfUrlPath, '/'))
                          ->orWhere('original_filename', 'LIKE', 'NLR_Report%');
                    })
                    ->first();

                if ($existingReport) {
                    $existingReport->update([
                        'multiple_suspect_case_id' => $multiCase ? $multiCase->id : null,
                        'single_suspect_case_id'   => $singleCaseId,
                        'lab_number'        => $report->laboratory_number ?: $finalCaseNo,
                        'case_number'       => $finalCaseNo,
                        'original_filename' => $filename,
                        'file_path'         => $pdfUrlPath,
                        'notes'             => 'Attached NLR Report PDF (' . ($report->nlr_code ?: 'NLR-FM-T51-F') . ')',
                        'status'            => 'active',
                    ]);
                } else {
                    \App\Models\SuspectNlrReport::create([
                        'case_suspect_id'          => $s->id,
                        'multiple_suspect_case_id' => $multiCase ? $multiCase->id : null,
                        'single_suspect_case_id'   => $singleCaseId,
                        'uploaded_by'              => auth()->id(),
                        'original_filename'        => $filename,
                        'stored_filename'          => 'nlr_report_' . $report->id . '.pdf',
                        'file_path'                => $pdfUrlPath,
                        'file_type'                => 'application/pdf',
                        'file_extension'           => 'pdf',
                        'file_size'                => 0,
                        'lab_number'               => $report->laboratory_number ?: $finalCaseNo,
                        'case_number'              => $finalCaseNo,
                        'notes'                    => 'Attached NLR Report PDF (' . ($report->nlr_code ?: 'NLR-FM-T51-F') . ')',
                        'status'                   => 'active',
                    ]);
                }
            }
        }

    }

    /**
     * API endpoint to check suspect biodata duplicate before submission.
     */
    public function checkDuplicateSuspect(Request $request)
    {
        $detector = new \App\Services\CaseDuplicateDetector();
        $result = $detector->checkSuspectBiodata($request->all());
        return response()->json($result);
    }

    /**
     * API endpoint to check case duplicate before submission.
     */
    public function checkDuplicateCase(Request $request)
    {
        $rawDocketInput = $request->input('docket_data');
        $inputDocket = is_array($rawDocketInput)
            ? $rawDocketInput
            : (json_decode($rawDocketInput ?? '{}', true) ?: []);

        $allSuspects = (isset($inputDocket['suspects']) && is_array($inputDocket['suspects']))
            ? array_values(array_filter($inputDocket['suspects'], function($s) {
                $n = trim($s['suspect_name'] ?? '');
                return !empty($n) && $n !== 'Unnamed Suspect' && $n !== 'Unknown';
            }))
            : [];

        if (empty($allSuspects) && $request->filled('suspect_name')) {
            $allSuspects = [[
                'suspect_name' => $request->input('suspect_name'),
                'ghana_card'   => $request->input('ghana_card'),
                'dob'          => $request->input('dob'),
                'gender'       => $request->input('gender'),
                'nationality'  => $request->input('nationality'),
                'phone'        => $request->input('phone'),
                'address'      => $request->input('address'),
            ]];
        }

        $detector = new \App\Services\CaseDuplicateDetector();
        $result = $detector->checkCaseDuplicate($request->all(), $allSuspects);
        return response()->json($result);
    }

    /**
     * Update Case Status. Must be authorized Case Status Controller.
     */
    public function updateCaseStatus(Request $request, $id)
    {
        $validated = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'case_status' => [
                'required',
                'string',
                'in:Under Investigation,On Remand,On Bail,In Court,Verdict Passed â€” Imprisoned,Verdict Passed â€” Acquitted,Case Dismissed,Case Closed'
            ],
        ])->validate();

        $case = $this->findCase($id);
        if (!$case) {
            return response()->json(['message' => 'Case file not found.'], 404);
        }

        $user = Auth::user();
        $isHead = !empty($user->is_head) || (isset($user->unit) && str_contains(strtolower($user->unit), 'head'));
        if (!$user || (!$isHead && strtolower($user->role ?? '') !== 'admin')) {
            return response()->json([
                'message' => 'Unauthorized: Only a Head of Unit or Admin can change the case status.'
            ], 403);
        }

        $oldStatus = $case->case_status ?? 'Under Investigation';
        $newStatus = $validated['case_status'];

        DB::transaction(function () use ($case, $oldStatus, $newStatus, $user) {
            $case->case_status = $newStatus;
            $case->save();

            $underlying = $case->underlyingRecord;
            if (!$underlying) {
                if (is_numeric($case->id)) {
                    $underlying = \App\Models\MultipleSuspectCase::find($case->id)
                        ?? \App\Models\SingleSuspectCase::find($case->id);
                }
                if (!$underlying && !empty($case->case_number)) {
                    $underlying = \App\Models\MultipleSuspectCase::where('case_number', $case->case_number)->first()
                        ?? \App\Models\SingleSuspectCase::where('case_number', $case->case_number)->first();
                }
            }
            $caseNum = $case->case_number ?? ($underlying ? $underlying->case_number : null);

            $isMultipleCase = ($underlying instanceof \App\Models\MultipleSuspectCase)
                || (method_exists($case, 'getSuspectsList') && count($case->getSuspectsList()) > 1);

            $isClearedOrReleased = function (?string $disp): bool {
                if (empty($disp)) return false;
                $t = strtolower(trim($disp));
                return str_contains($t, 'cleared') || str_contains($t, 'released') || str_contains($t, 'reclassified');
            };

            // 1. Update underlying model case_status & suspect_disposition
            if ($underlying) {
                $underlying->case_status = $newStatus;
                // For single suspect, case status unconditionally controls disposition. For multi-suspect, protect cleared/released.
                if (!$isMultipleCase || !$isClearedOrReleased($underlying->suspect_disposition)) {
                    $underlying->suspect_disposition = $newStatus;
                    $underlying->disposition_updated_at = now();
                    $underlying->disposition_updated_by = $user->fullname ?? $user->username;
                }
                $underlying->save();
            }

            // 2. Sync with all case_suspects rows if multi-suspect case
            $multiId = ($underlying && $underlying instanceof \App\Models\MultipleSuspectCase)
                ? $underlying->id
                : $case->id;

            $csRecords = \App\Models\CaseSuspect::where(function ($q) use ($multiId, $case, $caseNum) {
                if ($multiId) $q->orWhere('multiple_suspect_case_id', $multiId);
                if ($case && $case->id && $case->id !== $multiId) $q->orWhere('multiple_suspect_case_id', $case->id);
            })->get();

            if ($csRecords->isEmpty() && $caseNum) {
                $csRecords = \App\Models\CaseSuspect::whereIn('multiple_suspect_case_id', function ($q) use ($caseNum) {
                    $q->select('id')->from('multiple_suspect_cases')->where('case_number', $caseNum);
                })->get();
            }

            foreach ($csRecords as $cs) {
                $csData = ['case_status' => $newStatus];
                if (!$isMultipleCase || !$isClearedOrReleased($cs->suspect_disposition)) {
                    $csData['suspect_disposition'] = $newStatus;
                    $csData['disposition_updated_at'] = now();
                    $csData['disposition_updated_by'] = $user->fullname ?? $user->username;
                }
                $cs->update($csData);
            }

            // 3. Update docket_data['suspects'] array if exists
            if ($underlying && \Illuminate\Support\Facades\Schema::hasColumn($underlying->getTable(), 'docket_data')) {
                $dData = is_array($underlying->docket_data)
                    ? $underlying->docket_data
                    : (json_decode($underlying->docket_data ?? '[]', true) ?: []);

                if (isset($dData['suspects']) && is_array($dData['suspects'])) {
                    foreach ($dData['suspects'] as &$s) {
                        $s['case_status'] = $newStatus;
                        if (!$isMultipleCase || !$isClearedOrReleased($s['suspect_disposition'] ?? null)) {
                            $s['suspect_disposition'] = $newStatus;
                            $s['disposition_updated_at'] = now()->toDateTimeString();
                            $s['disposition_updated_by'] = $user->fullname ?? $user->username;
                        }
                    }
                    unset($s);
                    $underlying->docket_data = $dData;
                    $underlying->save();
                }
            }

            // 4. Sync main tables by case_number
            if ($caseNum) {
                \App\Models\SingleSuspectCase::where('case_number', $caseNum)->get()->each(function ($ssc) use ($newStatus, $isMultipleCase, $isClearedOrReleased, $user) {
                    $updateData = ['case_status' => $newStatus];
                    if (!$isMultipleCase || !$isClearedOrReleased($ssc->suspect_disposition)) {
                        $updateData['suspect_disposition'] = $newStatus;
                        $updateData['disposition_updated_at'] = now();
                        $updateData['disposition_updated_by'] = $user->fullname ?? $user->username;
                    }
                    $ssc->update($updateData);
                });

                \App\Models\MultipleSuspectCase::where('case_number', $caseNum)->get()->each(function ($msc) use ($newStatus, $isMultipleCase, $isClearedOrReleased, $user) {
                    $updateData = ['case_status' => $newStatus];
                    if (!$isMultipleCase || !$isClearedOrReleased($msc->suspect_disposition)) {
                        $updateData['suspect_disposition'] = $newStatus;
                        $updateData['disposition_updated_at'] = now();
                        $updateData['disposition_updated_by'] = $user->fullname ?? $user->username;
                    }
                    $msc->update($updateData);
                });
            }

            AuditLogger::log(
                'CASE_STATUS_CHANGED',
                "Case Status updated from '{$oldStatus}' to '{$newStatus}' for Case {$case->case_number}",
                [
                    'case_number'    => $case->case_number,
                    'auditable_type' => get_class($case->underlyingRecord ?? $case),
                    'auditable_id'   => $case->id,
                    'old_values'     => ['case_status' => $oldStatus],
                    'new_values'     => ['case_status' => $newStatus],
                    'user'           => $user,
                ]
            );
        });

        $refreshed = $case->fresh();

        return response()->json([
            'success'     => true,
            'message'     => "Case status successfully updated to '{$newStatus}'.",
            'case_status' => $newStatus,
            'case'        => $refreshed,
        ]);
    }

    /**
     * Initiate Case Status Handover request.
     */
    public function initiateHandover(Request $request, $id)
    {
        $validated = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'to_user_id'    => ['required', 'exists:users,id'],
            'request_notes' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        $case = $this->findCase($id);
        if (!$case) {
            return response()->json(['message' => 'Case file not found.'], 404);
        }

        $user = Auth::user();
        if (!$case->isController($user)) {
            return response()->json([
                'message' => 'Unauthorized: Only the current Case Status Controller can initiate a handover.'
            ], 403);
        }

        $toUser = \App\Models\User::findOrFail($validated['to_user_id']);
        if ((int)$toUser->id === (int)$user->id) {
            return response()->json([
                'message' => 'You cannot transfer case control to yourself.'
            ], 422);
        }

        // Check if pending handover exists
        $existing = CaseHandover::where('case_number', $case->case_number)
            ->where('status', 'Pending Acknowledgement')
            ->first();
        if ($existing) {
            return response()->json([
                'message' => 'An active handover request is already pending acknowledgement for this case.'
            ], 422);
        }

        $handover = DB::transaction(function () use ($case, $user, $toUser, $validated) {
            $h = CaseHandover::create([
                'case_number'              => $case->case_number,
                'single_suspect_case_id'   => $case->underlyingRecord instanceof \App\Models\SingleSuspectCase ? $case->id : null,
                'multiple_suspect_case_id' => $case->underlyingRecord instanceof \App\Models\MultipleSuspectCase ? $case->id : null,
                'from_user_id'             => $user->id,
                'from_unit'                => $user->unit ?? 'Unknown',
                'to_user_id'               => $toUser->id,
                'to_unit'                  => $toUser->unit ?? 'Unknown',
                'status'                   => 'Pending Acknowledgement',
                'request_notes'            => $validated['request_notes'] ?? null,
            ]);

            AuditLogger::log(
                'HANDOVER_INITIATED',
                "Handover initiated by {$user->fullname} ({$user->unit}) to {$toUser->fullname} ({$toUser->unit}) for Case {$case->case_number}",
                [
                    'case_number'    => $case->case_number,
                    'auditable_type' => CaseHandover::class,
                    'auditable_id'   => $h->id,
                    'new_values'     => [
                        'from_user' => $user->fullname,
                        'to_user'   => $toUser->fullname,
                        'to_unit'   => $toUser->unit,
                        'status'    => 'Pending Acknowledgement',
                    ],
                    'user'           => $user,
                ]
            );

            return $h;
        });

        return response()->json([
            'success'  => true,
            'message'  => "Handover request successfully sent to {$toUser->fullname} ({$toUser->unit}). Control remains with you until acknowledged.",
            'handover' => $handover,
        ]);
    }

    /**
     * Respond to Case Status Handover request (Accept, Decline, or Cancel).
     */
    public function respondHandover(Request $request, $id)
    {
        $validated = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'action'         => ['required', 'string', 'in:accept,decline,cancel'],
            'response_notes' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        $case = $this->findCase($id);
        if (!$case) {
            return response()->json(['message' => 'Case file not found.'], 404);
        }

        $handover = CaseHandover::where('case_number', $case->case_number)
            ->where('status', 'Pending Acknowledgement')
            ->latest()
            ->first();

        if (!$handover) {
            return response()->json(['message' => 'No pending handover request found for this case.'], 404);
        }

        $user = Auth::user();
        $action = strtolower($validated['action']);

        if ($action === 'accept') {
            if ((int)$user->id !== (int)$handover->to_user_id) {
                return response()->json([
                    'message' => 'Unauthorized: Only the designated receiving Unit Head can accept this handover request.'
                ], 403);
            }

            DB::transaction(function () use ($case, $handover, $user, $validated) {
                $handover->update([
                    'status'          => 'Accepted',
                    'acknowledged_at' => now(),
                    'response_notes'  => $validated['response_notes'] ?? null,
                ]);

                // Transfer controller_user_id
                $case->controller_user_id = $user->id;
                $case->save();

                AuditLogger::log(
                    'HANDOVER_ACCEPTED',
                    "Case Status Control for Case {$case->case_number} transferred from {$handover->fromUser->fullname} to {$user->fullname} ({$user->unit})",
                    [
                        'case_number'    => $case->case_number,
                        'auditable_type' => CaseHandover::class,
                        'auditable_id'   => $handover->id,
                        'old_values'     => ['controller_user_id' => $handover->from_user_id],
                        'new_values'     => ['controller_user_id' => $user->id],
                        'user'           => $user,
                    ]
                );
            });

            return response()->json([
                'success' => true,
                'message' => "Handover accepted. You are now the active Case Status Controller for Case {$case->case_number}.",
                'case'    => $case->fresh(),
            ]);
        } elseif ($action === 'decline') {
            if ((int)$user->id !== (int)$handover->to_user_id) {
                return response()->json([
                    'message' => 'Unauthorized: Only the designated receiving Unit Head can decline this handover request.'
                ], 403);
            }

            DB::transaction(function () use ($case, $handover, $user, $validated) {
                $handover->update([
                    'status'          => 'Declined',
                    'acknowledged_at' => now(),
                    'response_notes'  => $validated['response_notes'] ?? null,
                ]);

                AuditLogger::log(
                    'HANDOVER_DECLINED',
                    "Handover request for Case {$case->case_number} declined by {$user->fullname} ({$user->unit})",
                    [
                        'case_number'    => $case->case_number,
                        'auditable_type' => CaseHandover::class,
                        'auditable_id'   => $handover->id,
                        'user'           => $user,
                    ]
                );
            });

            return response()->json([
                'success' => true,
                'message' => "Handover request declined.",
            ]);
        } elseif ($action === 'cancel') {
            if ((int)$user->id !== (int)$handover->from_user_id) {
                return response()->json([
                    'message' => 'Unauthorized: Only the initiating Unit Head can cancel this handover request.'
                ], 403);
            }

            DB::transaction(function () use ($case, $handover, $user, $validated) {
                $handover->update([
                    'status'         => 'Cancelled',
                    'response_notes' => $validated['response_notes'] ?? 'Cancelled by initiator',
                ]);

                AuditLogger::log(
                    'HANDOVER_CANCELLED',
                    "Handover request for Case {$case->case_number} cancelled by {$user->fullname}",
                    [
                        'case_number'    => $case->case_number,
                        'auditable_type' => CaseHandover::class,
                        'auditable_id'   => $handover->id,
                        'user'           => $user,
                    ]
                );
            });

            return response()->json([
                'success' => true,
                'message' => "Handover request cancelled.",
            ]);
        }

        return response()->json(['message' => 'Invalid action.'], 400);
    }

    /**
     * Get Audit Trail logs for a case.
     */
    public function getAuditTrail($id)
    {
        $case = $this->findCase($id);
        if (!$case) {
            return response()->json(['message' => 'Case file not found.'], 404);
        }

        $logs = AuditLog::where('case_number', $case->case_number)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'logs'    => $logs,
        ]);
    }

    /**
     * Update suspect-level disposition (e.g., Cleared / No Further Action).
     */
    public function updateSuspectDisposition(Request $request, $id)
    {
        $validated = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'suspect_name'        => ['required', 'string'],
            'suspect_disposition' => ['required', 'string'],
            'disposition_reason'  => ['nullable', 'string', 'max:1000'],
        ])->validate();

        $case = $this->findCase($id);
        if (!$case) {
            return response()->json(['message' => 'Case file not found.'], 404);
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized: Please log in to update suspect disposition.'
            ], 401);
        }

        $isHead = !empty($user->is_head) || !empty($user->is_unit_head) || (isset($user->unit) && str_contains(strtolower($user->unit), 'head'));
        $isAdmin = strtolower($user->role ?? '') === 'admin';
        $isController = !empty($case->controller_user_id) && (int)$case->controller_user_id === (int)$user->id;
        $isCreator = !empty($case->user_id) && (int)$case->user_id === (int)$user->id;

        if (!$isHead && !$isAdmin && !$isController && !$isCreator) {
            return response()->json([
                'message' => 'Unauthorized: Only a Head of Unit, Admin, Case Controller, or Case Creator can modify suspect dispositions.'
            ], 403);
        }

        $underlyingCheck = $case->underlyingRecord;
        if (!$underlyingCheck) {
            if (is_numeric($case->id)) {
                $underlyingCheck = \App\Models\MultipleSuspectCase::find($case->id)
                    ?? \App\Models\SingleSuspectCase::find($case->id);
            }
            if (!$underlyingCheck && !empty($case->case_number)) {
                $underlyingCheck = \App\Models\MultipleSuspectCase::where('case_number', $case->case_number)->first()
                    ?? \App\Models\SingleSuspectCase::where('case_number', $case->case_number)->first();
            }
        }

        $isMultipleCase = ($underlyingCheck instanceof \App\Models\MultipleSuspectCase)
            || (method_exists($case, 'getSuspectsList') && count($case->getSuspectsList()) > 1);

        if (!$isMultipleCase) {
            return response()->json([
                'message' => 'Suspect disposition updates are strictly available for multiple suspect cases only.'
            ], 422);
        }

        $rawDisp = trim($validated['suspect_disposition']);
        if (str_contains($rawDisp, 'Active')) {
            $newDisp = 'Active — Under Investigation';
        } elseif (str_contains($rawDisp, 'Cleared')) {
            $newDisp = 'Cleared / No Further Action';
        } elseif (str_contains($rawDisp, 'Released')) {
            $newDisp = 'Released from Case';
        } elseif (str_contains($rawDisp, 'Reclassified')) {
            $newDisp = 'Reclassified';
        } elseif (str_contains($rawDisp, 'Referred')) {
            $newDisp = 'Referred for Further Review';
        } else {
            $newDisp = $rawDisp;
        }

        $reason = trim($validated['disposition_reason'] ?? '');
        $suspectName = trim($validated['suspect_name']);

        if (!str_starts_with($newDisp, 'Active') && empty($reason)) {
            return response()->json([
                'message' => 'A valid justification reason is required when marking a suspect as cleared, released, or reclassified.'
            ], 422);
        }

        $oldDisp = 'Active — Under Investigation';

        DB::transaction(function () use ($case, $suspectName, $newDisp, $reason, $user, &$oldDisp) {
            $underlying = $case->underlyingRecord;
            if (!$underlying) {
                if (is_numeric($case->id)) {
                    $underlying = \App\Models\MultipleSuspectCase::find($case->id)
                        ?? \App\Models\SingleSuspectCase::find($case->id);
                }
                if (!$underlying && !empty($case->case_number)) {
                    $underlying = \App\Models\MultipleSuspectCase::where('case_number', $case->case_number)->first()
                        ?? \App\Models\SingleSuspectCase::where('case_number', $case->case_number)->first();
                }
            }

            $caseNum = $case->case_number ?? ($underlying ? $underlying->case_number : null);

            // 1. Update CaseSuspect if multi-suspect DB record exists
            $multiId = ($underlying && $underlying instanceof \App\Models\MultipleSuspectCase)
                ? $underlying->id
                : $case->id;

            $csRecords = \App\Models\CaseSuspect::where(function ($q) use ($multiId, $case, $caseNum) {
                if ($multiId) $q->orWhere('multiple_suspect_case_id', $multiId);
                if ($case && $case->id && $case->id !== $multiId) $q->orWhere('multiple_suspect_case_id', $case->id);
            })->get();

            if ($csRecords->isEmpty() && $caseNum) {
                $csRecords = \App\Models\CaseSuspect::whereIn('multiple_suspect_case_id', function ($q) use ($caseNum) {
                    $q->select('id')->from('multiple_suspect_cases')->where('case_number', $caseNum);
                })->get();
            }

            $matchedCs = null;
            $tNameClean = strtolower(trim($suspectName));

            foreach ($csRecords as $cs) {
                $csNameClean = strtolower(trim($cs->suspect_name ?? ''));
                if (!empty($csNameClean) && ($csNameClean === $tNameClean || str_contains($csNameClean, $tNameClean) || str_contains($tNameClean, $csNameClean))) {
                    $matchedCs = $cs;
                    $oldDisp = $cs->suspect_disposition ?? $oldDisp;
                    $cs->update([
                        'suspect_disposition'    => $newDisp,
                        'disposition_reason'     => $reason,
                        'disposition_updated_at' => now(),
                        'disposition_updated_by' => $user->fullname ?? $user->username,
                    ]);
                    break;
                }
            }

            if (!$matchedCs && $caseNum) {
                $csDirect = \App\Models\CaseSuspect::where(DB::raw('LOWER(TRIM(suspect_name))'), strtolower(trim($suspectName)))
                    ->whereIn('multiple_suspect_case_id', function ($q) use ($caseNum) {
                        $q->select('id')->from('multiple_suspect_cases')->where('case_number', $caseNum);
                    })->first();

                if ($csDirect) {
                    $matchedCs = $csDirect;
                    $oldDisp = $csDirect->suspect_disposition ?? $oldDisp;
                    $csDirect->update([
                        'suspect_disposition'    => $newDisp,
                        'disposition_reason'     => $reason,
                        'disposition_updated_at' => now(),
                        'disposition_updated_by' => $user->fullname ?? $user->username,
                    ]);
                }
            }

            // 2. Update docket_data['suspects'] array on underlying model IF column exists
            if ($underlying && \Illuminate\Support\Facades\Schema::hasColumn($underlying->getTable(), 'docket_data')) {
                $dData = is_array($underlying->docket_data)
                    ? $underlying->docket_data
                    : (json_decode($underlying->docket_data ?? '[]', true) ?: []);

                if (empty($dData['suspects']) || !is_array($dData['suspects'])) {
                    $dData['suspects'] = $case->getSuspectsList();
                }

                if (isset($dData['suspects']) && is_array($dData['suspects'])) {
                    $foundInArray = false;
                    foreach ($dData['suspects'] as &$s) {
                        $sNameClean = strtolower(trim($s['suspect_name'] ?? ''));
                        if (!empty($sNameClean) && ($sNameClean === $tNameClean || str_contains($sNameClean, $tNameClean) || str_contains($tNameClean, $sNameClean))) {
                            $oldDisp = $s['suspect_disposition'] ?? $oldDisp;
                            $s['suspect_disposition']    = $newDisp;
                            $s['disposition_reason']     = $reason;
                            $s['disposition_updated_at'] = now()->toDateTimeString();
                            $s['disposition_updated_by'] = $user->fullname ?? $user->username;
                            $foundInArray = true;
                            break;
                        }
                    }
                    unset($s);

                    if (!$foundInArray && !empty($suspectName)) {
                        $dData['suspects'][] = [
                            'suspect_name'           => $suspectName,
                            'suspect_disposition'    => $newDisp,
                            'disposition_reason'     => $reason,
                            'disposition_updated_at' => now()->toDateTimeString(),
                            'disposition_updated_by' => $user->fullname ?? $user->username,
                        ];
                    }
                    $underlying->docket_data = $dData;
                }
            }

            // Determine if target suspect is the Primary Suspect (or single suspect)
            $isPrimaryTarget = false;
            if ($underlying instanceof \App\Models\SingleSuspectCase) {
                $isPrimaryTarget = true;
            } elseif ($matchedCs) {
                $roleLower = strtolower(trim($matchedCs->role ?? ''));
                if ($roleLower === 'primary suspect' || $roleLower === 'primary' || (int)($matchedCs->suspect_number ?? 0) === 1) {
                    $isPrimaryTarget = true;
                }
            } else {
                $primName = strtolower(trim($underlying->suspect_name ?? ''));
                if (!empty($primName) && $primName === strtolower(trim($suspectName))) {
                    $isPrimaryTarget = true;
                }
            }

            if ($underlying) {
                if ($isPrimaryTarget) {
                    $underlying->suspect_disposition    = $newDisp;
                    $underlying->disposition_reason     = $reason;
                    $underlying->disposition_updated_at = now();
                    $underlying->disposition_updated_by = $user->fullname ?? $user->username;
                }
                $underlying->save();
            }

            // 4. Sync main table suspect_disposition columns by case_number ONLY if primary suspect was target
            if ($caseNum && $isPrimaryTarget) {
                \App\Models\SingleSuspectCase::where('case_number', $caseNum)
                    ->update([
                        'suspect_disposition'    => $newDisp,
                        'disposition_reason'     => $reason,
                        'disposition_updated_at' => now(),
                        'disposition_updated_by' => $user->fullname ?? $user->username,
                    ]);

                \App\Models\MultipleSuspectCase::where('case_number', $caseNum)
                    ->update([
                        'suspect_disposition'    => $newDisp,
                        'disposition_reason'     => $reason,
                        'disposition_updated_at' => now(),
                        'disposition_updated_by' => $user->fullname ?? $user->username,
                    ]);
            }

            AuditLogger::log(
                'SUSPECT_DISPOSITION_CHANGED',
                "Suspect disposition for {$suspectName} updated from '{$oldDisp}' to '{$newDisp}' on Case {$case->case_number}. Reason: {$reason}",
                [
                    'case_number'    => $case->case_number,
                    'auditable_type' => $underlying ? get_class($underlying) : get_class($case),
                    'auditable_id'   => $underlying ? $underlying->id : $case->id,
                    'old_values'     => ['suspect_name' => $suspectName, 'suspect_disposition' => $oldDisp],
                    'new_values'     => ['suspect_name' => $suspectName, 'suspect_disposition' => $newDisp, 'disposition_reason' => $reason],
                    'user'           => $user,
                ]
            );
        });

        $freshCase = $this->findCase($case->case_number) ?? $this->findCase($id);

        return response()->json([
            'success'          => true,
            'message'          => "Suspect disposition for {$suspectName} updated to '{$newDisp}'.",
            'case'             => $freshCase,
            'active_suspects'  => $freshCase ? $freshCase->getActiveSuspects() : [],
            'cleared_suspects' => $freshCase ? $freshCase->getClearedSuspects() : [],
        ]);
    }

    /**
     * Upload one or more evidence/exhibit photos and return their stored public URLs.
     * Accepts multipart/form-data with field name 'photos[]' (multiple files).
     */
    public function uploadEvidencePhotos(Request $request)
    {
        $request->validate([
            'photos'   => ['required', 'array', 'min:1'],
            'photos.*' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:10240'],
        ]);

        $stored = [];

        foreach ($request->file('photos') as $photo) {
            if (!$photo->isValid()) {
                continue;
            }

            $path = $photo->store('evidence_photos', 'public');

            if ($path) {
                $stored[] = \Illuminate\Support\Facades\Storage::url($path);
            }
        }

        if (empty($stored)) {
            return response()->json(['success' => false, 'message' => 'No valid photos were uploaded.'], 422);
        }

        return response()->json([
            'success' => true,
            'urls'    => $stored,
            'message' => count($stored) . ' photo(s) uploaded successfully.',
        ]);
    }

    /**
     * Display Lab Analysis Results page showing suspects linked strictly to drugs only.
     * Groups multiple suspects linked to the SAME drug exhibit into a single record card.
     */
        public function labResultPage(Request $request)
    {
        $cases = ForensicCase::allCases();

        $drugKeywords = [
            'drug', 'narcotic', 'substance', 'cannabis', 'cocaine', 'heroin', 
            'meth', 'tramadol', 'weed', 'hemp', 'hashish', 'ephedrine', 
            'precursor', 'opioid', 'fentanyl', 'amphetamine', 'mdma', 'ecstasy',
            'morphine', 'opium', 'diacetylmorphine'
        ];

        $nonDrugKeywords = [
            'truck', 'vehicle', 'car', 'pistol', 'rifle', 'weapon', 'currency', 'zonda', 
            'cash', 'usd', 'ghs', 'laptop', 'phone', 'automobile', 'vessel', 'device', 
            'motorcycle', 'boat', 'firearm'
        ];

        $isStrictlyDrugName = function ($str) use ($nonDrugKeywords, $drugKeywords) {
            if (empty($str)) return false;
            $lower = strtolower(trim($str));
            if (in_array($lower, ['exhibits', 'n/a', 'unknown', 'none', 'null', '1 batch', '1 batch / package'])) return false;

            $hasNonDrug = false;
            foreach ($nonDrugKeywords as $nk) {
                if (str_contains($lower, $nk)) {
                    $hasNonDrug = true;
                    break;
                }
            }

            if ($hasNonDrug) {
                foreach ($drugKeywords as $dk) {
                    if ($dk !== 'drug' && $dk !== 'substance' && $dk !== 'narcotic' && $dk !== 'precursor' && str_contains($lower, $dk)) {
                        return true;
                    }
                }
                return false;
            }

            return true;
        };

        $resolveDrugName = function ($ex, $c, $docket) use ($nonDrugKeywords, $drugKeywords, $isStrictlyDrugName) {
            $ex = is_array($ex) ? $ex : [];

            // 1. Explicit drug fields on exhibit level
            $exCandidates = [
                $ex['seizure_name_drug'] ?? null,
                $ex['substance_identification'] ?? null,
                $ex['drug_name'] ?? null,
                $ex['substance_name'] ?? null,
                $ex['chemical_name'] ?? null,
                $ex['seizure_name'] ?? null,
                $ex['commodity_name'] ?? null,
            ];
            foreach ($exCandidates as $cand) {
                if ($isStrictlyDrugName($cand)) {
                    return trim($cand);
                }
            }

            // 2. Lab examination substance identification in docket
            $labExam = $docket['lab_examination'] ?? [];
            $labCandidates = [
                $labExam['substance_identification'] ?? null,
                $labExam['sample_weight_description'] ?? null,
                $labExam['drug_name'] ?? null,
            ];
            foreach ($labCandidates as $cand) {
                if ($isStrictlyDrugName($cand)) {
                    return trim($cand);
                }
            }

            // 3. Lab examinations list in docket
            $labList = $docket['lab_examinations_list'] ?? [];
            if (is_array($labList)) {
                foreach ($labList as $lItem) {
                    if (is_array($lItem)) {
                        if ($isStrictlyDrugName($lItem['substance_identification'] ?? null)) {
                            return trim($lItem['substance_identification']);
                        }
                        if ($isStrictlyDrugName($lItem['sample_weight_description'] ?? null)) {
                            return trim($lItem['sample_weight_description']);
                        }
                    }
                }
            }

            // 4. Case-level drug fields
            $caseCandidates = [
                $c->seizure_name_drug ?? null,
                $c->confirmatory_substance_identification ?? null,
                $c->substance_identification ?? null,
                $c->drug_name ?? null,
                $c->seizure_name ?? null,
            ];
            foreach ($caseCandidates as $cand) {
                if ($isStrictlyDrugName($cand)) {
                    return trim($cand);
                }
            }

            // 5. Check text blocks (seizure_value / seizure_description / offense_description)
            $textSources = [
                $ex['seizure_value'] ?? '',
                $ex['seizure_description'] ?? '',
                $c->seizure_value ?? '',
                $c->seizure_description ?? '',
                $c->offense_description ?? '',
            ];
            foreach ($textSources as $txt) {
                if (empty($txt)) continue;
                $tLower = strtolower($txt);
                foreach ($drugKeywords as $dk) {
                    if ($dk === 'drug' || $dk === 'substance' || $dk === 'narcotic' || $dk === 'precursor') continue;
                    if (str_contains($tLower, $dk)) {
                        foreach (explode("\n", $txt) as $line) {
                            if (str_contains(strtolower($line), $dk)) {
                                $cleanLine = trim(preg_replace('/^.*?\\[Drugs\\]:\\s*/i', '', $line));
                                if (str_contains($cleanLine, '|')) {
                                    $parts = array_map('trim', explode('|', $cleanLine));
                                    foreach ($parts as $p) {
                                        if (str_contains(strtolower($p), $dk) && $isStrictlyDrugName($p)) {
                                            return $p;
                                        }
                                    }
                                }
                                if ($isStrictlyDrugName($cleanLine)) {
                                    return $cleanLine;
                                }
                            }
                        }
                    }
                }
            }

            // 6. Presumptive / Confirmatory test result hints
            $presumptive = $ex['presumptive_result'] ?? ($labExam['presumptive_result'] ?? ($c->presumptive_test_result ?? ''));
            if (!empty($presumptive)) {
                $pLower = strtolower($presumptive);
                if (str_contains($pLower, 'cocaine')) return 'Cocaine (Narcotic Exhibit)';
                if (str_contains($pLower, 'heroin') || str_contains($pLower, 'diacetylmorphine')) return 'Heroin (Diacetylmorphine / Narcotic Exhibit)';
                if (str_contains($pLower, 'cannabis') || str_contains($pLower, 'weed')) return 'Cannabis (Narcotic Exhibit)';
                if (str_contains($pLower, 'tramadol')) return 'Tramadol (Narcotic Exhibit)';
                if (str_contains($pLower, 'meth')) return 'Methamphetamine (Narcotic Exhibit)';
            }

            return 'Heroin (Diacetylmorphine / Narcotic Exhibit)';
        };

        $drugRecords = collect();

        $resolveMugshot = function ($mug, $genderStr, $idx) {
            if (!empty($mug)) {
                if (str_starts_with($mug, 'data:image') || str_starts_with($mug, 'http://') || str_starts_with($mug, 'https://')) {
                    return $mug;
                }
                if (str_starts_with($mug, '/')) {
                    return $mug;
                }
                return asset($mug);
            }
            $g = strtolower(trim($genderStr ?? 'male'));
            if ($g === 'female' || $g === 'f') {
                return asset('test/Lady front.jfif');
            }
            $mugs = ['test/01.jfif', 'test/02.jpg', 'test/03.jfif', 'test/04.jfif', 'test/05.jfif'];
            return asset($mugs[$idx % count($mugs)]);
        };

        $checkIsDrugExhibit = function ($ex) use ($drugKeywords, $nonDrugKeywords, $isStrictlyDrugName) {
            if (empty($ex)) return false;
            $exArr = is_array($ex) ? $ex : (method_exists($ex, 'toArray') ? $ex->toArray() : (array)$ex);
            $cat = strtolower($exArr['exhibit_category'] ?? ($exArr['category'] ?? ''));
            $name = strtolower($exArr['commodity_name'] ?? ($exArr['seizure_name'] ?? ($exArr['seizure_name_drug'] ?? '')));
            $class = strtolower($exArr['drug_classification'] ?? '');
            $substance = strtolower($exArr['substance_identification'] ?? '');

            if (!empty($substance) && $isStrictlyDrugName($substance)) return true;

            $isNonDrug = false;
            foreach ($nonDrugKeywords as $nk) {
                if (str_contains($cat, $nk) || str_contains($name, $nk)) {
                    $isNonDrug = true;
                    break;
                }
            }

            if ($isNonDrug) {
                foreach ($drugKeywords as $dk) {
                    if ($dk !== 'drug' && $dk !== 'substance' && $dk !== 'narcotic' && $dk !== 'precursor' && (str_contains($cat, $dk) || str_contains($name, $dk))) {
                        return true;
                    }
                }
                return false;
            }

            foreach ($drugKeywords as $dk) {
                if (str_contains($cat, $dk) || str_contains($name, $dk) || str_contains($class, $dk)) {
                    return true;
                }
            }

            return false;
        };

        $suspectNamesMatch = function($n1, $n2) {
            if (empty($n1) || empty($n2)) return false;
            $c1 = strtolower(trim($n1));
            $c2 = strtolower(trim($n2));
            if ($c1 === $c2) return true;
            return str_contains($c1, $c2) || str_contains($c2, $c1);
        };

        $isSuspectLinkedToExhibit = function ($s, $ex, $c, $suspectsCount) use ($checkIsDrugExhibit, $suspectNamesMatch) {
            $sName = trim((string)($s['suspect_name'] ?? ($c->suspect_name ?? 'Unnamed Suspect')));
            if (empty($sName)) return false;

            if (is_array($ex)) {
                $exSuspectId = $ex['suspect_id'] ?? null;
                $exAssoc = strtolower(trim($ex['associated_person'] ?? ($ex['associated_suspect'] ?? ($ex['suspect_name'] ?? ($ex['exhibit_owner_name'] ?? '')))));
            } else {
                $exSuspectId = $ex->suspect_id ?? null;
                $exAssoc = strtolower(trim($ex->associated_person ?? ($ex->associated_suspect ?? '')));
            }

            $sDbId = $s['id'] ?? null;
            if (!empty($exSuspectId) && !empty($sDbId)) {
                if ((int)$exSuspectId !== (int)$sDbId) {
                    if (empty($exAssoc) || (!str_contains($exAssoc, strtolower($sName)) && !str_contains($exAssoc, 'all suspect'))) {
                        return false;
                    }
                } else {
                    return true;
                }
            }

            if (!empty($exAssoc)) {
                if (str_contains($exAssoc, 'all suspect')) {
                    return true;
                }
                if ($suspectNamesMatch($exAssoc, $sName)) {
                    return true;
                }
                return false;
            }

            if ($suspectsCount <= 1) {
                return true;
            }

            if (!empty($c->id)) {
                $sExhibits = $c->getExhibitsForSuspect(
                    $sName,
                    $s['id'] ?? null,
                    $s['associate_id'] ?? null,
                    $s['role'] ?? ($s['association_role'] ?? null)
                );

                if (!empty($sExhibits)) {
                    foreach ($sExhibits as $se) {
                        if ($checkIsDrugExhibit((array)$se)) {
                            return true;
                        }
                    }
                }
            }

            return false;
        };

        foreach ($cases as $c) {
            $docket = is_array($c->docket_data) ? $c->docket_data : (json_decode($c->docket_data ?? '[]', true) ?: []);
            $seizures = $docket['seizures'] ?? [];
            $suspectsList = $docket['suspects'] ?? [];

            $isDrugCase = false;
            $caseCat = strtolower($c->exhibit_category ?? '');
            $caseSeizure = strtolower($c->seizure_name ?? '');
            $caseOffense = strtolower($c->offense_description ?? '');

            foreach ($drugKeywords as $kw) {
                if (str_contains($caseCat, $kw) || str_contains($caseSeizure, $kw) || str_contains($caseOffense, $kw)) {
                    $isDrugCase = true;
                    break;
                }
            }

            if (!$isDrugCase && !empty($seizures)) {
                foreach ($seizures as $sz) {
                    $szCat = strtolower($sz['category'] ?? '');
                    $szName = strtolower($sz['seizure_name'] ?? ($sz['commodity_name'] ?? ''));
                    $szClass = strtolower($sz['drug_classification'] ?? '');
                    foreach ($drugKeywords as $kw) {
                        if (str_contains($szCat, $kw) || str_contains($szName, $kw) || str_contains($szClass, $kw)) {
                            $isDrugCase = true;
                            break 2;
                        }
                    }
                }
            }

            if (empty($suspectsList)) {
                $suspectsList = [[
                    'suspect_name' => $c->suspect_name ?? 'Unknown Suspect',
                    'suspect_alias' => $c->suspect_alias ?? 'N/A',
                    'ghana_card' => $c->ghana_card ?? 'N/A',
                    'gender' => $c->gender ?? 'Male',
                    'age' => $c->age ?? 'N/A',
                    'nationality' => $c->nationality ?? 'Ghanaian',
                    'phone' => $c->phone ?? 'N/A',
                    'address' => $c->address ?? 'N/A',
                    'role' => 'Primary Suspect',
                    'mugshot_front' => $c->mugshot_front ?? null,
                ]];
            }

            if ($isDrugCase) {
                // Filter seizures to drug exhibits only
                $drugExhibits = [];
                if (!empty($seizures)) {
                    foreach ($seizures as $sz) {
                        $szCat = strtolower($sz['category'] ?? ($sz['exhibit_category'] ?? ''));
                        $szName = strtolower($sz['seizure_name'] ?? ($sz['commodity_name'] ?? ''));
                        $szClass = strtolower($sz['drug_classification'] ?? '');
                        $szDrug = strtolower($sz['seizure_name_drug'] ?? ($sz['substance_identification'] ?? ''));

                        $isDrugSz = !empty($szDrug) && $isStrictlyDrugName($szDrug);
                        if (!$isDrugSz) {
                            foreach ($drugKeywords as $kw) {
                                if (str_contains($szCat, $kw) || str_contains($szName, $kw) || str_contains($szClass, $kw)) {
                                    $isDrugSz = true;
                                    break;
                                }
                            }
                        }

                        if ($isDrugSz && (empty($szDrug) || !$isStrictlyDrugName($szDrug))) {
                            $isNonDrugName = false;
                            foreach ($nonDrugKeywords as $nk) {
                                if (str_contains($szName, $nk)) {
                                    $isNonDrugName = true;
                                    break;
                                }
                            }
                            if ($isNonDrugName) {
                                $hasDrugKwInName = false;
                                foreach ($drugKeywords as $dk) {
                                    if (str_contains($szName, $dk)) {
                                        $hasDrugKwInName = true;
                                        break;
                                    }
                                }
                                if (!$hasDrugKwInName) {
                                    $isDrugSz = false;
                                }
                            }
                        }

                        if ($isDrugSz) {
                            $drugExhibits[] = $sz;
                        }
                    }
                }

                if (empty($drugExhibits)) {
                    $caseDrugName = $c->seizure_name_drug ?? ($c->confirmatory_substance_identification ?? null);
                    if (empty($caseDrugName) && $isStrictlyDrugName($c->seizure_name ?? null)) {
                        $caseDrugName = $c->seizure_name;
                    }
                    if (!empty($caseDrugName) && $isStrictlyDrugName($caseDrugName)) {
                        $drugExhibits[] = [
                            'seizure_name' => $caseDrugName,
                            'category' => $c->exhibit_category ?? 'Narcotics & Controlled Substances',
                            'seizure_weight' => $c->seizure_weight ?? null,
                            'seizure_unit' => $c->seizure_unit ?? 'kg',
                            'associated_person' => null,
                        ];
                    }
                }

                if (!empty($drugExhibits)) {
                    if (count($drugExhibits) === 1) {
                        $ex = $drugExhibits[0];
                        $drugName = $resolveDrugName($ex, $c, $docket);

                        $drugCategory = $ex['drug_classification'] ?? ($ex['category'] ?? ($c->exhibit_category ?? 'Narcotics & Controlled Substances'));
                        $drugWeight = $ex['seizure_weight'] ?? ($c->seizure_weight ?? null);
                        $drugUnit = $ex['seizure_unit'] ?? ($c->seizure_unit ?? 'kg');
                        $quantityStr = !empty($drugWeight) ? "{$drugWeight} {$drugUnit}" : ($ex['seizure_quantity'] ?? '1 Batch / Package');

                        $labExam = $docket['lab_examination'] ?? [];
                        $presumptive = $ex['presumptive_result'] ?? ($labExam['presumptive_result'] ?? ($c->presumptive_test_result ?? 'Positive (Scott / Marquis Reagent)'));
                        $confirmatory = $ex['confirmatory_result'] ?? ($labExam['confirmatory_result'] ?? ($c->confirmatory_test_result ?? 'YES'));
                        $substanceId = $ex['substance_identification'] ?? ($labExam['substance_identification'] ?? ($c->confirmatory_substance_identification ?? $drugName));

                        if (!$isStrictlyDrugName($substanceId)) {
                            $substanceId = $drugName;
                        }

                        $groupedSuspects = [];
                        foreach ($suspectsList as $sIdx => $s) {
                            $sName = $s['suspect_name'] ?? ($c->suspect_name ?? 'Unnamed Suspect');
                            if (empty(trim($sName))) continue;

                            if (!$isSuspectLinkedToExhibit($s, $ex, $c, count($suspectsList))) {
                                continue;
                            }

                            $rawMug = $s['mugshot_front'] ?? ($c->mugshot_front ?? null);
                            $genderVal = $s['gender'] ?? ($c->gender ?? 'Male');
                            $resolvedMug = $resolveMugshot($rawMug, $genderVal, $sIdx);

                            $groupedSuspects[] = [
                                'suspect_name'     => $sName,
                                'suspect_alias'    => $s['suspect_alias'] ?? ($c->suspect_alias ?? 'N/A'),
                                'ghana_card'       => $s['ghana_card'] ?? ($c->ghana_card ?? 'GHA-8820491-0'),
                                'gender'           => $genderVal,
                                'age'              => $s['age'] ?? ($c->age ?? 'N/A'),
                                'nationality'      => $s['nationality'] ?? ($c->nationality ?? 'Ghanaian'),
                                'phone'            => $s['phone'] ?? ($c->phone ?? 'N/A'),
                                'address'          => $s['address'] ?? ($c->address ?? 'Accra, Ghana'),
                                'role'             => $s['role'] ?? ($s['association_role'] ?? ($sIdx === 0 ? 'Primary Suspect' : 'Co-Suspect')),
                                'mugshot_front'    => $resolvedMug,
                                'holding_facility' => $s['holding_facility'] ?? ($c->holding_facility ?? 'NACOC Central Custody Facility'),
                                'arrest_date'      => $s['date_of_arrest'] ?? ($c->arrest_date ?? ($c->incident_date ? date('Y-m-d', strtotime((string)$c->incident_date)) : date('Y-m-d'))),
                            ];
                        }

                        if (!empty($groupedSuspects)) {
                            $drugRecords->push([
                                'id'               => $c->id,
                                'case_number'      => $c->case_number,
                                'laboratory_number'=> $c->laboratory_number ?? 'Lab No. 12/FS03/2026',
                                'drug_name'        => $drugName,
                                'drug_category'    => $drugCategory,
                                'quantity_str'     => $quantityStr,
                                'presumptive'      => $presumptive,
                                'confirmatory'     => $confirmatory,
                                'detected'         => $substanceId,
                                'raid_location'    => $c->raid_location ?? 'Accra Regional Sector',
                                'status'           => $c->status ?? 'Approved',
                                'case_status'      => $c->case_status ?? 'Under Investigation',
                                'submitting_officer'=> $c->submitting_officer ?? ($c->commanding_officer ?? 'Det. Insp. K. Mensah'),
                                'analyst'          => $labExam['analyst'] ?? 'Senior Forensic Chemist (GC-MS Specialist)',
                                'suspects'         => $groupedSuspects,
                            ]);
                        }
                    } else {
                        foreach ($drugExhibits as $exIdx => $ex) {
                            $linkedSuspects = [];

                            foreach ($suspectsList as $sIdx => $s) {
                                $sName = $s['suspect_name'] ?? ($c->suspect_name ?? 'Unnamed Suspect');
                                if (empty(trim($sName))) continue;

                                if ($isSuspectLinkedToExhibit($s, $ex, $c, count($suspectsList))) {
                                    $rawMug = $s['mugshot_front'] ?? ($c->mugshot_front ?? null);
                                    $genderVal = $s['gender'] ?? ($c->gender ?? 'Male');
                                    $resolvedMug = $resolveMugshot($rawMug, $genderVal, $sIdx);

                                    $linkedSuspects[] = [
                                        'suspect_name'     => $sName,
                                        'suspect_alias'    => $s['suspect_alias'] ?? ($c->suspect_alias ?? 'N/A'),
                                        'ghana_card'       => $s['ghana_card'] ?? ($c->ghana_card ?? 'GHA-8820491-0'),
                                        'gender'           => $genderVal,
                                        'age'              => $s['age'] ?? ($c->age ?? 'N/A'),
                                        'nationality'      => $s['nationality'] ?? ($c->nationality ?? 'Ghanaian'),
                                        'phone'            => $s['phone'] ?? ($c->phone ?? 'N/A'),
                                        'address'          => $s['address'] ?? ($c->address ?? 'Accra, Ghana'),
                                        'role'             => $s['role'] ?? ($s['association_role'] ?? ($sIdx === 0 ? 'Primary Suspect' : 'Co-Suspect')),
                                        'mugshot_front'    => $resolvedMug,
                                        'holding_facility' => $s['holding_facility'] ?? ($c->holding_facility ?? 'NACOC Central Custody Facility'),
                                        'arrest_date'      => $s['date_of_arrest'] ?? ($c->arrest_date ?? ($c->incident_date ? date('Y-m-d', strtotime((string)$c->incident_date)) : date('Y-m-d'))),
                                    ];
                                }
                            }

                            if (!empty($linkedSuspects)) {
                                $drugName = $resolveDrugName($ex, $c, $docket);

                                $drugCategory = $ex['drug_classification'] ?? ($ex['category'] ?? ($c->exhibit_category ?? 'Narcotics & Controlled Substances'));
                                $drugWeight = $ex['seizure_weight'] ?? ($c->seizure_weight ?? null);
                                $drugUnit = $ex['seizure_unit'] ?? ($c->seizure_unit ?? 'kg');
                                $quantityStr = !empty($drugWeight) ? "{$drugWeight} {$drugUnit}" : ($ex['seizure_quantity'] ?? '1 Batch / Package');

                                $labExam = $docket['lab_examination'] ?? [];
                                $presumptive = $ex['presumptive_result'] ?? ($labExam['presumptive_result'] ?? ($c->presumptive_test_result ?? 'Positive (Scott / Marquis Reagent)'));
                                $confirmatory = $ex['confirmatory_result'] ?? ($labExam['confirmatory_result'] ?? ($c->confirmatory_test_result ?? 'YES'));
                                $substanceId = $ex['substance_identification'] ?? ($labExam['substance_identification'] ?? ($c->confirmatory_substance_identification ?? $drugName));

                                if (!$isStrictlyDrugName($substanceId)) {
                                    $substanceId = $drugName;
                                }

                                $drugRecords->push([
                                    'id'               => $c->id,
                                    'case_number'      => $c->case_number,
                                    'laboratory_number'=> $c->laboratory_number ?? 'Lab No. 12/FS03/2026',
                                    'drug_name'        => $drugName,
                                    'drug_category'    => $drugCategory,
                                    'quantity_str'     => $quantityStr,
                                    'presumptive'      => $presumptive,
                                    'confirmatory'     => $confirmatory,
                                    'detected'         => $substanceId,
                                    'raid_location'    => $c->raid_location ?? 'Accra Regional Sector',
                                    'status'           => $c->status ?? 'Approved',
                                    'case_status'      => $c->case_status ?? 'Under Investigation',
                                    'submitting_officer'=> $c->submitting_officer ?? ($c->commanding_officer ?? 'Det. Insp. K. Mensah'),
                                    'analyst'          => $labExam['analyst'] ?? 'Senior Forensic Chemist (GC-MS Specialist)',
                                    'suspects'         => $linkedSuspects,
                                ]);
                            }
                        }
                    }
                }
            }
        }

        return view('Lab_Analysis.lab_result', compact('drugRecords'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  GSA Report File Upload
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Store one or more GSA certificate files for a specific suspect.
     *
     * POST /Lab_Analysis/gsa-reports/store
     * Fields: case_suspect_id, multiple_suspect_case_id, gsa_report_images[], lab_number, case_number, notes
     */
    public function storeGsaReports(\Illuminate\Http\Request $request, $id = null)
    {
        $request->validate([
            'case_suspect_id'          => 'nullable|integer',
            'suspect_name'             => 'nullable|string|max:255',
            'multiple_suspect_case_id' => 'nullable|integer',
            'gsa_report_images'        => 'required|array|min:1',
            'gsa_report_images.*'      => 'required|file|max:10240|mimes:jpg,jpeg,png,webp,pdf,docx,csv',
        ]);

        // ── Resolve the parent multi-suspect case ──────────────────────────
        $caseNumber = $request->input('case_number') ?: $id;
        $multiCase  = null;
        if ($request->filled('multiple_suspect_case_id')) {
            $multiCase = \App\Models\MultipleSuspectCase::find($request->input('multiple_suspect_case_id'));
            // Guard against an id that belongs to a different case number
            if ($multiCase && $caseNumber && $multiCase->case_number !== (string) $caseNumber) {
                $multiCase = null;
            }
        }
        if (!$multiCase && $caseNumber) {
            $multiCase = \App\Models\MultipleSuspectCase::where('case_number', (string) $caseNumber)->first();
        }
        if (!$multiCase) {
            return response()->json([
                'success' => false,
                'message' => 'GSA reports can only be attached to a registered multi-suspect case. No matching case record was found.',
            ], 422);
        }

        // ── Resolve the suspect this report belongs to ─────────────────────
        $caseSuspects = \App\Models\CaseSuspect::where('multiple_suspect_case_id', $multiCase->id)
            ->orderBy('suspect_number')
            ->get();

        $suspect = null;
        if ($request->filled('case_suspect_id')) {
            $suspect = $caseSuspects->firstWhere('id', (int) $request->input('case_suspect_id'));
        }
        if (!$suspect && $request->filled('suspect_name')) {
            $wanted  = strtolower(preg_replace('/\s+/', ' ', trim($request->input('suspect_name'))));
            $suspect = $caseSuspects->first(function ($s) use ($wanted) {
                return strtolower(preg_replace('/\s+/', ' ', trim((string) $s->suspect_name))) === $wanted;
            });
        }
        if (!$suspect) {
            $suspect = $caseSuspects->firstWhere('role', 'Primary Suspect') ?: $caseSuspects->first();
        }
        if (!$suspect) {
            return response()->json([
                'success' => false,
                'message' => 'No suspect record is registered for this case, so the GSA report cannot be attached.',
            ], 422);
        }

        $saved  = [];
        $folder = 'gsa_reports/' . now()->format('Y/m');

        foreach ($request->file('gsa_report_images') as $file) {
            $ext      = strtolower($file->getClientOriginalExtension());
            $stored   = \Illuminate\Support\Str::uuid() . '.' . $ext;
            $path     = $file->storeAs($folder, $stored, 'public');

            $record = \App\Models\SuspectGsaReport::create([
                'case_suspect_id'          => $suspect->id,
                'multiple_suspect_case_id' => $multiCase->id,
                'uploaded_by'              => auth()->id(),
                'original_filename'        => $file->getClientOriginalName(),
                'stored_filename'          => $stored,
                'file_path'                => $path,
                'file_type'                => $file->getMimeType(),
                'file_extension'           => $ext,
                'file_size'                => $file->getSize(),
                'lab_number'               => $request->input('lab_number'),
                'case_number'              => $request->input('case_number'),
                'notes'                    => $request->input('notes'),
                'status'                   => 'active',
            ]);

            $saved[] = [
                'id'                => $record->id,
                'original_filename' => $record->original_filename,
                'file_extension'    => $record->file_extension,
                'file_size_label'   => $record->file_size_label,
                'url'               => $record->url,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => count($saved) . ' GSA report file(s) saved successfully.',
            'files'   => $saved,
        ]);
    }
}
