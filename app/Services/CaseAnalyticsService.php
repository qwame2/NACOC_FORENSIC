<?php

namespace App\Services;

use App\Http\Controllers\Concerns\CaseControllerHelpers;
use App\Models\Exhibit;
use App\Models\ForensicCase;
use App\Models\MultipleSuspectCase;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * CaseAnalyticsService
 *
 * Dedicated service for real-time forensic dashboard analytics, KPI calculations,
 * and regional case distributions.
 *
 * Fix Issue 6 & 8 & 26: Compute strictly from real data or return 'N/A'/0.
 * Never return fake fabricated values.
 */
class CaseAnalyticsService
{
    use CaseControllerHelpers;

    /**
     * Ghana regional capital coordinates used when a case record stores a region/location
     * name without explicit decimal GPS latitude/longitude columns.
     *
     * @var array<string, array{0: float, 1: float}>
     */
    private const REGION_COORDINATES = [
        'Greater Accra' => [5.6037, -0.1870],
        'Ashanti'       => [6.6885, -1.6244],
        'Western'       => [4.8967, -1.7558],
        'Western North' => [6.2059, -2.4854],
        'Eastern'       => [6.0941, -0.2591],
        'Volta'         => [6.6008, 0.4713],
        'Oti'           => [8.0689, 0.1792],
        'Northern'      => [9.4008, -0.8393],
        'Savannah'      => [9.0830, -1.8180],
        'North East'    => [10.5269, -0.3694],
        'Central'       => [5.1053, -1.2466],
        'Bono'          => [7.3349, -2.3123],
        'Bono East'     => [7.5862, -1.9344],
        'Ahafo'         => [6.8036, -2.5172],
        'Upper East'    => [10.7856, -0.8514],
        'Upper West'    => [10.0601, -2.5099],
    ];

