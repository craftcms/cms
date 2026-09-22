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
use CraftCms\Cms\Form\Nodes\Group;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Workflow\Data\WorkflowStageContext;
use CraftCms\Cms\Workflow\Data\WorkflowStageResult;
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
            Group::make('user-review-settings', [
                Field::make(t('Reviewer groups'), UserGroupSelect::make('userGroups'))
                    ->required(),
                Group::make('user-review-approval-requirement', [
                    Field::make(t('Minimum'), Number::make('approvalsRequired')->min(1)->size(3))
                        ->labelSrOnly()
                        ->required(),
                    Field::make(t('Mode'), Choice::make('approvalMode')->options([
                        ['label' => t('Total'), 'value' => UserReviewApprovalMode::Total->value],
                        ['label' => t('Per group'), 'value' => UserReviewApprovalMode::PerGroup->value],
                    ]))
                        ->labelSrOnly()
                        ->required(),
                ])
                    ->label(t('Approvals required'))
                    ->instructions(t('The minimum number of approvals required in total or per reviewer group.'))
                    ->asField(),
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

    private function stateMachine(WorkflowStageContext $context): UserReviewStateMachine
    {
        return UserReviewStateMachine::for($this, $context);
    }
}
