<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MultipleSuspectCase extends Model
{
    use HasFactory;

    protected $table = 'multiple_suspect_cases';

    protected $fillable = [
        'id',
        'case_number',
        'laboratory_number',
        'title',
        'unit_name',
        'unit_id',
        'user_id',
        'arresting_officer',
        'submitting_officer',
        'operation_code',
        'operation_code_name',
        'search_warrant_document',
        'raid_location',
        'latitude',
        'longitude',
        'offense_description',
        'description_of_exhibits',
        'incident_date',
        'docket_summary',
        'total_suspects_count',
        'total_exhibits_count',
        'priority',
        'status',
        'hash_log',
        'confirmatory_remark',
        'case_status',
        'controller_user_id',
        'suspect_disposition',
        'disposition_reason',
        'disposition_updated_at',
        'disposition_updated_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function controllerUser()
    {
        return $this->belongsTo(User::class, 'controller_user_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function suspects()
    {
        return $this->hasMany(CaseSuspect::class, 'multiple_suspect_case_id')->orderBy('suspect_number');
    }

    public function primarySuspect()
    {
        return $this->hasOne(CaseSuspect::class, 'multiple_suspect_case_id')->where('role', 'Primary Suspect');
    }

    public function coSuspects()
    {
        return $this->hasMany(CaseSuspect::class, 'multiple_suspect_case_id')->where('role', 'Co-Suspect');
    }

    public function associateSuspects()
    {
        return $this->hasMany(CaseSuspect::class, 'multiple_suspect_case_id')->where('role', 'Associate Suspect');
    }

    public function exhibits()
    {
        return $this->hasMany(Exhibit::class, 'multiple_suspect_case_id');
    }

    public function labResults()
    {
        return $this->hasMany(LaboratoryExaminationResult::class, 'multiple_suspect_case_id');
    }

    public function gsaReports()
    {
        return $this->hasMany(SuspectGsaReport::class, 'multiple_suspect_case_id');
    }

    public function nlrReports()
    {
        return $this->hasMany(SuspectNlrReport::class, 'multiple_suspect_case_id');
    }

    public function getSubmittingOfficerAttribute()
    {
        return $this->attributes['arresting_officer'] ?? ($this->attributes['submitting_officer'] ?? null);
    }

    public function setSubmittingOfficerAttribute($value)
    {
        if (array_key_exists('arresting_officer', $this->attributes) || (!array_key_exists('submitting_officer', $this->attributes) && \Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'arresting_officer'))) {
            $this->attributes['arresting_officer'] = $value;
        } else {
            $this->attributes['submitting_officer'] = $value;
        }
    }

    public function getArrestingOfficerAttribute()
    {
        return $this->attributes['arresting_officer'] ?? ($this->attributes['submitting_officer'] ?? null);
    }

    public function setArrestingOfficerAttribute($value)
    {
        $this->attributes['arresting_officer'] = $value;
    }
}

