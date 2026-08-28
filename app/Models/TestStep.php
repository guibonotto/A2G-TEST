<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'test_case_id',
    'order',
    'description',
    'expected_result',
])]
class TestStep extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'test_steps';

    /**
     * Indicates if the model should have timestamps.
     */
    public $timestamps = false;

    public function testCase(): BelongsTo
    {
        return $this->belongsTo(TestCase::class);
    }
}
