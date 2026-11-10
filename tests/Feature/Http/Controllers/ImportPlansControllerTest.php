<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
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
use CraftCms\Cms\Import\ImportHelper;
use CraftCms\Cms\Import\ImportPlans;
use CraftCms\Cms\Import\Jobs\ImportPipeline;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\SystemMessage\Import\SystemMessageImporter;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Import\UserImporter;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;

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
        app(ImportPlans::class)->saveImportPlan(
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

    $saved = app(ImportPlans::class)->getImportPlanByHandle('fixtureImport');

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

    expect(app(ImportPlans::class)->getImportPlanByHandle('fixtureImport'))->toBeNull();
});

it('saves whether each step is enabled', function () {
    ($this->saveImportPlan)([
        ($this->entryStep)(),
        ($this->entryStep)(overrides: ['enabled' => false]),
    ])->assertOk();

    $saved = app(ImportPlans::class)->getImportPlanByHandle('fixtureImport');

    expect(array_map(fn ($step) => $step->enabled, $saved->steps))->toBe([true, false]);
});

it('rejects an import whose steps are all disabled', function () {
    ($this->saveImportPlan)([($this->entryStep)(overrides: ['enabled' => false])])
        ->assertStatus(400)
        ->assertJsonPath('errors.steps.0', 'An import plan needs at least one enabled step.');

    expect(app(ImportPlans::class)->getImportPlanByHandle('fixtureImport'))->toBeNull();
});

it('reports a step’s own validation errors against that step', function () {
    $step = ($this->entryStep)([], ['source' => 'tests/Fixtures/Import/does-not-exist.json']);

    $response = ($this->saveImportPlan)([$step])->assertStatus(400);

    expect(array_keys($response->json('errors')))->toContain("steps.{$step['uid']}.source");

    expect(app(ImportPlans::class)->getImportPlanByHandle('fixtureImport'))->toBeNull();
});

it('validates a draft step without saving the import', function () {
    $response = $this->postJson(action([ImportPlansController::class, 'validateStep']), [
        'step' => ($this->entryStep)(),
    ]);

    $response->assertOk();
    expect(app(ImportPlans::class)->getImportPlanByHandle('fixtureImport'))->toBeNull();
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

    $outerEntryType = EntryTypes::getEntryTypeByHandle('outerEt');
    $innerMatrixUid = $outerEntryType->getFieldLayout()->getCustomFieldElements()[0]->uid;

    $handles = array_column($response->json('groups.0.destinationCols'), 'prefixedHandle');
    expect($handles)->toContain("outerMatrix[$outerEntryType->uid][fields][$innerMatrixUid]");
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

    expect(app(ImportPlans::class)->getImportPlanByHandle('fixtureImport'))->toBeNull();
});

it('offers a template option and the source columns as template variables when mapping a step', function () {
    $response = $this->postJson(action([ImportPlansController::class, 'stepMapping']), [
        'step' => ($this->entryStep)(),
    ]);

    $response->assertOk();
    expect($response->json('sourceDataCols.1'))->toBe(['label' => 'Use template', 'value' => ImportHelper::TEMPLATE_RULE])
        ->and(array_column($response->json('templateTextExpanderTriggers.0.options'), 'value'))->toContain('{title}')
        ->and($response->json('suggestions.title'))->toBe('title');
});

it('reports a template that can’t be parsed against its own mapping path', function () {
    $response = $this->postJson(action([ImportPlansController::class, 'validateStep']), [
        'step' => ($this->entryStep)([
            'map' => ['title' => ImportHelper::TEMPLATE_RULE],
            'templates' => ['title' => '{{ title'],
        ]),
    ]);

    $response->assertStatus(422);
    expect(array_keys($response->json('errors')))->toContain('settings.templates.title');
});

it('reports a missing template for a column mapped to one', function () {
    $response = $this->postJson(action([ImportPlansController::class, 'validateStep']), [
        'step' => ($this->entryStep)([
            'map' => ['title' => ImportHelper::TEMPLATE_RULE],
            'templates' => ['title' => ' '],
        ]),
    ]);

    $response->assertStatus(422);
    expect(array_keys($response->json('errors')))->toContain('settings.templates.title');
});

