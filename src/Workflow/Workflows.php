<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow;

use Closure;
use CraftCms\Cms\Activity\ActivityTimelinePresenter;
use CraftCms\Cms\Activity\Data\ActivityActor;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Models\Draft;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Facades\Activities;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\Workflow\Activity\WorkflowActivityEvent;
use CraftCms\Cms\Workflow\Contracts\WorkflowableInterface;
use CraftCms\Cms\Workflow\Data\WorkflowReviewData;
use CraftCms\Cms\Workflow\Data\WorkflowStageContext;
use CraftCms\Cms\Workflow\Data\WorkflowStageData;
use CraftCms\Cms\Workflow\Data\WorkflowStageResult;
use CraftCms\Cms\Workflow\Data\WorkflowTimelineItemData;
use CraftCms\Cms\Workflow\Enums\WorkflowActivityType;
use CraftCms\Cms\Workflow\Enums\WorkflowStageStatus;
use CraftCms\Cms\Workflow\Enums\WorkflowStatus;
use CraftCms\Cms\Workflow\Enums\WorkflowTransition;
use CraftCms\Cms\Workflow\Events\WorkflowCommented;
use CraftCms\Cms\Workflow\Events\WorkflowTransitioned;
use CraftCms\Cms\Workflow\Events\WorkflowTransitioning;
use CraftCms\Cms\Workflow\Exceptions\WorkflowException;
use CraftCms\Cms\Workflow\Models\Workflow;
use CraftCms\Cms\Workflow\Models\WorkflowRun;
use CraftCms\Cms\Workflow\UserReview\UserReviewStage;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
#[Singleton]
class Workflows
{
    public function __construct(
        private readonly ActivityTimelinePresenter $activityTimelinePresenter,
        private readonly ProjectConfig $projectConfig,
    ) {}

    /** @return EloquentCollection<int, Workflow> */
    public function getAllWorkflows(): Collection
    {
        return Workflow::query()->get();
    }

    public function saveWorkflow(Workflow $workflow): void
    {
        $workflow->uid ??= Str::uuid7()->toString();
        $workflow->name = trim($workflow->name);

        $this->projectConfig->set(
            ProjectConfig::PATH_WORKFLOWS.'.'.$workflow->uid,
            $workflow->getConfig(),
            "Save workflow “{$workflow->name}”",
        );

        $workflow->id = Workflow::findByUid($workflow->uid)?->id;
    }

    public function deleteWorkflow(Workflow $workflow): void
    {
        $this->projectConfig->remove(
            ProjectConfig::PATH_WORKFLOWS.'.'.$workflow->uid,
            "Delete workflow “{$workflow->name}”",
        );
    }

    public function handleChangedWorkflow(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        /** @var array{name: string, stages: list<array{uid: string, name: string, type: string, settings?: array<string, mixed>}>} $data */
        $data = $event->newValue;

        DB::transaction(function () use ($uid, $data): void {
            $workflow = Workflow::findByUid($uid);
            $oldExecutionConfig = $workflow?->getExecutionConfig();
            $workflow ??= new Workflow(['uid' => $uid]);
            $workflow->fill([
                'name' => $data['name'],
                'stages' => array_map(
                    fn (array $stage): array => WorkflowStageData::fromArray($stage)->getConfig(),
                    $data['stages'],
                ),
            ])->save();

            if ($oldExecutionConfig !== null && $oldExecutionConfig !== $workflow->getExecutionConfig()) {
                $this->invalidateRuns($workflow, 'The workflow configuration changed.');
            }
        });
    }

    public function handleDeletedWorkflow(ConfigEvent $event): void
    {
        $workflow = Workflow::findByUid($event->tokenMatches[0]);
        if ($workflow === null) {
            return;
        }

        $this->invalidateRuns($workflow, 'The workflow was deleted.');
        $workflow->delete();
    }

    public function forElement(ElementInterface $element): ?Workflow
    {
        if (! Edition::isAtLeast(Edition::Pro) || ! $element instanceof WorkflowableInterface) {
            return null;
        }

        return $element->workflow();
    }

    public function requiresApproval(ElementInterface $element): bool
    {
        return $element->enabled
            && $element->getEnabledForSite()
            && $this->forElement($element) !== null;
    }

    public function latestRun(ElementInterface $draft): ?WorkflowRun
    {
        if (! $draft->draftId) {
            return null;
        }

        return WorkflowRun::query()
            ->where('draftId', $draft->draftId)
            ->with('activityRootEvent')
            ->latest('id')
            ->first();
    }

