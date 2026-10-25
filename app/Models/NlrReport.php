<?php

namespace App\Models;

use App\Models\Concerns\EnforcesMutuallyExclusiveCases;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NlrReport extends Model
{
    use HasFactory, EnforcesMutuallyExclusiveCases;

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

        // 1. If unique case_number is provided, query strictly by case_number first
        if (!empty($caseNo)) {
            $rec = (clone $query)->where('case_number', (string)$caseNo)->latest('id')->first();
            if ($rec) return $rec;
        }

        // 2. If non-numeric string was passed as $caseId (e.g. case_number passed as first argument)
        if (!empty($caseId) && !is_numeric($caseId)) {
            $rec = (clone $query)->where('case_number', (string)$caseId)->latest('id')->first();
            if ($rec) return $rec;
        }

        // 3. If laboratory_number is provided
        if (!empty($labNo)) {
            $cleanLab = str_replace('Lab No. ', '', (string)$labNo);
            $rec = (clone $query)->where(function($q) use ($labNo, $cleanLab) {
                $q->where('laboratory_number', (string)$labNo)
                  ->orWhere('laboratory_number', $cleanLab);
            })->latest('id')->first();
            if ($rec) return $rec;
        }

        // 4. Fallback to numeric ID only if case_number was not matched
        if (!empty($caseId) && is_numeric($caseId)) {
            $rec = (clone $query)->where(function ($q) use ($caseId) {
                $q->where('single_suspect_case_id', (int)$caseId)
                  ->orWhere('multiple_suspect_case_id', (int)$caseId);
            })->latest('id')->first();
            if ($rec) return $rec;
        }

        return null;
    }
}
