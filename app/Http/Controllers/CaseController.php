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
                'marital_status'   => $validated['marital_status'] ?? 'Single',
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
            'laboratory_number'        => $request->input('laboratory_number') ?? ($request->input('lab_number') ?? ('Lab No.' . rand(10, 99) . '/FS' . date('m') . '/' . date('Y'))),
            'confirmatory_test_result' => $validated['confirmatory_test_result'] ?? ($request->input('confirmatory_test_result') ?? 'NO'),
            'sample_weight_description'=> $request->input('sample_weight_description') ?? null,
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
                ? $suspect['marital_status'] : 'Single';

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
            if (!isset($docketData['lab_examination'])) {
                $docketData['lab_examination'] = [];
            }

            $labNum = $request->input('laboratory_number') ?: ($request->input('lab_number') ?: ($case->laboratory_number ?: ($docketData['lab_examination']['laboratory_number'] ?? null)));
            if ($labNum) {
                $docketData['lab_examination']['laboratory_number'] = $labNum;
                $case->laboratory_number = $labNum;
            }

            $labExamRef = $request->input('lab_exam_reference') ?: ($docketData['lab_examination']['exam_reference'] ?? null);
            if ($labExamRef) {
                $docketData['lab_examination']['exam_reference'] = $labExamRef;
            }

            $labExhNo = $request->input('lab_exhibit_number') ?: ($docketData['lab_examination']['exhibit_number'] ?? null);
            if ($labExhNo) {
                $docketData['lab_examination']['exhibit_number'] = $labExhNo;
            }

            $labUnitVal = $request->input('lab_unit') ?: ($docketData['lab_examination']['lab_unit'] ?? null);
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

            $presumptiveVal = $request->input('presumptive_result') ?? $request->input('presumptive_reaction_result_input') ?? $request->input('presumptive_test_result');
            if ($presumptiveVal !== null && $presumptiveVal !== '') {
                $docketData['lab_examination']['presumptive_result'] = $presumptiveVal;
                $case->presumptive_test_result = $presumptiveVal;
            }

            if ($request->has('confirmatory_test_result') || $request->has('confirmatory_result')) {
                $confirmatoryVal = $request->input('confirmatory_test_result') ?? $request->input('confirmatory_result');
                if ($confirmatoryVal !== null && $confirmatoryVal !== '') {
                    $docketData['lab_examination']['confirmatory_result'] = $confirmatoryVal;
                    $case->confirmatory_test_result = $confirmatoryVal;
                }
            }

            if (!isset($docketData['lab_examination']['sample_weight'])) {
                $docketData['lab_examination']['sample_weight'] = [];
            }
            $sampleDesc = $request->input('sample_weight_description');
            if ($sampleDesc !== null && $sampleDesc !== '') {
                $docketData['lab_examination']['sample_weight']['description'] = $sampleDesc;
                $case->sample_weight_description = $sampleDesc;
            }
            $sampleGross = $request->input('sample_weight_gross');
            if ($sampleGross !== null && $sampleGross !== '') {
                $docketData['lab_examination']['sample_weight']['gross_weight'] = $sampleGross;
                $case->sample_weight_gross = $sampleGross;
            }
            $sampleTaken = $request->input('sample_weight_taken');
            if ($sampleTaken !== null && $sampleTaken !== '') {
                $docketData['lab_examination']['sample_weight']['sample_taken'] = $sampleTaken;
                $case->sample_weight_taken = $sampleTaken;
            }
            $sampleReserved = $request->input('sample_weight_reserved');
            if ($sampleReserved !== null && $sampleReserved !== '') {
                $docketData['lab_examination']['sample_weight']['reserved_samples'] = $sampleReserved;
                $case->sample_weight_reserved = $sampleReserved;
            }

            $substanceId = $request->input('confirmatory_substance_identification');
            if ($substanceId !== null && $substanceId !== '') {
                $docketData['lab_examination']['substance_identification'] = $substanceId;
                $case->confirmatory_substance_identification = $substanceId;
            }

            if ($request->has('detected_substances')) {
                $detSubs = $request->input('detected_substances');
                if (!empty($detSubs)) {
                    $docketData['lab_examination']['detected_substances'] = $detSubs;
                    $case->detected_substances = $detSubs;
                }
            }

            $testMethods = $request->input('confirmatory_test_methods');
            if ($testMethods !== null && $testMethods !== '') {
                $docketData['lab_examination']['test_methods'] = $testMethods;
                $case->confirmatory_test_methods = $testMethods;
            }

            $specs = $request->input('confirmatory_specifications');
            if ($specs !== null && $specs !== '') {
                $docketData['lab_examination']['specifications'] = $specs;
                $case->confirmatory_specifications = $specs;
            }

            if (!empty($unitNotes)) {
                $docketData['lab_examination']['findings'] = $unitNotes;
                $case->laboratory_analytical_findings = $unitNotes;
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

            $currentCount = count($docketData['suspects']);
            foreach ($newPersons as $person) {
                if (empty($person['suspect_name'])) continue;

                $pobVal   = $person['place_of_birth'] ?? ($person['hometown'] ?? ($person['pob'] ?? null));
                $phoneVal = $person['phone_number'] ?? ($person['contact_phone'] ?? ($person['phone'] ?? null));
                $addrVal  = $person['residential_address'] ?? ($person['address'] ?? null);
                $roleVal  = $person['association_role'] ?? ($person['role'] ?? 'ASSOCIATE');

                $docketData['suspects'][] = [
                    'suspect_number'      => $currentCount,
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
                    'marital_status'      => $person['marital_status'] ?? 'Single',
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
                    'holding_facility'    => $person['holding_facility'] ?? null,
                    'notes'               => $person['notes'] ?? null,
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
            foreach ($newExhibits as $exhibit) {
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

                $docketData['seizures'][] = [
                    'category'          => $exhibit['category'] ?? ($exhibit['exhibit_category'] ?? 'General Exhibit'),
                    'seizure_name'      => $exName,
                    'seizure_quantity'  => $exhibit['seizure_quantity'] ?? ($exhibit['quantity'] ?? null),
                    'seizure_weight'    => $exhibit['seizure_weight'] ?? ($exhibit['weight'] ?? null),
                    'seizure_unit'      => $exhibit['seizure_unit'] ?? ($exhibit['unit'] ?? null),
                    'seizure_packaging' => $exhibit['seizure_packaging'] ?? ($exhibit['packaging'] ?? null),
                    'seizure_value'     => $exhibit['seizure_value'] ?? ($exhibit['value'] ?? null),
                    'photo'             => $mainPhoto,
                    'exhibit_photo'     => $mainPhoto,
                    'photos'            => $photosList,
                    'notes'             => $exhibit['notes'] ?? null,
                    'added_at'          => now()->toDateTimeString(),
                ];
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
            }

            if (!empty($exhibitSummaries)) {
                $newExText = implode(', ', array_filter($exhibitSummaries));
                if (!empty($case->exhibits_summary)) {
                    $case->exhibits_summary = $case->exhibits_summary . " | Associated: " . $newExText;
                } else {
                    $case->exhibits_summary = "Associated: " . $newExText;
                }
            }
        }

        $case->docket_data = $docketData;
        $case->save();

        $msg = "Successfully associated {$addedPersonsCount} person(s) and {$addedExhibitsCount} exhibit(s) with Case {$case->case_number}.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'case'    => $case,
            ]);
        }

        return redirect("/cases/{$case->case_number}/associate")->with('status', $msg);
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
        return view('Forensic_Head.show_review', compact('case'));
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
        return view('Forensic_Head.case_pdf', compact('case'));
    }
}
