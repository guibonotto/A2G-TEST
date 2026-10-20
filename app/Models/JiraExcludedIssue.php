<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A Jira issue whose requirement was deleted here, so a later import
 * does not silently bring it back.
 */
#[Fillable(['project_id', 'issue_key'])]
class JiraExcludedIssue extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'jira_excluded_issues';
}
