<?php

namespace App\Http\Controllers;

use App\Models\Exhibit;
use App\Models\SingleSuspectCase;
use App\Models\MultipleSuspectCase;
use App\Models\CaseSuspect;
use App\Models\ForensicCase;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EvidenceManagementController extends Controller
{
    /**
     * Display the Central Exhibit Vault showing all exhibits and associated suspects.
     */
    public function vault(Request $request)
    {
        $this->syncMissingCaseExhibits();

        $search = trim($request->input('search', ''));
        $category = trim($request->input('category', 'all'));
        $suspectFilter = trim($request->input('suspect_filter', 'all'));
        $actionStatus = trim($request->input('action_status', 'all')); // 'all', 'pending', 'unassigned_qr', 'unclassified', 'completed'
        $sort = trim($request->input('sort', 'newest'));
        $viewMode = trim($request->input('view', 'grid')); // 'grid' or 'table'
        $perPage = (int) $request->input('per_page', 12);
        if ($perPage < 6) $perPage = 12;
        if ($perPage > 100) $perPage = 100;

        // Base query with eager loading to prevent N+1 issues
        $query = Exhibit::with([
            'singleSuspectCase',
            'multipleSuspectCase.suspects',
            'suspect',
            'labResult'
        ]);

        // Action Status Filter (Unassigned QR Code and/or Unclassified Exhibits)
        if ($actionStatus === 'pending' || $actionStatus === 'needs_action' || $actionStatus === 'unassigned') {
            $query->needsQrOrClassification();
        } elseif ($actionStatus === 'unassigned_qr' || $actionStatus === 'no_qr') {
            $query->unassignedQr();
        } elseif ($actionStatus === 'unclassified' || $actionStatus === 'no_classification') {
            $query->unclassified();
        } elseif ($actionStatus === 'missing_both') {
            $query->where(function ($q) {
                $q->unassignedQr();
            })->where(function ($q) {
                $q->unclassified();
            });
        } elseif ($actionStatus === 'completed' || $actionStatus === 'tagged_and_classified') {
            $query->completedQrAndClassification();
        }

        // Category Filter
        if ($category !== 'all' && $category !== '') {
            $catLower = strtolower($category);
            if (str_contains($catLower, 'drug') || str_contains($catLower, 'narcotic')) {
                $query->where(function ($q) {
                    $q->where('exhibit_category', 'LIKE', '%drug%')
                      ->orWhere('exhibit_category', 'LIKE', '%narcotic%')
                      ->orWhereNotNull('drug_classification');
                });
            } elseif (str_contains($catLower, 'weapon') || str_contains($catLower, 'firearm')) {
                $query->where(function ($q) {
                    $q->where('exhibit_category', 'LIKE', '%weapon%')
                      ->orWhere('exhibit_category', 'LIKE', '%firearm%')
                      ->orWhereNotNull('wpn_model')
                      ->orWhereNotNull('wpn_serial_no');
                });
            } elseif (str_contains($catLower, 'vehic') || str_contains($catLower, 'car')) {
                $query->where(function ($q) {
                    $q->where('exhibit_category', 'LIKE', '%vehic%')
                      ->orWhereNotNull('veh_make')
                      ->orWhereNotNull('veh_reg_no');
                });
            } elseif (str_contains($catLower, 'devic') || str_contains($catLower, 'electron') || str_contains($catLower, 'digital') || str_contains($catLower, 'phone')) {
                $query->where(function ($q) {
                    $q->where('exhibit_category', 'LIKE', '%devic%')
                      ->orWhere('exhibit_category', 'LIKE', '%electron%')
                      ->orWhere('exhibit_category', 'LIKE', '%digital%')
                      ->orWhereNotNull('dev_model')
                      ->orWhereNotNull('dev_imei1');
                });
            } elseif (str_contains($catLower, 'money') || str_contains($catLower, 'cash') || str_contains($catLower, 'currency')) {
                $query->where(function ($q) {
                    $q->where('exhibit_category', 'LIKE', '%money%')
                      ->orWhere('exhibit_category', 'LIKE', '%cash%')
                      ->orWhere('exhibit_category', 'LIKE', '%currency%')
                      ->orWhere('commodity_name', 'LIKE', '%currency%')
                      ->orWhere('commodity_name', 'LIKE', '%cash%');
                });
            } elseif (str_contains($catLower, 'general') || str_contains($catLower, 'other')) {
                $query->where(function ($q) {
                    $q->where('exhibit_category', 'LIKE', '%general%')
                      ->orWhere('exhibit_category', 'LIKE', '%other%')
                      ->orWhereNull('exhibit_category')
                      ->orWhere('exhibit_category', '');
                });
            } else {
                $query->where('exhibit_category', 'LIKE', '%' . $category . '%');
            }
        }

        // Search Filter (Exhibit ID, Property ID, QR Token, Commodity, Remarks, Case Number, Suspect Name, Officers, Locations)
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('property_id', 'LIKE', "%{$search}%")
                  ->orWhere('qr_token', 'LIKE', "%{$search}%")
                  ->orWhere('exhibit_number', 'LIKE', "%{$search}%")
                  ->orWhere('commodity_name', 'LIKE', "%{$search}%")
                  ->orWhere('exhibit_category', 'LIKE', "%{$search}%")
                  ->orWhere('exhibit_type', 'LIKE', "%{$search}%")
                  ->orWhere('drug_classification', 'LIKE', "%{$search}%")
                  ->orWhere('seizure_packaging', 'LIKE', "%{$search}%")
                  ->orWhere('seizure_location', 'LIKE', "%{$search}%")
                  ->orWhere('seizure_officer', 'LIKE', "%{$search}%")
                  ->orWhere('source_of_exhibits', 'LIKE', "%{$search}%")
                  ->orWhere('exhibit_remark', 'LIKE', "%{$search}%")
                  ->orWhere('lab_remark', 'LIKE', "%{$search}%")
                  ->orWhere('associated_person', 'LIKE', "%{$search}%")
                  ->orWhere('veh_make', 'LIKE', "%{$search}%")
                  ->orWhere('veh_model', 'LIKE', "%{$search}%")
                  ->orWhere('veh_reg_no', 'LIKE', "%{$search}%")
                  ->orWhere('veh_vin', 'LIKE', "%{$search}%")
                  ->orWhere('wpn_model', 'LIKE', "%{$search}%")
                  ->orWhere('wpn_serial_no', 'LIKE', "%{$search}%")
                  ->orWhere('dev_model', 'LIKE', "%{$search}%")
                  ->orWhere('dev_serial_no', 'LIKE', "%{$search}%")
                  ->orWhere('dev_imei1', 'LIKE', "%{$search}%")
                  // Or Search inside Single Suspect Case
                  ->orWhereHas('singleSuspectCase', function ($sub) use ($search) {
                      $sub->where('case_number', 'LIKE', "%{$search}%")
                          ->orWhere('suspect_name', 'LIKE', "%{$search}%")
                          ->orWhere('suspect_alias', 'LIKE', "%{$search}%")
                          ->orWhere('ghana_card', 'LIKE', "%{$search}%");
                  })
                  // Or Search inside Multiple Suspect Case
                  ->orWhereHas('multipleSuspectCase', function ($sub) use ($search) {
                      $sub->where('case_number', 'LIKE', "%{$search}%")
                          ->orWhereHas('suspects', function ($susQuery) use ($search) {
                              $susQuery->where('suspect_name', 'LIKE', "%{$search}%")
                                       ->orWhere('suspect_alias', 'LIKE', "%{$search}%")
                                       ->orWhere('ghana_card', 'LIKE', "%{$search}%");
                          });
                  })
                  // Or directly linked suspect
                  ->orWhereHas('suspect', function ($sub) use ($search) {
                      $sub->where('suspect_name', 'LIKE', "%{$search}%")
                          ->orWhere('suspect_alias', 'LIKE', "%{$search}%")
                          ->orWhere('ghana_card', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Suspect Linked Status Filter
        if ($suspectFilter === 'with_suspects') {
            $query->where(function ($q) {
                $q->whereNotNull('single_suspect_case_id')
                  ->orWhereNotNull('suspect_id')
                  ->orWhere(function ($sub) {
                      $sub->whereNotNull('associated_person')->where('associated_person', '!=', '');
                  })
                  ->orWhereHas('multipleSuspectCase.suspects');
            });
        } elseif ($suspectFilter === 'no_suspects') {
            $query->whereNull('single_suspect_case_id')
                  ->whereNull('suspect_id')
                  ->where(function($q) {
                      $q->whereNull('associated_person')->orWhere('associated_person', '');
                  })
                  ->whereDoesntHave('multipleSuspectCase.suspects');
        }

        // Put exhibits that have not been assigned QR code or are unclassified on TOP!
        // 0 = missing both QR and classification (highest priority)
        // 1 = missing either QR code or classification
        // 2 = fully tagged and classified
        $query->orderByRaw("CASE 
            WHEN ((property_id IS NULL OR property_id = '') AND (tag_url IS NULL OR tag_url = ''))
                 AND (exhibit_type IS NULL OR exhibit_type = '' OR ((exhibit_category LIKE '%drug%' OR exhibit_category LIKE '%narcotic%') AND (drug_classification IS NULL OR drug_classification = '')))
            THEN 0
            WHEN ((property_id IS NULL OR property_id = '') AND (tag_url IS NULL OR tag_url = ''))
                 OR (exhibit_type IS NULL OR exhibit_type = '')
                 OR ((exhibit_category LIKE '%drug%' OR exhibit_category LIKE '%narcotic%') AND (drug_classification IS NULL OR drug_classification = ''))
            THEN 1
            ELSE 2
        END ASC");

        // Secondary Sorting (applied within each priority tier)
        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc')->orderBy('id', 'asc');
                break;
            case 'value_desc':
                $query->orderByRaw('CAST(COALESCE(seizure_value, 0) AS DECIMAL(15,2)) DESC');
                break;
            case 'value_asc':
                $query->orderByRaw('CAST(COALESCE(seizure_value, 0) AS DECIMAL(15,2)) ASC');
                break;
            case 'name_asc':
                $query->orderBy('commodity_name', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc')->orderBy('id', 'desc');
                break;
        }

        $exhibits = $query->paginate($perPage)->withQueryString();

        // High-level telemetry stats across entire Vault
        $totalExhibitsCount = Exhibit::count();
        $totalSeizureValue = Exhibit::selectRaw('SUM(CAST(COALESCE(seizure_value, 0) AS DECIMAL(15,2))) as total')->value('total') ?? 0;
        
        $pendingCount = Exhibit::countPendingQrOrClassification();
        $unassignedQrCount = Exhibit::unassignedQr()->count();
        $unclassifiedCount = Exhibit::unclassified()->count();
        $completedCount = Exhibit::completedQrAndClassification()->count();

        $drugsCount = Exhibit::where(function($q) {
            $q->where('exhibit_category', 'LIKE', '%drug%')
              ->orWhere('exhibit_category', 'LIKE', '%narcotic%')
              ->orWhereNotNull('drug_classification');
        })->count();

        $weaponsCount = Exhibit::where(function($q) {
            $q->where('exhibit_category', 'LIKE', '%weapon%')
              ->orWhere('exhibit_category', 'LIKE', '%firearm%')
              ->orWhereNotNull('wpn_model')
              ->orWhereNotNull('wpn_serial_no');
        })->count();

        $vehiclesCount = Exhibit::where(function($q) {
            $q->where('exhibit_category', 'LIKE', '%vehic%')
              ->orWhereNotNull('veh_make')
              ->orWhereNotNull('veh_reg_no');
        })->count();

        $devicesCount = Exhibit::where(function($q) {
            $q->where('exhibit_category', 'LIKE', '%devic%')
              ->orWhere('exhibit_category', 'LIKE', '%electron%')
              ->orWhere('exhibit_category', 'LIKE', '%digital%')
              ->orWhereNotNull('dev_model')
              ->orWhereNotNull('dev_imei1');
        })->count();

        $currencyCount = Exhibit::where(function($q) {
            $q->where('exhibit_category', 'LIKE', '%money%')
              ->orWhere('exhibit_category', 'LIKE', '%cash%')
              ->orWhere('exhibit_category', 'LIKE', '%currency%')
              ->orWhere('commodity_name', 'LIKE', '%currency%')
              ->orWhere('commodity_name', 'LIKE', '%cash%');
        })->count();

        $totalSuspectsCount = SingleSuspectCase::count() + CaseSuspect::count();

        if ($request->ajax()) {
            $html = view('Evidence_Management.partials.vault_content', compact(
                'exhibits',
                'viewMode',
                'search',
                'category',
                'suspectFilter',
                'actionStatus'
            ))->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'metrics' => [
                    'total' => $totalExhibitsCount,
                    'total_formatted' => number_format($totalExhibitsCount),
                    'total_value_formatted' => number_format($totalSeizureValue, 0),
                    'pending' => $pendingCount,
                    'pending_formatted' => number_format($pendingCount),
                    'unassigned_qr' => $unassignedQrCount,
                    'unclassified' => $unclassifiedCount,
                    'completed' => $completedCount,
                    'drugs' => $drugsCount,
                    'weapons' => $weaponsCount,
                    'vehicles' => $vehiclesCount,
                    'devices' => $devicesCount,
                    'currency' => $currencyCount,
                ]
            ]);
        }

        return view('Evidence_Management.vault', compact(
            'exhibits',
            'search',
            'category',
            'suspectFilter',
            'actionStatus',
            'sort',
            'viewMode',
            'totalExhibitsCount',
            'totalSeizureValue',
            'pendingCount',
            'unassignedQrCount',
            'unclassifiedCount',
            'completedCount',
            'drugsCount',
            'weaponsCount',
            'vehiclesCount',
            'devicesCount',
            'currencyCount',
            'totalSuspectsCount'
        ));
    }

    /**
     * Issue unique random Property IDs (NACOC/EMU/#####) not used by any stored exhibit.
     */
    public function propertyIds(Request $request)
    {
        $count = max(1, min(100, (int) $request->query('count', 20)));
        $ids = [];
        $guard = 0;
        while (count($ids) < $count && $guard++ < 2000) {
            $candidate = 'NACOC/EMU/' . random_int(10000, 99999);
            if (isset($ids[$candidate]) || Exhibit::where('property_id', $candidate)->exists()) {
                continue;
            }
            $ids[$candidate] = true;
        }

        return response()->json(['ids' => array_keys($ids)]);
    }

    /**
     * Store the generated Seized Property tag images for a freshly saved case and
     * record each image URL on the matching exhibit (exhibits.tag_url).
     * Tags are matched to exhibits by their order of creation within the case.
     */
    public function storeExhibitTags(Request $request)
    {
        $validated = $request->validate([
            'case_number'       => ['required', 'string', 'max:100'],
            'tags'              => ['required', 'array', 'min:1', 'max:200'],
            'tags.*.property_id' => ['nullable', 'string', 'max:100'],
            'tags.*.image'      => ['required', 'string', 'starts_with:data:image/png;base64,'],
        ]);

        $caseNumber = trim($validated['case_number']);
        $single = SingleSuspectCase::where('case_number', $caseNumber)->first();
        $multiple = $single ? null : MultipleSuspectCase::where('case_number', $caseNumber)->first();

        if (!$single && !$multiple) {
            return response()->json(['success' => false, 'message' => 'Case not found.'], 404);
        }

        $exhibits = Exhibit::query()
            ->when($single, fn ($q) => $q->where('single_suspect_case_id', $single->id))
            ->when($multiple, fn ($q) => $q->where('multiple_suspect_case_id', $multiple->id))
            ->orderBy('id')
            ->get()
            ->values();

        $saved = [];
        foreach (array_values($validated['tags']) as $i => $tag) {
            $binary = base64_decode(substr($tag['image'], strlen('data:image/png;base64,')), true);
            if ($binary === false || strlen($binary) > 3 * 1024 * 1024 || !@getimagesizefromstring($binary)) {
                continue;
            }

            $slug = preg_replace('/[^A-Za-z0-9_-]+/', '_', ($tag['property_id'] ?? '') ?: ('tag_' . $i));
            $path = 'exhibit_tags/' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $caseNumber)
                . '_' . $slug . '_' . substr(sha1($binary), 0, 8) . '.png';
            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $binary);
            $url = \Illuminate\Support\Facades\Storage::url($path);

            $targetExhibit = null;
            if (!empty($tag['property_id'])) {
                $targetExhibit = $exhibits->firstWhere('property_id', $tag['property_id']);
            }
            if (!$targetExhibit && isset($exhibits[$i])) {
                $targetExhibit = $exhibits[$i];
            }

            if ($targetExhibit) {
                $targetExhibit->tag_url = $url;
                if (!empty($tag['property_id']) && (empty($targetExhibit->property_id) || $targetExhibit->property_id === $tag['property_id'])) {
                    $targetExhibit->property_id = $tag['property_id'];
                }
                $targetExhibit->save();
            }
            $saved[] = $url;
        }

        return response()->json(['success' => true, 'urls' => $saved]);
    }

    /**
     * Update an individual exhibit from the Central Vault: assign QR code image / Seized Property tag,
     * classify drugs, update exhibit type, commodity name, category, and remarks.
     */
    public function updateExhibit(Request $request, $id)
    {
        $exhibit = Exhibit::with([
            'singleSuspectCase',
            'multipleSuspectCase.suspects',
            'suspect',
            'labResult'
        ])->findOrFail($id);

        $validated = $request->validate([
            'property_id'         => ['nullable', 'string', 'max:100'],
            'custodial_status'    => ['nullable', 'string', 'in:Seized,In Vault Custody,Under Laboratory Analysis,Court Evidence,Transferred,Released,Scheduled for Destruction'],
            'tag_image'           => ['nullable', 'string'],
            'drug_classification' => ['nullable', 'string', 'max:255'],
            'exhibit_type'        => ['nullable', 'string', 'max:255'],
            'commodity_name'      => ['nullable', 'string', 'max:255'],
            'exhibit_category'    => ['nullable', 'string', 'max:100'],
            'exhibit_remark'      => ['nullable', 'string', 'max:1000'],
        ]);

        // 1. Process base64 QR badge image upload if provided
        if (!empty($validated['tag_image'])) {
            $rawImg = $validated['tag_image'];
            if (str_contains($rawImg, ';base64,')) {
                $rawImg = substr($rawImg, strpos($rawImg, ';base64,') + 8);
            }
            $binary = base64_decode($rawImg, true);
            if ($binary !== false && strlen($binary) <= 5 * 1024 * 1024 && @getimagesizefromstring($binary)) {
                $propSlug = preg_replace('/[^A-Za-z0-9_-]+/', '_', $validated['property_id'] ?? ($exhibit->property_id ?: 'prop_' . $exhibit->id));
                $path = 'exhibit_tags/tag_' . $propSlug . '_' . substr(sha1($binary), 0, 8) . '.png';
                Storage::disk('public')->put($path, $binary);
                $exhibit->tag_url = Storage::url($path);
            }
        }

        // 2. Assign Property ID if provided
        if (!empty($validated['property_id'])) {
            $exhibit->property_id = trim($validated['property_id']);
        }

        // 2b. Assign Custodial Status (default always Seized)
        $exhibit->custodial_status = 'Seized';

        // 3. Drug classification
        if ($request->has('drug_classification')) {
            $exhibit->drug_classification = $validated['drug_classification'] ? trim($validated['drug_classification']) : null;
        }

        // 4. Exhibit type
        if ($request->has('exhibit_type')) {
            $exhibit->exhibit_type = $validated['exhibit_type'] ? trim($validated['exhibit_type']) : null;
        }

        // 5. Commodity Name / Category
        if (!empty($validated['commodity_name'])) {
            $exhibit->commodity_name = trim($validated['commodity_name']);
        }
        if (!empty($validated['exhibit_category'])) {
            $exhibit->exhibit_category = trim($validated['exhibit_category']);
        }

        // 6. Exhibit remarks
        if ($request->has('exhibit_remark')) {
            $exhibit->exhibit_remark = $validated['exhibit_remark'] ? trim($validated['exhibit_remark']) : null;
        }

        $exhibit->save();

        // 7. Sync back to parent case docket_data
        $this->syncExhibitBackToDocket($exhibit);

        // 8. Audit Log
        if (class_exists(AuditLogger::class)) {
            AuditLogger::log(
                'VAULT_EXHIBIT_UPDATED',
                "Exhibit #{$exhibit->id} ({$exhibit->commodity_name}) updated in Central Vault: Property ID: {$exhibit->property_id}, Classification: {$exhibit->drug_classification}, Type: {$exhibit->exhibit_type}",
                ['exhibit_id' => $exhibit->id, 'case_number' => $exhibit->case_number]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Exhibit updated and cataloged successfully in Central Vault.',
            'exhibit' => $exhibit->fresh([
                'singleSuspectCase',
                'multipleSuspectCase.suspects',
                'suspect',
                'labResult'
            ])
        ]);
    }

    /**
     * Reflect exhibit updates (QR tag, Property ID, Drug Classification, Exhibit Type)
     * back into the underlying case docket_data JSON.
     */
    protected function syncExhibitBackToDocket(Exhibit $exhibit): void
    {
        try {
            $case = $exhibit->singleSuspectCase ?: $exhibit->multipleSuspectCase;
            if (!$case) {
                $case = SingleSuspectCase::find($exhibit->single_suspect_case_id)
                     ?: MultipleSuspectCase::find($exhibit->multiple_suspect_case_id);
            }
            if (!$case) return;

            $docketData = is_array($case->docket_data) ? $case->docket_data : (json_decode($case->docket_data ?? '[]', true) ?: []);
            $seizures = $docketData['seizures'] ?? [];
            $updated = false;

            foreach ($seizures as $idx => &$sz) {
                $isMatch = (!empty($sz['id']) && $sz['id'] == $exhibit->id)
                        || (!empty($sz['property_id']) && $sz['property_id'] === $exhibit->property_id)
                        || (!empty($sz['exhibit_number']) && $sz['exhibit_number'] === $exhibit->exhibit_number)
                        || (!empty($sz['seizure_name']) && $sz['seizure_name'] === $exhibit->commodity_name);

                if ($isMatch) {
                    if ($exhibit->property_id) $sz['property_id'] = $exhibit->property_id;
                    if ($exhibit->tag_url) $sz['tag_url'] = $exhibit->tag_url;
                    if ($exhibit->drug_classification) $sz['drug_classification'] = $exhibit->drug_classification;
                    if ($exhibit->exhibit_type) $sz['exhibit_type'] = $exhibit->exhibit_type;
                    if ($exhibit->commodity_name) {
                        $sz['seizure_name'] = $exhibit->commodity_name;
                        if (str_contains(strtolower($exhibit->exhibit_category ?? ''), 'drug')) {
                            $sz['seizure_name_drug'] = $exhibit->commodity_name;
                        }
                    }
                    if ($exhibit->exhibit_remark) $sz['exhibit_remark'] = $exhibit->exhibit_remark;
                    $updated = true;
                    break;
                }
            }
            unset($sz);

            if ($updated && \App\Services\SchemaCache::hasColumn($case->getTable(), 'docket_data')) {
                $case->docket_data = $docketData;
                $case->save();
            }
        } catch (\Throwable $e) {
            Log::warning('syncExhibitBackToDocket note: ' . $e->getMessage());
        }
    }

    /**
     * Synchronize any exhibits entered by other units that might not yet be
     * represented in the central exhibits table.
     */
    protected function syncMissingCaseExhibits(): void
    {
        try {
            // 1. Single suspect cases
            $singleCases = SingleSuspectCase::all();
            foreach ($singleCases as $sc) {
                $promotedMulti = MultipleSuspectCase::where('case_number', $sc->case_number)->first();
                if ($promotedMulti) {
                    Exhibit::where('single_suspect_case_id', $sc->id)
                        ->whereNull('multiple_suspect_case_id')
                        ->update(['multiple_suspect_case_id' => $promotedMulti->id, 'case_type' => 'MULTIPLE']);
                }

                $existingCount = Exhibit::where('single_suspect_case_id', $sc->id)->count();
                if ($existingCount === 0) {
                    $wrapped = ForensicCase::wrapRecord($sc);
                    $docket = $wrapped ? $wrapped->docket_data : [];
                    $seizures = $docket['seizures'] ?? [];
                    if (!empty($seizures)) {
                        foreach ($seizures as $idx => $sz) {
                            $cat = $sz['exhibit_category'] ?? ($sz['category'] ?? ($sc->exhibit_category ?? 'General'));
                            $name = $sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? ($sz['commodity_name'] ?? ($sc->commodity_name ?? 'Exhibits')));
                            Exhibit::create([
                                'case_type'                => 'SINGLE',
                                'single_suspect_case_id'   => $sc->id,
                                'multiple_suspect_case_id' => $promotedMulti ? $promotedMulti->id : null,
                                'suspect_id'               => null,
                                'associated_person'        => $sc->suspect_name,
                                'exhibit_number'           => $sz['exhibit_number'] ?? ('EXH-' . sprintf('%02d', $idx + 1)),
                                'property_id'              => $sz['property_id'] ?? null,
                                'tag_url'                  => $sz['tag_url'] ?? null,
                                'exhibit_category'         => $cat,
                                'exhibit_type'             => $sz['exhibit_type'] ?? null,
                                'drug_classification'      => $sz['drug_classification'] ?? null,
                                'commodity_name'           => $name,
                                'seizure_quantity'         => $sz['seizure_quantity'] ?? null,
                                'seizure_packaging'        => $sz['seizure_packaging'] ?? null,
                                'seizure_weight'           => $sz['seizure_weight'] ?? null,
                                'seizure_unit'             => $sz['seizure_unit'] ?? null,
                                'seizure_value'            => $sz['seizure_value'] ?? null,
                                'seizure_concealment'      => $sz['seizure_concealment'] ?? null,
                                'seizure_date'             => $sz['seizure_date'] ?? $sc->incident_date,
                                'seizure_time'             => $sz['seizure_time'] ?? null,
                                'seizure_location'         => $sz['seizure_location'] ?? $sc->raid_location,
                                'seizure_officer'          => $sz['seizure_officer'] ?? $sc->arresting_officer,
                                'seizure_reason'           => $sz['seizure_reason'] ?? null,
                                'source_of_exhibits'       => $sz['source_of_exhibits'] ?? null,
                                'exhibit_remark'           => $sz['exhibit_remark'] ?? null,
                                'lab_remark'               => $sz['lab_remark'] ?? $sc->confirmatory_remark,
                            ]);
                        }
                    }
                }
            }

            // 2. Multiple suspect cases
            $multiCases = MultipleSuspectCase::with('suspects')->get();
            foreach ($multiCases as $mc) {
                $existingCount = Exhibit::where('multiple_suspect_case_id', $mc->id)->count();
                if ($existingCount === 0) {
                    $origSingle = SingleSuspectCase::where('case_number', $mc->case_number)->first();
                    if ($origSingle) {
                        $singleExs = Exhibit::where('single_suspect_case_id', $origSingle->id)->get();
                        if ($singleExs->isNotEmpty()) {
                            foreach ($singleExs as $sex) {
                                $sex->multiple_suspect_case_id = $mc->id;
                                $sex->case_type = 'MULTIPLE';
                                $sex->save();
                            }
                            continue;
                        }
                    }

                    $wrapped = ForensicCase::wrapRecord($mc);
                    $docket = $wrapped ? $wrapped->docket_data : [];
                    $seizures = $docket['seizures'] ?? [];
                    if (!empty($seizures)) {
                        $suspects = $mc->suspects;
                        foreach ($seizures as $idx => $sz) {
                            $cat = $sz['exhibit_category'] ?? ($sz['category'] ?? 'General');
                            $name = $sz['seizure_name_drug'] ?? ($sz['seizure_name'] ?? ($sz['commodity_name'] ?? 'Exhibits'));
                            $owner = $sz['associated_person'] ?? ($sz['suspect_name'] ?? null);
                            $matchedSuspect = $suspects->firstWhere('suspect_name', $owner) ?: $suspects->first();
                            Exhibit::create([
                                'case_type'                => 'MULTIPLE',
                                'single_suspect_case_id'   => null,
                                'multiple_suspect_case_id' => $mc->id,
                                'suspect_id'               => $matchedSuspect ? $matchedSuspect->id : null,
                                'associated_person'        => $matchedSuspect ? $matchedSuspect->suspect_name : ($owner ?: 'All Suspects'),
                                'exhibit_number'           => $sz['exhibit_number'] ?? ('EXH-' . sprintf('%02d', $idx + 1)),
                                'property_id'              => $sz['property_id'] ?? null,
                                'tag_url'                  => $sz['tag_url'] ?? null,
                                'exhibit_category'         => $cat,
                                'exhibit_type'             => $sz['exhibit_type'] ?? null,
                                'drug_classification'      => $sz['drug_classification'] ?? null,
                                'commodity_name'           => $name,
                                'seizure_quantity'         => $sz['seizure_quantity'] ?? null,
                                'seizure_packaging'        => $sz['seizure_packaging'] ?? null,
                                'seizure_weight'           => $sz['seizure_weight'] ?? null,
                                'seizure_unit'             => $sz['seizure_unit'] ?? null,
                                'seizure_value'            => $sz['seizure_value'] ?? null,
                                'seizure_concealment'      => $sz['seizure_concealment'] ?? null,
                                'seizure_date'             => $sz['seizure_date'] ?? $mc->incident_date,
                                'seizure_time'             => $sz['seizure_time'] ?? null,
                                'seizure_location'         => $sz['seizure_location'] ?? $mc->raid_location,
                                'seizure_officer'          => $sz['seizure_officer'] ?? $mc->submitting_officer,
                                'seizure_reason'           => $sz['seizure_reason'] ?? null,
                                'source_of_exhibits'       => $sz['source_of_exhibits'] ?? null,
                                'exhibit_remark'           => $sz['exhibit_remark'] ?? null,
                                'lab_remark'               => $sz['lab_remark'] ?? $mc->confirmatory_remark,
                            ]);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Exhibit vault auto-sync note: ' . $e->getMessage());
        }
    }

    /**
     * Dedicated Live Exhibit Information Page.
     * Scanned from the permanent QR code on the Official Seized Property Tag PNG.
     * Retrieves the latest dynamic custodial status directly from the database.
     */
    public function showLiveTag(Request $request, string $token)
    {
        $exhibit = Exhibit::where('qr_token', $token)
            ->with([
                'singleSuspectCase',
                'multipleSuspectCase.suspects',
                'suspect',
                'labResult'
            ])
            ->firstOrFail();

        if ($request->wantsJson() || $request->query('format') === 'json') {
            return response()->json([
                'success' => true,
                'exhibit' => [
                    'id'                  => $exhibit->id,
                    'property_id'         => $exhibit->property_id,
                    'exhibit_number'      => $exhibit->exhibit_number,
                    'case_number'         => $exhibit->case_number,
                    'custodial_status'    => $exhibit->custodial_status,
                    'commodity_name'      => $exhibit->commodity_name,
                    'exhibit_category'    => $exhibit->exhibit_category,
                    'exhibit_type'        => $exhibit->exhibit_type,
                    'drug_classification' => $exhibit->drug_classification,
                    'seizure_weight'      => $exhibit->seizure_weight,
                    'seizure_unit'        => $exhibit->seizure_unit,
                    'seizure_packaging'   => $exhibit->seizure_packaging,
                    'seizure_location'    => $exhibit->seizure_location,
                    'seizure_officer'     => $exhibit->seizure_officer,
                    'seizure_date'        => $exhibit->seizure_date,
                    'seizure_value'       => $exhibit->seizure_value,
                    'qr_token'            => $exhibit->qr_token,
                    'tag_url'             => $exhibit->tag_url,
                    'verified_at'         => now()->toIso8601String(),
                ]
            ]);
        }

        return redirect('/Evidence_Management/vault?search=' . urlencode($exhibit->property_id ?: $exhibit->exhibit_number));
    }

    /**
     * Update the dynamic custodial status of an exhibit.
     * Invoked when an authorized officer transitions custodial custody
     * (e.g. from Seized to In Vault Custody, Under Laboratory Analysis, Court Evidence, etc.)
     */
    public function updateCustodialStatus(Request $request, $id)
    {
        $exhibit = Exhibit::with([
            'singleSuspectCase',
            'multipleSuspectCase.suspects',
            'suspect'
        ])->findOrFail($id);

        $validated = $request->validate([
            'custodial_status' => ['nullable', 'string'],
            'status_remark'    => ['nullable', 'string', 'max:500'],
        ]);

        $oldStatus = $exhibit->custodial_status ?: 'Seized';
        $newStatus = 'Seized';

        $exhibit->custodial_status = 'Seized';

        if (!empty($validated['status_remark'])) {
            $note = "[Lodgement Note] " . trim($validated['status_remark']);
            $exhibit->exhibit_remark = $exhibit->exhibit_remark 
                ? ($exhibit->exhibit_remark . "\n" . $note) 
                : $note;
        }

        $exhibit->save();

        // Sync back to parent case docket_data
        $this->syncExhibitBackToDocket($exhibit);

        if (class_exists(AuditLogger::class)) {
            AuditLogger::log(
                'EXHIBIT_CUSTODIAL_STATUS_CONFIRMED',
                "Exhibit #{$exhibit->id} ({$exhibit->commodity_name}, Property ID: {$exhibit->property_id}) custodial status confirmed as 'Seized'",
                [
                    'exhibit_id' => $exhibit->id,
                    'case_number' => $exhibit->case_number,
                    'custodial_status' => 'Seized',
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => "Exhibit custodial status is locked to 'Seized'.",
            'custodial_status' => 'Seized',
            'exhibit' => $exhibit->fresh()
        ]);
    }

    /**
     * Helper to construct filtered query for Exhibits Master Report and PDF export.
     */
    private function buildExhibitsReportQuery(Request $request)
    {
        $search = trim($request->input('search', ''));
        $category = trim($request->input('category', 'all'));
        $actionStatus = trim($request->input('action_status', 'all'));
        $period = trim($request->input('period', 'general'));
        if (empty($period) || $period === 'all') {
            $period = 'general';
        }
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $sort = trim($request->input('sort', 'newest'));

        $query = Exhibit::with([
            'singleSuspectCase',
            'multipleSuspectCase.suspects',
            'suspect',
            'labResult'
        ]);

        // Period preset filter
        if (in_array(strtolower($period), ['weekly', 'week'])) {
            $query->where('created_at', '>=', now()->subDays(7));
        } elseif (in_array(strtolower($period), ['monthly', 'month'])) {
            $query->where('created_at', '>=', now()->subDays(30));
        } elseif (in_array(strtolower($period), ['annual', 'annually', 'year', 'yearly'])) {
            $query->where('created_at', '>=', now()->subYear());
        }

        // Custom Date Range filter
        if (!empty($fromDate)) {
            $query->where(function ($q) use ($fromDate) {
                $q->where(function ($sub) use ($fromDate) {
                    $sub->whereNotNull('seizure_date')
                        ->where('seizure_date', '!=', '')
                        ->whereDate('seizure_date', '>=', $fromDate);
                })->orWhere(function ($sub) use ($fromDate) {
                    $sub->where(function ($empty) {
                        $empty->whereNull('seizure_date')->orWhere('seizure_date', '');
                    })->whereDate('created_at', '>=', $fromDate);
                });
            });
        }
        if (!empty($toDate)) {
            $query->where(function ($q) use ($toDate) {
                $q->where(function ($sub) use ($toDate) {
                    $sub->whereNotNull('seizure_date')
                        ->where('seizure_date', '!=', '')
                        ->whereDate('seizure_date', '<=', $toDate);
                })->orWhere(function ($sub) use ($toDate) {
                    $sub->where(function ($empty) {
                        $empty->whereNull('seizure_date')->orWhere('seizure_date', '');
                    })->whereDate('created_at', '<=', $toDate);
                });
            });
        }

        // Action / Intake Status Filter
        if ($actionStatus === 'pending') {
            $query->needsQrOrClassification();
        } elseif ($actionStatus === 'unassigned_qr') {
            $query->unassignedQr();
        } elseif ($actionStatus === 'unclassified') {
            $query->unclassified();
        } elseif ($actionStatus === 'completed') {
            $query->completedQrAndClassification();
        }

        // Category Filter
        if ($category !== 'all' && $category !== '') {
            $catLower = strtolower($category);
            if (str_contains($catLower, 'drug')) {
                $query->where(function ($q) {
                    $q->where('exhibit_category', 'LIKE', '%drug%')
                      ->orWhere('exhibit_category', 'LIKE', '%narcotic%')
                      ->orWhereNotNull('drug_classification');
                });
            } elseif (str_contains($catLower, 'weapon')) {
                $query->where(function ($q) {
                    $q->where('exhibit_category', 'LIKE', '%weapon%')
                      ->orWhere('exhibit_category', 'LIKE', '%firearm%')
                      ->orWhereNotNull('wpn_model')
                      ->orWhereNotNull('wpn_serial_no');
                });
            } elseif (str_contains($catLower, 'vehic')) {
                $query->where(function ($q) {
                    $q->where('exhibit_category', 'LIKE', '%vehic%')
                      ->orWhereNotNull('veh_make')
                      ->orWhereNotNull('veh_reg_no');
                });
            } elseif (str_contains($catLower, 'devic')) {
                $query->where(function ($q) {
                    $q->where('exhibit_category', 'LIKE', '%devic%')
                      ->orWhere('exhibit_category', 'LIKE', '%electron%')
                      ->orWhere('exhibit_category', 'LIKE', '%digital%')
                      ->orWhereNotNull('dev_model')
                      ->orWhereNotNull('dev_imei1');
                });
            } elseif (str_contains($catLower, 'money') || str_contains($catLower, 'cash')) {
                $query->where(function ($q) {
                    $q->where('exhibit_category', 'LIKE', '%money%')
                      ->orWhere('exhibit_category', 'LIKE', '%cash%')
                      ->orWhere('exhibit_category', 'LIKE', '%currency%')
                      ->orWhere('commodity_name', 'LIKE', '%currency%')
                      ->orWhere('commodity_name', 'LIKE', '%cash%');
                });
            } else {
                $query->where('exhibit_category', 'LIKE', "%{$category}%");
            }
        }

        // Search Filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('exhibit_number', 'LIKE', "%{$search}%")
                  ->orWhere('commodity_name', 'LIKE', "%{$search}%")
                  ->orWhere('property_id', 'LIKE', "%{$search}%")
                  ->orWhere('qr_token', 'LIKE', "%{$search}%")
                  ->orWhere('exhibit_category', 'LIKE', "%{$search}%")
                  ->orWhere('drug_classification', 'LIKE', "%{$search}%")
                  ->orWhere('exhibit_type', 'LIKE', "%{$search}%")
                  ->orWhere('packaging_type', 'LIKE', "%{$search}%")
                  ->orWhere('veh_make', 'LIKE', "%{$search}%")
                  ->orWhere('veh_reg_no', 'LIKE', "%{$search}%")
                  ->orWhere('wpn_model', 'LIKE', "%{$search}%")
                  ->orWhere('wpn_serial_no', 'LIKE', "%{$search}%")
                  ->orWhere('dev_model', 'LIKE', "%{$search}%")
                  ->orWhere('dev_serial_no', 'LIKE', "%{$search}%")
                  ->orWhere('dev_imei1', 'LIKE', "%{$search}%")
                  ->orWhereHas('singleSuspectCase', function ($sub) use ($search) {
                      $sub->where('case_number', 'LIKE', "%{$search}%")
                          ->orWhere('suspect_name', 'LIKE', "%{$search}%")
                          ->orWhere('suspect_alias', 'LIKE', "%{$search}%")
                          ->orWhere('ghana_card', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('multipleSuspectCase', function ($sub) use ($search) {
                      $sub->where('case_number', 'LIKE', "%{$search}%")
                          ->orWhereHas('suspects', function ($susQuery) use ($search) {
                              $susQuery->where('suspect_name', 'LIKE', "%{$search}%")
                                       ->orWhere('suspect_alias', 'LIKE', "%{$search}%")
                                       ->orWhere('ghana_card', 'LIKE', "%{$search}%");
                          });
                  })
                  ->orWhereHas('suspect', function ($sub) use ($search) {
                      $sub->where('suspect_name', 'LIKE', "%{$search}%")
                          ->orWhere('suspect_alias', 'LIKE', "%{$search}%")
                          ->orWhere('ghana_card', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Sorting
        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc')->orderBy('id', 'asc');
                break;
            case 'value_desc':
                $query->orderByRaw('CAST(COALESCE(seizure_value, 0) AS DECIMAL(15,2)) DESC');
                break;
            case 'value_asc':
                $query->orderByRaw('CAST(COALESCE(seizure_value, 0) AS DECIMAL(15,2)) ASC');
                break;
            case 'name_asc':
                $query->orderBy('commodity_name', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc')->orderBy('id', 'desc');
                break;
        }

        return [$query, $search, $category, $actionStatus, $period, $fromDate, $toDate, $sort];
    }

    /**
     * Display the Evidence Management Unit Central Exhibits & Seized Property Master Report.
     */
    public function report(Request $request)
    {
        $this->syncMissingCaseExhibits();

        [$query, $search, $category, $actionStatus, $period, $fromDate, $toDate, $sort] = $this->buildExhibitsReportQuery($request);

        $perPage = (int) $request->input('per_page', 50);
        if ($perPage < 10) $perPage = 50;
        if ($perPage > 250) $perPage = 250;

        $exhibits = $query->paginate($perPage)->withQueryString();

        // High-level telemetry stats across entire system
        $totalExhibitsCount = Exhibit::count();
        $totalSeizureValue = Exhibit::selectRaw('SUM(CAST(COALESCE(seizure_value, 0) AS DECIMAL(15,2))) as total')->value('total') ?? 0;
        $taggedQrCount = Exhibit::whereNotNull('property_id')->where('property_id', '!=', '')->count();
        $classifiedCount = Exhibit::completedQrAndClassification()->count();
        $pendingCount = Exhibit::countPendingQrOrClassification();

        $drugsCount = Exhibit::where(function ($q) {
            $q->where('exhibit_category', 'LIKE', '%drug%')
              ->orWhere('exhibit_category', 'LIKE', '%narcotic%')
              ->orWhereNotNull('drug_classification');
        })->count();

        $weaponsCount = Exhibit::where(function ($q) {
            $q->where('exhibit_category', 'LIKE', '%weapon%')
              ->orWhere('exhibit_category', 'LIKE', '%firearm%')
              ->orWhereNotNull('wpn_model')
              ->orWhereNotNull('wpn_serial_no');
        })->count();

        $vehiclesCount = Exhibit::where(function ($q) {
            $q->where('exhibit_category', 'LIKE', '%vehic%')
              ->orWhereNotNull('veh_make')
              ->orWhereNotNull('veh_reg_no');
        })->count();

        $devicesCount = Exhibit::where(function ($q) {
            $q->where('exhibit_category', 'LIKE', '%devic%')
              ->orWhere('exhibit_category', 'LIKE', '%electron%')
              ->orWhere('exhibit_category', 'LIKE', '%digital%')
              ->orWhereNotNull('dev_model')
              ->orWhereNotNull('dev_imei1');
        })->count();

        $currencyCount = Exhibit::where(function ($q) {
            $q->where('exhibit_category', 'LIKE', '%money%')
              ->orWhere('exhibit_category', 'LIKE', '%cash%')
              ->orWhere('exhibit_category', 'LIKE', '%currency%')
              ->orWhere('commodity_name', 'LIKE', '%currency%')
              ->orWhere('commodity_name', 'LIKE', '%cash%');
        })->count();

        $totalCasesCount = SingleSuspectCase::count() + MultipleSuspectCase::count();

        return view('Evidence_Management.report', compact(
            'exhibits',
            'search',
            'category',
            'actionStatus',
            'period',
            'fromDate',
            'toDate',
            'sort',
            'perPage',
            'totalExhibitsCount',
            'totalSeizureValue',
            'taggedQrCount',
            'classifiedCount',
            'pendingCount',
            'drugsCount',
            'weaponsCount',
            'vehiclesCount',
            'devicesCount',
            'currencyCount',
            'totalCasesCount'
        ));
    }

    /**
     * Dedicated printable / PDF Master Audit Dossier view for Evidence Management Unit.
     */
    public function reportPdf(Request $request)
    {
        $this->syncMissingCaseExhibits();

        [$query, $search, $category, $actionStatus, $period, $fromDate, $toDate, $sort] = $this->buildExhibitsReportQuery($request);

        // Fetch filtered records up to 1000 items for official PDF ledger
        $exhibits = $query->limit(1000)->get();

        $totalFilteredCount = $exhibits->count();
        $totalFilteredValue = $exhibits->sum(fn($e) => (float)($e->seizure_value ?? 0));
        $taggedQrCount = $exhibits->filter(fn($e) => !empty($e->property_id))->count();

        // Segregate exhibits into 3 distinct operational tables:
        // 1. Drug Exhibits
        // 2. Physical Assets (Non-drug exhibits)
        // 3. Money Received / Seized Currency
        $drugExhibits = $exhibits->filter(function ($e) {
            $cat = strtolower($e->exhibit_category ?? '');
            $name = strtolower($e->commodity_name ?? '');
            // Guard: ensure currency is never classified as drugs
            if (str_contains($cat, 'money') || str_contains($cat, 'cash') || str_contains($cat, 'currency') || str_contains($name, 'banknote')) {
                return false;
            }
            return str_contains($cat, 'drug') 
                || str_contains($cat, 'narcotic') 
                || !empty($e->drug_classification)
                || str_contains($name, 'cocaine')
                || str_contains($name, 'heroin')
                || str_contains($name, 'cannabis')
                || str_contains($name, 'weed')
                || str_contains($name, 'meth')
                || str_contains($name, 'tramadol')
                || str_contains($name, 'ephedrine')
                || str_contains($name, 'hashish');
        })->values();

        $drugIds = $drugExhibits->pluck('id')->all();

        $moneyExhibits = $exhibits->filter(function ($e) use ($drugIds) {
            if (in_array($e->id, $drugIds)) {
                return false;
            }
            $cat = strtolower($e->exhibit_category ?? '');
            $name = strtolower($e->commodity_name ?? '');
            return str_contains($cat, 'money') 
                || str_contains($cat, 'cash') 
                || str_contains($cat, 'currency') 
                || str_contains($name, 'currency') 
                || str_contains($name, 'cash')
                || str_contains($name, 'cedis')
                || str_contains($name, 'dollar')
                || str_contains($name, 'banknote')
                || str_contains($name, 'funds')
                || str_contains($name, 'money');
        })->values();

        $moneyIds = $moneyExhibits->pluck('id')->all();

        // Assets = all non-drug exhibits and non-money exhibits (Vehicles, Weapons, Electronics, General Equipment, etc.)
        $assetExhibits = $exhibits->filter(function ($e) use ($drugIds, $moneyIds) {
            return !in_array($e->id, $drugIds) && !in_array($e->id, $moneyIds);
        })->values();

        $drugTotalValue = $drugExhibits->sum(fn($e) => (float)($e->seizure_value ?? 0));
        $assetTotalValue = $assetExhibits->sum(fn($e) => (float)($e->seizure_value ?? 0));
        $moneyTotalValue = $moneyExhibits->sum(fn($e) => (float)($e->seizure_value ?? 0));

        // Signatories (Evidence Unit Head & Forensic Directorate Head)
        $dbEvidenceHead = \App\Models\User::where(function($q) {
                            $q->where('unit', 'LIKE', '%Evidence%')
                              ->orWhere('unit', 'LIKE', '%Custody%');
                        })->where('is_head', true)->first()
                        ?? \App\Models\User::where('unit', 'LIKE', '%Evidence%')->first();

        $dbDeptHead = \App\Models\User::where('unit', 'LIKE', '%Directorate%')->where('is_head', true)->first()
                   ?? \App\Models\User::where('unit', 'LIKE', '%Forensic Head%')->first()
                   ?? \App\Models\User::where('is_head', true)->first();

        return view('Evidence_Management.report_pdf', compact(
            'exhibits',
            'drugExhibits',
            'assetExhibits',
            'moneyExhibits',
            'drugTotalValue',
            'assetTotalValue',
            'moneyTotalValue',
            'search',
            'category',
            'actionStatus',
            'period',
            'fromDate',
            'toDate',
            'sort',
            'totalFilteredCount',
            'totalFilteredValue',
            'taggedQrCount',
            'dbEvidenceHead',
            'dbDeptHead'
        ));
    }
}

