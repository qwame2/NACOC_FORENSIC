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
        'detected_substances',
        'detected_subtances_percentage',
        'test_methods',
        'specifications',
        'remark',
        'disposition_of_exhibit',
        'findings',
        'has_saved',
    ];

    protected $casts = [
        'detected_substances'            => 'array',
        'detected_subtances_percentage'  => 'array',
        'has_saved'                      => 'boolean',
        'date_received'                  => 'date:Y-m-d',
        'date_started'                   => 'date:Y-m-d',
        'date_completed'                 => 'date:Y-m-d',
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

    protected ?string $transientSubstanceIdentification = null;

    public function setSubstanceIdentificationAttribute($value)
    {
        // Column substance_identification deleted from database; safely keep transient in memory
        $val = is_scalar($value) ? trim((string)$value) : null;
        if ($val === '[]' || $val === '') {
            $val = null;
        }
        $this->transientSubstanceIdentification = $val;
        if ($val && (empty($this->attributes['detected_substances']) || $this->attributes['detected_substances'] === '[]')) {
            $this->setDetectedSubstancesAttribute([$val]);
        }
    }

    public function getSubstanceIdentificationAttribute()
    {
        if (!empty($this->transientSubstanceIdentification) && $this->transientSubstanceIdentification !== '[]') {
            return $this->transientSubstanceIdentification;
        }

        $subs = $this->detected_substances;
        if (is_string($subs)) {
            $subs = json_decode($subs, true) ?: [];
        }
        if (is_array($subs) && !empty($subs)) {
            $first = $subs[0];
            if (is_array($first)) {
                $candidate = $first['substance'] ?? ($first['name'] ?? null);
            } else {
                $candidate = is_string($first) ? $first : null;
            }
            if (!empty($candidate) && $candidate !== '[]') {
                return $candidate;
            }
        }

        if (!empty($this->sample_weight_description)) {
            return $this->sample_weight_description;
        }

        return null;
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
            return json_decode($value, true) ?: (trim($value) !== '' ? [trim($value)] : []);
        }
        return [];
    }

    public function setDetectedSubstancesAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            }
        }

        if (is_array($value)) {
            $substances = [];
            $percentages = [];
            $hasStructuredPercentages = false;

            foreach ($value as $item) {
                if (is_array($item)) {
                    $hasStructuredPercentages = true;
                    $sName = trim((string)($item['substance'] ?? ($item['name'] ?? ($item['compound'] ?? ''))));
                    $sPct = trim((string)($item['percentage'] ?? ($item['purity'] ?? ($item['concentration'] ?? ''))));
                    if ($sName !== '') {
                        $substances[] = $sName;
                    }
                    if ($sPct !== '' && strtoupper($sPct) !== 'N/A' && $sPct !== '—') {
                        if (is_numeric(str_replace('%', '', $sPct)) && !str_ends_with($sPct, '%')) {
                            $sPct .= '%';
                        }
                        $percentages[] = $sPct;
                    }
                } elseif (is_scalar($item) && trim((string)$item) !== '') {
                    $substances[] = trim((string)$item);
                }
            }

            if (empty($substances) && !empty($this->transientSubstanceIdentification) && $this->transientSubstanceIdentification !== '[]') {
                $substances[] = $this->transientSubstanceIdentification;
            }

            $this->attributes['detected_substances'] = json_encode($substances);

            if ($hasStructuredPercentages && !empty($percentages)) {
                if (empty($this->attributes['detected_subtances_percentage'])) {
                    $pctJson = json_encode($percentages);
                    $this->attributes['detected_subtances_percentage'] = $pctJson;
                }
            }
            return;
        }

        $this->attributes['detected_substances'] = $value;
    }

    public function setDetectedSubtancesPercentageAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            } else {
                $value = array_values(array_filter(array_map('trim', preg_split('/\s*[;,]\s*/', $value) ?: []), fn($v) => $v !== ''));
            }
        }

        if (is_array($value)) {
            $formatted = [];
            foreach ($value as $pct) {
                if (is_scalar($pct)) {
                    $p = trim((string)$pct);
                    if ($p !== '' && strtoupper($p) !== 'N/A' && $p !== '—') {
                        if (is_numeric(str_replace('%', '', $p)) && !str_ends_with($p, '%')) {
                            $p .= '%';
                        }
                        $formatted[] = $p;
                    }
                }
            }
            $this->attributes['detected_subtances_percentage'] = json_encode($formatted);
            return;
        }

        $this->attributes['detected_subtances_percentage'] = $value;
    }

    public function setDetectedSubstancesPercentageAttribute($value)
    {
        $this->setDetectedSubtancesPercentageAttribute($value);
    }

    public function getDetectedSubtancesPercentageAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            return trim($value) !== '' ? [trim($value)] : [];
        }
        return [];
    }

    public function getDetectedSubstancesPercentageAttribute()
    {
        return $this->getDetectedSubtancesPercentageAttribute($this->attributes['detected_subtances_percentage'] ?? null);
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
