<?php

namespace App\Http\Controllers;

use App\Models\Exhibit;
use App\Models\SingleSuspectCase;
use App\Models\MultipleSuspectCase;
use App\Models\CaseSuspect;
use App\Models\ForensicCase;
use App\Models\EvidenceReportNote;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
            $query->unassignedOrAwaitingSuspect();
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
     * Dedicated page for adding an exhibit without a suspect.
     * Displays only Section 4: Seizures (Commodities) form.
     */
    public function createUnassignedExhibit()
    {
        return redirect()->route('evidence_management.vault');
    }

    /**
     * Store unassigned exhibit(s) without a suspect into the Exhibit Vault.
     */
    public function storeUnassignedExhibits(Request $request)
    {
        $seizures = $request->input('seizures', []);
        if (empty($seizures) || !is_array($seizures)) {
            return response()->json([
                'success' => false,
                'message' => 'No exhibit details provided. Please enter at least one seized commodity in Section 4.'
            ], 422);
        }

        $topLevelPhotos = Exhibit::decodePhotosValue($request->input('evidence_photos', []));

        // Create a corresponding SingleSuspectCase dossier so the seized exhibit is directly reviewable in show_review
        $firstSz = $seizures[0];
        $firstCommodity = trim($firstSz['commodity_name'] ?? ($firstSz['name'] ?? ($firstSz['seizure_name'] ?? '')));
        if ($firstCommodity === '') {
            $firstCommodity = trim($firstSz['exhibit_category'] ?? 'Seized Commodity');
        }
        $authUser = auth()->user();
        $caseNumber = app(\App\Services\CaseNumberService::class)->generate();

        $singleCase = SingleSuspectCase::create([
            'case_number'             => $caseNumber,
            'title'                   => 'Seized Exhibit: ' . $firstCommodity . ' (Awaiting Suspect)',
            'unit_name'               => 'Evidence Management',
            'unit_id'                 => $authUser ? $authUser->unit_id : null,
            'user_id'                 => $authUser ? $authUser->id : null,
            'controller_user_id'      => $authUser ? $authUser->id : null,
            'arresting_officer'       => $firstSz['seizure_officer'] ?? ($authUser->fullname ?? 'Evidence Custodian'),
            'submitting_officer'      => $authUser->fullname ?? 'Evidence Custodian',
            'suspect_name'            => 'Unassigned (No Suspect)',
            'status'                  => 'Pending Approval',
            'case_status'             => 'Under Investigation',
            'priority'                => 'MEDIUM',
            'raid_location'           => $firstSz['raid_location'] ?? null,
            'offense_description'     => 'Direct seized exhibit lodgement into vault without suspect. Pending suspect identification and arrest linking.',
            'description_of_exhibits' => $firstCommodity,
            'incident_date'           => $firstSz['seizure_date'] ?? date('Y-m-d'),
            'hash_log'                => 'HASH-' . strtoupper(substr(md5(uniqid()), 0, 8)) . '-VERIFIED',
        ]);

        $savedExhibits = [];
        foreach ($seizures as $idx => $sz) {
            $commodityName = trim($sz['commodity_name'] ?? ($sz['name'] ?? ($sz['seizure_name'] ?? '')));
            if ($commodityName === '') {
                $commodityName = trim($sz['exhibit_category'] ?? 'Seized Commodity');
            }

            $propertyId = !empty($sz['property_id']) ? trim($sz['property_id']) : null;
            if (empty($propertyId)) {
                do {
                    $propertyId = 'NACOC/EMU/' . random_int(10000, 99999);
                } while (Exhibit::where('property_id', $propertyId)->exists());
            }

            $cat = trim($sz['exhibit_category'] ?? 'General Exhibit');
            $qty = $sz['seizure_quantity'] ?? null;
            $isMoney = in_array(strtolower($cat), ['money', 'currency', 'cash']);
            $amount = (!empty($sz['amount']) && $sz['amount'] !== 'N/A') ? $sz['amount'] : ($isMoney ? (!empty($qty) ? $qty : ((!empty($sz['seizure_value']) && $sz['seizure_value'] !== 'N/A') ? $sz['seizure_value'] : null)) : null);

            $rowPhotos = Exhibit::decodePhotosValue($sz['evidence_photos'] ?? []);
            $photos = !empty($rowPhotos) ? $rowPhotos : $topLevelPhotos;

            $exhibit = Exhibit::create([
                'case_type'                 => 'SINGLE',
                'single_suspect_case_id'    => $singleCase->id,
                'multiple_suspect_case_id'  => null,
                'suspect_id'                => null,
                'associated_person'         => 'Unassigned (No Suspect)',
                'property_id'               => $propertyId,
                'exhibit_number'            => $sz['exhibit_number'] ?? null,
                'lab_exhibit_no'            => $sz['lab_exhibit_no'] ?? null,
                'exhibit_category'          => $cat,
                'exhibit_type'              => $sz['exhibit_type'] ?? null,
                'drug_classification'       => $sz['drug_classification'] ?? null,
                'commodity_name'            => $commodityName,
                'contents'                  => $sz['contents'] ?? null,
                'description_of_exhibits'   => $sz['description_of_exhibits'] ?? null,
                'other_exhibit_description' => $sz['other_exhibit_description'] ?? null,
                'seizure_quantity'          => $qty,
                'amount'                    => $amount,
                'seizure_packaging'         => $sz['seizure_packaging'] ?? null,
                'seizure_weight'            => $sz['seizure_weight'] ?? null,
                'seizure_unit'              => $sz['seizure_unit'] ?? null,
                'seizure_value'             => $sz['seizure_value'] ?? null,
                'seizure_concealment'       => $sz['seizure_concealment'] ?? null,
                'seizure_date'              => $sz['seizure_date'] ?? date('Y-m-d'),
                'seizure_time'              => $sz['seizure_time'] ?? date('H:i'),
                'seizure_location'          => $sz['seizure_location'] ?? null,
                'seizure_officer'           => $sz['seizure_officer'] ?? (auth()->user()->fullname ?? null),
                'investigating_officer'     => $sz['investigating_officer'] ?? null,
                'source_requesting_unit'    => $sz['source_requesting_unit'] ?? 'Evidence Management',
                'seizure_reason'            => $sz['seizure_reason'] ?? null,
                'source_of_exhibits'        => $sz['source_of_exhibits'] ?? null,
                'exhibit_remark'            => $sz['exhibit_remark'] ?? null,
                'chain_of_custody'          => $sz['chain_of_custody'] ?? ($request->input('chain_of_custody') ?? null),
                'lab_remark'                => $sz['lab_remark'] ?? null,
                'custodial_status'          => 'Seized',
                'evidence_photos'           => $photos,
                'veh_make'                  => $sz['veh_make'] ?? ($sz['veh']['make'] ?? null),
                'veh_model'                 => $sz['veh_model'] ?? ($sz['veh']['model'] ?? null),
                'veh_type'                  => $sz['veh_type'] ?? ($sz['veh']['type'] ?? null),
                'veh_year'                  => $sz['veh_year'] ?? ($sz['veh']['year'] ?? null),
                'veh_colour'                => $sz['veh_colour'] ?? ($sz['veh']['colour'] ?? null),
                'veh_reg_no'                => $sz['veh_reg_no'] ?? ($sz['veh']['reg_no'] ?? null),
                'veh_vin'                   => $sz['veh_vin'] ?? ($sz['veh']['vin'] ?? null),
                'veh_engine_no'             => $sz['veh_engine_no'] ?? ($sz['veh']['engine_no'] ?? null),
                'veh_country_reg'           => $sz['veh_country_reg'] ?? ($sz['veh']['country_reg'] ?? null),
                'veh_owner'                 => $sz['veh_owner'] ?? ($sz['veh']['owner'] ?? null),
                'veh_driver'                => $sz['veh_driver'] ?? ($sz['veh']['driver'] ?? null),
                'veh_owner_contact'         => $sz['veh_owner_contact'] ?? ($sz['veh']['owner_contact'] ?? null),
                'veh_docs_available'        => $sz['veh_docs_available'] ?? ($sz['veh']['docs_available'] ?? null),
                'veh_doc_ref'               => $sz['veh_doc_ref'] ?? ($sz['veh']['doc_ref'] ?? null),
                'veh_condition'             => $sz['veh_condition'] ?? ($sz['veh']['condition'] ?? null),
                'veh_fuel'                  => $sz['veh_fuel'] ?? ($sz['veh']['fuel'] ?? null),
                'veh_keys_recv'             => $sz['veh_keys_recv'] ?? ($sz['veh']['keys_recv'] ?? null),
                'veh_key_count'             => $sz['veh_key_count'] ?? ($sz['veh']['key_count'] ?? null),
                'veh_damage'                => $sz['veh_damage'] ?? ($sz['veh']['damage'] ?? null),
                'veh_mods'                  => $sz['veh_mods'] ?? ($sz['veh']['mods'] ?? null),
                'veh_features'              => $sz['veh_features'] ?? ($sz['veh']['features'] ?? null),
                'wpn_model'                 => $sz['wpn_model'] ?? ($sz['wpn']['model'] ?? null),
                'wpn_serial_no'             => $sz['wpn_serial_no'] ?? ($sz['wpn']['serial_no'] ?? null),
                'wpn_calibre'               => $sz['wpn_calibre'] ?? ($sz['wpn']['calibre'] ?? null),
                'wpn_colour'                => $sz['wpn_colour'] ?? ($sz['wpn']['colour'] ?? null),
                'wpn_size'                  => $sz['wpn_size'] ?? ($sz['wpn']['size'] ?? null),
                'wpn_marks'                 => $sz['wpn_marks'] ?? ($sz['wpn']['marks'] ?? null),
                'wpn_location_found'        => $sz['wpn_location_found'] ?? ($sz['wpn']['location_found'] ?? null),
                'wpn_loaded_status'         => $sz['wpn_loaded_status'] ?? ($sz['wpn']['loaded_status'] ?? null),
                'wpn_condition'             => $sz['wpn_condition'] ?? ($sz['wpn']['condition'] ?? null),
                'dev_model'                 => $sz['dev_model'] ?? null,
                'dev_serial_no'             => $sz['dev_serial_no'] ?? null,
                'dev_imei1'                 => $sz['dev_imei1'] ?? null,
                'dev_imei2'                 => $sz['dev_imei2'] ?? null,
                'dev_mac_address'           => $sz['dev_mac_address'] ?? null,
                'dev_storage'               => $sz['dev_storage'] ?? null,
                'dev_colour'                => $sz['dev_colour'] ?? null,
                'dev_marks'                 => $sz['dev_marks'] ?? null,
                'ammunition_details'        => $sz['ammunition_details'] ?? null,
            ]);

            $savedExhibits[] = $exhibit;
        }

        return response()->json([
            'success'       => true,
            'message'       => count($savedExhibits) . ' exhibit(s) logged into vault successfully without suspect.',
            'case_id'       => $singleCase->id,
            'case_number'   => $singleCase->case_number,
            'review_url'    => url('/cases/' . urlencode($singleCase->case_number) . '/review?from=Evidence_Management'),
            'em_review_url' => url('/Evidence_Management/cases/' . urlencode($singleCase->case_number) . '/review'),
            'exhibits'      => $savedExhibits,
        ]);
    }

    /**
     * Get all unassigned seized exhibits (exhibits seized without suspects),
     * filtered to the calling unit's domain when a unit parameter is supplied.
     */
    public function getUnassignedExhibits(Request $request)
    {
        $query = Exhibit::with(['singleSuspectCase.user', 'multipleSuspectCase.user', 'labResult'])
            ->unassignedOrAwaitingSuspect();

        $targetUnit = trim((string) ($request->query('unit') ?? $request->query('target_unit') ?? ''));
        if ($targetUnit !== '') {
            $query->forCreateUnit($targetUnit);
        }

        if ($request->filled('q')) {
            $search = trim($request->input('q'));
            $query->where(function ($q) use ($search) {
                $q->where('commodity_name', 'LIKE', "%{$search}%")
                  ->orWhere('property_id', 'LIKE', "%{$search}%")
                  ->orWhere('exhibit_number', 'LIKE', "%{$search}%")
                  ->orWhere('exhibit_category', 'LIKE', "%{$search}%")
                  ->orWhere('seizure_location', 'LIKE', "%{$search}%");
            });
        }

        $exhibits = $query->orderByDesc('id')->get();

        return response()->json([
            'success'  => true,
            'exhibits' => $exhibits->map(fn ($e) => $e->toManifestPayloadArray())->values(),
        ]);
    }

    /**
     * API endpoint returning cross-unit exhibit and suspect alerts for Lab Analysis ('lab')
     * or Digital Forensics ('digital').
     */
    public function getUnitExhibitAlerts(Request $request)
    {
        $domain = (string) ($request->query('domain') ?? '');
        $domainKey = Exhibit::normalizeCreateUnitKey($domain) ?: strtolower(trim($domain));

        return response()->json([
            'success' => true,
            'domain'  => $domainKey,
            'alerts'  => static::buildUnitExhibitAlerts($domainKey),
        ]);
    }

    /**
     * Build live alerts for cross-unit no-suspect exhibits and suspect cases associated with
     * drugs ('lab') or electronic devices ('digital').
     */
    public static function buildUnitExhibitAlerts(string $domain): array
    {
        $domainKey = Exhibit::normalizeCreateUnitKey($domain) ?: strtolower(trim($domain));
        if (!in_array($domainKey, ['lab', 'digital'], true)) {
            return [];
        }

        $placeholderSuspectNames = [
            '', 'unknown', 'unassigned', 'no suspect', 'none', 'n/a', 'null', '-',
            'all suspects', 'no suspect assigned', 'unknown / unassigned',
        ];

        $isMatchingDomain = fn (Exhibit $ex): bool => $domainKey === 'lab'
            ? $ex->isDrugRelated()
            : $ex->isElectronicDeviceRelated();

        $alerts = collect();

        // 1. Cross-unit exhibits entered without a suspect matching this unit's domain
        $unassignedExhibits = Exhibit::with(['singleSuspectCase.user', 'multipleSuspectCase.user', 'labResult'])
            ->unassignedOrAwaitingSuspect()
            ->orderByDesc('id')
            ->limit(60)
            ->get();

        foreach ($unassignedExhibits as $exhibit) {
            if (!$isMatchingDomain($exhibit) || $exhibit->isLockedByPreliminaryReport()) {
                continue;
            }

            $sourceUnit = $exhibit->enteredByUnitName();
            $sourceUnitKey = Exhibit::normalizeCreateUnitKey($sourceUnit);
            if ($sourceUnit === '' || $sourceUnitKey === $domainKey) {
                continue;
            }

            $creator = $exhibit->singleSuspectCase?->user ?? $exhibit->multipleSuspectCase?->user;
            $alerts->push([
                'id'               => 'exhibit-' . $exhibit->id,
                'alert_type'       => 'unassigned_exhibit',
                'domain'           => $domainKey,
                'exhibit_id'       => $exhibit->id,
                'property_id'      => $exhibit->property_id,
                'exhibit_number'   => $exhibit->exhibit_number,
                'commodity_name'   => $exhibit->commodity_name,
                'exhibit_category' => $exhibit->exhibit_category,
                'source_unit'      => $sourceUnit,
                'entered_by_name'  => $creator?->fullname ?? ($creator?->name ?? ''),
                'case_number'      => $exhibit->singleSuspectCase?->case_number,
                'created_at_iso'   => optional($exhibit->created_at)->toIso8601String(),
                'created_at_human' => optional($exhibit->created_at)->diffForHumans() ?? '',
                'timestamp'        => optional($exhibit->created_at)->timestamp ?? 0,
            ]);
        }

        // 2a. Single-suspect cases from another unit associated with drugs / electronic devices
        $singleCases = SingleSuspectCase::with(['exhibits', 'user'])
            ->orderByDesc('id')
            ->limit(60)
            ->get();

        foreach ($singleCases as $case) {
            $suspectName = trim((string) $case->suspect_name);
            if (in_array(strtolower($suspectName), $placeholderSuspectNames, true)) {
                continue;
            }

            $sourceUnit = trim((string) ($case->unit_name ?: ($case->user?->unit ?? '')));
            $sourceUnitKey = Exhibit::normalizeCreateUnitKey($sourceUnit);
            if ($sourceUnit === '' || $sourceUnitKey === $domainKey) {
                continue;
            }

            $matchingExhibits = $case->exhibits->filter($isMatchingDomain)->values();
            if ($matchingExhibits->isEmpty()) {
                continue;
            }

            $reviewUrl = $domainKey === 'lab'
                ? route('lab.cases.review', ['id' => $case->id])
                : route('digital.cases.review', ['id' => $case->id]);

            $alerts->push([
                'id'               => 'single-suspect-' . $case->id,
                'alert_type'       => 'suspect_associated',
                'domain'           => $domainKey,
                'case_id'          => $case->id,
                'case_number'      => $case->case_number,
                'suspect_name'     => $suspectName,
                'source_unit'      => $sourceUnit,
                'entered_by_name'  => $case->user?->fullname ?? ($case->user?->name ?? ''),
                'exhibits_summary' => $matchingExhibits->map(fn ($e) => trim((string) ($e->commodity_name ?: $e->exhibit_category)))->filter()->unique()->values()->all(),
                'exhibit_count'    => $matchingExhibits->count(),
                'review_url'       => $reviewUrl,
                'created_at_iso'   => optional($case->created_at)->toIso8601String(),
                'created_at_human' => optional($case->created_at)->diffForHumans() ?? '',
                'timestamp'        => optional($case->created_at)->timestamp ?? 0,
            ]);
        }

        // 2b. Multi-suspect cases from another unit where a suspect is associated with drugs / electronic devices
        $multiSuspects = CaseSuspect::with(['multipleCase.user', 'multipleCase.exhibits', 'exhibits'])
            ->orderByDesc('id')
            ->limit(60)
            ->get();

        foreach ($multiSuspects as $suspect) {
            $case = $suspect->multipleCase;
            if (!$case) {
                continue;
            }

            $suspectName = trim((string) $suspect->suspect_name);
            if (in_array(strtolower($suspectName), $placeholderSuspectNames, true)) {
                continue;
            }

            $sourceUnit = trim((string) ($case->unit_name ?: ($case->user?->unit ?? '')));
            $sourceUnitKey = Exhibit::normalizeCreateUnitKey($sourceUnit);
            if ($sourceUnit === '' || $sourceUnitKey === $domainKey) {
                continue;
            }

            $candidateExhibits = $suspect->exhibits->isNotEmpty()
                ? $suspect->exhibits
                : $case->exhibits->filter(function ($ex) use ($suspectName) {
                    $assoc = strtolower(trim((string) $ex->associated_person));
                    return $assoc === '' || $assoc === strtolower($suspectName);
                });

            $matchingExhibits = $candidateExhibits->filter($isMatchingDomain)->values();
            if ($matchingExhibits->isEmpty()) {
                continue;
            }

            $reviewUrl = $domainKey === 'lab'
                ? route('lab.cases.review', ['id' => $case->id])
                : route('digital.cases.review', ['id' => $case->id]);

            $alerts->push([
                'id'               => 'multi-suspect-' . $suspect->id,
                'alert_type'       => 'suspect_associated',
                'domain'           => $domainKey,
                'case_id'          => $case->id,
                'case_number'      => $case->case_number,
                'suspect_name'     => $suspectName,
                'source_unit'      => $sourceUnit,
                'entered_by_name'  => $case->user?->fullname ?? ($case->user?->name ?? ''),
                'exhibits_summary' => $matchingExhibits->map(fn ($e) => trim((string) ($e->commodity_name ?: $e->exhibit_category)))->filter()->unique()->values()->all(),
                'exhibit_count'    => $matchingExhibits->count(),
                'review_url'       => $reviewUrl,
                'created_at_iso'   => optional($suspect->created_at)->toIso8601String(),
                'created_at_human' => optional($suspect->created_at)->diffForHumans() ?? '',
                'timestamp'        => optional($suspect->created_at)->timestamp ?? 0,
            ]);
        }

        return $alerts->sortByDesc('timestamp')->take(20)->values()->all();
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
            if ($caseNumber === 'UNASSIGNED') {
                $saved = [];
                foreach (array_values($validated['tags']) as $i => $tag) {
                    if (empty($tag['property_id'])) continue;
                    $targetExhibit = Exhibit::where('property_id', trim($tag['property_id']))->first();
                    if (!$targetExhibit) continue;
                    $binary = base64_decode(substr($tag['image'], strlen('data:image/png;base64,')), true);
                    if ($binary === false || strlen($binary) > 3 * 1024 * 1024 || !@getimagesizefromstring($binary)) {
                        continue;
                    }
                    $slug = preg_replace('/[^A-Za-z0-9_-]+/', '_', $targetExhibit->property_id);
                    $path = 'exhibit_tags/unassigned_' . $slug . '_' . substr(sha1($binary), 0, 8) . '.png';
                    \Illuminate\Support\Facades\Storage::disk('public')->put($path, $binary);
                    $url = \Illuminate\Support\Facades\Storage::url($path);
                    $targetExhibit->tag_url = $url;
                    $targetExhibit->save();
                    $saved[] = $url;
                }
                return response()->json(['success' => true, 'urls' => $saved]);
            }
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
            'chain_of_custody'    => ['nullable', 'string', 'max:5000'],
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

        // 6b. Chain of Custody
        if ($request->has('chain_of_custody')) {
            $exhibit->chain_of_custody = $validated['chain_of_custody'] ? trim($validated['chain_of_custody']) : null;
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
                    if ($exhibit->chain_of_custody !== null) $sz['chain_of_custody'] = $exhibit->chain_of_custody;
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
                                'exhibit_number'           => $sz['exhibit_number'] ?? null,
                                'lab_exhibit_no'           => $sz['lab_exhibit_no'] ?? null,
                                'property_id'              => $sz['property_id'] ?? null,
                                'tag_url'                  => $sz['tag_url'] ?? null,
                                'exhibit_category'         => $cat,
                                'exhibit_type'             => $sz['exhibit_type'] ?? null,
                                'drug_classification'      => $sz['drug_classification'] ?? null,
                                'commodity_name'           => $name,
                                'seizure_quantity'         => $sz['seizure_quantity'] ?? null,
                                'amount'                   => $sz['amount'] ?? (in_array(strtolower(trim((string)$cat)), ['money', 'currency', 'cash']) ? ($sz['seizure_quantity'] ?? null) : null),
                                'seizure_packaging'        => $sz['seizure_packaging'] ?? null,
                                'seizure_weight'           => $sz['seizure_weight'] ?? null,
                                'seizure_unit'             => $sz['seizure_unit'] ?? null,
                                'seizure_value'            => $sz['seizure_value'] ?? null,
                                'seizure_concealment'      => $sz['seizure_concealment'] ?? null,
                                'seizure_date'             => $sz['seizure_date'] ?? $sc->incident_date,
                                'seizure_time'             => $sz['seizure_time'] ?? null,
                                'seizure_location'         => $sz['seizure_location'] ?? $sc->raid_location,
                                'seizure_officer'          => $sz['seizure_officer'] ?? $sc->arresting_officer,
                                'investigating_officer'    => $sz['investigating_officer'] ?? null,
                                'source_requesting_unit'   => $sz['source_requesting_unit'] ?? null,
                                'seizure_reason'           => $sz['seizure_reason'] ?? null,
                                'source_of_exhibits'       => $sz['source_of_exhibits'] ?? null,
                                'exhibit_remark'           => $sz['exhibit_remark'] ?? null,
                                'chain_of_custody'         => $sz['chain_of_custody'] ?? null,
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
                                'exhibit_number'           => $sz['exhibit_number'] ?? null,
                                'lab_exhibit_no'           => $sz['lab_exhibit_no'] ?? null,
                                'property_id'              => $sz['property_id'] ?? null,
                                'tag_url'                  => $sz['tag_url'] ?? null,
                                'exhibit_category'         => $cat,
                                'exhibit_type'             => $sz['exhibit_type'] ?? null,
                                'drug_classification'      => $sz['drug_classification'] ?? null,
                                'commodity_name'           => $name,
                                'seizure_quantity'         => $sz['seizure_quantity'] ?? null,
                                'amount'                   => $sz['amount'] ?? (in_array(strtolower(trim((string)$cat)), ['money', 'currency', 'cash']) ? ($sz['seizure_quantity'] ?? null) : null),
                                'seizure_packaging'        => $sz['seizure_packaging'] ?? null,
                                'seizure_weight'           => $sz['seizure_weight'] ?? null,
                                'seizure_unit'             => $sz['seizure_unit'] ?? null,
                                'seizure_value'            => $sz['seizure_value'] ?? null,
                                'seizure_concealment'      => $sz['seizure_concealment'] ?? null,
                                'seizure_date'             => $sz['seizure_date'] ?? $mc->incident_date,
                                'seizure_time'             => $sz['seizure_time'] ?? null,
                                'seizure_location'         => $sz['seizure_location'] ?? $mc->raid_location,
                                'seizure_officer'          => $sz['seizure_officer'] ?? $mc->submitting_officer,
                                'investigating_officer'    => $sz['investigating_officer'] ?? null,
                                'source_requesting_unit'   => $sz['source_requesting_unit'] ?? null,
                                'seizure_reason'           => $sz['seizure_reason'] ?? null,
                                'source_of_exhibits'       => $sz['source_of_exhibits'] ?? null,
                                'exhibit_remark'           => $sz['exhibit_remark'] ?? null,
                                'chain_of_custody'         => $sz['chain_of_custody'] ?? null,
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
            'chain_of_custody' => ['nullable', 'string', 'max:5000'],
        ]);

        $oldStatus = $exhibit->custodial_status ?: 'Seized';
        $newStatus = 'Seized';

        $exhibit->custodial_status = 'Seized';

        if ($request->has('chain_of_custody')) {
            $exhibit->chain_of_custody = $validated['chain_of_custody'] ? trim($validated['chain_of_custody']) : null;
        }

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

        // Period preset filter (only applied when custom dates are not specified)
        if (empty($fromDate) && empty($toDate)) {
            if (in_array(strtolower($period), ['weekly', 'week'])) {
                $query->where(function ($q) {
                    $q->where('created_at', '>=', now()->subDays(7))
                      ->orWhere(function ($sub) {
                          $sub->whereNotNull('seizure_date')
                              ->where('seizure_date', '!=', '')
                              ->whereDate('seizure_date', '>=', now()->subDays(7)->toDateString());
                      });
                });
            } elseif (in_array(strtolower($period), ['monthly', 'month'])) {
                $query->where(function ($q) {
                    $q->where('created_at', '>=', now()->subDays(30))
                      ->orWhere(function ($sub) {
                          $sub->whereNotNull('seizure_date')
                              ->where('seizure_date', '!=', '')
                              ->whereDate('seizure_date', '>=', now()->subDays(30)->toDateString());
                      });
                });
            } elseif (in_array(strtolower($period), ['annual', 'annually', 'year', 'yearly'])) {
                $query->where(function ($q) {
                    $q->where('created_at', '>=', now()->subYear())
                      ->orWhere(function ($sub) {
                          $sub->whereNotNull('seizure_date')
                              ->where('seizure_date', '!=', '')
                              ->whereDate('seizure_date', '>=', now()->subYear()->toDateString());
                      });
                });
            }
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
            } elseif (str_contains($catLower, 'general') || str_contains($catLower, 'other')) {
                $query->where(function ($q) {
                    $q->where('exhibit_category', 'LIKE', '%general%')
                      ->orWhere('exhibit_category', 'LIKE', '%other%')
                      ->orWhereNull('exhibit_category')
                      ->orWhere('exhibit_category', '');
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
                  ->orWhere('seizure_packaging', 'LIKE', "%{$search}%")
                  ->orWhere('seizure_location', 'LIKE', "%{$search}%")
                  ->orWhere('seizure_officer', 'LIKE', "%{$search}%")
                  ->orWhere('investigating_officer', 'LIKE', "%{$search}%")
                  ->orWhere('source_requesting_unit', 'LIKE', "%{$search}%")
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

        // Total amount of money fetched from exhibits table (amount column, with seizure_value fallback)
        $totalMoneyAmount = Exhibit::where(function ($q) {
            $q->where('exhibit_category', 'LIKE', '%money%')
              ->orWhere('exhibit_category', 'LIKE', '%cash%')
              ->orWhere('exhibit_category', 'LIKE', '%currency%')
              ->orWhere('commodity_name', 'LIKE', '%currency%')
              ->orWhere('commodity_name', 'LIKE', '%cash%')
              ->orWhere('commodity_name', 'LIKE', '%cedis%')
              ->orWhere('commodity_name', 'LIKE', '%dollar%')
              ->orWhereNotNull('amount');
        })
        ->get()
        ->sum(function ($e) {
            $raw = $e->amount;
            if ($raw === null || $raw === '' || $raw === 'N/A') {
                $raw = $e->seizure_value;
            }
            if ($raw === null || $raw === '' || $raw === 'N/A') {
                return 0.0;
            }
            return (float) preg_replace('/[^\d.]/', '', (string) $raw);
        });

        $totalCasesCount = SingleSuspectCase::count() + MultipleSuspectCase::count();

        // Latest saved table notes for pre-populating audit remarks
        $recentNotes = EvidenceReportNote::latest()
            ->get()
            ->unique('table_key')
            ->keyBy('table_key');

        // Detected commodity categories for dynamically generated tables
        $detectedCommodityCategories = Exhibit::whereNotNull('exhibit_category')
            ->where('exhibit_category', '!=', '')
            ->distinct()
            ->pluck('exhibit_category')
            ->map(function ($cat) {
                $lower = strtolower(trim($cat));
                if ($lower === 'gold' || str_contains($lower, 'precious') || str_contains($lower, 'stone') || str_contains($lower, 'diamond') || str_contains($lower, 'gemstone')) {
                    return 'Precious Stones';
                }
                if (str_contains($lower, 'weapon') || str_contains($lower, 'firearm') || str_contains($lower, 'gun') || str_contains($lower, 'pistol') || str_contains($lower, 'rifle')) {
                    return 'Weapons';
                }
                if (str_contains($lower, 'ammunition') || str_contains($lower, 'ammo') || str_contains($lower, 'bullet') || str_contains($lower, 'cartridge')) {
                    return 'Ammunition';
                }
                return ucwords(strtolower(trim($cat)));
            })
            ->unique()
            ->filter(fn($c) => !in_array(strtolower($c), ['drugs', 'drug', 'narcotics', 'narcotic', 'money', 'cash', 'currency', 'vehicles', 'electronic devices', 'other exhibits', 'other']))
            ->values();

        // System-assigned table names for newly generated dynamic tables
        $systemGeneratedTableNames = $detectedCommodityCategories->map(function ($cat) {
            $lowerCat = strtolower(trim((string)$cat));
            return str_ends_with($lowerCat, 'log') ? $cat : $cat . ' Exhibit Log';
        });

        $standardDynamicTables = collect([
            'Precious Stones Exhibit Log',
            'Weapons Exhibit Log',
            'Ammunition Exhibit Log',
        ]);

        $systemGeneratedTableNames = $systemGeneratedTableNames
            ->concat($standardDynamicTables)
            ->unique()
            ->values();

        $recentNotesByTitle = EvidenceReportNote::latest()
            ->get()
            ->unique('table_title')
            ->mapWithKeys(fn($n) => [strtolower(trim($n->table_title)) => $n->note]);

        $viewName = $request->is('*/staff/report*')
            ? 'Evidence_Management.staff.report'
            : 'Evidence_Management.report';

        return view($viewName, compact(
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
            'totalMoneyAmount',
            'totalCasesCount',
            'recentNotes',
            'detectedCommodityCategories',
            'systemGeneratedTableNames',
            'recentNotesByTitle'
        ));
    }

    /**
     * Store officer audit notes for specific report tables into database.
     */
    public function saveReportNotes(Request $request)
    {
        $user = Auth::user();
        $batchId = $request->input('batch_id') ?: ('ERN-' . strtoupper(Str::random(10)));
        $period = $request->input('period');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $savedNotes = [];

        $rawNotes = $request->input('notes', []);
        if (is_array($rawNotes)) {
            foreach ($rawNotes as $item) {
                $noteText = trim((string)($item['note'] ?? ''));
                if ($noteText === '') {
                    continue;
                }

                $rawKey = trim((string)($item['table_key'] ?? ''));
                $tableTitle = trim((string)($item['table_title'] ?? 'Table Note'));
                $tableKey = !empty($rawKey) ? Str::slug($rawKey, '_') : Str::slug($tableTitle, '_');

                $record = EvidenceReportNote::create([
                    'user_id'      => $user?->id,
                    'officer_name' => $user?->name ?? 'Evidence Management Officer',
                    'table_key'    => $tableKey,
                    'table_title'  => $tableTitle,
                    'note'         => $noteText,
                    'batch_id'     => $batchId,
                    'report_type'  => 'evidence_management',
                    'period'       => $period,
                    'from_date'    => !empty($fromDate) ? $fromDate : null,
                    'to_date'      => !empty($toDate) ? $toDate : null,
                ]);

                $savedNotes[] = $record;
            }
        }

        return response()->json([
            'success'  => true,
            'batch_id' => $batchId,
            'count'    => count($savedNotes),
            'message'  => 'Table audit notes saved successfully.'
        ]);
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

        // Segregate exhibits into distinct operational tables:
        // 1. Drug Exhibits (strictly drug-related logs only)
        // 2. Physical Assets & Non-Drug Seized Property (strictly vehicles, electronic devices, other exhibits only)
        // 3. Seized Currency & Money Received Register (strictly money only)
        // 4. Dynamic Tables for Other Commodity Categories (weapons, ammunition, precious stones, and any custom category)

        $isMoneyExhibit = function ($e) {
            $cat = strtolower(trim((string)($e->exhibit_category ?? '')));
            $name = strtolower(trim((string)($e->commodity_name ?? '')));
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
        };

        $isDrugExhibit = function ($e) use ($isMoneyExhibit) {
            if ($isMoneyExhibit($e)) {
                return false;
            }
            $cat = strtolower(trim((string)($e->exhibit_category ?? '')));
            $name = strtolower(trim((string)($e->commodity_name ?? '')));
            $cls = strtolower(trim((string)($e->drug_classification ?? '')));
            $type = strtolower(trim((string)($e->exhibit_type ?? '')));

            // 1. If category explicitly says drugs or narcotics (and not explicitly non-drug category), it is ALWAYS a drug exhibit
            if ((str_contains($cat, 'drug') && !str_contains($cat, 'non-drug')) || str_contains($cat, 'narcotic')) {
                return true;
            }

            // 2. If explicit non-drug category, it is not a drug exhibit
            if (str_contains($cat, 'non-drug') 
                || str_contains($cat, 'vehicle') 
                || str_contains($cat, 'car') 
                || str_contains($cat, 'automobile') 
                || str_contains($cat, 'electronic') 
                || str_contains($cat, 'device') 
                || str_contains($cat, 'digital') 
                || str_contains($cat, 'weapon') 
                || str_contains($cat, 'firearm') 
                || str_contains($cat, 'gun') 
                || str_contains($cat, 'ammunition') 
                || str_contains($cat, 'ammo') 
                || str_contains($cat, 'stone') 
                || str_contains($cat, 'gold') 
                || str_contains($cat, 'diamond')) {
                return false;
            }

            // 3. Check drug classification or drug names
            if (!empty($cls) && $cls !== 'n/a') {
                return true;
            }

            if (str_contains($type, 'drug') && !str_contains($type, 'non-drug')) {
                return true;
            }

            return str_contains($name, 'cocaine')
                || str_contains($name, 'heroin')
                || str_contains($name, 'cannabis')
                || str_contains($name, 'weed')
                || str_contains($name, 'marijuana')
                || str_contains($name, 'meth')
                || str_contains($name, 'tramadol')
                || str_contains($name, 'ephedrine')
                || str_contains($name, 'hashish');
        };

        $isAssetExhibit = function ($e) use ($isMoneyExhibit, $isDrugExhibit) {
            if ($isMoneyExhibit($e) || $isDrugExhibit($e)) {
                return false;
            }
            $cat = strtolower(trim((string)($e->exhibit_category ?? '')));
            // Strictly vehicles, electronic devices, and other exhibits only:
            $isVehicle = str_contains($cat, 'vehicle') || str_contains($cat, 'car') || str_contains($cat, 'automobile') || str_contains($cat, 'truck') || str_contains($cat, 'vessel');
            $isDevice  = str_contains($cat, 'electronic') || str_contains($cat, 'device') || str_contains($cat, 'digital') || str_contains($cat, 'phone') || str_contains($cat, 'laptop') || str_contains($cat, 'computer');
            $isOther   = str_contains($cat, 'other') || $cat === '' || $cat === 'n/a';

            return $isVehicle || $isDevice || $isOther;
        };

        $drugExhibits = $exhibits->filter($isDrugExhibit)->values();
        $drugIds = $drugExhibits->pluck('id')->all();

        // Ironclad safeguard: Ensure all drug log entries are consolidated into $drugExhibits
        $leftoverDrugs = $exhibits->filter(function ($e) use ($drugIds) {
            if (in_array($e->id, $drugIds)) {
                return false;
            }
            $cat = strtolower(trim((string)($e->exhibit_category ?? '')));
            return (str_contains($cat, 'drug') && !str_contains($cat, 'non-drug')) || str_contains($cat, 'narcotic');
        })->values();
        if ($leftoverDrugs->isNotEmpty()) {
            $drugExhibits = $drugExhibits->concat($leftoverDrugs)->unique('id')->values();
            $drugIds = $drugExhibits->pluck('id')->all();
        }

        $moneyExhibits = $exhibits->filter(function ($e) use ($isMoneyExhibit, $drugIds) {
            return !in_array($e->id, $drugIds) && $isMoneyExhibit($e);
        })->values();
        $moneyIds = $moneyExhibits->pluck('id')->all();

        $assetExhibits = $exhibits->filter(function ($e) use ($isAssetExhibit, $drugIds, $moneyIds) {
            return !in_array($e->id, $drugIds) && !in_array($e->id, $moneyIds) && $isAssetExhibit($e);
        })->values();
        $assetIds = $assetExhibits->pluck('id')->all();

        // Any exhibit not covered above gets its own dynamic dedicated table (strictly non-drug, non-money, non-standard assets)
        $otherExhibits = $exhibits->filter(function ($e) use ($drugIds, $moneyIds, $assetIds, $isDrugExhibit, $isMoneyExhibit) {
            if (in_array($e->id, $drugIds) || in_array($e->id, $moneyIds) || in_array($e->id, $assetIds)) {
                return false;
            }
            $cat = strtolower(trim((string)($e->exhibit_category ?? '')));
            if ($isDrugExhibit($e) || (str_contains($cat, 'drug') && !str_contains($cat, 'non-drug')) || str_contains($cat, 'narcotic')) {
                return false;
            }
            if ($isMoneyExhibit($e) || str_contains($cat, 'money') || str_contains($cat, 'cash') || str_contains($cat, 'currency')) {
                return false;
            }
            return true;
        })->values();

        $dynamicExhibitsByCategory = $otherExhibits->groupBy(function ($e) {
            $cat = trim((string)($e->exhibit_category ?? ''));
            $lower = strtolower($cat);
            if ($lower === 'gold' || str_contains($lower, 'precious') || str_contains($lower, 'stone') || str_contains($lower, 'diamond') || str_contains($lower, 'gemstone')) {
                return 'Precious Stones';
            }
            if (str_contains($lower, 'weapon') || str_contains($lower, 'firearm') || str_contains($lower, 'gun') || str_contains($lower, 'pistol') || str_contains($lower, 'rifle')) {
                return 'Weapons';
            }
            if (str_contains($lower, 'ammunition') || str_contains($lower, 'ammo') || str_contains($lower, 'bullet') || str_contains($lower, 'cartridge')) {
                return 'Ammunition';
            }
            return !empty($cat) ? ucwords(strtolower($cat)) : 'General Exhibits';
        })->filter(function ($group, $catName) {
            $lower = strtolower(trim((string)$catName));
            // Strictly disallow duplicate/separate drug, money, or standard asset sections
            return !in_array($lower, ['drugs', 'drug', 'narcotics', 'narcotic', 'money', 'cash', 'currency', 'vehicles', 'electronic devices', 'other exhibits', 'other']);
        });

        $dynamicCategoryTotals = [];
        foreach ($dynamicExhibitsByCategory as $catName => $group) {
            $dynamicCategoryTotals[$catName] = $group->sum(fn($e) => (float)($e->seizure_value ?? 0));
        }

        $drugTotalValue = $drugExhibits->sum(fn($e) => (float)($e->seizure_value ?? 0));
        $assetTotalValue = $assetExhibits->sum(fn($e) => (float)($e->seizure_value ?? 0));
        $moneyTotalValue = $moneyExhibits->sum(function ($e) {
            $raw = $e->amount;
            if ($raw === null || $raw === '' || $raw === 'N/A') {
                $raw = $e->seizure_value;
            }
            if ($raw === null || $raw === '' || $raw === 'N/A') {
                return 0.0;
            }
            return (float) preg_replace('/[^\d.]/', '', (string) $raw);
        });

        // Signatories (Evidence Unit Head & Forensic Directorate Head)
        $dbEvidenceHead = \App\Models\User::where(function($q) {
                            $q->where('unit', 'LIKE', '%Evidence%')
                              ->orWhere('unit', 'LIKE', '%Custody%');
                        })->where('is_head', true)->first()
                        ?? \App\Models\User::where('unit', 'LIKE', '%Evidence%')->first();

        $dbDeptHead = \App\Models\User::where('unit', 'LIKE', '%Directorate%')->where('is_head', true)->first()
                   ?? \App\Models\User::where('unit', 'LIKE', '%Forensic Head%')->first()
                   ?? \App\Models\User::where('is_head', true)->first();

        // Fetch table audit notes for this report dossier
        $batchId = $request->input('batch_id');
        $reportNotes = collect();

        if (!empty($batchId)) {
            $reportNotes = EvidenceReportNote::where('batch_id', $batchId)->get();
        }

        if ($reportNotes->isEmpty()) {
            $reportNotes = EvidenceReportNote::latest()->get()->unique('table_key');
        }

        return view('Evidence_Management.report_pdf', compact(
            'exhibits',
            'drugExhibits',
            'assetExhibits',
            'moneyExhibits',
            'dynamicExhibitsByCategory',
            'dynamicCategoryTotals',
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
            'dbDeptHead',
            'reportNotes',
            'batchId'
        ));
    }
}

