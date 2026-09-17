<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\UserReview;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Form\Controls\Number;
use CraftCms\Cms\Form\Controls\UserGroupSelect;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Workflow\Data\WorkflowStageContext;
use CraftCms\Cms\Workflow\Data\WorkflowStageResult;
use CraftCms\Cms\Workflow\Enums\WorkflowStageStatus;
use CraftCms\Cms\Workflow\Exceptions\WorkflowException;
use CraftCms\Cms\Workflow\Stages\WorkflowStage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use function CraftCms\Cms\t;

class UserReviewStage extends WorkflowStage
{
    #[\Override]
    protected bool $showDefaultReviewActions = false;

    public int $approvalsRequired = 1;

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
        ];
    }

    public function settingsForm(FormContext $context = new FormContext): Form
    {
        return Form::make([
            Field::make(t('Reviewer groups'), UserGroupSelect::make('userGroups'))
                ->required(),
            Field::make(t('Approvals required'), Number::make('approvalsRequired')->min(1))
                ->required(),
        ]);
    }

    public function evaluate(WorkflowStageContext $context): WorkflowStageResult
    {
        $decisions = $this->decisions($context->payload);
        $failed = collect($decisions)->contains(fn (array $decision): bool => $decision['decision'] === UserReviewDecision::Rejected->value);

        if ($failed) {
            return new WorkflowStageResult(WorkflowStageStatus::Failed, t('Changes requested'), $context->payload);
        }

        $reviewerIds = $this->reviewers($context)->pluck('id')->all();
        $approvals = collect($decisions)->filter(fn (array $decision): bool => $decision['decision'] === UserReviewDecision::Approved->value &&
            in_array($decision['reviewerId'], $reviewerIds, true)
        )->count();

        if (count($reviewerIds) < $this->approvalsRequired) {
            return new WorkflowStageResult(
                WorkflowStageStatus::Pending,
                t('Not enough eligible reviewers are available.'),
                $context->payload,
            );
        }

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
        $reviewers = $this->reviewers($context);
        $reviewerIds = $reviewers->pluck('id');
        $approvals = collect($this->decisions($context->payload))
            ->where('decision', UserReviewDecision::Approved->value)
            ->whereIn('reviewerId', $reviewerIds)
            ->count();

        return [
            'approvalsRequired' => $this->approvalsRequired,
            'approvals' => $approvals,
            'reviewers' => $reviewers->map(fn (User $reviewer): array => ['name' => $reviewer->name])->all(),
        ];
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
        $payload['decisions'] = [
            ...$this->decisions($payload),
            [
                'reviewerId' => $reviewer->getCraftUserId(),
                'decision' => $decision->value,
                'message' => $message !== '' ? $message : null,
                'decidedAt' => now()->toIso8601String(),
            ],
        ];

        return $this->evaluate(new WorkflowStageContext(
            draft: $context->draft,
            run: $context->run,
            stage: $context->stage,
            payload: $payload,
        ));
    }

    private function canReview(WorkflowStageContext $context, CraftUser $reviewer): bool
    {
        $reviewerId = $reviewer->getCraftUserId();

        return $reviewerId !== null
            && $reviewerId !== $context->run->authorId
            && $this->reviewers($context)->contains('id', $reviewerId)
            && collect($this->decisions($context->payload))->doesntContain('reviewerId', $reviewerId);
    }

    /** @return Collection<int, User> */
    public function reviewers(WorkflowStageContext $context): Collection
    {
        $groupIds = array_filter(array_map(
            fn (string $uid): ?int => UserGroups::getGroupByUid($uid)?->id,
            $this->userGroups,
        ));

        return User::find()
            ->groupId($groupIds)
            ->status(User::STATUS_ACTIVE)
            ->id(['not', $context->run->authorId])
            ->can('accessCp')
            ->collect()
            ->filter(fn (User $user): bool => Gate::forUser($user)->allows('view', $context->draft))
            ->values();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{reviewerId: int, decision: string, message: string|null, decidedAt: string}>
     */
    private function decisions(array $payload): array
    {
        return is_array($payload['decisions'] ?? null) ? $payload['decisions'] : [];
    }
}
