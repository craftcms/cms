<?php

declare(strict_types=1);

use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
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
use CraftCms\Cms\Workflow\Enums\WorkflowStatus;
use CraftCms\Cms\Workflow\Enums\WorkflowTransition;
use CraftCms\Cms\Workflow\Exceptions\WorkflowException;
use CraftCms\Cms\Workflow\Models\Workflow;
use CraftCms\Cms\Workflow\Models\WorkflowRun;
use CraftCms\Cms\Workflow\UserReview\UserReviewApprovalMode;
use CraftCms\Cms\Workflow\UserReview\UserReviewDecision;
use CraftCms\Cms\Workflow\UserReview\UserReviewStage;
use CraftCms\Cms\Workflow\Workflows;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

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

it('carries eligible approvals into a later user review stage and completes it without a duplicate event', function () {
    $group = userReviewGroup([$this->reviewers[0]]);
    $workflow = userReviewWorkflow($this->entry, [
        makeUserReviewStage('Editorial review', $group),
        makeUserReviewStage('Legal review', $group),
    ]);
    $run = submitUserReview($this->workflows, $this->draft, $this->author);

    $run = decideUserReview(
        $this->workflows,
        $this->reviewers[0],
        $run,
        $workflow->stages->first(),
        UserReviewDecision::Approved,
    );

    expect($run->status)->toBe(WorkflowStatus::Approved)
        ->and(userReviewEvents($this->entry, WorkflowTransition::Approve))->toHaveCount(1)
        ->and(userReviewEvents($this->entry, WorkflowTransition::StageApproved))->toHaveCount(0)
        ->and(collect($this->workflows->reviewData($this->draft, $this->author)->runs)->firstWhere('current')->stages[0]->summaryProps['approvedReviewers'])->toBe([
            ['name' => $this->reviewers[0]->name],
        ])
        ->and(collect($this->workflows->reviewData($this->draft, $this->author)->runs)->firstWhere('current')->stages[1]->summaryProps['carriedReviewers'])->toBe([
            ['name' => $this->reviewers[0]->name],
        ])
        ->and($this->reviewers[0]->notifications()->count())->toBe(1);
});

it('applies a draft approved through a carried approval', function () {
    $group = userReviewGroup([$this->reviewers[0]]);
    $workflow = userReviewWorkflow($this->entry, [
        makeUserReviewStage('Editorial review', $group),
        makeUserReviewStage('Legal review', $group),
    ]);
    $run = submitUserReview($this->workflows, $this->draft, $this->author);

    decideUserReview(
        $this->workflows,
        $this->reviewers[0],
        $run,
        $workflow->stages->first(),
        UserReviewDecision::Approved,
    );

    postJson(cp_url('actions/elements/apply-draft'), [
        'elementType' => Entry::class,
        'elementId' => $this->entry->id,
        'siteId' => $this->draft->siteId,
        'draftId' => $this->draft->draftId,
        'workflowRunId' => $run->id,
        'workflowCurrentStage' => 1,
    ])->assertOk();

    expect($run->fresh()->status)->toBe(WorkflowStatus::Published);
});

it('excludes carried reviewers from later review actions and notifications', function () {
    $firstGroup = userReviewGroup([$this->reviewers[0]]);
    $secondGroup = userReviewGroup([$this->reviewers[0], $this->reviewers[1]]);
    $workflow = userReviewWorkflow($this->entry, [
        makeUserReviewStage('Editorial review', $firstGroup),
        makeUserReviewStage('Legal review', $secondGroup, approvals: 2),
    ]);
    $run = submitUserReview($this->workflows, $this->draft, $this->author);

    $run = decideUserReview(
        $this->workflows,
        $this->reviewers[0],
        $run,
        $workflow->stages->first(),
        UserReviewDecision::Approved,
    );

    expect($run->status)->toBe(WorkflowStatus::Pending)
        ->and($this->workflows->reviewData($this->draft, $this->reviewers[0])->actionProps['canReview'])->toBeFalse()
        ->and($this->reviewers[0]->notifications()->count())->toBe(1)
        ->and($this->reviewers[1]->notifications()->count())->toBe(1)
        ->and(fn () => decideUserReview(
            $this->workflows,
            $this->reviewers[0],
            $run,
            $workflow->stages->last(),
            UserReviewDecision::Approved,
        ))->toThrow(WorkflowException::class, 'not available');
});

