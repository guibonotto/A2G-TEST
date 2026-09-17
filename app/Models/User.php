<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Permission;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;

#[Fillable(['name', 'email', 'password', 'role_id'])]
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

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_user')->withTimestamps();
    }

    public function hasRole(string ...$slugs): bool
    {
        return in_array($this->role?->slug, $slugs, true);
    }

    public function hasPermission(Permission $permission): bool
    {
        return in_array($permission->value, $this->role?->permissions ?? [], true);
    }

    public function level(): int
    {
        return $this->role?->level() ?? 0;
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