    public function submitForReview(ElementInterface $draft, ?string $note = null): WorkflowRun
    {
        $this->ensureAvailable();
        $author = $this->actor();
        $submittedDraft = $draft;

        $run = DB::transaction(function () use ($draft, $author, $note, &$submittedDraft): WorkflowRun {
            Draft::lockIds((int) $draft->draftId);
            $submittedDraft = $draft::find()
                ->draftId($draft->draftId)
                ->provisionalDrafts(null)
                ->siteId($draft->siteId)
                ->status(null)
                ->one()
                ?? throw new WorkflowException('This draft could not be found.');
            $this->ensureReviewableDraft($submittedDraft);

            if (! Gate::forUser($author)->allows('save', $submittedDraft)) {
                throw new WorkflowException('You are not allowed to edit this draft.');
            }

            $workflow = $this->forElement($submittedDraft)
                ?? throw new WorkflowException('This element does not have an approval workflow.');
            $latestRun = $this->latestRun($submittedDraft);

            if ($latestRun?->isPending()) {
                throw new WorkflowException('This draft is already in review.');
            }

            if ($latestRun?->status === WorkflowStatus::Approved) {
                throw new WorkflowException('This draft is already approved.');
            }

            if ($workflow->stages->isEmpty()) {
                throw new WorkflowException('A workflow must contain at least one stage.');
            }

            return $this->createRun($submittedDraft, $author, $workflow, $note);
        });

        $this->afterTransition(WorkflowTransition::Submit, $submittedDraft, $author, $run, $note);

        return $run;
    }

    public function restartWorkflow(ElementInterface $draft, int $runId): WorkflowRun
    {
        $this->ensureAvailable();
        $actor = $this->actor();
        $submittedDraft = $draft;
        $restartedRun = null;

        $run = DB::transaction(function () use ($draft, $runId, $actor, &$submittedDraft, &$restartedRun): WorkflowRun {
            Draft::lockIds((int) $draft->draftId);
            $submittedDraft = $draft::find()
                ->draftId($draft->draftId)
                ->provisionalDrafts(null)
                ->siteId($draft->siteId)
                ->status(null)
                ->one()
                ?? throw new WorkflowException('This draft could not be found.');
            $this->ensureReviewableDraft($submittedDraft);

            if (! Gate::forUser($actor)->allows('save', $submittedDraft)) {
                throw new WorkflowException('You are not allowed to edit this draft.');
            }

            $workflow = $this->forElement($submittedDraft)
                ?? throw new WorkflowException('This element does not have an approval workflow.');
            $restartedRun = $this->latestRun($submittedDraft);

            if ($restartedRun?->id !== $runId || ! in_array($restartedRun->status, [WorkflowStatus::Pending, WorkflowStatus::Approved], true)) {
                throw new WorkflowException('This review can no longer be restarted.');
            }

            $stage = $this->currentStage($restartedRun);
            $this->beforeTransition(WorkflowTransition::Restart, $submittedDraft, $actor, $restartedRun);
            $restartedRun->update([
                'status' => WorkflowStatus::Invalidated,
                'currentStageResult' => null,
            ]);
            $this->recordTransition($submittedDraft, $actor, $restartedRun, WorkflowTransition::Restart, $stage);

            return $this->createRun($submittedDraft, $actor, $workflow);
        });

        $this->afterTransition(WorkflowTransition::Restart, $submittedDraft, $actor, $restartedRun);
        $this->afterTransition(WorkflowTransition::Submit, $submittedDraft, $actor, $run);

        return $run;
    }

    /**
     * Readies a draft in review for the user to edit it. If they review an earlier stage than the current one,
     * the review goes back to that stage. Otherwise the draft is taken out of review, and has to be submitted again.
     * The current stage’s reviewers can edit it without either.
     */
    public function prepareForEditing(ElementInterface $draft, int $runId): WorkflowRun
    {
        $this->ensureAvailable();
        $actor = $this->actor();

        return DB::transaction(function () use ($draft, $runId, $actor): WorkflowRun {
            Draft::lockIds((int) $draft->draftId);
            $draft = $draft::find()
                ->draftId($draft->draftId)
                ->provisionalDrafts(null)
                ->siteId($draft->siteId)
                ->status(null)
                ->one()
                ?? throw new WorkflowException('This draft could not be found.');

            if (! Gate::forUser($actor)->allows('save', $draft)) {
                throw new WorkflowException('You are not allowed to edit this draft.');
            }

            $run = $this->latestRun($draft);

            if ($run?->id !== $runId || ! in_array($run->status, [WorkflowStatus::Pending, WorkflowStatus::Approved], true)) {
                throw new WorkflowException('This review is no longer current. Refresh and try again.');
            }

            if ($this->isCurrentStageReviewer($draft, $run, $actor)) {
                return $run;
            }

            $stageIndex = $this->rollbackStageIndex($draft, $run, $actor);

            if ($stageIndex !== null) {
                $this->beforeTransition(WorkflowTransition::SendBack, $draft, $actor, $run);
                $this->rollBackToStage($draft, $run, $stageIndex, $actor);
            } else {
                $this->beforeTransition(WorkflowTransition::Withdraw, $draft, $actor, $run);
                $this->withdrawFromReview($draft, $run, $actor);
            }

            return $run;
        });
    }

