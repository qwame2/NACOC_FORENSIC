<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NlrReport extends Model
{
    use HasFactory;

    protected $table = 'nlr_reports';

    protected $fillable = [
        'case_number',
        'laboratory_number',
        'nlr_code',
        'single_suspect_case_id',
        'multiple_suspect_case_id',
        'suspect_id',
        'detailed_exhibit_description',
        'condition_of_reserved_sample',
        'analyst',
        'user_id',
        'status',
    ];

    /**
     * Helper to find an NLR Report record by case identifier, case_number, or laboratory_number
     */
    public static function forCase($caseId = null, $caseNo = null, $labNo = null)
    {
        $query = static::query();

        $hasCondition = false;

        if (!empty($caseId)) {
            $query->where(function ($q) use ($caseId) {
                $q->where('single_suspect_case_id', $caseId)
                  ->orWhere('multiple_suspect_case_id', $caseId);
            });
            $hasCondition = true;
        }

        if (!empty($caseNo)) {
            if ($hasCondition) {
                $query->orWhere('case_number', $caseNo);
            } else {
                $query->where('case_number', $caseNo);
                $hasCondition = true;
            }
        }

        if (!empty($labNo)) {
            $cleanLab = str_replace('Lab No. ', '', $labNo);
            if ($hasCondition) {
                $query->orWhere('laboratory_number', $labNo)
                      ->orWhere('laboratory_number', $cleanLab);
            } else {
                $query->where(function($q) use ($labNo, $cleanLab) {
                    $q->where('laboratory_number', $labNo)
                      ->orWhere('laboratory_number', $cleanLab);
                });
                $hasCondition = true;
            }
        }

        if (!$hasCondition) {
            return null;
        }

        return $query->latest('id')->first();
    }
}
