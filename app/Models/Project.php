<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[Fillable(['uuid', 'name', 'owner_id'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (self $project): void {
            $project->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user')->withPivot('role_id')->withTimestamps();
    }

    /**
     * Add the user to the project with the given role (by slug), or update
     * the role they hold when they already belong to it. A role missing from an
     * unseeded database is created with no stored permissions, which the
     * management roles read as full access and every other role as none.
     */
    public function addMember(User $user, ?string $roleSlug): void
    {
        $roleId = $roleSlug !== null
            ? Role::firstOrCreate(['slug' => $roleSlug], ['name' => Str::headline($roleSlug)])->id
            : null;

        $this->members()->syncWithoutDetaching([$user->id => ['role_id' => $roleId]]);
        $user->forgetRoles();
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(ProjectInvitation::class);
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(Requirement::class);
    }

    public function jiraIntegration(): HasOne
    {
        return $this->hasOne(JiraIntegration::class);
    }

    public function testCases(): HasMany
    {
        return $this->hasMany(TestCase::class);
    }

    /**
     * Resolve the active project for the authenticated user from the session,
     * scoped to projects they actually belong to.
     */
    public static function current(): ?self
    {
        $id = session('current_project_id');

        if (! $id || ! auth()->check()) {
            return null;
        }

        return static::query()
            ->whereKey($id)
            ->whereHas('members', fn ($query) => $query->whereKey(auth()->id()))
            ->first();
    }
}
