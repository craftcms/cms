<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Events\ElementRestored;
use CraftCms\Cms\Element\Events\ElementSaving;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Mcp\Capabilities\Elements as ElementCapabilities;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    $this->actor = User::query()->firstOrFail();
    actingAs($this->actor);
    app(Request::class)->setUserResolver(fn (): User => $this->actor);

    $field = Field::factory()->create(['handle' => 'summary', 'type' => PlainText::class]);
    $layout = FieldLayout::factory()->forField($field, required: true)->create();
    $type = EntryType::factory()->withFieldLayout($layout)->create();
    $this->section = Section::factory()->withEntryTypes($type)->create();
    EntryTypes::refreshEntryTypes();
    Fields::refreshFields();
    $this->entry = app(ElementCapabilities::class)->create('entries',
        ['sectionId' => $this->section->id, 'title' => 'Original title', 'enabled' => true],
        ['summary' => 'Original summary'],
    )['element'];

    $this->callLifecycle = function (string $tool, array $arguments, ?User $actor = null): TestResponse {
        Passport::actingAs($actor ?? $this->actor, ['mcp:use'], 'craft-mcp');

        return McpRequest::send($this, 'tools/call', ['name' => $tool, 'arguments' => $arguments]);
    };

    $this->newAsset = function (): Asset {
        config()->set('filesystems.disks.lifecycle-assets', [
            'driver' => 'local', 'root' => storage_path('framework/testing/lifecycle-assets'),
        ]);
        Storage::fake('lifecycle-assets');
        $volume = Volume::factory()->create(['fs' => 'lifecycle-assets']);
        $folder = VolumeFolder::factory()->create(['volumeId' => $volume->id, 'path' => '']);
        $asset = AssetModel::factory()->createElement([
            'volumeId' => $volume->id, 'folderId' => $folder->id, 'filename' => 'original.txt', 'kind' => 'text',
        ]);
        Storage::disk('lifecycle-assets')->put($asset->getPath(), 'Original file');

        return $asset;
    };
});

it('returns live validation errors for proposed changes without persisting them', function (): void {
    ($this->callLifecycle)('elements.validate', ['type' => 'entries',
        'id' => $this->entry['id'],
        'attributes' => ['title' => str_repeat('x', 256)],
        'fields' => ['summary' => ''],
    ])->assertOk()->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.valid', false)
        ->assertJsonPath('result.structuredContent.scenario', 'live')
        ->assertJsonStructure(['result' => ['structuredContent' => ['errors' => ['title', 'summary']]]]);

    ($this->callLifecycle)('elements.validate', ['type' => 'entries',
        'uid' => $this->entry['uid'],
        'attributes' => ['title' => 'Proposed title'],
        'fields' => ['summary' => 'Proposed summary'],
    ])->assertOk()->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.valid', true);

    $saved = Entry::find()->id($this->entry['id'])->one();
    expect($saved->title)->toBe('Original title')
        ->and($saved->getFieldValue('summary'))->toBe('Original summary');
});

it('allows viewers to validate saved state but requires update permissions for proposals and restore', function (): void {
    $viewer = User::factory()->withPermissions([
        'accessCp', 'useCraftMcp',
        "viewEntries:{$this->section->uid}", "viewPeerEntries:{$this->section->uid}",
    ])->create(['admin' => false]);

    ($this->callLifecycle)('elements.validate', ['type' => 'entries', 'id' => $this->entry['id']], $viewer)
        ->assertOk()->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.valid', true);
    ($this->callLifecycle)('elements.validate', ['type' => 'entries',
        'id' => $this->entry['id'], 'fields' => ['summary' => 'Unauthorized edit'],
    ], $viewer)->assertOk()->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'You are not authorized to save this entry.');
    ($this->callLifecycle)('elements.restore', ['type' => 'entries', 'id' => $this->entry['id']], $viewer)
        ->assertOk()->assertJsonPath('result.isError', true);
    expect(Entry::find()->id($this->entry['id'])->one()->getFieldValue('summary'))->toBe('Original summary');
});

