<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\Data;

readonly class WorkflowDraftReviewData
{
    public function __construct(
        public string $name,
        public string $requester,
        public string $stage,
        public string $statusLabel,
        public string $statusIndicator,
        public string $url,
    ) {}
}
