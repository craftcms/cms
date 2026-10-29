<?php

declare(strict_types=1);

use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Drafts as DraftService;
use CraftCms\Cms\Element\Models\Draft;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Release\Enums\ReleaseStatus;
use CraftCms\Cms\Release\Models\Release;
use CraftCms\Cms\Release\Releases;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\UserPermissions;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;

beforeEach(function (): void {
    Edition::set(Edition::Pro);
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    $this->actor = User::query()->firstOrFail();
    Passport::actingAs($this->actor, ['mcp:admin'], 'craft-mcp');
    $this->entry = EntryModel::factory()->title('Live title')
        ->withField('summary', PlainText::class, value: 'Live summary')
        ->createElementWithFields()->element;
    $this->draft = app(DraftService::class)->createDraft($this->entry, $this->actor->id, name: 'Launch copy');
    $this->releases = app(Releases::class);
    $this->release = $this->releases->create('Launch', now()->addDays(2), $this->actor, 'Original description');
    $this->releases->attach($this->release, $this->draft, $this->actor);
    $this->callRelease = fn (string $tool, array $arguments): TestResponse => McpRequest::send($this, 'tools/call', [
        'name' => $tool, 'arguments' => $arguments,
    ])->assertOk();
});

it('offers no release tools below Craft Pro', function (): void {
    Edition::set(Edition::Team);

    // The list is paginated, so follow it to the end.
    $tools = collect();
    $cursor = null;
    do {
        $result = McpRequest::send($this, 'tools/list', $cursor === null ? [] : ['cursor' => $cursor])->assertOk()->json('result');
        $tools->push(...collect($result['tools'])->pluck('name'));
        $cursor = $result['nextCursor'] ?? null;
    } while ($cursor !== null);

    expect($tools)->not->toBeEmpty()
        ->and($tools->filter(fn (string $name): bool => str_starts_with($name, 'releases.')))->toBeEmpty();
});

it('refuses the shipped draft apply tool on a scheduled release member', function (): void {
    $this->draft->title = 'Prepared release title';
    expect(Elements::saveElement($this->draft))->toBeTrue();
    $this->releases->schedule($this->release, now()->addDay(), $this->actor);

    ($this->callRelease)('drafts.apply', ['type' => 'entries', 'id' => $this->draft->id])
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'Scheduled and completed releases cannot be changed.');

    expect(Entry::find()->id($this->entry->id)->status(null)->one()->title)->toBe('Live title')
        ->and(Draft::find($this->draft->draftId))->not->toBeNull()
        ->and($this->release->items()->pluck('draftId')->all())->toBe([$this->draft->draftId])
        ->and($this->release->fresh()->status)->toBe(ReleaseStatus::Scheduled);
});

it('edits the explicitly selected draft and applies it ahead of its mutable release', function (): void {
    expect($this->draft->id)->not->toBe($this->draft->draftId);
    ($this->callRelease)('drafts.field-schema', ['draftId' => $this->draft->draftId])
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.schema.properties.summary.type', fn (string|array $type): bool => in_array('string', is_array($type) ? $type : [$type], true));
    ($this->callRelease)('drafts.update', [
        'draftId' => $this->draft->draftId, 'attributes' => ['title' => 'Release title'], 'fields' => ['summary' => 'Release summary'],
    ])->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.draft.id', $this->draft->id)
        ->assertJsonPath('result.structuredContent.draft.draftId', $this->draft->draftId);
    ($this->callRelease)('drafts.get', ['draftId' => $this->draft->draftId])
        ->assertJsonPath('result.structuredContent.draft.title', 'Release title')
        ->assertJsonPath('result.structuredContent.draft.summary', 'Release summary');

    $canonical = Entry::find()->id($this->entry->id)->status(null)->one();
    expect($canonical->getFieldValue('summary'))->toBe('Live summary');

    ($this->callRelease)('drafts.apply', ['draftId' => $this->draft->draftId])
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.element.id', $this->entry->id)
        ->assertJsonPath('result.structuredContent.element.summary', 'Release summary');

    expect(Entry::find()->id($this->entry->id)->status(null)->one()->getFieldValue('summary'))->toBe('Release summary')
        ->and($this->release->items()->exists())->toBeFalse()
        ->and($this->release->fresh()->status)->toBe(ReleaseStatus::Draft)
        ->and(Draft::find($this->draft->draftId))->toBeNull();
});

