<?php

namespace App\Http\Controllers;

use App\Models\CaseSuspect;
use App\Models\DrugScreening;
use App\Models\User;
use Illuminate\Http\Request;

class DrugScreeningController extends Controller
{
    /**
     * Display a listing of all drug screening records.
     */
    public function index(Request $request)
    {
        $query = DrugScreening::with(['suspect.singleCase', 'suspect.multipleCase', 'suspect.exhibits'])
            ->orderBy('screening_date', 'desc')
            ->orderBy('id', 'desc');

        // Filter by result (Positive, Negative, Pending)
        if ($request->filled('result') && in_array(strtolower($request->result), ['positive', 'negative', 'pending'])) {
            $query->whereRaw('LOWER(result) = ?', [strtolower($request->result)]);
        }

        // Search by suspect name, case number, or purpose
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('purpose', 'LIKE', "%{$search}%")
                  ->orWhere('result', 'LIKE', "%{$search}%")
                  ->orWhere('substances_found', 'LIKE', "%{$search}%")
                  ->orWhere('notes', 'LIKE', "%{$search}%")
                  ->orWhereHas('suspect', function ($sq) use ($search) {
                      $sq->where('suspect_name', 'LIKE', "%{$search}%")
                        ->orWhere('suspect_alias', 'LIKE', "%{$search}%")
                        ->orWhere('ghana_card', 'LIKE', "%{$search}%")
                        ->orWhereHas('multipleCase', function ($cq) use ($search) {
                            $cq->where('case_number', 'LIKE', "%{$search}%")
                               ->orWhere('title', 'LIKE', "%{$search}%");
                        })
                        ->orWhereHas('singleCase', function ($cq) use ($search) {
                            $cq->where('case_number', 'LIKE', "%{$search}%")
                               ->orWhere('title', 'LIKE', "%{$search}%");
                        });
                  });
            });
        }
        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 1) $perPage = 15;
        if ($perPage > 100) $perPage = 100;

        $screenings = $query->paginate($perPage)->withQueryString();

        // Statistical metrics
        $allScreenings = DrugScreening::all();
        $totalCount    = $allScreenings->count();
        $positiveCount = $allScreenings->filter(fn($s) => strtolower(trim($s->result)) === 'positive')->count();
        $negativeCount = $allScreenings->filter(fn($s) => strtolower(trim($s->result)) === 'negative')->count();
        $pendingCount  = $allScreenings->filter(fn($s) => strtolower(trim($s->result)) === 'pending')->count();

        $dbHead = User::where('unit', 'LIKE', '%Lab%')->where('is_head', true)->first();
        $headName = $dbHead ? $dbHead->fullname : 'N/A';

        return view('Lab_Analysis.drug_screening.index', compact(
            'screenings',
            'totalCount',
            'positiveCount',
            'negativeCount',
            'pendingCount',
            'headName'
        ));
    }

    /**
     * Show the entry form for creating a new drug screening record.
     */
    public function create()
    {
        $suspects = $this->getAllSuspects();
        $eligibleSuspects = $suspects; // backward compatibility

        $dbHead = User::where('unit', 'LIKE', '%Lab%')->where('is_head', true)->first();
        $headName = $dbHead ? $dbHead->fullname : 'N/A';

        $screenedSuspects = DrugScreening::with(['suspect.singleCase', 'suspect.multipleCase'])->get()->keyBy('suspect_id');
        $screenedMap = [];
        foreach ($screenedSuspects as $sId => $ds) {
            $screenedMap[$sId] = [
                'screening_id'             => $ds->id,
                'suspect_id'               => $ds->suspect_id,
                'suspect_name'             => $ds->suspect->suspect_name ?? 'Suspect',
                'suspect_alias'            => $ds->suspect->suspect_alias ?? '',
                'case_number'              => $ds->suspect->case_number ?? ($ds->suspect->singleCase->case_number ?? ($ds->suspect->multipleCase->case_number ?? 'NCC-2026')),
                'purpose'                  => $ds->purpose,
                'screening_date'           => $ds->screening_date ? $ds->screening_date->format('Y-m-d') : '',
                'screening_date_formatted' => $ds->screening_date ? $ds->screening_date->format('d M Y') : 'N/A',
                'result'                   => $ds->result,
                'substances_found'         => $ds->substances_found ?? ($ds->result === 'Negative' ? 'No drug(s) detected' : ''),
                'notes'                    => $ds->notes ?? $ds->note ?? '',
                'note'                     => $ds->notes ?? $ds->note ?? '',
                'created_at'               => $ds->created_at ? $ds->created_at->format('d M Y, H:i') : 'N/A',
            ];
        }

        return view('Lab_Analysis.drug_screening.create', compact('suspects', 'eligibleSuspects', 'headName', 'screenedMap'));
    }

    /**
     * Store a newly created or updated drug screening record in the database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'screening_id'     => ['nullable', 'integer', 'exists:drug_screening,id'],
            'suspect_id'       => ['required', 'integer', 'exists:case_suspects,id'],
            'purpose'          => ['required', 'string', 'max:255'],
            'screening_date'   => ['required', 'date'],
            'result'           => ['required', 'string', \Illuminate\Validation\Rule::in(\App\Enums\ScreeningResult::values())],
            'substances_found' => ['nullable', 'string', 'max:255'],
            'notes'            => ['nullable', 'string', 'max:2000'],
            'note'             => ['nullable', 'string', 'max:2000'],
        ], [
            'suspect_id.required'     => 'Please select a suspect from the dropdown list.',
            'suspect_id.exists'       => 'The selected suspect could not be found in active records.',
            'purpose.required'        => 'The purpose of the screening is required.',
            'screening_date.required' => 'The date of screening is required.',
            'result.required'         => 'The screening outcome result is required.',
            'result.in'               => 'The result must be Positive, Negative, or Pending.',
        ]);

        $notesVal = $request->input('notes') ?? $request->input('note');
        $validated['notes'] = $notesVal;
        unset($validated['note']);

        if (strtolower(trim($validated['result'])) === 'negative') {
            $validated['substances_found'] = 'No drug(s) detected';
        }

        if ($request->filled('screening_id')) {
            $screening = DrugScreening::findOrFail($request->screening_id);
            $screening->update($validated);
            $actionVerb = 'updated';
        } else {
            // If suspect has already been screened, update their existing record
            $existing = DrugScreening::where('suspect_id', $validated['suspect_id'])->first();
            if ($existing) {
                $existing->update($validated);
                $screening = $existing;
                $actionVerb = 'updated';
            } else {
                $screening = DrugScreening::create($validated);
                $actionVerb = 'recorded';
            }
        }

        return redirect()->to('/Lab_Analysis/drug-screening')
            ->with('success', "Drug screening record for {$screening->suspect->suspect_name} {$actionVerb} successfully (Result: {$screening->result}).");
    }

    /**
     * Update the specified drug screening record directly.
     */
    public function update(Request $request, $id)
    {
        $screening = DrugScreening::findOrFail($id);

        $validated = $request->validate([
            'suspect_id'       => ['required', 'integer', 'exists:case_suspects,id'],
            'purpose'          => ['required', 'string', 'max:255'],
            'screening_date'   => ['required', 'date'],
            'result'           => ['required', 'string', \Illuminate\Validation\Rule::in(\App\Enums\ScreeningResult::values())],
            'substances_found' => ['nullable', 'string', 'max:255'],
            'notes'            => ['nullable', 'string', 'max:2000'],
            'note'             => ['nullable', 'string', 'max:2000'],
        ]);

        $notesVal = $request->input('notes') ?? $request->input('note');
        $validated['notes'] = $notesVal;
        unset($validated['note']);

        if (strtolower(trim($validated['result'])) === 'negative') {
            $validated['substances_found'] = 'No drug(s) detected';
        }

        $screening->update($validated);

        return redirect()->to('/Lab_Analysis/drug-screening')
            ->with('success', "Drug screening record for {$screening->suspect->suspect_name} updated successfully (Result: {$screening->result}).");
    }

    /**
     * Remove the specified drug screening record.
     */
    public function destroy($id)
    {
        $screening = DrugScreening::findOrFail($id);
        $name = $screening->suspect->suspect_name ?? 'Suspect';
        $screening->delete();

        return redirect()->to('/Lab_Analysis/drug-screening')
            ->with('success', "Drug screening record for {$name} was deleted successfully.");
    }

    /**
     * Gather all suspects in the database, treating multiple suspects as single individual entries.
     */
    public function getAllSuspects()
    {
        // 1. Synchronize single-suspect cases to case_suspects if not already present
        $singleCases = \App\Models\SingleSuspectCase::whereNotNull('suspect_name')
            ->where('suspect_name', '!=', '')
            ->get();

        foreach ($singleCases as $sc) {
            $caseSuspect = \App\Models\CaseSuspect::firstOrCreate(
                [
                    'single_suspect_case_id' => $sc->id,
                    'suspect_name'           => $sc->suspect_name,
                ],
                [
                    'suspect_number'   => 1,
                    'role'             => 'Primary Suspect',
                    'ghana_card'       => $sc->ghana_card,
                    'gender'           => $sc->gender ?? 'Male',
                    'dob'              => $sc->dob,
                    'age'              => $sc->age,
                    'holding_facility' => $sc->holding_facility,
                    'arrest_date'      => $sc->arrest_date,
                    'mugshot_front'    => $sc->mugshot_front,
                ]
            );

            if (empty($caseSuspect->mugshot_front) && !empty($sc->mugshot_front)) {
                $caseSuspect->update(['mugshot_front' => $sc->mugshot_front]);
            }
        }

        // 2. Fetch all individual suspects from case_suspects (both single and multiple suspect cases)
        return CaseSuspect::with(['singleCase', 'multipleCase'])
            ->whereNotNull('suspect_name')
            ->where('suspect_name', '!=', '')
            ->orderBy('suspect_name', 'asc')
            ->get();
    }

    /**
     * Alias for getAllSuspects.
     */
    public function getEligibleSuspects()
    {
        return $this->getAllSuspects();
    }

    /**
     * Get the active drug screening record for a suspect by suspect ID (JSON response).
     */
    public function getBySuspect($id)
    {
        $screening = DrugScreening::with(['suspect.multipleCase'])
            ->where('suspect_id', (int) $id)
            ->latest('screening_date')
            ->latest('id')
            ->first();

        if (!$screening) {
            return response()->json([
                'success'       => false,
                'has_screening' => false,
                'message'       => 'No drug screening record found for this suspect.',
            ], 404);
        }

        return response()->json([
            'success'       => true,
            'has_screening' => true,
            'screening'     => [
                'id'                       => $screening->id,
                'suspect_id'               => $screening->suspect_id,
                'suspect_name'             => $screening->suspect->suspect_name ?? 'Suspect',
                'suspect_alias'            => $screening->suspect->suspect_alias ?? '',
                'ghana_card'               => $screening->suspect->ghana_card ?? 'N/A',
                'case_number'              => $screening->suspect->multipleCase->case_number ?? 'NCC-2026',
                'holding_facility'         => $screening->suspect->holding_facility ?? config('forensics.facilities.default', 'NACOC Central Custody Facility'),
                'purpose'                  => $screening->purpose,
                'screening_date'           => $screening->screening_date ? $screening->screening_date->format('Y-m-d') : null,
                'screening_date_formatted' => $screening->screening_date ? $screening->screening_date->format('d M Y') : 'N/A',
                'result'                   => $screening->result,
                'substances_found'         => $screening->substances_found,
                'substance_list'           => $screening->substance_list,
                'notes'                    => $screening->notes ?? $screening->note ?? '',
                'note'                     => $screening->notes ?? $screening->note ?? '',
                'created_at'               => $screening->created_at ? $screening->created_at->format('d M Y, H:i') : 'N/A',
            ],
        ]);
    }

    /**
     * Show detailed drug screening record.
     */
    public function show(Request $request, $id)
    {
        $screening = DrugScreening::with(['suspect.multipleCase'])->findOrFail($id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'   => true,
                'screening' => [
                    'id'                       => $screening->id,
                    'suspect_id'               => $screening->suspect_id,
                    'suspect_name'             => $screening->suspect->suspect_name ?? 'Suspect',
                    'suspect_alias'            => $screening->suspect->suspect_alias ?? '',
                    'ghana_card'               => $screening->suspect->ghana_card ?? 'N/A',
                    'case_number'              => $screening->suspect->multipleCase->case_number ?? 'NCC-2026',
                    'holding_facility'         => $screening->suspect->holding_facility ?? config('forensics.facilities.default', 'NACOC Central Custody Facility'),
                    'purpose'                  => $screening->purpose,
                    'screening_date'           => $screening->screening_date ? $screening->screening_date->format('Y-m-d') : null,
                    'screening_date_formatted' => $screening->screening_date ? $screening->screening_date->format('d M Y') : 'N/A',
                    'result'                   => $screening->result,
                    'substances_found'         => $screening->substances_found,
                    'substance_list'           => $screening->substance_list,
                    'notes'                    => $screening->notes,
                    'created_at'               => $screening->created_at ? $screening->created_at->format('d M Y, H:i') : 'N/A',
                ],
            ]);
        }

        return redirect()->to('/Lab_Analysis/drug-screening?search=' . urlencode($screening->suspect->suspect_name ?? ''));
    }
}

