<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrugScreeningMedication extends Model
{
    use HasFactory;

    protected $table = 'drug_screening_medications';

    protected $fillable = [
        'drug_screening_id',
        'medication_name',
        'dosage',
        'form',
        'frequency',
        'route',
        'prescription_source',
        'date_started',
        'duration_ongoing',
        'last_dose_taken',
    ];

    protected $casts = [
        'date_started'    => 'date',
        'last_dose_taken' => 'datetime',
    ];

    /**
     * Relationship to parent DrugScreening record.
     */
    public function drugScreening(): BelongsTo
    {
        return $this->belongsTo(DrugScreening::class, 'drug_screening_id');
    }

    /**
     * Human-friendly label for prescription source.
     */
    public function getSourceLabelAttribute(): string
    {
        return match ($this->prescription_source) {
            'doctor'            => 'Doctor Prescribed',
            'otc'               => 'Over The Counter (OTC)',
            'self_administered' => 'Self-Administered',
            default             => $this->prescription_source ?: 'Unspecified',
        };
    }

    /**
     * Formatted string of medication and dosage.
     */
    public function getDisplayNameAttribute(): string
    {
        $name = trim($this->medication_name ?? '');
        $dosage = trim($this->dosage ?? '');
        if ($dosage !== '') {
            return "{$name} {$dosage}";
        }
        return $name;
    }
}

