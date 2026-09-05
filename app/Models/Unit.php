<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'category',
        'head_name',
        'head_title',
    ];

    /**
     * Get the head associated with the unit.
     */
    public function unitHead()
    {
        return $this->hasOne(UnitHead::class);
    }
}
