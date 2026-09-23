<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\Stages;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Component\Concerns\ConfigurableComponent;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\Workflow\Contracts\WorkflowStageInterface;
use CraftCms\Cms\Workflow\Data\WorkflowStageContext;

abstract class WorkflowStage extends Component implements WorkflowStageInterface
{
    use ConfigurableComponent;

    protected bool $showDefaultReviewActions = true;

    public bool $showDefaultActions {
        get => $this->showDefaultReviewActions;
    }

    public function actionComponent(): ?string
    {
        return null;
    }

    public function actionProps(WorkflowStageContext $context, CraftUser $viewer): array
    {
        return [];
    }

    public function summaryComponent(): ?string
    {
        return null;
    }

    public function summaryProps(WorkflowStageContext $context, CraftUser $viewer): array
    {
        return [];
    }
}