it('restores discoverable deleted elements and leaves active elements unchanged on retry', function (string $capability): void {
    $element = match ($capability) {
        'entries' => app(Elements::class)->getElementById($this->entry['id'], Entry::class),
        'assets' => ($this->newAsset)(),
        'users' => User::factory()->createElement(),
        'addresses' => Address::find()->id(app(ElementCapabilities::class)->create('addresses', [
            'ownerId' => $this->actor->id, 'countryCode' => 'BE', 'title' => 'Home',
            'locality' => 'Brussels', 'postalCode' => '1000', 'addressLine1' => 'Example Street 1',
        ])['element']['id'])->one(),
    };
    if ($element instanceof Asset) {
        $element->keepFileOnDelete = true;
    }

    app(Elements::class)->deleteElement($element);
    Event::fake([ElementRestored::class]);

    ($this->callLifecycle)('elements.list', [
        'type' => $capability,
        'criteria' => ['id' => $element->id, 'trashed' => true, 'status' => null],
    ])->assertOk()->assertJsonPath('result.structuredContent.count', 1);
    ($this->callLifecycle)('elements.restore', ['type' => $capability, 'uid' => $element->uid])
        ->assertOk()->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.restored', true);
    $restoredAt = DB::table(Table::ELEMENTS)->where('id', $element->id)->value('dateUpdated');
    $this->travel(1)->minutes();
    ($this->callLifecycle)('elements.restore', ['type' => $capability, 'id' => $element->id])
        ->assertOk()->assertJsonPath('result.structuredContent.restored', false);

    expect(DB::table(Table::ELEMENTS)->where('id', $element->id)->value('dateDeleted'))->toBeNull()
        ->and(DB::table(Table::ELEMENTS)->where('id', $element->id)->value('dateUpdated'))->toBe($restoredAt);
    Event::assertDispatchedTimes(ElementRestored::class, 1);
})->with(['entries', 'assets', 'users', 'addresses']);

it('duplicates canonical entries and saved drafts as independent content in the requested mode', function (bool $sourceDraft, ?string $mode): void {
    $source = app(Elements::class)->getElementById($this->entry['id'], Entry::class);

    if ($sourceDraft) {
        $source = app(Drafts::class)->createDraft($source, $this->actor->id);
        $source->title = 'Saved draft title';
        app(Elements::class)->saveElement($source);
    }

    $arguments = ['id' => $source->id];
    if ($mode !== null) {
        $arguments['mode'] = $mode;
    }
    $duplicate = ($this->callLifecycle)('elements.duplicate', ['type' => 'entries', ...$arguments])
        ->assertOk()->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.element.isDraft', $mode !== 'canonical')
        ->assertJsonPath('result.structuredContent.element.isUnpublishedDraft', $mode !== 'canonical')
        ->json('result.structuredContent.element');
    $saved = app(Elements::class)->getElementById($duplicate['id'], Entry::class);

    expect($saved->id)->not->toBe($source->id)
        ->and($saved->getCanonicalId())->toBe($saved->id)
        ->and($saved->title)->toBe($sourceDraft ? 'Saved draft title' : 'Original title')
        ->and($saved->getFieldValue('summary'))->toBe('Original summary')
        ->and(Entry::find()->id($this->entry['id'])->one()->title)->toBe('Original title');
})->with([
    'canonical source, default unpublished copy' => [false, null],
    'canonical source, canonical copy' => [false, 'canonical'],
    'draft source, unpublished copy' => [true, 'unpublished'],
    'draft source, canonical copy' => [true, 'canonical'],
]);

it('lets creators duplicate as unpublished content without granting canonical duplication', function (): void {
    $creator = User::factory()->withPermissions([
        'accessCp', 'useCraftMcp',
        "viewEntries:{$this->section->uid}", "viewPeerEntries:{$this->section->uid}",
        "createEntries:{$this->section->uid}",
    ])->create(['admin' => false]);

    ($this->callLifecycle)('elements.duplicate', ['type' => 'entries', 'id' => $this->entry['id']], $creator)
        ->assertOk()->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.element.isUnpublishedDraft', true)
        ->assertJsonPath('result.structuredContent.element.draftCreatorId', $creator->id);
    ($this->callLifecycle)('elements.duplicate', ['type' => 'entries',
        'id' => $this->entry['id'], 'mode' => 'canonical',
    ], $creator)->assertOk()->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'You are not authorized to duplicate this element.');
});