it('preserves omitted metadata, clears nullable metadata, and pages release names', function (): void {
    $this->releases->create('Excluded campaign', null, $this->actor);
    $other = ($this->callRelease)('releases.create', ['name' => 'Other launch'])
        ->assertJsonPath('result.isError', false)->json('result.structuredContent.release');
    ($this->callRelease)('releases.publish', ['id' => $other['id']])->assertJsonPath('result.isError', true);
    ($this->callRelease)('releases.get', ['id' => $other['id']])
        ->assertJsonPath('result.structuredContent.release.status', 'failed')
        ->assertJsonPath('result.structuredContent.release.lastError', 'The release has no drafts.');
    ($this->callRelease)('releases.update', ['id' => $this->release->id, 'changes' => ['description' => null]])
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.release.name', 'Launch')
        ->assertJsonPath('result.structuredContent.release.description', null)
        ->assertJsonPath('result.structuredContent.release.targetDate', $this->release->targetDate->toIso8601String());
    ($this->callRelease)('releases.update', ['id' => $this->release->id, 'changes' => ['targetDate' => null, 'name' => 'Renamed launch']])
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.release.targetDate', null)
        ->assertJsonPath('result.structuredContent.release.name', 'Renamed launch');
    $first = ($this->callRelease)('releases.list', ['criteria' => ['search' => '  launch  ', 'orderBy' => 'name asc', 'limit' => 1]])
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.releases.0.id', $other['id'])
        ->assertJsonPath('result.structuredContent.releases.0.itemCount', 0)
        ->json('result.structuredContent');
    ($this->callRelease)('releases.list', ['criteria' => ['search' => 'launch', 'orderBy' => 'name asc', 'limit' => 1, 'offset' => $first['nextOffset']]])
        ->assertJsonPath('result.structuredContent.releases.0.id', $this->release->id)
        ->assertJsonPath('result.structuredContent.nextOffset', null);
    ($this->callRelease)('releases.delete', ['id' => $this->release->id])->assertJsonPath('result.structuredContent.deleted', true);

    expect(Release::find($this->release->id))->toBeNull()
        ->and(Draft::find($this->draft->draftId))->not->toBeNull();
});

it('allows a release publisher to inspect basic metadata and publish without content permissions', function (): void {
    $publisher = User::factory()->create();
    app(UserPermissions::class)->saveUserPermissions($publisher->id, ['accessCp', 'manageReleases', 'publishReleases']);
    Passport::actingAs($publisher, ['mcp:admin'], 'craft-mcp');

    ($this->callRelease)('releases.get', ['id' => $this->release->id])
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.release.itemCount', 1)
        ->assertJsonPath('result.structuredContent.release.items.0.elements.0.title', $this->draft->title)
        ->assertJsonPath('result.structuredContent.release.items.0.elements.0.canView', false)
        ->assertJsonPath('result.structuredContent.release.items.0.elements.0.cpEditUrl', null)
        ->assertDontSee('Live summary', escape: false);
    ($this->callRelease)('drafts.get', ['draftId' => $this->draft->draftId])->assertJsonPath('result.isError', true);
    ($this->callRelease)('releases.detach', ['id' => $this->release->id, 'draftId' => $this->draft->draftId])
        ->assertJsonPath('result.isError', true);
    ($this->callRelease)('releases.check-readiness', ['id' => $this->release->id])
        ->assertJsonPath('result.structuredContent.readiness.ready', true);
    ($this->callRelease)('releases.schedule', ['id' => $this->release->id, 'publishAt' => now()->addDay()->toIso8601String()])
        ->assertJsonPath('result.structuredContent.release.status', 'scheduled');
    ($this->callRelease)('releases.unschedule', ['id' => $this->release->id])
        ->assertJsonPath('result.structuredContent.release.status', 'draft');
    ($this->callRelease)('releases.publish', ['id' => $this->release->id])
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.release.status', 'published')
        ->assertJsonPath('result.structuredContent.release.items.0.draftId', null)
        ->assertJsonPath('result.structuredContent.release.items.0.publishedElementId', $this->entry->id);

    expect($this->release->fresh()->status)->toBe(ReleaseStatus::Published);
});

