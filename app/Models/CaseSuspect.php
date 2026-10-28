<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseSuspect extends Model
{
    use HasFactory;

    protected $attributes = [
        'suspect_disposition' => 'Active â€” Under Investigation',
    ];

    protected $fillable = [
        'single_suspect_case_id',
        'multiple_suspect_case_id',
        'suspect_number',
        'role',
        'associate_id',
        'suspect_name',
        'suspect_alias',
        'ghana_card',
        'gender',
        'dob',
        'age',
        'height',
        'height_unit',
        'eye_color',
        'nationality',
        'pob',
        'phone',
        'phones',
        'address',
        'marital_status',
        'has_children',
        'children_count',
        'spouse_info',
        'spouse_name',
        'spouse_phone',
        'partner_name',
        'partner_phone',
        'partner_info',
        'dependents',
        'holding_facility',
        'raid_location',
        'offense_description',
        'arrest_date',
        'arrest_time',
        'mugshot_front',
        'mugshot_left',
        'mugshot_right',
        'extra_mugshots',
        'case_status',
        'suspect_disposition',
        'disposition_reason',
        'disposition_updated_at',
        'disposition_updated_by',
    ];

    protected $casts = [
        'extra_mugshots' => 'array',
        'has_children'   => 'boolean',
        'phones'         => 'array',
    ];

    public function singleCase()
    {
        return $this->belongsTo(SingleSuspectCase::class, 'single_suspect_case_id');
    }

    public function multipleCase()
    {
        return $this->belongsTo(MultipleSuspectCase::class, 'multiple_suspect_case_id');
    }

    public function multipleSuspectCase()
    {
        return $this->multipleCase();
    }

    public function case()
    {
        return $this->single_suspect_case_id ? $this->singleCase() : $this->multipleCase();
    }

    public function getCaseNumberAttribute()
    {
        return $this->singleCase?->case_number ?? ($this->multipleCase?->case_number ?? null);
    }

    public function exhibits()
    {
        return $this->hasMany(Exhibit::class, 'suspect_id');
    }

    public function labResults()
    {
        return $this->hasMany(LaboratoryExaminationResult::class, 'suspect_id');
    }

    public function gsaReports()
    {
        return $this->hasMany(SuspectGsaReport::class, 'case_suspect_id');
    }

    public function nlrReports()
    {
        return $this->hasMany(SuspectNlrReport::class, 'case_suspect_id');
    }

    public function drugScreenings()
    {
        return $this->hasMany(DrugScreening::class, 'suspect_id');
    }

    public function latestDrugScreening()
    {
        return $this->hasOne(DrugScreening::class, 'suspect_id')->latestOfMany();
    }

    public function getDrugScreeningAttribute()
    {
        return $this->latestDrugScreening;
    }
}


