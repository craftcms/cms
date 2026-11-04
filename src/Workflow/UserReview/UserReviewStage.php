<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\UserReview;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Controls\Number;
use CraftCms\Cms\Ui\Controls\UserGroupSelect;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Group;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Workflow\Data\WorkflowStageContext;
use CraftCms\Cms\Workflow\Data\WorkflowStageResult;
use CraftCms\Cms\Workflow\Stages\WorkflowStage;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
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
        return t('User Review');
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

    public function settingsUi(UiContext $context = new UiContext): Ui
    {
        return Ui::make([
            Group::make('user-review-settings', [
                Field::make(t('Reviewer Groups'), UserGroupSelect::make('userGroups'))
                    ->required(),
                Group::make('user-review-approval-requirement', [
                    Field::make(t('Minimum'), Number::make('approvalsRequired')->min(1)->size(3))
                        ->required(),
                    Field::make(t('Mode'), Choice::make('approvalMode')->options([
                        ['label' => t('Total'), 'value' => UserReviewApprovalMode::Total->value],
                        ['label' => t('Per group'), 'value' => UserReviewApprovalMode::PerGroup->value],
                    ]))
                        ->required(),
                ])
                    ->label(t('Approvals Required'))
                    ->instructions(t('The minimum number of approvals required in total or per reviewer group.'))
                    ->asField()
                    ->required(),
            ]),
        ]);
    }

    public function evaluate(WorkflowStageContext $context): WorkflowStageResult
    {
        return $this->stateMachine($context)->result();
    }

    public function actionComponent(): string
    {
        return 'craft:user-review-workflow-stage-actions';
    }

    public function actionProps(WorkflowStageContext $context, CraftUser $viewer): array
    {
        return $this->stateMachine($context)->actionsFor($viewer);
    }

    public function summaryComponent(): string
    {
        return 'craft:user-review-workflow-stage-summary';
    }

    public function summaryProps(WorkflowStageContext $context, CraftUser $viewer): array
    {
        $stateMachine = $this->stateMachine($context);

        return UserReviewSummary::props($this, $stateMachine->state(), $stateMachine->groups());
    }

    public function decide(UserReviewDecision $decision, ?string $message, WorkflowStageContext $context, CraftUser $reviewer): WorkflowStageResult
    {
        return $this->stateMachine($context)->decide($decision, $message, $reviewer);
    }

    public function requestReviewAgain(WorkflowStageContext $context, CraftUser $requester): WorkflowStageResult
    {
        return $this->stateMachine($context)->requestReviewAgain($requester);
    }

    public function contentChanged(WorkflowStageContext $context): ?WorkflowStageResult
    {
        return $this->stateMachine($context)->contentChanged();
    }

    /** @return Collection<int, User> */
    public function outstandingReviewers(WorkflowStageContext $context): Collection
    {
        return $this->stateMachine($context)->outstandingReviewers();
    }

    /**
     * Returns whether a user is one of the stage’s reviewers, as its details list them.
     */
    public function isReviewer(WorkflowStageContext $context, CraftUser $user): bool
    {
        $userId = $user->getCraftUserId();

        return $userId !== null && $this->stateMachine($context)->state()->reviewers->contains('id', $userId);
    }

    /**
     * Returns whether a user’s approval of the stage stands, including one carried from an earlier stage.
     */
    public function hasApproved(WorkflowStageContext $context, CraftUser $user): bool
    {
        $userId = $user->getCraftUserId();

        return $userId !== null && $this->stateMachine($context)->state()->effectiveApprovalIds->contains($userId);
    }

    /**
     * Returns the stage’s payload without a user’s approval, including one carried from an earlier stage, so they
     * have to approve it again.
     *
     * @return array<string, mixed>
     */
    public function revokeApproval(WorkflowStageContext $context, CraftUser $user): array
    {
        $userId = $user->getCraftUserId();
        $payload = $context->payload;
        $payload['decisions'] = collect(UserReviewDecisions::fromPayload($payload)->all)
            ->reject(fn (UserReviewDecisionData $decision): bool => $decision->reviewerId === $userId)
            ->map(fn (UserReviewDecisionData $decision): array => $decision->toArray())
            ->values()
            ->all();
        $payload['revokedApprovalIds'] = UserReviewDecisions::revokedApprovalIds($payload)
            ->push($userId)
            ->unique()
            ->values()
            ->all();

        return $payload;
    }

    private function stateMachine(WorkflowStageContext $context): UserReviewStateMachine
    {
        return UserReviewStateMachine::for($this, $context);
    }
}
