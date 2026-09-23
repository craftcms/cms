<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Http\Controllers\Import\ImportPlansController;
use CraftCms\Cms\Import\ImportPlan;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\SystemMessage\Import\SystemMessageImporter;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Import\UserImporter;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::find()->one());

    // BaseImporter::resolvedFilePath() resolves everything against @root, which points at the
    // Testbench skeleton during tests - point it at the package so the fixtures are reachable
    $this->originalRoot = Aliases::get('@root');
    Aliases::set('@root', dirname(__DIR__, 4));
    $this->file = 'tests/Fixtures/Import/entries-plain-text.json';

    $innerPlainTextField = Field::factory()->create([
        'name' => 'Inner Plain Text',
        'handle' => 'innerPlainText',
        'type' => PlainText::class,
    ]);

    $innerEntryType = EntryType::factory()
        ->withField($innerPlainTextField)
        ->create(['name' => 'Inner ET', 'handle' => 'innerEt', 'hasTitleField' => true]);

    $innerMatrixField = Field::factory()->create([
        'name' => 'Inner Matrix',
        'handle' => 'innerMatrix',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$innerEntryType->id]],
    ]);

    Fields::refreshFields();

    $outerEntryType = EntryType::factory()
        ->withFieldLayout(
            FieldLayout::factory()
                ->withContentTab([
                    new EntryTitleField(['uid' => Str::uuid()->toString(), 'required' => true]),
                    CustomField::make($innerMatrixField->handle),
                ])
                ->create()
        )
        ->create(['name' => 'Outer ET', 'handle' => 'outerEt', 'hasTitleField' => true]);

    $this->outerMatrixField = Field::factory()->create([
        'name' => 'Outer Matrix',
        'handle' => 'outerMatrix',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$outerEntryType->id]],
    ]);

    Fields::refreshFields();

    $this->entryType = EntryType::factory()
        ->withFieldLayout(
            FieldLayout::factory()
                ->withContentTab([
                    new EntryTitleField(['uid' => Str::uuid()->toString(), 'required' => true]),
                    CustomField::make($this->outerMatrixField->handle),
                ])
                ->create()
        )
        ->create(['name' => 'Fixture Type', 'handle' => 'fixtureType', 'hasTitleField' => true]);

    $this->section = Section::factory()
        ->withEntryTypes($this->entryType)
        ->create(['handle' => 'fixtureSection', 'minAuthors' => 0]);

    EntryTypes::refreshEntryTypes();
    Fields::refreshFields();

    $this->entryStep = fn (array $settings = [], array $overrides = []) => array_merge([
        'uid' => Str::uuid7()->toString(),
        'type' => EntryImporter::class,
        'file' => $this->file,
        'transformer' => null,
        'batchSize' => null,
        'settings' => array_merge([
            'site' => Sites::getPrimarySite()->handle,
            'section' => $this->section->handle,
            'entryType' => $this->entryType->handle,
        ], $settings),
    ], $overrides);

    $this->saveImportPlan = fn (array $steps, array $overrides = []) => $this->postJson(
        action([ImportPlansController::class, 'store']),
        array_merge([
            'name' => 'Fixture Import',
            'handle' => 'fixtureImport',
            'steps' => $steps,
        ], $overrides),
    );
});

afterEach(function () {
    Aliases::set('@root', $this->originalRoot);
});

it('saves an import with several steps of different types in one request', function () {
    ($this->saveImportPlan)([
        ($this->entryStep)(),
        [
            'uid' => Str::uuid7()->toString(),
            'type' => SystemMessageImporter::class,
            'file' => $this->file,
            'transformer' => null,
            'batchSize' => 25,
            'settings' => [],
        ],
    ])->assertOk();

    $saved = app(ImportPlan::class)->getImportPlanByHandle('fixtureImport');

    expect($saved)->not->toBeNull()
        ->and($saved->steps)->toHaveCount(2)
        ->and($saved->steps[0]['type'])->toBe(EntryImporter::class)
        ->and($saved->steps[1]['type'])->toBe(SystemMessageImporter::class)
        ->and($saved->steps[1]['batchSize'])->toBe(25)
        ->and($saved->getImporters())->toHaveCount(2);
});

