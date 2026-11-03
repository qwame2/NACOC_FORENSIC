<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuspectGsaReport extends Model
{
    protected $table = 'suspect_gsa_reports';

    protected $fillable = [
        'case_suspect_id',
        'single_suspect_case_id',
        'multiple_suspect_case_id',
        'uploaded_by',
        'original_filename',
        'stored_filename',
        'file_path',
        'file_type',
        'file_extension',
        'file_size',
        'lab_number',
        'gsa_lab_number',
        'gsa_case_reference',
        'gsa_status',
        'exhibit_type',
        'case_number',
        'notes',
        'status',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────────────────────

    /** The suspect this GSA report belongs to */
    public function suspect(): BelongsTo
    {
        return $this->belongsTo(CaseSuspect::class, 'case_suspect_id');
    }

    /** The parent single case dossier (if single suspect case) */
    public function singleCase(): BelongsTo
    {
        return $this->belongsTo(SingleSuspectCase::class, 'single_suspect_case_id');
    }

    /** The parent multiple case dossier (if multiple suspect case) */
    public function multipleCase(): BelongsTo
    {
        return $this->belongsTo(MultipleSuspectCase::class, 'multiple_suspect_case_id');
    }

    /** The parent case dossier */
    public function case()
    {
        return $this->single_suspect_case_id ? $this->singleCase() : $this->multipleCase();
    }

    /** The lab officer who uploaded the file */
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

    /** Filter a collection of GSA files down to one suspect, by DB id first, then by name. */
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