it('builds a step’s settings form from the posted draft step', function () {
    $response = $this->postJson(action([ImportPlansController::class, 'stepSettings']), [
        'step' => ($this->entryStep)(),
    ]);

    $response->assertOk();
    expect($response->json('ui.nodes'))->not->toBeEmpty()
        ->and($response->json('ui.values.type'))->toBe(EntryImporter::class);
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

    $importer = app(ImportPlans::class)->getImportPlanByHandle('fixtureImport')->steps[0];

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

    $importer = app(ImportPlans::class)->getImportPlanByHandle('fixtureImport')->steps[0];

    expect($importer->matchCriteria)->toBe(['title' => 1])
        ->and($importer->clearableItems)->toBe(['title' => 1]);
});

it('persists templates alongside the map', function () {
    ($this->saveImportPlan)([
        ($this->entryStep)([
            'map' => ['title' => ImportHelper::TEMPLATE_RULE],
            'templates' => ['title' => '{title} ({slug})'],
        ]),
    ])->assertOk();

    $importer = app(ImportPlans::class)->getImportPlanByHandle('fixtureImport')->steps[0];

    expect($importer->templates)->toBe(['title' => '{title} ({slug})']);
});

it('still decodes JSON-encoded container branches on save', function () {
    ($this->saveImportPlan)([
        ($this->entryStep)([
            'map' => ['outerMatrix' => json_encode(['outerEt' => ['title' => 'Title']])],
        ]),
    ])->assertOk();

    $importer = app(ImportPlans::class)->getImportPlanByHandle('fixtureImport')->steps[0];

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

    $importer = app(ImportPlans::class)->getImportPlanByHandle('fixtureImport')->steps[0];

    expect($importer)->toBeInstanceOf(SystemMessageImporter::class)
        ->and($importer->map)->toBe(['subject' => 'incomingSubject'])
        ->and($importer->matchCriteria)->toBe(['key' => 'key']);
});

it('lists a registered import plan alongside the saved ones, keyed by its registered handle', function () {
    $imports = app(ImportPlans::class);

    $imports->register('registeredImport', fn () => new ImportPlanData()
        ->name('Registered Import')
        ->steps([($this->entryStep)()]));

    expect($imports->getAllImportPlans()->keys()->all())->toBe(['registeredImport']);

    ($this->createImportPlan)('Saved Import', 'savedImport');

    expect($imports->getEditableImportPlans()->keys()->all())->toBe(['savedImport'])
        ->and($imports->getNonEditableImportPlans()->keys()->all())->toBe(['registeredImport'])
        ->and($imports->getImportPlanByHandle('registeredImport')?->isEditable())->toBeFalse();
});

it('refuses to run an import plan that is already running', function () {
    Bus::fake();
    ($this->createImportPlan)('Fixture Import', 'fixtureImport');

    postJson(action([ImportPlansController::class, 'run']), ['handle' => 'fixtureImport'])->assertOk();
    postJson(action([ImportPlansController::class, 'run']), ['handle' => 'fixtureImport'])->assertBadRequest()
        ->assertJsonPath('message', 'This import plan is already running.');

    Bus::assertDispatchedTimes(ImportPipeline::class, 1);
});

it('saves the order the editable import plans are put in', function () {
    ($this->createImportPlan)('Alpha', 'alpha');
    ($this->createImportPlan)('Bravo', 'bravo');
    ($this->createImportPlan)('Charlie', 'charlie');

    $plans = app(ImportPlans::class);
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

    $plans = app(ImportPlans::class);

    postJson(action([ImportPlansController::class, 'duplicate']), [
        'uid' => $plans->getImportPlanByHandle('zulu')->uid,
    ])->assertOk();

    expect($plans->getEditableImportPlans()->pluck('handle')->values()->all())
        ->toBe(['zulu', 'alpha', 'zulu2']);
});

it('saves a duplicated import plan like any other new one, with fresh step uids', function () {
    ($this->createImportPlan)('Zulu', 'zulu');
    $original = app(ImportPlans::class)->getImportPlanByHandle('zulu');

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
    $uid = app(ImportPlans::class)->getImportPlanByHandle('alpha')->uid;

    actingAs(UserModel::factory()->withPermissions(['accessCp'])->createElement());

    postJson(action([ImportPlansController::class, 'reorder']), [
        'uids' => [$uid],
    ])->assertForbidden();
});

