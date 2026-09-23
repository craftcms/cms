<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\Contracts;

use CraftCms\Cms\Component\Contracts\ComponentInterface;
use CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\Validation\Contracts\Validatable;
use CraftCms\Cms\Workflow\Data\WorkflowStageContext;
use CraftCms\Cms\Workflow\Data\WorkflowStageResult;

interface WorkflowStageInterface extends ComponentInterface, ConfigurableComponentInterface, Validatable
{
    /** Whether the default review actions should be rendered alongside the action component. */
    public bool $showDefaultActions { get; }

    /** Evaluates the stage when a workflow reaches it or its state changes. */
    public function evaluate(WorkflowStageContext $context): WorkflowStageResult;

    /** Returns the registered CP component rendered for the current pending stage above the generic review controls, or null for no action UI. */
    public function actionComponent(): ?string;

    /**
     * Returns the props passed to the action component for the current viewer.
     *
     * @return array<string, mixed>
     */
    public function actionProps(WorkflowStageContext $context, CraftUser $viewer): array;

    /** Returns the registered CP component rendered after the stage events in the review timeline, or null for no summary UI. */
    public function summaryComponent(): ?string;

    /**
     * Returns the props passed to the summary component for a stage in any current or previous run.
     *
     * @return array<string, mixed>
     */
    public function summaryProps(WorkflowStageContext $context, CraftUser $viewer): array;
}
