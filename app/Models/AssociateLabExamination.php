<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssociateLabExamination extends Model
{
    use HasFactory;

    protected $table = 'associate_lab_examinations';

    protected $fillable = [
        'case_id',
        'suspect_name',
        'associate_id',
        'exhibit_number',
        'laboratory_number',
        'exam_reference',
        'lab_unit',
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
        'remark',
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

    public function case()
    {
        return $this->belongsTo(ForensicCase::class, 'case_id');
    }
}
