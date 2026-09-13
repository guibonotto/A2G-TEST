<?php

namespace App\Models;

use Database\Factories\TestTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title',
    'description',
    'classification_id',
    'created_by',
])]
class TestTemplate extends Model
{
    /** @use HasFactory<TestTemplateFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'test_templates';

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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Default classification applied to test cases created from this template.
     */
    public function classification(): BelongsTo
    {
        return $this->belongsTo(Classification::class);
    }

    /**
     * Steps copied into a test case when it is created from this template.
     */
    public function steps(): HasMany
    {
        return $this->hasMany(TestTemplateStep::class)->orderBy('order');
    }

    /**
     * Test cases that were created from this template (kept as a trace only).
     */
    public function testCases(): HasMany
    {
        return $this->hasMany(TestCase::class, 'template_id');
    }
}
