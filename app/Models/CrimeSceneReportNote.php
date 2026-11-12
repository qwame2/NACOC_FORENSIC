<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrimeSceneReportNote extends Model
{
    use HasFactory;

    protected $table = 'crime_scene_report_notes';

    protected $fillable = [
        'user_id',
        'officer_name',
        'status',
        'recommendation',
        'conclusion',
        'batch_id',
        'report_type',
        'period',
        'from_date',
        'to_date',
        'search',
        'priority',
        'status_filter',
        'total_cases_count',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'total_cases_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

