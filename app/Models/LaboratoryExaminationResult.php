<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaboratoryExaminationResult extends Model
{
    use HasFactory;

    protected $table = 'laboratory_examination_results';

    protected $fillable = [
        'case_type',
        'single_suspect_case_id',
        'multiple_suspect_case_id',
        'suspect_id',
        'exhibit_id',
        'laboratory_number',
        'exam_reference',
        'analyst',
        'date_received',
        'date_started',
        'date_completed',
        'presumptive_result',
        'confirmatory_result',
        'sample_weight_description',
        'sample_weight_units',
        'sample_weight_gross',
        'sample_weight_taken',
        'sample_weight_reserved',
        'substance_identification',
        'detected_substances',
        'test_methods',
        'specifications',
        'findings',
        'has_saved',
    ];

    protected $casts = [
        'detected_substances' => 'array',
        'has_saved'           => 'boolean',
        'date_received'       => 'date:Y-m-d',
        'date_started'        => 'date:Y-m-d',
        'date_completed'      => 'date:Y-m-d',
    ];

    public function singleCase()
    {
        return $this->belongsTo(SingleSuspectCase::class, 'single_suspect_case_id');
    }

    public function multipleCase()
    {
        return $this->belongsTo(MultipleSuspectCase::class, 'multiple_suspect_case_id');
    }

    public function suspect()
    {
        return $this->belongsTo(CaseSuspect::class, 'suspect_id');
    }

    public function exhibit()
    {
        return $this->belongsTo(Exhibit::class, 'exhibit_id');
    }
}
