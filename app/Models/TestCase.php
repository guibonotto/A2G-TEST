<?php

namespace App\Models;

use Database\Factories\TestCaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'title',
    'tested_method',
    'description',
    'classification_id',
    'created_by',
    'template_id',
    'status_id',
    'assigned_to',
    'project_id',
    'version',
])]
class TestCase extends Model
{
    /** @use HasFactory<TestCaseFactory> */
    use HasFactory;

    use SoftDeletes;

    protected static function booted(): void
    {
        static::updating(function (self $testCase): void {
            if (! $testCase->isDirty('status_id') || $testCase->status_id === null) {
                return;
            }

            $statusName = TestCaseStatus::query()
                ->whereKey($testCase->status_id)
                ->value('name');

            if ($statusName === TestCaseStatus::DEFAULT_NAME && $testCase->executions()->exists()) {
                throw ValidationException::withMessages([
                    'status_id' => __('A test case with executions cannot return to Draft.'),
                ]);
            }
        });
    }

    /**
     * The table associated with the model.
     */
    protected $table = 'test_cases';

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function specificationSnapshot(): array
    {
        $this->loadMissing(['steps', 'classification', 'template', 'status', 'assignee']);
        $step = $this->steps->first();

        return [
            'title' => $this->title,
            'tested_method' => $this->tested_method,
            'description' => $this->description,
            'input' => $step?->description,
            'expected_output' => $step?->expected_result,
            'classification' => $this->classification?->only(['id', 'name']),
            'template' => $this->template?->only(['id', 'title']),
            'status' => $this->status?->only(['id', 'name']),
            'assigned_to' => $this->assignee?->only(['id', 'name']),
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function classification(): BelongsTo
    {
        return $this->belongsTo(Classification::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TestCaseStatus::class, 'status_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(TestTemplate::class, 'template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(TestStep::class)->orderBy('order');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(Execution::class)->latest('execution_date');
    }

    public function latestExecution(): HasOne
    {
        return $this->hasOne(Execution::class)->latestOfMany('execution_date');
    }

    public function requirements(): BelongsToMany
    {
        return $this->belongsToMany(Requirement::class, 'test_case_requirements');
    }

    /**
     * Test cases a user is responsible for in a project, leaving out the
     * obsolete ones, which nobody should run any more.
     */
    #[Scope]
    protected function assignedTo(Builder $query, User $user, int $projectId): void
    {
        $query->where('project_id', $projectId)
            ->where('assigned_to', $user->id)
            ->whereDoesntHave('status', fn (Builder $query) => $query->where('name', 'Obsolete'));
    }

    /**
     * Test cases that still need someone to run them: never executed, or
     * whose last run did not pass (failed or blocked cases need a new run).
     */
    #[Scope]
    protected function awaitingExecution(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereDoesntHave('executions')
            ->orWhereHas('latestExecution', fn (Builder $query) => $query->where('status', '!=', 'APROVADO'))
        );
    }
}
