<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuspectGsaReport extends Model
{
    protected $table = 'suspect_gsa_reports';

    protected $fillable = [
        'case_suspect_id',
        'multiple_suspect_case_id',
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

    /** The suspect this GSA report belongs to */
    public function suspect(): BelongsTo
    {
        return $this->belongsTo(CaseSuspect::class, 'case_suspect_id');
    }

    /** The parent case dossier */
    public function case(): BelongsTo
    {
        return $this->belongsTo(MultipleSuspectCase::class, 'multiple_suspect_case_id');
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
        return $query->where('status', 'active');
    }
}
