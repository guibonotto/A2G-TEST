<?php

namespace App\Models;

use App\Enums\Permission;
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
    public const string ADMIN = 'admin';

    /**
     * Fixed hierarchy: a role may only manage roles and users strictly below its own level.
     */
    public const array LEVELS = [
        'admin' => 100,
        'qa' => 50,
        'developer' => 30,
        'viewer' => 10,
    ];

    /**
     * Roles that hold every permission whenever their set was never configured.
     */
    public const array MANAGEMENT_SLUGS = ['qa', self::ADMIN];

    public function level(): int
    {
        return self::LEVELS[$this->slug] ?? 0;
    }

    /**
     * The permissions actually enforced for this role.
     *
     * A null set means the role predates permission enforcement and was never
     * configured, which for the management roles means full access; an empty
     * array is an explicit "none" and is honoured as such.
     *
     * @return array<int, string>
     */
    public function effectivePermissions(): array
    {
        if ($this->permissions !== null) {
            return $this->permissions;
        }

        return in_array($this->slug, self::MANAGEMENT_SLUGS, true)
            ? array_column(Permission::cases(), 'value')
            : [];
    }

    public function outranks(self $other): bool
    {
        return $this->level() > $other->level();
    }

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

    /**
     * Exposed so the frontend gates on what is enforced, not on the raw column.
     *
     * @var list<string>
     */
    protected $appends = ['effective_permissions'];

    /**
     * @return array<int, string>
     */
    public function getEffectivePermissionsAttribute(): array
    {
        return $this->effectivePermissions();
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
