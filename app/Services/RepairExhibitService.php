<?php

namespace App\Services;

use App\Models\Exhibit;
use App\Models\SingleSuspectCase;

/**
 * Service to repair and sync exhibit attributes and docket relationships.
 * WHY: Missing seizure metadata (seizure_officer, seizure_reason, lab_remark)
 * between the case docket JSON and relational exhibits table is reconciled
 * systematically without cross-suspect data leakage.
 */
class RepairExhibitService
{
    /**
     * Repair exhibit associations for the given case.
     *
     * @param mixed $case
     * @return bool
     */
    public function repair(&$case): bool
    {
        if (!$case || empty($case->docket_data)) {
            return false;
        }

        $changed = false;
        $docketData = is_array($case->docket_data)
            ? $case->docket_data
            : (json_decode($case->docket_data, true) ?: []);

        if (!empty($docketData['seizures']) && is_array($docketData['seizures'])) {
            $isSingle = ($case->underlyingRecord instanceof SingleSuspectCase);
            $exList = Exhibit::where(function ($q) use ($case, $isSingle) {
                if ($isSingle) {
                    $q->where('single_suspect_case_id', $case->id);
                } else {
                    $q->where('multiple_suspect_case_id', $case->id);
                }
            })->get();

            $docketByExNo = [];
            foreach ($docketData['seizures'] as $sz) {
                $exNo = strtolower(trim($sz['exhibit_number'] ?? ''));
                if (!empty($exNo)) {
                    $docketByExNo[$exNo] = $sz;
                }
            }

            foreach ($exList as $idx => $exItem) {
                $dbExNo = strtolower(trim($exItem->exhibit_number ?? ''));
                $szDoc = (!empty($dbExNo) && isset($docketByExNo[$dbExNo]))
                    ? $docketByExNo[$dbExNo]
                    : ($docketData['seizures'][$idx] ?? null);

                if (empty($szDoc)) {
                    continue;
                }

                $exUpdates = [];
                if (empty($exItem->seizure_officer) && !empty($szDoc['seizure_officer'])) {
                    $exUpdates['seizure_officer'] = $szDoc['seizure_officer'];
                }
                if (empty($exItem->investigating_officer) && !empty($szDoc['investigating_officer'])) {
                    $exUpdates['investigating_officer'] = $szDoc['investigating_officer'];
                }
                if (empty($exItem->source_requesting_unit) && !empty($szDoc['source_requesting_unit'])) {
                    $exUpdates['source_requesting_unit'] = $szDoc['source_requesting_unit'];
                }
                if (empty($exItem->lab_exhibit_no) && !empty($szDoc['lab_exhibit_no'])) {
                    $exUpdates['lab_exhibit_no'] = $szDoc['lab_exhibit_no'];
                }
                if (empty($exItem->seizure_reason) && !empty($szDoc['seizure_reason'])) {
                    $exUpdates['seizure_reason'] = $szDoc['seizure_reason'];
                }
                if (empty($exItem->lab_remark) && !empty($szDoc['lab_remark'])) {
                    $exUpdates['lab_remark'] = $szDoc['lab_remark'];
                } elseif (empty($exItem->lab_remark) && !empty($case->confirmatory_remark) && $idx === 0) {
                    $exUpdates['lab_remark'] = $case->confirmatory_remark;
                }

                if (!empty($exUpdates)) {
                    $exItem->update($exUpdates);
                    $changed = true;
                }
            }
        }

        if ($changed && $case && method_exists($case, 'save')) {
            $case->save();
        }

        return $changed;
    }
}
