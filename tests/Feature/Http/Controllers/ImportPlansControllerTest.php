<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Http\Controllers\Import\ImportPlansController;
use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Import\Events\ImportPlanSaved;
use CraftCms\Cms\Import\ImportPlan;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\SystemMessage\Import\SystemMessageImporter;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Import\UserImporter;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::find()->one());

    // resolvedSourcePath() resolves against @root (the Testbench skeleton), so point it at the package.
    $this->originalRoot = Aliases::get('@root');
    Aliases::set('@root', dirname(__DIR__, 4));
    $this->source = 'tests/Fixtures/Import/entries-plain-text.json';

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
        'source' => $this->source,
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
    $this->createImportPlan = fn (string $name, string $handle) => expect(
        app(ImportPlan::class)->saveImportPlan(
            new ImportPlanData(['editable' => true])
                ->name($name)
                ->handle($handle)
                ->steps([($this->entryStep)()]),
        ),
    )->toBeTrue();
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
            'source' => $this->source,
            'transformer' => null,
            'batchSize' => 25,
            'settings' => [],
        ],
    ])->assertOk();

    $saved = app(ImportPlan::class)->getImportPlanByHandle('fixtureImport');

    expect($saved)->not->toBeNull()
        ->and($saved->steps)->toHaveCount(2)
        ->and($saved->steps[0]::class)->toBe(EntryImporter::class)
        ->and($saved->steps[1]::class)->toBe(SystemMessageImporter::class)
        ->and($saved->steps[1]->batchSize)->toBe(25)
        ->and($saved->steps)->toHaveCount(2);
});

it('rejects an import with no steps', function () {
    ($this->saveImportPlan)([])
        ->assertStatus(400)
        ->assertJsonPath('errors.steps.0', 'An import plan needs at least one step.');

    expect(app(ImportPlan::class)->getImportPlanByHandle('fixtureImport'))->toBeNull();
});

it('reports a step’s own validation errors against that step', function () {
    $step = ($this->entryStep)([], ['source' => 'tests/Fixtures/Import/does-not-exist.json']);

    $response = ($this->saveImportPlan)([$step])->assertStatus(400);

    expect(array_keys($response->json('errors')))->toContain("steps.{$step['uid']}.source");

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
    $step = ($this->entryStep)([], ['source' => 'tests/Fixtures/Import/does-not-exist.json']);

    $response = $this->postJson(action([ImportPlansController::class, 'validateStep']), [
        'step' => $step,
    ]);

    $response->assertStatus(422);
    expect(array_keys($response->json('errors')))->toContain('source');
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

    $handles = array_column($response->json('groups.0.destinationCols'), 'prefixedHandle');
    expect($handles)->toContain('outerMatrix[outerEt][fields][innerMatrix]');
});