it('carries approvals only when the reviewer remains eligible for the destination stage', function () {
    $firstGroup = userReviewGroup([$this->reviewers[0]]);
    $secondGroup = userReviewGroup([$this->reviewers[1]]);
    $workflow = userReviewWorkflow($this->entry, [
        makeUserReviewStage('Editorial review', $firstGroup),
        makeUserReviewStage('Legal review', $secondGroup),
    ]);
    $run = submitUserReview($this->workflows, $this->draft, $this->author);

    $run = decideUserReview(
        $this->workflows,
        $this->reviewers[0],
        $run,
        $workflow->stages->first(),
        UserReviewDecision::Approved,
    );

    $review = $this->workflows->reviewData($this->draft, $this->reviewers[1]);
    expect($run->status)->toBe(WorkflowStatus::Pending)
        ->and($review->actionProps['canReview'])->toBeTrue()
        ->and(collect($review->runs)->firstWhere('current')->stages[1]->summaryProps['approvals'])->toBe(0);
});

it('does not carry approvals into a manually restarted workflow run', function () {
    $editorial = userReviewGroup([$this->reviewers[0]]);
    $legal = userReviewGroup([$this->reviewers[1]]);
    $workflow = userReviewWorkflow($this->entry, [
        makeUserReviewStage('Editorial review', $editorial),
        makeUserReviewStage('Legal review', $legal),
    ]);
    $firstRun = submitUserReview($this->workflows, $this->draft, $this->author);

    decideUserReview(
        $this->workflows,
        $this->reviewers[0],
        $firstRun,
        $workflow->stages->first(),
        UserReviewDecision::Approved,
    );
    expect($firstRun->fresh()->currentStage)->toBe(1);

    $secondRun = actAsUserReview(
        $this->author,
        fn (): WorkflowRun => $this->workflows->restartWorkflow($this->draft, $firstRun->id),
    );

    $review = $this->workflows->reviewData($this->draft, $this->reviewers[0]);
    expect($firstRun->fresh()->status)->toBe(WorkflowStatus::Invalidated)
        ->and($secondRun->status)->toBe(WorkflowStatus::Pending)
        ->and($secondRun->currentStage)->toBe(0)
        ->and($review->actionProps['canReview'])->toBeTrue()
        ->and(collect($review->runs)->firstWhere('current')->stages[0]->summaryProps['approvals'])->toBe(0);
});

it('deduplicates carried approvals in total mode', function () {
    $firstGroup = userReviewGroup([$this->reviewers[0]]);
    $secondGroup = userReviewGroup([$this->reviewers[0], $this->reviewers[1]]);
    $workflow = userReviewWorkflow($this->entry, [
        makeUserReviewStage('Editorial review', $firstGroup),
        makeUserReviewStageForGroups('Legal review', [$firstGroup, $secondGroup], approvals: 2),
    ]);
    $run = submitUserReview($this->workflows, $this->draft, $this->author);

    $run = decideUserReview(
        $this->workflows,
        $this->reviewers[0],
        $run,
        $workflow->stages->first(),
        UserReviewDecision::Approved,
    );

    $review = $this->workflows->reviewData($this->draft, $this->reviewers[1]);
    expect($run->status)->toBe(WorkflowStatus::Pending)
        ->and(collect($review->runs)->firstWhere('current')->stages[1]->summaryProps['approvals'])->toBe(1);
});

it('requires approvals from every selected group in per-group mode', function () {
    $editorial = userReviewGroup([$this->reviewers[0], $this->reviewers[1]]);
    $legal = userReviewGroup([$this->reviewers[0], $this->reviewers[2]]);
    $workflow = userReviewWorkflow($this->entry, [
        makeUserReviewStageForGroups('Final review', [$editorial, $legal], approvals: 2, approvalMode: UserReviewApprovalMode::PerGroup),
    ]);
    $run = submitUserReview($this->workflows, $this->draft, $this->author);
    $stage = $workflow->stages->sole();

    $run = decideUserReview($this->workflows, $this->reviewers[0], $run, $stage, UserReviewDecision::Approved);
    $run = decideUserReview($this->workflows, $this->reviewers[1], $run, $stage, UserReviewDecision::Approved);

    $review = $this->workflows->reviewData($this->draft, $this->reviewers[2]);
    $summaryProps = collect($review->runs)->firstWhere('current')->stages[0]->summaryProps;
    expect($run->status)->toBe(WorkflowStatus::Pending)
        ->and($summaryProps['approvalMode'])->toBe('per-group')
        ->and($summaryProps['groups'][0]['approvals'])->toBe(2)
        ->and($summaryProps['groups'][1]['approvals'])->toBe(1);

    $run = decideUserReview($this->workflows, $this->reviewers[2], $run, $stage, UserReviewDecision::Approved);
    expect($run->status)->toBe(WorkflowStatus::Approved);
});

