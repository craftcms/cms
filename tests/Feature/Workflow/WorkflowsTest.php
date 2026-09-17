<?php

declare(strict_types=1);

use CraftCms\Cms\Activity\Data\ActivityActor;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Controllers\Settings\WorkflowsController;
use CraftCms\Cms\Http\Controllers\Workflows\UserReviewController;
use CraftCms\Cms\Http\ViewModels\WorkflowEditViewModel;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Models\UserGroup;
use CraftCms\Cms\Workflow\Activity\WorkflowActivityEvent;
use CraftCms\Cms\Workflow\Data\WorkflowStageContext;
use CraftCms\Cms\Workflow\Data\WorkflowStageData;
use CraftCms\Cms\Workflow\Data\WorkflowStageResult;
use CraftCms\Cms\Workflow\Enums\WorkflowActivityType;
use CraftCms\Cms\Workflow\Enums\WorkflowStageStatus;
use CraftCms\Cms\Workflow\Enums\WorkflowStatus;
use CraftCms\Cms\Workflow\Enums\WorkflowTransition;
use CraftCms\Cms\Workflow\Events\WorkflowCommented;
use CraftCms\Cms\Workflow\Events\WorkflowTransitioned;
use CraftCms\Cms\Workflow\Exceptions\WorkflowException;
use CraftCms\Cms\Workflow\Models\Workflow;
use CraftCms\Cms\Workflow\Models\WorkflowRun;
use CraftCms\Cms\Workflow\Stages\MissingWorkflowStage;
use CraftCms\Cms\Workflow\Stages\WorkflowStage as BaseWorkflowStage;
use CraftCms\Cms\Workflow\UserReview\UserReviewDecision;
use CraftCms\Cms\Workflow\UserReview\UserReviewStage;
use CraftCms\Cms\Workflow\Workflows;
use CraftCms\Cms\Workflow\WorkflowStageTypes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia;
use Workbench\App\Workflow\AutomaticApprovalStage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

class TestAutomatedWorkflowStage extends BaseWorkflowStage
{
    public string $outcome = 'approved';

    public function getRules(): array
    {
        return [
            'outcome' => ['required', 'in:approved,pending,failed'],
        ];
    }

    public function evaluate(WorkflowStageContext $context): WorkflowStageResult
    {
        $payload = [
            ...$context->payload,
            'evaluations' => (int) ($context->payload['evaluations'] ?? 0) + 1,
        ];

        return new WorkflowStageResult(
            WorkflowStageStatus::from($this->outcome),
            "Automated check {$this->outcome}",
            $payload,
        );
    }
}

beforeEach(function () {
    Edition::set(Edition::Pro);

    $this->author = UserModel::factory()->admin()->createElement(['fullName' => 'Draft Author']);
    $this->reviewers = collect([
        UserModel::factory()->admin()->createElement(['fullName' => 'Reviewer One']),
        UserModel::factory()->admin()->createElement(['fullName' => 'Reviewer Two']),
        UserModel::factory()->admin()->createElement(['fullName' => 'Reviewer Three']),
    ]);
    $this->entry = EntryModel::factory()->createElement(['title' => 'Canonical title']);
    $this->draft = app(Drafts::class)->createDraft($this->entry, $this->author->id, name: 'Campaign draft');
    $this->draft->title = 'Reviewed title';
    actingAs($this->author);
    expect(Elements::saveElement($this->draft, updateSearchIndex: false))->toBeTrue();

    $this->workflows = app(Workflows::class);
});

it('registers and hydrates workflow stage components', function () {
    $types = app(WorkflowStageTypes::class);
    $types->register(TestAutomatedWorkflowStage::class);
    $stage = new WorkflowStageData(
        uid: Str::uuid7()->toString(),
        name: 'Automated review',
        type: TestAutomatedWorkflowStage::class,
        settings: ['outcome' => 'pending'],
    );

    expect($types->types())->toContain(UserReviewStage::class, TestAutomatedWorkflowStage::class)
        ->and($stage->component())->toBeInstanceOf(TestAutomatedWorkflowStage::class)
        ->and($stage->component()->getSettings())->toBe(['outcome' => 'pending']);
});

