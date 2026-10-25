<?php

namespace App\Services\Jira;

use App\Models\Execution;
use App\Models\JiraIntegration;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Keeps the status of reported defects in line with Jira. Both the test case
 * page and the dashboard read it, and both must keep working when Jira is
 * unreachable, so a failure leaves the last known status in place.
 */
class JiraDefectStatusSync
{
    public function __construct(private readonly JiraClient $jira) {}

    /**
     * Ask Jira, in a single request, where the defects of these executions
     * stand and store the answer on each execution.
     *
     * @param  Collection<int, Execution>  $executions
     */
    public function refresh(Collection $executions, ?JiraIntegration $integration): void
    {
        $executionsWithDefect = $executions->whereNotNull('jira_defect_key');

        if ($integration === null || $executionsWithDefect->isEmpty()) {
            return;
        }

        try {
            $statuses = $this->jira->issueStatuses(
                $integration,
                $executionsWithDefect->pluck('jira_defect_key')->unique()->values()->all(),
            );
        } catch (Throwable) {
            return;
        }

        foreach ($executionsWithDefect as $execution) {
            $status = $statuses[$execution->jira_defect_key] ?? null;

            if ($status !== null) {
                $execution->update([
                    'jira_defect_status' => $status['name'],
                    'jira_defect_resolved' => $status['resolved'],
                ]);
            }
        }
    }
}
