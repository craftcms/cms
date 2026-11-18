<?php

declare(strict_types=1);

use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\EmbeddedElementIndex;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\Models\FieldLayout as FieldLayoutModel;
use CraftCms\Cms\Http\Controllers\Elements\ElementIndex\ElementIndexController;
use CraftCms\Cms\Http\ViewModels\EmbeddedIndexViewModel;
use CraftCms\Cms\Site\Models\Site as SiteModel;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Fields;
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

it('lists elements from every site when the criteria ask for all sites', function () {
    $entry = EntryModel::factory()->create();
    $index = EmbeddedElementIndex::make(Entry::class)->criteria(['id' => $entry->id, 'siteId' => '*']);

    $payload = EmbeddedIndexViewModel::forDefinition($index)->payload();

    expect(collect($payload['data'])->pluck('id')->all())->toBe([$entry->id]);
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

it('defaults to the element type’s table columns', function () {
    $payload = EmbeddedIndexViewModel::forDefinition(EmbeddedElementIndex::make(User::class))->payload();

    expect($payload['defaultTableColumns'])->toBe(['status', 'fullName', 'email', 'dateCreated', 'lastLoginDate']);
});