it('preserves settings for unavailable stage types', function () {
    $stage = new WorkflowStageData(
        uid: Str::uuid7()->toString(),
        name: 'Missing review',
        type: 'plugin\\MissingStage',
        settings: ['endpoint' => 'https://example.test/review'],
    );

    expect($stage->component())->toBeInstanceOf(MissingWorkflowStage::class)
        ->and($stage->component()->getSettings())->toBe(['endpoint' => 'https://example.test/review']);
});

it('renders registered stage types and inline settings forms', function () {
    $payload = app(WorkflowEditViewModel::class, [
        'workflow' => new Workflow,
        'readOnly' => false,
    ])->toArray();
    $userReview = collect($payload['stageTypes'])->firstWhere('type', UserReviewStage::class);
    $automaticApproval = collect($payload['stageTypes'])->firstWhere('type', AutomaticApprovalStage::class);

    expect($userReview['label'])->toBe('User review')
        ->and($userReview['settings'])->toBe([
            'approvalsRequired' => 1,
            'userGroups' => [],
        ])
        ->and($userReview['settingsForm'])->not->toBeNull()
        ->and($userReview['settingsForm']->nodes[0]->control->component)->toBe('craft:user-group-select')
        ->and($automaticApproval)->toMatchArray([
            'label' => 'Automatic approval',
            'settings' => [],
            'settingsForm' => null,
        ]);
});

it('runs a workbench-provided stage type without core registration', function () {
    workflowFor($this->entry, [[
        'name' => 'External approval',
        'type' => AutomaticApprovalStage::class,
        'settings' => [],
    ]]);

    $run = submitAs($this->workflows, $this->draft, $this->author);

    expect($run->status)->toBe(WorkflowStatus::Approved)
        ->and($run->currentStageResult)->toBe('Automatically approved');
});

it('saves a settings-free workflow stage through project config', function () {
    postJson(action([WorkflowsController::class, 'store']), [
        'name' => 'Automated workflow',
        'stages' => [[
            'uid' => Str::uuid7()->toString(),
            'name' => 'External approval',
            'type' => AutomaticApprovalStage::class,
            'settings' => [],
        ]],
    ])->assertOk();

    expect(Workflow::query()->where('name', 'Automated workflow')->firstOrFail()
        ->stages->sole()->settings)->toBe([]);
});

it('persists typed stages and assigns workflows from sections', function () {
    $group = reviewerGroup([$this->reviewers[0]]);
    $stageUid = Str::uuid7()->toString();

    postJson(action([WorkflowsController::class, 'store']), [
        'name' => 'Editorial workflow',
        'stages' => [[
            'uid' => $stageUid,
            'name' => 'Editorial',
            'type' => UserReviewStage::class,
            'settings' => [
                'approvalsRequired' => 1,
                'userGroups' => [$group->uid],
            ],
        ]],
    ])->assertOk();

    $workflow = Workflow::query()->where('name', 'Editorial workflow')->firstOrFail();
    assignWorkflow($this->entry, $workflow);
    $stage = $workflow->stages->sole();

    expect($stage)->toBeInstanceOf(WorkflowStageData::class)
        ->and($stage)->toMatchObject([
            'uid' => $stageUid,
            'name' => 'Editorial',
            'type' => UserReviewStage::class,
        ])->and($stage->settings)->toBe([
            'approvalsRequired' => 1,
            'userGroups' => [$group->uid],
        ])->and($this->draft->workflow()?->is($workflow))->toBeTrue();
});