it('validates saved drafts without exposing peer drafts to unauthorized viewers', function (): void {
    $canonical = app(Elements::class)->getElementById($this->entry['id'], Entry::class);
    $draft = app(Drafts::class)->createDraft($canonical, $this->actor->id);
    $viewer = User::factory()->withPermissions([
        'accessCp', 'useCraftMcp',
        "viewEntries:{$this->section->uid}", "viewPeerEntries:{$this->section->uid}",
        "createEntries:{$this->section->uid}",
    ])->create(['admin' => false]);

    ($this->callLifecycle)('elements.duplicate', ['type' => 'entries', 'id' => $canonical->id], $viewer)
        ->assertOk()->assertJsonPath('result.isError', false);
    ($this->callLifecycle)('elements.validate', ['type' => 'entries', 'id' => $draft->id])
        ->assertOk()->assertJsonPath('result.structuredContent.valid', true);
    ($this->callLifecycle)('elements.validate', ['type' => 'entries', 'id' => $draft->id], $viewer)
        ->assertOk()->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'Entry not found.');
    ($this->callLifecycle)('elements.duplicate', ['type' => 'entries', 'id' => $draft->id], $viewer)
        ->assertOk()->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'Entry not found.');
});

it('rejects revision and provisional-draft inputs for lifecycle tools', function (string $tool): void {
    $source = app(Elements::class)->getElementById($this->entry['id'], Entry::class);
    $revisionId = app(Revisions::class)->createRevision($source, $this->actor->id);
    $provisional = app(Drafts::class)->createDraft($source, $this->actor->id, provisional: true);

    foreach ([$revisionId, $provisional->id] as $id) {
        ($this->callLifecycle)("elements.$tool", ['type' => 'entries', 'id' => $id])
            ->assertOk()->assertJsonPath('result.isError', true);
    }
})->with(['validate', 'duplicate', 'restore']);

it('duplicates addresses without changing ownership and validates owner changes under update permissions', function (): void {
    $address = app(ElementCapabilities::class)->create('addresses', [
        'ownerId' => $this->actor->id, 'countryCode' => 'BE', 'title' => 'Home',
        'locality' => 'Brussels', 'postalCode' => '1000', 'addressLine1' => 'Example Street 1',
    ])['element'];
    $duplicate = ($this->callLifecycle)('elements.duplicate', ['type' => 'addresses', 'id' => $address['id']])
        ->assertOk()->assertJsonPath('result.isError', false)->json('result.structuredContent.element');

    expect($duplicate['id'])->not->toBe($address['id'])
        ->and(Address::find()->id($duplicate['id'])->one()->getOwnerId())->toBe($this->actor->id);

    ($this->callLifecycle)('elements.validate', ['type' => 'addresses',
        'id' => $address['id'], 'attributes' => ['countryCode' => 'XX'],
    ])->assertOk()->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.valid', false)
        ->assertJsonStructure(['result' => ['structuredContent' => ['errors' => ['countryCode']]]]);
    expect(Address::find()->id($address['id'])->one()->countryCode)->toBe('BE');

    $editor = User::factory()->withPermissions(['accessCp', 'useCraftMcp', 'viewUsers'])->create(['admin' => false]);
    $ownAddress = app(ElementCapabilities::class)->create('addresses', [
        'ownerId' => $editor->id, 'countryCode' => 'BE', 'title' => 'Work',
        'locality' => 'Brussels', 'postalCode' => '1000', 'addressLine1' => 'Example Street 2',
    ])['element'];
    ($this->callLifecycle)('elements.validate', ['type' => 'addresses',
        'id' => $ownAddress['id'], 'attributes' => ['ownerId' => $this->actor->id],
    ], $editor)->assertOk()->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'You are not authorized to save this address.');
    expect(Address::find()->id($ownAddress['id'])->one()->getOwnerId())->toBe($editor->id);
});

