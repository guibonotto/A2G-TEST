<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'description',
])]
class Classification extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'classifications';

    /**
     * Indicates if the model should have timestamps.
     */
    public $timestamps = false;
}