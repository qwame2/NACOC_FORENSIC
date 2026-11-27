<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseSuspect extends Model
{
    use HasFactory;

    protected $attributes = [
        'suspect_disposition' => 'Active — Under Investigation',
    ];

    protected $fillable = [
        'single_suspect_case_id',
        'multiple_suspect_case_id',
        'suspect_number',
        'role',
        'associate_id',
        'status',
        'suspect_name',
        'suspect_alias',
        'ghana_card',
        'passport_number',
        'flight_number',
        'airline',
        'destination',
        'substance_expelled',
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
        'latitude',
        'longitude',
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

    public function setStatusAttribute($value)
    {
        if (in_array(strtolower(trim((string)$value)), ['approved', 'pending approval', 'under multi-unit processing', 'completed', 'awaiting sign-off', 'pending review'], true)) {
            return;
        }
        $this->attributes['suspect_disposition'] = $value;
    }

    public function getStatusAttribute()
    {
        return $this->attributes['suspect_disposition'] ?? null;
    }

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

    public function getMugshotFrontUrlAttribute(): ?string
    {
        $front = $this->mugshot_front;

        if (empty($front) && !empty($this->extra_mugshots)) {
            $extra = is_array($this->extra_mugshots) ? $this->extra_mugshots : json_decode($this->extra_mugshots, true);
            $front = $extra['front'] ?? ($extra['front-profile'] ?? ($extra['photo'] ?? ($extra[0] ?? null)));
        }

        if (empty($front)) {
            $front = $this->mugshot_left ?: ($this->mugshot_right ?: null);
        }

        if (empty($front) && $this->singleCase) {
            $front = $this->singleCase->mugshot_front;
            if (empty($front) && !empty($this->singleCase->extra_mugshots)) {
                $scExtra = is_array($this->singleCase->extra_mugshots) ? $this->singleCase->extra_mugshots : json_decode($this->singleCase->extra_mugshots, true);
                $front = $scExtra['front'] ?? ($scExtra['front-profile'] ?? ($scExtra['photo'] ?? ($scExtra[0] ?? null)));
            }
            if (empty($front)) {
                $front = $this->singleCase->mugshot_left ?: ($this->singleCase->mugshot_right ?: null);
            }
        }

        if (empty($front) && $this->multipleCase && !empty($this->multipleCase->docket_data)) {
            $docket = is_array($this->multipleCase->docket_data)
                ? $this->multipleCase->docket_data
                : json_decode((string) $this->multipleCase->docket_data, true);
            if (is_array($docket) && !empty($docket['suspects']) && is_array($docket['suspects'])) {
                $targetName = strtolower(trim((string) $this->suspect_name));
                foreach ($docket['suspects'] as $sEntry) {
                    if (!is_array($sEntry)) {
                        continue;
                    }
                    $entryName = strtolower(trim((string) ($sEntry['suspect_name'] ?? ($sEntry['name'] ?? ''))));
                    if ($targetName !== '' && $entryName === $targetName) {
                        $front = $sEntry['mugshot_front']
                            ?? ($sEntry['mugshots']['front'] ?? ($sEntry['photo'] ?? null));
                        if (!empty($front)) {
                            break;
                        }
                    }
                }
            }
        }

        if (empty($front) && !empty($this->ghana_card) && $this->ghana_card !== 'N/A') {
            $other = static::where('ghana_card', $this->ghana_card)
                ->where('id', '!=', $this->id)
                ->whereNotNull('mugshot_front')
                ->where('mugshot_front', '!=', '')
                ->first();
            if ($other) {
                $front = $other->mugshot_front;
            }
        }

        if (empty($front) && !empty($this->suspect_name)) {
            $scByName = SingleSuspectCase::where('suspect_name', $this->suspect_name)
                ->whereNotNull('mugshot_front')
                ->where('mugshot_front', '!=', '')
                ->latest('id')
                ->first();
            if ($scByName) {
                $front = $scByName->mugshot_front;
            }
        }

        return \App\Services\CasePhotoResolver::resolveMugshotUrl($front);
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

    public function expellingReports()
    {
        return $this->hasMany(SuspectExpellingReport::class, 'case_suspect_id');
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


