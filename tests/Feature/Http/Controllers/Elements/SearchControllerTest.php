<?php

declare(strict_types=1);

use CraftCms\Cms\Condition\BaseCondition;
use CraftCms\Cms\Condition\Conditions;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Conditions\IdConditionRule;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Operations\ElementPlaceholders;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\Http\Controllers\Elements\SearchController;
use CraftCms\Cms\Search\Search as SearchService;
use CraftCms\Cms\Section\Models\Section as SectionModel;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    if (DB::isMysql()) {
        app(SearchService::class)->useFullText = false;
    }

    $this->entryType = EntryTypeModel::factory()
        ->withFieldLayout()
        ->create([
            'hasTitleField' => true,
        ]);

    $this->section = SectionModel::factory()
        ->withEntryTypes($this->entryType)
        ->create();

    actingAs(User::findOne());
});

it('requires authentication', function () {
    Auth::logout();

    postJson(action(SearchController::class), [
        'elementType' => Entry::class,
        'search' => 'Alpha',
    ])->assertUnauthorized();
});

it('validates the required payload', function () {
    postJson(action(SearchController::class), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['search']);
});

it('validates invalid request payloads', function (array $payload, array $errors) {
    postJson(action(SearchController::class), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($errors);
})->with([
    'invalid element type' => [[
        'elementType' => SearchController::class,
        'search' => 'Alpha',
    ], ['elementType']],
    'invalid criteria type' => [[
        'elementType' => Entry::class,
        'search' => 'Alpha',
        'criteria' => 'invalid',
    ], ['criteria']],
    'invalid exclude ids' => [[
        'elementType' => Entry::class,
        'search' => 'Alpha',
        'excludeIds' => ['invalid'],
    ], ['excludeIds.0']],
    'invalid condition type' => [[
        'elementType' => Entry::class,
        'search' => 'Alpha',
        'condition' => 123,
    ], ['condition']],
    'missing condition class' => [[
        'elementType' => Entry::class,
        'search' => 'Alpha',
        'condition' => [],
    ], ['condition']],
    'invalid reference element ids' => [[
        'elementType' => Entry::class,
        'search' => 'Alpha',
        'referenceElementId' => 'invalid',
        'referenceElementOwnerId' => 'invalid',
        'referenceElementSiteId' => 'invalid',
    ], ['referenceElementId', 'referenceElementOwnerId', 'referenceElementSiteId']],
]);

it('returns an empty result set when nothing matches', function () {
    $entry = EntryModel::factory()
        ->indexed()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement(['title' => 'Something Else Entirely']);

    postJson(action(SearchController::class), [
        'elementType' => Entry::class,
        'search' => 'No Matches Here',
    ])
        ->assertOk()
        ->assertExactJson([
            'elements' => [],
            'exactMatch' => false,
        ]);
});

it('applies sanitized criteria to the query', function () {
    $matchingEntry = EntryModel::factory()
        ->indexed()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement(['title' => 'Criteria Target']);

    $otherEntry = EntryModel::factory()
        ->indexed()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement(['title' => 'Criteria Target']);

    $response = postJson(action(SearchController::class), [
        'elementType' => Entry::class,
        'search' => 'Criteria Target',
        'criteria' => [
            'id' => [$matchingEntry->id],
            'where' => ['id' => 999999],
        ],
    ])
        ->assertOk();

    expect($response->json('elements'))->toHaveCount(1)
        ->and($response->json('elements.0.id'))->toBe($matchingEntry->id)
        ->and($response->json('elements.0.html'))->toContain('chromeless')
        ->and($response->json('elements.0.html'))->toContain('Criteria Target')
        ->and($response->json('exactMatch'))->toBeTrue();
});

it('marks exact matches and sorts excluded results last', function () {
    $exactIncluded = EntryModel::factory()
        ->indexed()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement(['title' => 'Alpha']);

    $partialMatch = EntryModel::factory()
        ->indexed()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement(['title' => 'Alpha Beta']);

    $exactExcluded = EntryModel::factory()
        ->indexed()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement(['title' => 'Alpha']);

    $response = postJson(action(SearchController::class), [
        'elementType' => Entry::class,
        'search' => 'Alpha',
        'excludeIds' => [$exactExcluded->id],
    ])
        ->assertOk();

    expect(collect($response->json('elements'))->pluck('id')->all())
        ->toBe([$exactIncluded->id, $partialMatch->id, $exactExcluded->id])
        ->and($response->json('elements.0.exclude'))->toBeFalse()
        ->and($response->json('elements.1.exclude'))->toBeFalse()
        ->and($response->json('elements.2.exclude'))->toBeTrue()
        ->and($response->json('exactMatch'))->toBeTrue();
});

it('applies element conditions to the query', function () {
    $matchingEntry = EntryModel::factory()
        ->indexed()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement(['title' => 'Conditional Result']);

    $otherEntry = EntryModel::factory()
        ->indexed()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement(['title' => 'Conditional Result']);

    $response = postJson(action(SearchController::class), [
        'elementType' => Entry::class,
        'search' => 'Conditional Result',
        'condition' => [
            'class' => ElementCondition::class,
            'elementType' => Entry::class,
            'conditionRules' => [[
                'class' => IdConditionRule::class,
                'operator' => '=',
                'value' => (string) $matchingEntry->id,
            ]],
        ],
    ])
        ->assertOk();

    expect($response->json('elements'))->toHaveCount(1)
        ->and($response->json('elements.0.id'))->toBe($matchingEntry->id)
        ->and($response->json('exactMatch'))->toBeTrue();
});

it('ignores non-element conditions', function () {
    $state = new stdClass;

    app()->instance(Conditions::class, new readonly class($state) extends Conditions
    {
        public function __construct(
            public stdClass $state,
        ) {}

        public function createCondition(array|string $config): ConditionInterface
        {
            $this->state->config = $config;

            return new class extends BaseCondition
            {
                protected function selectableConditionRules(): array
                {
                    return [];
                }
            };
        }
    });

    $firstEntry = EntryModel::factory()
        ->indexed()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement(['title' => 'Ignored Condition']);

    $secondEntry = EntryModel::factory()
        ->indexed()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement(['title' => 'Ignored Condition']);

    $response = postJson(action(SearchController::class), [
        'elementType' => Entry::class,
        'search' => 'Ignored Condition',
        'condition' => 'ignored-condition',
    ])
        ->assertOk();

    expect(collect($response->json('elements'))->pluck('id')->sort()->values()->all())
        ->toBe(collect([$firstEntry->id, $secondEntry->id])->sort()->values()->all())
        ->and($state->config)->toBe('ignored-condition');
});

it('passes the reference element context into element conditions', function () {
    $referenceEntry = EntryModel::factory()
        ->indexed()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement(['title' => 'Reference Result']);

    $otherEntry = EntryModel::factory()
        ->indexed()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement(['title' => 'Reference Result']);

    $state = new stdClass;

    $conditions = new readonly class($state) extends Conditions
    {
        public function __construct(
            public stdClass $state,
        ) {}

        public function createCondition(array|string $config): ConditionInterface
        {
            $this->state->config = $config;

            return new class($this->state) extends ElementCondition
            {
                public function __construct(
                    public stdClass $state,
                ) {
                    parent::__construct(Entry::class);
                }

                public function modifyQuery(ElementQueryInterface $query): void
                {
                    $this->state->modifyQueryCalled = true;
                    $this->state->referenceElementId = $this->referenceElement?->id;

                    $query->id($this->referenceElement?->id ?? 0);
                }
            };
        }
    };

    $elements = new class(app(ElementPlaceholders::class), $referenceEntry) extends Elements
    {
        public ?int $requestedElementId = null;

        public array|int|string|null $requestedSiteId = null;

        public array $requestedCriteria = [];

        public function __construct(
            ElementPlaceholders $placeholders,
            private readonly Entry $referenceEntry,
        ) {
            parent::__construct($placeholders);
        }

        public function getElementById(
            int $elementId,
            ?string $elementType = null,
            array|int|string|null $siteId = null,
            array $criteria = [],
        ): ElementInterface {
            $this->requestedElementId = $elementId;
            $this->requestedSiteId = $siteId;
            $this->requestedCriteria = $criteria;

            return $this->referenceEntry;
        }
    };

    app()->instance(Conditions::class, $conditions);
    app()->instance(Elements::class, $elements);

    $response = postJson(action(SearchController::class), [
        'elementType' => Entry::class,
        'search' => 'Reference Result',
        'condition' => 'reference-condition',
        'referenceElementId' => $referenceEntry->id,
        'referenceElementOwnerId' => 123,
        'referenceElementSiteId' => $referenceEntry->siteId,
    ])
        ->assertOk();

    expect($response->json('elements'))->toHaveCount(1)
        ->and($response->json('elements.0.id'))->toBe($referenceEntry->id)
        ->and($state->config)->toBe('reference-condition')
        ->and($state->modifyQueryCalled)->toBeTrue()
        ->and($state->referenceElementId)->toBe($referenceEntry->id)
        ->and($elements->requestedElementId)->toBe($referenceEntry->id)
        ->and($elements->requestedSiteId)->toBe($referenceEntry->siteId)
        ->and($elements->requestedCriteria)->toBe(['ownerId' => 123]);
});
