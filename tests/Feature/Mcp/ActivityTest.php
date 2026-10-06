<?php

declare(strict_types=1);

use CraftCms\Cms\Activity\Activities;
use CraftCms\Cms\Activity\ActivityComments;
use CraftCms\Cms\Activity\Data\ActivityActor;
use CraftCms\Cms\Activity\Data\ActivityChange;
use CraftCms\Cms\Activity\EventTypes\CommentCreated;
use CraftCms\Cms\Activity\EventTypes\ElementUpdated;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Mcp\CapabilityRegistry;
use CraftCms\Cms\Queue\Job;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\Notifications\ActivityMentionNotification;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;
use Mcp\Capability\Attribute\McpTool;

beforeEach(function (): void {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    Notification::fake();

    $this->author = User::query()->firstOrFail();
    $this->entry = Entry::factory()->createElement(['title' => 'Release notes']);
    DB::table(Table::ACTIVITYEVENTS)->delete();

    $this->callActivity = function (string $tool, array $arguments = [], ?User $actor = null): TestResponse {
        $actor ??= $this->author;
        Passport::actingAs($actor, ['mcp:use'], 'craft-mcp');

        return McpRequest::send($this, 'tools/call', [
            'name' => $tool,
            'arguments' => ['type' => 'entries', 'id' => $this->entry->id, ...$arguments],
        ]);
    };
});

afterEach(function (): void {
    Date::setTestNow();
});

it('creates editorial comments with authenticated authorship, mentions, and durable MCP provenance', function (): void {
    $editor = User::factory()->create(['admin' => true, 'username' => 'editor']);
    $markdown = "Please review [@editor](craft-user:{$editor->id}).";
    $created = ($this->callActivity)('activity.comments.create', ['markdown' => $markdown])
        ->assertOk()
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.event.actor.id', $this->author->id)
        ->assertJsonPath('result.structuredContent.event.comment.markdown', $markdown)
        ->assertJsonPath('result.structuredContent.event.origin', 'MCP')
        ->json('result.structuredContent.event');

    $comment = ActivityEvent::findOrFail($created['id']);
    Notification::assertSentTo($editor->asElement(), ActivityMentionNotification::class);
    app(ActivityComments::class)->edit($comment, $this->author->asElement(), 'Updated review request', $this->entry);

    ($this->callActivity)('activity.list')
        ->assertJsonPath('result.structuredContent.events.0.comment.markdown', 'Updated review request')
        ->assertJsonPath('result.structuredContent.events.0.origin', 'MCP');
});

it('captures provenance for existing mutation tools without leaking it into subsequent editor actions', function (): void {
    Context::addHidden('host.request', 'preserved');

    ($this->callActivity)('entries.update', ['attributes' => ['title' => 'Updated through MCP']])
        ->assertOk()->assertJsonPath('result.isError', false);
    $mcpEvent = ActivityEvent::query()->eventTypes(ElementUpdated::class)->firstOrFail();

    $this->entry->title = 'Updated by editor';
    Elements::saveElement($this->entry, updateSearchIndex: false);
    $editorEvent = ActivityEvent::query()->eventTypes(ElementUpdated::class)->newestFirst()->firstOrFail();

    expect($mcpEvent->snapshots['origin'])->toBe('MCP')
        ->and($editorEvent->snapshots)->not->toHaveKey('origin')
        ->and(Context::getHidden('host.request'))->toBe('preserved');
});

it('lists canonical activity with inclusive timezone bounds, site isolation, and stable pagination', function (): void {
    $otherSite = Site::factory()->create();
    Sites::refreshSites();
    $activities = app(Activities::class);
    $site = Sites::getSiteById($this->entry->siteId);
    $draft = app(Drafts::class)->createDraft($this->entry, $this->author->id);
    DB::table(Table::ACTIVITYEVENTS)->delete();

    Date::setTestNow('2026-10-04 10:00:00 UTC');
    $activities->record(new ElementUpdated(subject: $this->entry, site: $site));
    Date::setTestNow('2026-10-05 10:00:00 UTC');
    $neutral = $activities->record(new ElementUpdated(subject: $this->entry));
    $changed = $activities->record(new ElementUpdated(
        subject: $this->entry, site: $site,
        changes: [new ActivityChange('Title', 'Original', 'Revised')],
    ));
    $activities->record(new ElementUpdated(subject: $this->entry, site: Sites::getSiteById($otherSite->id)));
    $activities->record(new ElementUpdated(subject: Entry::factory()->createElement()));
    Date::setTestNow('2026-10-06 10:00:00 UTC');
    $activities->record(new ElementUpdated(subject: $this->entry, site: $site));
    $criteria = ['occurredFrom' => '2026-10-05T12:00:00+02:00', 'occurredUntil' => '2026-10-05T10:00:00Z', 'limit' => 1];

    ($this->callActivity)('activity.list', ['id' => $draft->id, 'criteria' => $criteria])
        ->assertOk()
        ->assertJsonPath('result.structuredContent.count', 1)
        ->assertJsonPath('result.structuredContent.events.0.id', (string) $changed->id)
        ->assertJsonPath('result.structuredContent.events.0.subject.uid', $this->entry->uid)
        ->assertJsonPath('result.structuredContent.events.0.changes.0.old', 'Original')
        ->assertJsonPath('result.structuredContent.events.0.changes.0.new', 'Revised')
        ->assertJsonMissingPath('result.structuredContent.events.0.component');
    ($this->callActivity)('activity.list', ['criteria' => [...$criteria, 'offset' => 1]])
        ->assertJsonPath('result.structuredContent.events.0.id', (string) $neutral->id);
    ($this->callActivity)('activity.list', ['criteria' => [...$criteria, 'offset' => 2]])
        ->assertJsonPath('result.structuredContent.count', 0);
    ($this->callActivity)('activity.list', ['criteria' => [
        'occurredFrom' => '2026-10-05T10:00:00.001Z',
        'occurredUntil' => '2026-10-05T10:00:01Z',
    ]])->assertJsonPath('result.structuredContent.count', 0);
});

