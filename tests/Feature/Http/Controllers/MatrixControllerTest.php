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
use CraftCms\Cms\Entry\Events\EntryTypesResolving;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Events\EntryTypesForFieldResolving;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\FieldLayoutCompiler;
use CraftCms\Cms\Http\Controllers\MatrixController;
use CraftCms\Cms\Support\Facades\Elements as ElementsFacade;
use CraftCms\Cms\Support\Facades\EntryTypes as EntryTypesFacade;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Tests\Support\MatrixControllerFixture;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\Workflow\Workflows;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(UserElement::findOne());
    $this->fixture = MatrixControllerFixture::create();
});

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
    postJson(action([MatrixController::class, 'createEntry']), MatrixControllerFixture::payload($this->fixture, [
        'ownerId' => 999999,
    ]))->assertBadRequest()
        ->assertJsonPath('message', 'Invalid owner ID, element type, or site ID.');
});

it('rejects invalid matrix field ids when creating a matrix entry', function () {
    postJson(action([MatrixController::class, 'createEntry']), MatrixControllerFixture::payload($this->fixture, [
        'fieldId' => 999999,
    ]))->assertBadRequest()
        ->assertJsonPath('message', 'Invalid Matrix field ID: 999999');
});

it('rejects invalid entry type ids when creating a matrix entry', function () {
    postJson(action([MatrixController::class, 'createEntry']), MatrixControllerFixture::payload($this->fixture, [
        'entryTypeId' => 999999,
    ]))->assertBadRequest()
        ->assertJsonPath('message', 'Invalid entry type ID: 999999');
});

it('rejects invalid site ids when creating a matrix entry', function () {
    postJson(action([MatrixController::class, 'createEntry']), MatrixControllerFixture::payload($this->fixture, [
        'siteId' => 999999,
    ]))->assertBadRequest()
        ->assertJsonPath('message', 'Invalid owner ID, element type, or site ID.');
});

it('returns the new block as form nodes when given a control path', function () {
    // The Form control renders blocks with FormNodeList, so it asks for nodes
    // rather than the rendered block HTML the legacy stack splices in.
    $response = postJson(action([MatrixController::class, 'createEntry']), MatrixControllerFixture::payload($this->fixture, [
        'path' => ['fields', 'matrixField'],
    ]))
        ->assertOk()
        ->assertJsonStructure(['uid', 'type', 'form' => ['scope', 'refreshable', 'nodes'], 'values']);

    $entry = MatrixControllerFixture::entries($this->fixture)->sole();

    expect($entry->typeId)->toBe($this->fixture['entryType']->id)
        ->and($entry->fieldId)->toBe($this->fixture['field']->id)
        ->and($entry->getOwnerId())->toBe($this->fixture['owner']->id)
        ->and($entry->draftId)->not->toBeNull();

    // The server minted the identity, and it is the bare UUID the Control keys
    // blocks by — no `uid:` prefix, nothing for the browser to reconcile.
    expect($response->json('uid'))->toBe($entry->uid)
        ->and($response->json('type'))->toBe($this->fixture['entryType']->handle)
        ->and($response->json('form.scope'))
        ->toBe(['fields', 'matrixField', 'entries', $entry->uid])
        ->and($response->json('form.nodes'))->not->toBeEmpty()
        ->and($response->json('values'))->not->toBeEmpty();
    ;
});

it('refuses an entry type the field does not offer', function () {
    // It would save happily, and then the field couldn't render what it got back:
    // the Matrix Control rejects a block whose type it doesn't offer, which takes
    // the whole edit screen down with it.
    $other = EntryType::factory()->create([
        'name' => 'Not On This Field',
        'handle' => 'notOnThisField',
    ]);

    postJson(action([MatrixController::class, 'createEntry']), MatrixControllerFixture::payload($this->fixture, [
        'entryTypeId' => $other->id,
    ]))->assertBadRequest();

    expect(MatrixControllerFixture::entries($this->fixture))->toHaveCount(0);
});

it('returns a failure response when saving a new matrix draft fails', function () {
    app()->instance(Drafts::class, new readonly class(app(Elements::class), app(DraftActivity::class), app(Workflows::class)) extends Drafts
    {
        public function saveElementAsDraft(ElementInterface $element, ?int $creatorId = null, ?string $name = null, ?string $notes = null, bool $markAsSaved = true): bool
        {
            return false;
        }
    });

    postJson(action([MatrixController::class, 'createEntry']), MatrixControllerFixture::payload($this->fixture))->assertBadRequest()
        ->assertJsonPath('message', mb_ucfirst(t('Couldn’t create {type}.', [
            'type' => EntryElement::lowerDisplayName(),
        ])));
});

