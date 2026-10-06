<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Address\Models\Address as AddressModel;
use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Actions\Copy;
use CraftCms\Cms\Element\Actions\Delete;
use CraftCms\Cms\Element\Actions\Duplicate;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\Exporters\Raw;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Conditions\AuthorConditionRule;
use CraftCms\Cms\Entry\Conditions\EntryCondition;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Http\Controllers\Elements\ElementIndex\ElementIndexController;
use CraftCms\Cms\Http\ViewModels\EmbeddedIndexViewModel;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Section\Models\SectionSiteSettings;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Twig\Twig;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\DB;
use Symfony\Component\DomCrawler\Crawler;

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

/**
 * @param  array<string, mixed>  $settings
 * @param  list<string>  $titles
 * @param  list<string>  $otherMatrixTitles
 * @return array{owner: Entry, field: Matrix, nestedType: EntryType, columnField: Field}
 */
function embeddedMatrixIndexFixture(array $settings = [], array $titles = ['First', 'Second'], array $otherMatrixTitles = []): array
{
    $columnField = Field::factory()->create([
        'handle' => 'indexColumn',
        'type' => PlainText::class,
    ]);
    $nestedLayout = FieldLayout::factory()->forField($columnField)->create(['type' => Entry::class]);
    $nestedType = EntryType::factory()->withFieldLayout($nestedLayout)->create(['hasTitleField' => true]);
    $matrixSettings = [
        'entryTypes' => [$nestedType->id],
        'viewMode' => Matrix::VIEW_MODE_INDEX,
        'includeTableView' => true,
        'defaultIndexViewMode' => 'table',
        'defaultTableColumns' => ['dateCreated'],
        'pageSize' => 1,
        ...$settings,
    ];
    $factory = EntryModel::factory()->withField('matrixField', Matrix::class, $matrixSettings);

    if ($otherMatrixTitles !== []) {
        $factory = $factory->withField('otherMatrix', Matrix::class, $matrixSettings);
    }

    $fixture = $factory->createElementWithFields();
    $owner = $fixture->element;

    foreach (['matrixField' => $titles, 'otherMatrix' => $otherMatrixTitles] as $handle => $handleTitles) {
        if ($handleTitles === []) {
            continue;
        }

        $entries = [];
        $sortOrder = [];

        foreach ($handleTitles as $title) {
            $uid = 'uid:'.Str::uuid();
            $entries[$uid] = ['type' => $nestedType->handle, 'title' => $title];
            $sortOrder[] = $uid;
        }

        $owner->setFieldValueFromRequest($handle, [
            'entries' => $entries,
            'sortOrder' => $sortOrder,
        ]);
    }

    expect(Elements::saveElement($owner))->toBeTrue();

    return [
        'owner' => Entry::find()->id($owner->id)->one(),
        'field' => Fields::getFieldById($fixture->field('matrixField')->id),
        'nestedType' => $nestedType,
        'columnField' => $columnField,
    ];
}

it('requires authentication for get-elements', function () {
    auth()->logout();

    postJson(action([ElementIndexController::class, 'getElements']), [
        'elementType' => Entry::class,
    ])->assertUnauthorized();
});

it('returns element HTML and action metadata for get-elements', function (string $context) {
    EntryModel::factory()->count(2)->create();

    $response = ($this->postIndexAction)('get-elements', [
        'context' => $context,
        'source' => '__IMP__',
        'sortable' => true,
    ])->assertOk()
        ->assertJsonStructure([
            'html',
            'headHtml',
            'bodyHtml',
            'actionsHeadHtml',
            'actionsBodyHtml',
            'exporters',
        ]);

    expect(array_column($response->json('actions') ?? [], 'type'))->toContain(Delete::class)
        ->and(array_column($response->json('exporters') ?? [], 'type'))->toContain(Raw::class)
        ->and(new Crawler($response->json('html'))->filter('tbody .move')->count())->toBe(2);
})->with([
    'standalone index' => ['index'],
    'legacy embedded index' => ['embedded-index'],
]);

