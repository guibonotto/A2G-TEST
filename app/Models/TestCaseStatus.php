<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'color',
])]
class TestCaseStatus extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'test_case_statuses';

    /**
     * Indicates if the model should have timestamps.
     */
    public $timestamps = false;

    public function testCases(): HasMany
    {
        return $this->hasMany(TestCase::class, 'status_id');
    }
}
