<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuspectExpellingReport extends Model
{
    use HasFactory;

    protected $table = 'suspect_expelling_reports';

    protected $fillable = [
        'case_suspect_id',
        'single_suspect_case_id',
        'multiple_suspect_case_id',
        'generated_by',
        'case_number',
        'suspect_name',
        'passport_number',
        'place_of_arrest',
        'date_of_arrest',
        'arrest_officers',
        'flight_number',
        'airline',
        'destination',
        'substance_expelled',
        'report_url',
        'status',
        'generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    public function suspect(): BelongsTo
    {
        return $this->belongsTo(CaseSuspect::class, 'case_suspect_id');
    }

    public function singleCase(): BelongsTo
    {
        return $this->belongsTo(SingleSuspectCase::class, 'single_suspect_case_id');
    }

    public function multipleCase(): BelongsTo
    {
        return $this->belongsTo(MultipleSuspectCase::class, 'multiple_suspect_case_id');
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}

