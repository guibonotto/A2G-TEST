<?php

namespace App\Models;

use App\Enums\Permission;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    public const string ADMIN = 'admin';

    public const string VIEWER = 'viewer';

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

    /**
     * Roles whose users can be put in charge of a test case.
     */
    public const array ASSIGNABLE_SLUGS = ['admin', 'qa', 'developer'];

    public function level(): int
    {
        return self::LEVELS[$this->slug] ?? 0;
    }

    /**
     * Permission sets already looked up in this request, keyed by project id.
     *
     * @var array<int, array<int, string>|null>
     */
    private array $permissionsByProject = [];

    /**
     * The permissions actually enforced for this role in the given project
     * (the current one by default).
     *
     * A project that changed the role's permissions uses its own set; any
     * other project falls back to the role's default set. In that default, a
     * null set means the role predates permission enforcement and was never
     * configured, which for the management roles means full access; an empty
     * array is an explicit "none" and is honoured as such.
     *
     * @return array<int, string>
     */
    public function effectivePermissions(?Project $project = null): array
    {
        $project ??= Project::current();

        if ($project !== null) {
            if (! array_key_exists($project->id, $this->permissionsByProject)) {
                $this->permissionsByProject[$project->id] = ProjectRolePermission::query()
                    ->where('project_id', $project->id)
                    ->where('role_id', $this->id)
                    ->first()
                    ?->permissions;
            }

            if ($this->permissionsByProject[$project->id] !== null) {
                return $this->permissionsByProject[$project->id];
            }
        }

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
