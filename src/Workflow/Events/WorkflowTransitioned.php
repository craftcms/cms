<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\Workflow\Enums\WorkflowTransition;
use CraftCms\Cms\Workflow\Models\WorkflowRun;
use Illuminate\Foundation\Events\Dispatchable;

class WorkflowTransitioned
{
    use Dispatchable;

    public function __construct(
        public readonly WorkflowTransition $transition,
        public readonly ElementInterface $draft,
        public readonly ?CraftUser $actor,
        public readonly WorkflowRun $run,
        public readonly ?string $note = null,
    ) {}
}
