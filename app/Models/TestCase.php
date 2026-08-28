<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title',
    'description',
    'classification_id',
    'created_by',
    'template_id',
    'status_id',
    'assigned_to',
])]
class TestCase extends Model
{
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
        ];
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

    public function requirements(): BelongsToMany
    {
        return $this->belongsToMany(Requirement::class, 'test_case_requirements');
    }
}
