<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Actions\Restore;
use CraftCms\Cms\Element\Actions\SetStatus;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\EmbeddedElementIndex;
use CraftCms\Cms\Element\Exporters\Raw;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\Models\FieldLayout as FieldLayoutModel;
use CraftCms\Cms\Http\Controllers\Elements\ElementIndex\ElementIndexController;
use CraftCms\Cms\Http\Controllers\Elements\ElementIndex\ExportElementIndexController;
use CraftCms\Cms\Http\Controllers\Elements\ElementIndex\SaveElementIndexElementsController;
use CraftCms\Cms\Http\Controllers\Elements\PerformElementActionController;
use CraftCms\Cms\Http\ViewModels\EmbeddedIndexViewModel;
use CraftCms\Cms\Section\Models\Section as SectionModel;
use CraftCms\Cms\Site\Models\Site as SiteModel;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiResolver;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    $this->postEmbedded = fn (array $payload = []) => postJson(
        action([ElementIndexController::class, 'getElements']),
        [
            'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
            'elementType' => Entry::class,
            'source' => '__IMP__',
            'viewMode' => 'table',
            'viewState' => ['mode' => 'table'],
            ...$payload,
        ],
    );

    $this->postToIndex = fn (string $controller, EmbeddedElementIndex $index, array $payload) => postJson(
        action($controller),
        [
            'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
            'elementType' => $index->elementType(),
            'source' => '__IMP__',
            'embeddedIndexToken' => $index->token(),
            ...$payload,
        ],
    );
});

it('lists only the elements matching the definition criteria', function () {
    $entries = EntryModel::factory()->count(3)->create();
    $index = EmbeddedElementIndex::make(Entry::class)
        ->criteria(['id' => [$entries[0]->id, $entries[1]->id]])
        ->defaultViewMode('table')
        ->defaultTableColumns(['dateCreated']);

    $payload = ($this->postEmbedded)(['embeddedIndexToken' => $index->token()])
        ->assertOk()
        ->json();

    expect(collect($payload['data'])->pluck('id')->sort()->values()->all())
        ->toBe(collect([$entries[0]->id, $entries[1]->id])->sort()->values()->all())
        ->and($payload['viewState']['mode'])->toBe('table');
});

it('keeps the definition criteria when the request posts its own', function () {
    $entries = EntryModel::factory()->count(2)->create();
    $index = EmbeddedElementIndex::make(Entry::class)->criteria(['id' => $entries[0]->id]);

    $payload = ($this->postEmbedded)([
        'embeddedIndexToken' => $index->token(),
        'baseCriteria' => ['id' => $entries[1]->id],
        'criteria' => ['id' => $entries[1]->id],
    ])->assertOk()->json();

    expect(collect($payload['data'])->pluck('id')->all())->toBe([$entries[0]->id]);
});

it('applies a status in the definition criteria', function () {
    $entries = EntryModel::factory()->count(2)->create();
    $disabled = Entry::find()->id($entries[1]->id)->one();
    $disabled->enabled = false;
    Elements::saveElement($disabled);

    $index = EmbeddedElementIndex::make(Entry::class)
        ->criteria(['id' => [$entries[0]->id, $entries[1]->id], 'status' => 'disabled']);

    $payload = ($this->postEmbedded)(['embeddedIndexToken' => $index->token()])
        ->assertOk()
        ->json();

    expect(collect($payload['data'])->pluck('id')->all())->toBe([$entries[1]->id]);
});

it('rejects tokens that are tampered with, for another element type, or for another user', function () {
    $index = EmbeddedElementIndex::make(Entry::class);

    ($this->postEmbedded)(['embeddedIndexToken' => 'garbage'])->assertStatus(400);

    ($this->postEmbedded)([
        'embeddedIndexToken' => $index->token(),
        'elementType' => User::class,
    ])->assertStatus(400);

    $token = $index->token();
    $other = UserModel::factory()->admin()->create();
    actingAs(User::findOne($other->id));

    ($this->postEmbedded)(['embeddedIndexToken' => $token])->assertStatus(400);
});

