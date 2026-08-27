<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'test_case_id',
    'requirement_id',
])]
class TestCaseRequirement extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'test_case_requirements';

    /**
     * Indicates if the model should have timestamps.
     */
    public $timestamps = false;
}