describe('data source suggestions', function () {
    beforeEach(function () {
        $this->sourceRoot = sys_get_temp_dir().'/import-sources-'.Str::random(8);
        mkdir($this->sourceRoot.'/resources/nested', 0777, true);
        touch($this->sourceRoot.'/resources/posts.json');
        touch($this->sourceRoot.'/resources/people.csv');
        touch($this->sourceRoot.'/resources/notes.php');
        touch($this->sourceRoot.'/resources/.secret.json');
        touch($this->sourceRoot.'/resources/nested/inner.xml');
        Aliases::set('@root', $this->sourceRoot);

        $this->suggest = fn (string $query) => $this->getJson(action([ImportPlansController::class, 'sourceSuggestions'], ['query' => $query]))
            ->assertOk()
            ->json();
    });

    afterEach(function () {
        File::deleteDirectory($this->sourceRoot);
    });

    it('starts `./` in the project root', function () {
        touch($this->sourceRoot.'/root.json');

        expect(($this->suggest)('./'))->toBe([
            ['label' => 'resources/', 'value' => './resources/', 'data' => ['hint' => 'Folder'], 'continues' => true],
            ['label' => 'root.json', 'value' => './root.json'],
        ]);
    });

    it('lists a folder’s subfolders first, leaving out files that can’t be imported', function () {
        expect(($this->suggest)('./resources/'))->toBe([
            ['label' => 'nested/', 'value' => './resources/nested/', 'data' => ['hint' => 'Folder'], 'continues' => true],
            ['label' => 'people.csv', 'value' => './resources/people.csv'],
            ['label' => 'posts.json', 'value' => './resources/posts.json'],
        ]);
    });

    it('narrows the suggestions to what’s been typed of a name', function () {
        expect(array_column(($this->suggest)('./resources/po'), 'value'))->toBe(['./resources/posts.json']);
    });

    it('goes on into a folder once its path is typed', function () {
        expect(array_column(($this->suggest)('./resources/nested/'), 'value'))->toBe(['./resources/nested/inner.xml']);
    });

    // one request per test: the controller, and the request it holds, outlive a request in tests
    it('offers aliases after `@`', function () {
        Aliases::set('@importData', $this->sourceRoot.'/resources');

        expect(($this->suggest)('@importD'))->toBe([
            ['label' => '@importData', 'value' => '@importData/', 'data' => ['hint' => $this->sourceRoot.'/resources'], 'continues' => true],
        ]);
    });

    it('goes on into the folder an alias names', function () {
        Aliases::set('@importData', $this->sourceRoot.'/resources');

        expect(array_column(($this->suggest)('@importData/ne'), 'value'))->toBe(['@importData/nested/']);
    });

    it('doesn’t follow a path out of the folder it starts in', function () {
        expect(($this->suggest)('./resources/../'))->toBe([]);
    });

    it('doesn’t suggest anything inside a system folder', function () {
        expect(($this->suggest)('@vendor/'))->toBe([]);
    });

    it('adds the triggers to the Data Source field', function () {
        $response = $this->postJson(action([ImportPlansController::class, 'stepSettings']), [
            'step' => ($this->entryStep)(),
        ])->assertOk();

        $source = collect(uiNodes($response->json('ui.nodes')))->first(fn (array $node) => ($node['control']['path'] ?? null) === ['source']);

        expect(array_column($source['control']['props']['textExpanderTriggers'], 'trigger'))->toBe(['./', '@']);
    });
});

it('forbids data source suggestions without permission to save import plans', function () {
    actingAs(UserModel::factory()->withPermissions(['accessCp', 'viewImportPlans'])->createElement());

    $this->getJson(action([ImportPlansController::class, 'sourceSuggestions'], ['query' => './']))->assertForbidden();
});