it('rejects nested mapping columns for a step whose importer can’t be built', function () {
    $this->postJson(action([ImportPlansController::class, 'nestedMappingCols']), [
        'step' => ($this->entryStep)(['site' => 'no-such-site']),
        'fieldUid' => $this->outerMatrixField->uid,
        'fieldHandle' => 'outerMatrix',
    ])->assertStatus(400);
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

it('applies only declared settings when building a draft step', function () {
    actingAs(UserModel::factory()->withPermissions(['accessCp', 'viewImportPlans'])->createElement());

    $this->postJson(action([ImportPlansController::class, 'stepSettings']), [
        'step' => ($this->entryStep)([
            'importItem' => [
                'title' => 'Injected entry',
                'sectionId' => $this->section->handle,
                'typeId' => $this->entryType->handle,
            ],
        ]),
    ])->assertOk();

    expect(EntryElement::find()->title('Injected entry')->status(null)->exists())->toBeFalse();
});

it('reports a step with a resolved field layout as mappable', function () {
    $response = $this->postJson(action([ImportPlansController::class, 'stepSettings']), [
        'step' => ($this->entryStep)(),
    ]);

    expect($response->json('canMap'))->toBeTrue();
});

it('reports a step whose field layout hasn’t resolved as not mappable', function () {
    $response = $this->postJson(action([ImportPlansController::class, 'stepSettings']), [
        'step' => ($this->entryStep)(['entryType' => null]),
    ]);

    expect($response->json('canMap'))->toBeFalse();
});

it('reports a step with no source as not mappable', function () {
    $response = $this->postJson(action([ImportPlansController::class, 'stepSettings']), [
        'step' => ($this->entryStep)([], ['source' => null]),
    ]);

    expect($response->json('canMap'))->toBeFalse()
        ->and($response->json('sourceError'))->toBeNull();
});

it('reports an unsaved users step as mappable', function () {
    $response = $this->postJson(action([ImportPlansController::class, 'stepSettings']), [
        'step' => [
            'uid' => Str::uuid7()->toString(),
            'type' => UserImporter::class,
            'source' => $this->source,
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

it('refuses to build the mapping for a step whose source can’t be used, with the reason', function () {
    $response = $this->postJson(action([ImportPlansController::class, 'stepMapping']), [
        'step' => ($this->entryStep)([], ['source' => '@nope/entries.json']),
    ]);

    $response->assertOk();
    expect($response->json('available'))->toBeFalse()
        ->and($response->json('message'))->toBe('The alias in “@nope/entries.json” isn’t defined.')
        ->and($response->json('attribute'))->toBe('source');
});

it('refuses to build the mapping for a step whose source can’t be parsed', function () {
    $response = $this->postJson(action([ImportPlansController::class, 'stepMapping']), [
        'step' => ($this->entryStep)([], ['source' => 'tests/Fixtures/Import/broken.xml']),
    ]);

    $response->assertOk();
    expect($response->json('available'))->toBeFalse()
        ->and($response->json('message'))->toBe('The data in “tests/Fixtures/Import/broken.xml” couldn’t be read.')
        ->and($response->json('attribute'))->toBe('source');
});

it('reports a step with an undefined alias as not mappable instead of failing', function () {
    $response = $this->postJson(action([ImportPlansController::class, 'stepSettings']), [
        'step' => ($this->entryStep)([], ['source' => '@nope/entries.json']),
    ]);

    $response->assertOk();
    expect($response->json('canMap'))->toBeFalse()
        ->and($response->json('sourceError'))->toBe('The alias in “@nope/entries.json” isn’t defined.');
});

it('persists both levels’ keepMissingNestedElements decisions with the step', function () {
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

    $importer = app(ImportPlan::class)->getImportPlanByHandle('fixtureImport')->steps[0];

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

    $importer = app(ImportPlan::class)->getImportPlanByHandle('fixtureImport')->steps[0];

    expect($importer->matchCriteria)->toBe(['title' => 1])
        ->and($importer->clearableItems)->toBe(['title' => 1]);
});

it('still decodes JSON-encoded container branches on save', function () {
    ($this->saveImportPlan)([
        ($this->entryStep)([
            'map' => ['outerMatrix' => json_encode(['outerEt' => ['title' => 'Title']])],
        ]),
    ])->assertOk();

    $importer = app(ImportPlan::class)->getImportPlanByHandle('fixtureImport')->steps[0];

    expect($importer->map)->toBe([
        'outerMatrix' => ['outerEt' => ['title' => 'Title']],
    ]);
});

it('saves and maps a model importer step', function () {
    ($this->saveImportPlan)([
        [
            'uid' => Str::uuid7()->toString(),
            'type' => SystemMessageImporter::class,
            'source' => $this->source,
            'transformer' => null,
            'batchSize' => null,
            'settings' => [
                'map' => ['subject' => 'incomingSubject'],
                'matchCriteria' => ['key' => 'key'],
            ],
        ],
    ])->assertOk();

    $importer = app(ImportPlan::class)->getImportPlanByHandle('fixtureImport')->steps[0];

    expect($importer)->toBeInstanceOf(SystemMessageImporter::class)
        ->and($importer->map)->toBe(['subject' => 'incomingSubject'])
        ->and($importer->matchCriteria)->toBe(['key' => 'key']);
});

it('saves the order the editable import plans are put in', function () {
    ($this->createImportPlan)('Alpha', 'alpha');
    ($this->createImportPlan)('Bravo', 'bravo');
    ($this->createImportPlan)('Charlie', 'charlie');

    $plans = app(ImportPlan::class);
    $uids = array_map(
        fn (string $handle) => $plans->getImportPlanByHandle($handle)->uid,
        ['charlie', 'alpha', 'bravo'],
    );

    postJson(action([ImportPlansController::class, 'reorder']), ['uids' => $uids])->assertOk();

    expect($plans->getEditableImportPlans()->pluck('handle')->values()->all())
        ->toBe(['charlie', 'alpha', 'bravo']);
});

it('adds new and duplicated import plans after the existing ones', function () {
    ($this->createImportPlan)('Zulu', 'zulu');
    ($this->createImportPlan)('Alpha', 'alpha');

    $plans = app(ImportPlan::class);

    postJson(action([ImportPlansController::class, 'duplicate']), [
        'uid' => $plans->getImportPlanByHandle('zulu')->uid,
    ])->assertOk();

    expect($plans->getEditableImportPlans()->pluck('handle')->values()->all())
        ->toBe(['zulu', 'alpha', 'zulu2']);
});

it('saves a duplicated import plan like any other new one, with fresh step uids', function () {
    ($this->createImportPlan)('Zulu', 'zulu');
    $original = app(ImportPlan::class)->getImportPlanByHandle('zulu');

    Event::fake([ImportPlanSaved::class]);

    postJson(action([ImportPlansController::class, 'duplicate']), ['uid' => $original->uid])->assertOk();

    Event::assertDispatched(fn (ImportPlanSaved $event) => $event->isNew
        && $event->importPlan->handle === 'zulu2'
        && array_intersect(
            array_map(fn ($step) => $step->uid, $event->importPlan->steps),
            array_map(fn ($step) => $step->uid, $original->steps),
        ) === []);
});

it('rejects reordering an import plan that doesn’t exist', function () {
    postJson(action([ImportPlansController::class, 'reorder']), [
        'uids' => [Str::uuid7()->toString()],
    ])->assertJsonValidationErrors('uids.0');
});

it('forbids reordering import plans without permission to save them', function () {
    ($this->createImportPlan)('Alpha', 'alpha');
    $uid = app(ImportPlan::class)->getImportPlanByHandle('alpha')->uid;

    actingAs(UserModel::factory()->withPermissions(['accessCp'])->createElement());

    postJson(action([ImportPlansController::class, 'reorder']), [
        'uids' => [$uid],
    ])->assertForbidden();
});
