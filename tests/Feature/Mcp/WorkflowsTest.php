<?php

declare(strict_types=1);

use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\Models\UserGroup;
use CraftCms\Cms\User\UserPermissions;
use CraftCms\Cms\Workflow\Data\WorkflowStageContext;
use CraftCms\Cms\Workflow\Data\WorkflowStageResult;
use CraftCms\Cms\Workflow\Enums\WorkflowStageStatus;
use CraftCms\Cms\Workflow\Enums\WorkflowStatus;
use CraftCms\Cms\Workflow\Models\Workflow;
use CraftCms\Cms\Workflow\Models\WorkflowRun;
use CraftCms\Cms\Workflow\Stages\WorkflowStage;
use CraftCms\Cms\Workflow\UserReview\UserReviewStage;
use CraftCms\Cms\Workflow\WorkflowStageTypes;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;

beforeEach(function (): void {
    Edition::set(Edition::Pro);
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);

    $this->author = User::query()->firstOrFail();
    $this->reviewer = User::factory()->create();
    $this->entry = Entry::factory()->createElement(['title' => 'Canonical title']);
    $this->section = $this->entry->getSection();
    app(UserPermissions::class)->saveUserPermissions($this->reviewer->id, [
        'accessCp', 'useCraftMcp', "viewEntries:{$this->section->uid}", "viewPeerEntryDrafts:{$this->section->uid}",
    ]);
    $group = UserGroup::factory()->create();
    $group->users()->sync([$this->author->id, $this->reviewer->id]);
    $this->workflow = Workflow::query()->create([
        'uid' => Str::uuid7()->toString(),
        'name' => 'Editorial review',
        'stages' => [[
            'uid' => Str::uuid7()->toString(),
            'name' => 'Editor approval',
            'type' => UserReviewStage::class,
            'settings' => ['approvalsRequired' => 1, 'userGroups' => [$group->uid]],
        ]],
    ]);
    $this->draft = app(Drafts::class)->createDraft($this->entry, $this->author->id, name: 'Editorial draft');
    $this->draft->title = 'Reviewed title';
    expect(Elements::saveElement($this->draft, updateSearchIndex: false))->toBeTrue();
    Section::query()->whereKey($this->section->id)->update(['workflowId' => $this->workflow->id]);
    Sections::refreshSections();

    $this->callWorkflow = function (string $tool, array $arguments = [], ?User $actor = null): TestResponse {
        Passport::actingAs($actor ?? $this->author, ['mcp:use'], 'craft-mcp');

        return McpRequest::send($this, 'tools/call', [
            'name' => $tool,
            'arguments' => ['type' => 'entries', 'id' => $this->draft->id, 'siteId' => $this->draft->siteId, ...$arguments],
        ]);
    };
});

it('reviews and publishes a draft through MCP as distinct operations', function (): void {
    ($this->callWorkflow)('workflows.review')
        ->assertOk()
        ->assertJsonPath('result.structuredContent.review.status', 'notSubmitted')
        ->assertJsonPath('result.structuredContent.review.actions.canSubmit', true);

    $submitted = ($this->callWorkflow)('workflows.submit', ['note' => 'Ready for review'])
        ->assertOk()
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.review.status', 'pending')
        ->assertJsonPath('result.structuredContent.review.runs.0.submission.note', 'Ready for review')
        ->json('result.structuredContent.review');
    $target = ['runId' => $submitted['runId'], 'stageUid' => $submitted['stage']['uid']];

    ($this->callWorkflow)('workflows.review', actor: $this->reviewer)
        ->assertOk()
        ->assertJsonPath('result.structuredContent.review.actions.canApprove', true)
        ->assertJsonPath('result.structuredContent.review.actions.canApply', false)
        ->assertJsonMissingPath('result.structuredContent.review.actionComponent');
    ($this->callWorkflow)('drafts.apply')->assertJsonPath('result.isError', true);

    ($this->callWorkflow)('workflows.approve', [...$target, 'message' => 'Approved copy'], $this->reviewer)
        ->assertOk()
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.review.status', 'approved')
        ->assertJsonPath('result.structuredContent.review.runs.0.stages.0.events.0.actor.name', $this->reviewer->asElement()->name)
        ->assertJsonPath('result.structuredContent.review.runs.0.stages.0.events.0.note', 'Approved copy');

    expect(EntryElement::find()->id($this->entry->id)->siteId($this->entry->siteId)->status(null)->one()->title)->toBe('Canonical title')
        ->and(WorkflowRun::find($submitted['runId'])->payload[$target['stageUid']]['decisions'][0]['reviewerId'])->toBe($this->reviewer->id);

    ($this->callWorkflow)('drafts.apply', ['workflowRunId' => $submitted['runId'], 'workflowCurrentStage' => $submitted['currentStage']])
        ->assertOk()
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.element.title', 'Reviewed title');

    expect(WorkflowRun::find($submitted['runId'])->status)->toBe(WorkflowStatus::Published);
});