describe('step pages', function () {
    beforeEach(function () {
        ($this->createImportPlan)('Fixture Import', 'fixtureImport');
        $this->plan = fn () => app(ImportPlans::class)->getImportPlanByHandle('fixtureImport');
        $this->saveStep = fn (array $step) => $this->postJson(
            action([ImportPlansController::class, 'storeStep']),
            ['planUid' => ($this->plan)()->uid, 'step' => $step],
        );
    });

    it('renders a step on its own page, with the same form as its slideout', function () {
        $uid = ($this->plan)()->steps[0]->uid;

        $this->get(route('craft.cp.import.steps.edit', ['handle' => 'fixtureImport', 'uid' => $uid]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('import/StepEdit')
                ->where('step.uid', $uid)
                ->where('planUid', ($this->plan)()->uid)
                ->has('stepUi.ui.nodes')
                ->where('stepUi.canMap', true));
    });

    it('404s for a step the plan doesn’t have', function () {
        $this->get(route('craft.cp.import.steps.edit', ['handle' => 'fixtureImport', 'uid' => Str::uuid()->toString()]))
            ->assertNotFound();
    });

    it('links each saved step to its own page from the plan', function () {
        $uid = ($this->plan)()->steps[0]->uid;

        $this->get(route('craft.cp.import.edit', ['handle' => 'fixtureImport']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where(
                "stepEditUrls.$uid",
                route('craft.cp.import.steps.edit', ['handle' => 'fixtureImport', 'uid' => $uid]),
            ));
    });

    it('returns to the plans after saving, with an option to keep editing the plan', function () {
        $this->get(route('craft.cp.import.edit', ['handle' => 'fixtureImport']))
            ->assertOk()
            ->assertInertia(function ($page) {
                $props = $page->toArray()['props'];
                $continue = collect($props['formActions'])->firstWhere('label', 'Save and continue editing');

                expect(Crypt::decrypt($props['redirectUrl']))->toBe('import')
                    ->and(Crypt::decrypt($continue['redirect']))->toBe('import/{handle}')
                    ->and($props['saveShortcutRedirect'])->toBe('import/{handle}');
            });
    });

    it('labels a container field’s nested destinations by their whole path, at every depth', function () {
        $labels = $this->postJson(action([ImportPlansController::class, 'stepSettings']), [
            'step' => ($this->entryStep)(),
        ])->assertOk()->json('summary.destinationLabels');

        expect($labels['outerMatrix.outerEt.fields.innerMatrix'])->toMatchArray(['label' => 'Outer ET › Inner Matrix', 'handle' => 'innerMatrix'])
            ->and($labels['outerMatrix.outerEt.fields.innerMatrix.innerEt.fields.innerPlainText'])->toMatchArray(['label' => 'Inner ET › Inner Plain Text', 'handle' => 'innerPlainText'])
            ->and($labels['outerMatrix.outerEt.title']['label'])->toBe('Outer ET › Title');
    });

    it('gives the plan each step at a glance, by its uid', function () {
        $uid = ($this->plan)()->steps[0]->uid;

        $this->get(route('craft.cp.import.edit', ['handle' => 'fixtureImport']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where("stepSummaries.$uid.destinationLabels.title", ['label' => 'Title', 'handle' => 'title', 'icon' => 'i-cursor'])
                ->where("stepSummaries.$uid.settings", [
                    ['label' => 'Site', 'value' => Sites::getPrimarySite()->name],
                    ['label' => 'Section', 'value' => $this->section->name],
                    ['label' => 'Entry Type', 'value' => $this->entryType->name],
                ]));
    });

    it('replaces the step with its uid, leaving the plan’s other steps alone', function () {
        $existing = ($this->plan)()->serializeSteps()[0];

        ($this->saveStep)([...$existing, 'batchSize' => 7])->assertOk();

        expect(($this->plan)()->steps)->toHaveCount(1)
            ->and(($this->plan)()->steps[0]->batchSize)->toBe(7);
    });

    it('adds a step it doesn’t have yet', function () {
        $uid = Str::uuid()->toString();

        ($this->saveStep)(($this->entryStep)(overrides: ['uid' => $uid]))->assertOk();

        expect(array_map(fn ($step) => $step->uid, ($this->plan)()->steps))->toContain($uid)
            ->and(($this->plan)()->steps)->toHaveCount(2);
    });

    it('reports a step’s errors against its uid in the plan', function () {
        $step = ($this->entryStep)(overrides: ['source' => 'tests/Fixtures/Import/does-not-exist.json']);

        $response = ($this->saveStep)($step)->assertStatus(400);

        expect(array_keys($response->json('errors')))->toContain("steps.{$step['uid']}.source")
            ->and(($this->plan)()->steps)->toHaveCount(1);
    });

    it('lands back on the step it saved', function () {
        $step = ($this->entryStep)();

        $this->post(action([ImportPlansController::class, 'storeStep']), [
            'planUid' => ($this->plan)()->uid,
            'step' => $step,
        ])->assertRedirect(route('craft.cp.import.steps.edit', ['handle' => 'fixtureImport', 'uid' => $step['uid']]));
    });
});

/**
 * Every node in a UI payload's tree, depth first.
 *
 * @param  list<array<string, mixed>>  $nodes
 * @return list<array<string, mixed>>
 */
function uiNodes(array $nodes): array
{
    $all = [];

    foreach ($nodes as $node) {
        $all[] = $node;
        array_push($all, ...uiNodes($node['children'] ?? []));
    }

    return $all;
}

describe('the step form', function () {
    beforeEach(function () {
        $this->stepUiTopLevel = fn () => $this->postJson(action([ImportPlansController::class, 'stepSettings']), [
            'step' => ($this->entryStep)(),
        ])->assertOk()->json('ui.nodes');
        $this->stepUi = fn (array $settings = []) => collect(uiNodes(
            $this->postJson(action([ImportPlansController::class, 'stepSettings']), [
                'step' => ($this->entryStep)($settings),
            ])->assertOk()->json('ui.nodes'),
        ));
    });

    it('lays the step out on one page, with its mapping before its advanced settings', function () {
        $nodes = ($this->stepUi)();

        expect($nodes->where('component', 'craft:tab'))->toBeEmpty()
            ->and(array_column(($this->stepUiTopLevel)(), 'uid'))->toBe(['scope:"settings"', 'mapping', 'import-step-advanced'])
            ->and($nodes->firstWhere('uid', 'import-step-settings')['props']['flat'])->toBeTrue()
            ->and($nodes->firstWhere('component', 'craft:slot')['props']['name'])->toBe('mapping');
    });

    it('reports each destination column’s label, keyed like the map', function () {
        $labels = $this->postJson(action([ImportPlansController::class, 'stepSettings']), [
            'step' => ($this->entryStep)(),
        ])->assertOk()->json('summary.destinationLabels');

        expect($labels['title'])->toBe(['label' => 'Title', 'handle' => 'title', 'icon' => 'i-cursor'])
            ->and($labels['postDate']['icon'])->toBe('calendar')
            ->and($labels['enabled']['icon'])->toBe('toggle-on')
            ->and($labels)->toHaveKey('slug');
    });

});

describe('previewing a step', function () {
    beforeEach(function () {
        $this->existing = EntryModel::factory()
            ->forSection($this->section)
            ->forEntryType($this->entryType)
            ->createElement(['title' => 'Old title', 'slug' => 'second-file-entry-slug']);

        // only the second item has a slug to match on
        $this->previewStep = ($this->entryStep)([
            'map' => ['title' => 'title', 'slug' => 'slug'],
            'matchCriteria' => ['slug' => 1],
        ]);
    });

    it('says what importing each item would do, without saving anything', function () {
        $response = $this->postJson(action([ImportPlansController::class, 'previewStep']), [
            'step' => $this->previewStep,
        ]);

        $response->assertOk();
        expect($response->json('error'))->toBeNull()
            ->and($response->json('total'))->toBe(3)
            ->and($response->json('matchColumns'))->toBe([['key' => 'slug', 'label' => 'Slug']])
            ->and($response->json('items.*.result'))->toBe(['new', 'update', 'new'])
            ->and($response->json('items.1.match'))->toBe(['slug' => 'second-file-entry-slug'])
            ->and($response->json('items.1.title'))->toBe('second file entry');

        expect(EntryElement::find()->sectionId($this->section->id)->status(null)->count())->toBe(1)
            ->and(EntryElement::find()->id($this->existing->id)->status(null)->one()->title)->toBe('Old title');
    });

    it('compares an item’s element as it is with how the import would leave it', function () {
        $response = $this->postJson(action([ImportPlansController::class, 'previewItem']), [
            'step' => $this->previewStep,
            'index' => 1,
        ]);

        $response->assertOk();
        expect($response->json('result'))->toBe('update')
            ->and($response->json('comparison.from.label'))->toBe('Current')
            ->and($response->json('comparison.badged'))->toBe(['from' => false, 'to' => true]);

        $toTitle = collect($response->json('comparison.to.ui.values'))->flatten()->contains('second file entry');
        $changedRows = collect($response->json('comparison.rows'))->where('changed', true);

        expect($toTitle)->toBeTrue()
            ->and($changedRows)->toHaveCount(1);
    });
});