it('allows view-only editors to comment and protects comment versions from other viewers', function (): void {
    $viewer = User::factory()->withPermissions([
        'accessCp', 'useCraftMcp', "viewEntries:{$this->entry->getSection()->uid}",
        "viewPeerEntries:{$this->entry->getSection()->uid}",
    ])->create(['admin' => false]);
    ($this->callActivity)('activity.comments.create', ['markdown' => 'View-only editorial note'], $viewer)
        ->assertOk()->assertJsonPath('result.isError', false);

    $root = app(ActivityComments::class)->create($this->entry, $this->author->asElement(), null, 'Original text');
    app(ActivityComments::class)->edit($root, $this->author->asElement(), 'Current text', $this->entry);
    ($this->callActivity)('activity.list', actor: $viewer)
        ->assertJsonPath('result.structuredContent.events.0.id', (string) $root->id)
        ->assertJsonPath('result.structuredContent.events.0.comment.markdown', null)
        ->assertJsonPath('result.structuredContent.events.0.comment.edited', true)
        ->assertJsonPath('result.structuredContent.count', 2);

    app(ActivityComments::class)->delete($root, $this->author->asElement());
    ($this->callActivity)('activity.list')
        ->assertJsonPath('result.structuredContent.events.0.comment.deleted', true)
        ->assertJsonPath('result.structuredContent.events.0.comment.html', null)
        ->assertJsonPath('result.structuredContent.events.0.comment.markdown', null);

    $denied = User::factory()->withPermissions(['accessCp', 'useCraftMcp'])->create(['admin' => false]);
    ($this->callActivity)('activity.list', actor: $denied)
        ->assertOk()->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'Element not found.');
    ($this->callActivity)('activity.comments.create', ['markdown' => 'Unauthorized'], $denied)
        ->assertOk()->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'Element not found.');
    expect(ActivityEvent::query()->eventTypes(CommentCreated::class)->count())->toBe(2);
});

it('rejects ambiguous identities and invalid timezone bounds before returning activity', function (Closure $arguments, string $message): void {
    $response = ($this->callActivity)('activity.list', $arguments($this->entry))
        ->assertOk()->assertJsonPath('result.isError', true);

    expect($response->json('result.content.0.text'))->toContain($message);
})->with([
    'both identities' => [fn (EntryElement $entry): array => ['uid' => $entry->uid], 'Provide exactly one of: id, uid.'],
    'no identity' => [fn (): array => ['id' => null], 'Provide exactly one of: id, uid.'],
    'timezone missing' => [fn (): array => ['criteria' => ['occurredFrom' => '2026-10-05T10:00:00']], 'format is invalid'],
    'reversed range' => [fn (): array => ['criteria' => ['occurredFrom' => '2026-10-06T10:00:00Z', 'occurredUntil' => '2026-10-05T10:00:00Z']], 'occurredFrom must not be after occurredUntil.'],
]);

it('carries MCP provenance into queued work and clears it for unrelated worker jobs', function (): void {
    Cms::config()->queueName('mcp-activity')->trackedQueueNames(['mcp-activity']);
    app(CapabilityRegistry::class)->register(QueuedActivityCapability::class);
    ($this->callActivity)('test.activity.queue')->assertOk()->assertJsonPath('result.isError', false);
    $queue = app(QueueManager::class)->connection('database');
    $queue->push(new QueuedActivityJob($this->entry->id), queue: 'mcp-activity');

    Artisan::call('queue:work', ['connection' => 'database', '--queue' => 'mcp-activity', '--once' => true, '--no-interaction' => true]);
    $causedByMcp = ActivityEvent::query()->eventTypes(ElementUpdated::class)->firstOrFail();
    Artisan::call('queue:work', ['connection' => 'database', '--queue' => 'mcp-activity', '--once' => true, '--no-interaction' => true]);
    $unrelated = ActivityEvent::query()->eventTypes(ElementUpdated::class)->newestFirst()->firstOrFail();

    expect($causedByMcp->actorType)->toBe(ActivityActor::TYPE_SYSTEM)
        ->and($causedByMcp->snapshots['origin'])->toBe('MCP')
        ->and($unrelated->id)->not->toBe($causedByMcp->id)
        ->and($unrelated->snapshots)->not->toHaveKey('origin');
});

class QueuedActivityCapability
{
    /** @return array{queued: true} */
    #[McpTool(name: 'test.activity.queue')]
    public function queue(string $type, int $id): array
    {
        app(QueueManager::class)->connection('database')->push(new QueuedActivityJob($id), queue: 'mcp-activity');

        return ['queued' => true];
    }
}

class QueuedActivityJob extends Job
{
    public function __construct(public int $elementId) {}

    public function handle(Activities $activities): void
    {
        $activities->record(new ElementUpdated(
            subject: EntryElement::find()->id($this->elementId)->status(null)->one(),
            actor: ActivityActor::system(),
        ));
    }
}