it('saves enabled entries in a workflow section as drafts and suggests submitting them', function (string $tool, Closure $arguments, bool $unpublished): void {
    Passport::actingAs($this->author, ['mcp:use'], 'craft-mcp');

    $saved = McpRequest::send($this, 'tools/call', ['name' => $tool, 'arguments' => $arguments($this)])
        ->assertOk()
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.savedAsDraft', true)
        ->assertJsonPath('result.structuredContent.nextToolCall.name', 'workflows.submit')
        ->json('result.structuredContent');
    $draft = EntryElement::find()->id($saved['element']['id'])->drafts()->status(null)->one();

    expect($draft)->not->toBeNull()
        ->and($draft->getIsUnpublishedDraft())->toBe($unpublished)
        ->and($saved['nextToolCall']['arguments'])->toBe(['type' => 'entries', 'id' => $draft->id, 'siteId' => $draft->siteId])
        ->and($saved['instructions'])->toContain("ID {$draft->id}")
        ->and(EntryElement::find()->id($this->entry->id)->one()->title)->toBe('Canonical title');

    McpRequest::send($this, 'tools/call', $saved['nextToolCall'])
        ->assertOk()
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.review.status', 'pending');
})->with([
    'new entry' => ['elements.create', fn ($test): array => ['type' => 'entries', 'attributes' => [
        'sectionId' => $test->section->id,
        'typeId' => $test->entry->typeId,
        'title' => 'Proposed entry',
        'enabled' => true,
    ]], true],
    'existing entry' => ['elements.update', fn ($test): array => ['type' => 'entries', 'id' => $test->entry->id, 'attributes' => ['title' => 'Proposed title']], false],
]);

it('distinguishes re-review from restarting and rejects obsolete review targets', function (): void {
    $submitted = ($this->callWorkflow)('workflows.submit')->json('result.structuredContent.review');
    $target = ['runId' => $submitted['runId'], 'stageUid' => $submitted['stage']['uid']];

    ($this->callWorkflow)('workflows.requestChanges', [...$target, 'message' => 'Correct the heading'], $this->reviewer)
        ->assertOk()
        ->assertJsonPath('result.structuredContent.review.status', 'failed');
    ($this->callWorkflow)('workflows.requestReview', $target, $this->reviewer)
        ->assertJsonPath('result.isError', true);
    ($this->callWorkflow)('workflows.requestReview', $target)
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.review.runId', $submitted['runId'])
        ->assertJsonPath('result.structuredContent.review.status', 'pending');

    $restarted = ($this->callWorkflow)('workflows.restart', ['runId' => $submitted['runId']])
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.review.runs.1.status', 'invalidated')
        ->json('result.structuredContent.review');

    expect($restarted['runId'])->not->toBe($submitted['runId']);

    ($this->callWorkflow)('workflows.approve', $target, $this->reviewer)->assertJsonPath('result.isError', true);
    ($this->callWorkflow)('workflows.restart', ['runId' => $submitted['runId']])->assertJsonPath('result.isError', true);
    ($this->callWorkflow)('workflows.approve', ['runId' => $restarted['runId'], 'stageUid' => Str::uuid7()->toString()], $this->reviewer)->assertJsonPath('result.isError', true);

    expect(WorkflowRun::find($restarted['runId'])->status)->toBe(WorkflowStatus::Pending);
});

it('allows submission after rejection to create a new review run', function (): void {
    $submitted = ($this->callWorkflow)('workflows.submit')->json('result.structuredContent.review');
    ($this->callWorkflow)('workflows.requestChanges', [
        'runId' => $submitted['runId'], 'stageUid' => $submitted['stage']['uid'], 'message' => 'Needs another pass',
    ], $this->reviewer)->assertJsonPath('result.isError', false);

    $resubmitted = ($this->callWorkflow)('workflows.submit')
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.review.status', 'pending')
        ->json('result.structuredContent.review');

    expect($resubmitted['runId'])->not->toBe($submitted['runId']);
});

