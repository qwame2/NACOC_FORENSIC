<?php

namespace App\Models;

use App\Models\Concerns\EnforcesMutuallyExclusiveCases;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseHandover extends Model
{
    use HasFactory, EnforcesMutuallyExclusiveCases;

    protected $guarded = [];

    protected $casts = [
        'acknowledged_at' => 'datetime',
    ];

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function singleSuspectCase()
    {
        return $this->belongsTo(SingleSuspectCase::class, 'single_suspect_case_id');
    }

    public function multipleSuspectCase()
    {
        return $this->belongsTo(MultipleSuspectCase::class, 'multiple_suspect_case_id');
    }
}
