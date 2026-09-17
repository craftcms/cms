<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow;

use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Workflow\Contracts\WorkflowStageInterface;
use CraftCms\Cms\Workflow\UserReview\UserReviewStage;
use Illuminate\Container\Attributes\Singleton;

/**
 * The workflow stage type registry.
 *
 * @extends TypeRegistry<WorkflowStageInterface>
 */
#[Singleton]
class WorkflowStageTypes extends TypeRegistry
{
    protected const string CONTRACT = WorkflowStageInterface::class;

    protected const array DEFAULT_TYPES = [
        UserReviewStage::class,
    ];
}