it('rejects an import with no steps', function () {
    ($this->saveImportPlan)([])
        ->assertStatus(400)
        ->assertJsonPath('errors.steps.0', 'An import plan needs at least one step.');

    expect(app(ImportPlan::class)->getImportPlanByHandle('fixtureImport'))->toBeNull();
});

it('reports a step’s own validation errors against that step', function () {
    $step = ($this->entryStep)([], ['file' => 'tests/Fixtures/Import/does-not-exist.json']);

    $response = ($this->saveImportPlan)([$step])->assertStatus(400);

    // the key is a flat dotted string, which is how the step list on the edit screen
    // matches an error back to the row that caused it
    expect(array_keys($response->json('errors')))->toContain("steps.{$step['uid']}.file");

    expect(app(ImportPlan::class)->getImportPlanByHandle('fixtureImport'))->toBeNull();
});

it('validates a draft step without saving the import', function () {
    $response = $this->postJson(action([ImportPlansController::class, 'validateStep']), [
        'step' => ($this->entryStep)(),
    ]);

    $response->assertOk();
    expect(app(ImportPlan::class)->getImportPlanByHandle('fixtureImport'))->toBeNull();
});

it('reports a draft step’s validation errors before the import is ever saved', function () {
    $step = ($this->entryStep)([], ['file' => 'tests/Fixtures/Import/does-not-exist.json']);

    $response = $this->postJson(action([ImportPlansController::class, 'validateStep']), [
        'step' => $step,
    ]);

    $response->assertStatus(422);
    expect(array_keys($response->json('errors')))->toContain('file');
});

it('returns a container field’s destination columns for an unsaved step', function () {
    $response = $this->postJson(action([ImportPlansController::class, 'nestedMappingCols']), [
        'step' => ($this->entryStep)(),
        'fieldUid' => $this->outerMatrixField->uid,
        'fieldHandle' => 'outerMatrix',
    ]);

    $response->assertOk();
    expect($response->json('fieldName'))->toBe('Outer Matrix');
    expect($response->json('groups.0.providerName'))->toBe('Outer ET');

    // The columns are addressed from the root of the mapping trees, prefixed with the
    // container's own handle — that's what lets the panel edit the step's state in place.
    $handles = array_column($response->json('groups.0.destinationCols'), 'prefixedHandle');
    expect($handles)->toContain('outerMatrix[outerEt][fields][innerMatrix]');
});

it('returns a step’s mapping structure without the import being saved', function () {
    $response = $this->postJson(action([ImportPlansController::class, 'stepMapping']), [
        'step' => ($this->entryStep)(),
    ]);

    $response->assertOk();
    expect($response->json('available'))->toBeTrue()
        ->and($response->json('destinationCols'))->not->toBeEmpty()
        ->and($response->json('sourceDataCols'))->not->toBeEmpty()
        ->and($response->json('values.map'))->toBe([])
        // the guesses the mapping panel fills in and highlights as best guesses
        ->and($response->json('suggestions'))->not->toBeEmpty()
        ->and($response->json('suggestions.title'))->toBe('title');

    expect(app(ImportPlan::class)->getImportPlanByHandle('fixtureImport'))->toBeNull();
});

it('builds a step’s settings form from the posted draft step', function () {
    $response = $this->postJson(action([ImportPlansController::class, 'stepSettings']), [
        'step' => ($this->entryStep)(),
    ]);

    $response->assertOk();
    expect($response->json('form.nodes'))->not->toBeEmpty()
        ->and($response->json('form.values.type'))->toBe(EntryImporter::class);
});

it('reports a step with a resolved field layout as mappable', function () {
    $response = $this->postJson(action([ImportPlansController::class, 'stepSettings']), [
        'step' => ($this->entryStep)(),
    ]);

    expect($response->json('canMap'))->toBeTrue();
});

it('reports a step whose field layout hasn’t resolved as not mappable', function () {
    // An element importer's destination columns come from the field layout its entry type
    // resolves, so there is nothing to map onto until one is chosen.
    $response = $this->postJson(action([ImportPlansController::class, 'stepSettings']), [
        'step' => ($this->entryStep)(['entryType' => null]),
    ]);

    expect($response->json('canMap'))->toBeFalse();
});