it('preserves user identity during uniqueness validation and enforces restricted attributes', function (): void {
    $other = User::factory()->create(['email' => 'other@example.test']);
    ($this->callLifecycle)('elements.validate', ['type' => 'users',
        'id' => $this->actor->id, 'attributes' => ['email' => $this->actor->email],
    ])->assertOk()->assertJsonPath('result.structuredContent.valid', true);
    ($this->callLifecycle)('elements.validate', ['type' => 'users',
        'id' => $this->actor->id, 'attributes' => ['email' => $other->email],
    ])->assertOk()->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.valid', false)
        ->assertJsonStructure(['result' => ['structuredContent' => ['errors' => ['email']]]]);

    $editor = User::factory()->withPermissions(['accessCp', 'useCraftMcp', 'viewUsers', 'editUsers'])
        ->create(['admin' => false]);
    ($this->callLifecycle)('elements.validate', ['type' => 'users',
        'id' => $other->id, 'attributes' => ['admin' => true],
    ], $editor)->assertOk()->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'You are not authorized to set restricted user attributes: admin.');
    expect($this->actor->fresh()->email)->toBe($this->actor->email)
        ->and($other->fresh()->admin)->toBeFalse();
});

it('rejects attributes outside the update schema before validating an element', function (): void {
    ($this->callLifecycle)('elements.validate', ['type' => 'entries',
        'id' => $this->entry['id'], 'attributes' => ['sectionId' => $this->section->id, 'id' => 999999],
    ])->assertOk()->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'Invalid attributes for entries: Additional object properties are not allowed: ["sectionId","id"]. Call elements.schema for the accepted attributes.');
});

it('validates proposed asset attributes without moving files and still requires relocation permissions', function (): void {
    $asset = ($this->newAsset)();
    ($this->callLifecycle)('elements.validate', ['type' => 'assets',
        'id' => $asset->id, 'attributes' => ['alt' => 'Proposed description', 'filename' => 'proposed.txt'],
    ])->assertOk()->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.valid', true);

    $volume = $asset->getVolume();
    $editor = User::factory()->withPermissions([
        'accessCp', 'useCraftMcp', "viewAssets:$volume->uid", "viewPeerAssets:$volume->uid",
        "saveAssets:$volume->uid", "savePeerAssets:$volume->uid",
    ])->create(['admin' => false]);
    ($this->callLifecycle)('elements.validate', ['type' => 'assets',
        'id' => $asset->id, 'attributes' => ['alt' => 'Permitted edit'],
    ], $editor)->assertOk()->assertJsonPath('result.isError', false);
    ($this->callLifecycle)('elements.validate', ['type' => 'assets',
        'id' => $asset->id, 'attributes' => ['filename' => 'unauthorized.txt'],
    ], $editor)->assertOk()->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'You are not authorized to move this asset file.');

    $saved = Asset::find()->id($asset->id)->one();
    expect($saved->getFilename())->toBe('original.txt')
        ->and($saved->alt)->toBeNull()
        ->and(Storage::disk('lifecycle-assets')->get($saved->getPath()))->toBe('Original file')
        ->and(Storage::disk('lifecycle-assets')->exists('proposed.txt'))->toBeFalse();
});

it('keeps duplicated nested entries in their existing owner field', function (): void {
    $blockType = EntryType::factory()->create(['handle' => 'block', 'hasTitleField' => false, 'titleFormat' => '{id}']);
    $owner = EntryModel::factory()->withField(
        'blocks', Matrix::class, ['entryTypes' => [$blockType->id]],
        value: ['new1' => ['type' => 'block']],
    )->createElementWithFields()->element;
    $source = $owner->getFieldValue('blocks')->one();
    $duplicate = ($this->callLifecycle)('elements.duplicate', ['type' => 'entries', 'id' => $source->id, 'mode' => 'canonical'])
        ->assertOk()->assertJsonPath('result.isError', false)->json('result.structuredContent.element');

    $saved = Entry::find()->id($duplicate['id'])->status(null)->one();
    expect($saved->getOwnerId())->toBe($owner->id)
        ->and($saved->fieldId)->toBe($source->fieldId)
        ->and(Entry::find()->id($owner->id)->one()->getFieldValue('blocks')->ids())
        ->toContain($source->id, $saved->id);
});

