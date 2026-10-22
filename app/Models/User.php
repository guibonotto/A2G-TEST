<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Permission;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Roles already looked up in this request, keyed by project id.
     *
     * @var array<int, Role|null>
     */
    private array $rolesByProject = [];

    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_user')->withPivot('role_id')->withTimestamps();
    }

    /**
     * The role the user holds in the given project. Roles belong to the
     * membership, so the same person can be QA in one project and Viewer in
     * another; outside a project (or without a role there) they have none.
     */
    public function roleIn(?Project $project): ?Role
    {
        if ($project === null) {
            return null;
        }

        if (! array_key_exists($project->id, $this->rolesByProject)) {
            $roleId = $project->members()->whereKey($this->id)->value('project_user.role_id');
            $this->rolesByProject[$project->id] = $roleId !== null ? Role::find($roleId) : null;
        }

        return $this->rolesByProject[$project->id];
    }

    /**
     * The role the user holds in the project selected in the session.
     */
    public function currentRole(): ?Role
    {
        return $this->roleIn(Project::current());
    }

    /**
     * Drop the roles looked up so far, after a membership's role changed.
     */
    public function forgetRoles(): void
    {
        $this->rolesByProject = [];
    }

    public function hasRole(string ...$slugs): bool
    {
        return in_array($this->currentRole()?->slug, $slugs, true);
    }

    public function hasRoleIn(?Project $project, string ...$slugs): bool
    {
        return in_array($this->roleIn($project)?->slug, $slugs, true);
    }

    /**
     * Admins always hold every permission: their stored set is never editable
     * (see canEditPermissionsOf), so reading it would let a missing or empty
     * set lock the last account out of the screens that could repair it.
     *
     * The role checked is the one held in the given project, or in the
     * current project when none is given.
     */
    public function hasPermission(Permission $permission, ?Project $project = null): bool
    {
        $role = $project !== null ? $this->roleIn($project) : $this->currentRole();

        if ($role?->slug === Role::ADMIN) {
            return true;
        }

        return in_array($permission->value, $role?->effectivePermissions() ?? [], true);
    }

    public function level(): int
    {
        return $this->currentRole()?->level() ?? 0;
    }

    public function outranks(Role|User $other): bool
    {
        return $this->level() > $other->level();
    }

    public function canManageAccess(): bool
    {
        return $this->hasRole('qa', 'admin');
    }

    /**
     * A role's permissions may only be edited by someone strictly above it,
     * so nobody can touch their own role or the admin role.
     */
    public function canEditPermissionsOf(Role $role): bool
    {
        return $this->canManageAccess() && $this->outranks($role);
    }

    /**
     * Another user's role may only be changed by someone strictly above them;
     * users never change their own role.
     */
    public function canChangeRoleOf(User $target): bool
    {
        return $this->canManageAccess() && $target->isNot($this) && $this->outranks($target);
    }

    /**
     * Admins may hand out any role (including admin); everyone else only roles
     * below their own. Removing the role (null) is always allowed.
     */
    public function canAssignRole(?Role $role): bool
    {
        return $role === null || $this->hasRole(Role::ADMIN) || $this->outranks($role);
    }
}
