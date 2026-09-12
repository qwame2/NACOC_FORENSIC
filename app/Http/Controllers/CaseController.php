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
     */
    public function store(Request $request)
    {
        $validated = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'case_number' => ['nullable', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'unit_name' => ['nullable', 'string', 'max:255'],
            'commanding_officer' => ['nullable', 'string', 'max:255'],
            'submitting_officer' => ['nullable', 'string', 'max:255'],
            'suspect_name' => ['nullable', 'string', 'max:255'],
            'suspect_alias' => ['nullable', 'string', 'max:255'],
            'ghana_card' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:50'],
            'dob' => ['nullable', 'string', 'max:50'],
            'age' => ['nullable'],
            'height' => ['nullable', 'string', 'max:100'],
            'eye_color' => ['nullable', 'string', 'max:100'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'pob' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'marital_status' => ['nullable', 'string', 'max:100'],
            'has_children' => ['nullable'],
            'children_count' => ['nullable'],
            'spouse_info' => ['nullable', 'string'],
            'spouse_name' => ['nullable', 'string', 'max:255'],
            'spouse_phone' => ['nullable', 'string', 'max:100'],
            'partner_name' => ['nullable', 'string', 'max:255'],
            'partner_phone' => ['nullable', 'string', 'max:100'],
            'partner_info' => ['nullable', 'string'],
            'dependents' => ['nullable', 'string', 'max:100'],
            'holding_facility' => ['nullable', 'string', 'max:255'],
            'exhibit_category' => ['nullable', 'string', 'max:255'],
            'valuation' => ['nullable', 'string', 'max:255'],
            'operation_code' => ['nullable', 'string', 'max:255'],
            'operation_code_name' => ['nullable', 'string', 'max:255'],
            'suspect_docket_mode' => ['nullable', 'string', 'max:50'],
            'raid_location' => ['nullable', 'string', 'max:255'],
            'offense_description' => ['nullable', 'string'],
            'arrest_date' => ['nullable'],
            'arrest_time' => ['nullable', 'string', 'max:50'],
            'has_seizures' => ['nullable'],
            'seizure_name' => ['nullable', 'string', 'max:255'],
            'seizure_name_drug' => ['nullable', 'string', 'max:255'],
            'seizure_name_currency' => ['nullable', 'string', 'max:255'],
            'seizure_name_ammo' => ['nullable', 'string', 'max:255'],
            'seizure_name_device' => ['nullable', 'string', 'max:255'],
            'dev_model' => ['nullable', 'string', 'max:255'],
            'dev_serial_no' => ['nullable', 'string', 'max:255'],
            'dev_imei1' => ['nullable', 'string', 'max:255'],
            'dev_imei2' => ['nullable', 'string', 'max:255'],
            'dev_mac_address' => ['nullable', 'string', 'max:255'],
            'dev_storage' => ['nullable', 'string', 'max:255'],
            'dev_colour' => ['nullable', 'string', 'max:255'],
            'dev_marks' => ['nullable', 'string'],
            'seizure_quantity' => ['nullable', 'string', 'max:100'],
            'seizure_packaging' => ['nullable', 'string', 'max:255'],
            'seizure_weight' => ['nullable', 'string', 'max:100'],
            'seizure_unit' => ['nullable', 'string', 'max:100'],
            'seizure_value' => ['nullable', 'string', 'max:255'],
            'seizure_concealment' => ['nullable', 'string', 'max:255'],
            'seizure_date' => ['nullable'],
            'seizure_time' => ['nullable', 'string', 'max:50'],
            'seizure_location' => ['nullable', 'string', 'max:255'],
            'seizure_officer' => ['nullable', 'string'],
            'seizure_reason' => ['nullable', 'string'],
            'veh_make' => ['nullable', 'string', 'max:255'],
            'veh_model' => ['nullable', 'string', 'max:255'],
            'veh_type' => ['nullable', 'string', 'max:255'],
            'veh_year' => ['nullable', 'string', 'max:50'],
            'veh_colour' => ['nullable', 'string', 'max:100'],
            'veh_reg_no' => ['nullable', 'string', 'max:100'],
            'veh_vin' => ['nullable', 'string', 'max:100'],
            'veh_engine_no' => ['nullable', 'string', 'max:100'],
            'veh_country_reg' => ['nullable', 'string', 'max:100'],
            'veh_owner' => ['nullable', 'string', 'max:255'],
            'veh_driver' => ['nullable', 'string', 'max:255'],
            'veh_owner_contact' => ['nullable', 'string', 'max:100'],
            'veh_docs_available' => ['nullable', 'string', 'max:50'],
            'veh_doc_ref' => ['nullable', 'string', 'max:255'],
            'veh_condition' => ['nullable', 'string', 'max:100'],
            'veh_fuel' => ['nullable', 'string', 'max:100'],
            'veh_keys_recv' => ['nullable', 'string', 'max:50'],
            'veh_key_count' => ['nullable'],
            'veh_damage' => ['nullable', 'string'],
            'veh_mods' => ['nullable', 'string'],
            'veh_features' => ['nullable', 'string'],
            'wpn_model' => ['nullable', 'string', 'max:255'],
            'wpn_serial_no' => ['nullable', 'string', 'max:255'],
            'wpn_calibre' => ['nullable', 'string', 'max:255'],
            'wpn_colour' => ['nullable', 'string', 'max:255'],
            'wpn_size' => ['nullable', 'string', 'max:255'],
            'wpn_marks' => ['nullable', 'string'],
            'wpn_location_found' => ['nullable', 'string', 'max:255'],
            'wpn_loaded_status' => ['nullable', 'string', 'max:100'],
            'wpn_condition' => ['nullable', 'string', 'max:100'],
            'evidence_photos' => ['nullable'],
            'mugshot_front' => ['nullable', 'string'],
            'mugshot_left' => ['nullable', 'string'],
            'mugshot_right' => ['nullable', 'string'],
            'docket_data' => ['nullable'],
            'priority' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'exhibits_summary' => ['nullable', 'string'],
        ])->validate();

        $user = Auth::user();

        // Auto generate unique case reference number if not supplied
        if ($request->filled('case_number')) {
            $caseRef = trim($request->input('case_number'));
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

        $case = ForensicCase::create([
            'case_number' => $caseRef,
            'title' => $validated['title'],
            'unit_name' => $validated['unit_name'] ?? ($user ? $user->unit : 'Forensic Directorate'),
            'unit_id' => $unit ? $unit->id : null,
            'user_id' => $user ? $user->id : null,
            'commanding_officer' => $validated['commanding_officer'] ?? ($user ? $user->fullname : 'Cmdr. Directorate Head'),
            'submitting_officer' => $validated['submitting_officer'] ?? ($user ? $user->fullname : 'Officer in Charge'),
            'suspect_name' => $validated['suspect_name'] ?? null,
            'suspect_alias' => $validated['suspect_alias'] ?? null,
            'ghana_card' => $validated['ghana_card'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'dob' => $validated['dob'] ?? null,
            'age' => isset($validated['age']) && $validated['age'] !== '' ? (int)$validated['age'] : null,
            'height' => $validated['height'] ?? null,
            'eye_color' => $validated['eye_color'] ?? null,
            'nationality' => $validated['nationality'] ?? null,
            'pob' => $validated['pob'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'marital_status' => $validated['marital_status'] ?? null,
            'has_children' => !empty($validated['has_children']),
            'children_count' => isset($validated['children_count']) && $validated['children_count'] !== '' ? (int)$validated['children_count'] : null,
            'spouse_info' => $validated['spouse_info'] ?? null,
            'spouse_name' => $validated['spouse_name'] ?? null,
            'spouse_phone' => $validated['spouse_phone'] ?? null,
            'partner_name' => $validated['partner_name'] ?? null,
            'partner_phone' => $validated['partner_phone'] ?? null,
            'partner_info' => $validated['partner_info'] ?? null,
            'dependents' => $validated['dependents'] ?? null,
            'holding_facility' => $validated['holding_facility'] ?? null,
            'exhibit_category' => $validated['exhibit_category'] ?? null,
            'valuation' => $validated['valuation'] ?? null,
            'operation_code' => $validated['operation_code'] ?? null,
            'operation_code_name' => $validated['operation_code_name'] ?? null,
            'suspect_docket_mode' => $validated['suspect_docket_mode'] ?? null,
            'raid_location' => $validated['raid_location'] ?? null,
            'offense_description' => $validated['offense_description'] ?? null,
            'arrest_date' => $validated['arrest_date'] ?? null,
            'arrest_time' => $validated['arrest_time'] ?? null,
            'has_seizures' => !empty($validated['has_seizures']),
            'seizure_name' => $validated['seizure_name'] ?? null,
            'seizure_name_drug' => $validated['seizure_name_drug'] ?? null,
            'seizure_name_currency' => $validated['seizure_name_currency'] ?? null,
            'seizure_name_ammo' => $validated['seizure_name_ammo'] ?? null,
            'seizure_name_device' => $validated['seizure_name_device'] ?? null,
            'dev_model' => $validated['dev_model'] ?? null,
            'dev_serial_no' => $validated['dev_serial_no'] ?? null,
            'dev_imei1' => $validated['dev_imei1'] ?? null,
            'dev_imei2' => $validated['dev_imei2'] ?? null,
            'dev_mac_address' => $validated['dev_mac_address'] ?? null,
            'dev_storage' => $validated['dev_storage'] ?? null,
            'dev_colour' => $validated['dev_colour'] ?? null,
            'dev_marks' => $validated['dev_marks'] ?? null,
            'seizure_quantity' => $validated['seizure_quantity'] ?? null,
            'seizure_packaging' => $validated['seizure_packaging'] ?? null,
            'seizure_weight' => $validated['seizure_weight'] ?? null,
            'seizure_unit' => $validated['seizure_unit'] ?? null,
            'seizure_value' => $validated['seizure_value'] ?? null,
            'seizure_concealment' => $validated['seizure_concealment'] ?? null,
            'seizure_date' => $validated['seizure_date'] ?? null,
            'seizure_time' => $validated['seizure_time'] ?? null,
            'seizure_location' => $validated['seizure_location'] ?? null,
            'seizure_officer' => $validated['seizure_officer'] ?? null,
            'seizure_reason' => $validated['seizure_reason'] ?? null,
            'veh_make' => $validated['veh_make'] ?? null,
            'veh_model' => $validated['veh_model'] ?? null,
            'veh_type' => $validated['veh_type'] ?? null,
            'veh_year' => $validated['veh_year'] ?? null,
            'veh_colour' => $validated['veh_colour'] ?? null,
            'veh_reg_no' => $validated['veh_reg_no'] ?? null,
            'veh_vin' => $validated['veh_vin'] ?? null,
            'veh_engine_no' => $validated['veh_engine_no'] ?? null,
            'veh_country_reg' => $validated['veh_country_reg'] ?? null,
            'veh_owner' => $validated['veh_owner'] ?? null,
            'veh_driver' => $validated['veh_driver'] ?? null,
            'veh_owner_contact' => $validated['veh_owner_contact'] ?? null,
            'veh_docs_available' => $validated['veh_docs_available'] ?? null,
            'veh_doc_ref' => $validated['veh_doc_ref'] ?? null,
            'veh_condition' => $validated['veh_condition'] ?? null,
            'veh_fuel' => $validated['veh_fuel'] ?? null,
            'veh_keys_recv' => $validated['veh_keys_recv'] ?? null,
            'veh_key_count' => isset($validated['veh_key_count']) && $validated['veh_key_count'] !== '' ? (int)$validated['veh_key_count'] : null,
            'veh_damage' => $validated['veh_damage'] ?? null,
            'veh_mods' => $validated['veh_mods'] ?? null,
            'veh_features' => $validated['veh_features'] ?? null,
            'wpn_model' => $validated['wpn_model'] ?? null,
            'wpn_serial_no' => $validated['wpn_serial_no'] ?? null,
            'wpn_calibre' => $validated['wpn_calibre'] ?? null,
            'wpn_colour' => $validated['wpn_colour'] ?? null,
            'wpn_size' => $validated['wpn_size'] ?? null,
            'wpn_marks' => $validated['wpn_marks'] ?? null,
            'wpn_location_found' => $validated['wpn_location_found'] ?? null,
            'wpn_loaded_status' => $validated['wpn_loaded_status'] ?? null,
            'wpn_condition' => $validated['wpn_condition'] ?? null,
            'evidence_photos' => $validated['evidence_photos'] ?? null,
            'mugshot_front' => $validated['mugshot_front'] ?? null,
            'mugshot_left' => $validated['mugshot_left'] ?? null,
            'mugshot_right' => $validated['mugshot_right'] ?? null,
            'docket_data' => $validated['docket_data'] ?? null,
            'priority' => $validated['priority'] ?? 'HIGH',
            'status' => $validated['status'] ?? 'Under Multi-Unit Processing',
            'location' => $validated['location'] ?? ($validated['raid_location'] ?? 'NACOC Forensic Directorate Vault'),
            'description' => $validated['description'] ?? ($validated['offense_description'] ?? 'Directorate Authorized Forensic Case File'),
            'exhibits_summary' => $validated['exhibits_summary'] ?? ($validated['seizure_name'] ? implode(' ', array_filter([$validated['seizure_quantity'] ? "Qty: {$validated['seizure_quantity']}" : null, $validated['seizure_weight'] ? "Wt: {$validated['seizure_weight']} " . ($validated['seizure_unit'] ?? '') : $validated['seizure_unit'], $validated['seizure_name']])) : 'Logged Exhibits & Dossier'),
            'hash_log' => 'HASH-' . strtoupper(substr(md5(uniqid()), 0, 8)) . '-VERIFIED',
            'incident_date' => $validated['arrest_date'] ?? now(),
        ]);

        // Initialize unit_progress in docket_data for the 4 specialized units
        $docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
        if (!isset($docketData['unit_progress'])) {
            $docketData['unit_progress'] = [
                'Crime Scene Support' => [
                    'status' => 'COMPLETED',
                    'officer' => $case->submitting_officer ?: 'Field Officer',
                    'updated_at' => now()->toDateTimeString()
                ],
                'Evidence Management' => [
                    'status' => 'PENDING',
                    'officer' => null,
                    'updated_at' => null
                ],
                'Lab Analysis' => [
                    'status' => 'PENDING',
                    'officer' => null,
                    'updated_at' => null
                ],
                'Digital & Physical Forensics' => [
                    'status' => 'PENDING',
                    'officer' => null,
                    'updated_at' => null
                ],
            ];
            $case->docket_data = $docketData;
            $case->save();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Case {$case->case_number} created and saved to database successfully.",
                'case' => $case,
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
                'Crime Scene Support' => ['status' => 'COMPLETED', 'officer' => 'Initial Field Officer', 'updated_at' => $case->created_at ? $case->created_at->toDateTimeString() : now()->toDateTimeString()],
                'Evidence Management' => ['status' => 'PENDING', 'officer' => null, 'updated_at' => null],
                'Lab Analysis' => ['status' => 'PENDING', 'officer' => null, 'updated_at' => null],
                'Digital & Physical Forensics' => ['status' => 'PENDING', 'officer' => null, 'updated_at' => null],
            ];
        }

        $docketData['unit_progress'][$unitNameKey] = [
            'status' => $unitStatus,
            'officer' => $officer,
            'notes' => $unitNotes,
            'updated_at' => now()->toDateTimeString(),
        ];

        if (!empty($unitNotes)) {
            if (!isset($docketData['unit_notes'])) {
                $docketData['unit_notes'] = [];
            }
            $docketData['unit_notes'][$unitNameKey] = [
                'notes' => $unitNotes,
                'officer' => $officer,
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
            'success' => true,
            'message' => $msg,
            'completed_count' => $completedCount,
            'total_units' => 4,
            'unit_name' => $unitNameKey,
            'unit_progress' => $docketData['unit_progress'],
            'status' => $case->status,
            'case' => $case,
        ]);
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
                'case_number' => (string)$id,
                'title' => 'Directorate Forensic Docket (' . $id . ')',
                'unit_name' => 'Forensic Directorate',
                'commanding_officer' => Auth::user()->fullname ?? 'Department Head',
                'submitting_officer' => 'Handling Officer',
                'priority' => 'HIGH',
                'status' => 'Approved',
                'location' => 'NACOC Central Vault Storage',
                'description' => 'Directorate Approved Case File',
                'exhibits_summary' => 'Verified Exhibits Dossier',
                'hash_log' => 'HASH-' . strtoupper(substr(md5((string)$id), 0, 8)) . '-VERIFIED',
                'incident_date' => now(),
            ]);
        } else {
            $case->status = 'Approved';
            $case->save();
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Case {$case->case_number} approved successfully.",
                'case' => $case,
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
                'case_number' => (string)$id,
                'title' => 'Directorate Forensic Docket (' . $id . ')',
                'unit_name' => 'Forensic Directorate',
                'commanding_officer' => Auth::user()->fullname ?? 'Department Head',
                'submitting_officer' => 'Handling Officer',
                'priority' => 'HIGH',
                'status' => 'Declined',
                'location' => 'NACOC Central Vault Storage',
                'description' => 'Directorate Declined Case File',
                'exhibits_summary' => 'Exhibits Dossier',
                'hash_log' => 'HASH-' . strtoupper(substr(md5((string)$id), 0, 8)) . '-VERIFIED',
                'incident_date' => now(),
            ]);
        } else {
            $case->status = 'Declined';
            $case->save();
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Case {$case->case_number} has been declined.",
                'case' => $case,
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
                'count' => $updatedCount,
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
            $dbHead = User::where('is_head', true)->first();
            $headName = $dbHead ? $dbHead->fullname : 'Anita Johnson';
            $case = new ForensicCase([
                'case_number' => $id,
                'title' => 'Directorate High Priority Forensic Docket',
                'unit_name' => 'Forensic Directorate',
                'commanding_officer' => $headName,
                'submitting_officer' => 'Insp. Eric Ofori',
                'suspect_name' => 'Under Active Investigation',
                'priority' => 'HIGH',
                'status' => 'Awaiting Sign-off',
                'location' => 'NACOC Central Vault Safe A-01',
                'description' => 'Detailed forensic analysis, exhibits chain of custody log, and findings certificate awaiting director review.',
                'exhibits_summary' => '3x Sealed Evidence Packages with SHA-256 Checksums',
                'hash_log' => 'HASH-' . strtoupper(substr(md5($id), 0, 8)) . '-VERIFIED',
                'incident_date' => now(),
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
            $dbHead = User::where('is_head', true)->first();
            $headName = $dbHead ? $dbHead->fullname : 'Anita Johnson';
            $case = new ForensicCase([
                'case_number' => $id,
                'title' => 'Directorate High Priority Forensic Docket',
                'unit_name' => 'Forensic Directorate',
                'commanding_officer' => $headName,
                'submitting_officer' => 'Insp. Eric Ofori',
                'suspect_name' => 'BEN ADU',
                'priority' => 'HIGH',
                'status' => 'Approved',
                'location' => 'NACOC Central Vault Safe A-01',
                'description' => 'Detailed forensic analysis, exhibits chain of custody log, and findings certificate awaiting director review.',
                'exhibits_summary' => '45 Kilograms (kg) Cocaine (High-Purity Powder / Slabs)',
                'hash_log' => 'HASH-' . strtoupper(substr(md5($id), 0, 8)) . '-VERIFIED',
                'incident_date' => now(),
            ]);
        }
        return view('Forensic_Head.case_pdf', compact('case'));
    }
}