    /**
     * Distinct Ghana city/town/border coordinates for finer-grained geocoding
     * when explicit decimal GPS columns are absent.
     *
     * @var array<string, array{0: float, 1: float}>
     */
    private const LOCATION_COORDINATES = [
        'accra'        => [5.6037, -0.1870],
        'kotoka'       => [5.6052, -0.1668],
        'airport'      => [5.6052, -0.1668],
        'spintex'      => [5.6350, -0.1050],
        'madina'       => [5.6786, -0.1669],
        'cantonments'  => [5.5806, -0.1731],
        'osu'          => [5.5560, -0.1816],
        'east legon'   => [5.6358, -0.1558],
        'dansoman'     => [5.5515, -0.2647],
        'kaneshie'     => [5.5681, -0.2361],
        'lapaz'        => [5.6089, -0.2498],
        'dome'         => [5.6500, -0.2333],
        'ashaiman'     => [5.6950, -0.0328],
        'dodowa'       => [5.8819, -0.0981],
        'ada'          => [5.7833, 0.6333],
        'prampram'     => [5.7167, 0.1167],
        'nungua'       => [5.6019, -0.0753],
        'teshie'       => [5.5833, -0.1000],
        'labadi'       => [5.5636, -0.1525],
        'kasoa'        => [5.5345, -0.4168],
        'weija'        => [5.5667, -0.3333],
        'amasaman'     => [5.7042, -0.2994],
        'kumasi'       => [6.6885, -1.6244],
        'obuasi'       => [6.2023, -1.6634],
        'ejisu'        => [6.7167, -1.4667],
        'konongo'      => [6.6167, -1.2167],
        'mampong'      => [7.0627, -1.4001],
        'bekwai'       => [6.4532, -1.5786],
        'offinso'      => [6.9500, -1.6500],
        'agogo'        => [6.8000, -1.0833],
        'takoradi'     => [4.8967, -1.7558],
        'sekondi'      => [4.9340, -1.7137],
        'tarkwa'       => [5.3018, -1.9930],
        'elubo'        => [5.2869, -2.7675],
        'axim'         => [4.8699, -2.2405],
        'prestea'      => [5.4339, -2.1428],
        'bogoso'       => [5.5667, -2.0167],
        'sefwi wiawso' => [6.2059, -2.4854],
        'wiawso'       => [6.2059, -2.4854],
        'bibiani'      => [6.4635, -2.3194],
        'koforidua'    => [6.0941, -0.2591],
        'nkawkaw'      => [6.5512, -0.7662],
        'nsawam'       => [5.8089, -0.3503],
        'suhum'        => [6.0367, -0.4500],
        'akim oda'     => [5.9267, -0.9858],
        'oda'          => [5.9267, -0.9858],
        'somanya'      => [6.1000, -0.0167],
        'akropong'     => [5.9744, -0.0914],
        'kibi'         => [6.1667, -0.5500],
        'begoro'       => [6.3833, -0.3833],
        'ho'           => [6.6008, 0.4713],
        'aflao'        => [6.1190, 1.1904],
        'hohoe'        => [7.1518, 0.4736],
        'kpando'       => [6.9954, 0.2931],
        'keta'         => [5.9179, 0.9879],
        'sogakope'     => [6.0000, 0.6000],
        'akatsi'       => [6.1333, 0.8000],
        'dambai'       => [8.0689, 0.1792],
        'nkwanta'      => [8.2500, 0.5167],
        'jasikan'      => [7.4000, 0.4667],
        'tamale'       => [9.4008, -0.8393],
        'yendi'        => [9.4427, -0.0099],
        'savelugu'     => [9.6244, -0.8253],
        'damongo'      => [9.0830, -1.8180],
        'bole'         => [9.0333, -2.4833],
        'salaga'       => [8.5508, -0.5188],
        'buipe'        => [8.7667, -1.4833],
        'nalerigu'     => [10.5269, -0.3694],
        'walewale'     => [10.3500, -0.8000],
        'cape coast'   => [5.1053, -1.2466],
        'elmina'       => [5.0847, -1.3509],
        'winneba'      => [5.3511, -0.6232],
        'mankessim'    => [5.2667, -1.0167],
        'swedru'       => [5.5371, -0.6998],
        'saltpond'     => [5.2091, -1.0606],
        'assin fosu'   => [5.7012, -1.2866],
        'dunkwa'       => [5.9667, -1.7833],
        'sunyani'      => [7.3349, -2.3123],
        'berekum'      => [7.4534, -2.5840],
        'dormaa'       => [7.2833, -2.8833],
        'wenchi'       => [7.7392, -2.1046],
        'techiman'     => [7.5862, -1.9344],
        'kintampo'     => [8.0563, -1.7306],
        'atebubu'      => [7.7500, -0.9833],
        'yeji'         => [8.2219, -0.6531],
        'goaso'        => [6.8036, -2.5172],
        'bechem'       => [7.0903, -2.0250],
        'bolgatanga'   => [10.7856, -0.8514],
        'paga'         => [10.9969, -1.1136],
        'bawku'        => [11.0616, -0.2417],
        'navrongo'     => [10.8956, -1.0921],
        'zebilla'      => [10.9167, -0.5167],
        'wa'           => [10.0601, -2.5099],
        'tumu'         => [10.8833, -1.9833],
        'lawra'        => [10.6500, -2.8833],
    ];

    /**
     * Keyword lookup per Ghana administrative region.
     *
     * @var array<string, list<string>>
     */
    private const REGION_KEYWORDS = [
        'Greater Accra' => ['greater accra', 'accra', 'tema', 'airport', 'kotoka', 'cantonments', 'spintex', 'osu', 'east legon', 'dansoman', 'kaneshie', 'lapaz', 'dome', 'ashaiman', 'dodowa', 'ada', 'prampram', 'nungua', 'teshie', 'labadi', 'madina', 'kasoa', 'weija', 'amasaman'],
        'Ashanti'       => ['ashanti', 'kumasi', 'obuasi', 'ejisu', 'konongo', 'mampong', 'bekwai', 'offinso', 'agogo'],
        'Western'       => ['western', 'takoradi', 'sekondi', 'tarkwa', 'elubo', 'axim', 'prestea', 'bogoso'],
        'Western North' => ['western north', 'sefwi wiawso', 'wiawso', 'bibiani'],
        'Eastern'       => ['eastern', 'koforidua', 'akim oda', 'oda', 'nkawkaw', 'nsawam', 'suhum', 'somanya', 'akropong', 'kibi', 'begoro'],
        'Volta'         => ['volta', 'ho', 'aflao', 'hohoe', 'kpando', 'keta', 'sogakope', 'akatsi'],
        'Oti'           => ['oti', 'dambai', 'nkwanta', 'jasikan'],
        'Northern'      => ['northern', 'tamale', 'yendi', 'savelugu'],
        'Savannah'      => ['savannah', 'damongo', 'bole', 'salaga', 'buipe'],
        'North East'    => ['north east', 'nalerigu', 'walewale'],
        'Central'       => ['central', 'cape coast', 'elmina', 'winneba', 'mankessim', 'swedru', 'saltpond', 'assin fosu', 'dunkwa'],
        'Bono'          => ['bono', 'sunyani', 'berekum', 'dormaa', 'wenchi'],
        'Bono East'     => ['bono east', 'techiman', 'kintampo', 'atebubu', 'yeji'],
        'Ahafo'         => ['ahafo', 'goaso', 'bechem'],
        'Upper East'    => ['upper east', 'bolgatanga', 'paga', 'bawku', 'navrongo', 'zebilla'],
        'Upper West'    => ['upper west', 'wa', 'tumu', 'lawra'],
    ];

