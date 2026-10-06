<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Mcp\Attributes\RequiresAdmin;
use CraftCms\Cms\Mcp\ElementQueryFactory;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Mcp\Serializers\WorkflowReviewSerializer;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Workflow\Data\WorkflowStageContext;
use CraftCms\Cms\Workflow\Data\WorkflowStageResult;
use CraftCms\Cms\Workflow\Enums\WorkflowStatus;
use CraftCms\Cms\Workflow\Enums\WorkflowTransition;
use CraftCms\Cms\Workflow\Exceptions\WorkflowException;
use CraftCms\Cms\Workflow\Models\Workflow;
use CraftCms\Cms\Workflow\UserReview\UserReviewDecision;
use CraftCms\Cms\Workflow\UserReview\UserReviewStage;
use CraftCms\Cms\Workflow\Workflows as WorkflowService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/**
 * @since 6.0.0
 */
readonly class Workflows
{
    public function __construct(
        private WorkflowService $workflows,
        private ElementQueryFactory $elementQueries,
        private McpActor $actor,
        private WorkflowReviewSerializer $reviews,
    ) {}

    /** @return array{count: int, workflows: list<array<string, mixed>>} */
    #[McpTool(name: 'workflows.list', description: 'Lists editorial workflow definitions and their assigned sections.', annotations: new ToolAnnotations(readOnlyHint: true))]
    #[RequiresAdmin]
    public function list(): array
    {
        $workflows = $this->workflows->getAllWorkflows()
            ->load('sections')
            ->sortBy('id')
            ->map($this->definition(...))
            ->values();

        return ['count' => $workflows->count(), 'workflows' => $workflows->all()];
    }

    /** @return array{workflow: array<string, mixed>} */
    #[McpTool(name: 'workflows.get', description: 'Gets an editorial workflow definition and its assigned sections by ID or UID.', annotations: new ToolAnnotations(readOnlyHint: true))]
    #[RequiresAdmin]
    public function get(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
    ): array {
        if (count(Arr::whereNotNull([$id, $uid])) !== 1) {
            throw new ToolCallException('Provide exactly one of: id, uid.');
        }

        $workflow = $this->workflows->getAllWorkflows()->first(fn (Workflow $workflow): bool => $id !== null ? $workflow->id === $id : $workflow->uid === $uid);

        if ($workflow === null) {
            throw new ToolCallException('Workflow not found.');
        }

        return ['workflow' => $this->definition($workflow->load('sections'))];
    }

    /** @return array{review: array<string, mixed>|null} */
    #[McpTool(name: 'workflows.review', description: 'Inspects a draft’s assigned editorial workflow, review state, history, and actions available to the authenticated user. Refresh before each decision or apply. Use review.runId and review.stage.uid for review decisions; use review.runId and review.currentStage as workflowRunId and workflowCurrentStage for drafts.apply. Approval does not publish.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function review(
        #[Schema(description: 'Registered element type reference handle or class name.')]
        string $type,
        #[Schema(description: 'Draft element ID, not the canonical element ID or draft record ID.')]
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
    ): array {
        return $this->response($this->draft($type, $id, $uid, $siteId));
    }

    /** @return array{review: array<string, mixed>|null} */
    #[McpTool(name: 'workflows.submit', description: 'Submits an enabled, saved named draft for editorial review. After rejection, creates a new run from the first stage.')]
    public function submit(
        string $type,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        #[Schema(maxLength: 5000)]
        ?string $note = null,
    ): array {
        $draft = $this->draft($type, $id, $uid, $siteId);
        $this->validateNote($note);

        return $this->transition($draft, fn () => $this->workflows->submitForReview($draft, $note));
    }

    /** @return array{review: array<string, mixed>|null} */
    #[McpTool(name: 'workflows.approve', description: 'Records the authenticated reviewer’s approval at a user-review stage. Does not publish the draft.')]
    public function approve(
        string $type,
        #[Schema(minimum: 1, description: 'review.runId from a fresh workflows.review result for the draft.')]
        int $runId,
        #[Schema(format: 'uuid', description: 'review.stage.uid from the same workflows.review result; not the numeric review.currentStage index.')]
        string $stageUid,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        #[Schema(maxLength: 5000)]
        ?string $message = null,
    ): array {
        return $this->decide($this->draft($type, $id, $uid, $siteId), $runId, $stageUid, UserReviewDecision::Approved, $message);
    }

    /** @return array{review: array<string, mixed>|null} */
    #[McpTool(name: 'workflows.requestChanges', description: 'Requests changes to a draft at its current user-review stage. Requires a message explaining the changes.')]
    public function requestChanges(
        string $type,
        #[Schema(minimum: 1, description: 'review.runId from a fresh workflows.review result for the draft.')]
        int $runId,
        #[Schema(format: 'uuid', description: 'review.stage.uid from the same workflows.review result; not the numeric review.currentStage index.')]
        string $stageUid,
        #[Schema(minLength: 1, maxLength: 5000)]
        string $message,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
    ): array {
        return $this->decide($this->draft($type, $id, $uid, $siteId), $runId, $stageUid, UserReviewDecision::Rejected, $message);
    }

    /** @return array{review: array<string, mixed>|null} */
    #[McpTool(name: 'workflows.requestReview', description: 'Lets the submitting author request another review after changes were requested. Resets decisions at the current user-review stage within the same run.')]
    public function requestReview(
        string $type,
        #[Schema(minimum: 1, description: 'review.runId from a fresh workflows.review result for the draft.')]
        int $runId,
        #[Schema(format: 'uuid', description: 'review.stage.uid from the same workflows.review result; not the numeric review.currentStage index.')]
        string $stageUid,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
    ): array {
        $draft = $this->draft($type, $id, $uid, $siteId);

        return $this->stageTransition($draft, $runId, $stageUid, WorkflowTransition::RequestReview, fn (UserReviewStage $stage, WorkflowStageContext $context): WorkflowStageResult => $stage->requestReviewAgain($context, $this->actor->user()));
    }

    /** @return array{review: array<string, mixed>|null} */
    #[McpTool(name: 'workflows.restart', description: 'Restarts a pending or approved editorial workflow from the first stage, invalidating the specified run.', annotations: new ToolAnnotations(destructiveHint: true))]
    public function restart(
        string $type,
        #[Schema(minimum: 1, description: 'review.runId from a fresh workflows.review result for the draft.')]
        int $runId,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
    ): array {
        $draft = $this->draft($type, $id, $uid, $siteId);

        return $this->transition($draft, fn () => $this->workflows->restartWorkflow($draft, $runId));
    }

    /** @return array{review: array<string, mixed>|null} */
    private function decide(ElementInterface $draft, int $runId, string $stageUid, UserReviewDecision $decision, ?string $message): array
    {
        $this->validateNote($message, required: $decision === UserReviewDecision::Rejected);
        $transition = $decision === UserReviewDecision::Approved ? WorkflowTransition::Approve : WorkflowTransition::Reject;

        return $this->stageTransition($draft, $runId, $stageUid, $transition, fn (UserReviewStage $stage, WorkflowStageContext $context): WorkflowStageResult => $stage->decide($decision, $message, $context, $this->actor->user()), trim((string) $message) ?: null);
    }

    /**
     * @param  Closure(UserReviewStage, WorkflowStageContext): WorkflowStageResult  $result
     * @return array{review: array<string, mixed>|null}
     */
    private function stageTransition(ElementInterface $draft, int $runId, string $stageUid, WorkflowTransition $transition, Closure $result, ?string $note = null): array
    {
        return $this->transition($draft, fn () => DB::transaction(function () use ($draft, $runId, $stageUid, $transition, $result, $note): void {
            $this->workflows->lockDraftIds([(int) $draft->draftId]);
            $run = $this->workflows->latestRun($draft);
            $stage = $run?->stages()->get($run->currentStage);
            $expectedStatus = $transition === WorkflowTransition::RequestReview ? WorkflowStatus::Failed : WorkflowStatus::Pending;

            if ($run?->id !== $runId || $stage?->uid !== $stageUid || $run->status !== $expectedStatus) {
                throw new WorkflowException('The workflow review has changed. Refresh and try again.');
            }

            $this->workflows->reportStageResult(
                runId: $runId,
                stageUid: $stageUid,
                result: function (WorkflowStageContext $context) use ($result): WorkflowStageResult {
                    $component = $context->stage->component();

                    if (! $component instanceof UserReviewStage) {
                        throw new WorkflowException('The current workflow stage does not accept user reviews.');
                    }

                    return $result($component, $context);
                },
                actor: $this->actor->user(),
                activityTransition: $transition,
                activityNote: $note,
            );
        }));
    }

    /**
     * @param  Closure(): mixed  $callback
     * @return array{review: array<string, mixed>|null}
     */
    private function transition(ElementInterface $draft, Closure $callback): array
    {
        try {
            $callback();
        } catch (WorkflowException $exception) {
            throw new ToolCallException($exception->getMessage(), previous: $exception);
        }

        return $this->response($draft);
    }

    private function validateNote(?string $note, bool $required = false): void
    {
        if ($required && trim((string) $note) === '') {
            throw new ToolCallException('A message is required when requesting changes.');
        }

        if ($note !== null && mb_strlen($note) > 5000) {
            throw new ToolCallException('Review notes must not exceed 5000 characters.');
        }
    }

    private function draft(string $type, ?int $id, ?string $uid, ?int $siteId): ElementInterface
    {
        if (count(Arr::whereNotNull([$id, $uid])) !== 1) {
            throw new ToolCallException('Provide exactly one of: id, uid.');
        }

        $query = $this->elementQueries->make($type)
            ->drafts()
            ->provisionalDrafts(null)
            ->status(null);

        Typecast::configure($query, Arr::whereNotNull(['id' => $id, 'uid' => $uid, 'siteId' => $siteId]));
        $draft = $query->one();

        if ($draft === null || ! $draft->getIsDraft() || ! Gate::forUser($this->actor->user())->allows('view', $draft)) {
            throw new ToolCallException('Draft not found.');
        }

        return $draft;
    }

    /** @return array{review: array<string, mixed>|null} */
    private function response(ElementInterface $draft): array
    {
        return ['review' => $this->reviews->serialize($draft, $this->actor->user())];
    }

    /** @return array<string, mixed> */
    private function definition(Workflow $workflow): array
    {
        return [
            'id' => $workflow->id,
            'uid' => $workflow->uid,
            ...$workflow->getConfig(),
            'sections' => $workflow->sections->map(fn (Section $section): array => [
                'id' => $section->id,
                'uid' => $section->uid,
                'name' => $section->name,
                'handle' => $section->handle,
            ])->values()->all(),
        ];
    }
}