it('validates settings with the registered stage type', function () {
    app(WorkflowStageTypes::class)->register(TestAutomatedWorkflowStage::class);

    postJson(action([WorkflowsController::class, 'store']), [
        'name' => 'Automated workflow',
        'stages' => [[
            'uid' => Str::uuid7()->toString(),
            'name' => 'Automated review',
            'type' => TestAutomatedWorkflowStage::class,
            'settings' => ['outcome' => 'unexpected'],
        ]],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('stages.0.settings.outcome');
});

it('cascades approved automated stages', function () {
    app(WorkflowStageTypes::class)->register(TestAutomatedWorkflowStage::class);
    workflowFor($this->entry, [
        automatedStage('lint', 'approved'),
        automatedStage('policy', 'approved'),
    ]);

    $run = submitAs($this->workflows, $this->draft, $this->author);

    expect($run->status)->toBe(WorkflowStatus::Approved)
        ->and(workflowEvents($this->entry, WorkflowTransition::StageApproved))->toHaveCount(2);
});

it('keeps pending automated stages pending with their status message', function () {
    app(WorkflowStageTypes::class)->register(TestAutomatedWorkflowStage::class);
    workflowFor($this->entry, [automatedStage('External check', 'pending')]);

    $run = submitAs($this->workflows, $this->draft, $this->author);

    expect($run->status)->toBe(WorkflowStatus::Pending)
        ->and($run->currentStageResult)->toBe('Automated check pending')
        ->and($this->workflows->reviewData($this->draft, $this->author)->actionComponent)->toBeNull();
});

it('completes a pending automated stage and advances the workflow', function () {
    app(WorkflowStageTypes::class)->register(TestAutomatedWorkflowStage::class);
    $workflow = workflowFor($this->entry, [automatedStage('External check', 'pending')]);
    $run = submitAs($this->workflows, $this->draft, $this->author);

    $completed = $this->workflows->reportStageResult(
        runId: $run->id,
        stageUid: $workflow->stages->sole()->uid,
        result: new WorkflowStageResult(
            WorkflowStageStatus::Approved,
            'Automated check approved',
            ['evaluations' => 1, 'summary' => 'No issues found.'],
        ),
    );

    expect($completed->status)->toBe(WorkflowStatus::Approved)
        ->and($completed->currentStageResult)->toBe('Automated check approved')
        ->and($completed->payload[$workflow->stages->sole()->uid])->toBe([
            'evaluations' => 1,
            'summary' => 'No issues found.',
        ]);

    $event = workflowEvents($this->entry, WorkflowTransition::StageApproved)->sole();
    expect($event->actorType)->toBe(ActivityActor::TYPE_SYSTEM)
        ->and(WorkflowActivityEvent::format($event))->toContain('External check');

    $stageData = collect($this->workflows->reviewData($this->draft, $this->author)->runs)
        ->firstWhere('current')->stages[0];
    expect($stageData->events)->toHaveCount(1)
        ->and($stageData->icon)->toBe('check')
        ->and($stageData->events[0]->actor['label'])->toBe('Craft CMS')
        ->and($stageData->events[0]->decision)->toBe('approved')
        ->and($stageData->events[0]->icon)->toBe('check')
        ->and($stageData->events[0]->description)->toBe('approved')
        ->and($stageData->events[0]->noteHtml)->toContain('Automated check approved');
});

it('records a failed automatic stage with its result message', function () {
    app(WorkflowStageTypes::class)->register(TestAutomatedWorkflowStage::class);
    workflowFor($this->entry, [automatedStage('Policy check', 'failed')]);

    $run = submitAs($this->workflows, $this->draft, $this->author);
    $event = workflowEvents($this->entry, WorkflowTransition::StageFailed)->sole();

    expect($run->status)->toBe(WorkflowStatus::Failed)
        ->and($event->actorType)->toBe(ActivityActor::TYPE_SYSTEM);

    $stageData = collect($this->workflows->reviewData($this->draft, $this->author)->runs)
        ->firstWhere('current')->stages[0];
    expect($stageData->events[0]->decision)->toBe('failed')
        ->and($stageData->events[0]->noteHtml)->toContain('Automated check failed');
});

it('ignores stale automated stage completions', function () {
    app(WorkflowStageTypes::class)->register(TestAutomatedWorkflowStage::class);
    $workflow = workflowFor($this->entry, [automatedStage('External check', 'pending')]);
    $run = submitAs($this->workflows, $this->draft, $this->author);
    $result = new WorkflowStageResult(
        WorkflowStageStatus::Approved,
        'Automated check approved',
        ['evaluations' => 1, 'summary' => 'No issues found.'],
    );

    $completed = $this->workflows->reportStageResult(
        runId: $run->id,
        stageUid: 'stale-stage',
        result: $result,
    );

    expect($completed->status)->toBe(WorkflowStatus::Pending)
        ->and($completed->currentStageResult)->toBe('Automated check pending');

    $this->workflows->contentChanged($this->draft);
    $completed = $this->workflows->reportStageResult(
        runId: $run->id,
        stageUid: $workflow->stages->sole()->uid,
        result: $result,
    );

    expect($completed->status)->toBe(WorkflowStatus::Invalidated);

    expect($this->workflows->reportStageResult(
        runId: PHP_INT_MAX,
        stageUid: 'missing',
        result: $result,
    ))->toBeNull();
});

it('requires live eligible reviewers and excludes the requester', function () {
    $group = reviewerGroup([$this->author, $this->reviewers[0]]);
    $workflow = workflowFor($this->entry, [userReviewStage('Review', $group, approvals: 1)]);
    $run = submitAs($this->workflows, $this->draft, $this->author);
    $stage = $workflow->stages->sole();

    expect($this->workflows->reviewData($this->draft, $this->author)->actionProps['canReview'])->toBeFalse();

    $group->users()->detach($this->reviewers[0]->id);
    expect(fn () => reviewStageAs(
        $this->workflows,
        $this->reviewers[0],
        $run,
        $stage,
        UserReviewDecision::Approved,
    ))->toThrow(WorkflowException::class, 'not available');

    $group->users()->attach($this->reviewers[1]->id);
    $run = reviewStageAs(
        $this->workflows,
        $this->reviewers[1],
        $run,
        $stage,
        UserReviewDecision::Approved,
    );

    expect($run->status)->toBe(WorkflowStatus::Approved);
});

it('fails on a change request and preserves history when resubmitted', function () {
    $group = reviewerGroup([$this->reviewers[0]]);
    $workflow = workflowFor($this->entry, [userReviewStage('Review', $group)]);
    $stage = $workflow->stages->sole();
    $firstRun = submitAs($this->workflows, $this->draft, $this->author, 'Ready for review');

    $firstRun = reviewStageAs(
        $this->workflows,
        $this->reviewers[0],
        $firstRun,
        $stage,
        UserReviewDecision::Rejected,
        'Clarify the claim.',
    );
    $this->workflows->addComment($this->draft, $firstRun->id, $stage->uid, 'I will revise it.');
    submitAs($this->workflows, $this->draft, $this->author, 'Revised.');
    $review = $this->workflows->reviewData($this->draft, $this->author);

    expect($firstRun->status)->toBe(WorkflowStatus::Failed)
        ->and($review->runs)->toHaveCount(2)
        ->and(collect($review->runs)->last()->stages[0]->icon)->toBe('xmark')
        ->and(collect($review->runs)->last()->stages[0]->events)->toHaveCount(2)
        ->and(collect($review->runs)->last()->stages[0]->events[0]->icon)->toBe('xmark')
        ->and(collect($review->runs)->last()->stages[0]->events[0]->description)->toBe('requested changes')
        ->and(collect($review->runs)->last()->stages[0]->events[1]->icon)->toBe('comment')
        ->and(collect($review->runs)->last()->stages[0]->events[1]->description)->toBe('commented.')
        ->and(collect($review->runs)->first()->submission->icon)->toBe('clipboard-list-check')
        ->and(collect($review->runs)->first()->submission->description)->toBe('requested review');
});

it('presents workflow activity as a grouped review and snapshots the workflow definition', function () {
    $group = reviewerGroup([$this->reviewers[0]]);
    $workflow = workflowFor($this->entry, [userReviewStage('Editorial review', $group)]);
    $stage = $workflow->stages->sole();
    $run = submitAs($this->workflows, $this->draft, $this->author, 'Ready for review.');

    reviewStageAs(
        $this->workflows,
        $this->reviewers[0],
        $run,
        $stage,
        UserReviewDecision::Approved,
        'Approved.',
    );

    $review = $this->workflows->reviewData($this->draft, $this->author);
    $presentedRun = collect($review->runs)->firstWhere('current');

    expect($presentedRun->submission->noteHtml)->toContain('Ready for review.')
        ->and($presentedRun->stages[0]->events)->toHaveCount(1)
        ->and($presentedRun->stages[0]->events[0]->decision)->toBe('approved');

    $workflow->stages = $workflow->stages->map(fn (WorkflowStageData $workflowStage): array => [
        'uid' => $workflowStage->uid,
        'name' => $workflowStage->uid === $stage->uid ? 'Renamed review' : $workflowStage->name,
        'type' => $workflowStage->type,
        'settings' => $workflowStage->settings,
    ])->all();
    $workflow->save();
    $review = $this->workflows->reviewData($this->draft, $this->author);

    expect(collect($review->runs)->firstWhere('current')->stages[0]->name)->toBe('Editorial review')
        ->and($run->fresh()->status)->toBe(WorkflowStatus::Approved);
});

it('lets any viewer comment but only the stage offers review actions', function () {
    $group = reviewerGroup([$this->reviewers[0]]);
    $workflow = workflowFor($this->entry, [userReviewStage('Review', $group)]);
    $stage = $workflow->stages->sole();
    $run = submitAs($this->workflows, $this->draft, $this->author);
    $viewer = $this->reviewers[1];

    $review = $this->workflows->reviewData($this->draft, $viewer);
    expect($review->canComment)->toBeTrue()
        ->and($review->actionProps['canReview'])->toBeFalse();

    Event::fake([WorkflowCommented::class, WorkflowTransitioned::class]);
    actAs($viewer, fn () => $this->workflows->addComment($this->draft, $run->id, $stage->uid, 'A useful note.'));
    expect(workflowEvents($this->entry, WorkflowActivityType::Comment))->toHaveCount(1);
    Event::assertDispatched(WorkflowCommented::class);
    Event::assertNotDispatched(WorkflowTransitioned::class);
});

it('invalidates pending and approved runs when publishable content changes', function () {
    $group = reviewerGroup([$this->reviewers[0]]);
    $workflow = workflowFor($this->entry, [userReviewStage('Review', $group)]);
    $stage = $workflow->stages->sole();
    $pending = submitAs($this->workflows, $this->draft, $this->author);

    $this->draft->title = 'Changed during review';
    expect(Elements::saveElement($this->draft, updateSearchIndex: false))->toBeTrue();
    expect($pending->fresh()->status)->toBe(WorkflowStatus::Invalidated);

    $approved = submitAs($this->workflows, $this->draft, $this->author);
    reviewStageAs(
        $this->workflows,
        $this->reviewers[0],
        $approved,
        $stage,
        UserReviewDecision::Approved,
    );
    $this->draft->title = 'Changed after approval';
    expect(Elements::saveElement($this->draft, updateSearchIndex: false))->toBeTrue();

    expect($approved->fresh()->status)->toBe(WorkflowStatus::Invalidated);
});

it('invalidates an approved run when its workflow assignment changes', function () {
    $group = reviewerGroup([$this->reviewers[0]]);
    $workflow = workflowFor($this->entry, [userReviewStage('Review', $group)]);
    $run = submitAs($this->workflows, $this->draft, $this->author);
    reviewStageAs(
        $this->workflows,
        $this->reviewers[0],
        $run,
        $workflow->stages->sole(),
        UserReviewDecision::Approved,
    );

    Section::query()->whereKey($this->entry->sectionId)->update(['workflowId' => null]);
    Sections::refreshSections();
    $this->workflows->invalidateSectionRuns($this->entry->sectionId);

    expect($run->fresh()->status)->toBe(WorkflowStatus::Invalidated);
});

it('reloads the draft after locking it for review submission', function () {
    workflowFor($this->entry, [automatedStage('Review', 'pending')]);
    DB::table(Table::DRAFTS)->where('id', $this->draft->draftId)->update(['provisional' => true]);

    expect(fn () => submitAs($this->workflows, $this->draft, $this->author))
        ->toThrow(WorkflowException::class, 'Only saved named drafts');
});

it('does not submit disabled drafts for review', function () {
    workflowFor($this->entry, [automatedStage('Review', 'pending')]);
    $this->draft->enabled = false;
    expect(Elements::saveElement($this->draft, updateSearchIndex: false))->toBeTrue();

    expect(fn () => submitAs($this->workflows, $this->draft, $this->author))
        ->toThrow(WorkflowException::class, 'Only enabled drafts');
});

it('gates only draft application and permits an admin override without a reason', function () {
    $group = reviewerGroup([$this->reviewers[0]]);
    workflowFor($this->entry, [userReviewStage('Review', $group)]);
    $applied = false;

    expect(fn () => $this->workflows->applyDraft(
        $this->draft,
        $this->author,
        null,
        null,
        function (ElementInterface $draft) use (&$applied): ElementInterface {
            $applied = true;

            return $draft;
        },
    ))->toThrow(WorkflowException::class, 'must be approved')
        ->and($applied)->toBeFalse();

    $run = submitAs($this->workflows, $this->draft, $this->author);

    expect(fn () => $this->workflows->applyDraft(
        $this->draft,
        $this->author,
        $run->id,
        $run->currentStage,
        function (ElementInterface $draft) use (&$applied): ElementInterface {
            $applied = true;

            return $draft;
        },
    ))->toThrow(WorkflowException::class, 'must be approved')
        ->and($applied)->toBeFalse();

    $run = $this->workflows->overrideApproval($this->draft, $run->id);
    $this->workflows->applyDraft(
        $this->draft,
        $this->author,
        $run->id,
        $run->currentStage,
        function (ElementInterface $draft) use (&$applied): ElementInterface {
            $applied = true;

            return $draft;
        },
    );

    expect($applied)->toBeTrue()
        ->and($run->fresh()->status)->toBe(WorkflowStatus::Published)
        ->and(workflowEvents($this->entry, WorkflowTransition::Publish))->toHaveCount(1);
});

it('does not apply an approved draft that became outdated', function () {
    $group = reviewerGroup([$this->reviewers[0]]);
    workflowFor($this->entry, [userReviewStage('Review', $group)]);
    $run = submitAs($this->workflows, $this->draft, $this->author);
    $run = $this->workflows->overrideApproval($this->draft, $run->id);
    $this->draft->getCanonical()->dateUpdated = now()->addDay();
    expect(ElementHelper::isOutdated($this->draft))->toBeTrue();
    $applied = false;

    expect(fn () => $this->workflows->applyDraft(
        $this->draft,
        $this->author,
        $run->id,
        $run->currentStage,
        function (ElementInterface $draft) use (&$applied): ElementInterface {
            $applied = true;

            return $draft;
        },
    ))->toThrow(WorkflowException::class, 'changed since it was approved')
        ->and($applied)->toBeFalse()
        ->and($run->fresh()->status)->toBe(WorkflowStatus::Approved);
});

it('exposes stage components and safely handles user review decisions', function () {
    $group = reviewerGroup([$this->reviewers[0]]);
    $workflow = workflowFor($this->entry, [userReviewStage('Review', $group)]);
    $stage = $workflow->stages->sole();
    $run = submitAs($this->workflows, $this->draft, $this->author);
    actingAs($this->reviewers[0]);

    $review = $this->workflows->reviewData($this->draft, $this->reviewers[0]);
    expect($review->actionComponent)->toBe('craft:user-review-workflow-stage-actions')
        ->and($review->showDefaultActions)->toBeFalse()
        ->and($review->actionProps)->toBe(['canReview' => true]);

    postJson(action([UserReviewController::class, 'approve'], [
        'workflowRun' => $run,
        'stage' => $stage->uid,
    ]), elementIdentity($this->entry, $this->draft, ['message' => 'Looks good.']))
        ->assertOk()
        ->assertJsonPath('workflowReview.status', 'approved')
        ->assertJsonPath('workflowReview.actionComponent', null)
        ->assertJsonPath('workflowReview.showDefaultActions', true)
        ->assertJsonPath('workflowReview.actionProps', [])
        ->assertJsonPath('editorActions.buttons.0.disabled', false)
        ->assertJsonPath('editorActions.buttons.0.params.workflowRunId', $run->id);

    $event = workflowEvents($this->entry, WorkflowTransition::Approve)->sole();
    expect($event->actorId)->toBe($this->reviewers[0]->id)
        ->and($this->author->notifications()->latest()->firstOrFail()->data['title'])->toBe('Draft approved');
});

it('blocks workflow deletion while a section references it', function () {
    $workflow = workflowFor($this->entry, [automatedStage('Review', 'pending')]);

    delete(action([WorkflowsController::class, 'destroy'], $workflow))
        ->assertSessionHas('error', 'This workflow cannot be deleted while it is assigned to a section.');

    expect($workflow->fresh())->not->toBeNull();
});

it('links review notifications to the workflow panel', function () {
    $group = reviewerGroup([$this->reviewers[0]]);
    workflowFor($this->entry, [userReviewStage('Review', $group)]);
    submitAs($this->workflows, $this->draft, $this->author);

    $notification = $this->reviewers[0]->notifications()->latest()->firstOrFail();
    expect($notification->data['title'])->toBe('Review requested')
        ->and($notification->data['url'])->toEndWith('#workflow');
});

it('requests user review after an asynchronous stage advances', function () {
    app(WorkflowStageTypes::class)->register(TestAutomatedWorkflowStage::class);
    $group = reviewerGroup([$this->reviewers[0]]);
    $workflow = workflowFor($this->entry, [
        automatedStage('External check', 'pending'),
        userReviewStage('Editorial review', $group),
    ]);
    $run = submitAs($this->workflows, $this->draft, $this->author);

    expect($this->reviewers[0]->notifications()->count())->toBe(0);

    $this->workflows->reportStageResult(
        runId: $run->id,
        stageUid: $workflow->stages->first()->uid,
        result: new WorkflowStageResult(
            WorkflowStageStatus::Approved,
            'Automated check approved',
            ['evaluations' => 1],
        ),
    );

    expect($this->reviewers[0]->notifications()->latest()->firstOrFail()->data['title'])->toBe('Review requested');
});

it('shows workflow settings routes with separate store and update targets', function () {
    $workflow = workflowFor($this->entry, [automatedStage('Review', 'pending')]);

    get(action([WorkflowsController::class, 'create']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('submit.method', 'post')
            ->where('submit.url', action([WorkflowsController::class, 'store']))
            ->etc());

    get(action([WorkflowsController::class, 'edit'], $workflow))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('submit.method', 'patch')
            ->where('submit.url', action([WorkflowsController::class, 'update'], $workflow))
            ->etc());
});

/** @param list<array{name: string, type: string, settings: array<string, mixed>}> $stages */
function workflowFor(Entry $entry, array $stages): Workflow
{
    $workflow = Workflow::query()->create([
        'name' => 'Editorial workflow',
        'uid' => Str::uuid7()->toString(),
        'stages' => array_map(fn (array $stage): array => [
            ...$stage,
            'uid' => Str::uuid7()->toString(),
        ], $stages),
    ]);

    assignWorkflow($entry, $workflow);

    return $workflow->refresh();
}

function assignWorkflow(Entry $entry, Workflow $workflow): void
{
    Section::query()->whereKey($entry->sectionId)->update(['workflowId' => $workflow->id]);
    Sections::refreshSections();
}

/** @return array{name: string, type: string, settings: array<string, mixed>} */
function userReviewStage(string $name, UserGroup $group, int $approvals = 1): array
{
    return [
        'name' => $name,
        'type' => UserReviewStage::class,
        'settings' => [
            'approvalsRequired' => $approvals,
            'userGroups' => [$group->uid],
        ],
    ];
}

/** @return array{name: string, type: string, settings: array<string, mixed>} */
function automatedStage(string $name, string $outcome): array
{
    return [
        'name' => $name,
        'type' => TestAutomatedWorkflowStage::class,
        'settings' => ['outcome' => $outcome],
    ];
}

function submitAs(Workflows $workflows, Entry $draft, User $author, ?string $note = null): WorkflowRun
{
    return actAs($author, fn () => $workflows->submitForReview($draft, $note));
}

function reviewStageAs(
    Workflows $workflows,
    User $reviewer,
    WorkflowRun $run,
    WorkflowStageData $stage,
    UserReviewDecision $decision,
    ?string $message = null,
): WorkflowRun {
    return actAs($reviewer, function () use ($workflows, $reviewer, $run, $stage, $decision, $message): WorkflowRun {
        $transition = $decision === UserReviewDecision::Approved
            ? WorkflowTransition::Approve
            : WorkflowTransition::Reject;

        return $workflows->reportStageResult(
            runId: $run->id,
            stageUid: $stage->uid,
            result: function (WorkflowStageContext $context) use ($reviewer, $decision, $message): WorkflowStageResult {
                $component = $context->stage->component();
                expect($component)->toBeInstanceOf(UserReviewStage::class);

                return $component->decide($decision, $message, $context, $reviewer);
            },
            actor: $reviewer,
            activityTransition: $transition,
            activityNote: trim((string) $message) ?: null,
        );
    });
}

/**
 * @template TResult
 *
 * @param  Closure(): TResult  $callback
 * @return TResult
 */
function actAs(User $actor, Closure $callback): mixed
{
    $previousUser = Auth::user();
    Auth::guard()->setUser($actor);

    try {
        return $callback();
    } finally {
        if ($previousUser === null) {
            Auth::logout();
        } else {
            Auth::guard()->setUser($previousUser);
        }
    }
}

/** @param list<User> $reviewers */
function reviewerGroup(array $reviewers): UserGroup
{
    $group = UserGroup::factory()->create();
    $group->users()->sync(collect($reviewers)->pluck('id'));

    return $group;
}

/** @return array<string, mixed> */
function elementIdentity(Entry $entry, Entry $draft, array $extra = []): array
{
    return [
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'siteId' => $draft->siteId,
        'draftId' => $draft->draftId,
        ...$extra,
    ];
}

/** @return Collection<int, ActivityEvent> */
function workflowEvents(Entry $entry, WorkflowActivityType|WorkflowTransition $type): Collection
{
    return ActivityEvent::query()
        ->subject(ActivitySubject::fromElement($entry))
        ->eventTypes(WorkflowActivityEvent::class)
        ->get()
        ->filter(fn (ActivityEvent $event): bool => $event->data['type'] === $type->value)
        ->values();
}