    /** @param WorkflowStageResult|Closure(WorkflowStageContext): WorkflowStageResult $result */
    public function reportStageResult(
        int $runId,
        string $stageUid,
        WorkflowStageResult|Closure $result,
        ?CraftUser $actor = null,
        ?WorkflowTransition $activityTransition = null,
        ?string $activityNote = null,
    ): ?WorkflowRun {
        $this->ensureAvailable();
        $run = WorkflowRun::query()->with('activityRootEvent')->find($runId);
        if ($run === null) {
            return null;
        }

        $draft = $this->draftForRun($run);
        if ($draft === null) {
            return $run;
        }

        $stageCompleted = false;
        $completedTransition = $activityTransition;
        $run = DB::transaction(function () use ($draft, $runId, $stageUid, $result, $actor, $activityTransition, $activityNote, &$stageCompleted, &$completedTransition): ?WorkflowRun {
            Draft::lockIds((int) $draft->draftId);
            $run = WorkflowRun::query()
                ->whereKey($runId)
                ->with('activityRootEvent')
                ->lockForUpdate()
                ->first();

            if ($run === null) {
                return null;
            }

            $canRequestReviewAgain = $activityTransition === WorkflowTransition::RequestReview
                && $run->status === WorkflowStatus::Failed;
            if (! $run->isPending() && ! $canRequestReviewAgain) {
                return $run;
            }

            $stage = $this->currentStage($run);
            if ($stage->uid !== $stageUid) {
                return $run;
            }

            $context = $this->stageContext($draft, $run, $stage);
            if ($activityTransition !== null) {
                $this->beforeTransition($activityTransition, $draft, $actor, $run, $activityNote);
            }

            $resolvedResult = value($result, $context);
            if ($activityTransition === null) {
                $this->recordAutomatedTransition($draft, $run, $stage, $resolvedResult);
                $completedTransition = match ($resolvedResult->status) {
                    WorkflowStageStatus::Approved => WorkflowTransition::StageApproved,
                    WorkflowStageStatus::Failed => WorkflowTransition::StageFailed,
                    WorkflowStageStatus::Pending => null,
                };
            } else {
                $this->recordTransition($draft, $actor, $run, $activityTransition, $stage, $activityNote);
            }
            if ($this->advanceFromResult($run, $stage, $resolvedResult)) {
                $this->advance($draft, $run);
            }
            $stageCompleted = true;

            return $run->refresh()->load('activityRootEvent');
        });

        if ($stageCompleted && $completedTransition !== null && $run !== null) {
            $this->afterTransition($completedTransition, $draft, $actor, $run, $activityNote);
        }

        return $run;
    }

    public function addComment(ElementInterface $draft, int $runId, int|string $stage, string $note): WorkflowRun
    {
        $this->ensureAvailable();

        if (trim($note) === '') {
            throw new WorkflowException('A comment is required.');
        }

        $actor = $this->actor();

        return DB::transaction(function () use ($draft, $runId, $stage, $note, $actor): WorkflowRun {
            $run = $this->lockedRun($draft, $runId);
            $workflowStage = is_int($stage) ? $this->stage($runId, $stage) : $run->stages()->firstWhere('uid', $stage);
            $stageIndex = $workflowStage === null
                ? false
                : $run->stages()->search(fn (WorkflowStageData $candidate): bool => $candidate->uid === $workflowStage->uid);
            if ($workflowStage === null || $stageIndex === false || $stageIndex > $run->currentStage) {
                throw new WorkflowException('This review stage has not been reached.');
            }

            if ($run->status === WorkflowStatus::Published || ! Gate::forUser($actor)->allows('view', $draft)) {
                throw new WorkflowException('You cannot comment on this review.');
            }

            $this->recordActivity($draft, $actor, $run, WorkflowActivityType::Comment, $workflowStage, $note);
            DB::afterCommit(fn () => event(new WorkflowCommented($draft, $actor, $run, $workflowStage, $note)));

            return $run;
        });
    }

    public function overrideApproval(ElementInterface $draft, int $runId, ?string $reason = null): WorkflowRun
    {
        $this->ensureAvailable();

        $reason = $reason !== null && trim($reason) !== '' ? $reason : null;

        $actor = $this->actor();
        if (! $actor->isAdmin()) {
            throw new WorkflowException('You do not have permission to override this workflow.');
        }

        $run = DB::transaction(function () use ($draft, $runId, $reason, $actor): WorkflowRun {
            Draft::lockIds((int) $draft->draftId);
            $run = $this->lockedRun($draft, $runId);

            if (! in_array($run->status, [WorkflowStatus::Pending, WorkflowStatus::Failed], true)) {
                throw new WorkflowException('This review can no longer be overridden.');
            }

            $this->beforeTransition(WorkflowTransition::Override, $draft, $actor, $run, $reason);
            $run->update([
                'status' => WorkflowStatus::Approved,
                'currentStageResult' => null,
            ]);
            $this->recordTransition($draft, $actor, $run, WorkflowTransition::Override, $run->stages()->get($run->currentStage), $reason);

            return $run->refresh()->load('activityRootEvent');
        });

        $this->afterTransition(WorkflowTransition::Override, $draft, $actor, $run, $reason);

        return $run;
    }

