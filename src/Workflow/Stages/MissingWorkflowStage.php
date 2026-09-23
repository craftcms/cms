<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\Stages;

use CraftCms\Cms\Component\Concerns\MissingComponentTrait;
use CraftCms\Cms\Component\Contracts\MissingComponentInterface;
use CraftCms\Cms\Workflow\Data\WorkflowStageContext;
use CraftCms\Cms\Workflow\Data\WorkflowStageResult;
use CraftCms\Cms\Workflow\Enums\WorkflowStageStatus;

use function CraftCms\Cms\t;

class MissingWorkflowStage extends WorkflowStage implements MissingComponentInterface
{
    use MissingComponentTrait;

    public function evaluate(WorkflowStageContext $context): WorkflowStageResult
    {
        return new WorkflowStageResult(
            WorkflowStageStatus::Pending,
            t('This stage cannot run because its type is unavailable.'),
            $context->payload,
        );
    }

    /** @return array<string, mixed> */
    public function getSettings(): array
    {
        return $this->settings ?? [];
    }
}