it('reports a step with no file as not mappable', function () {
    $response = $this->postJson(action([ImportPlansController::class, 'stepSettings']), [
        'step' => ($this->entryStep)([], ['file' => null]),
    ]);

    expect($response->json('canMap'))->toBeFalse();
});

it('reports an unsaved users step as mappable', function () {
    // Users have one layout for the element type rather than one per setting, so it has to
    // resolve while the step is being built — not only when the import is saved.
    $response = $this->postJson(action([ImportPlansController::class, 'stepSettings']), [
        'step' => [
            'uid' => Str::uuid7()->toString(),
            'type' => UserImporter::class,
            'file' => $this->file,
            'transformer' => null,
            'batchSize' => null,
            'settings' => ['site' => Sites::getPrimarySite()->handle],
        ],
    ]);

    expect($response->json('canMap'))->toBeTrue();
});

it('refuses to build the mapping for a step whose layout hasn’t resolved', function () {
    $response = $this->postJson(action([ImportPlansController::class, 'stepMapping']), [
        'step' => ($this->entryStep)(['entryType' => null]),
    ]);

    $response->assertOk();
    expect($response->json('available'))->toBeFalse()
        ->and($response->json('message'))->not->toBeEmpty();
});

it('persists both levels’ keepMissingNestedElements decisions with the step', function () {
    // The mapping panel writes into the step client-side, so the whole nested shape
    // arrives in the one save — there's no per-container round trip.
    ($this->saveImportPlan)([
        ($this->entryStep)([
            'map' => ['outerMatrix' => ['outerEt' => []]],
            'keepMissingNestedElements' => [
                'outerMatrix' => [
                    '__keep__' => '1',
                    'outerEt' => [
                        'fields' => [
                            'innerMatrix' => ['__keep__' => '1'],
                        ],
                    ],
                ],
            ],
        ]),
    ])->assertOk();

    $importer = app(ImportPlan::class)->getImportPlanByHandle('fixtureImport')->getImporters()->first();

    expect($importer->keepMissingNestedElements)->toBe([
        'outerMatrix' => [
            '__keep__' => 1,
            'outerEt' => [
                'fields' => [
                    'innerMatrix' => ['__keep__' => 1],
                ],
            ],
        ],
    ]);
});

it('persists match criteria and clearable items alongside the map', function () {
    ($this->saveImportPlan)([
        ($this->entryStep)([
            'map' => ['title' => 'Name'],
            'matchCriteria' => ['title' => '1'],
            'clearableItems' => ['title' => '1'],
        ]),
    ])->assertOk();

    $importer = app(ImportPlan::class)->getImportPlanByHandle('fixtureImport')->getImporters()->first();

    expect($importer->matchCriteria)->toBe(['title' => 1])
        ->and($importer->clearableItems)->toBe(['title' => 1]);
});

it('still decodes JSON-encoded container branches on save', function () {
    // File-based configs and plugins can still post the shape the Twig screen's hidden
    // inputs produced.
    ($this->saveImportPlan)([
        ($this->entryStep)([
            'map' => ['outerMatrix' => json_encode(['outerEt' => ['title' => 'Title']])],
        ]),
    ])->assertOk();

    $importer = app(ImportPlan::class)->getImportPlanByHandle('fixtureImport')->getImporters()->first();

    expect($importer->map)->toBe([
        'outerMatrix' => ['outerEt' => ['title' => 'Title']],
    ]);
});

it('saves and maps a model importer step', function () {
    // ModelImporter has no site, fieldLayout or keepMissingNestedElements, so this is the
    // path that has to stay clear of the ElementImporter-only properties
    ($this->saveImportPlan)([
        [
            'uid' => Str::uuid7()->toString(),
            'type' => SystemMessageImporter::class,
            'file' => $this->file,
            'transformer' => null,
            'batchSize' => null,
            'settings' => [
                'map' => ['subject' => 'incomingSubject'],
                'matchCriteria' => ['key' => 'key'],
            ],
        ],
    ])->assertOk();

    $importer = app(ImportPlan::class)->getImportPlanByHandle('fixtureImport')->getImporters()->first();

    expect($importer)->toBeInstanceOf(SystemMessageImporter::class)
        ->and($importer->map)->toBe(['subject' => 'incomingSubject'])
        ->and($importer->matchCriteria)->toBe(['key' => 'key']);
});
