<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\Data;

/**
 * @since 6.0.0
 */
readonly class WorkflowStageHistory
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public WorkflowStageData $stage,
        public array $payload,
    ) {}
}