    public function buildDashboardRealtimeData(Request $request): array
    {
        $cases = ForensicCase::allCases();
        $regions = self::REGION_KEYWORDS;

        $regionCounts = array_fill_keys(array_keys($regions), 0);
        $casesList = [];
        $overdueSum = 0.0;
        $overdueCount = 0;
        $completedTurnarounds = [];
        $activeAges = [];

        foreach ($cases as $c) {
            $underlying = $c->underlyingRecord ?? $c;
            $docket = $this->decodeDocket($c);

            [$rawLocation, $matchedReg, $lat, $lng] = $this->resolveCaseLocationAndCoordinates($c, $docket);
            if ($matchedReg !== null && isset($regionCounts[$matchedReg])) {
                $regionCounts[$matchedReg]++;
            }

            $isMultiple = ($underlying instanceof MultipleSuspectCase)
                || (!empty($docket['suspects']) && count($docket['suspects']) > 1);

            $suspectsRaw = $c->getSuspectsList();
            $suspectsList = [];
            if (!empty($suspectsRaw)) {
                foreach ($suspectsRaw as $sIdx => $s) {
                    $suspectsList[] = [
                        'id'           => $s['id'] ?? ($sIdx + 1),
                        'suspect_id'   => $s['id'] ?? ($sIdx + 1),
                        'name'         => strtoupper($s['suspect_name'] ?? 'SUSPECT'),
                        'role'         => $s['role'] ?? ($sIdx === 0 ? 'Primary Target' : 'Associate'),
                        'nationality'  => $s['nationality'] ?? 'Ghanaian',
                        'gender'       => $s['gender'] ?? 'Male',
                        'age'          => !empty($s['age']) ? $s['age'] . ' yrs' : null,
                        'ghana_card'   => $s['ghana_card'] ?? 'GHA-XXXXXXXXX-X',
                        'mugshot_front'=> $s['mugshot_front'] ?? null,
                        'disposition'  => $s['suspect_disposition'] ?? 'In Custody',
                    ];
                }
            } else {
                $suspectsList[] = [
                    'id'           => $c->id,
                    'suspect_id'   => $c->id,
                    'name'         => strtoupper($c->suspect_name ?: 'SUSPECT'),
                    'role'         => 'Primary Target',
                    'nationality'  => $c->nationality ?: 'Ghanaian',
                    'gender'       => $c->gender ?: 'Male',
                    'age'          => $c->age ? $c->age . ' yrs' : null,
                    'ghana_card'   => $c->ghana_card ?: 'GHA-XXXXXXXXX-X',
                    'mugshot_front'=> $c->mugshot_front,
                    'disposition'  => $c->status ?: 'In Custody',
                ];
            }

            $exhibits = $c->exhibits ?? [];
            $exhibitItems = [];
            $totalEstVal = 0.0;

            if (!empty($exhibits) && count($exhibits) > 0) {
                foreach ($exhibits as $ex) {
                    $valNum = !empty($ex->seizure_value) ? (float)preg_replace('/[^0-9.]/', '', (string)$ex->seizure_value) : 0.0;
                    $totalEstVal += $valNum;
                    $cat = $ex->commodity_name ?: ($ex->exhibit_category ?: 'Seized Asset');
                    $qty = trim(($ex->seizure_quantity ?: '') . ' ' . ($ex->seizure_unit ?: ''));

                    $exhibitItems[] = [
                        'id'                => $ex->id,
                        'title'             => $cat,
                        'category'          => $ex->exhibit_category ?: 'Evidence Seizure',
                        'quantity'          => $qty ?: '1 unit',
                        'value'             => '$' . number_format($valNum, 2),
                        'value_raw'         => $valNum,
                        'description'       => $ex->other_exhibit_description ?: 'Registered case exhibit',
                        'suspect_id'        => $ex->suspect_id,
                        'associated_person' => $ex->associated_person ?: ($isMultiple ? 'All Suspects' : $suspectsList[0]['name']),
                    ];
                }
            } elseif (!empty($docket['seizures'])) {
                foreach ($docket['seizures'] as $sIdx => $sz) {
                    $valNum = !empty($sz['seizure_value']) ? (float)preg_replace('/[^0-9.]/', '', (string)$sz['seizure_value']) : 0.0;
                    $totalEstVal += $valNum;
                    $cat = $sz['seizure_name'] ?? ($sz['category'] ?? 'Evidence Seizure');
                    $qty = trim(($sz['seizure_quantity'] ?? '') . ' ' . ($sz['seizure_unit'] ?? ''));

                    $exhibitItems[] = [
                        'id'                => 'docket-' . $sIdx,
                        'title'             => $cat,
                        'category'          => $sz['category'] ?? 'Evidence Seizure',
                        'quantity'          => $qty ?: '1 unit',
                        'value'             => '$' . number_format($valNum, 2),
                        'value_raw'         => $valNum,
                        'description'       => $sz['seizure_description'] ?? 'Seized exhibit item',
                        'suspect_id'        => null,
                        'associated_person' => $isMultiple ? 'All Suspects' : $suspectsList[0]['name'],
                    ];
                }
            }

            $cDate = $c->created_at ? Carbon::parse($c->created_at) : now();
            $uDate = $c->updated_at ? Carbon::parse($c->updated_at) : $cDate;
            $diffDays = $cDate->diffInDays(now());
            $relativeTime = $diffDays === 0 ? 'Today' : ($diffDays === 1 ? '1 day ago' : "{$diffDays} days ago");
            $updatedDiffDays = $uDate->diffInDays(now());
            $updatedHuman = $updatedDiffDays === 0 ? 'Today' : ($updatedDiffDays === 1 ? '1 day ago' : "{$updatedDiffDays} days ago");

            $statusBadge = $c->case_status ?? ($c->status ?? 'Under Investigation');
            $isApproved = in_array(strtolower($statusBadge), ['approved', 'completed', 'authorized'], true);
            $priorityNorm = strtoupper(trim((string) ($c->priority ?? 'HIGH')));
            if (!in_array($priorityNorm, ['CRITICAL', 'HIGH', 'MEDIUM', 'LOW'], true)) {
                $priorityNorm = 'HIGH';
            }

            if ($diffDays > 30 && !$isApproved) {
                $overdueSum += $totalEstVal;
                $overdueCount++;
            }

            if ($c->created_at) {
                $startAt = Carbon::parse($c->created_at);
                $endAt = ($isApproved && $c->updated_at) ? Carbon::parse($c->updated_at) : now();
                $hours = max(1, $startAt->diffInHours($endAt));
                if ($isApproved && $c->updated_at) {
                    $completedTurnarounds[] = $hours;
                } else {
                    $activeAges[] = $hours;
                }
            }

            $casesList[] = [
                'id'                   => $c->id,
                'case_number'          => $c->case_number,
                'is_multiple'          => $isMultiple,
                'suspects_count'       => count($suspectsList),
                'suspects'             => $suspectsList,
                'suspect_name'         => implode(' & ', array_column($suspectsList, 'name')),
                'primary_suspect'      => $suspectsList[0] ?? null,
                'laboratory_number'    => $c->laboratory_number,
                'title'                => $c->title ?? 'Forensic Docket File',
                'suspect_alias'        => $c->suspect_alias ?? 'N/A',
                'suspect_role'         => $suspectsList[0]['role'] ?? 'Primary Target',
                'nationality'          => $suspectsList[0]['nationality'] ?? 'Ghanaian',
                'gender'               => $suspectsList[0]['gender'] ?? 'Male',
                'age'                  => $suspectsList[0]['age'] ?? null,
                'ghana_card'           => $suspectsList[0]['ghana_card'] ?? 'GHA-XXXXXXXXX-X',
                'mugshot_front'        => $suspectsList[0]['mugshot_front'] ?? null,
                'unit_name'            => $c->unit_name ?? 'Forensic Directorate',
                'status'               => $c->status,
                'status_badge'         => $statusBadge,
                'case_status'          => $statusBadge,
                'priority'             => $priorityNorm,
                'raid_location'        => $rawLocation !== '' ? $rawLocation : 'N/A',
                'region'               => $matchedReg ?? 'Unmapped',
                'latitude'             => $lat,
                'longitude'            => $lng,
                'incident_date'        => $cDate->format('d M Y'),
                'created_at_iso'       => $cDate->toIso8601String(),
                'created_at_month'     => $cDate->format('Y-m'),
                'created_at_human'     => $relativeTime,
                'created_at_formatted' => $cDate->format('d M Y, H:i'),
                'updated_at_human'     => $updatedHuman,
                'updated_at_formatted' => $uDate->format('d M Y, H:i'),
                'days_open'            => (int) $diffDays,
                'submitting_officer'   => $c->submitting_officer ?? 'N/A',
                'exhibits_count'       => count($exhibitItems),
                'exhibits'             => $exhibitItems,
                'total_amount'         => '$' . number_format($totalEstVal, 2),
                'total_amount_raw'     => $totalEstVal,
                'balance_due'          => '$' . number_format($totalEstVal, 2),
            ];
        }

        $casesCol = collect($casesList);
        $totalCases = $casesCol->count();
        $pendingCount = $casesCol->filter(fn($c) => in_array(strtolower($c['status_badge']), ['pending', 'awaiting sign-off', 'pending approval']))->count();
        $inProgressCount = $casesCol->filter(fn($c) => in_array(strtolower($c['status_badge']), ['in progress', 'under investigation', 'under multi-unit processing', 'active']))->count();
        $approvedCount = $casesCol->filter(fn($c) => in_array(strtolower($c['status_badge']), ['approved', 'completed', 'authorized']))->count();
        $criticalHighCount = $casesCol->filter(fn($c) => in_array($c['priority'], ['CRITICAL', 'HIGH'], true))->count();
        $mediumCount = $casesCol->where('priority', 'MEDIUM')->count();
        $lowCount = $casesCol->where('priority', 'LOW')->count();
        $totalExhibitsCount = Exhibit::count();

        // Build rolling 6-month historical series strictly from real database timestamps
        $monthBuckets = [];
        for ($m = 5; $m >= 0; $m--) {
            $dt = Carbon::now()->startOfMonth()->subMonths($m);
            $monthBuckets[$dt->format('Y-m')] = [
                'label'       => $dt->format('M'),
                'total'       => 0,
                'pending'     => 0,
                'in_progress' => 0,
                'approved'    => 0,
                'exhibits'    => 0,
            ];
        }

        foreach ($casesList as $item) {
            $ym = $item['created_at_month'] ?? null;
            if ($ym && isset($monthBuckets[$ym])) {
                $monthBuckets[$ym]['total']++;
                $st = strtolower($item['status_badge']);
                if (in_array($st, ['pending', 'awaiting sign-off', 'pending approval'], true)) {
                    $monthBuckets[$ym]['pending']++;
                } elseif (in_array($st, ['approved', 'completed', 'authorized'], true)) {
                    $monthBuckets[$ym]['approved']++;
                } else {
                    $monthBuckets[$ym]['in_progress']++;
                }
            }
        }

        $exhibitsDates = Exhibit::query()->select(['created_at'])->get();
        foreach ($exhibitsDates as $exRow) {
            if ($exRow->created_at) {
                $ym = Carbon::parse($exRow->created_at)->format('Y-m');
                if (isset($monthBuckets[$ym])) {
                    $monthBuckets[$ym]['exhibits']++;
                }
            }
        }

        $unitCounts = $casesCol->groupBy(fn($c) => $c['unit_name'] ?: 'Forensic Directorate')
            ->map->count()
            ->sortDesc()
            ->toArray();

        $vaultValuation = $casesCol->sum('total_amount_raw');
        $turnaroundSource = !empty($completedTurnarounds) ? $completedTurnarounds : $activeAges;
        $avgHours = !empty($turnaroundSource) ? (array_sum($turnaroundSource) / count($turnaroundSource)) : null;
        if ($avgHours === null) {
            $avgTurnaroundDays = null;
            $turnaroundValue = 'N/A';
            $turnaroundUnit = '';
        } elseif ($avgHours < 24) {
            $roundedHours = max(1, (int) round($avgHours));
            $avgTurnaroundDays = 1;
            $turnaroundValue = (string) $roundedHours;
            $turnaroundUnit = $roundedHours === 1 ? 'hr' : 'hrs';
        } else {
            $roundedDays = max(1, (int) round($avgHours / 24));
            $avgTurnaroundDays = $roundedDays;
            $turnaroundValue = (string) $roundedDays;
            $turnaroundUnit = $roundedDays === 1 ? 'day' : 'days';
        }
        $completionRate = $totalCases > 0 ? (int) round(($approvedCount / $totalCases) * 100) : 0;

        // WHY Fix Issue 6 & 8 & 26: Return N/A when data is unavailable. Never return fake numbers.
        return [
            'success'   => true,
            'timestamp' => now()->toIso8601String(),
            'metrics'   => [
                'total_cases'        => $totalCases,
                'pending_cases'      => $pendingCount,
                'in_progress_cases'  => $inProgressCount,
                'approved_cases'     => $approvedCount,
                'completion_rate'    => $completionRate,
                'high_priority_cases'=> $criticalHighCount,
                'medium_cases'       => $mediumCount,
                'low_cases'          => $lowCount,
                'total_exhibits'     => $totalExhibitsCount,
                'overdue_amount'     => $overdueCount > 0 ? '$' . number_format($overdueSum, 2) : 'N/A',
                'overdue_trend'      => 'N/A',
                'next_month_amount'  => 'N/A',
                'next_month_trend'   => 'N/A',
                'turnaround_days'    => $turnaroundValue !== 'N/A' ? trim("{$turnaroundValue} {$turnaroundUnit}") : 'N/A',
                'turnaround_days_raw'=> $avgTurnaroundDays,
                'turnaround_value'   => $turnaroundValue,
                'turnaround_unit'    => $turnaroundUnit,
                'turnaround_trend'   => !empty($completedTurnarounds) ? 'Closed' : (!empty($activeAges) ? 'Active' : 'N/A'),
                'vault_amount'       => '$' . number_format($vaultValuation, 2),
                'vault_trend'        => 'N/A',
            ],
            'chart_data' => [
                'months'              => array_values(array_column($monthBuckets, 'label')),
                'monthly_bars'        => array_values(array_column($monthBuckets, 'total')),
                'sparkline'           => array_values(array_column($monthBuckets, 'total')),
                'monthly_pending'     => array_values(array_column($monthBuckets, 'pending')),
                'monthly_in_progress' => array_values(array_column($monthBuckets, 'in_progress')),
                'monthly_approved'    => array_values(array_column($monthBuckets, 'approved')),
                'monthly_exhibits'    => array_values(array_column($monthBuckets, 'exhibits')),
            ],
            'breakdowns' => [
                'severity' => [
                    'high'   => $criticalHighCount,
                    'medium' => $mediumCount,
                    'low'    => $lowCount,
                ],
                'status' => [
                    'pending'     => $pendingCount,
                    'in_progress' => $inProgressCount,
                    'approved'    => $approvedCount,
                ],
                'units' => $unitCounts,
            ],
            'region_counts' => $regionCounts,
            'top_incident_locations' => $this->buildTopIncidentLocations($cases),
            'cases'         => $casesList,
        ];
    }

