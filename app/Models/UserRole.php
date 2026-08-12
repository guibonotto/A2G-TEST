<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'role_id',
])]
class UserRole extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'user_roles';

    /**
     * Indicates if the model has an auto-incrementing primary key.
     */
    public $incrementing = false;

    /**
     * Indicates if the model should have timestamps.
     */
    public $timestamps = false;
}