    public function reviewData(ElementInterface $draft, CraftUser $viewer): ?WorkflowReviewData
    {
        $workflow = $this->forElement($draft);
        if ($workflow === null) {
            return null;
        }

        $run = $this->latestRun($draft);
        $runs = $run === null ? collect() : WorkflowRun::query()
            ->where('draftId', $draft->draftId)
            ->with('activityRootEvent')
            ->latest('id')
            ->get();
        $timelineItems = collect($this->timelineItems($draft, $viewer, $runs));

        $rollbackStageIndex = $run !== null && ! $this->isCurrentStageReviewer($draft, $run, $viewer)
            ? $this->rollbackStageIndex($draft, $run, $viewer)
            : null;

        return WorkflowReviewData::from(
            $draft,
            $viewer,
            $run,
            $runs,
            $timelineItems,
            $rollbackStageIndex !== null ? $run?->stages()->get($rollbackStageIndex) : null,
        );
    }

    /**
     * @template T of ElementInterface
     *
     * @param  T  $draft
     * @param  Closure(T): T  $apply
     * @return T
     */
    public function applyDraft(ElementInterface $draft, ?CraftUser $actor, ?int $expectedRunId, ?int $expectedStage, Closure $apply): ElementInterface
    {
        if ($this->forElement($draft) === null) {
            return $apply($draft);
        }

        $run = $this->latestRun($draft);
        if ($run === null) {
            throw new WorkflowException('This draft must be approved before it can be applied.');
        }
        if ($run->id !== $expectedRunId || $run->currentStage !== $expectedStage) {
            throw new WorkflowException('The approval workflow has changed. Refresh and try again.');
        }
        if ($run->status !== WorkflowStatus::Approved) {
            throw new WorkflowException('This draft must be approved before it can be applied.');
        }
        if (ElementHelper::isOutdated($draft)) {
            throw new WorkflowException('This draft has changed since it was approved. Refresh and submit it for review again.');
        }

        $canonical = $apply($draft);
        $run->update(['status' => WorkflowStatus::Published]);
        $this->recordTransition($draft, $actor, $run, WorkflowTransition::Publish, $run->stages()->get($run->currentStage));

        return $canonical;
    }

    public function contentChanged(ElementInterface $draft): void
    {
        $this->contentChangedByDraftIds([(int) $draft->draftId]);
    }

    /** @param list<int> $draftIds */
    public function contentChangedByDraftIds(array $draftIds): void
    {
        $this->resetApprovalsForContentChanges(WorkflowRun::query()->whereIn('draftId', $draftIds));
    }

    public function invalidateRuns(Workflow $workflow, string $reason): void
    {
        $this->invalidateActiveRuns(
            WorkflowRun::query()->where('workflowId', $workflow->id),
            $reason,
        );
    }

    public function invalidateSectionRuns(int $sectionId): void
    {
        $draftIds = DB::table(Table::ENTRIES)
            ->join(Table::ELEMENTS, Table::ELEMENTS.'.id', '=', Table::ENTRIES.'.id')
            ->where(Table::ENTRIES.'.sectionId', $sectionId)
            ->whereNotNull(Table::ELEMENTS.'.draftId')
            ->pluck(Table::ELEMENTS.'.draftId');

        $this->invalidateActiveRuns(
            WorkflowRun::query()->whereIn('draftId', $draftIds),
            'The workflow assignment changed.',
        );
    }

    /**
     * @template T
     *
     * @param  list<int>  $draftIds
     * @param  Closure(): T  $callback
     * @return T
     */
    public function withContentChangeLock(array $draftIds, Closure $callback): mixed
    {
        return DB::transaction(function () use ($draftIds, $callback): mixed {
            Draft::lockIds(...$draftIds);
            $result = $callback();
            $this->contentChangedByDraftIds($draftIds);

            return $result;
        });
    }

    /**
     * @template T
     *
     * @param  Closure(WorkflowRun): T  $callback
     * @return T
     */
    public function withApplicationLock(ElementInterface $draft, ?int $expectedRunId, ?int $expectedStage, Closure $callback): mixed
    {
        return DB::transaction(function () use ($draft, $expectedRunId, $expectedStage, $callback): mixed {
            Draft::lockIds((int) $draft->draftId);
            $run = $this->latestRun($draft);
            if ($run === null) {
                throw new WorkflowException('This draft must be approved before it can be applied.');
            }
            if ($run->id !== $expectedRunId || $run->currentStage !== $expectedStage) {
                throw new WorkflowException('The approval workflow has changed. Refresh and try again.');
            }

            return $callback($run);
        });
    }