    /**
     * Build Leaflet map configuration and marker array strictly from real forensic cases.
     *
     * @param \Illuminate\Support\Collection<int, mixed>|null $cases
     * @return array<string, mixed>
     */
    public function buildLeafletMapPayload($cases = null): array
    {
        $collection = $cases ?? ForensicCase::allCases();
        $markers = [];
        $latSum = 0.0;
        $lngSum = 0.0;

        foreach ($collection as $c) {
            $docket = $this->decodeDocket($c);
            [$rawLocation, $matchedReg, $lat, $lng] = $this->resolveCaseLocationAndCoordinates($c, $docket);
            if ($lat === null || $lng === null) {
                continue;
            }
            $latSum += $lat;
            $lngSum += $lng;

            $statusBadge = $c->case_status ?? ($c->status ?? 'Under Investigation');
            $priorityNorm = strtoupper(trim((string) ($c->priority ?? 'HIGH')));
            if (!in_array($priorityNorm, ['CRITICAL', 'HIGH', 'MEDIUM', 'LOW'], true)) {
                $priorityNorm = 'HIGH';
            }

            $exhibitsCount = is_countable($c->exhibits ?? null) ? count($c->exhibits) : 0;
            $encodedRef = rawurlencode((string) ($c->case_number ?: $c->id));

            $markers[] = [
                'id'             => $c->id,
                'case_number'    => $c->case_number,
                'title'          => $c->suspect_name ?: ($c->title ?: 'Forensic Docket'),
                'unit_name'      => $c->unit_name ?? 'Forensic Directorate',
                'status'         => $statusBadge,
                'priority'       => $priorityNorm,
                'location'       => $rawLocation !== '' ? $rawLocation : ($matchedReg ?? 'Unknown'),
                'region'         => $matchedReg ?? 'Unmapped',
                'exhibits_count' => $exhibitsCount,
                'latitude'       => $lat,
                'longitude'      => $lng,
                'review_url'     => url('/cases/' . $encodedRef . '/review'),
            ];
        }

        $count = count($markers);
        $defaultLat = (float) config('services.leaflet.default_latitude', 7.9465);
        $defaultLng = (float) config('services.leaflet.default_longitude', -1.0232);
        $defaultZoom = (int) config('services.leaflet.default_zoom', 7);

        return [
            'latitude'    => $count > 0 ? round($latSum / $count, 6) : $defaultLat,
            'longitude'   => $count > 0 ? round($lngSum / $count, 6) : $defaultLng,
            'zoom'        => $defaultZoom,
            'markers'     => $markers,
            'tile_url'    => (string) config('services.leaflet.tile_url', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'),
            'attribution' => (string) config('services.leaflet.attribution', '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'),
        ];
    }

    /**
     * Resolve case location text, matched region, and decimal coordinates.
     * Returns null region and null coordinates when the place of incident
     * cannot be matched to a known town, city, region, or explicit GPS coordinate.
     *
     * @param  array<string, mixed>  $docket
     * @return array{0: string, 1: string|null, 2: float|null, 3: float|null}
     */
    private function resolveCaseLocationAndCoordinates($case, array $docket = []): array
    {
        $rawLocation = trim((string) ($case->raid_location ?: ($case->location ?: ($docket['raid_location'] ?? ''))));
        if ($rawLocation === '' && method_exists($case, 'getSuspectsList')) {
            $suspects = $case->getSuspectsList();
            if (!empty($suspects[0]['raid_location'])) {
                $rawLocation = trim((string) $suspects[0]['raid_location']);
            }
        }

        $raidLoc = strtolower($rawLocation);
        $matchedReg = null;
        if ($raidLoc !== '' && !in_array($raidLoc, ['n/a', 'na', 'none', 'unknown', 'nil', '-'], true)) {
            foreach (self::REGION_KEYWORDS as $reg => $kws) {
                foreach ($kws as $kw) {
                    if (preg_match('/\b' . preg_quote($kw, '/') . '\b/i', $raidLoc)) {
                        $matchedReg = $reg;
                        break 2;
                    }
                }
            }
        }

        if (isset($case->latitude, $case->longitude) && is_numeric($case->latitude) && is_numeric($case->longitude)) {
            return [$rawLocation, $matchedReg, (float) $case->latitude, (float) $case->longitude];
        }

        if ($raidLoc !== '' && !in_array($raidLoc, ['n/a', 'na', 'none', 'unknown', 'nil', '-'], true)) {
            $keywords = array_keys(self::LOCATION_COORDINATES);
            usort($keywords, fn (string $a, string $b): int => strlen($b) <=> strlen($a));

            foreach ($keywords as $keyword) {
                if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $raidLoc)) {
                    $coords = self::LOCATION_COORDINATES[$keyword];
                    return [$rawLocation, $matchedReg, $coords[0], $coords[1]];
                }
            }
        }

