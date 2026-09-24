<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\Data;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\Workflow\Models\WorkflowRun;
use Illuminate\Support\Collection;

readonly class WorkflowRunData
{
    /** @param list<WorkflowRunStageData> $stages */
    public function __construct(
        public int $id,
        public bool $current,
        public string $statusLabel,
        public string $statusIndicator,
        public WorkflowTimelineItemData $submission,
        public array $stages,
    ) {}

    /** @param Collection<int, WorkflowTimelineItemData> $events */
    public static function from(WorkflowRun $run, ?WorkflowRun $currentRun, ElementInterface $draft, CraftUser $viewer, Collection $events): self
    {
        $runEvents = $events->filter(fn (WorkflowTimelineItemData $event): bool => $event->belongsToRun($run->id));

        return new self(
            id: $run->id,
            current: $run->id === $currentRun?->id,
            statusLabel: $run->status->label(),
            statusIndicator: $run->status->indicator(),
            submission: $runEvents->sole(fn (WorkflowTimelineItemData $event): bool => $event->type === 'submission'),
            stages: $run->stages()
                ->map(fn (WorkflowStageData $stage, int $stageIndex): WorkflowRunStageData => WorkflowRunStageData::from($stage, $stageIndex, $run, $draft, $viewer, $runEvents))
                ->values()
                ->all(),
        );
    }
}