    private function advance(ElementInterface $draft, WorkflowRun $run): void
    {
        while ($run->isPending()) {
            $stage = $this->currentStage($run);
            $context = $this->stageContext($draft, $run, $stage);
            $result = $stage->component()->evaluate($context);
            if (! $stage->component() instanceof UserReviewStage) {
                $this->recordAutomatedTransition($draft, $run, $stage, $result);
            }

            if (! $this->advanceFromResult($run, $stage, $result)) {
                return;
            }
        }
    }

    private function advanceFromResult(WorkflowRun $run, WorkflowStageData $stage, WorkflowStageResult $result): bool
    {
        $this->storeStageResult($run, $stage, $result);

        if ($result->status === WorkflowStageStatus::Pending) {
            if (! $run->isPending()) {
                $run->update(['status' => WorkflowStatus::Pending]);
            }

            return false;
        }

        if ($result->status === WorkflowStageStatus::Failed) {
            $run->update(['status' => WorkflowStatus::Failed]);

            return false;
        }

        if ($run->currentStage >= $run->stages()->count() - 1) {
            $run->update(['status' => WorkflowStatus::Approved]);

            return false;
        }

        $run->increment('currentStage');
        $run->refresh();

        return true;
    }

    private function storeStageResult(WorkflowRun $run, WorkflowStageData $stage, WorkflowStageResult $result): void
    {
        $payload = $run->payload ?? [];
        $payload[$stage->uid] = $result->payload;
        $run->update(['payload' => $payload, 'currentStageResult' => $result->message]);
    }

    private function recordAutomatedTransition(
        ElementInterface $draft,
        WorkflowRun $run,
        WorkflowStageData $stage,
        WorkflowStageResult $result,
    ): void {
        $transition = match ($result->status) {
            WorkflowStageStatus::Approved => WorkflowTransition::StageApproved,
            WorkflowStageStatus::Failed => WorkflowTransition::StageFailed,
            WorkflowStageStatus::Pending => null,
        };

        if ($transition === null) {
            return;
        }

        $this->recordTransition(
            draft: $draft,
            actor: ActivityActor::system(),
            run: $run,
            transition: $transition,
            stage: $stage,
            note: $result->message,
        );
    }

    /** @param Builder<WorkflowRun> $query */
    private function invalidateActiveRuns($query, string $reason): void
    {
        DB::transaction(function () use ($query, $reason): void {
            $query->whereIn('status', [WorkflowStatus::Pending, WorkflowStatus::Approved])
                ->with('activityRootEvent')
                ->orderBy('draftId')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->each(function (WorkflowRun $run) use ($reason): void {
                    $draft = $this->draftForRun($run);
                    $run->update([
                        'status' => WorkflowStatus::Invalidated,
                        'currentStageResult' => null,
                    ]);

                    if ($draft === null) {
                        return;
                    }

                    $actor = currentUser();
                    $this->recordTransition($draft, $actor, $run, WorkflowTransition::Invalidate, $run->stages()->get($run->currentStage), $reason);
                    $this->afterTransition(WorkflowTransition::Invalidate, $draft, $actor, $run, $reason);
                });
        });
    }

    /** @param Builder<WorkflowRun> $query */
    private function resetApprovalsForContentChanges($query): void
    {
        $query->whereIn('status', [WorkflowStatus::Pending, WorkflowStatus::Approved])
            ->with('activityRootEvent')
            ->get()
            ->each(function (WorkflowRun $run): void {
                $draft = $this->draftForRun($run);
                if ($draft === null) {
                    return;
                }

                $stage = $this->currentStage($run);
                $component = $stage->component();
                $context = $this->stageContext($draft, $run, $stage);
                $actor = currentUser();

                if ($actor !== null && $component instanceof UserReviewStage && $component->isReviewer($context, $actor)) {
                    $this->revokeEditorApproval($draft, $run, $actor);

                    return;
                }

                if ($actor !== null) {
                    $stageIndex = $this->rollbackStageIndex($draft, $run, $actor);

                    if ($stageIndex !== null) {
                        $this->rollBackToStage($draft, $run, $stageIndex, $actor);
                    } else {
                        $this->withdrawFromReview($draft, $run, $actor);
                    }

                    return;
                }

                if ($component instanceof UserReviewStage) {
                    $contentChangeResult = $component->contentChanged($context);
                    if ($contentChangeResult === null) {
                        return;
                    }

                    $stagePayload = $contentChangeResult->payload;
                } else {
                    if ($run->isPending()) {
                        return;
                    }

                    $stagePayload = [];
                }

                $payload = $run->payload ?? [];
                $payload[$stage->uid] = $stagePayload;
                $run->update([
                    'status' => WorkflowStatus::Pending,
                    'currentStageResult' => null,
                    'payload' => $payload,
                ]);

                $this->recordTransition($draft, $actor, $run, WorkflowTransition::RequestReview, $stage);
                $this->advance($draft, $run);
                $run->refresh();
                $this->afterTransition(WorkflowTransition::RequestReview, $draft, $actor, $run);
            });
    }

