<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvidenceReportNote extends Model
{
    use HasFactory;

    protected $table = 'evidence_report_notes';

    protected $fillable = [
        'user_id',
        'officer_name',
        'table_key',
        'table_title',
        'note',
        'batch_id',
        'report_type',
        'period',
        'from_date',
        'to_date',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

