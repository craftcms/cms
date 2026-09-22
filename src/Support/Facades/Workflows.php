<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \CraftCms\Cms\Workflow\Models\Workflow|null forElement(\CraftCms\Cms\Element\Contracts\ElementInterface $element)
 * @method static \CraftCms\Cms\Workflow\Models\WorkflowRun|null latestRun(\CraftCms\Cms\Element\Contracts\ElementInterface $draft)
 * @method static \CraftCms\Cms\Workflow\Models\WorkflowRun submitForReview(\CraftCms\Cms\Element\Contracts\ElementInterface $draft, string|null $note = null)
 * @method static \CraftCms\Cms\Workflow\Models\WorkflowRun restartWorkflow(\CraftCms\Cms\Element\Contracts\ElementInterface $draft, int $runId)
 * @method static \CraftCms\Cms\Workflow\Models\WorkflowRun|null reportStageResult(int $runId, string $stageUid, \CraftCms\Cms\Workflow\Data\WorkflowStageResult|\Closure $result, \CraftCms\Cms\User\Contracts\CraftUser|null $actor = null, \CraftCms\Cms\Workflow\Enums\WorkflowTransition|null $activityTransition = null, string|null $activityNote = null)
 * @method static \CraftCms\Cms\Workflow\Models\WorkflowRun addComment(\CraftCms\Cms\Element\Contracts\ElementInterface $draft, int $runId, int|string $stage, string $note)
 * @method static \CraftCms\Cms\Workflow\Models\WorkflowRun overrideApproval(\CraftCms\Cms\Element\Contracts\ElementInterface $draft, int $runId, string|null $reason = null)
 * @method static \CraftCms\Cms\Workflow\Data\WorkflowReviewData|null reviewData(\CraftCms\Cms\Element\Contracts\ElementInterface $draft, \CraftCms\Cms\User\Contracts\CraftUser $viewer)
 * @method static \CraftCms\Cms\Element\Contracts\ElementInterface applyDraft(\CraftCms\Cms\Element\Contracts\ElementInterface $draft, \CraftCms\Cms\User\Contracts\CraftUser|null $actor, int|null $expectedRunId, int|null $expectedStage, \Closure $apply)
 * @method static void contentChanged(\CraftCms\Cms\Element\Contracts\ElementInterface $draft)
 * @method static void contentChangedByDraftIds(array $draftIds)
 * @method static void invalidateRuns(\CraftCms\Cms\Workflow\Models\Workflow $workflow, string $reason)
 * @method static void invalidateSectionRuns(int $sectionId)
 * @method static mixed withContentChangeLock(array $draftIds, \Closure $callback)
 * @method static void lockDraftIds(array $draftIds)
 * @method static mixed withApplicationLock(\CraftCms\Cms\Element\Contracts\ElementInterface $draft, int|null $expectedRunId, int|null $expectedStage, \Closure $callback)
 *
 * @see \CraftCms\Cms\Workflow\Workflows
 */
class Workflows extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Workflow\Workflows::class;
    }
}
