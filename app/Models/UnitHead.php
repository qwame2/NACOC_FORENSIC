<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitHead extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'fullname',
        'rank_title',
        'service_id',
    ];

    /**
     * Get the unit that owns the head.
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