    /**
     * Returns the index of the latest stage before the current one that a user reviews, which their edits send the
     * review back to.
     */
    private function rollbackStageIndex(ElementInterface $draft, WorkflowRun $run, CraftUser $user): ?int
    {
        if (! in_array($run->status, [WorkflowStatus::Pending, WorkflowStatus::Approved], true)) {
            return null;
        }

        for ($index = $run->currentStage - 1; $index >= 0; $index--) {
            $stage = $run->stages()->get($index);
            $component = $stage?->component();

            if ($component instanceof UserReviewStage && $component->isReviewer($this->stageContext($draft, $run, $stage), $user)) {
                return $index;
            }
        }

        return null;
    }

    private function isCurrentStageReviewer(ElementInterface $draft, WorkflowRun $run, CraftUser $user): bool
    {
        $stage = $this->currentStage($run);
        $component = $stage->component();

        return $component instanceof UserReviewStage && $component->isReviewer($this->stageContext($draft, $run, $stage), $user);
    }

    /**
     * Sends a review back to an earlier stage that the user reviews. Earlier stages and the other reviewers’
     * approvals of that stage stand; the user approves it again, and the stages after it start over.
     */
    private function rollBackToStage(ElementInterface $draft, WorkflowRun $run, int $stageIndex, CraftUser $actor): void
    {
        $stages = $run->stages();
        $stage = $stages->get($stageIndex) ?? throw new WorkflowException('The workflow stage could not be found.');
        $component = $stage->component();
        $payload = $run->payload ?? [];

        foreach ($stages->slice($stageIndex + 1) as $laterStage) {
            unset($payload[$laterStage->uid]);
        }

        if ($component instanceof UserReviewStage) {
            $payload[$stage->uid] = $component->revokeApproval($this->stageContext($draft, $run, $stage), $actor);
        }

        $run->update([
            'status' => WorkflowStatus::Pending,
            'currentStage' => $stageIndex,
            'currentStageResult' => null,
            'payload' => $payload,
        ]);

        $this->recordTransition($draft, $actor, $run, WorkflowTransition::SendBack, $stage);
        $this->advance($draft, $run);
        $run->refresh();
        $this->afterTransition(WorkflowTransition::SendBack, $draft, $actor, $run);
    }

    /**
     * Withdraws the approval of a current-stage reviewer who edited the draft, so they have to approve it again.
     * The other reviewers’ approvals stand.
     */
    private function revokeEditorApproval(ElementInterface $draft, WorkflowRun $run, CraftUser $actor): void
    {
        $stage = $this->currentStage($run);
        $component = $stage->component();
        $context = $this->stageContext($draft, $run, $stage);

        if (! $component instanceof UserReviewStage) {
            return;
        }

        $stagePayload = $component->revokeApproval($context, $actor);

        if ($run->isPending() && $stagePayload == $context->payload) {
            return;
        }

        $payload = $run->payload ?? [];
        $payload[$stage->uid] = $stagePayload;
        $run->update([
            'status' => WorkflowStatus::Pending,
            'currentStageResult' => null,
            'payload' => $payload,
        ]);

        $this->recordTransition($draft, $actor, $run, WorkflowTransition::Edit, $stage);
        $this->advance($draft, $run);
        $run->refresh();
        $this->afterTransition(WorkflowTransition::Edit, $draft, $actor, $run);
    }

    /**
     * Takes a draft out of review because someone outside its review edited it, so it has to be submitted again.
     */
    private function withdrawFromReview(ElementInterface $draft, WorkflowRun $run, CraftUser $actor): void
    {
        $run->update([
            'status' => WorkflowStatus::Withdrawn,
            'currentStageResult' => null,
        ]);

        $this->recordTransition($draft, $actor, $run, WorkflowTransition::Withdraw, $this->currentStage($run));
        $this->afterTransition(WorkflowTransition::Withdraw, $draft, $actor, $run);
    }

    private function draftForRun(WorkflowRun $run): ?ElementInterface
    {
        $type = $run->activityRootEvent->data['draft']['elementType'] ?? null;
        $siteId = $run->activityRootEvent->data['draft']['siteId'] ?? null;
        if (! is_string($type) || ! is_a($type, ElementInterface::class, true) || ! is_int($siteId)) {
            return null;
        }

        /** @var class-string<ElementInterface> $type */
        return $type::find()
            ->draftId($run->draftId)
            ->siteId($siteId)
            ->status(null)
            ->one();
    }