it('leaves nested element actions out of its card menus', function () {
    $entry = EntryModel::factory()->create();
    $index = EmbeddedElementIndex::make(Entry::class)->criteria(['id' => $entry->id]);

    $items = ($this->postEmbedded)([
        'embeddedIndexToken' => $index->token(),
        'viewMode' => 'cards',
        'viewState' => ['mode' => 'cards'],
    ])->assertOk()->json('data.0.actionMenuItems');

    expect($items)->not->toBeEmpty()
        ->and(collect($items)->pluck('action.name')->all())->not->toContain('craft:nested-element-action')
        ->and(collect($items)->pluck('label')->all())->not->toContain('Duplicate', 'Delete entry');
});

it('builds the initial payload without a request or owner', function () {
    EntryModel::factory()->count(2)->create();

    $payload = EmbeddedIndexViewModel::forDefinition(
        EmbeddedElementIndex::make(Entry::class)->pageSize(1),
    )->payload();

    expect($payload['data'])->toHaveCount(1)
        ->and($payload['pagination']['total'])->toBeGreaterThanOrEqual(2)
        ->and($payload['reorderable'])->toBeFalse()
        ->and($payload['sort'])->toBe([['field' => 'dateCreated', 'direction' => 'desc']]);
});

it('resolves a Ui node carrying the token and initial payload', function () {
    $node = EmbeddedElementIndex::make(Entry::class)
        ->storageKey('test:entries')
        ->uiNode('entries');

    $props = $node->props();

    expect($node->component())->toBe('craft:element-index')
        ->and($node->getControl())->toBeNull()
        ->and($props['storageKey'])->toBe('test:entries')
        ->and($props['elementType'])->toBe(Entry::class)
        ->and(EmbeddedElementIndex::fromToken($props['token']))->not->toBeNull()
        ->and($props['initial'])->toHaveKey('data');
});

it('resolves as a Ui node when the element type has custom fields', function () {
    $field = Field::factory()->create(['type' => PlainText::class, 'name' => 'Tracking Number', 'handle' => 'trackingNumber']);
    FieldLayoutModel::factory()->forField($field)->create(['type' => User::class]);
    Fields::refreshFields();

    $payload = app(UiResolver::class)->resolve(Ui::make([
        EmbeddedElementIndex::make(User::class)->uiNode('users'),
    ]), new UiContext);

    expect(Json::encode($payload))->toContain('craft:element-index');
});

it('lists one site at a time when the criteria allow every site', function () {
    [$entry, $otherSite] = embeddedIndexEntryInTwoSites();
    $index = EmbeddedElementIndex::make(Entry::class)->criteria(['id' => $entry->id, 'siteId' => '*']);

    $payload = ($this->postEmbedded)(['embeddedIndexToken' => $index->token(), 'site' => $otherSite->handle])
        ->assertOk()
        ->json();

    expect(collect($payload['data'])->pluck('id')->all())->toBe([$entry->id])
        ->and($payload['siteId'])->toBe($otherSite->id)
        ->and(collect($payload['sites'])->pluck('id')->all())->toContain($entry->siteId, $otherSite->id);
});

it('offers only the sites the criteria list, and lists the menu’s first when another is requested', function () {
    [$entry, $otherSite] = embeddedIndexEntryInTwoSites();
    $unlistedSite = SiteModel::factory()->create();
    app(Sites::class)->refreshSites();
    $index = EmbeddedElementIndex::make(Entry::class)
        ->criteria(['id' => $entry->id, 'siteId' => [$otherSite->id, $entry->siteId]]);

    $payload = ($this->postEmbedded)(['embeddedIndexToken' => $index->token(), 'site' => $unlistedSite->handle])
        ->assertOk()
        ->json();

    expect(collect($payload['sites'])->pluck('id')->sort()->values()->all())
        ->toBe(collect([$entry->siteId, $otherSite->id])->sort()->values()->all())
        ->and($payload['siteId'])->toBe($payload['sites'][0]['id'])
        ->and(collect($payload['data'])->pluck('id')->all())->toBe([$entry->id]);
});

it('saves inline edits only to the site it lists when the criteria allow every site', function () {
    [$entry, $otherSite] = embeddedIndexEntryInTwoSites();
    $index = EmbeddedElementIndex::make(Entry::class)->criteria(['id' => $entry->id, 'siteId' => '*']);

    ($this->postToIndex)(SaveElementIndexElementsController::class, $index, [
        'site' => $otherSite->handle,
        'siteId' => $otherSite->id,
        'namespace' => 'inline',
        'inline' => ["element-$entry->id" => ['title' => 'Translated']],
    ])->assertOk();

    expect(Entry::find()->id($entry->id)->siteId($otherSite->id)->status(null)->one()->title)->toBe('Translated')
        ->and(Entry::find()->id($entry->id)->siteId($entry->siteId)->status(null)->one()->title)->toBe('Original');
});

