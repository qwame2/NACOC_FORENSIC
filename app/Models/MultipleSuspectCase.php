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
        'commanding_officer',
        'submitting_officer',
        'operation_code',
        'operation_code_name',
        'raid_location',
        'offense_description',
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
}