    private function stageContext(ElementInterface $draft, WorkflowRun $run, WorkflowStageData $stage): WorkflowStageContext
    {
        return new WorkflowStageContext($draft, $run, $stage, $run->payload[$stage->uid] ?? []);
    }

    private function lockedRun(ElementInterface $draft, int $runId): WorkflowRun
    {
        return WorkflowRun::query()->whereKey($runId)->where('draftId', $draft->draftId)
            ->with('activityRootEvent')->lockForUpdate()->first()
            ?? throw new WorkflowException('This review could not be found.');
    }

    private function currentStage(WorkflowRun $run): WorkflowStageData
    {
        return $run->stages()->get($run->currentStage)
            ?? throw new WorkflowException('The current workflow stage could not be found.');
    }

    private function stage(int $runId, int $index): WorkflowStageData
    {
        $run = WorkflowRun::query()->with('activityRootEvent')->findOrFail($runId);

        return $run->stages()->get($index)
            ?? throw new WorkflowException('The workflow stage could not be found.');
    }

    private function ensureAvailable(): void
    {
        if (! Edition::isAtLeast(Edition::Pro)) {
            throw new WorkflowException('Approval workflows require Craft Pro or Enterprise.');
        }
    }

    private function ensureReviewableDraft(ElementInterface $draft): void
    {
        if (! $draft->getIsDraft() || $draft->isProvisionalDraft || ! $draft->draftId || ! $draft->markDraftAsSaved || ! $draft::hasDrafts()) {
            throw new WorkflowException('Only saved named drafts can be submitted for review.');
        }

        if (! $this->requiresApproval($draft)) {
            throw new WorkflowException('Only enabled drafts with an approval workflow can be submitted for review.');
        }
    }

    private function actor(): CraftUser
    {
        return currentUser() ?? throw new WorkflowException('You must be signed in to update an approval workflow.');
    }

    private function beforeTransition(WorkflowTransition $transition, ElementInterface $draft, ?CraftUser $actor, ?WorkflowRun $run = null, ?string $note = null): void
    {
        $event = new WorkflowTransitioning($transition, $draft, $actor, $run, $note);
        event($event);
        if ($event->cancel) {
            throw new WorkflowException('The workflow transition was canceled.');
        }
    }

    private function afterTransition(WorkflowTransition $transition, ElementInterface $draft, ?CraftUser $actor, WorkflowRun $run, ?string $note = null): void
    {
        DB::afterCommit(fn () => event(new WorkflowTransitioned($transition, $draft, $actor, $run, $note)));
    }

    private function createRun(ElementInterface $draft, CraftUser $author, Workflow $workflow, ?string $note = null): WorkflowRun
    {
        $this->beforeTransition(WorkflowTransition::Submit, $draft, $author, note: $note);
        $rootEvent = $this->recordSubmission($draft, $author, $workflow, $note);
        $run = WorkflowRun::query()->create([
            'workflowId' => $workflow->id,
            'draftId' => $draft->draftId,
            'authorId' => $author->getCraftUserId(),
            'activityRootEventId' => $rootEvent->id,
            'currentStage' => 0,
            'currentStageResult' => null,
            'status' => WorkflowStatus::Pending,
            'payload' => [],
        ]);

        $this->advance($draft, $run);

        return $run->refresh()->load('activityRootEvent');
    }

    private function recordSubmission(ElementInterface $draft, CraftUser $actor, Workflow $workflow, ?string $note): ActivityEvent
    {
        return Activities::record(new WorkflowActivityEvent(
            subject: $draft->getCanonical(),
            type: WorkflowActivityType::Submit,
            note: $note,
            workflow: [
                'id' => $workflow->id,
                'uid' => $workflow->uid,
                'name' => $workflow->name,
                'stages' => $workflow->stages->map(fn (WorkflowStageData $stage): array => [
                    'uid' => $stage->uid,
                    'name' => $stage->name,
                    'type' => $stage->type,
                    'settings' => $stage->settings,
                ])->values()->all(),
            ],
            draft: [
                'elementId' => (int) $draft->id,
                'draftId' => (int) $draft->draftId,
                'elementType' => $draft::class,
                'siteId' => (int) $draft->siteId,
            ],
            actor: $actor,
        ));
    }

    private function recordTransition(
        ElementInterface $draft,
        CraftUser|ActivityActor|null $actor,
        WorkflowRun $run,
        WorkflowTransition $transition,
        ?WorkflowStageData $stage = null,
        ?string $note = null,
    ): void {
        $this->recordActivity(
            $draft,
            $actor,
            $run,
            WorkflowActivityType::from($transition->value),
            $stage,
            $note,
        );
    }

