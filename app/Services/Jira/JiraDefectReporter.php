<?php

namespace App\Services\Jira;

use App\Models\Execution;
use App\Models\JiraIntegration;
use App\Models\Requirement;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Turns a failed execution into a Bug in Jira, so the developer gets
 * everything needed to reproduce it without opening A2G TEST.
 */
class JiraDefectReporter
{
    public function __construct(private readonly JiraClient $jira) {}

    /**
     * Create the Bug, upload the evidences and remember its key on the
     * execution. Returns how many evidences could not be uploaded: the issue
     * already exists at that point, so a failed upload must not undo it.
     */
    public function report(
        JiraIntegration $integration,
        Execution $execution,
        User $reporter,
        string $jiraProjectKey,
        string $summary,
        bool $attachEvidences,
    ): int {
        $execution->loadMissing(['testCase.requirements', 'evidences', 'executor']);

        $key = $this->jira->createIssue($integration, [
            'project' => ['key' => $jiraProjectKey],
            'issuetype' => ['name' => 'Bug'],
            'summary' => $summary,
            'description' => $this->description($integration, $execution),
        ]);

        $execution->update([
            'jira_defect_key' => $key,
            'jira_defect_status' => null,
            'jira_defect_resolved' => false,
            'jira_defect_reported_by' => $reporter->id,
            'jira_defect_reported_at' => now(),
        ]);

        if (! $attachEvidences) {
            return 0;
        }

        $failedUploads = 0;

        foreach ($execution->evidences as $evidence) {
            try {
                $this->jira->addAttachment(
                    $integration,
                    $key,
                    Storage::disk('local')->get($evidence->file_path),
                    $evidence->file_name,
                );
            } catch (Throwable) {
                $failedUploads++;
            }
        }

        return $failedUploads;
    }

    /**
     * Bug description in Atlassian Document Format, the only format the v3
     * API accepts. It uses the specification the run was made against (the
     * snapshot), not the current one, since the case may have changed since.
     *
     * @return array<string, mixed>
     */
    private function description(JiraIntegration $integration, Execution $execution): array
    {
        $testCase = $execution->testCase;
        $snapshot = $execution->test_case_snapshot ?? $testCase->specificationSnapshot();

        $content = [
            $this->paragraph([
                $this->text('Reported from A2G TEST. Test case: '),
                $this->text($snapshot['title'] ?? $testCase->title, ['strong']),
                $this->text(" (version {$execution->test_case_version})."),
            ]),
            $this->heading('Method/function tested'),
            $this->codeBlock($snapshot['tested_method'] ?? null),
            $this->heading('Input'),
            $this->codeBlock($snapshot['input'] ?? null),
            $this->heading('Expected output'),
            $this->codeBlock($snapshot['expected_output'] ?? null),
            $this->heading('Obtained output'),
            $this->codeBlock($execution->obtained_output),
            $this->heading('Execution'),
            $this->paragraph([
                $this->text(sprintf(
                    'Run by %s on %s.',
                    $execution->executor?->name ?? 'a deleted user',
                    $execution->execution_date->format('Y-m-d H:i'),
                )),
            ]),
        ];

        if (filled($execution->comment)) {
            $content[] = $this->paragraph([$this->text($execution->comment)]);
        }

        $requirements = $testCase->requirements;

        if ($requirements->isNotEmpty()) {
            $content[] = $this->heading('Requirements covered');
            $content[] = [
                'type' => 'bulletList',
                'content' => $requirements->map(fn (Requirement $requirement): array => [
                    'type' => 'listItem',
                    'content' => [$this->paragraph($this->requirementLine($integration, $requirement))],
                ])->values()->all(),
            ];
        }

        return ['type' => 'doc', 'version' => 1, 'content' => $content];
    }

    /**
     * Requirements imported from Jira link back to their issue; the ones
     * created by hand only exist in A2G TEST, so they are plain text.
     *
     * @return array<int, array<string, mixed>>
     */
    private function requirementLine(JiraIntegration $integration, Requirement $requirement): array
    {
        $code = $requirement->jira_synced_at !== null
            ? $this->text($requirement->code, [['type' => 'link', 'attrs' => ['href' => $integration->issueUrl($requirement->code)]]])
            : $this->text($requirement->code);

        return [$code, $this->text(' - '.$requirement->title)];
    }

    /**
     * @return array<string, mixed>
     */
    private function heading(string $text): array
    {
        return ['type' => 'heading', 'attrs' => ['level' => 3], 'content' => [$this->text($text)]];
    }

    /**
     * @param  array<int, array<string, mixed>>  $content
     * @return array<string, mixed>
     */
    private function paragraph(array $content): array
    {
        return ['type' => 'paragraph', 'content' => $content];
    }

    /**
     * @return array<string, mixed>
     */
    private function codeBlock(?string $text): array
    {
        return ['type' => 'codeBlock', 'content' => [$this->text(filled($text) ? $text : '—')]];
    }

    /**
     * @param  array<int, string|array<string, mixed>>  $marks
     * @return array<string, mixed>
     */
    private function text(string $text, array $marks = []): array
    {
        $node = ['type' => 'text', 'text' => $text];

        if ($marks !== []) {
            $node['marks'] = array_map(fn (string|array $mark): array => is_string($mark) ? ['type' => $mark] : $mark, $marks);
        }

        return $node;
    }
}
