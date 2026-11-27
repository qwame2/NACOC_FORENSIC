<?php

namespace App\Http\Controllers;

use App\Models\CaseSuspect;
use App\Models\DrugScreening;
use App\Models\DrugScreeningMedication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DrugScreeningController extends Controller
{
    /**
     * Display a listing of all drug screening records.
     */
    public function index(Request $request)
    {
        $query = DrugScreening::with(['suspect.singleCase', 'suspect.multipleCase', 'suspect.exhibits', 'medications'])
            ->orderBy('screening_date', 'desc')
            ->orderBy('id', 'desc');

        // Filter by result (Positive, Negative, Pending)
        if ($request->filled('result') && in_array(strtolower($request->result), ['positive', 'negative', 'pending'])) {
            $query->whereRaw('LOWER(result) = ?', [strtolower($request->result)]);
        }

        // Filter by type (All, Suspects, Civilians)
        if ($request->filled('type')) {
            $type = strtolower(trim($request->type));
            if (in_array($type, ['suspects', 'suspect'])) {
                $query->whereNotNull('suspect_id');
            } elseif (in_array($type, ['civilians', 'civilian'])) {
                $query->whereNotNull('civilian_name');
            }
        }

        // Filter by period preset (General, Weekly, Monthly, Annually)
        if ($request->filled('period')) {
            $period = strtolower(trim($request->period));
            if (in_array($period, ['weekly', 'week'])) {
                $query->where('screening_date', '>=', now()->subDays(7)->startOfDay()->toDateString());
            } elseif (in_array($period, ['monthly', 'month'])) {
                $query->where('screening_date', '>=', now()->subDays(30)->startOfDay()->toDateString());
            } elseif (in_array($period, ['annually', 'annual', 'yearly', 'year'])) {
                $query->where('screening_date', '>=', now()->subDays(365)->startOfDay()->toDateString());
            }
        }

        // Filter by custom date bounds
        if ($request->filled('from')) {
            $query->where('screening_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('screening_date', '<=', $request->to);
        }

        // Filter by specific person (suspect or civilian)
        if ($request->filled('person')) {
            $person = trim($request->person);
            $query->where(function ($q) use ($person) {
                $q->where('civilian_name', 'LIKE', "%{$person}%")
                  ->orWhereHas('suspect', function ($sq) use ($person) {
                      $sq->where('suspect_name', 'LIKE', "%{$person}%")
                        ->orWhere('suspect_alias', 'LIKE', "%{$person}%");
                  });
            });
        }

        // Search by suspect/civilian name, case number, or purpose
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('purpose', 'LIKE', "%{$search}%")
                  ->orWhere('result', 'LIKE', "%{$search}%")
                  ->orWhere('substances_found', 'LIKE', "%{$search}%")
                  ->orWhere('notes', 'LIKE', "%{$search}%")
                  ->orWhere('civilian_name', 'LIKE', "%{$search}%")
                  ->orWhere('medication_summary', 'LIKE', "%{$search}%")
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

        $screenings = $query->paginate($perPage)->appends($request->except('poll'));

        // Statistical metrics
        $allScreenings  = DrugScreening::all();
        $totalCount     = $allScreenings->count();
        $positiveCount  = $allScreenings->filter(fn($s) => strtolower(trim($s->result)) === 'positive')->count();
        $negativeCount  = $allScreenings->filter(fn($s) => strtolower(trim($s->result)) === 'negative')->count();
        $pendingCount   = $allScreenings->filter(fn($s) => strtolower(trim($s->result)) === 'pending')->count();
        $suspectsCount  = $allScreenings->filter(fn($s) => !empty($s->suspect_id))->count();
        $civiliansCount = $allScreenings->filter(fn($s) => !empty($s->civilian_name))->count();

        // Distinct list of screened persons for personal report dropdown filter
        $personsQuery = DrugScreening::with(['suspect.singleCase', 'suspect.multipleCase']);
        if ($request->filled('type')) {
            $t = strtolower(trim($request->type));
            if (in_array($t, ['suspects', 'suspect'])) {
                $personsQuery->whereNotNull('suspect_id');
            } elseif (in_array($t, ['civilians', 'civilian'])) {
                $personsQuery->whereNotNull('civilian_name');
            }
        }
        if ($request->filled('result')) {
            $r = strtolower(trim($request->result));
            if ($r !== 'all') {
                $personsQuery->whereRaw('LOWER(result) = ?', [$r]);
            }
        }
        if ($request->filled('period')) {
            $period = strtolower(trim($request->period));
            if (in_array($period, ['weekly', 'week'])) {
                $personsQuery->where('screening_date', '>=', now()->subDays(7)->startOfDay()->toDateString());
            } elseif (in_array($period, ['monthly', 'month'])) {
                $personsQuery->where('screening_date', '>=', now()->subDays(30)->startOfDay()->toDateString());
            } elseif (in_array($period, ['annually', 'annual', 'yearly', 'year'])) {
                $personsQuery->where('screening_date', '>=', now()->subDays(365)->startOfDay()->toDateString());
            }
        }
        if ($request->filled('from')) {
            $personsQuery->where('screening_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $personsQuery->where('screening_date', '<=', $request->to);
        }
        $allScreenedPersons = $personsQuery
            ->orderBy('screening_date', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($s) {
                return [
                    'id'          => $s->id,
                    'person_name' => $s->person_name,
                    'type'        => $s->isCivilian() ? 'Civilian' : 'Suspect',
                    'case_ref'    => $s->case_reference,
                    'date'        => $s->screening_date ? $s->screening_date->format('d/m/Y') : '',
                    'result'      => $s->result,
                ];
            })
            ->unique('person_name')
            ->values();

        $dbHead = User::where('unit', 'LIKE', '%Lab%')->where('is_head', true)->first();
        $headName = $dbHead ? $dbHead->fullname : 'N/A';

        $stats = [
            'total'     => $totalCount,
            'positive'  => $positiveCount,
            'negative'  => $negativeCount,
            'pending'   => $pendingCount,
            'suspects'  => $suspectsCount,
            'civilians' => $civiliansCount,
        ];

        $sigRows = [];
        foreach ($screenings->items() as $sRow) {
            $sigRows[] = [
                $sRow->id,
                (string) ($sRow->result ?? ''),
                (string) ($sRow->substances_found ?? ''),
                (int) ((bool) $sRow->on_medication),
                (string) ($sRow->suspect?->mugshot_front_url ?? ''),
                optional($sRow->updated_at)->timestamp,
            ];
        }
        $initialSignature = md5((string) json_encode([
            'stats'   => $stats,
            'filters' => [
                (string) $request->input('search', ''),
                (string) $request->input('type', ''),
                (string) $request->input('result', ''),
                (string) $request->input('period', ''),
                (string) $request->input('from', ''),
                (string) $request->input('to', ''),
                $perPage,
                $screenings->currentPage(),
                $screenings->total(),
            ],
            'rows'    => $sigRows,
        ]));

        if ($request->boolean('poll')) {
            $html = view('Lab_Analysis.drug_screening.partials.table', compact(
                'screenings',
                'totalCount',
                'positiveCount',
                'negativeCount',
                'pendingCount',
                'suspectsCount',
                'civiliansCount',
                'headName',
                'allScreenedPersons'
            ))->render();

            return response()->json([
                'success'   => true,
                'html'      => $html,
                'stats'     => $stats,
                'signature' => $initialSignature,
                'total'     => method_exists($screenings, 'total') ? $screenings->total() : $screenings->count(),
            ]);
        }

        if ($request->ajax()) {
            if ($request->wantsJson()) {
                $html = view('Lab_Analysis.drug_screening.partials.table', compact(
                    'screenings',
                    'totalCount',
                    'positiveCount',
                    'negativeCount',
                    'pendingCount',
                    'suspectsCount',
                    'civiliansCount',
                    'headName',
                    'allScreenedPersons'
                ))->render();

                return response()->json([
                    'success'   => true,
                    'html'      => $html,
                    'stats'     => $stats,
                    'signature' => $initialSignature,
                    'total'     => method_exists($screenings, 'total') ? $screenings->total() : $screenings->count(),
                ]);
            }

            return view('Lab_Analysis.drug_screening.partials.table', compact(
                'screenings',
                'totalCount',
                'positiveCount',
                'negativeCount',
                'pendingCount',
                'suspectsCount',
                'civiliansCount',
                'headName',
                'allScreenedPersons'
            ));
        }

        return view('Lab_Analysis.drug_screening.index', compact(
            'screenings',
            'totalCount',
            'positiveCount',
            'negativeCount',
            'pendingCount',
            'suspectsCount',
            'civiliansCount',
            'headName',
            'allScreenedPersons',
            'initialSignature'
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

        $screenedSuspects = DrugScreening::whereNotNull('suspect_id')
            ->with(['suspect.singleCase', 'suspect.multipleCase', 'medications'])
            ->get()
            ->keyBy('suspect_id');

        $screenedMap = [];
        foreach ($screenedSuspects as $sId => $ds) {
            $screenedMap[$sId] = [
                'screening_id'             => $ds->id,
                'suspect_id'               => $ds->suspect_id,
                'suspect_name'             => $ds->suspect->suspect_name ?? 'Suspect',
                'suspect_alias'            => $ds->suspect->suspect_alias ?? '',
                'mugshot_url'              => $ds->suspect?->mugshot_front_url ?? \App\Services\CasePhotoResolver::resolveMugshotUrl($ds->suspect?->mugshot_front ?: ($ds->suspect?->singleCase?->mugshot_front ?: null)),
                'case_number'              => $ds->suspect->case_number ?? ($ds->suspect->singleCase->case_number ?? ($ds->suspect->multipleCase->case_number ?? ('NCC-' . date('Y')))),
                'purpose'                  => $ds->purpose,
                'screening_date'           => $ds->screening_date ? $ds->screening_date->format('Y-m-d') : '',
                'screening_date_formatted' => $ds->screening_date ? $ds->screening_date->format('d M Y') : 'N/A',
                'result'                   => $ds->result,
                'substances_found'         => $ds->substances_found ?? ($ds->result === 'Negative' ? 'No drug(s) detected' : ''),
                'notes'                    => $ds->notes ?? $ds->note ?? '',
                'note'                     => $ds->notes ?? $ds->note ?? '',
                'on_medication'            => (bool) $ds->on_medication,
                'medication_summary'       => $ds->medication_summary ?? '',
                'medications'              => $ds->medications->map(function ($m) {
                    return [
                        'id'                  => $m->id,
                        'medication_name'     => $m->medication_name,
                        'dosage'              => $m->dosage ?? '',
                        'form'                => $m->form ?? '',
                        'frequency'           => $m->frequency ?? '',
                        'route'               => $m->route ?? '',
                        'prescription_source' => $m->prescription_source ?? '',
                        'source_label'        => $m->source_label,
                        'date_started'        => $m->date_started ? $m->date_started->format('Y-m-d') : '',
                        'duration_ongoing'    => $m->duration_ongoing ?? '',
                        'last_dose_taken'     => $m->last_dose_taken ? $m->last_dose_taken->format('Y-m-d\TH:i') : '',
                    ];
                })->values()->toArray(),
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
        // If suspect_id was submitted as a civilian tag or text
        if ($request->filled('suspect_id') && !is_numeric($request->suspect_id)) {
            $raw = (string) $request->suspect_id;
            $civName = str_starts_with($raw, 'civilian:') ? substr($raw, 9) : $raw;
            $request->merge([
                'civilian_name' => trim($civName),
                'suspect_id' => null,
            ]);
        }

        $isOnMedication = $request->boolean('on_medication');

        $validationRules = [
            'screening_id'     => ['nullable', 'integer', 'exists:drug_screening,id'],
            'suspect_id'       => ['nullable', 'required_without:civilian_name', 'integer', 'exists:case_suspects,id'],
            'civilian_name'    => ['nullable', 'required_without:suspect_id', 'string', 'max:255'],
            'purpose'          => ['required', 'string', 'max:255'],
            'screening_date'   => ['required', 'date'],
            'result'           => ['required', 'string', \Illuminate\Validation\Rule::in(\App\Enums\ScreeningResult::values())],
            'substances_found' => ['nullable', 'string', 'max:255'],
            'notes'            => ['nullable', 'string', 'max:2000'],
            'note'             => ['nullable', 'string', 'max:2000'],
            'on_medication'    => ['nullable'],
        ];

        if ($isOnMedication) {
            $validationRules['medications'] = ['required', 'array', 'min:1'];
            $validationRules['medications.*.medication_name'] = ['required', 'string', 'max:255'];
            $validationRules['medications.*.dosage'] = ['nullable', 'string', 'max:100'];
            $validationRules['medications.*.form'] = ['nullable', 'string', 'max:100'];
            $validationRules['medications.*.frequency'] = ['nullable', 'string', 'max:100'];
            $validationRules['medications.*.route'] = ['nullable', 'string', 'max:100'];
            $validationRules['medications.*.prescription_source'] = ['nullable', 'string', 'in:doctor,otc,self_administered'];
            $validationRules['medications.*.date_started'] = ['nullable', 'date'];
            $validationRules['medications.*.duration_ongoing'] = ['nullable', 'string', 'max:100'];
            $validationRules['medications.*.last_dose_taken'] = ['nullable', 'date'];
        }

        $validationMessages = [
            'suspect_id.required_without'             => 'Please select a registered suspect or enter a civilian name.',
            'civilian_name.required_without'          => 'Please select a registered suspect or enter a civilian name.',
            'suspect_id.exists'                       => 'The selected suspect could not be found in active records.',
            'purpose.required'                        => 'The purpose of the screening is required.',
            'screening_date.required'                 => 'The date of screening is required.',
            'result.required'                         => 'The screening outcome result is required.',
            'result.in'                               => 'The result must be Positive, Negative, or Pending.',
            'medications.required'                    => 'Please provide at least one medication entry when "On Medication" is Yes.',
            'medications.min'                         => 'Please provide at least one medication entry when "On Medication" is Yes.',
            'medications.*.medication_name.required'  => 'Medication name is required for each entered medication record.',
            'medications.*.prescription_source.in'    => 'The prescription source must be Doctor, Over the Counter, or Self-Administered.',
        ];

        $validated = $request->validate($validationRules, $validationMessages);

        $notesVal = $request->input('notes') ?? $request->input('note');
        $validated['notes'] = $notesVal;
        unset($validated['note']);

        if (strtolower(trim($validated['result'])) === 'negative') {
            $validated['substances_found'] = 'No drug(s) detected';
        }

        if (!empty($validated['civilian_name'])) {
            $validated['civilian_name'] = trim($validated['civilian_name']);
            $validated['suspect_id'] = null;
        } else {
            $validated['civilian_name'] = null;
        }

        $actionVerb = 'recorded';

        $screening = DB::transaction(function () use ($request, $validated, $isOnMedication, &$actionVerb) {
            $dataToSave = [
                'suspect_id'       => $validated['suspect_id'] ?? null,
                'civilian_name'    => $validated['civilian_name'] ?? null,
                'purpose'          => $validated['purpose'],
                'screening_date'   => $validated['screening_date'],
                'result'           => $validated['result'],
                'substances_found' => $validated['substances_found'] ?? null,
                'notes'            => $validated['notes'] ?? null,
                'on_medication'    => $isOnMedication,
            ];

            if ($request->filled('screening_id')) {
                $record = DrugScreening::findOrFail($request->screening_id);
                $record->update($dataToSave);
                $actionVerb = 'updated';
            } else {
                // If suspect has already been screened, update their existing record
                $existing = null;
                if (!empty($dataToSave['suspect_id'])) {
                    $existing = DrugScreening::where('suspect_id', $dataToSave['suspect_id'])->first();
                }
                if ($existing) {
                    $existing->update($dataToSave);
                    $record = $existing;
                    $actionVerb = 'updated';
                } else {
                    $record = DrugScreening::create($dataToSave);
                    $actionVerb = 'recorded';
                }
            }

            if ($isOnMedication) {
                $medsInput = $request->input('medications', []);
                $validMeds = [];
                foreach ($medsInput as $m) {
                    $mName = trim($m['medication_name'] ?? '');
                    if ($mName === '') {
                        continue;
                    }
                    $validMeds[] = [
                        'medication_name'     => $mName,
                        'dosage'              => !empty(trim($m['dosage'] ?? '')) ? trim($m['dosage']) : null,
                        'form'                => !empty(trim($m['form'] ?? '')) ? trim($m['form']) : null,
                        'frequency'           => !empty(trim($m['frequency'] ?? '')) ? trim($m['frequency']) : null,
                        'route'               => !empty(trim($m['route'] ?? '')) ? trim($m['route']) : null,
                        'prescription_source' => in_array($m['prescription_source'] ?? '', ['doctor', 'otc', 'self_administered']) ? $m['prescription_source'] : null,
                        'date_started'        => !empty($m['date_started']) ? $m['date_started'] : null,
                        'duration_ongoing'    => !empty(trim($m['duration_ongoing'] ?? '')) ? trim($m['duration_ongoing']) : null,
                        'last_dose_taken'     => !empty($m['last_dose_taken']) ? $m['last_dose_taken'] : null,
                    ];
                }

                // Sync medications: delete old and create new
                $record->medications()->delete();
                foreach ($validMeds as $vMed) {
                    $record->medications()->create($vMed);
                }

                $summary = DrugScreening::buildMedicationSummary($validMeds);
                $record->update([
                    'on_medication'      => true,
                    'medication_summary' => $summary,
                ]);
            } else {
                // If on_medication is false, delete any existing meds for this screening and nullify medication_summary
                $record->medications()->delete();
                $record->update([
                    'on_medication'      => false,
                    'medication_summary' => null,
                ]);
            }

            return $record;
        });

        $personName = $screening->person_name;

        $redirect = redirect()->to('/Lab_Analysis/drug-screening')
            ->with('success', "Drug screening record for {$personName} {$actionVerb} successfully (Result: {$screening->result}).");

        if ($screening->isCivilian()) {
            try {
                $referenceNumber = sprintf(
                    'NACOC-CIV-%s-%04d',
                    $screening->screening_date ? $screening->screening_date->format('Y') : date('Y'),
                    $screening->id
                );
                $dbHead = User::where('unit', 'LIKE', '%Lab%')->where('is_head', true)->first();
                $labHeadName = $dbHead ? $dbHead->fullname : 'Superintendent of Police / Head of Forensic Lab';
                $analystName = auth()->check() && auth()->user()->fullname ? auth()->user()->fullname : 'Forensic Analyst';

                $medsCount = ($screening->on_medication && $screening->medications) ? $screening->medications->count() : 0;
                $totalPages = $medsCount > 6 ? 3 : 2;

                // Pre-render view to verify generation succeeds without exceptions
                view('Lab_Analysis.drug_screening.civilian_pdf', compact('screening', 'referenceNumber', 'analystName', 'labHeadName', 'totalPages'))->render();

                $pdfUrl = url("/Lab_Analysis/drug-screening/{$screening->id}/pdf?auto_print=1");
                $redirect->with('auto_download_pdf', $pdfUrl)
                    ->with('civilian_pdf_id', $screening->id)
                    ->with('civilian_name', $screening->civilian_name);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Failed to generate civilian PDF report for screening #{$screening->id}: " . $e->getMessage(), [
                    'exception' => $e,
                ]);
                $redirect->with('pdf_error', "The civilian screening was saved successfully, but the PDF report could not be generated: " . $e->getMessage());
            }
        }

        return $redirect;
    }

    /**
     * Update the specified drug screening record directly.
     */
    public function update(Request $request, $id)
    {
        $screening = DrugScreening::findOrFail($id);

        if ($request->filled('suspect_id') && !is_numeric($request->suspect_id)) {
            $raw = (string) $request->suspect_id;
            $civName = str_starts_with($raw, 'civilian:') ? substr($raw, 9) : $raw;
            $request->merge([
                'civilian_name' => trim($civName),
                'suspect_id' => null,
            ]);
        }

        $isOnMedication = $request->boolean('on_medication');

        $validationRules = [
            'suspect_id'       => ['nullable', 'required_without:civilian_name', 'integer', 'exists:case_suspects,id'],
            'civilian_name'    => ['nullable', 'required_without:suspect_id', 'string', 'max:255'],
            'purpose'          => ['required', 'string', 'max:255'],
            'screening_date'   => ['required', 'date'],
            'result'           => ['required', 'string', \Illuminate\Validation\Rule::in(\App\Enums\ScreeningResult::values())],
            'substances_found' => ['nullable', 'string', 'max:255'],
            'notes'            => ['nullable', 'string', 'max:2000'],
            'note'             => ['nullable', 'string', 'max:2000'],
            'on_medication'    => ['nullable'],
        ];

        if ($isOnMedication) {
            $validationRules['medications'] = ['required', 'array', 'min:1'];
            $validationRules['medications.*.medication_name'] = ['required', 'string', 'max:255'];
            $validationRules['medications.*.dosage'] = ['nullable', 'string', 'max:100'];
            $validationRules['medications.*.form'] = ['nullable', 'string', 'max:100'];
            $validationRules['medications.*.frequency'] = ['nullable', 'string', 'max:100'];
            $validationRules['medications.*.route'] = ['nullable', 'string', 'max:100'];
            $validationRules['medications.*.prescription_source'] = ['nullable', 'string', 'in:doctor,otc,self_administered'];
            $validationRules['medications.*.date_started'] = ['nullable', 'date'];
            $validationRules['medications.*.duration_ongoing'] = ['nullable', 'string', 'max:100'];
            $validationRules['medications.*.last_dose_taken'] = ['nullable', 'date'];
        }

        $validationMessages = [
            'suspect_id.required_without'             => 'Please select a registered suspect or enter a civilian name.',
            'civilian_name.required_without'          => 'Please select a registered suspect or enter a civilian name.',
            'suspect_id.exists'                       => 'The selected suspect could not be found in active records.',
            'purpose.required'                        => 'The purpose of the screening is required.',
            'screening_date.required'                 => 'The date of screening is required.',
            'result.required'                         => 'The screening outcome result is required.',
            'result.in'                               => 'The result must be Positive, Negative, or Pending.',
            'medications.required'                    => 'Please provide at least one medication entry when "On Medication" is Yes.',
            'medications.min'                         => 'Please provide at least one medication entry when "On Medication" is Yes.',
            'medications.*.medication_name.required'  => 'Medication name is required for each entered medication record.',
            'medications.*.prescription_source.in'    => 'The prescription source must be Doctor, Over the Counter, or Self-Administered.',
        ];

        $validated = $request->validate($validationRules, $validationMessages);

        $notesVal = $request->input('notes') ?? $request->input('note');
        $validated['notes'] = $notesVal;
        unset($validated['note']);

        if (strtolower(trim($validated['result'])) === 'negative') {
            $validated['substances_found'] = 'No drug(s) detected';
        }

        if (!empty($validated['civilian_name'])) {
            $validated['civilian_name'] = trim($validated['civilian_name']);
            $validated['suspect_id'] = null;
        } else {
            $validated['civilian_name'] = null;
        }

        DB::transaction(function () use ($screening, $request, $validated, $isOnMedication) {
            $dataToSave = [
                'suspect_id'       => $validated['suspect_id'] ?? null,
                'civilian_name'    => $validated['civilian_name'] ?? null,
                'purpose'          => $validated['purpose'],
                'screening_date'   => $validated['screening_date'],
                'result'           => $validated['result'],
                'substances_found' => $validated['substances_found'] ?? null,
                'notes'            => $validated['notes'] ?? null,
                'on_medication'    => $isOnMedication,
            ];

            $screening->update($dataToSave);

            if ($isOnMedication) {
                $medsInput = $request->input('medications', []);
                $validMeds = [];
                foreach ($medsInput as $m) {
                    $mName = trim($m['medication_name'] ?? '');
                    if ($mName === '') {
                        continue;
                    }
                    $validMeds[] = [
                        'medication_name'     => $mName,
                        'dosage'              => !empty(trim($m['dosage'] ?? '')) ? trim($m['dosage']) : null,
                        'form'                => !empty(trim($m['form'] ?? '')) ? trim($m['form']) : null,
                        'frequency'           => !empty(trim($m['frequency'] ?? '')) ? trim($m['frequency']) : null,
                        'route'               => !empty(trim($m['route'] ?? '')) ? trim($m['route']) : null,
                        'prescription_source' => in_array($m['prescription_source'] ?? '', ['doctor', 'otc', 'self_administered']) ? $m['prescription_source'] : null,
                        'date_started'        => !empty($m['date_started']) ? $m['date_started'] : null,
                        'duration_ongoing'    => !empty(trim($m['duration_ongoing'] ?? '')) ? trim($m['duration_ongoing']) : null,
                        'last_dose_taken'     => !empty($m['last_dose_taken']) ? $m['last_dose_taken'] : null,
                    ];
                }

                // Sync medications: delete old and create new
                $screening->medications()->delete();
                foreach ($validMeds as $vMed) {
                    $screening->medications()->create($vMed);
                }

                $summary = DrugScreening::buildMedicationSummary($validMeds);
                $screening->update([
                    'on_medication'      => true,
                    'medication_summary' => $summary,
                ]);
            } else {
                $screening->medications()->delete();
                $screening->update([
                    'on_medication'      => false,
                    'medication_summary' => null,
                ]);
            }
        });

        $personName = $screening->person_name;

        $redirect = redirect()->to('/Lab_Analysis/drug-screening')
            ->with('success', "Drug screening record for {$personName} updated successfully (Result: {$screening->result}).");

        if ($screening->isCivilian()) {
            try {
                $referenceNumber = sprintf(
                    'NACOC-CIV-%s-%04d',
                    $screening->screening_date ? $screening->screening_date->format('Y') : date('Y'),
                    $screening->id
                );
                $dbHead = User::where('unit', 'LIKE', '%Lab%')->where('is_head', true)->first();
                $labHeadName = $dbHead ? $dbHead->fullname : 'Superintendent of Police / Head of Forensic Lab';
                $analystName = auth()->check() && auth()->user()->fullname ? auth()->user()->fullname : 'Forensic Analyst';

                $medsCount = ($screening->on_medication && $screening->medications) ? $screening->medications->count() : 0;
                $totalPages = $medsCount > 6 ? 3 : 2;

                // Pre-render view to verify generation succeeds without exceptions
                view('Lab_Analysis.drug_screening.civilian_pdf', compact('screening', 'referenceNumber', 'analystName', 'labHeadName', 'totalPages'))->render();

                $pdfUrl = url("/Lab_Analysis/drug-screening/{$screening->id}/pdf?auto_print=1");
                $redirect->with('auto_download_pdf', $pdfUrl)
                    ->with('civilian_pdf_id', $screening->id)
                    ->with('civilian_name', $screening->civilian_name);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Failed to generate civilian PDF report for screening #{$screening->id}: " . $e->getMessage(), [
                    'exception' => $e,
                ]);
                $redirect->with('pdf_error', "The civilian screening was updated successfully, but the PDF report could not be generated: " . $e->getMessage());
            }
        }

        return $redirect;
    }

    /**
     * Remove the specified drug screening record.
     */
    public function destroy($id)
    {
        $screening = DrugScreening::findOrFail($id);
        $name = $screening->person_name;
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
        $screening = DrugScreening::with(['suspect.multipleCase', 'medications'])
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
                'mugshot_url'              => $screening->suspect?->mugshot_front_url ?? \App\Services\CasePhotoResolver::resolveMugshotUrl($screening->suspect?->mugshot_front ?: ($screening->suspect?->singleCase?->mugshot_front ?: null)),
                'ghana_card'               => $screening->suspect->ghana_card ?? 'N/A',
                'case_number'              => $screening->suspect->multipleCase->case_number ?? ('NCC-' . date('Y')),
                'holding_facility'         => $screening->suspect->holding_facility ?? config('forensics.facilities.default', 'NACOC Central Custody Facility'),
                'purpose'                  => $screening->purpose,
                'screening_date'           => $screening->screening_date ? $screening->screening_date->format('Y-m-d') : null,
                'screening_date_formatted' => $screening->screening_date ? $screening->screening_date->format('d M Y') : 'N/A',
                'result'                   => $screening->result,
                'substances_found'         => $screening->substances_found,
                'substance_list'           => $screening->substance_list,
                'notes'                    => $screening->notes ?? $screening->note ?? '',
                'note'                     => $screening->notes ?? $screening->note ?? '',
                'on_medication'            => (bool) $screening->on_medication,
                'medication_summary'       => $screening->medication_summary,
                'medications'              => $screening->medications->map(function ($m) {
                    return [
                        'id'                  => $m->id,
                        'medication_name'     => $m->medication_name,
                        'dosage'              => $m->dosage ?? '',
                        'form'                => $m->form ?? '',
                        'frequency'           => $m->frequency ?? '',
                        'route'               => $m->route ?? '',
                        'prescription_source' => $m->prescription_source ?? '',
                        'source_label'        => $m->source_label,
                        'date_started'        => $m->date_started ? $m->date_started->format('Y-m-d') : '',
                        'duration_ongoing'    => $m->duration_ongoing ?? '',
                        'last_dose_taken'     => $m->last_dose_taken ? $m->last_dose_taken->format('Y-m-d\TH:i') : '',
                    ];
                })->values()->toArray(),
                'created_at'               => $screening->created_at ? $screening->created_at->format('d M Y, H:i') : 'N/A',
            ],
        ]);
    }

    /**
     * Show detailed drug screening record.
     */
    public function show(Request $request, $id)
    {
        $screening = DrugScreening::with(['suspect.multipleCase', 'medications'])->findOrFail($id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'   => true,
                'screening' => [
                    'id'                       => $screening->id,
                    'type'                     => $screening->isCivilian() ? 'Civilian' : 'Suspect',
                    'suspect_id'               => $screening->suspect_id,
                    'civilian_name'            => $screening->civilian_name,
                    'suspect_name'             => $screening->person_name,
                    'suspect_alias'            => $screening->suspect->suspect_alias ?? '',
                    'ghana_card'               => $screening->suspect->ghana_card ?? 'N/A',
                    'case_number'              => $screening->suspect->multipleCase->case_number ?? ($screening->isCivilian() ? 'Civilian Assessment' : ('NCC-' . date('Y'))),
                    'holding_facility'         => $screening->suspect->holding_facility ?? ($screening->isCivilian() ? 'Walk-in / Civilian' : config('forensics.facilities.default', 'NACOC Central Custody Facility')),
                    'purpose'                  => $screening->purpose,
                    'screening_date'           => $screening->screening_date ? $screening->screening_date->format('Y-m-d') : null,
                    'screening_date_formatted' => $screening->screening_date ? $screening->screening_date->format('d M Y') : 'N/A',
                    'result'                   => $screening->result,
                    'substances_found'         => $screening->substances_found,
                    'substance_list'           => $screening->substance_list,
                    'notes'                    => $screening->notes,
                    'on_medication'            => (bool) $screening->on_medication,
                    'medication_summary'       => $screening->medication_summary,
                    'medications'              => $screening->medications->map(function ($m) {
                        return [
                            'id'                  => $m->id,
                            'medication_name'     => $m->medication_name,
                            'dosage'              => $m->dosage ?? '',
                            'form'                => $m->form ?? '',
                            'frequency'           => $m->frequency ?? '',
                            'route'               => $m->route ?? '',
                            'prescription_source' => $m->prescription_source ?? '',
                            'source_label'        => $m->source_label,
                            'date_started'        => $m->date_started ? $m->date_started->format('Y-m-d') : '',
                            'duration_ongoing'    => $m->duration_ongoing ?? '',
                            'last_dose_taken'     => $m->last_dose_taken ? $m->last_dose_taken->format('Y-m-d\TH:i') : '',
                        ];
                    })->values()->toArray(),
                    'created_at'               => $screening->created_at ? $screening->created_at->format('d M Y, H:i') : 'N/A',
                ],
            ]);
        }

        return redirect()->to('/Lab_Analysis/drug-screening?search=' . urlencode($screening->person_name));
    }

    /**
     * Display printable PDF report for civilian or suspect drug screening dossier.
     */
    public function showPdf($id)
    {
        $screening = DrugScreening::with(['medications', 'suspect.singleCase', 'suspect.multipleCase'])->findOrFail($id);

        $referenceNumber = $screening->isCivilian()
            ? sprintf('NACOC-CIV-%s-%04d', $screening->screening_date ? $screening->screening_date->format('Y') : date('Y'), $screening->id)
            : sprintf('NACOC-TOX-%s-%04d', $screening->screening_date ? $screening->screening_date->format('Y') : date('Y'), $screening->id);

        $dbHead = User::where('unit', 'LIKE', '%Lab%')->where('is_head', true)->first();
        $labHeadName = $dbHead ? $dbHead->fullname : 'Superintendent of Police / Head of Forensic Lab';
        $analystName = auth()->check() && auth()->user()->fullname ? auth()->user()->fullname : 'Forensic Analyst';

        $medsCount = ($screening->on_medication && $screening->medications) ? $screening->medications->count() : 0;
        $totalPages = $medsCount > 6 ? 3 : 2;

        return view('Lab_Analysis.drug_screening.civilian_pdf', compact('screening', 'referenceNumber', 'analystName', 'labHeadName', 'totalPages'));
    }

    /**
     * Generate printable PDF report with flexible filter parameters:
     * Personal (specific suspect or civilian), General, Weekly, Monthly, Annually, or Custom date range.
     */
    public function generateReportPdf(Request $request)
    {
        // 1. Direct personal report by screening ID
        if ($request->filled('screening_id')) {
            return $this->showPdf($request->screening_id);
        }

        $rawPeriod = strtolower(trim($request->input('period', 'general')));
        $rawType = strtolower(trim($request->input('type', 'all')));
        $rawResult = strtolower(trim($request->input('result', 'all')));
        $fromDate = $request->input('from');
        $toDate = $request->input('to');
        $person = trim((string) $request->input('person', ''));
        $reportMode = strtolower(trim((string) $request->input('report_mode', 'general')));

        // 2. Personal report mode or specific person requested
        if (!empty($person) || (($reportMode === 'personal' || $request->filled('personal')) && !empty($person))) {
            $personalRecord = DrugScreening::where('civilian_name', 'LIKE', "%{$person}%")
                ->orWhereHas('suspect', function ($sq) use ($person) {
                    $sq->where('suspect_name', 'LIKE', "%{$person}%")
                      ->orWhere('suspect_alias', 'LIKE', "%{$person}%");
                })
                ->latest('screening_date')
                ->latest('id')
                ->first();

            if ($personalRecord) {
                return $this->showPdf($personalRecord->id);
            }
        }

        // 3. Consolidated Register Report Query
        $query = DrugScreening::with(['suspect.singleCase', 'suspect.multipleCase', 'medications'])
            ->orderBy('screening_date', 'desc')
            ->orderBy('id', 'desc');

        // Period filter
        if (in_array($rawPeriod, ['weekly', 'week'])) {
            $query->where('screening_date', '>=', now()->subDays(7)->startOfDay()->toDateString());
        } elseif (in_array($rawPeriod, ['monthly', 'month'])) {
            $query->where('screening_date', '>=', now()->subDays(30)->startOfDay()->toDateString());
        } elseif (in_array($rawPeriod, ['annually', 'annual', 'yearly', 'year'])) {
            $query->where('screening_date', '>=', now()->subDays(365)->startOfDay()->toDateString());
        }

        // Custom date boundaries
        if (!empty($fromDate)) {
            $query->where('screening_date', '>=', $fromDate);
        }
        if (!empty($toDate)) {
            $query->where('screening_date', '<=', $toDate);
        }

        // Target classification
        if (in_array($rawType, ['suspects', 'suspect'])) {
            $query->whereNotNull('suspect_id');
        } elseif (in_array($rawType, ['civilians', 'civilian'])) {
            $query->whereNotNull('civilian_name');
        }

        // Qualitative result filter
        if (in_array($rawResult, ['positive', 'negative', 'pending'])) {
            $query->whereRaw('LOWER(result) = ?', [$rawResult]);
        }

        // Person filter if supplied
        if (!empty($person)) {
            $query->where(function ($q) use ($person) {
                $q->where('civilian_name', 'LIKE', "%{$person}%")
                  ->orWhereHas('suspect', function ($sq) use ($person) {
                      $sq->where('suspect_name', 'LIKE', "%{$person}%")
                        ->orWhere('suspect_alias', 'LIKE', "%{$person}%");
                  });
            });
        }

        // Text search if supplied
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('purpose', 'LIKE', "%{$search}%")
                  ->orWhere('result', 'LIKE', "%{$search}%")
                  ->orWhere('substances_found', 'LIKE', "%{$search}%")
                  ->orWhere('notes', 'LIKE', "%{$search}%")
                  ->orWhere('civilian_name', 'LIKE', "%{$search}%")
                  ->orWhereHas('suspect', function ($sq) use ($search) {
                      $sq->where('suspect_name', 'LIKE', "%{$search}%")
                        ->orWhere('suspect_alias', 'LIKE', "%{$search}%")
                        ->orWhere('ghana_card', 'LIKE', "%{$search}%");
                  });
            });
        }

        $screenings = $query->get();

        // Statistical metrics
        $totalCount    = $screenings->count();
        $positiveCount = $screenings->filter(fn($s) => $s->isPositive())->count();
        $negativeCount = $screenings->filter(fn($s) => $s->isNegative())->count();
        $pendingCount  = $screenings->filter(fn($s) => $s->isPending())->count();
        $medsCount     = $screenings->filter(fn($s) => $s->on_medication)->count();
        $suspectsCount = $screenings->filter(fn($s) => $s->isSuspect())->count();
        $civiliansCount= $screenings->filter(fn($s) => $s->isCivilian())->count();

        // Format Period Label & Report Type Label
        $hasCustomRange = !empty($fromDate) || !empty($toDate);
        $periodLabel = match(true) {
            $hasCustomRange => 'Period: ' . ($fromDate ? date('d F Y', strtotime($fromDate)) : 'Start') . ' – ' . ($toDate ? date('d F Y', strtotime($toDate)) : 'Present'),
            in_array($rawPeriod, ['weekly', 'week']) => 'Period: Past 7 Days (' . now()->subDays(7)->format('d F') . ' – ' . now()->format('d F Y') . ')',
            in_array($rawPeriod, ['monthly', 'month']) => 'Period: Month of ' . now()->format('F Y') . ' (Past 30 Days)',
            in_array($rawPeriod, ['annually', 'annual', 'yearly', 'year']) => 'Period: Calendar Year ' . now()->format('Y'),
            default => 'Period: All Registered Cases (Cumulative)'
        };

        $reportTypeLabel = match(true) {
            in_array($rawPeriod, ['weekly', 'week']) => 'WEEKLY DRUG SCREENING REPORT',
            in_array($rawPeriod, ['monthly', 'month']) => 'MONTHLY DRUG SCREENING REPORT',
            in_array($rawPeriod, ['annually', 'annual', 'yearly', 'year']) => 'ANNUAL DRUG SCREENING REPORT',
            $hasCustomRange => 'CUSTOM DATE RANGE DRUG SCREENING REPORT',
            default => 'GENERAL DRUG FORENSIC REPORT'
        };

        $scopeLabel = match($rawType) {
            'suspects', 'suspect' => 'SUSPECT DRUG SCREENING REGISTRY',
            'civilians', 'civilian' => 'CIVILIAN DRUG SCREENING REGISTRY',
            default => null
        };

        if (!empty($person)) {
            $scopeLabel = (!empty($scopeLabel) ? $scopeLabel . ' • ' : '') . 'SUBJECT: ' . strtoupper($person);
        }

        $referenceNumber = sprintf('NACOC-DSR-%s-%04d', date('Y'), rand(100, 999));

        $dbHead = User::where('unit', 'LIKE', '%Lab%')->where('is_head', true)->first();
        $labHeadName = $dbHead ? $dbHead->fullname : 'Superintendent of Police / Head of Forensic Lab';
        $analystName = auth()->check() && auth()->user()->fullname ? auth()->user()->fullname : 'Forensic Analyst';

        return view('Lab_Analysis.drug_screening.report_pdf', compact(
            'screenings',
            'totalCount',
            'positiveCount',
            'negativeCount',
            'pendingCount',
            'medsCount',
            'suspectsCount',
            'civiliansCount',
            'periodLabel',
            'reportTypeLabel',
            'scopeLabel',
            'rawPeriod',
            'rawType',
            'rawResult',
            'referenceNumber',
            'labHeadName',
            'analystName'
        ));
    }
}