it('restores all supported site variants when loading the deleted entry in one site', function (): void {
    $otherSite = Site::factory()->create();
    $type = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($type)->withSites($otherSite)->create();
    EntryTypes::refreshEntryTypes();

    $entry = app(ElementCapabilities::class)->create('entries', [
        'sectionId' => $section->id, 'title' => 'Multisite entry', 'enabled' => true,
    ])['element'];
    app(ElementCapabilities::class)->delete('entries', id: $entry['id']);

    ($this->callLifecycle)('elements.restore', ['type' => 'entries', 'id' => $entry['id'], 'siteId' => $otherSite->id])
        ->assertOk()->assertJsonPath('result.structuredContent.restored', true);

    expect(Entry::find()->id($entry['id'])->siteId('*')->status(null)->all())->toHaveCount(2)
        ->and(Entry::find()->id($entry['id'])->siteId('*')->trashed(true)->status(null)->exists())->toBeFalse();
});

it('reports an asset restore failure when deletion removed its file', function (): void {
    $asset = ($this->newAsset)();
    ($this->callLifecycle)('elements.delete', ['type' => 'assets', 'id' => $asset->id])
        ->assertOk()->assertJsonPath('result.structuredContent.deleted', true);
    ($this->callLifecycle)('elements.restore', ['type' => 'assets', 'id' => $asset->id])
        ->assertOk()->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'Element could not be restored.');

    expect(Asset::find()->id($asset->id)->trashed(true)->status(null)->exists())->toBeTrue()
        ->and(Storage::disk('lifecycle-assets')->exists($asset->getPath()))->toBeFalse();
});

it('validates proposed nested content without saving or deleting nested entries', function (): void {
    $blockType = EntryType::factory()->create(['handle' => 'proposedBlock', 'hasTitleField' => true]);
    $owner = EntryModel::factory()->withField(
        'blocks', Matrix::class, ['entryTypes' => [$blockType->id]],
        value: ['new1' => ['type' => 'proposedBlock', 'title' => 'Original block']],
    )->createElementWithFields()->element;
    $source = $owner->getFieldValue('blocks')->one();
    $count = DB::table(Table::ENTRIES)->count();

    ($this->callLifecycle)('elements.validate', ['type' => 'entries',
        'id' => $owner->id,
        'attributes' => ['authorId' => $this->actor->id],
        'fields' => ['blocks' => [
            'new1' => ['type' => 'proposedBlock', 'title' => str_repeat('x', 256)],
        ]],
    ])->assertOk()->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.valid', false)
        ->assertJsonStructure(['result' => ['structuredContent' => ['errors' => ['blocks']]]]);

    ($this->callLifecycle)('elements.validate', ['type' => 'entries',
        'id' => $owner->id,
        'attributes' => ['authorId' => $this->actor->id],
        'fields' => ['blocks' => [
            'new1' => ['type' => 'proposedBlock', 'title' => 'Proposed block'],
        ]],
    ])->assertOk()->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.valid', true);

    expect(DB::table(Table::ENTRIES)->count())->toBe($count)
        ->and(Entry::find()->id($owner->id)->one()->getFieldValue('blocks')->ids())->toBe([$source->id])
        ->and(Entry::find()->id($source->id)->one()->title)->toBe('Original block');
});

it('leaves no draft or entry behind when duplication is rejected during saving', function (): void {
    $entryCount = DB::table(Table::ENTRIES)->count();
    $draftCount = DB::table(Table::DRAFTS)->count();
    Event::listen(ElementSaving::class, static function (ElementSaving $event): void {
        if ($event->element->duplicateOf !== null) {
            $event->element->errors()->add('title', 'Copy rejected.');
            $event->isValid = false;
        }
    });

    ($this->callLifecycle)('elements.duplicate', ['type' => 'entries', 'id' => $this->entry['id']])
        ->assertOk()->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'Copy rejected.');

    expect(DB::table(Table::ENTRIES)->count())->toBe($entryCount)
        ->and(DB::table(Table::DRAFTS)->count())->toBe($draftCount);
});
