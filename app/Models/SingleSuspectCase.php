<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SingleSuspectCase extends Model
{
    use HasFactory;

    protected $table = 'single_suspect_cases';

    protected $fillable = [
        'case_number',
        'laboratory_number',
        'title',
        'unit_name',
        'unit_id',
        'user_id',
        'commanding_officer',
        'submitting_officer',
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
        'operation_code',
        'operation_code_name',
        'raid_location',
        'offense_description',
        'arrest_date',
        'arrest_time',
        'incident_date',
        'mugshot_front',
        'mugshot_left',
        'mugshot_right',
        'extra_mugshots',
        'priority',
        'status',
        'hash_log',
    ];

    protected $casts = [
        'extra_mugshots' => 'array',
        'has_children'   => 'boolean',
    ];



    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function exhibits()
    {
        return $this->hasMany(Exhibit::class, 'single_suspect_case_id');
    }

    public function labResults()
    {
        return $this->hasMany(LaboratoryExaminationResult::class, 'single_suspect_case_id');
    }

    public function primaryLabResult()
    {
        return $this->hasOne(LaboratoryExaminationResult::class, 'single_suspect_case_id')->latestOfMany();
    }
}