it('lists the elements matching criteria posted without a token', function () {
    $entries = EntryModel::factory()->count(3)->create();

    $payload = ($this->postEmbedded)(['embeddedCriteria' => ['id' => [$entries[0]->id, $entries[2]->id]]])
        ->assertOk()
        ->json();

    expect(collect($payload['data'])->pluck('id')->sort()->values()->all())
        ->toBe([$entries[0]->id, $entries[2]->id]);
});

it('lists assets from any volume', function () {
    AssetModel::factory()->createElement();
    $asset = AssetModel::factory()->createElement();

    $payload = ($this->postEmbedded)(['elementType' => Asset::class, 'embeddedCriteria' => ['id' => $asset->id]])
        ->assertOk()
        ->json();

    expect(collect($payload['data'])->pluck('id')->all())->toBe([$asset->id]);
});

it('lists the site the index requests', function () {
    $site = SiteModel::factory()->create();
    app(Sites::class)->refreshSites();

    $payload = ($this->postEmbedded)([
        'embeddedIndexToken' => EmbeddedElementIndex::make(Entry::class)->token(),
        'site' => $site->handle,
    ])->assertOk()->json();

    expect($payload['siteId'])->toBe($site->id);
});

it('carries elements in the criteria as their IDs', function () {
    $entries = EntryModel::factory()->count(2)->create();
    $index = EmbeddedElementIndex::make(Entry::class)
        ->criteria(['id' => [Entry::find()->id($entries[0]->id)->one()]]);

    $payload = ($this->postEmbedded)(['embeddedIndexToken' => $index->token()])
        ->assertOk()
        ->json();

    expect(collect($payload['data'])->pluck('id')->all())->toBe([$entries[0]->id]);
});

it('rejects criteria and field layouts the token can’t carry', function () {
    expect(fn () => EmbeddedElementIndex::make(Entry::class)->criteria(['id' => new stdClass]))
        ->toThrow(InvalidArgumentException::class, 'id')
        ->and(fn () => EmbeddedElementIndex::make(Entry::class)->fieldLayouts([new FieldLayout(['type' => Entry::class])]))
        ->toThrow(InvalidArgumentException::class);
});

it('offers the custom fields of the given field layouts as columns', function () {
    $field = Field::factory()->create(['type' => PlainText::class, 'name' => 'Tracking Number', 'handle' => 'trackingNumber']);
    $layoutModel = FieldLayoutModel::factory()->forField($field)->create();
    Fields::refreshFields();
    $index = EmbeddedElementIndex::make(Entry::class)->fieldLayouts([Fields::getLayoutById($layoutModel->id)]);

    $payload = ($this->postEmbedded)(['embeddedIndexToken' => $index->token()])
        ->assertOk()
        ->json();

    expect(collect($payload['tableColumns'])->pluck('label')->all())->toContain('Tracking Number');
});

it('offers the custom fields of the element type’s field layouts when none are given', function () {
    $field = Field::factory()->create(['type' => PlainText::class, 'name' => 'Tracking Number', 'handle' => 'trackingNumber']);
    FieldLayoutModel::factory()->forField($field)->create(['type' => User::class]);
    Fields::refreshFields();

    $payload = EmbeddedIndexViewModel::forDefinition(EmbeddedElementIndex::make(User::class))->payload(false);

    expect(collect($payload['tableColumns'])->pluck('label')->all())->toContain('Tracking Number')
        ->and(collect($payload['sortOptions'])->pluck('label')->all())->toContain('Tracking Number');
});

it('defaults to the element type’s table columns', function () {
    $payload = EmbeddedIndexViewModel::forDefinition(EmbeddedElementIndex::make(User::class))->payload();

    expect($payload['defaultTableColumns'])->toBe(['status', 'fullName', 'email', 'dateCreated', 'lastLoginDate']);
});