it('attaches and detaches drafts through MCP and removes membership on explicit draft deletion', function (): void {
    ($this->callRelease)('releases.detach', ['id' => $this->release->id, 'draftId' => $this->draft->draftId])
        ->assertJsonPath('result.structuredContent.release.itemCount', 0);
    ($this->callRelease)('releases.attach', ['id' => $this->release->id, 'draftId' => $this->draft->draftId])
        ->assertJsonPath('result.structuredContent.release.items.0.draftId', $this->draft->draftId);
    ($this->callRelease)('drafts.delete', ['draftId' => $this->draft->draftId])
        ->assertJsonPath('result.structuredContent.deleted', true)
        ->assertJsonPath('result.structuredContent.release.itemCount', 0);
    expect(Draft::find($this->draft->draftId))->toBeNull()
        ->and(Entry::find()->id($this->entry->id)->status(null)->one()?->title)->toBe('Live title');
});

it('rejects conflicting draft selectors without applying or deleting content', function (string $tool, string $conflict): void {
    $selectors = ['draftId' => $this->draft->draftId, 'type' => $conflict === 'type' ? 'assets' : 'entries'];
    if ($conflict === 'id') {
        $selectors['id'] = $this->draft->id;
    }

    ($this->callRelease)($tool, $selectors)
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', $conflict === 'id'
            ? 'Provide exactly one of: draftId, id, uid.'
            : 'The draft does not match the requested element type.');

    expect(Draft::find($this->draft->draftId))->not->toBeNull()
        ->and($this->release->items()->pluck('draftId')->all())->toBe([$this->draft->draftId]);
})->with([
    'apply with two selectors' => ['drafts.apply', 'id'],
    'delete with two selectors' => ['drafts.delete', 'id'],
    'apply with a different type' => ['drafts.apply', 'type'],
    'delete with a different type' => ['drafts.delete', 'type'],
]);

it('targets a draft locale explicitly and preserves the other locale and canonical content', function (): void {
    $site = Site::factory()->create();
    Sites::refreshSites();
    $entryType = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($entryType)->withSites($site)->create();
    Sections::refreshSections();
    EntryTypes::refreshEntryTypes();
    $canonical = EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement(['title' => 'Original localized entry']);
    $canonical->setAuthorIds([$this->actor->id]);
    expect(Elements::saveElement($canonical))->toBeTrue();
    $draft = app(DraftService::class)->createDraft($canonical, $this->actor->id);

    ($this->callRelease)('drafts.update', ['draftId' => $draft->draftId, 'siteId' => $site->id, 'attributes' => ['title' => 'Translated draft']])
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.draft.siteId', $site->id);
    ($this->callRelease)('drafts.get', ['draftId' => $draft->draftId, 'siteId' => $site->id])
        ->assertJsonPath('result.structuredContent.draft.title', 'Translated draft');
    ($this->callRelease)('drafts.get', ['draftId' => $draft->draftId])
        ->assertJsonPath('result.structuredContent.draft.title', 'Original localized entry')
        ->assertJsonPath('result.structuredContent.draft.siteId', $canonical->siteId);

    expect(Entry::find()->id($canonical->id)->siteId($site->id)->status(null)->one()->title)->toBe('Original localized entry');
});
