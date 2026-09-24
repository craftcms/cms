<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\Workflow\Data\WorkflowStageData;
use CraftCms\Cms\Workflow\Models\WorkflowRun;
use Illuminate\Foundation\Events\Dispatchable;

class WorkflowCommented
{
    use Dispatchable;

    public function __construct(
        public readonly ElementInterface $draft,
        public readonly CraftUser $actor,
        public readonly WorkflowRun $run,
        public readonly WorkflowStageData $stage,
        public readonly string $note,
    ) {}
}
