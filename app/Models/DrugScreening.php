<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DrugScreening extends Model
{
    use HasFactory;

    protected $table = 'drug_screening';

    protected $fillable = [
        'suspect_id',
        'civilian_name',
        'purpose',
        'screening_date',
        'result',
        'substances_found',
        'notes',
        'on_medication',
        'medication_summary',
    ];

    protected $casts = [
        'screening_date' => 'date',
        'on_medication'  => 'boolean',
    ];

    /**
     * Virtual accessor: map 'note' to 'notes' property if accessed.
     */
    public function getNoteAttribute()
    {
        return $this->attributes['notes'] ?? null;
    }

    /**
     * Relationship to the suspect being screened.
     */
    public function suspect(): BelongsTo
    {
        return $this->belongsTo(CaseSuspect::class, 'suspect_id');
    }

    /**
     * Relationship to associated medications.
     */
    public function medications(): HasMany
    {
        return $this->hasMany(DrugScreeningMedication::class, 'drug_screening_id');
    }

    /**
     * Helper to check if screened person is on medication.
     */
    public function isOnMedication(): bool
    {
        return (bool) $this->on_medication;
    }

    /**
     * Auto-generate a readable medication summary string (e.g. "Paracetamol 500mg; Amlodipine 10mg").
     */
    public static function buildMedicationSummary(iterable $medications): ?string
    {
        $items = [];
        foreach ($medications as $med) {
            $name = is_array($med) ? ($med['medication_name'] ?? '') : ($med->medication_name ?? '');
            $dosage = is_array($med) ? ($med['dosage'] ?? '') : ($med->dosage ?? '');
            $name = trim((string) $name);
            $dosage = trim((string) $dosage);
            if ($name === '') {
                continue;
            }
            $items[] = $dosage !== '' ? "{$name} {$dosage}" : $name;
        }

        return !empty($items) ? implode('; ', $items) : null;
    }

    /**
     * Determine if this is a civilian screening.
     */
    public function isCivilian(): bool
    {
        return !empty($this->civilian_name);
    }

    /**
     * Determine if this is a suspect screening.
     */
    public function isSuspect(): bool
    {
        return !empty($this->suspect_id);
    }

    /**
     * Name of the screened person (Civilian or Suspect).
     */
    public function getPersonNameAttribute(): string
    {
        if ($this->isCivilian()) {
            return $this->civilian_name;
        }

        return $this->suspect->suspect_name ?? 'Suspect';
    }

    /**
     * Case Reference or Identification for the screened person.
     */
    public function getCaseReferenceAttribute(): string
    {
        if ($this->isCivilian()) {
            return 'Civilian (N/A)';
        }

        $suspect = $this->suspect;
        if (!$suspect) {
            return 'N/A';
        }

        $case = $suspect->case ?? ($suspect->singleCase ?? $suspect->multipleCase);
        return $case ? $case->case_number : ($suspect->case_number ?? 'N/A');
    }

    /**
     * Suspect case status or classification.
     * Fetches from the case_status column of the suspect from the database.
     */
    public function getClassificationAttribute(): string
    {
        if ($this->isCivilian()) {
            return 'Civilian (Non-Custody Intake)';
        }

        if ($this->suspect) {
            if (!empty($this->suspect->case_status)) {
                return $this->suspect->case_status;
            }
            if ($this->suspect->singleCase && !empty($this->suspect->singleCase->case_status)) {
                return $this->suspect->singleCase->case_status;
            }
            if ($this->suspect->multipleCase && !empty($this->suspect->multipleCase->case_status)) {
                return $this->suspect->multipleCase->case_status;
            }
            if (!empty($this->suspect->suspect_disposition)) {
                return $this->suspect->suspect_disposition;
            }
        }

        return 'Under Investigation';
    }

    /**
     * Direct accessor for suspect case_status from database.
     */
    public function getCaseStatusAttribute(): ?string
    {
        if ($this->isCivilian()) {
            return 'Civilian (Non-Custody Intake)';
        }

        return $this->suspect->case_status
            ?? ($this->suspect->singleCase->case_status
            ?? ($this->suspect->multipleCase->case_status
            ?? 'Under Investigation'));
    }

    /**
     * Check if result is positive.
     */
    public function isPositive(): bool
    {
        return strtolower(trim($this->result ?? '')) === 'positive';
    }

    /**
     * Check if result is negative.
     */
    public function isNegative(): bool
    {
        return strtolower(trim($this->result ?? '')) === 'negative';
    }

    /**
     * Check if result is pending.
     */
    public function isPending(): bool
    {
        return strtolower(trim($this->result ?? '')) === 'pending';
    }

    /**
     * List of substances found as an array.
     */
    public function getSubstanceListAttribute(): array
    {
        if (empty($this->substances_found) || $this->isNegative()) {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $this->substances_found))));
    }

    /**
     * Formatted screening date.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->screening_date ? $this->screening_date->format('d M Y') : 'N/A';
    }

    /**
     * Lookup the active drug screening record for a suspect by suspect_id or suspect_name.
     * When case context is provided, searches are strictly scoped to the specified case.
     */
    public static function forSuspect($suspectId = null, $suspectName = null, $caseId = null, $caseNumber = null)
    {
        if (empty($suspectId) && empty($suspectName)) {
            return null;
        }

        $cStr = !empty($caseNumber) ? (string) $caseNumber : null;
        $cId  = !empty($caseId) ? (int) $caseId : null;

        if (!empty($suspectId) && is_numeric($suspectId) && (int) $suspectId > 0) {
            $query = static::with(['suspect.multipleCase', 'medications'])
                ->where('suspect_id', (int) $suspectId);

            if (!empty($cId) || !empty($cStr)) {
                $query->whereHas('suspect', function ($q) use ($cId, $cStr) {
                    $q->where(function ($sq) use ($cId, $cStr) {
                        if (!empty($cId)) {
                            $sq->orWhere('multiple_suspect_case_id', $cId)
                               ->orWhere('single_suspect_case_id', $cId);
                        }
                        if (!empty($cStr)) {
                            $sq->orWhereHas('multipleCase', fn($mq) => $mq->where('case_number', $cStr))
                               ->orWhereHas('singleCase', fn($sqCase) => $sqCase->where('case_number', $cStr));
                        }
                    });
                });
            }

            $record = $query->latest('screening_date')
                ->latest('id')
                ->first();

            if ($record) {
                return $record;
            }
        }

        if (!empty($suspectName)) {
            $norm = strtolower(preg_replace('/\s+/', ' ', trim((string) $suspectName)));
            if ($norm !== '') {
                $query = static::with(['suspect.multipleCase', 'medications'])
                    ->whereHas('suspect', function ($q) use ($norm, $cId, $cStr) {
                        $q->where(function ($nq) use ($norm) {
                            $nq->whereRaw('LOWER(TRIM(suspect_name)) = ?', [$norm])
                               ->orWhereRaw('LOWER(TRIM(suspect_alias)) = ?', [$norm]);
                        });

                        if (!empty($cId) || !empty($cStr)) {
                            $q->where(function ($sq) use ($cId, $cStr) {
                                if (!empty($cId)) {
                                    $sq->orWhere('multiple_suspect_case_id', $cId)
                                       ->orWhere('single_suspect_case_id', $cId);
                                }
                                if (!empty($cStr)) {
                                    $sq->orWhereHas('multipleCase', fn($mq) => $mq->where('case_number', $cStr))
                                       ->orWhereHas('singleCase', fn($sqCase) => $sqCase->where('case_number', $cStr));
                                }
                            });
                        }
                    });

                return $query->latest('screening_date')
                    ->latest('id')
                    ->first();
            }
        }

        return null;
    }

    /**
     * Retrieve all drug screening records for suspects associated with a specific case.
     */
    public static function forCase($caseNumber = null, $caseId = null)
    {
        if (empty($caseNumber) && empty($caseId)) {
            return collect();
        }

        $cStr = !empty($caseNumber) ? (string) $caseNumber : null;
        $cId  = !empty($caseId) ? (int) $caseId : null;

        return static::with(['suspect.multipleCase', 'medications'])
            ->whereHas('suspect', function ($q) use ($cStr, $cId) {
                $q->where(function ($sq) use ($cStr, $cId) {
                    if (!empty($cId)) {
                        $sq->orWhere('multiple_suspect_case_id', $cId);
                    }
                    if (!empty($cStr)) {
                        $sq->orWhereHas('multipleCase', function ($mq) use ($cStr) {
                            $mq->where('case_number', $cStr);
                        });
                    }
                });
            })
            ->orderByDesc('screening_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Filter a collection of screenings for a specific suspect by ID or Name.
     */
    public static function filterForSuspect($screenings, $suspectId = null, $suspectName = null)
    {
        $norm = fn($v) => strtolower(preg_replace('/\s+/', ' ', trim((string) $v)));
        $wantedName = $norm($suspectName);

        return collect($screenings)->filter(function ($ds) use ($suspectId, $wantedName, $norm) {
            if (!empty($suspectId) && !empty($ds->suspect_id)) {
                if ((int) $ds->suspect_id === (int) $suspectId) {
                    return true;
                }
            }
            $sName = !empty($ds->suspect) ? ($ds->suspect->suspect_name ?? null) : null;
            if ($wantedName !== '' && !empty($sName) && $norm($sName) === $wantedName) {
                return true;
            }
            return false;
        })->values();
    }
}

