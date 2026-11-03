<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferenceSequence extends Model
{
    protected $table = 'reference_sequences';

    protected $guarded = [];

    protected $casts = [
        'year'           => 'integer',
        'current_number' => 'integer',
    ];
}
