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
        'arresting_officer',
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
        'operation_code',
        'operation_code_name',
        'search_warrant_document',
        'raid_location',
        'latitude',
        'longitude',
        'offense_description',
        'description_of_exhibits',
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
        'confirmatory_remark',
        'case_status',
        'controller_user_id',
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

    public function suspect()
    {
        return $this->hasOne(CaseSuspect::class, 'single_suspect_case_id');
    }

    public function suspects()
    {
        return $this->hasMany(CaseSuspect::class, 'single_suspect_case_id');
    }

    public function gsaReports()
    {
        return $this->hasMany(SuspectGsaReport::class, 'single_suspect_case_id');
    }

    public function nlrReports()
    {
        return $this->hasMany(SuspectNlrReport::class, 'single_suspect_case_id');
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

    public function isPendingApproval(): bool
    {
        $status = strtolower(trim((string) ($this->status ?? '')));
        $caseStatus = strtolower(trim((string) ($this->case_status ?? '')));

        if (in_array($status, ['pending approval', 'awaiting approval', 'pending review', 'awaiting sign-off', 'pending'], true)) {
            return true;
        }

        if (in_array($caseStatus, ['pending approval', 'awaiting approval', 'pending review', 'awaiting sign-off'], true)) {
            return true;
        }

        if (!empty($this->case_number)) {
            return \App\Models\StaffCaseSubmission::where('case_number_preview', $this->case_number)
                ->whereIn('status', ['Pending Approval', 'Awaiting Approval', 'Pending Review'])
                ->exists();
        }

        return false;
    }
}
