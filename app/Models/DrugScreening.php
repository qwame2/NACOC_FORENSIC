<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrugScreening extends Model
{
    use HasFactory;

    protected $table = 'drug_screening';

    protected $fillable = [
        'suspect_id',
        'purpose',
        'screening_date',
        'result',
        'substances_found',
        'notes',
    ];

    protected $casts = [
        'screening_date' => 'date',
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
     */
    public static function forSuspect($suspectId = null, $suspectName = null)
    {
        if (empty($suspectId) && empty($suspectName)) {
            return null;
        }

        if (!empty($suspectId)) {
            $record = static::with(['suspect.multipleCase'])
                ->where('suspect_id', (int) $suspectId)
                ->latest('screening_date')
                ->latest('id')
                ->first();

            if ($record) {
                return $record;
            }
        }

        if (!empty($suspectName)) {
            $norm = strtolower(preg_replace('/\s+/', ' ', trim((string) $suspectName)));
            if ($norm !== '') {
                return static::with(['suspect.multipleCase'])
                    ->whereHas('suspect', function ($q) use ($norm) {
                        $q->whereRaw('LOWER(TRIM(suspect_name)) = ?', [$norm])
                          ->orWhereRaw('LOWER(TRIM(suspect_alias)) = ?', [$norm]);
                    })
                    ->latest('screening_date')
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

        return static::with(['suspect.multipleCase'])
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

