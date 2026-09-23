<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\Data;

use CraftCms\Cms\Workflow\Enums\WorkflowStageStatus;

readonly class WorkflowStageResult
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public WorkflowStageStatus $status,
        public string $message,
        public array $payload,
    ) {}
}
