<?php

declare(strict_types=1);

use CraftCms\Cms\Activity\DraftActivity;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\ElementTypes;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Operations\ElementPlaceholders;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\EntryTypes as EntryTypesService;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Http\Controllers\MatrixController;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements as ElementsFacade;
use CraftCms\Cms\Support\Facades\EntryTypes as EntryTypesFacade;
use CraftCms\Cms\Support\Facades\Fields as FieldsFacade;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User as UserElement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\DomCrawler\Crawler;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(UserElement::findOne());

    $innerField = Field::factory()->create([
        'name' => 'Inner Text',
        'handle' => 'innerText',
        'type' => PlainText::class,
    ]);

    $entryType = EntryType::factory()
        ->withField($innerField)
        ->create([
            'name' => 'Matrix Block',
            'handle' => 'matrixBlock',
            'hasTitleField' => true,
        ]);

    $matrixField = Field::factory()->create([
        'name' => 'Matrix Field',
        'handle' => 'matrixField',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$entryType->id]],
    ]);

    $ownerType = EntryType::factory()
        ->withField($matrixField)
        ->create([
            'name' => 'Owner',
            'handle' => 'owner',
            'hasTitleField' => true,
        ]);

    $section = Section::factory()
        ->withEntryTypes($ownerType)
        ->create([
            'handle' => 'owners',
        ]);

    $owner = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($ownerType)
        ->createElement([
            'title' => 'Owner Entry',
            'slug' => Str::slug('Owner Entry '.Str::random(6)),
        ]);

    EntryTypesFacade::refreshEntryTypes();
    FieldsFacade::invalidateCaches();
    FieldsFacade::refreshFields();

    $this->fixture = [
        'entryType' => $entryType,
        'field' => FieldsFacade::getFieldById($matrixField->id),
        'innerField' => $innerField,
        'owner' => EntryElement::find()->id($owner->id)->status(null)->one(),
        'ownerType' => $ownerType,
        'section' => $section,
        'siteId' => Site::firstOrFail()->id,
    ];
});

function createMatrixControllerPayload(array $fixture, array $overrides = []): array
{
    return array_merge([
        'fieldId' => $fixture['field']->id,
        'entryTypeId' => $fixture['entryType']->id,
        'ownerId' => $fixture['owner']->id,
        'ownerElementType' => EntryElement::class,
        'siteId' => $fixture['siteId'],
        'namespace' => 'testNamespace',
    ], $overrides);
}

function saveMatrixControllerBlocks(array $fixture, array $blocks): EntryElement
{
    $entries = [];
    $sortOrder = [];

    foreach ($blocks as $block) {
        $uid = $block['uid'] ?? Str::uuid()->toString();
        $sortOrder[] = $uid;
        $entries["uid:$uid"] = [
            'type' => $fixture['entryType']->handle,
            'title' => $block['title'],
            'enabled' => $block['enabled'] ?? true,
            'fields' => [
                'innerText' => $block['innerText'],
            ],
        ];
    }

    $owner = EntryElement::find()->id($fixture['owner']->id)->status(null)->one();
    $owner->setFieldValueFromRequest($fixture['field']->handle, [
        'entries' => $entries,
        'sortOrder' => $sortOrder,
    ]);

    expect(ElementsFacade::saveElement($owner))->toBeTrue();

    return EntryElement::find()->id($owner->id)->status(null)->one();
}

function matrixControllerNestedEntries(array $fixture): Collection
{
    return collect(EntryElement::find()
        ->fieldId($fixture['field']->id)
        ->ownerId($fixture['owner']->id)
        ->siteId($fixture['siteId'])
        ->drafts(null)
        ->status(null)
        ->all());
}

function refreshMatrixControllerFixture(array $fixture): array
{
    $fixture['field'] = FieldsFacade::getFieldById($fixture['field']->id);
    $fixture['owner'] = EntryElement::find()->id($fixture['owner']->id)->status(null)->one();

    return $fixture;
}

it('validates entry type ids for default table column options', function () {
    postJson(action([MatrixController::class, 'defaultTableColumnOptions']))
        ->assertJsonValidationErrorFor('entryTypeIds');
});

