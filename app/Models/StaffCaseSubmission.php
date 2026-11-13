<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffCaseSubmission extends Model
{
    protected $table = 'staff_case_submissions';

    protected $fillable = [
        'submitted_by',
        'submitted_by_name',
        'unit_name',
        'case_number_preview',
        'title',
        'suspect_name',
        'priority',
        'status',
        'payload',
        'reviewed_by',
        'reviewed_by_name',
        'reviewed_at',
        'review_notes',
        'approved_case_number',
    ];

    protected $casts = [
        'payload' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function scopeForUnit($query, string $unitContext)
    {
        $u = strtolower(trim($unitContext));
        if ($u === '' || str_contains($u, 'directorate') || str_contains($u, 'forensic_head') || str_contains($u, 'forensic head')) {
            return $query;
        }

        if (str_contains($u, 'lab')) {
            return $query->where('unit_name', 'LIKE', '%Lab%');
        }
        if (str_contains($u, 'evidence') || str_contains($u, 'vault')) {
            return $query->where('unit_name', 'LIKE', '%Evidence%');
        }
        if (str_contains($u, 'digital') || str_contains($u, 'physical') || str_contains($u, 'cyber')) {
            return $query->where(function ($q) {
                $q->where('unit_name', 'LIKE', '%Digital%')
                  ->orWhere('unit_name', 'LIKE', '%Physical%');
            });
        }

        return $query->where('unit_name', 'LIKE', '%Crime%');
    }
}

