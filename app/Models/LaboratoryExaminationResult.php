<?php

namespace App\Models;

use App\Models\Concerns\EnforcesMutuallyExclusiveCases;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaboratoryExaminationResult extends Model
{
    use HasFactory, EnforcesMutuallyExclusiveCases;

    protected $table = 'laboratory_examination_results';

    protected $fillable = [
        'case_type',
        'single_suspect_case_id',
        'multiple_suspect_case_id',
        'suspect_id',
        'exhibit_id',
        'laboratory_number',
        'gsa_lab_number',
        'exam_reference',
        'purpose_of_examination',
        'preliminary_exam_procedure',
        'screening_method',
        'analyst',
        'date_received',
        'date_started',
        'date_completed',
        'presumptive_result',
        'presumptive_presence',
        'presumptive_percentage',
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
        'disposition_of_exhibit',
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

    protected function sanitizeStringOrArrayField($value): ?string
    {
        if (is_array($value)) {
            $filtered = [];
            foreach ($value as $item) {
                if (is_array($item)) {
                    $item = json_encode($item);
                }
                $str = trim((string)$item);
                if ($str !== '') {
                    $filtered[] = $str;
                }
            }
            return count($filtered) > 0 ? implode(', ', $filtered) : null;
        }
        return (is_string($value) && trim($value) !== '') ? trim($value) : (is_scalar($value) ? (string) $value : null);
    }

    public function setScreeningMethodAttribute($value)
    {
        $this->attributes['screening_method'] = $this->sanitizeStringOrArrayField($value);
    }

    public function setTestMethodsAttribute($value)
    {
        $this->attributes['test_methods'] = $this->sanitizeStringOrArrayField($value);
    }

    public function setPreliminaryExamProcedureAttribute($value)
    {
        $this->attributes['preliminary_exam_procedure'] = $this->sanitizeStringOrArrayField($value);
    }

    public function setPurposeOfExaminationAttribute($value)
    {
        $this->attributes['purpose_of_examination'] = $this->sanitizeStringOrArrayField($value);
    }

    public function setDispositionOfExhibitAttribute($value)
    {
        $this->attributes['disposition_of_exhibit'] = $this->sanitizeStringOrArrayField($value);
    }

    public function setFindingsAttribute($value)
    {
        $this->attributes['findings'] = $this->sanitizeStringOrArrayField($value);
    }

    public function setSpecificationsAttribute($value)
    {
        $this->attributes['specifications'] = $this->sanitizeStringOrArrayField($value);
    }

    public function setRemarkAttribute($value)
    {
        $this->attributes['remark'] = $this->sanitizeStringOrArrayField($value);
    }

    public function setAnalystAttribute($value)
    {
        $this->attributes['analyst'] = $this->sanitizeStringOrArrayField($value);
    }

    public function setPresumptiveResultAttribute($value)
    {
        $this->attributes['presumptive_result'] = $this->sanitizeStringOrArrayField($value);
    }

    public function setPresumptivePresenceAttribute($value)
    {
        $this->attributes['presumptive_presence'] = $this->sanitizeStringOrArrayField($value);
    }

    public function setPresumptivePercentageAttribute($value)
    {
        $this->attributes['presumptive_percentage'] = $this->sanitizeStringOrArrayField($value);
    }

    public function setConfirmatoryResultAttribute($value)
    {
        $this->attributes['confirmatory_result'] = $this->sanitizeStringOrArrayField($value) ?? 'Pending';
    }

    public function setSubstanceIdentificationAttribute($value)
    {
        $this->attributes['substance_identification'] = $this->sanitizeStringOrArrayField($value);
    }

    public function setSampleWeightDescriptionAttribute($value)
    {
        $this->attributes['sample_weight_description'] = $this->sanitizeStringOrArrayField($value);
    }

    public function setSampleWeightUnitsAttribute($value)
    {
        $this->attributes['sample_weight_units'] = $this->sanitizeStringOrArrayField($value);
    }

    public function setSampleWeightGrossAttribute($value)
    {
        $this->attributes['sample_weight_gross'] = $this->sanitizeStringOrArrayField($value);
    }

    public function setSampleWeightTakenAttribute($value)
    {
        $this->attributes['sample_weight_taken'] = $this->sanitizeStringOrArrayField($value);
    }

    public function setSampleWeightReservedAttribute($value)
    {
        $this->attributes['sample_weight_reserved'] = $this->sanitizeStringOrArrayField($value);
    }

    public function setExamReferenceAttribute($value)
    {
        $this->attributes['exam_reference'] = $this->sanitizeStringOrArrayField($value);
    }

    public function setLaboratoryNumberAttribute($value)
    {
        $this->attributes['laboratory_number'] = $this->sanitizeStringOrArrayField($value);
    }

    public function getDetectedSubstancesAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            return json_decode($value, true) ?: [];
        }
        return [];
    }

    public function setDetectedSubstancesAttribute($value)
    {
        $this->attributes['detected_substances'] = is_array($value) ? json_encode($value) : $value;
    }

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
