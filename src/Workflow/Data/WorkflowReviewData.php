<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\Data;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\Workflow\Enums\WorkflowStatus;
use CraftCms\Cms\Workflow\Models\WorkflowRun;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

use function CraftCms\Cms\t;

/** @implements Arrayable<string, mixed> */
readonly class WorkflowReviewData implements Arrayable
{
    /**
     * @param  list<WorkflowRunData>  $runs
     */
    public function __construct(
        public WorkflowStatus $status,
        public string $statusLabel,
        public string $statusIndicator,
        public string $submitLabel,
        public ?string $applyDisabledReason,
        public ?int $runId,
        public ?int $currentStage,
        public ?string $stageUid,
        public ?string $actionComponent,
        public bool $showDefaultActions,
        /** @var array<string, mixed> */
        public array $actionProps,
        public array $runs,
        public bool $canSubmit,
        public bool $canComment,
        public bool $canOverride,
        public bool $canApply,
    ) {}

    /**
     * @param  Collection<int, WorkflowRun>  $runs
     * @param  Collection<int, WorkflowTimelineItemData>  $timelineItems
     */
    public static function from(
        ElementInterface $draft,
        CraftUser $viewer,
        ?WorkflowRun $run,
        Collection $runs,
        Collection $timelineItems,
    ): self {
        $status = $run->status ?? WorkflowStatus::NotSubmitted;
        $stage = $run?->stages()->get($run->currentStage);
        $context = $run !== null && $stage !== null ? new WorkflowStageContext(
            $draft,
            $run,
            $stage,
            $run->payload[$stage->uid] ?? [],
        ) : null;
        $component = $run?->isPending() && $context !== null ? $stage->component() : null;
        $canSubmit = $draft->enabled && $draft->getEnabledForSite()
            && $draft->getIsDraft() && ! $draft->isProvisionalDraft && (bool) $draft->draftId
            && $draft->markDraftAsSaved
            && ! in_array($run?->status, [WorkflowStatus::Pending, WorkflowStatus::Approved], true)
            && Gate::forUser($viewer)->allows('save', $draft);
        $canComment = $stage !== null
            && $run?->status !== WorkflowStatus::Published
            && Gate::forUser($viewer)->allows('view', $draft);
        $canOverride = $viewer->isAdmin()
            && in_array($run?->status, [WorkflowStatus::Pending, WorkflowStatus::Failed], true);
        $canApply = $run?->status === WorkflowStatus::Approved
            && Gate::forUser($viewer)->allows('save', $draft)
            && Gate::forUser($viewer)->allows('saveCanonical', $draft);

        return new self(
            status: $status,
            statusLabel: $status->label(),
            statusIndicator: $status->indicator(),
            submitLabel: $status === WorkflowStatus::Failed ? t('Resubmit for review') : t('Submit for review'),
            applyDisabledReason: $canApply ? null : t('This draft must be approved before it can be applied.'),
            runId: $run?->id,
            currentStage: $run?->currentStage,
            stageUid: $stage?->uid,
            actionComponent: $component?->actionComponent(),
            showDefaultActions: $component->showDefaultActions ?? true,
            actionProps: $component?->actionProps($context, $viewer) ?? [],
            runs: $runs->map(fn (WorkflowRun $historyRun): WorkflowRunData => WorkflowRunData::from($historyRun, $run, $draft, $viewer, $timelineItems))->values()->all(),
            canSubmit: $canSubmit,
            canComment: $canComment,
            canOverride: $canOverride,
            canApply: $canApply,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            ...get_object_vars($this),
            'status' => $this->status->value,
        ];
    }
}
