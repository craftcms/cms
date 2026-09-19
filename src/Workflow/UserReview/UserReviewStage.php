<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\UserReview;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Number;
use CraftCms\Cms\Form\Controls\UserGroupSelect;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\UserGroup;
use CraftCms\Cms\Workflow\Data\WorkflowStageContext;
use CraftCms\Cms\Workflow\Data\WorkflowStageResult;
use CraftCms\Cms\Workflow\Enums\WorkflowStageStatus;
use CraftCms\Cms\Workflow\Exceptions\WorkflowException;
use CraftCms\Cms\Workflow\Stages\WorkflowStage;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

use function CraftCms\Cms\t;

class UserReviewStage extends WorkflowStage
{
    #[\Override]
    protected bool $showDefaultReviewActions = false;

    public int $approvalsRequired = 1;

    public UserReviewApprovalMode $approvalMode = UserReviewApprovalMode::Total;

    /** @var list<string> */
    public array $userGroups = [];

    public static function displayName(): string
    {
        return t('User review');
    }

    public function getRules(): array
    {
        return [
            'userGroups' => ['required', 'array', 'min:1'],
            'userGroups.*' => ['required', 'string', 'distinct', Rule::exists(Table::USERGROUPS, 'uid')],
            'approvalsRequired' => ['required', 'integer', 'min:1'],
            'approvalMode' => ['required', Rule::enum(UserReviewApprovalMode::class)],
        ];
    }

    public function settingsForm(FormContext $context = new FormContext): Form
    {
        return Form::make([
            Field::make(t('Reviewer groups'), UserGroupSelect::make('userGroups'))
                ->required(),
            Field::make(t('Approvals required'), Number::make('approvalsRequired')->min(1))
                ->required(),
            Field::make(t('Approval mode'), Choice::make('approvalMode')->options([
                ['label' => t('Total'), 'value' => UserReviewApprovalMode::Total->value],
                ['label' => t('Per group'), 'value' => UserReviewApprovalMode::PerGroup->value],
            ]))->required(),
        ]);
    }

    public function evaluate(WorkflowStageContext $context): WorkflowStageResult
    {
        if (UserReviewDecisions::fromPayload($context->payload)->hasRejection()) {
            return new WorkflowStageResult(WorkflowStageStatus::Failed, t('Changes requested'), $context->payload);
        }

        if ($this->hasMissingReviewerGroups()) {
            return new WorkflowStageResult(
                WorkflowStageStatus::Pending,
                t('A configured reviewer group no longer exists.'),
                $context->payload,
            );
        }

        $state = $this->state($context);

        if ($this->approvalMode === UserReviewApprovalMode::PerGroup) {
            return $this->evaluatePerGroup($context, $state);
        }

        if ($state->reviewers->count() < $this->approvalsRequired) {
            return new WorkflowStageResult(
                WorkflowStageStatus::Pending,
                t('Not enough eligible reviewers are available.'),
                $context->payload,
            );
        }

        $approvals = $state->effectiveApprovalIds->count();
        if ($approvals >= $this->approvalsRequired) {
            return new WorkflowStageResult(WorkflowStageStatus::Approved, t('Approved'), $context->payload);
        }

        return new WorkflowStageResult(
            WorkflowStageStatus::Pending,
            t('{count} of {required} approved', ['count' => $approvals, 'required' => $this->approvalsRequired]),
            $context->payload,
        );
    }

    public function actionComponent(): string
    {
        return 'craft:user-review-workflow-stage-actions';
    }

    public function actionProps(WorkflowStageContext $context, CraftUser $viewer): array
    {
        return [
            'canReview' => $this->canReview($context, $viewer),
        ];
    }

    public function summaryComponent(): string
    {
        return 'craft:user-review-workflow-stage-summary';
    }

    public function summaryProps(WorkflowStageContext $context, CraftUser $viewer): array
    {
        $groups = $this->reviewerGroups();

        return UserReviewSummary::props($this, UserReviewState::for($context, $groups), $groups);
    }

    public function decide(UserReviewDecision $decision, ?string $message, WorkflowStageContext $context, CraftUser $reviewer): WorkflowStageResult
    {
        if (! $this->canReview($context, $reviewer)) {
            throw new WorkflowException('This stage action is not available to you.');
        }

        $message = trim((string) $message);
        if ($decision === UserReviewDecision::Rejected && $message === '') {
            throw new WorkflowException('A message is required when requesting changes.');
        }

        $payload = $context->payload;
        $payload['decisions'] = UserReviewDecisions::fromPayload($payload)->append($reviewer->getCraftUserId(), $decision, $message !== '' ? $message : null);

        return $this->evaluate(new WorkflowStageContext(
            draft: $context->draft,
            run: $context->run,
            stage: $context->stage,
            payload: $payload,
        ));
    }

    private function canReview(WorkflowStageContext $context, CraftUser $reviewer): bool
    {
        if ($this->hasMissingReviewerGroups()) {
            return false;
        }

        $reviewerId = $reviewer->getCraftUserId();
        $state = $this->state($context);

        return $reviewerId !== null
            && $reviewerId !== $context->run->authorId
            && $state->reviewers->contains('id', $reviewerId)
            && $state->effectiveApprovalIds->doesntContain($reviewerId)
            && ! UserReviewDecisions::fromPayload($context->payload)->hasDecisionFrom($reviewerId);
    }

    /** @return Collection<int, User> */
    public function outstandingReviewers(WorkflowStageContext $context): Collection
    {
        if ($this->hasMissingReviewerGroups()) {
            return collect();
        }

        $state = $this->state($context);
        $decidedReviewerIds = collect(UserReviewDecisions::fromPayload($context->payload)->all)->pluck('reviewerId');

        return $state->reviewers->whereNotIn('id', $state->effectiveApprovalIds->merge($decidedReviewerIds));
    }

    private function evaluatePerGroup(WorkflowStageContext $context, UserReviewState $state): WorkflowStageResult
    {
        $groups = $this->reviewerGroups()->map(function (UserGroup $group) use ($state): array {
            $reviewerIds = $state->reviewersForGroup($group)->pluck('id');

            return [
                'reviewers' => $reviewerIds->count(),
                'approvals' => $state->effectiveApprovalIds->intersect($reviewerIds)->count(),
            ];
        });

        if ($groups->contains(fn (array $group): bool => $group['reviewers'] < $this->approvalsRequired)) {
            return new WorkflowStageResult(WorkflowStageStatus::Pending, t('Not enough eligible reviewers are available.'), $context->payload);
        }

        if ($groups->every(fn (array $group): bool => $group['approvals'] >= $this->approvalsRequired)) {
            return new WorkflowStageResult(WorkflowStageStatus::Approved, t('Approved'), $context->payload);
        }

        return new WorkflowStageResult(WorkflowStageStatus::Pending, t('Approvals are required from every reviewer group.'), $context->payload);
    }

    /** @return Collection<int, UserGroup> */
    private function reviewerGroups(): Collection
    {
        $groups = UserGroup::query()->whereIn('uid', $this->userGroups)->get()->keyBy('uid');

        return collect($this->userGroups)
            ->map(fn (string $uid): ?UserGroup => $groups->get($uid))
            ->filter()
            ->values();
    }

    private function hasMissingReviewerGroups(): bool
    {
        return $this->reviewerGroups()->count() !== count($this->userGroups);
    }

    private function state(WorkflowStageContext $context): UserReviewState
    {
        return UserReviewState::for($context, $this->reviewerGroups());
    }
}