it('rejects invalid entry type ids for default table column options', function () {
    postJson(action([MatrixController::class, 'defaultTableColumnOptions']), [
        'entryTypeIds' => [999999],
    ])->assertBadRequest()
        ->assertJsonPath('message', 'Invalid entry type ID: 999999');
});

it('returns default table column options for matrix entry types', function () {
    $response = postJson(action([MatrixController::class, 'defaultTableColumnOptions']), [
        'entryTypeIds' => [$this->fixture['entryType']->id],
    ])
        ->assertOk();

    $expectedOptions = Matrix::defaultTableColumnOptions([
        app(EntryTypesService::class)->getEntryTypeById($this->fixture['entryType']->id),
    ]);

    expect($response->json('options'))->toBe($expectedOptions);
});

it('validates create entry payloads', function () {
    postJson(action([MatrixController::class, 'createEntry']))
        ->assertJsonValidationErrorFor('fieldId');
});

it('rejects invalid owners when creating a matrix entry', function () {
    postJson(action([MatrixController::class, 'createEntry']), createMatrixControllerPayload($this->fixture, [
        'ownerId' => 999999,
    ]))->assertBadRequest()
        ->assertJsonPath('message', 'Invalid owner ID, element type, or site ID.');
});

it('rejects invalid matrix field ids when creating a matrix entry', function () {
    postJson(action([MatrixController::class, 'createEntry']), createMatrixControllerPayload($this->fixture, [
        'fieldId' => 999999,
    ]))->assertBadRequest()
        ->assertJsonPath('message', 'Invalid Matrix field ID: 999999');
});

it('rejects invalid entry type ids when creating a matrix entry', function () {
    postJson(action([MatrixController::class, 'createEntry']), createMatrixControllerPayload($this->fixture, [
        'entryTypeId' => 999999,
    ]))->assertBadRequest()
        ->assertJsonPath('message', 'Invalid entry type ID: 999999');
});

it('rejects invalid site ids when creating a matrix entry', function () {
    postJson(action([MatrixController::class, 'createEntry']), createMatrixControllerPayload($this->fixture, [
        'siteId' => 999999,
    ]))->assertBadRequest()
        ->assertJsonPath('message', 'Invalid owner ID, element type, or site ID.');
});

it('creates a new matrix entry draft and renders its block html', function () {
    $response = postJson(action([MatrixController::class, 'createEntry']), createMatrixControllerPayload($this->fixture, [
        'staticEntries' => true,
    ]))
        ->assertOk()
        ->assertJsonStructure(['blockHtml', 'headHtml', 'bodyHtml']);

    $entries = matrixControllerNestedEntries($this->fixture);
    $entry = $entries->sole();

    expect($entry->typeId)->toBe($this->fixture['entryType']->id)
        ->and($entry->fieldId)->toBe($this->fixture['field']->id)
        ->and($entry->getOwnerId())->toBe($this->fixture['owner']->id)
        ->and($entry->draftId)->not->toBeNull();

    $html = $response->json('blockHtml');
    $host = new Crawler($html)->filter('craft-entry-field-layout-form[data-payload]');

    expect($html)
        ->toContain('Matrix Block')
        ->toContain('testNamespace[matrixField][entries][uid:'.$entry->uid.'][fresh]')
        ->and($host)->toHaveCount(1)
        ->and(json_decode((string) $host->attr('data-payload'), true, flags: JSON_THROW_ON_ERROR)['scope'])
        ->toBe(['testNamespace', 'matrixField', 'entries', "uid:{$entry->uid}"]);
});

it('returns a failure response when saving a new matrix draft fails', function () {
    app()->instance(Drafts::class, new readonly class(app(Elements::class), app(DraftActivity::class)) extends Drafts
    {
        public function saveElementAsDraft(ElementInterface $element, ?int $creatorId = null, ?string $name = null, ?string $notes = null, bool $markAsSaved = true): bool
        {
            return false;
        }
    });

    postJson(action([MatrixController::class, 'createEntry']), createMatrixControllerPayload($this->fixture))->assertBadRequest()
        ->assertJsonPath('message', mb_ucfirst(t('Couldn’t create {type}.', [
            'type' => EntryElement::lowerDisplayName(),
        ])));
});

