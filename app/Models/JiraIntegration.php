<?php

namespace App\Models;

use Database\Factories\JiraIntegrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'project_id',
    'site_url',
    'cloud_id',
    'access_token',
    'refresh_token',
    'expires_at',
    'scopes',
    'connected_by',
    'connected_at',
])]
class JiraIntegration extends Model
{
    /** @use HasFactory<JiraIntegrationFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'jira_integrations';

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'expires_at' => 'datetime',
            'connected_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function connector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Whether Atlassian granted write access, which reporting defects needs.
     * Connections made before that feature only hold read scopes.
     */
    public function canCreateIssues(): bool
    {
        return in_array('write:jira-work', explode(' ', (string) $this->scopes), true);
    }

    public function issueUrl(string $key): string
    {
        return rtrim($this->site_url, '/').'/browse/'.rawurlencode($key);
    }

    /**
     * Only an administrator of the integration's project can disconnect it,
     * the same people who can connect it.
     */
    public function canBeDisconnectedBy(User $user): bool
    {
        return $user->hasRoleIn($this->project, Role::ADMIN);
    }
}
