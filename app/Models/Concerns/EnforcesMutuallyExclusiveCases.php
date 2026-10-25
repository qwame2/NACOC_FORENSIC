<?php

namespace App\Models\Concerns;

/**
 * Trait EnforcesMutuallyExclusiveCases
 *
 * Ensures that child records (such as Exhibits, Lab Examinations, NLR Reports, etc.)
 * cannot simultaneously belong to both a SingleSuspectCase and a MultipleSuspectCase.
 *
 * If both IDs are set, or if an ID conflicts with the case_type, the trait resolves
 * the conflict prior to saving, protecting against cross-case data leakage.
 */
trait EnforcesMutuallyExclusiveCases
{
    public static function bootEnforcesMutuallyExclusiveCases(): void
    {
        static::saving(function ($model) {
            $hasCaseTypeCol = in_array($model->getTable(), ['exhibits', 'laboratory_examination_results']);
            $explicitCaseType = !empty($model->case_type) ? strtoupper(trim($model->case_type)) : null;

            $singleId = $model->single_suspect_case_id ?? null;
            $multiId  = $model->multiple_suspect_case_id ?? null;

            // Priority 1: Explicit case_type supplied
            if ($explicitCaseType === 'SINGLE') {
                $model->multiple_suspect_case_id = null;
                if ($hasCaseTypeCol) $model->case_type = $model->case_type ?: 'SINGLE';
                return;
            } elseif ($explicitCaseType === 'MULTIPLE') {
                $model->single_suspect_case_id = null;
                if ($hasCaseTypeCol) $model->case_type = $model->case_type ?: 'MULTIPLE';
                return;
            }

            // Priority 2: Overlapping non-null IDs without explicit case_type
            if (!empty($singleId) && !empty($multiId)) {
                if ($model->isDirty('multiple_suspect_case_id') && !$model->isDirty('single_suspect_case_id')) {
                    $model->single_suspect_case_id = null;
                    if ($hasCaseTypeCol) $model->case_type = 'MULTIPLE';
                } elseif ($model->isDirty('single_suspect_case_id') && !$model->isDirty('multiple_suspect_case_id')) {
                    $model->multiple_suspect_case_id = null;
                    if ($hasCaseTypeCol) $model->case_type = 'SINGLE';
                } else {
                    $model->single_suspect_case_id = null;
                    if ($hasCaseTypeCol) $model->case_type = 'MULTIPLE';
                }
                return;
            }

            // Priority 3: Align case_type with active case foreign key
            if ($hasCaseTypeCol) {
                if (!empty($multiId)) {
                    $model->single_suspect_case_id = null;
                    $model->case_type = 'MULTIPLE';
                } elseif (!empty($singleId)) {
                    $model->multiple_suspect_case_id = null;
                    $model->case_type = 'SINGLE';
                }
            }
        });
    }
}
