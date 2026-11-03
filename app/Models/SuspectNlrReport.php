<?php

namespace App\Models;

use App\Models\Concerns\EnforcesMutuallyExclusiveCases;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuspectNlrReport extends Model
{
    use EnforcesMutuallyExclusiveCases;

    protected $table = 'suspect_nlr_reports';

    protected $fillable = [
        'case_suspect_id',
        'multiple_suspect_case_id',
        'single_suspect_case_id',
        'uploaded_by',
        'original_filename',
        'stored_filename',
        'file_path',
        'file_type',
        'file_extension',
        'file_size',
        'lab_number',
        'case_number',
        'notes',
        'status',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────────────────────

    /** The suspect this NLR report belongs to */
    public function suspect(): BelongsTo
    {
        return $this->belongsTo(CaseSuspect::class, 'case_suspect_id');
    }

    /** The parent multiple suspect case dossier */
    public function multipleCase(): BelongsTo
    {
        return $this->belongsTo(MultipleSuspectCase::class, 'multiple_suspect_case_id');
    }

    /** The parent single suspect case dossier */
    public function singleCase(): BelongsTo
    {
        return $this->belongsTo(SingleSuspectCase::class, 'single_suspect_case_id');
    }

    /** The parent case dossier */
    public function case()
    {
        if ($this->single_suspect_case_id) {
            return $this->singleCase();
        }
        return $this->multipleCase();
    }

    /** The lab officer who generated/uploaded the file */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /** Human-readable file size */
    public function getFileSizeLabelAttribute(): string
    {
        $bytes = $this->file_size ?? 0;
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)    return number_format($bytes / 1024, 1)    . ' KB';
        return $bytes . ' B';
    }

    /** Public URL to the stored file */
    public function getUrlAttribute(): string
    {
        if (empty($this->file_path)) {
            return '#';
        }
        if (str_starts_with($this->file_path, 'http://') || str_starts_with($this->file_path, 'https://')) {
            return $this->file_path;
        }
        if (str_starts_with($this->file_path, '/') || str_starts_with($this->file_path, 'Lab_Analysis/')) {
            return url($this->file_path);
        }
        return asset('storage/' . $this->file_path);
    }

    /** Returns true if the file is an image */
    public function getIsImageAttribute(): bool
    {
        return in_array($this->file_extension, ['jpg', 'jpeg', 'png', 'webp']);
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'active')
              ->orWhereNull('status')
              ->orWhere('status', '!=', 'deleted');
        });
    }

    // ── Lookup helpers (used by docket & analytical report views) ─────────

    public static function forCase($caseNumber = null, $caseId = null)
    {
        if (empty($caseNumber) && empty($caseId)) {
            return collect();
        }

        $cStr = (string) $caseNumber;
        $id = !empty($caseId) ? (int) $caseId : null;

        return static::with('suspect:id,suspect_name')
            ->where(function ($q) use ($cStr, $id) {
                if (!empty($cStr)) {
                    $q->orWhere('case_number', $cStr);
                }
                if (!empty($id)) {
                    $q->orWhere('multiple_suspect_case_id', $id)
                      ->orWhere('single_suspect_case_id', $id);
                }
            })
            ->active()
            ->orderByDesc('created_at')
            ->get();
    }

    /** Filter a collection of NLR files down to one suspect, by DB id first, then by name. */
    public static function filterForSuspect($files, $suspectId = null, $suspectName = null)
    {
        $norm = fn($v) => strtolower(preg_replace('/\s+/', ' ', trim((string) $v)));
        $wantedName = $norm($suspectName);

        return collect($files)->filter(function ($f) use ($suspectId, $wantedName, $norm) {
            if (!empty($suspectId) && !empty($f->case_suspect_id)) {
                if ((int) $f->case_suspect_id === (int) $suspectId) {
                    return true;
                }
            }
            $fName = !empty($f->suspect) ? ($f->suspect->suspect_name ?? null) : null;
            if ($wantedName !== '' && !empty($fName) && $norm($fName) === $wantedName) {
                return true;
            }
            if ($wantedName !== '' && !empty($fName)) {
                if (str_contains($wantedName, $norm($fName)) || str_contains($norm($fName), $wantedName)) {
                    return true;
                }
            }
            if (!empty($f->single_suspect_case_id)) {
                return true;
            }
            if (!empty($f->multiple_suspect_case_id)) {
                return false;
            }
            if (empty($f->case_suspect_id)) {
                return true;
            }
            return false;
        })->values();
    }
}