it('rejects invalid duplicate source ids when creating a matrix entry', function () {
    postJson(action([MatrixController::class, 'createEntry']), createMatrixControllerPayload($this->fixture, [
        'duplicate' => 999999,
    ]))->assertBadRequest()
        ->assertJsonPath('message', 'Invalid source element ID: 999999');
});

it('rejects duplicate sources from another matrix owner', function () {
    $victimOwner = EntryModel::factory()
        ->forSection($this->fixture['section'])
        ->forEntryType($this->fixture['ownerType'])
        ->createElement([
            'title' => 'Victim Owner Entry',
            'slug' => Str::slug('Victim Owner Entry '.Str::random(6)),
        ]);
    $victimFixture = [
        ...$this->fixture,
        'owner' => EntryElement::find()->id($victimOwner->id)->status(null)->one(),
    ];

    $victimFixture['owner'] = saveMatrixControllerBlocks($victimFixture, [[
        'title' => 'Victim Block',
        'innerText' => 'Victim text',
    ]]);
    $victimFixture = refreshMatrixControllerFixture($victimFixture);
    $source = matrixControllerNestedEntries($victimFixture)->sole();

    postJson(action([MatrixController::class, 'createEntry']), createMatrixControllerPayload($this->fixture, [
        'duplicate' => $source->id,
    ]))->assertBadRequest()
        ->assertJsonPath('message', "Invalid source element ID: $source->id");
});

it('forbids duplicating a matrix entry when authorization fails', function () {
    $this->fixture['owner'] = saveMatrixControllerBlocks($this->fixture, [[
        'title' => 'Source Block',
        'innerText' => 'Source text',
    ]]);
    $this->fixture = refreshMatrixControllerFixture($this->fixture);
    $source = matrixControllerNestedEntries($this->fixture)->sole();

    Gate::before(function ($user, string $ability) {
        if ($ability === 'duplicateAsDraft') {
            return false;
        }

        return null;
    });

    postJson(action([MatrixController::class, 'createEntry']), createMatrixControllerPayload($this->fixture, [
        'duplicate' => $source->id,
    ]))
        ->assertForbidden();
});

it('forbids duplicating a matrix entry when viewing the source is not authorized', function () {
    $this->fixture['owner'] = saveMatrixControllerBlocks($this->fixture, [[
        'title' => 'Source Block',
        'innerText' => 'Source text',
    ]]);
    $this->fixture = refreshMatrixControllerFixture($this->fixture);
    $source = matrixControllerNestedEntries($this->fixture)->sole();

    Gate::before(function ($user, string $ability) {
        if ($ability === 'view') {
            return false;
        }

        return null;
    });

    postJson(action([MatrixController::class, 'createEntry']), createMatrixControllerPayload($this->fixture, [
        'duplicate' => $source->id,
    ]))
        ->assertForbidden();
});

it('duplicates an existing matrix entry and renders its block html', function () {
    $this->fixture['owner'] = saveMatrixControllerBlocks($this->fixture, [[
        'title' => 'Source Block',
        'innerText' => 'Source text',
    ]]);
    $this->fixture = refreshMatrixControllerFixture($this->fixture);
    $source = matrixControllerNestedEntries($this->fixture)->sole();

    $response = postJson(action([MatrixController::class, 'createEntry']), createMatrixControllerPayload($this->fixture, [
        'duplicate' => $source->id,
    ]))
        ->assertOk()
        ->assertJsonStructure(['blockHtml', 'headHtml', 'bodyHtml']);

    $entries = matrixControllerNestedEntries($this->fixture);
    $duplicate = $entries->first(fn (EntryElement $entry) => $entry->id !== $source->id);

    expect($entries)->toHaveCount(2)
        ->and($duplicate)->not->toBeNull()
        ->and($duplicate->id)->not->toBe($source->id)
        ->and($duplicate->getFieldValue('innerText'))->toBe('Source text');

    expect($response->json('blockHtml'))
        ->toContain('testNamespace[matrixField][entries][uid:'.$duplicate->uid.'][fresh]');
});

