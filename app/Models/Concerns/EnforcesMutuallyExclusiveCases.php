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
            $hasCaseType = array_key_exists('case_type', $model->getAttributes());
            $caseType = $hasCaseType && !empty($model->case_type) ? strtoupper(trim($model->case_type)) : null;

            $singleId = $model->single_suspect_case_id ?? null;
            $multiId  = $model->multiple_suspect_case_id ?? null;

            // Scenario 1: Explicit case_type defined
            if ($caseType === 'SINGLE') {
                $model->multiple_suspect_case_id = null;
            } elseif ($caseType === 'MULTIPLE') {
                $model->single_suspect_case_id = null;
            }
            // Scenario 2: Overlapping non-null IDs without explicit case_type
            elseif (!empty($singleId) && !empty($multiId)) {
                if ($singleId === $multiId) {
                    // Both IDs identical (the overlap anomaly) - prioritize multiple case or single based on dirty fields
                    if ($model->isDirty('multiple_suspect_case_id') && !$model->isDirty('single_suspect_case_id')) {
                        $model->single_suspect_case_id = null;
                        if ($hasCaseType) $model->case_type = 'MULTIPLE';
                    } elseif ($model->isDirty('single_suspect_case_id') && !$model->isDirty('multiple_suspect_case_id')) {
                        $model->multiple_suspect_case_id = null;
                        if ($hasCaseType) $model->case_type = 'SINGLE';
                    } else {
                        // Default to MULTIPLE as the primary relational model for multi-party dockets
                        $model->single_suspect_case_id = null;
                        if ($hasCaseType) $model->case_type = 'MULTIPLE';
                    }
                } else {
                    // Two different IDs supplied simultaneously: keep the dirty/active one
                    if ($model->isDirty('multiple_suspect_case_id')) {
                        $model->single_suspect_case_id = null;
                        if ($hasCaseType) $model->case_type = 'MULTIPLE';
                    } else {
                        $model->multiple_suspect_case_id = null;
                        if ($hasCaseType) $model->case_type = 'SINGLE';
                    }
                }
            }
            // Scenario 3: Align case_type with the single active ID
            elseif ($hasCaseType && empty($caseType)) {
                if (!empty($multiId)) {
                    $model->case_type = 'MULTIPLE';
                } elseif (!empty($singleId)) {
                    $model->case_type = 'SINGLE';
                }
            }
        });
    }
}