it('keeps workflow administration private while allowing draft-scoped review inspection', function (): void {
    Passport::actingAs($this->author, ['mcp:use'], 'craft-mcp');
    McpRequest::send($this, 'tools/call', ['name' => 'workflows.list'])
        ->assertOk()
        ->assertJsonPath('result.structuredContent.workflows.0.sections.0.id', $this->section->id)
        ->assertJsonPath('result.structuredContent.workflows.0.stages.0.settings.approvalsRequired', 1);
    McpRequest::send($this, 'tools/call', ['name' => 'workflows.get', 'arguments' => ['uid' => $this->workflow->uid]])
        ->assertOk()
        ->assertJsonPath('result.structuredContent.workflow.id', $this->workflow->id);

    ($this->callWorkflow)('workflows.review', actor: $this->reviewer)
        ->assertOk()
        ->assertJsonPath('result.structuredContent.review.workflow.id', $this->workflow->id)
        ->assertJsonMissingPath('result.structuredContent.review.workflow.stages.0.settings');
    McpRequest::send($this, 'tools/call', ['name' => 'workflows.get', 'arguments' => ['id' => $this->workflow->id]])
        ->assertBadRequest()
        ->assertJsonPath('error.code', -32602);
    McpRequest::send($this, 'tools/call', ['name' => 'workflows.list'])
        ->assertBadRequest()
        ->assertJsonPath('error.code', -32602);

    app(UserPermissions::class)->saveUserPermissions($this->reviewer->id, ['accessCp', 'useCraftMcp']);
    ($this->callWorkflow)('workflows.review', actor: $this->reviewer)->assertJsonPath('result.isError', true);
});

it('does not treat an author or an unrelated admin as an eligible reviewer', function (string $actor): void {
    $submitted = ($this->callWorkflow)('workflows.submit')->json('result.structuredContent.review');
    $user = $actor === 'author' ? $this->author : User::factory()->admin()->create();

    ($this->callWorkflow)('workflows.approve', ['runId' => $submitted['runId'], 'stageUid' => $submitted['stage']['uid']], $user)
        ->assertJsonPath('result.isError', true);

    expect(WorkflowRun::find($submitted['runId'])->status)->toBe(WorkflowStatus::Pending);
})->with(['author', 'unrelated admin']);

it('cannot use a readable draft to review another draft’s run', function (): void {
    $submitted = ($this->callWorkflow)('workflows.submit')->json('result.structuredContent.review');
    $other = app(Drafts::class)->createDraft($this->entry, $this->author->id, name: 'Another draft');

    ($this->callWorkflow)('workflows.approve', [
        'id' => $other->id, 'runId' => $submitted['runId'], 'stageUid' => $submitted['stage']['uid'],
    ], $this->reviewer)->assertJsonPath('result.isError', true);

    expect(WorkflowRun::find($submitted['runId'])->status)->toBe(WorkflowStatus::Pending);
});

it('rejects empty change requests', function (): void {
    $submitted = ($this->callWorkflow)('workflows.submit')->json('result.structuredContent.review');
    ($this->callWorkflow)('workflows.requestChanges', [
        'runId' => $submitted['runId'], 'stageUid' => $submitted['stage']['uid'], 'message' => '   ',
    ], $this->reviewer)
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'A message is required when requesting changes.');

    expect(WorkflowRun::find($submitted['runId'])->status)->toBe(WorkflowStatus::Pending);
});

it('rejects an overlong submission note before creating a review run', function (): void {
    ($this->callWorkflow)('workflows.submit', ['note' => str_repeat('é', 5001)])
        ->assertBadRequest()
        ->assertJsonPath('error.code', -32602)
        ->assertJsonPath('error.data.validation_errors.0.pointer', '/note');

    expect(WorkflowRun::query()->where('draftId', $this->draft->draftId)->exists())->toBeFalse();
});