it('offers element actions unless it’s read-only', function () {
    $editable = EmbeddedIndexViewModel::forDefinition(EmbeddedElementIndex::make(Entry::class))->payload(false);
    $readOnly = EmbeddedIndexViewModel::forDefinition(EmbeddedElementIndex::make(Entry::class)->readOnly())->payload(false);

    expect($editable['readOnly'])->toBeFalse()
        ->and(collect($editable['actions'])->pluck('action.body.elementAction')->all())->toContain(SetStatus::class)
        ->and($readOnly['readOnly'])->toBeTrue()
        ->and($readOnly['actions'])->toBeEmpty();
});

it('runs element actions only on the elements it lists', function () {
    [$listed, $unlisted] = EntryModel::factory()->count(2)->create();
    $index = EmbeddedElementIndex::make(Entry::class)->criteria(['id' => $listed->id]);

    ($this->postToIndex)(PerformElementActionController::class, $index, [
        'elementAction' => SetStatus::class,
        'elementIds' => [$listed->id, $unlisted->id],
        'status' => SetStatus::DISABLED,
    ])->assertOk();

    expect(Entry::find()->id($listed->id)->status(null)->one()->enabled)->toBeFalse()
        ->and(Entry::find()->id($unlisted->id)->status(null)->one()->enabled)->toBeTrue();
});

it('runs element actions on the trashed elements it lists', function () {
    $entry = EntryModel::factory()->createElement();
    Elements::deleteElement($entry);
    $index = EmbeddedElementIndex::make(Entry::class)->criteria(['id' => $entry->id]);

    ($this->postToIndex)(PerformElementActionController::class, $index, [
        'elementAction' => Restore::class,
        'elementIds' => [$entry->id],
        'criteria' => ['status' => 'trashed'],
    ])->assertOk();

    expect(Entry::find()->id($entry->id)->status(null)->exists())->toBeTrue();
});

it('saves inline edits to the elements it lists', function () {
    $entry = EntryModel::factory()->createElement(['title' => 'Before']);
    $index = EmbeddedElementIndex::make(Entry::class)->criteria(['id' => $entry->id]);

    ($this->postToIndex)(SaveElementIndexElementsController::class, $index, [
        'siteId' => $entry->siteId,
        'namespace' => 'inline',
        'inline' => ["element-$entry->id" => ['title' => 'After']],
    ])->assertOk();

    expect(Entry::find()->id($entry->id)->status(null)->one()->title)->toBe('After');
});

it('exports the elements it lists', function () {
    [$listed] = EntryModel::factory()->count(2)->create();
    $index = EmbeddedElementIndex::make(Entry::class)->criteria(['id' => $listed->id]);

    $payload = ($this->postToIndex)(ExportElementIndexController::class, $index, [
        'type' => Raw::class,
        'format' => 'json',
    ])->assertOk()->json();

    expect(collect($payload)->pluck('id')->all())->toBe([$listed->id]);
});

it('rejects element actions and inline edits but allows exports when it’s read-only', function () {
    $entry = EntryModel::factory()->createElement(['title' => 'Before']);
    $index = EmbeddedElementIndex::make(Entry::class)->criteria(['id' => $entry->id])->readOnly();

    ($this->postToIndex)(PerformElementActionController::class, $index, [
        'elementAction' => SetStatus::class,
        'elementIds' => [$entry->id],
        'status' => SetStatus::DISABLED,
    ])->assertForbidden();
    ($this->postToIndex)(SaveElementIndexElementsController::class, $index, [
        'siteId' => $entry->siteId,
        'namespace' => 'inline',
        'inline' => ["element-$entry->id" => ['title' => 'After']],
    ])->assertForbidden();
    $exported = ($this->postToIndex)(ExportElementIndexController::class, $index, [
        'type' => Raw::class,
        'format' => 'json',
    ])->assertOk()->json();

    $entry = Entry::find()->id($entry->id)->status(null)->one();

    expect($entry->enabled)->toBeTrue()
        ->and($entry->title)->toBe('Before')
        ->and(collect($exported)->pluck('id')->all())->toBe([$entry->id]);
});

it('keeps the definition status when the request picks another one', function () {
    [$enabled, $disabled] = EntryModel::factory()->count(2)->create();
    $element = Entry::find()->id($disabled->id)->one();
    $element->enabled = false;
    Elements::saveElement($element);
    $index = EmbeddedElementIndex::make(Entry::class)
        ->criteria(['id' => [$enabled->id, $disabled->id], 'status' => 'disabled']);

    foreach (['enabled', 'live', ''] as $status) {
        $payload = ($this->postEmbedded)(['embeddedIndexToken' => $index->token(), 'status' => $status])
            ->assertOk()
            ->json();

        expect(collect($payload['data'])->pluck('id')->all())->toBe($status === '' ? [$disabled->id] : []);
    }
});

