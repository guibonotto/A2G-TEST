<?php

namespace App\Services\Jira;

class JiraIssueMapper
{
    /**
     * Map a raw Jira issue payload to Requirement attributes.
     *
     * @param  array<string, mixed>  $issue
     * @return array{code: string, type: string, title: string, description: ?string, priority: ?string, status: ?string}
     */
    public static function toRequirementAttributes(array $issue): array
    {
        $fields = $issue['fields'] ?? [];

        return [
            'code' => $issue['key'],
            'type' => 'funcional',
            'title' => $fields['summary'] ?? $issue['key'],
            'description' => self::adfToText($fields['description'] ?? null),
            'priority' => self::mapPriority($fields['priority']['name'] ?? null),
            'status' => self::mapStatus($fields['status']['statusCategory']['key'] ?? null),
        ];
    }

    /**
     * Flatten Atlassian Document Format content into plain text.
     *
     * @param  array<string, mixed>|null  $node
     */
    private static function adfToText(?array $node): ?string
    {
        if ($node === null) {
            return null;
        }

        $text = self::extractText($node);

        return $text === '' ? null : $text;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private static function extractText(array $node): string
    {
        $parts = [];

        if (isset($node['text']) && is_string($node['text'])) {
            $parts[] = $node['text'];
        }

        foreach ($node['content'] ?? [] as $child) {
            if (is_array($child)) {
                $parts[] = self::extractText($child);
            }
        }

        return trim(implode(' ', array_filter($parts)));
    }

    private static function mapPriority(?string $jiraPriority): ?string
    {
        return match ($jiraPriority) {
            'Highest', 'High' => 'alta',
            'Medium' => 'media',
            'Low', 'Lowest' => 'baixa',
            default => null,
        };
    }

    private static function mapStatus(?string $statusCategoryKey): ?string
    {
        return match ($statusCategoryKey) {
            'new' => 'pendente',
            'indeterminate' => 'em_andamento',
            'done' => 'concluido',
            default => null,
        };
    }
}
