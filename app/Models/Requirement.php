<?php

namespace App\Models;

use Database\Factories\RequirementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'project_id',
    'code',
    'type',
    'title',
    'description',
    'priority',
    'status',
    'created_by',
])]
class Requirement extends Model
{
    /** @use HasFactory<RequirementFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * The priority a requirement gets when none is given.
     */
    public const string DEFAULT_PRIORITY = 'media';

    /**
     * The status follows the requirement's coverage instead of being typed in:
     * pending while no test case covers it, done once every linked test case
     * passed its latest run, and in progress in between.
     */
    public static function coverageStatus(int $linkedTestCases, int $passedTestCases): string
    {
        return match (true) {
            $linkedTestCases === 0 => 'pendente',
            $passedTestCases >= $linkedTestCases => 'concluido',
            default => 'em_andamento',
        };
    }

    /**
     * The table associated with the model.
     */
    protected $table = 'requirements';

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'jira_synced_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function testCases(): BelongsToMany
    {
        return $this->belongsToMany(TestCase::class, 'test_case_requirements');
    }
}
