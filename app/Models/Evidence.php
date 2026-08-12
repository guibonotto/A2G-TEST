<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'execution_id',
    'file_name',
    'file_url',
    'uploaded_at',
])]
class Evidence extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'evidences';

    /**
     * Indicates if the model should have timestamps.
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }
}