<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'test_case_id',
    'assigned_to',
    'assigned_by',
    'assignment_date',
])]
class Assignment extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'assignments';

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assignment_date' => 'datetime',
        ];
    }
}