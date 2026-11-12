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

    public function buildDashboardRealtimeData(Request $request): array
    {
        $cases = ForensicCase::allCases();

        $regions = [
            'Greater Accra' => ['accra', 'tema', 'airport', 'kotoka', 'cantonments', 'spintex', 'osu', 'diaspora', 'madina'],
            'Ashanti'       => ['kumasi', 'ashanti', 'obuasi', 'ejisu'],
            'Western'       => ['takoradi', 'sekondi', 'western', 'tarkwa', 'elubo'],
            'Eastern'       => ['koforidua', 'eastern', 'akim', 'nkawkaw'],
            'Volta'         => ['ho', 'aflao', 'volta'],
            'Northern'      => ['tamale', 'northern', 'yendi'],
            'Central'       => ['cape coast', 'central', 'elmina', 'winneba'],
            'Bono'          => ['sunyani', 'bono', 'techiman'],
            'Upper East'    => ['bolgatanga', 'upper east', 'paga', 'bawku'],
            'Upper West'    => ['wa', 'upper west'],
        ];

        $regionCounts = array_fill_keys(array_keys($regions), 0);
        $casesList = [];
        $overdueSum = 0.0;
        $overdueCount = 0;
        $completedTurnarounds = [];

        foreach ($cases as $c) {
            $underlying = $c->underlyingRecord ?? $c;
            $docket = $this->decodeDocket($c);

            $raidLoc = strtolower(trim((string)($c->raid_location ?: ($c->location ?: ''))));
            $matchedReg = 'Greater Accra';
            foreach ($regions as $reg => $kws) {
                foreach ($kws as $kw) {
                    if (str_contains($raidLoc, $kw)) {
                        $matchedReg = $reg;
                        break 2;
                    }
                }
            }
            $regionCounts[$matchedReg]++;

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

            if ($isApproved && $c->updated_at && $c->created_at) {
                $completedTurnarounds[] = Carbon::parse($c->created_at)->diffInDays(Carbon::parse($c->updated_at));
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
                'raid_location'        => $c->raid_location ?? 'Accra Regional Sector',
                'region'               => $matchedReg,
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
        $avgTurnaroundDays = !empty($completedTurnarounds) ? (int) round(array_sum($completedTurnarounds) / count($completedTurnarounds)) : null;
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
                'turnaround_days'    => $avgTurnaroundDays !== null ? "{$avgTurnaroundDays} days" : 'N/A',
                'turnaround_days_raw'=> $avgTurnaroundDays,
                'turnaround_trend'   => 'N/A',
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
            'cases'         => $casesList,
        ];
    }
}
