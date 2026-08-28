<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'test_case_id',
    'executed_by',
    'status',
    'comment',
    'batch_id',
    'execution_date',
])]
class Execution extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'executions';

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'execution_date' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function testCase(): BelongsTo
    {
        return $this->belongsTo(TestCase::class);
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }
}
