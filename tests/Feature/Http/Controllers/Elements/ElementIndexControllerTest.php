<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Controllers\Elements\ElementIndex\ElementIndexController;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    $this->postIndexAction = fn (string $path, array $payload = []) => postJson(
        action([ElementIndexController::class, match ($path) {
            'get-elements' => 'getElements',
            'get-more-elements' => 'getMoreElements',
            'count-elements' => 'countElements',
            'filter-hud' => 'filterHud',
            'element-table-html' => 'elementTableHtml',
        }]),
        array_merge([
            'context' => ElementSources::CONTEXT_INDEX,
            'elementType' => Entry::class,
            'source' => '*',
            'viewState' => [
                'mode' => 'table',
                'static' => false,
            ],
        ], $payload),
        [
            'Accept' => 'application/json',
        ],
    );
});

it('requires authentication for get-elements', function () {
    auth()->logout();

    postJson(action([ElementIndexController::class, 'getElements']), [
        'elementType' => Entry::class,
    ])->assertUnauthorized();
});

it('returns element HTML and action metadata for get-elements', function () {
    EntryModel::factory()->count(2)->create();

    ($this->postIndexAction)('get-elements')->assertOk()
        ->assertJsonStructure([
            'html',
            'headHtml',
            'bodyHtml',
            'actionsHeadHtml',
            'actionsBodyHtml',
            'exporters',
        ]);
});

it('sorts elements by the requested view state order', function () {
    EntryModel::factory()->createElement(['title' => 'Charlie']);
    EntryModel::factory()->createElement(['title' => 'Alpha']);
    EntryModel::factory()->createElement(['title' => 'Bravo']);

    ($this->postIndexAction)('get-elements', [
        'viewState' => [
            'mode' => 'table',
            'static' => false,
            'order' => 'title',
            'sort' => 'asc',
            'tableColumns' => ['title'],
        ],
    ])->assertOk()
        ->assertJsonPath('html', fn (string $html) => strpos($html, 'Alpha') < strpos($html, 'Bravo') &&
            strpos($html, 'Bravo') < strpos($html, 'Charlie'));
});

it('sorts entries by post date ascending from the element index', function () {
    EntryModel::factory()->createElement([
        'title' => 'Newest',
        'postDate' => now()->subDay(),
    ]);
    EntryModel::factory()->createElement([
        'title' => 'Oldest',
        'postDate' => now()->subDays(3),
    ]);
    EntryModel::factory()->createElement([
        'title' => 'Middle',
        'postDate' => now()->subDays(2),
    ]);

    ($this->postIndexAction)('get-elements', [
        'viewState' => [
            'mode' => 'table',
            'static' => false,
            'order' => 'postDate',
            'sort' => 'asc',
            'tableColumns' => ['title', 'postDate'],
        ],
    ])->assertOk()
        ->assertJsonPath('html', fn (string $html) => strpos($html, 'Oldest') < strpos($html, 'Middle') &&
            strpos($html, 'Middle') < strpos($html, 'Newest'));
});

it('uses the order history as secondary element index ordering', function () {
    EntryModel::factory()->createElement(['title' => 'Alpha', 'slug' => 'alpha']);
    EntryModel::factory()->createElement(['title' => 'Alpha', 'slug' => 'zulu']);
    EntryModel::factory()->createElement(['title' => 'Bravo', 'slug' => 'bravo']);

    ($this->postIndexAction)('get-elements', [
        'viewState' => [
            'mode' => 'table',
            'static' => false,
            'order' => 'title',
            'sort' => 'asc',
            'orderHistory' => [
                ['slug', 'desc'],
            ],
            'tableColumns' => ['title', 'slug'],
        ],
    ])->assertOk()
        ->assertJsonPath('html', fn (string $html) => strpos($html, 'zulu') < strpos($html, 'alpha') &&
            strpos($html, 'alpha') < strpos($html, 'bravo'));
});

it('omits action metadata for get-more-elements', function () {
    EntryModel::factory()->count(2)->create();

    ($this->postIndexAction)('get-more-elements')->assertOk()
        ->assertJsonMissingPath('actions')
        ->assertJsonMissingPath('actionsHeadHtml')
        ->assertJsonMissingPath('actionsBodyHtml')
        ->assertJsonMissingPath('exporters')
        ->assertJsonStructure([
            'html',
        ]);
});

it('returns different filtered and unfiltered counts when filters are applied', function () {
    EntryModel::factory()->count(2)->create();

    $entry = Entry::find()->status(null)->orderBy('elements.id')->first();

    ($this->postIndexAction)('count-elements', [
        'criteria' => [
            'id' => [$entry->id],
        ],
        'resultSet' => 'filtered',
    ])->assertOk()
        ->assertJsonPath('resultSet', 'filtered')
        ->assertJsonPath('total', 1)
        ->assertJsonPath('unfilteredTotal', 2);
});

it('accepts the embedded index context for element index routes', function () {
    EntryModel::factory()->create();

    ($this->postIndexAction)('get-elements', [
        'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
    ])->assertOk();
});

it('returns filter hud html with asset payloads', function () {
    ($this->postIndexAction)('filter-hud', [
        'id' => 'filters',
        'conditionConfig' => [
            'class' => ElementCondition::class,
            'elementType' => Entry::class,
        ],
    ])->assertOk()
        ->assertJsonPath('hudHtml', fn (string $html) => str_contains($html, 'condition-container'))
        ->assertJsonStructure([
            'headHtml',
            'bodyHtml',
        ]);
});

it('prefers the current users provisional draft for element table html', function () {
    $entry = EntryModel::factory()->createElement([
        'title' => 'Canonical Title',
    ]);

    $draft = app(Drafts::class)->createDraft($entry, auth()->id(), provisional: true);
    $draft->title = 'Draft Title';
    Elements::saveElement($draft);

    postJson(action([ElementIndexController::class, 'elementTableHtml']), [
        'context' => ElementSources::CONTEXT_INDEX,
        'elementType' => Entry::class,
        'source' => '*',
        'id' => $entry->id,
        'viewState' => [
            'mode' => 'table',
            'tableColumns' => ['title'],
        ],
    ], [
        'Accept' => 'application/json',
    ])->assertOk()
        ->assertJsonPath('attributeHtml.title', fn (string $html) => str_contains($html, 'Draft Title'));
});

it('preserves the legacy action route contract for get-elements', function () {
    EntryModel::factory()->create();

    postJson('/'.implode('/', array_filter([
        Cms::config()->cpTrigger,
        Cms::config()->actionTrigger,
        'element-indexes/get-elements',
    ])), [
        'context' => ElementSources::CONTEXT_INDEX,
        'elementType' => Entry::class,
        'source' => '*',
        'viewState' => [
            'mode' => 'table',
            'static' => false,
        ],
    ], [
        'Accept' => 'application/json',
    ])->assertOk()
        ->assertJsonStructure([
            'html',
        ]);
});
