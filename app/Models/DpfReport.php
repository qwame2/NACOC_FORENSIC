<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DpfReport extends Model
{
    use HasFactory;

    protected $table = 'dpf_report';

    protected $guarded = [];

    protected $casts = [
        'investigation_date' => 'date',
        'report_data'        => 'array',
    ];

    /**
     * Relationship to SingleSuspectCase
     */
    public function singleSuspectCase()
    {
        return $this->belongsTo(SingleSuspectCase::class, 'single_suspect_case_id');
    }

    /**
     * Relationship to MultipleSuspectCase
     */
    public function multipleSuspectCase()
    {
        return $this->belongsTo(MultipleSuspectCase::class, 'multiple_suspect_case_id');
    }

    /**
     * Relationship to CaseSuspect
     */
    public function suspect()
    {
        return $this->belongsTo(CaseSuspect::class, 'suspect_id');
    }

    /**
     * Relationship to User (Analyst / Submitter)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Helper to find a DPF Report by case identifier, case_number, or laboratory_number
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

    /**
     * Get array of court order file paths
     */
    public function getCourtOrderFilesAttribute(): array
    {
        $raw = $this->court_order_file;
        if (empty($raw)) return [];
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) return $decoded;
        return [$raw];
    }

    /**
     * Get array of report & witness statement file paths
     */
    public function getReportWitnessStatementFilesAttribute(): array
    {
        $raw = $this->report_witness_statement_file;
        if (empty($raw)) return [];
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) return $decoded;
        return [$raw];
    }
}