    private function recordActivity(
        ElementInterface $draft,
        CraftUser|ActivityActor|null $actor,
        WorkflowRun $run,
        WorkflowActivityType $type,
        ?WorkflowStageData $stage = null,
        ?string $note = null,
    ): void {
        $stageIndex = $stage === null
            ? false
            : $run->stages()->search(fn (WorkflowStageData $candidate): bool => $candidate->uid === $stage->uid);

        Activities::record(new WorkflowActivityEvent(
            subject: $draft->getCanonical(),
            type: $type,
            stage: $stage?->name,
            stageNumber: $stageIndex === false ? null : $stageIndex + 1,
            note: $note,
            runId: $run->id,
            actor: $actor,
        ));
    }

    /**
     * @param  Collection<int, WorkflowRun>  $runs
     * @return list<WorkflowTimelineItemData>
     */
    private function timelineItems(ElementInterface $draft, CraftUser $viewer, Collection $runs): array
    {
        if ($runs->isEmpty()) {
            return [];
        }

        $runIdsByRootEvent = $runs->mapWithKeys(fn (WorkflowRun $run): array => [$run->activityRootEventId => $run->id]);
        $rootEventIds = $runIdsByRootEvent->keys();
        $events = ActivityEvent::query()->subject(ActivitySubject::fromElement($draft))->eventTypes(WorkflowActivityEvent::class)
            ->where(fn (Builder $query) => $query
                ->whereIn('id', $rootEventIds)
                ->orWhereIn('payload->data->runId', $runs->pluck('id')))
            ->oldest('occurredAt')->orderBy('id')->get();
        $presented = $this->activityTimelinePresenter->events($events, $viewer->asElement())->keyBy('id');

        return $events->map(function (ActivityEvent $event) use ($presented, $runIdsByRootEvent): WorkflowTimelineItemData {
            $presentation = $presented->get($event->id);
            $type = WorkflowActivityType::from($event->data['type']);
            $runId = $event->data['runId'] ?? $runIdsByRootEvent->get($event->id);
            $impersonator = $presentation['impersonator'];

            return new WorkflowTimelineItemData(
                id: (string) $event->id,
                type: $type === WorkflowActivityType::Submit ? 'submission' : ($type === WorkflowActivityType::Comment ? 'comment' : 'review'),
                icon: match ($type) {
                    WorkflowActivityType::Submit => 'clipboard-list-check',
                    WorkflowActivityType::Comment => 'comment',
                    WorkflowActivityType::Approve, WorkflowActivityType::StageApproved, WorkflowActivityType::Override => 'check',
                    WorkflowActivityType::Publish => 'arrow-up-from-bracket',
                    WorkflowActivityType::Reject, WorkflowActivityType::StageFailed => 'xmark-large',
                    WorkflowActivityType::Edit => 'pencil',
                    default => 'rotate',
                },
                description: match ($type) {
                    WorkflowActivityType::Submit => t('requested review'),
                    WorkflowActivityType::Comment => t('commented'),
                    WorkflowActivityType::Approve, WorkflowActivityType::StageApproved => t('approved'),
                    WorkflowActivityType::Reject => t('requested changes'),
                    WorkflowActivityType::RequestReview => t('requested another review'),
                    WorkflowActivityType::Restart => t('restarted the workflow'),
                    WorkflowActivityType::StageFailed => t('failed the stage'),
                    WorkflowActivityType::Override => t('overrode the approval'),
                    WorkflowActivityType::Publish => t('published the draft'),
                    WorkflowActivityType::Invalidate => t('reset the review because its workflow changed'),
                    WorkflowActivityType::Withdraw => t('started editing the draft, withdrawing it from review'),
                    WorkflowActivityType::SendBack => t('started editing the draft, sending it back to this stage'),
                    WorkflowActivityType::Edit => t('edited the draft'),
                },
                actor: (array) $presentation['actor'],
                impersonator: is_array($impersonator) ? $impersonator : null,
                decision: match ($type) {
                    WorkflowActivityType::Approve, WorkflowActivityType::StageApproved, WorkflowActivityType::Override => WorkflowStageStatus::Approved->value,
                    WorkflowActivityType::Reject => 'rejected',
                    WorkflowActivityType::RequestReview,
                    WorkflowActivityType::Restart,
                    WorkflowActivityType::Invalidate,
                    WorkflowActivityType::Withdraw,
                    WorkflowActivityType::SendBack,
                    WorkflowActivityType::Edit => 'reset',
                    WorkflowActivityType::StageFailed => WorkflowStageStatus::Failed->value,
                    default => null,
                },
                noteHtml: $presentation['props']['noteHtml'] ?? null,
                occurredAt: $presentation['occurredAt'],
                formattedOccurredAt: (array) $presentation['formattedOccurredAt'],
                runId: (int) $runId,
                stageNumber: isset($event->data['stageNumber']) ? (int) $event->data['stageNumber'] : null,
            );
        })->all();
    }
}
