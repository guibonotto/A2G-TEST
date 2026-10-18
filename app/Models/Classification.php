<?php

namespace App\Models;

use Database\Factories\ClassificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'description',
])]
class Classification extends Model
{
    /** @use HasFactory<ClassificationFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'classifications';

    /**
     * Indicates if the model should have timestamps.
     */
    public $timestamps = false;

    public function testCases(): HasMany
    {
        return $this->hasMany(TestCase::class);
    }
}
