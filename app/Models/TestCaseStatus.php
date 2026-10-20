<?php

namespace App\Models;

use Database\Factories\TestCaseStatusFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'description',
    'color',
    'category',
])]
class TestCaseStatus extends Model
{
    /** @use HasFactory<TestCaseStatusFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'test_case_statuses';

    /**
     * Indicates if the model should have timestamps.
     */
    public $timestamps = false;

    /**
     * The status a test case receives when none is chosen on creation.
     */
    public const DEFAULT_NAME = 'Draft';

    /**
     * Resolve the default status, creating it when the installation lacks it,
     * so a test case is never left without a status.
     */
    public static function default(): self
    {
        return self::firstOrCreate(['name' => self::DEFAULT_NAME], ['color' => 'warning']);
    }

    public function testCases(): HasMany
    {
        return $this->hasMany(TestCase::class, 'status_id');
    }
}