it('keeps the definition status for the site it lists', function () {
    $otherSite = SiteModel::factory()->create();
    app(Sites::class)->refreshSites();
    $entryType = EntryTypeModel::factory()->create();
    $section = SectionModel::factory()->withEntryTypes($entryType)->withSites($otherSite)->create();
    $model = EntryModel::factory()->forSection($section)->forEntryType($entryType)->create();
    $entry = Entry::find()->id($model->id)->one();
    $entry->setEnabledForSite([$entry->siteId => true, $otherSite->id => false]);
    Elements::saveElement($entry);
    $index = EmbeddedElementIndex::make(Entry::class)->criteria(['id' => $entry->id, 'status' => 'disabled']);

    $listed = fn (string $siteHandle) => collect(($this->postEmbedded)([
        'embeddedIndexToken' => $index->token(),
        'status' => 'enabled',
        'site' => $siteHandle,
    ])->assertOk()->json('data'))->pluck('id')->all();

    expect(Entry::find()->id($entry->id)->siteId($otherSite->id)->status('disabled')->exists())->toBeTrue()
        ->and($listed(app(Sites::class)->getPrimarySite()->handle))->toBe([]);
});

it('exports only the elements matching the definition status', function () {
    [$enabled, $disabled] = EntryModel::factory()->count(2)->create();
    $element = Entry::find()->id($disabled->id)->one();
    $element->enabled = false;
    Elements::saveElement($element);
    $index = EmbeddedElementIndex::make(Entry::class)
        ->criteria(['id' => [$enabled->id, $disabled->id], 'status' => 'disabled']);

    $byStatus = ($this->postToIndex)(ExportElementIndexController::class, $index, [
        'type' => Raw::class,
        'format' => 'json',
        'status' => 'enabled',
    ])->assertOk()->json();
    $byId = ($this->postToIndex)(ExportElementIndexController::class, $index, [
        'type' => Raw::class,
        'format' => 'json',
        'criteria' => ['id' => [$enabled->id]],
    ])->assertOk()->json();

    expect($byStatus)->toBe([])
        ->and($byId)->toBe([]);
});

it('runs element actions only on the elements matching the definition status', function () {
    [$enabled, $disabled] = EntryModel::factory()->count(2)->create();
    $element = Entry::find()->id($disabled->id)->one();
    $element->enabled = false;
    Elements::saveElement($element);
    $index = EmbeddedElementIndex::make(Entry::class)
        ->criteria(['id' => [$enabled->id, $disabled->id], 'status' => 'disabled']);

    ($this->postToIndex)(PerformElementActionController::class, $index, [
        'elementAction' => SetStatus::class,
        'elementIds' => [$enabled->id, $disabled->id],
        'status' => SetStatus::ENABLED,
    ])->assertOk();
    ($this->postToIndex)(PerformElementActionController::class, $index, [
        'elementAction' => SetStatus::class,
        'elementIds' => [$enabled->id],
        'status' => SetStatus::DISABLED,
    ]);

    expect(Entry::find()->id($enabled->id)->status(null)->one()->enabled)->toBeTrue()
        ->and(Entry::find()->id($disabled->id)->status(null)->one()->enabled)->toBeTrue();
});

it('saves inline edits only to the elements matching the definition status', function () {
    $entry = EntryModel::factory()->createElement(['title' => 'Before']);
    $index = EmbeddedElementIndex::make(Entry::class)->criteria(['id' => $entry->id, 'status' => 'disabled']);

    ($this->postToIndex)(SaveElementIndexElementsController::class, $index, [
        'siteId' => $entry->siteId,
        'namespace' => 'inline',
        'inline' => ["element-$entry->id" => ['title' => 'After']],
    ])->assertUnprocessable();

    expect(Entry::find()->id($entry->id)->status(null)->one()->title)->toBe('Before');
});