it('rejects an overlong approval note without approving the review', function (): void {
    $submitted = ($this->callWorkflow)('workflows.submit')->json('result.structuredContent.review');
    ($this->callWorkflow)('workflows.approve', [
        'runId' => $submitted['runId'], 'stageUid' => $submitted['stage']['uid'], 'message' => str_repeat('é', 5001),
    ], $this->reviewer)
        ->assertBadRequest()
        ->assertJsonPath('error.code', -32602)
        ->assertJsonPath('error.data.validation_errors.0.pointer', '/message');

    expect(WorkflowRun::find($submitted['runId'])->status)->toBe(WorkflowStatus::Pending);
});

it('inspects custom stages without exposing settings or pretending they accept user reviews', function (): void {
    app(WorkflowStageTypes::class)->register(McpExternalReviewStage::class);
    $this->workflow->update(['stages' => [[
        'uid' => Str::uuid7()->toString(), 'name' => 'External review', 'type' => McpExternalReviewStage::class,
        'settings' => ['apiKey' => 'private-plugin-credential'],
    ]]]);
    $submitted = ($this->callWorkflow)('workflows.submit')
        ->assertJsonPath('result.structuredContent.review.stage.type', McpExternalReviewStage::class)
        ->assertJsonPath('result.structuredContent.review.actions.canApprove', false)
        ->assertJsonMissingPath('result.structuredContent.review.workflow.stages.0.settings')
        ->assertJsonPath('result.structuredContent.review.runs.0.stages.0.summary', null)
        ->assertDontSee('private-plugin-credential', escape: false)
        ->json('result.structuredContent.review');

    ($this->callWorkflow)('workflows.approve', ['runId' => $submitted['runId'], 'stageUid' => $submitted['stage']['uid']], $this->reviewer)
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'The current workflow stage does not accept user reviews.');
});

it('rejects stale approvals on draft application without changing the canonical entry', function (): void {
    $submitted = ($this->callWorkflow)('workflows.submit')->json('result.structuredContent.review');
    $restarted = ($this->callWorkflow)('workflows.restart', ['runId' => $submitted['runId']])->json('result.structuredContent.review');
    ($this->callWorkflow)('workflows.approve', ['runId' => $restarted['runId'], 'stageUid' => $restarted['stage']['uid']], $this->reviewer)
        ->assertJsonPath('result.isError', false);

    ($this->callWorkflow)('drafts.apply', ['workflowRunId' => $submitted['runId'], 'workflowCurrentStage' => $submitted['currentStage']])
        ->assertJsonPath('result.isError', true);
    ($this->callWorkflow)('drafts.apply', ['workflowRunId' => $restarted['runId'], 'workflowCurrentStage' => $restarted['currentStage'] + 1])
        ->assertJsonPath('result.isError', true);
    ($this->callWorkflow)('drafts.apply', ['workflowRunId' => $restarted['runId']])
        ->assertJsonPath('result.isError', true);

    expect(WorkflowRun::find($restarted['runId'])->status)->toBe(WorkflowStatus::Approved)
        ->and(EntryElement::find()->id($this->entry->id)->siteId($this->entry->siteId)->status(null)->one()->title)->toBe('Canonical title');

    ($this->callWorkflow)('drafts.apply')
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.element.title', 'Reviewed title');
});

it('does not discard an expected approval when the draft no longer requires review', function (): void {
    $submitted = ($this->callWorkflow)('workflows.submit')->json('result.structuredContent.review');
    Section::query()->whereKey($this->section->id)->update(['workflowId' => null]);
    Sections::refreshSections();

    ($this->callWorkflow)('drafts.apply', ['workflowRunId' => $submitted['runId'], 'workflowCurrentStage' => $submitted['currentStage']])
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'This draft no longer requires workflow approval. Refresh and try again.');

    expect(EntryElement::find()->id($this->entry->id)->siteId($this->entry->siteId)->status(null)->one()->title)->toBe('Canonical title');
});

class McpExternalReviewStage extends WorkflowStage
{
    public string $apiKey = '';

    public function evaluate(WorkflowStageContext $context): WorkflowStageResult
    {
        return new WorkflowStageResult(WorkflowStageStatus::Pending, 'Awaiting external review', $context->payload);
    }

    public function actionProps(WorkflowStageContext $context, CraftUser $viewer): array
    {
        return ['apiKey' => $this->apiKey, 'canReview' => true];
    }

    public function summaryProps(WorkflowStageContext $context, CraftUser $viewer): array
    {
        return ['apiKey' => $this->apiKey];
    }
}