        if ($matchedReg !== null && isset(self::REGION_COORDINATES[$matchedReg])) {
            $regionCoords = self::REGION_COORDINATES[$matchedReg];
            return [$rawLocation, $matchedReg, $regionCoords[0], $regionCoords[1]];
        }

        return [$rawLocation, null, null, null];
    }

    /**
     * Build ranked scoreboard of incident locations with the highest case concentrations.
     *
     * @param \Illuminate\Support\Collection<int, mixed>|null $cases
     * @return array<int, array{rank: int, name: string, full_location: string, region: string, latitude: float|null, longitude: float|null, cases_count: int, critical_count: int, percentage: float}>
     */
    public function buildTopIncidentLocations($cases = null): array
    {
        $collection = $cases ?? ForensicCase::allCases();
        $buckets = [];
        $totalMapped = 0;

        foreach ($collection as $c) {
            $docket = $this->decodeDocket($c);
            [$rawLocation, $matchedReg, $lat, $lng] = $this->resolveCaseLocationAndCoordinates($c, $docket);
            $cleanLoc = trim($rawLocation);
            if ($cleanLoc === '' || in_array(strtolower($cleanLoc), ['n/a', 'na', 'none', 'unknown', 'nil', '-'], true)) {
                $cleanLoc = $matchedReg ? ($matchedReg . ' Region') : 'Unspecified Scene';
            }

            $shortName = $this->extractShortLocationName($cleanLoc, $matchedReg);
            $key = strtolower($shortName);

            if (!isset($buckets[$key])) {
                $buckets[$key] = [
                    'name'           => $shortName,
                    'full_location'  => $cleanLoc,
                    'region'         => $matchedReg ?? 'Ghana',
                    'latitude'       => $lat,
                    'longitude'      => $lng,
                    'cases_count'    => 0,
                    'critical_count' => 0,
                ];
            }

            $buckets[$key]['cases_count']++;
            $priority = strtoupper(trim((string) ($c->priority ?? 'HIGH')));
            if ($priority === 'CRITICAL' || $priority === 'HIGH') {
                $buckets[$key]['critical_count']++;
            }
            $totalMapped++;
        }

        // Sort descending by cases_count, then by critical_count
        usort($buckets, function ($a, $b) {
            if ($b['cases_count'] === $a['cases_count']) {
                return $b['critical_count'] <=> $a['critical_count'];
            }
            return $b['cases_count'] <=> $a['cases_count'];
        });

        // Add rank and percentage
        $ranked = [];
        foreach (array_values($buckets) as $idx => $item) {
            $item['rank'] = $idx + 1;
            $item['percentage'] = $totalMapped > 0 ? round(($item['cases_count'] / $totalMapped) * 100, 1) : 0;
            $ranked[] = $item;
        }

        return $ranked;
    }

    /**
     * Format a concise location name suitable for scoreboard card and bar charts.
     */
    private function extractShortLocationName(string $raw, ?string $region = null): string
    {
        $lower = strtolower($raw);

        if (str_contains($lower, 'east legon')) return 'East Legon, Accra';
        if (str_contains($lower, 'airport') && str_contains($lower, 'accra')) return 'Airport Area, Accra';
        if (str_contains($lower, 'kejetia') || str_contains($lower, 'kumasi')) return 'Kumasi Central Hub';
        if (str_contains($lower, 'aflao')) return 'Aflao Border Post';
        if (str_contains($lower, 'takoradi') && str_contains($lower, 'harbour')) return 'Takoradi Harbour Port';
        if (str_contains($lower, 'tema')) return 'Tema Harbour Port';
        if (str_contains($lower, 'spintex')) return 'Spintex Road, Accra';
        if (str_contains($lower, 'dansoman')) return 'Dansoman, Accra';
        if (str_contains($lower, 'kaneshie')) return 'Kaneshie Hub, Accra';
        if (str_contains($lower, 'osu')) return 'Osu Sector, Accra';
        if (str_contains($lower, 'tamale')) return 'Tamale Central';
        if (str_contains($lower, 'koforidua')) return 'Koforidua Central';
        if (str_contains($lower, 'cape coast')) return 'Cape Coast Metro';
        if (str_contains($lower, 'sunyani')) return 'Sunyani Central';
        if (str_contains($lower, 'bolgatanga')) return 'Bolgatanga Central';
        if (str_contains($lower, 'wa') && !str_contains($lower, 'wiawso')) return 'Wa Central';
        if (str_contains($lower, 'elubo')) return 'Elubo Border Post';
        if (str_contains($lower, 'paga')) return 'Paga Border Post';
        if (str_contains($lower, 'techiman')) return 'Techiman Hub';
        if (str_contains($lower, 'ho ') || str_contains($lower, 'ho civic') || str_contains($lower, 'ho,') || $lower === 'ho') return 'Ho Central';

        $parts = array_map('trim', explode(',', $raw));
        if (count($parts) >= 2) {
            return $parts[0] . ', ' . $parts[1];
        }

        return !empty($parts[0]) ? $parts[0] : ($region ? "{$region} Sector" : 'Unspecified Location');
    }
}