it('keeps a user review stage pending when a configured group no longer exists', function () {
    $group = userReviewGroup([$this->reviewers[0]]);
    userReviewWorkflow($this->entry, [
        makeUserReviewStageForGroups('Final review', [$group], approvalMode: UserReviewApprovalMode::PerGroup),
    ]);
    $group->delete();

    $run = submitUserReview($this->workflows, $this->draft, $this->author);

    expect($run->status)->toBe(WorkflowStatus::Pending)
        ->and($run->currentStageResult)->toBe('A configured reviewer group no longer exists.')
        ->and($this->workflows->reviewData($this->draft, $this->reviewers[0])->actionProps['canReview'])->toBeFalse()
        ->and($this->reviewers[0]->notifications()->count())->toBe(0);
});

/** @param list<array{name: string, type: string, settings: array<string, mixed>}> $stages */
function userReviewWorkflow(Entry $entry, array $stages): Workflow
{
    $workflow = Workflow::query()->create([
        'name' => 'Editorial workflow',
        'uid' => Str::uuid7()->toString(),
        'stages' => array_map(fn (array $stage): array => [...$stage, 'uid' => Str::uuid7()->toString()], $stages),
    ]);
    Section::query()->whereKey($entry->sectionId)->update(['workflowId' => $workflow->id]);
    Sections::refreshSections();

    return $workflow->refresh();
}

/** @return array{name: string, type: string, settings: array<string, mixed>} */
function makeUserReviewStage(string $name, UserGroup $group, int $approvals = 1): array
{
    return makeUserReviewStageForGroups($name, [$group], $approvals);
}

/** @param list<UserGroup> $groups */
function makeUserReviewStageForGroups(string $name, array $groups, int $approvals = 1, UserReviewApprovalMode $approvalMode = UserReviewApprovalMode::Total): array
{
    return [
        'name' => $name,
        'type' => UserReviewStage::class,
        'settings' => [
            'approvalsRequired' => $approvals,
            'approvalMode' => $approvalMode->value,
            'userGroups' => collect($groups)->pluck('uid')->all(),
        ],
    ];
}

function submitUserReview(Workflows $workflows, Entry $draft, User $author): WorkflowRun
{
    return actAsUserReview($author, fn () => $workflows->submitForReview($draft));
}

function decideUserReview(Workflows $workflows, User $reviewer, WorkflowRun $run, WorkflowStageData $stage, UserReviewDecision $decision): WorkflowRun
{
    return actAsUserReview($reviewer, fn (): WorkflowRun => $workflows->reportStageResult(
        runId: $run->id,
        stageUid: $stage->uid,
        result: function (WorkflowStageContext $context) use ($reviewer, $decision): WorkflowStageResult {
            $component = $context->stage->component();
            expect($component)->toBeInstanceOf(UserReviewStage::class);

            return $component->decide($decision, null, $context, $reviewer);
        },
        actor: $reviewer,
        activityTransition: $decision === UserReviewDecision::Approved ? WorkflowTransition::Approve : WorkflowTransition::Reject,
    ));
}

/**
 * @template TResult
 *
 * @param  Closure(): TResult  $callback
 * @return TResult
 */
function actAsUserReview(User $actor, Closure $callback): mixed
{
    $previousUser = Auth::user();
    Auth::guard()->setUser($actor);

    try {
        return $callback();
    } finally {
        $previousUser === null ? Auth::logout() : Auth::guard()->setUser($previousUser);
    }
}

/** @param list<User> $reviewers */
function userReviewGroup(array $reviewers): UserGroup
{
    $group = UserGroup::factory()->create();
    $group->users()->sync(collect($reviewers)->pluck('id'));

    return $group;
}

/** @return Collection<int, ActivityEvent> */
function userReviewEvents(Entry $entry, WorkflowTransition $transition): Collection
{
    return ActivityEvent::query()
        ->subject(ActivitySubject::fromElement($entry))
        ->eventTypes(WorkflowActivityEvent::class)
        ->get()
        ->filter(fn (ActivityEvent $event): bool => $event->data['type'] === $transition->value)
        ->values();
}
