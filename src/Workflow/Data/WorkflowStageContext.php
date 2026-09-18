<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\Data;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Workflow\Models\WorkflowRun;
use Illuminate\Support\Collection;

readonly class WorkflowStageContext
{
    /** @var Collection<int, WorkflowStageHistory> */
    public Collection $previousStages;

    /** @param array<string, mixed> $payload */
    public function __construct(
        public ElementInterface $draft,
        public WorkflowRun $run,
        public WorkflowStageData $stage,
        public array $payload,
    ) {
        $stageIndex = $run->stages()->search(fn (WorkflowStageData $stage): bool => $stage->uid === $this->stage->uid);

        $this->previousStages = $stageIndex === false
            ? collect()
            : $run->stages()
                ->take($stageIndex)
                ->map(fn (WorkflowStageData $stage): WorkflowStageHistory => new WorkflowStageHistory(
                    $stage,
                    $run->payload[$stage->uid] ?? [],
                ));
    }
}