it('renders element table rows with strict Twig variables', function () {
    app(Twig::class)->get(TemplateMode::Cp)->enableStrictVariables();
    EntryModel::factory()->create();

    ($this->postIndexAction)('get-elements')->assertOk()
        ->assertJsonPath('html', fn (string $html) => str_contains($html, '<tr'));
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

it('requires owner scope for embedded index routes', function () {
    EntryModel::factory()->create();

    ($this->postIndexAction)('get-elements', [
        'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['ownerElementType', 'ownerId', 'ownerSiteId', 'attribute']);
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
    actingAs(UserModel::findOrFail(auth()->id()));

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

it('reopens an author filter with its selected user', function () {
    $author = UserModel::factory()->createElement();

    $response = ($this->postIndexAction)('filter-hud', [
        'id' => 'filters',
        'conditionConfig' => [
            'class' => EntryCondition::class,
            'elementType' => Entry::class,
            'conditionRules' => [[
                'class' => AuthorConditionRule::class,
                'elementIds' => [$author->id],
            ]],
        ],
    ])->assertOk();

    expect($response->json('builder.value.conditionRules.rules.0.elementIds'))->toBe([$author->id]);
});

it('accepts the modern filter HUD source descriptor', function () {
    ($this->postIndexAction)('filter-hud', [
        'id' => 'filters',
        'source' => ['type' => 'native', 'key' => '*', 'label' => 'All entries'],
    ])->assertOk()->assertJsonPath('builder.config.sourceKey', '*');
});

it('includes the first server-rendered page in editable Matrix index controls', function () {
    $fixture = embeddedMatrixIndexFixture();
    $props = $fixture['field']->formControl(new FieldContext(
        path: 'matrixField',
        element: $fixture['owner'],
    ))->props();

    expect($props['index'])->toHaveKeys(['indexSettings', 'initial'])
        ->not->toHaveKeys(['defaultTableColumns', 'fieldLayouts', 'pageSize'])
        ->and($props['index']['indexSettings'])->toHaveKeys(['storageKey', 'showHeaderColumn'])
        ->and($props['index']['initial'])->toHaveKeys(['data', 'pagination', 'headHtml', 'bodyHtml', 'reorderable'])
        ->and($props['index']['initial']['viewState']['mode'])->toBe('table')
        ->and($props['index']['initial']['pagination']['per_page'])->toBe(1)
        ->and($props['index']['initial']['pagination']['total'])->toBe(2)
        ->and($props['index']['initial']['data'])->toHaveCount(1)
        ->and($props['index']['initial']['sort'])->toBe([['field' => 'sortOrder', 'direction' => 'asc']])
        ->and($props['index']['initial']['reorderable'])->toBeTrue()
        ->and(json_decode(json_encode($props), true))->toBe($props)
        ->and($props['manager']['pasteableData'])->toBe([
            'attribute' => 'entryTypeId',
            'values' => [$fixture['nestedType']->id],
        ]);
});

it('keeps page assets outside an embedded Matrix initial payload', function () {
    $fixture = embeddedMatrixIndexFixture();
    HtmlStack::cssFile('/page-before-matrix.css');

    $props = $fixture['field']->formControl(new FieldContext(
        path: 'matrixField',
        element: $fixture['owner'],
    ))->props();

    expect($props['index']['initial']['headHtml'])->not->toContain('/page-before-matrix.css')
        ->and(HtmlStack::headHtml())->toContain('/page-before-matrix.css');
});

it('uses posted Matrix index presentation settings while retaining the owner scope', function () {
    $fixture = embeddedMatrixIndexFixture();
    $props = $fixture['field']->formControl(new FieldContext(
        path: 'matrixField',
        element: $fixture['owner'],
    ))->props();

    $response = postJson(action([ElementIndexController::class, 'getElements']), [
        ...$props['manager'],
        'elementType' => Entry::class,
        'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
        'source' => '__IMP__',
        'fieldId' => 999999,
        'viewMode' => 'table',
        'allowedViewModes' => ['table'],
        'defaultTableColumns' => ['dateUpdated'],
        'fieldLayouts' => $props['index']['initial']['fieldLayouts'],
        'per_page' => 2,
        'showHeaderColumn' => false,
        'sortable' => true,
        'static' => false,
        'prevalidate' => true,
        'sort' => [['field' => 'sortOrder', 'direction' => 'desc']],
    ])->assertOk();
    $actions = collect($response->json('actions'))->keyBy('key');

    expect($response->json('pagination.per_page'))->toBe(2)
        ->and($response->json('data'))->toHaveCount(2)
        ->and($response->json('data.0.title'))->toContain('First')
        ->and($response->json('data.0.cardAttributes.class'))->toContain('removable')
        ->and($response->json('data.0.editUrl'))->toContain("fieldId={$fixture['field']->id}", 'prevalidate=1')
        ->and($response->json('sort'))->toBe([['field' => 'sortOrder', 'direction' => 'asc']])
        ->and($response->json('reorderable'))->toBeTrue()
        ->and($response->json('viewState.static'))->toBeFalse()
        ->and($response->json('viewState.showHeaderColumn'))->toBeFalse()
        ->and($response->json('defaultTableColumns'))->toBe(['dateUpdated'])
        ->and(array_column($response->json('tableColumns'), 'value'))->toContain("field:{$fixture['columnField']->uid}")
        ->and(array_column($response->json('viewModes'), 'mode'))->toBe(['table'])
        ->and($actions->keys()->all())->toContain(Copy::class, Duplicate::class, Delete::class)
        ->and($actions[Copy::class]['selectionAttribute'])->toBe('copyable')
        ->and($actions[Duplicate::class]['selectionAttribute'])->toBe('duplicatable')
        ->and($actions[Delete::class]['selectionAttribute'])->toBe('deletable');
});

it('preserves posted native index settings without changing owner scope or authorization', function (bool $authorized, bool $canReorder) {
    $columnField = Field::factory()->create(['handle' => 'addressIndexColumn', 'type' => PlainText::class]);
    $layout = FieldLayout::factory()->forField($columnField)->create(['type' => Address::class]);
    Fields::refreshFields();
    $owner = UserModel::factory()->createElement();
    AddressModel::factory()->withOwnedElement($owner, 1)->createElement(['addressLine1' => 'First']);
    AddressModel::factory()->withOwnedElement($owner, 2)->createElement(['addressLine1' => 'Second']);
    $config = [
        'sortable' => true,
        'canPaste' => true,
        'pageSize' => 1,
        'allowedViewModes' => ['table'],
        'defaultViewMode' => 'table',
        'defaultTableColumns' => ['addressLine1'],
        'fieldLayouts' => [Fields::getLayoutById($layout->id)],
    ];
    $manager = $owner->getAddressManager()->getIndexData($owner, $config);
    $initial = EmbeddedIndexViewModel::forOwner(
        Address::class,
        $owner,
        'addresses',
        $owner->getAddressManager()->getIndexConfig($owner, $config),
    )->payload();
    $otherOwner = UserModel::factory()->createElement();
    AddressModel::factory()->withOwnedElement($otherOwner, 1)->createElement(['addressLine1' => 'Wrong owner']);

    if (! $authorized) {
        SessionAuth::deauthorize("manageNestedElements::$owner->id::addresses");
    }

    if (! $canReorder) {
        SessionAuth::deauthorize("reorderNestedElements::$owner->id::addresses");
    }

    $response = postJson(action([ElementIndexController::class, 'getElements']), [
        ...$manager,
        'elementType' => Address::class,
        'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
        'source' => '__IMP__',
        'viewMode' => 'table',
        'per_page' => $initial['pagination']['per_page'],
        'sortable' => true,
        'canPaste' => true,
        'allowedViewModes' => ['table'],
        'defaultTableColumns' => $initial['defaultTableColumns'],
        'fieldLayouts' => $initial['fieldLayouts'] ?? [],
        'baseCriteria' => ['ownerId' => $otherOwner->id],
        'criteria' => ['ownerId' => $otherOwner->id],
        'static' => false,
    ])->assertOk()
        ->assertJsonPath('pagination.per_page', 1)
        ->assertJsonPath('pagination.total', 2)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('defaultTableColumns', ['addressLine1'])
        ->assertJsonPath('viewModes.0.mode', 'table')
        ->assertJsonCount(1, 'viewModes')
        ->assertJsonPath('viewState.static', ! $authorized)
        ->assertJsonPath('reorderable', $authorized && $canReorder);

    expect(array_column($response->json('tableColumns'), 'value'))->toContain("field:$columnField->uid");
})->with([
    'editable' => [true, true],
    'read-only' => [false, true],
    'reordering not authorized' => [true, false],
]);

it('disables embedded reordering when the view is filtered or re-sorted', function (Closure $query) {
    $fixture = embeddedMatrixIndexFixture();
    $props = $fixture['field']->formControl(new FieldContext(
        path: 'matrixField',
        element: $fixture['owner'],
    ))->props();

    postJson(action([ElementIndexController::class, 'getElements']), [
        ...$props['manager'],
        'elementType' => Entry::class,
        'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
        'source' => '__IMP__',
        ...$query(),
    ])->assertOk()->assertJsonPath('reorderable', false);
})->with([
    'search' => fn () => ['search' => 'First'],
    'status' => fn () => ['status' => 'disabled'],
    'condition rules' => fn () => ['condition' => [
        'class' => EntryCondition::class,
        'conditionRules' => [[
            'class' => AuthorConditionRule::class,
            'elementIds' => [auth()->id()],
        ]],
    ]],
    'different primary sort' => fn () => ['sort' => [['field' => 'dateCreated', 'direction' => 'asc']]],
]);

it('scopes a read-only embedded Matrix index without granting mutation access', function (bool $revision) {
    $fixture = embeddedMatrixIndexFixture(titles: ['Included'], otherMatrixTitles: ['Wrong field']);
    $owner = $fixture['owner'];
    $field = $fixture['field'];

    if ($revision) {
        $owner = Elements::getElementById(app(Revisions::class)->createRevision($owner, force: true));
    }
    $props = $field->formControl(new FieldContext(
        path: 'matrixField',
        element: $owner,
        mode: ControlMode::ReadOnly,
    ))->props();
    expect(json_decode(json_encode($props), true))->toBe($props)
        ->and($props['index']['initial']['viewState']['static'])->toBeTrue()
        ->and($props['index']['initial']['reorderable'])->toBeFalse()
        ->and($props['index']['initial']['data'])->toHaveCount(1)
        ->and($props['index']['initial']['data'][0]['label'])->toBe('Included');

    $request = [
        ...$props['manager'],
        ...Arr::except($props['index'], ['initial']),
        'elementType' => Entry::class,
        'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
        'source' => '*',
        'baseCriteria' => ['ownerId' => 999999, 'fieldId' => 999999],
        'criteria' => ['ownerId' => 999999, 'fieldId' => 999999, 'trashed' => true],
        'allowedViewModes' => ['thumbs'],
        'editable' => true,
        'per_page' => 100,
        'sortable' => true,
        'static' => false,
    ];
    $cardResponse = postJson(action([ElementIndexController::class, 'getElements']), [
        ...$request,
        'viewMode' => 'cards',
    ])->assertOk()
        ->assertJsonPath('pagination.total', 1)
        ->assertJsonPath('pagination.unfilteredTotal', 1)
        ->assertJsonPath('pagination.per_page', 100)
        ->assertJsonPath('reorderable', false)
        ->assertJsonPath('viewState.static', true);
    $tableResponse = postJson(action([ElementIndexController::class, 'getElements']), [
        ...$request,
        'viewMode' => 'table',
    ])->assertOk();
    $card = $cardResponse->json('data.0');
    $tableRow = $tableResponse->json('data.0');

    expect($cardResponse->json('data'))->toHaveCount(1)
        ->and($card['label'])->toBe('Included')
        ->and($card['actionMenuItems'])->toBe([])
        ->and($tableRow)->not->toHaveKey('inlineEditable')
        ->and($tableRow)->not->toHaveKey('inlineInputHtml')
        ->and($card['editUrl'])->toBe($tableRow['editUrl'])
        ->and(new Crawler($tableRow['title'])->filter('[href]')->attr('href'))->toBe($tableRow['editUrl'])
        ->and(SessionAuth::checkAuthorization("manageNestedElements::{$owner->id}::field:matrixField"))->toBeFalse()
        ->and(SessionAuth::checkAuthorization("reorderNestedElements::{$owner->id}::field:matrixField"))->toBeFalse();
})->with(['canonical owner' => false, 'revision owner' => true]);

it('uses grid directions for embedded Matrix card actions', function () {
    $fixture = embeddedMatrixIndexFixture(titles: ['Nested entry']);
    $props = $fixture['field']->formControl(new FieldContext(path: 'matrixField', element: $fixture['owner']))->props();

    $response = postJson(action([ElementIndexController::class, 'getElements']), [
        ...$props['manager'],
        ...$props['index'],
        'elementType' => Entry::class,
        'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
        'source' => '*',
        'viewMode' => 'cards',
        'showInGrid' => true,
    ])->assertOk();

    $labels = array_column($response->json('data.0.actionMenuItems'), 'label');

    expect($labels)->toContain('Move forward', 'Move backward', 'Paste entry before')
        ->not()->toContain('Move up', 'Move down', 'Paste entry above');
});

it('uses the validated owner site for embedded Matrix rows', function () {
    $fixture = embeddedMatrixIndexFixture(['pageSize' => null], ['Primary site child']);
    $owner = $fixture['owner'];
    $field = $fixture['field'];
    $nestedType = $fixture['nestedType'];

    $secondSite = Site::factory()->create();
    Sites::refreshSites();
    SectionSiteSettings::factory()->create([
        'sectionId' => $owner->sectionId,
        'siteId' => $secondSite->id,
    ]);
    Sections::refreshSections();

    $owner = Entry::find()->id($owner->id)->siteId($owner->siteId)->one();
    $secondaryOwner = Elements::propagateElement($owner, $secondSite->id);
    $secondaryPrimaryChild = Entry::find()
        ->fieldId($field->id)
        ->ownerId($secondaryOwner->id)
        ->siteId($secondSite->id)
        ->status(null)
        ->one();
    if ($secondaryPrimaryChild !== null) {
        Elements::deleteElementForSite($secondaryPrimaryChild);
    }
    $section = Section::query()->findOrFail($owner->sectionId);
    $section->entryTypes()->syncWithoutDetaching([$nestedType->id => ['sortOrder' => 2]]);

    foreach (['First secondary site child', 'Second secondary site child'] as $sortOrder => $title) {
        $child = EntryModel::factory()
            ->forSection($section)
            ->forEntryType($nestedType)
            ->title($title)
            ->createElement([
                'fieldId' => $field->id,
                'primaryOwnerId' => $secondaryOwner->id,
            ]);

        DB::table(Table::ENTRIES)->where('id', $child->id)->update(['sectionId' => null]);
        DB::table(Table::ELEMENTS_OWNERS)->insert([
            'elementId' => $child->id,
            'ownerId' => $secondaryOwner->id,
            'sortOrder' => $sortOrder + 1,
        ]);

        Sections::refreshSections();
        $child = Entry::find()->id($child->id)->siteId($owner->siteId)->status(null)->one();
        Elements::propagateElement($child, $secondSite->id);
        Elements::deleteElementForSite($child);
    }
    Sections::refreshSections();

    $props = $field->formControl(new FieldContext(
        path: 'matrixField',
        element: $secondaryOwner,
        mode: ControlMode::ReadOnly,
    ))->props();

    postJson(action([ElementIndexController::class, 'getElements']), [
        ...$props['manager'],
        ...$props['index'],
        'elementType' => Entry::class,
        'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
        'source' => '*',
        'viewMode' => 'table',
    ])->assertOk()
        ->assertJsonPath('pagination.total', 2)
        ->assertJsonPath('pagination.unfilteredTotal', 2)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data', function (array $rows) use ($secondSite): bool {
            $titles = implode(' ', array_column($rows, 'title'));

            return collect($rows)->every(fn (array $row): bool => $row['siteId'] === $secondSite->id) &&
                str_contains($titles, 'First secondary site child') &&
                str_contains($titles, 'Second secondary site child') &&
                ! str_contains($titles, 'Primary site child');
        });
});
