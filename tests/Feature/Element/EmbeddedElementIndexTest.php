<?php

declare(strict_types=1);

use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\EmbeddedElementIndex;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Controllers\Elements\ElementIndex\ElementIndexController;
use CraftCms\Cms\Http\ViewModels\EmbeddedIndexViewModel;
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
        ->and($payload['reorderable'])->toBeFalse();
});

it('resolves a Ui control carrying the token and initial payload', function () {
    $control = EmbeddedElementIndex::make(Entry::class)
        ->storageKey('test:entries')
        ->uiControl(['entries']);

    $props = $control->props();

    expect($control->component())->toBe('craft:element-index')
        ->and($props['storageKey'])->toBe('test:entries')
        ->and($props['elementType'])->toBe(Entry::class)
        ->and(EmbeddedElementIndex::fromToken($props['token']))->not->toBeNull()
        ->and($props['initial'])->toHaveKey('data');
});