it('hides the status menu when the definition sets a status', function () {
    $fixed = EmbeddedIndexViewModel::forDefinition(EmbeddedElementIndex::make(Entry::class)->criteria(['status' => 'disabled']))->payload(false);
    $open = EmbeddedIndexViewModel::forDefinition(EmbeddedElementIndex::make(Entry::class))->payload(false);

    expect($fixed['statusOptions'])->toBe([])
        ->and(collect($open['statusOptions'])->pluck('value')->all())->toContain('live', 'disabled');
});

it('doesn’t let an inline edit redirect the save to another element', function () {
    $listed = EntryModel::factory()->createElement(['title' => 'Listed']);
    $other = EntryModel::factory()->createElement(['title' => 'Other']);
    $index = EmbeddedElementIndex::make(Entry::class)->criteria(['id' => $listed->id]);

    ($this->postToIndex)(SaveElementIndexElementsController::class, $index, [
        'siteId' => $listed->siteId,
        'namespace' => 'inline',
        'inline' => ["element-$listed->id" => ['id' => $other->id, 'title' => 'Hacked']],
    ])->assertOk();

    expect(Entry::find()->id($other->id)->status(null)->one()->title)->toBe('Other')
        ->and(Entry::find()->id($listed->id)->status(null)->one()->title)->toBe('Hacked');
});

it('applies inline edits to the elements they were posted for, whatever order the index lists them in', function () {
    $entries = collect(['Alpha', 'Bravo', 'Charlie'])
        ->map(fn (string $title) => EntryModel::factory()->createElement(['title' => $title]));
    $index = EmbeddedElementIndex::make(Entry::class)
        ->criteria(['id' => $entries->pluck('id')->all()])
        ->defaultSort('title', 'desc');

    ($this->postToIndex)(SaveElementIndexElementsController::class, $index, [
        'siteId' => $entries[0]->siteId,
        'namespace' => 'inline',
        'inline' => $entries->mapWithKeys(fn (Entry $entry) => ["element-$entry->id" => ['title' => "$entry->title saved"]])->all(),
    ])->assertOk();

    expect($entries->map(fn (Entry $entry) => Entry::find()->id($entry->id)->status(null)->one()->title)->all())
        ->toBe(['Alpha saved', 'Bravo saved', 'Charlie saved']);
});

it('authorizes inline edits after applying them', function () {
    Edition::set(Edition::Pro);
    $entry = EntryModel::factory()->createElement(['title' => 'Before']);
    $otherSection = EntryModel::factory()->createElement()->getSection();
    $section = $entry->getSection();
    $site = app(Sites::class)->getSiteById($entry->siteId);
    $editor = UserModel::factory()
        ->withPermissions([
            'accessCp',
            "editSite:$site->uid",
            "viewEntries:$section->uid",
            "viewPeerEntries:$section->uid",
            "saveEntries:$section->uid",
            "savePeerEntries:$section->uid",
        ])
        ->createElement(['admin' => false]);
    actingAs($editor);
    $index = EmbeddedElementIndex::make(Entry::class)->criteria(['id' => $entry->id]);

    ($this->postToIndex)(SaveElementIndexElementsController::class, $index, [
        'siteId' => $entry->siteId,
        'namespace' => 'inline',
        'inline' => ["element-$entry->id" => ['title' => 'Before saved']],
    ])->assertOk();
    ($this->postToIndex)(SaveElementIndexElementsController::class, $index, [
        'siteId' => $entry->siteId,
        'namespace' => 'inline',
        'inline' => ["element-$entry->id" => ['sectionId' => $otherSection->id, 'title' => 'Moved']],
    ])->assertForbidden();

    $entry = Entry::find()->id($entry->id)->status(null)->one();

    expect($entry->sectionId)->toBe($section->id)
        ->and($entry->title)->toBe('Before saved');
});

/**
 * @return array{0: Entry, 1: SiteModel}
 */
function embeddedIndexEntryInTwoSites(): array
{
    $otherSite = SiteModel::factory()->create();
    app(Sites::class)->refreshSites();
    $entryType = EntryTypeModel::factory()->create();
    $section = SectionModel::factory()->withEntryTypes($entryType)->withSites($otherSite)->create();
    $model = EntryModel::factory()->forSection($section)->forEntryType($entryType)->create();
    $entry = Entry::find()->id($model->id)->one();
    $entry->title = 'Original';
    Elements::saveElement($entry);

    return [Entry::find()->id($entry->id)->one(), $otherSite];
}
