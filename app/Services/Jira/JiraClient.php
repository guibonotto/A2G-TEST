<?php

namespace App\Services\Jira;

use App\Models\JiraIntegration;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class JiraClient
{
    private const AUTHORIZE_URL = 'https://auth.atlassian.com/authorize';

    private const TOKEN_URL = 'https://auth.atlassian.com/oauth/token';

    private const ACCESSIBLE_RESOURCES_URL = 'https://api.atlassian.com/oauth/token/accessible-resources';

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $redirectUri,
    ) {}

    /**
     * Build the Atlassian OAuth 2.0 (3LO) authorization URL.
     */
    public function authorizationUrl(string $state): string
    {
        return self::AUTHORIZE_URL.'?'.http_build_query([
            'audience' => 'api.atlassian.com',
            'client_id' => $this->clientId,
            'scope' => 'read:jira-work read:jira-user offline_access',
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
            'response_type' => 'code',
            'prompt' => 'consent',
        ]);
    }

    /**
     * Exchange an authorization code for an access/refresh token pair.
     *
     * @return array{access_token: string, refresh_token: string, expires_in: int}
     */
    public function exchangeCodeForToken(string $code): array
    {
        return $this->requestToken([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
        ]);
    }

    /**
     * Exchange a refresh token for a new access/refresh token pair.
     *
     * @return array{access_token: string, refresh_token: string, expires_in: int}
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        return $this->requestToken([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);
    }

    /**
     * @param  array<string, string>  $grantParams
     * @return array{access_token: string, refresh_token: string, expires_in: int}
     */
    private function requestToken(array $grantParams): array
    {
        $response = Http::asJson()->post(self::TOKEN_URL, [
            ...$grantParams,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ])->throw();

        return $response->json();
    }

    /**
     * Fetch the Jira Cloud sites accessible to the authenticated user.
     *
     * @return array<int, array{id: string, url: string, name: string}>
     */
    public function accessibleResources(string $accessToken): array
    {
        return Http::withToken($accessToken)
            ->get(self::ACCESSIBLE_RESOURCES_URL)
            ->throw()
            ->json();
    }

    /**
     * Return a valid access token for the integration, refreshing it first if expired.
     */
    public function validAccessToken(JiraIntegration $integration): string
    {
        if (! $integration->isExpired()) {
            return $integration->access_token;
        }

        $tokens = $this->refreshAccessToken($integration->refresh_token);

        $integration->update([
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'] ?? $integration->refresh_token,
            'expires_at' => now()->addSeconds($tokens['expires_in']),
        ]);

        return $integration->access_token;
    }

    /**
     * List the Jira projects visible to the connected site.
     *
     * @return array<int, array{key: string, name: string}>
     */
    public function projects(JiraIntegration $integration): array
    {
        $response = $this->api($integration)->get('project/search')->throw()->json();

        return array_map(
            fn (array $project): array => ['key' => $project['key'], 'name' => $project['name']],
            $response['values'] ?? []
        );
    }

    /**
     * List the distinct issue type names available in a Jira project.
     *
     * @return array<int, string>
     */
    public function issueTypeNamesForProject(JiraIntegration $integration, string $projectKey): array
    {
        $project = $this->api($integration)->get("project/{$projectKey}")->throw()->json();

        $issueTypes = $this->api($integration)->get('issuetype/project', [
            'projectId' => $project['id'],
        ])->throw()->json();

        return array_values(array_unique(array_map(
            fn (array $issueType): string => $issueType['name'],
            $issueTypes
        )));
    }

    /**
     * Run a JQL search against the connected site, returning raw Jira issues.
     * Uses the enhanced /search/jql endpoint, which paginates via an opaque
     * cursor token instead of the retired startAt/total offset model.
     *
     * @return array{issues: array<int, array<string, mixed>>, nextPageToken: ?string}
     */
    public function searchIssues(JiraIntegration $integration, string $jql, ?string $pageToken, int $maxResults = 100): array
    {
        $response = $this->api($integration)->get('search/jql', array_filter([
            'jql' => $jql,
            'nextPageToken' => $pageToken,
            'maxResults' => $maxResults,
            'fields' => 'summary,description,issuetype,priority,status',
        ]))->throw()->json();

        return [
            'issues' => $response['issues'] ?? [],
            'nextPageToken' => $response['nextPageToken'] ?? null,
        ];
    }

    private function api(JiraIntegration $integration): PendingRequest
    {
        if (Str::of($integration->cloud_id)->isEmpty()) {
            throw new RuntimeException('Jira integration is missing a cloud ID.');
        }

        return Http::withToken($this->validAccessToken($integration))
            ->baseUrl("https://api.atlassian.com/ex/jira/{$integration->cloud_id}/rest/api/3");
    }
}
