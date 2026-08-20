<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'name',
    'slug',
    'permissions',
])]
class Role extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'roles';

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $role): void {
            $role->uuid ??= (string) Str::uuid();
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
