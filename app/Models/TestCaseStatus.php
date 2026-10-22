<?php

namespace App\Models;

use Database\Factories\TestCaseStatusFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'project_id',
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
     * The statuses every new project starts with.
     *
     * @var list<array{name: string, color: string}>
     */
    public const DEFAULTS = [
        ['name' => self::DEFAULT_NAME, 'color' => 'warning'],
        ['name' => 'Ready', 'color' => 'success'],
        ['name' => 'Obsolete', 'color' => 'secondary'],
    ];

    /**
     * Resolve the project's default status, creating it when the project lacks
     * it, so a test case is never left without a status.
     */
    public static function defaultFor(Project $project): self
    {
        return self::firstOrCreate(
            ['project_id' => $project->id, 'name' => self::DEFAULT_NAME],
            ['color' => 'warning'],
        );
    }

    /**
     * Give a project its starting set of statuses.
     */
    public static function createDefaultsFor(Project $project): void
    {
        foreach (self::DEFAULTS as $status) {
            self::firstOrCreate(['project_id' => $project->id, 'name' => $status['name']], $status);
        }
    }

    /**
     * Statuses are per project: each project manages its own set.
     */
    #[Scope]
    protected function forProject(Builder $query, Project|int $project): void
    {
        $query->where('project_id', $project instanceof Project ? $project->id : $project);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function testCases(): HasMany
    {
        return $this->hasMany(TestCase::class, 'status_id');
    }
}
