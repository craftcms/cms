<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\UserReview;

use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\UserGroup;
use CraftCms\Cms\Workflow\Data\WorkflowStageContext;
use CraftCms\Cms\Workflow\Data\WorkflowStageResult;
use CraftCms\Cms\Workflow\Enums\WorkflowStageStatus;
use CraftCms\Cms\Workflow\Exceptions\WorkflowException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

use function CraftCms\Cms\t;

readonly class UserReviewStateMachine
{
    /**
     * @param  Collection<int, UserGroup>  $groups
     */
    private function __construct(
        private UserReviewStage $stage,
        private WorkflowStageContext $context,
        private Collection $groups,
        private UserReviewState $state,
        private UserReviewDecisions $decisions,
        private bool $hasMissingReviewerGroups,
    ) {}

    public static function for(UserReviewStage $stage, WorkflowStageContext $context): self
    {
        $groupsByUid = UserGroup::query()->whereIn('uid', $stage->userGroups)->get()->keyBy('uid');
        $groups = collect($stage->userGroups)
            ->map(fn (string $uid): ?UserGroup => $groupsByUid->get($uid))
            ->filter()
            ->values();

        return new self(
            $stage,
            $context,
            $groups,
            UserReviewState::for($context, $groups),
            UserReviewDecisions::fromPayload($context->payload),
            $groups->count() !== count($stage->userGroups),
        );
    }

    public function result(): WorkflowStageResult
    {
        if ($this->decisions->hasRejection()) {
            return new WorkflowStageResult(WorkflowStageStatus::Failed, t('Changes requested'), $this->context->payload);
        }

        if ($this->hasMissingReviewerGroups) {
            return new WorkflowStageResult(
                WorkflowStageStatus::Pending,
                t('A configured reviewer group no longer exists.'),
                $this->context->payload,
            );
        }

        if ($this->stage->approvalMode === UserReviewApprovalMode::PerGroup) {
            return $this->perGroupResult();
        }

        if ($this->state->reviewers->count() < $this->stage->approvalsRequired) {
            return new WorkflowStageResult(
                WorkflowStageStatus::Pending,
                t('Not enough eligible reviewers are available.'),
                $this->context->payload,
            );
        }

        $approvals = $this->state->effectiveApprovalIds->count();
        if ($approvals >= $this->stage->approvalsRequired) {
            return new WorkflowStageResult(WorkflowStageStatus::Approved, t('Approved'), $this->context->payload);
        }

        return new WorkflowStageResult(
            WorkflowStageStatus::Pending,
            t('{count} of {required} approved', ['count' => $approvals, 'required' => $this->stage->approvalsRequired]),
            $this->context->payload,
        );
    }

    /** @return array{canReview: bool, canRequestReviewAgain: bool} */
    public function actionsFor(CraftUser $viewer): array
    {
        return [
            'canReview' => $this->canReview($viewer),
            'canRequestReviewAgain' => $this->canRequestReviewAgain($viewer),
        ];
    }

    public function decide(UserReviewDecision $decision, ?string $message, CraftUser $reviewer): WorkflowStageResult
    {
        $reviewerId = $reviewer->getCraftUserId();
        if ($reviewerId === null || ! $this->canReview($reviewer)) {
            throw new WorkflowException('This stage action is not available to you.');
        }

        $message = trim((string) $message);
        if ($decision === UserReviewDecision::Rejected && $message === '') {
            throw new WorkflowException('A message is required when requesting changes.');
        }

        $payload = $this->context->payload;
        $payload['decisions'] = $this->decisions->append(
            $reviewerId,
            $decision,
            $message !== '' ? $message : null,
        );

        return self::for($this->stage, $this->withPayload($payload))->result();
    }

    public function requestReviewAgain(CraftUser $requester): WorkflowStageResult
    {
        if (! $this->canRequestReviewAgain($requester)) {
            throw new WorkflowException('This stage cannot be requested again.');
        }

        return $this->awaitingApproval();
    }

    public function contentChanged(): ?WorkflowStageResult
    {
        if ($this->context->run->isPending() && $this->state->effectiveApprovalIds->isEmpty()) {
            return null;
        }

        return $this->awaitingApproval();
    }

    /** @return Collection<int, User> */
    public function outstandingReviewers(): Collection
    {
        if ($this->hasMissingReviewerGroups) {
            return collect();
        }

        $decidedReviewerIds = collect($this->decisions->all)->pluck('reviewerId');

        return $this->state->reviewers->whereNotIn(
            'id',
            $this->state->effectiveApprovalIds->merge($decidedReviewerIds),
        );
    }

    public function state(): UserReviewState
    {
        return $this->state;
    }

    /** @return Collection<int, UserGroup> */
    public function groups(): Collection
    {
        return $this->groups;
    }

    private function canReview(CraftUser $reviewer): bool
    {
        if ($this->hasMissingReviewerGroups || $this->decisions->hasRejection()) {
            return false;
        }

        $reviewerId = $reviewer->getCraftUserId();

        return $reviewerId !== null
            && $reviewerId !== $this->context->run->authorId
            && $this->state->reviewers->contains('id', $reviewerId)
            && $this->state->effectiveApprovalIds->doesntContain($reviewerId)
            && ! $this->decisions->hasDecisionFrom($reviewerId);
    }

    private function canRequestReviewAgain(CraftUser $requester): bool
    {
        return $this->decisions->hasRejection()
            && $requester->getCraftUserId() === $this->context->run->authorId
            && Gate::forUser($requester)->allows('save', $this->context->draft);
    }

    private function perGroupResult(): WorkflowStageResult
    {
        $groups = $this->groups->map(function (UserGroup $group): array {
            $reviewerIds = $this->state->reviewersForGroup($group)->pluck('id');

            return [
                'reviewers' => $reviewerIds->count(),
                'approvals' => $this->state->effectiveApprovalIds->intersect($reviewerIds)->count(),
            ];
        });

        if ($groups->contains(fn (array $group): bool => $group['reviewers'] < $this->stage->approvalsRequired)) {
            return new WorkflowStageResult(
                WorkflowStageStatus::Pending,
                t('Not enough eligible reviewers are available.'),
                $this->context->payload,
            );
        }

        if ($groups->every(fn (array $group): bool => $group['approvals'] >= $this->stage->approvalsRequired)) {
            return new WorkflowStageResult(WorkflowStageStatus::Approved, t('Approved'), $this->context->payload);
        }

        return new WorkflowStageResult(
            WorkflowStageStatus::Pending,
            t('Approvals are required from every reviewer group.'),
            $this->context->payload,
        );
    }

    private function awaitingApproval(): WorkflowStageResult
    {
        return new WorkflowStageResult(
            WorkflowStageStatus::Pending,
            t('Awaiting approval'),
            [
                'decisions' => [],
                'previousApprovalsReset' => true,
            ],
        );
    }

    /** @param array<string, mixed> $payload */
    private function withPayload(array $payload): WorkflowStageContext
    {
        return new WorkflowStageContext(
            draft: $this->context->draft,
            run: $this->context->run,
            stage: $this->context->stage,
            payload: $payload,
        );
    }
}
