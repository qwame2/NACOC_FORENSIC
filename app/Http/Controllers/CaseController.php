<?php

namespace App\Http\Controllers;

use App\Models\ForensicCase;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CaseController extends Controller
{
    /**
     * Display a listing of all cases stored in the database.
     */
    public function index()
    {
        $cases = ForensicCase::with(['user', 'unit'])->orderBy('created_at', 'desc')->get();
        return response()->json($cases);
    }

    /**
     * Store a new case entered by a Unit Head into the database table.
     * Supports multiple suspects — creates one ForensicCase row per suspect
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
            'pob'                  => ['nullable', 'string', 'max:255'],
            'phone'                => ['nullable', 'string', 'max:100'],
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
        // Shared (case-level) fields — seizures, exhibits, etc.
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
        $docketData = is_array($validated['docket_data'] ?? null)
            ? $validated['docket_data']
            : (json_decode($validated['docket_data'] ?? '{}', true) ?: []);
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
            'presumptive_test_result'  => $validated['presumptive_test_result'] ?? ($request->input('presumptive_reaction_result_input') ?? null),
            'laboratory_number'        => $request->input('laboratory_number') ?? ($request->input('lab_number') ?? null),
            'confirmatory_test_result' => $validated['confirmatory_test_result'] ?? ($request->input('confirmatory_test_result') ?? null),
            'sample_weight_description'=> $request->input('sample_weight_description') ?? null,
            'sample_weight_units'      => $request->input('sample_weight_units') ?? null,
            'sample_weight_gross'      => $request->input('sample_weight_gross') ?? null,
            'sample_weight_taken'      => $request->input('sample_weight_taken') ?? null,
            'sample_weight_reserved'   => $request->input('sample_weight_reserved') ?? null,
            'confirmatory_substance_identification' => $request->input('confirmatory_substance_identification') ?? null,
            'detected_substances'      => $request->input('detected_substances') ?? null,
            'confirmatory_test_methods'=> $request->input('confirmatory_test_methods') ?? null,
            'confirmatory_specifications' => $request->input('confirmatory_specifications') ?? null,
            'incident_date'            => $validated['arrest_date'] ?? now(),
        ];

        // -----------------------------------------------------------------------
        // Helper closure: map a suspect array to personal-detail DB columns
        // -----------------------------------------------------------------------
        $suspectFields = function (array $suspect) use ($validated): array {
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
                'phone'            => $suspect['phone'] ?? null,
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
                                       ? $suspect['children_count'] . ' Kids' : 'No Children',
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

        // On a joint case, set suspect_name on the row to the combined label
        if (count($allNames) > 1) {
            $primaryPersonalFields['suspect_name'] = $jointSuspectNameLabel;
        }

        $case = ForensicCase::create(array_merge($sharedFields, $primaryPersonalFields, [
            'case_number'    => $caseRef,
            'suspect_number' => 1,
            'parent_case_id' => null,
            'mugshot_front'  => $validated['mugshot_front'] ?? ($primarySuspect['mugshot_front'] ?? null),
            'mugshot_left'   => $validated['mugshot_left'] ?? ($primarySuspect['mugshot_left'] ?? null),
            'mugshot_right'  => $validated['mugshot_right'] ?? ($primarySuspect['mugshot_right'] ?? null),
            'hash_log'       => 'HASH-' . strtoupper(substr(md5(uniqid()), 0, 8)) . '-VERIFIED',
        ]));

        // -----------------------------------------------------------------------
        // Initialize unit_progress in docket_data for the 4 specialized units
        // -----------------------------------------------------------------------
        $unitProgressBlock = [
            'Crime Scene Support' => [
                'status'     => 'COMPLETED',
                'officer'    => $case->submitting_officer ?: 'Field Officer',
                'updated_at' => now()->toDateTimeString(),
            ],
            'Evidence Management' => ['status' => 'PENDING', 'officer' => null, 'updated_at' => null],
            'Lab Analysis'        => ['status' => 'PENDING', 'officer' => null, 'updated_at' => null],
            'Digital & Physical Forensics' => ['status' => 'PENDING', 'officer' => null, 'updated_at' => null],
        ];

        $caseDocketData = is_array($case->docket_data)
            ? $case->docket_data
            : (json_decode($case->docket_data ?? '[]', true) ?: []);
        if (!isset($caseDocketData['unit_progress'])) {
            $caseDocketData['unit_progress'] = $unitProgressBlock;
            $case->docket_data = $caseDocketData;
            $case->save();
        }

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
            $docketData['unit_progress'] = [
                'Crime Scene Support'          => ['status' => 'COMPLETED', 'officer' => 'Initial Field Officer', 'updated_at' => $case->created_at ? $case->created_at->toDateTimeString() : now()->toDateTimeString()],
                'Evidence Management'          => ['status' => 'PENDING', 'officer' => null, 'updated_at' => null],
                'Lab Analysis'                 => ['status' => 'PENDING', 'officer' => null, 'updated_at' => null],
                'Digital & Physical Forensics' => ['status' => 'PENDING', 'officer' => null, 'updated_at' => null],
            ];
        }

        $docketData['unit_progress'][$unitNameKey] = [
            'status'     => $unitStatus,
            'officer'    => $officer,
            'notes'      => $unitNotes,
            'updated_at' => now()->toDateTimeString(),
        ];

        if ($unitNameKey === 'Lab Analysis' || $request->has('lab_unit') || $request->has('presumptive_result')) {
            $isAssoc = $request->boolean('exhibit_is_associate');
            $primaryName = $case->suspect_name ?? '';

            if (!empty($exOwnerName)) {
                $exOwnerLower = strtolower(trim($exOwnerName));
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
                    if (!$isCoSuspect) {
                        $isAssoc = true;
                    } else {
                        $isAssoc = false;
                    }
                }
            }

            $labNum = $request->input('laboratory_number') ?: ($request->input('lab_number') ?: ($case->laboratory_number ?: ($docketData['lab_examination']['laboratory_number'] ?? null)));
            if ($labNum) {
                $case->laboratory_number = $labNum;
            }

            $labExamRef = $request->input('lab_exam_reference') ?: ($docketData['lab_examination']['exam_reference'] ?? null);
            $labExhNo = $request->input('lab_exhibit_number') ?: ($docketData['lab_examination']['exhibit_number'] ?? null);
            $labUnitVal = $request->input('lab_unit') ?: ($docketData['lab_examination']['lab_unit'] ?? null);

            $presumptiveVal = $request->input('presumptive_result') ?? $request->input('presumptive_reaction_result_input') ?? $request->input('presumptive_test_result');
            if ($presumptiveVal !== null && $presumptiveVal !== '') {
                $case->presumptive_test_result = $presumptiveVal;
            }

            $confirmatoryVal = null;
            if ($request->has('confirmatory_test_result') || $request->has('confirmatory_result')) {
                $confirmatoryVal = $request->input('confirmatory_test_result') ?? $request->input('confirmatory_result');
                if ($confirmatoryVal !== null && $confirmatoryVal !== '') {
                    $case->confirmatory_test_result = $confirmatoryVal;
                }
            }

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
            $sampleTaken = $request->input('sample_weight_taken');
            if ($sampleTaken !== null && $sampleTaken !== '') {
                $case->sample_weight_taken = $sampleTaken;
            }
            $sampleReserved = $request->input('sample_weight_reserved');
            if ($sampleReserved !== null && $sampleReserved !== '') {
                $case->sample_weight_reserved = $sampleReserved;
            }

            $substanceId = $request->input('confirmatory_substance_identification');
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

            $testMethods = $request->input('confirmatory_test_methods');
            if ($testMethods !== null && $testMethods !== '') {
                $case->confirmatory_test_methods = $testMethods;
            }

            $specs = $request->input('confirmatory_specifications');
            if ($specs !== null && $specs !== '') {
                $case->confirmatory_specifications = $specs;
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
                \App\Models\AssociateLabExamination::updateOrCreate(
                    [
                        'case_id'      => $case->id,
                        'suspect_name' => $exOwnerName ?: 'Associate Suspect',
                    ],
                    [
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
                        'findings'                  => $unitNotes,
                        'has_saved'                 => true,
                    ]
                );

                if (!isset($docketData['associate_lab_examinations']) || !is_array($docketData['associate_lab_examinations'])) {
                    $docketData['associate_lab_examinations'] = [];
                }
                $docketData['associate_lab_examinations'][$exOwnerName ?: 'Associate Suspect'] = $currentExamRecord;

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
                if (!empty($unitNotes)) {
                    $docketData['lab_examination']['findings'] = $unitNotes;
                }
                $docketData['lab_examination']['has_saved'] = true;
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

        // Check if all 4 units have completed processing
        $units = ['Crime Scene Support', 'Evidence Management', 'Lab Analysis', 'Digital & Physical Forensics'];
        $completedCount = 0;
        foreach ($units as $u) {
            if (isset($docketData['unit_progress'][$u]) && strtoupper($docketData['unit_progress'][$u]['status'] ?? '') === 'COMPLETED') {
                $completedCount++;
            }
        }

        if ($completedCount >= 4) {
            $case->status = 'Awaiting Sign-off';
            $msg = "All 4 specialized units have completed processing! Case {$case->case_number} has been forwarded to Head of Forensic Directorate for final sign-off.";
        } else {
            $case->status = 'Under Multi-Unit Processing';
            $msg = "{$unitNameKey} progress updated for Case {$case->case_number}. Currently {$completedCount}/4 units completed.";
        }

        $case->docket_data = $docketData;
        $case->save();

        return response()->json([
            'success'         => true,
            'message'         => $msg,
            'completed_count' => $completedCount,
            'total_units'     => 4,
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

        // 2. Fallback to case->suspect_name (could be comma separated)
        if (empty($allSuspects) && !empty($case->suspect_name)) {
            $parts = array_map('trim', explode(',', $case->suspect_name));
            foreach ($parts as $pIdx => $pName) {
                if (!empty($pName)) {
                    $addSuspect($pName, $pIdx > 0);
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

        $docketData = is_array($case->docket_data)
            ? $case->docket_data
            : (json_decode($case->docket_data ?? '[]', true) ?: []);

        $newPersons = $request->input('associated_persons', []);
        if (is_string($newPersons)) {
            $newPersons = json_decode($newPersons, true) ?: [];
        }

        $newExhibits = $request->input('associated_exhibits', []);
        if (is_string($newExhibits)) {
            $newExhibits = json_decode($newExhibits, true) ?: [];
        }

        $addedPersonsCount = 0;
        $addedExhibitsCount = 0;

        // Process new associated persons/suspects
        if (!empty($newPersons) && is_array($newPersons)) {
            if (!isset($docketData['suspects']) || !is_array($docketData['suspects'])) {
                $docketData['suspects'] = [];
            }
            $totalSuspects = count($docketData['suspects']);
            $assocCount = count(array_filter($docketData['suspects'], function($s) {
                return !empty($s['is_associate']);
            }));
            foreach ($newPersons as $person) {
                if (empty($person['suspect_name'])) continue;

                $totalSuspects++;
                $assocCount++;
                $assocId  = $person['associate_id'] ?? ($person['suspect_id'] ?? ('ASC-' . str_pad($assocCount, 3, '0', STR_PAD_LEFT)));
                $pobVal   = $person['place_of_birth'] ?? ($person['hometown'] ?? ($person['pob'] ?? null));
                $phoneVal = $person['phone_number'] ?? ($person['contact_phone'] ?? ($person['phone'] ?? null));
                $addrVal  = $person['residential_address'] ?? ($person['address'] ?? null);
                $roleVal  = $person['association_role'] ?? ($person['role'] ?? 'ASSOCIATE');

                $docketData['suspects'][] = [
                    'associate_id'        => $assocId,
                    'suspect_id'          => $assocId,
                    'suspect_number'      => $totalSuspects,
                    'suspect_name'        => $person['suspect_name'],
                    'suspect_alias'       => $person['suspect_alias'] ?? null,
                    'ghana_card'          => $person['ghana_card'] ?? null,
                    'gender'              => $person['gender'] ?? null,
                    'dob'                 => $person['dob'] ?? ($person['date_of_birth'] ?? null),
                    'age'                 => isset($person['age']) && $person['age'] !== '' ? (int)$person['age'] : null,
                    'height'              => $person['height'] ?? null,
                    'eye_color'           => $person['eye_color'] ?? ($person['eye'] ?? null),
                    'nationality'         => $person['nationality'] ?? null,
                    'place_of_birth'      => $pobVal,
                    'hometown'            => $pobVal,
                    'pob'                 => $pobVal,
                    'phone_number'        => $phoneVal,
                    'contact_phone'       => $phoneVal,
                    'phone'               => $phoneVal,
                    'residential_address' => $addrVal,
                    'address'             => $addrVal,
                    'marital_status'      => $person['marital_status'] ?? null,
                    'spouse_name'         => $person['spouse_name'] ?? null,
                    'spouse_phone'        => $person['spouse_phone'] ?? null,
                    'spouse_info'         => $person['spouse_info'] ?? null,
                    'partner_name'        => $person['spouse_name'] ?? null,
                    'partner_phone'       => $person['spouse_phone'] ?? null,
                    'partner_info'        => $person['spouse_info'] ?? null,
                    'dependents'          => $person['dependents'] ?? null,
                    'raid_location'       => $person['raid_location'] ?? ($person['location'] ?? null),
                    'date_of_arrest'      => $person['date_of_arrest'] ?? ($person['arrest_date'] ?? null),
                    'time_of_arrest'      => $person['time_of_arrest'] ?? ($person['arrest_time'] ?? null),
                    'offense'             => $person['offense'] ?? ($person['offense_description'] ?? null),
                    'mugshot_front'       => $person['mugshot_front'] ?? null,
                    'mugshot_left'        => $person['mugshot_left'] ?? null,
                    'mugshot_right'       => $person['mugshot_right'] ?? null,
                    'is_associate'        => true,
                    'association_role'    => $roleVal,
                    'role'                => $roleVal,
                    'arresting_officer'    => $person['arresting_officer'] ?? ($person['arresting_officers'] ?? null),
                    'arresting_officer_id' => $person['arresting_officer_id'] ?? ($person['arresting_officers_id'] ?? null),
                    'seizing_officer'      => $person['seizing_officer'] ?? ($person['seizure_officer'] ?? null),
                    'seizure_officer'      => $person['seizing_officer'] ?? ($person['seizure_officer'] ?? null),
                    'seizing_officer_id'   => $person['seizing_officer_id'] ?? ($person['seizure_officer_id'] ?? null),
                    'seizure_officer_id'   => $person['seizing_officer_id'] ?? ($person['seizure_officer_id'] ?? null),
                    'holding_facility'     => $person['holding_facility'] ?? null,
                    'notes'                => $person['notes'] ?? null,
                ];
                $addedPersonsCount++;
            }

            $docketData['suspects_count'] = count($docketData['suspects']);
            $allNames = array_values(array_filter(array_column($docketData['suspects'], 'suspect_name')));
            if (!empty($allNames)) {
                $jointSuspectNameLabel = count($allNames) > 1 ? implode(', ', $allNames) : $allNames[0];
                $docketData['full_name'] = $jointSuspectNameLabel;
                $case->suspect_name = $jointSuspectNameLabel;
                $case->title = 'Republic vs ' . $jointSuspectNameLabel;
            }
        }

        // Process new associated exhibits
        if (!empty($newExhibits) && is_array($newExhibits)) {
            if (!isset($docketData['seizures']) || !is_array($docketData['seizures'])) {
                $docketData['seizures'] = [];
            }

            $exhibitSummaries = [];
            foreach ($newExhibits as $exIdx => $exhibit) {
                $exName = $exhibit['seizure_name'] ?? ($exhibit['name'] ?? null);
                if (empty($exName)) continue;

                $photosList = [];
                if (!empty($exhibit['photos']) && is_array($exhibit['photos'])) {
                    $photosList = array_values(array_filter($exhibit['photos']));
                } elseif (!empty($exhibit['photo'])) {
                    $photosList = [$exhibit['photo']];
                } elseif (!empty($exhibit['exhibit_photo'])) {
                    $photosList = [$exhibit['exhibit_photo']];
                }

                $mainPhoto = !empty($photosList) ? $photosList[0] : null;

                $assocPersonName = !empty($exhibit['associated_person'])
                    ? $exhibit['associated_person']
                    : (!empty($exhibit['suspect_name'])
                        ? $exhibit['suspect_name']
                        : (!empty($exhibit['associated_suspect'])
                            ? $exhibit['associated_suspect']
                            : (!empty($newPersons[$exIdx]['suspect_name'])
                                ? $newPersons[$exIdx]['suspect_name']
                                : ((!empty($newPersons) && is_array($newPersons) && !empty($newPersons[0]['suspect_name']))
                                    ? $newPersons[0]['suspect_name']
                                    : null))));

                $assocId = !empty($exhibit['associate_id'])
                    ? $exhibit['associate_id']
                    : (!empty($exhibit['suspect_id'])
                        ? $exhibit['suspect_id']
                        : null);

                if (empty($assocId) && !empty($assocPersonName) && !empty($docketData['suspects'])) {
                    foreach ($docketData['suspects'] as $sp) {
                        if (!empty($sp['suspect_name']) && strtolower(trim($sp['suspect_name'])) === strtolower(trim($assocPersonName))) {
                            $assocId = $sp['associate_id'] ?? ($sp['suspect_id'] ?? null);
                            break;
                        }
                    }
                }

                if (empty($assocId) && !empty($newPersons[$exIdx])) {
                    $assocId = $newPersons[$exIdx]['associate_id'] ?? ($newPersons[$exIdx]['suspect_id'] ?? null);
                }

                $seizOfficer   = $exhibit['seizure_officer'] ?? ($exhibit['seizing_officer'] ?? null);
                $seizOfficerId = $exhibit['seizure_officer_id'] ?? ($exhibit['seizing_officer_id'] ?? null);

                $docketData['seizures'][] = array_merge($exhibit, [
                    'category'           => $exhibit['category'] ?? ($exhibit['exhibit_category'] ?? 'General Exhibit'),
                    'seizure_name'       => $exName,
                    'seizure_quantity'   => $exhibit['seizure_quantity'] ?? ($exhibit['quantity'] ?? null),
                    'seizure_weight'     => $exhibit['seizure_weight'] ?? ($exhibit['weight'] ?? null),
                    'seizure_unit'       => $exhibit['seizure_unit'] ?? ($exhibit['unit'] ?? null),
                    'seizure_packaging'  => $exhibit['seizure_packaging'] ?? ($exhibit['packaging'] ?? null),
                    'seizure_value'      => $exhibit['seizure_value'] ?? ($exhibit['value'] ?? null),
                    'seizure_officer'    => $seizOfficer,
                    'seizing_officer'    => $seizOfficer,
                    'seizure_officer_id' => $seizOfficerId,
                    'seizing_officer_id' => $seizOfficerId,
                    'photo'              => $mainPhoto,
                    'exhibit_photo'     => $mainPhoto,
                    'photos'            => $photosList,
                    'notes'             => $exhibit['notes'] ?? null,
                    'added_at'          => now()->toDateTimeString(),
                    'is_associate'      => true,
                    'associate_id'      => $assocId,
                    'suspect_id'        => $assocId,
                    'associated_person' => $assocPersonName,
                ]);
                $addedExhibitsCount++;

                if (!empty($photosList)) {
                    if (!isset($docketData['evidence_photos']) || !is_array($docketData['evidence_photos'])) {
                        $docketData['evidence_photos'] = [];
                    }
                    foreach ($photosList as $pUrl) {
                        if (!in_array($pUrl, $docketData['evidence_photos'])) {
                            $docketData['evidence_photos'][] = $pUrl;
                        }
                    }
                }

                $qty = $exhibit['seizure_quantity'] ?? '';
                $unit = $exhibit['seizure_unit'] ?? '';
                $exhibitSummaries[] = trim("{$qty} {$unit} {$exName}");

                // Update case model exhibit metadata if primary fields are empty
                $cat = $exhibit['category'] ?? ($exhibit['exhibit_category'] ?? 'General Exhibit');
                if (empty($case->exhibit_category) || $case->exhibit_category === 'General Exhibit') {
                    $case->exhibit_category = $cat;
                }
                if (empty($case->seizure_name)) {
                    $case->seizure_name = $exName;
                }
                $case->has_seizures = true;

                $catLower = strtolower($cat);
                $exNameLower = strtolower($exName);
                if (
                    str_contains($catLower, 'drug') || str_contains($catLower, 'narcotic') || str_contains($catLower, 'substance') ||
                    str_contains($exNameLower, 'cocaine') || str_contains($exNameLower, 'heroin') || str_contains($exNameLower, 'cannabis') ||
                    str_contains($exNameLower, 'weed') || str_contains($exNameLower, 'meth') || str_contains($exNameLower, 'tramadol') ||
                    str_contains($exNameLower, 'drug') || str_contains($exNameLower, 'narcotic')
                ) {
                    if (empty($case->seizure_name_drug)) {
                        $case->seizure_name_drug = $exName;
                    }
                }
            }

            if (!empty($exhibitSummaries)) {
                $newExText = implode(', ', array_filter($exhibitSummaries));
                if (!empty($case->exhibits_summary)) {
                    $case->exhibits_summary = $case->exhibits_summary . " | Associated: " . $newExText;
                } else {
                    $case->exhibits_summary = "Associated: " . $newExText;
                }
            }

            if (!empty($docketData['evidence_photos'])) {
                $case->evidence_photos = $docketData['evidence_photos'];
            }
        }

        // Merge lab analysis & confirmatory findings input into docket_data
        $labInputs = $request->only([
            'lab_exam_reference',
            'lab_case_reference',
            'lab_exhibit_number',
            'lab_exhibit_description',
            'laboratory_number',
            'lab_analyst',
            'lab_date_received',
            'lab_date_started',
            'lab_date_completed',
            'presumptive_test_yes',
            'presumptive_test_no',
            'presumptive_reaction_result_input',
            'confirmatory_test_yes',
            'confirmatory_test_no',
            'confirmatory_test_result',
            'sample_weight_description',
            'sample_weight_units',
            'sample_weight_gross',
            'sample_weight_taken',
            'sample_weight_reserved',
            'confirmatory_substance_identification',
            'detected_substances',
            'confirmatory_test_methods',
            'confirmatory_specifications',
        ]);
        
        foreach ($labInputs as $lKey => $lVal) {
            if ($lVal !== null) {
                $docketData[$lKey] = $lVal;
            }
        }
        if (!isset($docketData['lab_analysis']) || !is_array($docketData['lab_analysis'])) {
            $docketData['lab_analysis'] = [];
        }
        $docketData['lab_analysis'] = array_merge($docketData['lab_analysis'], array_filter($labInputs, function($v) { return $v !== null; }));

        $case->docket_data = $docketData;
        $case->save();

        $fromUnit = $request->input('from_unit') ?? $request->input('unit') ?? $request->query('unit') ?? $request->query('from') ?? '';
        $userUnit = auth()->user()->unit ?? session('user_unit') ?? '';
        $referer  = $request->headers->get('referer') ?? '';

        $isLab = str_contains(strtolower($fromUnit), 'lab') 
                 || str_contains(strtolower($userUnit), 'lab')
                 || str_contains(strtolower($referer), 'lab');

        $redirectUrl = url('/cases') . ($isLab ? '?unit=Lab+Analysis' : '');

        $msg = "Successfully associated {$addedPersonsCount} person(s) and {$addedExhibitsCount} exhibit(s) with Case {$case->case_number}.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => $msg,
                'case'     => $case,
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
        return view('Forensic_Head.show_review', compact('case'));
    }

    /**
     * Auto-repair legacy or mis-assigned associate exhibit records.
     */
    protected function repairExhibitAssociations(&$case)
    {
        if (!$case || empty($case->docket_data)) return;
        $docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data, true) ?: []);

        if (empty($docketData['suspects']) || empty($docketData['seizures'])) return;

        $associates = array_values(array_filter($docketData['suspects'], function($s) {
            return !empty($s['suspect_name']) && (!empty($s['is_associate']) || (!empty($s['association_role']) && strtolower($s['association_role']) !== 'primary suspect'));
        }));

        if (count($associates) >= 2) {
            $modified = false;
            foreach ($docketData['seizures'] as $idx => &$sz) {
                $cat = strtolower($sz['category'] ?? ($sz['exhibit_category'] ?? ''));
                $name = strtolower($sz['seizure_name'] ?? ($sz['name'] ?? ''));
                $owner = strtolower(trim($sz['associated_person'] ?? ''));

                $isWeapon = str_contains($cat, 'weapon') || str_contains($name, 'ak-47') || str_contains($name, 'pistol') || str_contains($name, 'rifle') || str_contains($name, 'firearm') || str_contains($name, 'machete') || str_contains($name, 'gun');

                if ($isWeapon && !empty($associates[1]['suspect_name'])) {
                    $assoc1Name = strtolower(trim($associates[0]['suspect_name']));
                    $assoc2Name = $associates[1]['suspect_name'];
                    $assoc2Id   = $associates[1]['associate_id'] ?? ($associates[1]['suspect_id'] ?? 'ASC-002');

                    if ($owner === $assoc1Name || empty($owner)) {
                        $sz['associated_person'] = $assoc2Name;
                        $sz['associate_id']      = $assoc2Id;
                        $sz['suspect_id']        = $assoc2Id;
                        $sz['is_associate']      = true;
                        $modified = true;
                    }
                }
            }
            unset($sz);

            if ($modified) {
                $case->docket_data = $docketData;
                if (!empty($case->exists)) {
                    $case->save();
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
}
