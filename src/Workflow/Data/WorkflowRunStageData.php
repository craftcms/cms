<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\Data;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\Workflow\Enums\WorkflowStageStatus;
use CraftCms\Cms\Workflow\Enums\WorkflowStatus;
use CraftCms\Cms\Workflow\Models\WorkflowRun;
use Illuminate\Support\Collection;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
readonly class WorkflowRunStageData
{
    /** @param list<WorkflowTimelineItemData> $events */
    public function __construct(
        public string $name,
        public bool $current,
        public bool $approved,
        /** @var 'enabled'|'pending'|'expired'|'disabled' The `craft-status` state its dot shows. */
        public string $status,
        public string $statusLabel,
        public ?string $message,
        public ?string $summaryComponent,
        /** @var array<string, mixed> */
        public array $summaryProps,
        public array $events,
    ) {}

    /** @param Collection<int, WorkflowTimelineItemData> $runEvents */
    public static function from(WorkflowStageData $stage, int $stageIndex, WorkflowRun $run, ElementInterface $draft, CraftUser $viewer, Collection $runEvents): self
    {
        $component = $stage->component();
        $context = new WorkflowStageContext($draft, $run, $stage, $run->payload[$stage->uid] ?? []);
        $isCurrent = $stageIndex === $run->currentStage;
        $summaryComponent = $component->summaryComponent();
        $isApproved = $stageIndex < $run->currentStage
            || ($isCurrent && in_array($run->status, [WorkflowStatus::Approved, WorkflowStatus::Published], true));
        $events = $runEvents
            ->filter(fn (WorkflowTimelineItemData $event): bool => $event->belongsToStage($stageIndex + 1))
            ->values();
        $lastReset = $events->search(fn (WorkflowTimelineItemData $event): bool => $event->decision === 'reset');
        $currentEvents = $lastReset === false ? $events : $events->slice($lastReset + 1);
        $failed = ($isCurrent && $run->status === WorkflowStatus::Failed)
            || $currentEvents->contains(fn (WorkflowTimelineItemData $event): bool => in_array($event->decision, [WorkflowStageStatus::Failed->value, 'rejected'], true));

        return new self(
            name: t($stage->name),
            current: $run->isPending() && $isCurrent,
            approved: $isApproved,
            status: match (true) {
                $failed => 'expired',
                $isApproved => 'enabled',
                $run->isPending() && $isCurrent => 'pending',
                default => 'disabled',
            },
            statusLabel: match (true) {
                $failed => WorkflowStatus::Failed->label(),
                $isApproved => WorkflowStatus::Approved->label(),
                $isCurrent => $run->status->label(),
                default => t('Not started'),
            },
            message: $isCurrent && $summaryComponent === null ? $run->currentStageResult : null,
            summaryComponent: $summaryComponent,
            summaryProps: $component->summaryProps($context, $viewer),
            events: $events->all(),
        );
    }
}