it('rejects invalid duplicate source ids when creating a matrix entry', function () {
    postJson(action([MatrixController::class, 'createEntry']), MatrixControllerFixture::payload($this->fixture, [
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

    $victimFixture['owner'] = MatrixControllerFixture::saveBlocks($victimFixture, [[
        'title' => 'Victim Block',
        'innerText' => 'Victim text',
    ]]);
    $victimFixture = MatrixControllerFixture::refresh($victimFixture);
    $source = MatrixControllerFixture::entries($victimFixture)->sole();

    postJson(action([MatrixController::class, 'createEntry']), MatrixControllerFixture::payload($this->fixture, [
        'duplicate' => $source->id,
    ]))->assertBadRequest()
        ->assertJsonPath('message', "Invalid source element ID: $source->id");
});

it('forbids duplicating a matrix entry when authorization fails', function () {
    $this->fixture['owner'] = MatrixControllerFixture::saveBlocks($this->fixture, [[
        'title' => 'Source Block',
        'innerText' => 'Source text',
    ]]);
    $this->fixture = MatrixControllerFixture::refresh($this->fixture);
    $source = MatrixControllerFixture::entries($this->fixture)->sole();

    Gate::before(function ($user, string $ability) {
        if ($ability === 'duplicateAsDraft') {
            return false;
        }

        return null;
    });

    postJson(action([MatrixController::class, 'createEntry']), MatrixControllerFixture::payload($this->fixture, [
        'duplicate' => $source->id,
    ]))
        ->assertForbidden();
});

it('forbids duplicating a matrix entry when viewing the source is not authorized', function () {
    $this->fixture['owner'] = MatrixControllerFixture::saveBlocks($this->fixture, [[
        'title' => 'Source Block',
        'innerText' => 'Source text',
    ]]);
    $this->fixture = MatrixControllerFixture::refresh($this->fixture);
    $source = MatrixControllerFixture::entries($this->fixture)->sole();

    Gate::before(function ($user, string $ability) {
        if ($ability === 'view') {
            return false;
        }

        return null;
    });

    postJson(action([MatrixController::class, 'createEntry']), MatrixControllerFixture::payload($this->fixture, [
        'duplicate' => $source->id,
    ]))
        ->assertForbidden();
});

it('duplicates an existing matrix entry and returns its form', function () {
    $this->fixture['owner'] = MatrixControllerFixture::saveBlocks($this->fixture, [[
        'title' => 'Source Block',
        'innerText' => 'Source text',
    ]]);
    $this->fixture = MatrixControllerFixture::refresh($this->fixture);
    $source = MatrixControllerFixture::entries($this->fixture)->sole();

    $response = postJson(action([MatrixController::class, 'createEntry']), MatrixControllerFixture::payload($this->fixture, [
        'duplicate' => $source->id,
    ]))
        ->assertOk()
        ->assertJsonStructure(['uid', 'form', 'values']);

    $entries = MatrixControllerFixture::entries($this->fixture);
    $duplicate = $entries->first(fn (EntryElement $entry) => $entry->id !== $source->id);

    expect($entries)->toHaveCount(2)
        ->and($duplicate)->not->toBeNull()
        ->and($duplicate->id)->not->toBe($source->id)
        ->and($duplicate->getFieldValue('innerText'))->toBe('Source text');

    expect($response->json('uid'))->toBe($duplicate->uid)
        ->and($response->json('values.fields.matrixField.entries.'.$duplicate->uid.'.fields.innerText'))->toBe('Source text');
});

it('returns a failure response when duplicating a matrix entry fails validation', function () {
    $this->fixture['owner'] = MatrixControllerFixture::saveBlocks($this->fixture, [[
        'title' => 'Source Block',
        'innerText' => 'Source text',
    ]]);
    $this->fixture = MatrixControllerFixture::refresh($this->fixture);
    $source = MatrixControllerFixture::entries($this->fixture)->sole();

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

    postJson(action([MatrixController::class, 'createEntry']), MatrixControllerFixture::payload($this->fixture, [
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

it('rejects render blocks requests for entries outside matrix fields', function () {
    $entry = EntryModel::factory()->createElement();

    postJson(action([MatrixController::class, 'renderBlocks']), [
        'entryIds' => [$entry->id],
        'siteId' => $entry->siteId,
        'path' => ['fields', 'matrixField'],
    ])->assertBadRequest()
        ->assertJsonPath('message', 'Entry must belong to a Matrix field.');
});

it('forbids rendering matrix blocks when authorization fails', function () {
    $this->fixture['owner'] = MatrixControllerFixture::saveBlocks($this->fixture, [[
        'title' => 'First Block',
        'innerText' => 'First text',
    ]]);
    $this->fixture = MatrixControllerFixture::refresh($this->fixture);
    $entry = MatrixControllerFixture::entries($this->fixture)->sole();

    Gate::before(function ($user, string $ability) {
        if ($ability === 'view') {
            return false;
        }

        return null;
    });

    postJson(action([MatrixController::class, 'renderBlocks']), [
        'entryIds' => [$entry->id],
        'siteId' => $this->fixture['siteId'],
        'path' => ['fields', 'matrixField'],
    ])
        ->assertForbidden();
});

it('saves a draft owner that holds a block minted before the draft existed', function () {
    // `matrix/create-entry` persists the new block as a draft of its own, owned
    // by whichever element the form was compiled against. Edit the owner
    // afterwards and it becomes a provisional draft — leaving a block that is
    // already a draft and still primarily owned by the canonical.
    $response = postJson(action([MatrixController::class, 'createEntry']), MatrixControllerFixture::payload($this->fixture))
        ->assertOk();

    $block = MatrixControllerFixture::entries($this->fixture)->sole();

    expect($block->getIsDraft())->toBeTrue()
        ->and($block->getPrimaryOwnerId())->toBe($this->fixture['owner']->id);

    $draft = app(Drafts::class)->createDraft($this->fixture['owner'], provisional: true);
    $draft->setFieldValueFromRequest($this->fixture['field']->handle, [
        'entries' => ["uid:{$block->uid}" => [
            'type' => $this->fixture['entryType']->handle,
            'title' => 'Block title',
            'enabled' => true,
            'fields' => ['innerText' => 'Typed after the draft appeared'],
        ]],
        'sortOrder' => [$block->uid],
    ]);

    expect(ElementsFacade::saveElement($draft))->toBeTrue();
    expect($response->json('uid'))->toBe($block->uid);
});

it('badges a block’s own field when that block was edited through a draft', function () {
    $this->fixture['field']->viewMode = Matrix::VIEW_MODE_BLOCKS;
    // A block that exists on the canonical owner, then edited through a
    // provisional draft, is duplicated as a draft with a canonical behind it —
    // which is what gives it something to be "modified" against.
    $this->fixture['owner'] = MatrixControllerFixture::saveBlocks($this->fixture, [[
        'title' => 'Block',
        'innerText' => 'Original',
    ]]);
    $this->fixture = MatrixControllerFixture::refresh($this->fixture);
    $canonicalBlock = MatrixControllerFixture::entries($this->fixture)->sole();

    $draft = app(Drafts::class)->createDraft($this->fixture['owner'], provisional: true);
    $draft->setFieldValueFromRequest($this->fixture['field']->handle, [
        'entries' => ["uid:{$canonicalBlock->uid}" => [
            'type' => $this->fixture['entryType']->handle,
            'title' => 'Block',
            'enabled' => true,
            'fields' => ['innerText' => 'Changed in the draft'],
        ]],
        'sortOrder' => [$canonicalBlock->uid],
    ]);
    expect(ElementsFacade::saveElement($draft))->toBeTrue();

    $block = collect(EntryElement::find()
        ->fieldId($this->fixture['field']->id)
        ->ownerId($draft->id)
        ->siteId($this->fixture['siteId'])
        ->drafts(null)
        ->status(null)
        ->all())->sole();

    expect($block->getIsCanonical())->toBeFalse()
        ->and($block->isFieldModified('innerText'))->toBeTrue();

    // What `Matrix::formControl()` actually compiles each block against.
    $value = $draft->getFieldValue($this->fixture['field']->handle);
    $compiled = (clone $value)
        ->drafts(null)
        ->canonicalsOnly()
        ->status(null)
        ->limit(null)
        ->all();

    expect($compiled)->toHaveCount(1)
        ->and($compiled[0]->id)->toBe($block->id)
        ->and($compiled[0]->getIsCanonical())->toBeFalse()
        ->and($compiled[0]->isFieldModified('innerText'))->toBeTrue();

    // And the compiled form says so, which is what puts the badge on screen.
    $payload = app(FieldLayoutCompiler::class)->compile(
        $draft->getFieldLayout(),
        $draft,
        new UiContext,
    );
    $statuses = [];
    $collect = function (array $node) use (&$collect, &$statuses): void {
        if (! empty($node['props']['status'])) {
            $statuses[implode('.', $node['control']['path'] ?? ['?'])] = $node['props']['status'];
        }

        foreach ($node['control']['forms'] ?? [] as $form) {
            foreach ($form['nodes'] ?? [] as $child) {
                $collect($child);
            }
        }

        foreach ($node['children'] ?? [] as $child) {
            $collect($child);
        }
    };

    foreach (json_decode(json_encode($payload), true)['nodes'] as $node) {
        $collect($node);
    }

    // The block's own field carries a badge of its own — that's what makes an
    // edit inside a block visible without the owner's field claiming it. Blocks
    // are keyed by their canonical identity here, not the derivative's uid.
    $handle = $this->fixture['field']->handle;
    $keys = array_keys($statuses);

    expect($statuses)->toHaveCount(2)
        ->and($keys[0])->toBe("fields.{$handle}")
        ->and($keys[1])->toMatch("/^fields\\.{$handle}\\.entries\\.[-a-f0-9]+\\.fields\\.innerText$/")
        ->and(array_values($statuses))->toBe(['modified', 'modified']);
});

it('only creates event-added types for eligible authorized owners', function () {
    $this->fixture['owner'] = MatrixControllerFixture::saveBlocks($this->fixture, [[
        'title' => 'Existing disabled block',
        'innerText' => 'Existing text',
        'enabled' => false,
    ]]);
    $this->fixture = MatrixControllerFixture::refresh($this->fixture);
    $existing = MatrixControllerFixture::entries($this->fixture)->sole();
    $extra = EntryType::factory()->withField($this->fixture['innerField'])->create([
        'name' => 'Plugin type',
        'handle' => 'pluginType',
    ]);
    EntryTypesFacade::refreshEntryTypes();
    $extraType = EntryTypesFacade::getEntryTypeById($extra->id);
    $ownerId = $this->fixture['owner']->id;
    $fieldId = $this->fixture['field']->id;

    Event::listen(function (EntryTypesForFieldResolving $event) use ($ownerId, $fieldId, $existing, $extraType): void {
        if ($event->field->id === $fieldId && $event->element?->id === $ownerId &&
            in_array($existing->id, array_column($event->value, 'id'), true)) {
            $event->entryTypes[] = $extraType;
        }
    });

    $otherOwner = EntryModel::factory()
        ->forSection($this->fixture['section'])
        ->forEntryType($this->fixture['ownerType'])
        ->createElement();
    postJson(action([MatrixController::class, 'createEntry']), MatrixControllerFixture::payload($this->fixture, [
        'ownerId' => $otherOwner->id,
        'entryTypeId' => $extra->id,
        'path' => ['fields', 'matrixField'],
    ]))->assertBadRequest()
        ->assertJsonPath('message', "Entry type {$extra->id} is not available to Matrix field {$fieldId}.");

    $payload = MatrixControllerFixture::payload($this->fixture, ['entryTypeId' => $extra->id, 'path' => ['fields', 'matrixField']]);
    $response = postJson(action([MatrixController::class, 'createEntry']), $payload)
        ->assertOk()->assertJsonPath('type', 'pluginType');
    $created = MatrixControllerFixture::entries($this->fixture)->firstWhere('uid', $response->json('uid'));
    expect($created->typeId)->toBe($extra->id)
        ->and($created->getOwnerId())->toBe($ownerId);

    $this->fixture['field']->viewMode = Matrix::VIEW_MODE_BLOCKS;
    $control = $this->fixture['field']->formControl(new FieldContext(
        path: ['fields', 'matrixField'],
        value: $this->fixture['owner']->getFieldValue('matrixField'),
        element: $this->fixture['owner'],
    ));
    $props = $control->props($control->getValue());
    expect($props['createEntryTypes'])->toContain('pluginType')
        ->and($props['create']['entryTypeIds']['pluginType'] ?? null)->toBe($extra->id);

    Gate::before(fn ($user, string $ability): ?bool => $ability === 'save' ? false : null);
    postJson(action([MatrixController::class, 'createEntry']), $payload)->assertForbidden();
    expect(MatrixControllerFixture::entries($this->fixture))->toHaveCount(2);
});

it('renders and duplicates event-added entry types absent from creation choices', function () {
    $this->fixture['owner'] = MatrixControllerFixture::saveBlocks($this->fixture, [[
        'title' => 'Existing block',
        'innerText' => 'Retained content',
        'enabled' => false,
    ]]);
    $this->fixture = MatrixControllerFixture::refresh($this->fixture);
    $extra = EntryType::factory()->withField($this->fixture['innerField'])->create([
        'name' => 'Plugin type',
        'handle' => 'pluginType',
    ]);
    EntryTypesFacade::refreshEntryTypes();
    $extraType = EntryTypesFacade::getEntryTypeById($extra->id);
    $fieldId = $this->fixture['field']->id;
    Event::listen(function (EntryTypesResolving $event) use ($fieldId, $extraType): void {
        if ($event->entry->fieldId === $fieldId) {
            $event->entryTypes[] = $extraType;
        }
    });
    $entry = MatrixControllerFixture::entries($this->fixture)->sole();
    $entry->setTypeId($extra->id);
    $entry->setFieldValue('innerText', 'Retained content');
    expect(ElementsFacade::saveElement($entry))->toBeTrue();
    $this->fixture = MatrixControllerFixture::refresh($this->fixture);

    postJson(action([MatrixController::class, 'renderBlocks']), [
        'entryIds' => [$entry->id],
        'siteId' => $this->fixture['siteId'],
        'path' => ['fields', 'matrixField'],
    ])->assertOk()
        ->assertJsonPath('blocks.0.type', 'pluginType')
        ->assertJsonPath("blocks.0.values.fields.matrixField.entries.{$entry->uid}.fields.innerText", 'Retained content');

    $this->fixture['field']->viewMode = Matrix::VIEW_MODE_BLOCKS;
    $control = $this->fixture['field']->formControl(new FieldContext(
        path: ['fields', 'matrixField'],
        value: $this->fixture['owner']->getFieldValue('matrixField'),
        element: $this->fixture['owner'],
    ));
    $props = $control->props($control->getValue());
    expect(collect($props['entryTypes'])->firstWhere('value', 'pluginType')['label'])->toBe('Plugin type')
        ->and($props['createEntryTypes'])->not->toContain('pluginType')
        ->and($control->getValue()['entries'][$entry->uid]['enabled'])->toBeFalse();

    postJson(action([MatrixController::class, 'createEntry']), MatrixControllerFixture::payload($this->fixture, [
        'entryTypeId' => $extra->id,
        'path' => ['fields', 'matrixField'],
    ]))->assertBadRequest()
        ->assertJsonPath('message', "Entry type {$extra->id} is not available to Matrix field {$fieldId}.");

    $response = postJson(action([MatrixController::class, 'createEntry']), MatrixControllerFixture::payload($this->fixture, [
        'duplicate' => $entry->id,
        'entryTypeId' => $extra->id,
        'path' => ['fields', 'matrixField'],
    ]))->assertOk()->assertJsonPath('type', 'pluginType');
    $duplicate = MatrixControllerFixture::entries($this->fixture)->firstWhere('uid', $response->json('uid'));
    expect($duplicate->id)->not->toBe($entry->id)
        ->and($duplicate->getFieldValue('innerText'))->toBe('Retained content');
});

it('rejects legacy HTML requests without creating an entry when the adapter is absent', function () {
    $payload = MatrixControllerFixture::payload($this->fixture);
    unset($payload['path']);
    $payload['namespace'] = 'testNamespace';

    postJson(action([MatrixController::class, 'createEntry']), $payload)->assertBadRequest();
    expect(MatrixControllerFixture::entries($this->fixture))->toHaveCount(0);

    postJson(action([MatrixController::class, 'renderBlocks']), [
        'entryIds' => [999999],
        'siteId' => $this->fixture['siteId'],
        'namespace' => 'testNamespace',
    ])->assertBadRequest();
});

it('returns empty blocks when no entries match', function () {
    postJson(action([MatrixController::class, 'renderBlocks']), [
        'entryIds' => [999999],
        'siteId' => $this->fixture['siteId'],
        'path' => ['fields', 'matrixField'],
    ])->assertOk()->assertJsonPath('blocks', []);
});

it('returns block forms in the requested order', function () {
    MatrixControllerFixture::saveBlocks($this->fixture, [
        ['title' => 'First Block', 'innerText' => 'First text'],
        ['title' => 'Second Block', 'innerText' => 'Second text'],
    ]);
    [$first, $second] = MatrixControllerFixture::entries($this->fixture)->values()->all();

    $response = postJson(action([MatrixController::class, 'renderBlocks']), [
        'entryIds' => [$second->id, $first->id],
        'siteId' => $this->fixture['siteId'],
        'path' => ['fields', 'matrixField'],
    ])->assertOk();

    expect(array_column($response->json('blocks'), 'uid'))->toBe([$second->uid, $first->uid]);
});
