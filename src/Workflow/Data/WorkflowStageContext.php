<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\Data;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Workflow\Models\WorkflowRun;

readonly class WorkflowStageContext
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public ElementInterface $draft,
        public WorkflowRun $run,
        public WorkflowStageData $stage,
        public array $payload,
    ) {}
}
