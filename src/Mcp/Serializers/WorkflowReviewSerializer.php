<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Serializers;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\Workflow\Data\WorkflowRunData;
use CraftCms\Cms\Workflow\Data\WorkflowRunStageData;
use CraftCms\Cms\Workflow\Data\WorkflowStageData;
use CraftCms\Cms\Workflow\Data\WorkflowTimelineItemData;
use CraftCms\Cms\Workflow\Models\WorkflowRun;
use CraftCms\Cms\Workflow\UserReview\UserReviewStage;
use CraftCms\Cms\Workflow\Workflows;

/**
 * @since 6.0.0
 */
readonly class WorkflowReviewSerializer
{
    public function __construct(private Workflows $workflows) {}

    /** @return array<string, mixed>|null */
    public function serialize(ElementInterface $draft, CraftUser $viewer): ?array
    {
        $review = $this->workflows->reviewData($draft, $viewer);
        $workflow = $this->workflows->forElement($draft);

        if ($review === null || $workflow === null) {
            return null;
        }

        $runs = WorkflowRun::query()
            ->where('draftId', $draft->draftId)
            ->with('activityRootEvent')
            ->get()
            ->keyBy('id');
        $currentRun = $runs->get($review->runId);
        $stage = $currentRun?->stages()->get($review->currentStage);
        $userReview = $stage?->component() instanceof UserReviewStage;

        return [
            'draft' => ['type' => $draft::class, 'id' => $draft->id, 'uid' => $draft->uid, 'siteId' => $draft->siteId],
            'workflow' => [
                'id' => $workflow->id,
                'uid' => $workflow->uid,
                'name' => $workflow->name,
                'stages' => $workflow->stages->map($this->stage(...))->values()->all(),
            ],
            'status' => $review->status->value,
            'runId' => $review->runId,
            'currentStage' => $review->currentStage,
            'stage' => $stage !== null ? $this->stage($stage) : null,
            'message' => $currentRun?->currentStageResult,
            'actions' => [
                'canSubmit' => $review->canSubmit,
                'canApprove' => $userReview && ($review->actionProps['canReview'] ?? false),
                'canRequestChanges' => $userReview && ($review->actionProps['canReview'] ?? false),
                'canRequestReview' => $userReview && ($review->actionProps['canRequestReviewAgain'] ?? false),
                'canRestart' => $review->canRestart,
                'canApply' => $review->canApply,
            ],
            'runs' => array_map(function (WorkflowRunData $history) use ($runs): array {
                $run = $runs->get($history->id);
                $stages = $run->stages();

                return [
                    'id' => $history->id,
                    'current' => $history->current,
                    'status' => $run->status->value,
                    'submission' => $this->event($history->submission),
                    'stages' => array_map(function (WorkflowRunStageData $historyStage, int $index) use ($stages): array {
                        $stage = $stages->get($index);

                        return [
                            ...$this->stage($stage),
                            'current' => $historyStage->current,
                            'approved' => $historyStage->approved,
                            'message' => $historyStage->message,
                            'summary' => $stage->component() instanceof UserReviewStage ? $historyStage->summaryProps : null,
                            'events' => array_map($this->event(...), $historyStage->events),
                        ];
                    }, $history->stages, array_keys($history->stages)),
                ];
            }, $review->runs),
        ];
    }

    /** @return array{uid: string, name: string, type: string} */
    private function stage(WorkflowStageData $stage): array
    {
        return ['uid' => $stage->uid, 'name' => $stage->name, 'type' => $stage->type];
    }

    /** @return array<string, mixed> */
    private function event(WorkflowTimelineItemData $event): array
    {
        return [
            'id' => $event->id,
            'type' => $event->type,
            'actor' => ['name' => $event->actor['label'], 'deleted' => $event->actor['deleted']],
            'decision' => $event->decision,
            'note' => $event->noteHtml !== null ? trim(html_entity_decode(strip_tags($event->noteHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8')) : null,
            'occurredAt' => $event->occurredAt,
        ];
    }
}