it('returns a failure response when duplicating a matrix entry fails validation', function () {
    $this->fixture['owner'] = saveMatrixControllerBlocks($this->fixture, [[
        'title' => 'Source Block',
        'innerText' => 'Source text',
    ]]);
    $this->fixture = refreshMatrixControllerFixture($this->fixture);
    $source = matrixControllerNestedEntries($this->fixture)->sole();

    app()->instance(Elements::class, new class(app(ElementPlaceholders::class), app(ElementTypes::class), app(ElementCaches::class)) extends Elements
    {
        public function duplicateElement(
            ElementInterface $element,
            array $newAttributes = [],
            bool $placeInStructure = true,
            bool $asUnpublishedDraft = false,
            bool $checkAuthorization = false,
            bool $copyModifiedFields = false,
        ): ElementInterface {
            throw new InvalidElementException($element, 'Invalid element');
        }
    });

    postJson(action([MatrixController::class, 'createEntry']), createMatrixControllerPayload($this->fixture, [
        'duplicate' => $source->id,
    ]))->assertBadRequest()
        ->assertJsonPath('message', t('Couldn’t duplicate {type}.', [
            'type' => EntryElement::lowerDisplayName(),
        ]));
});

it('validates render blocks payloads', function () {
    postJson(action([MatrixController::class, 'renderBlocks']))
        ->assertJsonValidationErrorFor('entryIds');
});

it('returns empty html when render blocks cannot find entries', function () {
    postJson(action([MatrixController::class, 'renderBlocks']), [
        'entryIds' => [999999],
        'siteId' => Site::firstOrFail()->id,
        'namespace' => 'testNamespace',
    ])
        ->assertOk()
        ->assertJsonPath('blockHtml', '')
        ->assertJsonStructure(['headHtml', 'bodyHtml']);
});

it('rejects render blocks requests for entries outside matrix fields', function () {
    $entry = EntryModel::factory()->createElement();

    postJson(action([MatrixController::class, 'renderBlocks']), [
        'entryIds' => [$entry->id],
        'siteId' => $entry->siteId,
        'namespace' => 'testNamespace',
    ])->assertBadRequest()
        ->assertJsonPath('message', 'Entry must belong to a Matrix field.');
});

it('forbids rendering matrix blocks when authorization fails', function () {
    $this->fixture['owner'] = saveMatrixControllerBlocks($this->fixture, [[
        'title' => 'First Block',
        'innerText' => 'First text',
    ]]);
    $this->fixture = refreshMatrixControllerFixture($this->fixture);
    $entry = matrixControllerNestedEntries($this->fixture)->sole();

    Gate::before(function ($user, string $ability) {
        if ($ability === 'view') {
            return false;
        }

        return null;
    });

    postJson(action([MatrixController::class, 'renderBlocks']), [
        'entryIds' => [$entry->id],
        'siteId' => $this->fixture['siteId'],
        'namespace' => 'testNamespace',
    ])
        ->assertForbidden();
});

it('renders matrix blocks in the requested order', function () {
    $this->fixture['owner'] = saveMatrixControllerBlocks($this->fixture, [
        [
            'title' => 'First Block',
            'innerText' => 'First text',
        ],
        [
            'title' => 'Second Block',
            'innerText' => 'Second text',
        ],
    ]);

    $this->fixture = refreshMatrixControllerFixture($this->fixture);
    [$first, $second] = matrixControllerNestedEntries($this->fixture)->values()->all();

    $blockHtml = postJson(action([MatrixController::class, 'renderBlocks']), [
        'entryIds' => [$second->id, $first->id],
        'siteId' => $this->fixture['siteId'],
        'namespace' => 'testNamespace',
    ])
        ->assertOk()
        ->assertJsonStructure(['blockHtml', 'headHtml', 'bodyHtml'])
        ->json('blockHtml');

    $secondPosition = strpos((string) $blockHtml, 'testNamespace[matrixField][entries][uid:'.$second->uid.']');
    $firstPosition = strpos((string) $blockHtml, 'testNamespace[matrixField][entries][uid:'.$first->uid.']');

    expect($blockHtml)->toContain('testNamespace[matrixField][entries][uid:'.$second->uid.']')
        ->toContain('testNamespace[matrixField][entries][uid:'.$first->uid.']')
        ->and($secondPosition)->not->toBeFalse()
        ->and($firstPosition)->not->toBeFalse()
        ->and($secondPosition)->toBeLessThan($firstPosition);
});
