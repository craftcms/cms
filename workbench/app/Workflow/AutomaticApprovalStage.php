<?php

declare(strict_types=1);

namespace Workbench\App\Workflow;

use CraftCms\Cms\Workflow\Data\WorkflowStageContext;
use CraftCms\Cms\Workflow\Data\WorkflowStageResult;
use CraftCms\Cms\Workflow\Enums\WorkflowStageStatus;
use CraftCms\Cms\Workflow\Stages\WorkflowStage;

class AutomaticApprovalStage extends WorkflowStage
{
    public static function displayName(): string
    {
        return 'Automatic Approval';
    }

    public function evaluate(WorkflowStageContext $context): WorkflowStageResult
    {
        return new WorkflowStageResult(
            WorkflowStageStatus::Approved,
            'Automatically approved',
            $context->payload,
        );
    }
}
