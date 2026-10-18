<?php

namespace App\Models;

use Database\Factories\RequirementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
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
        ];
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
