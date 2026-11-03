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
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\ImportHelper;
use CraftCms\Cms\Import\ImportPlans;
use CraftCms\Cms\Import\Jobs\Import as ImportJob;
use CraftCms\Cms\Import\Jobs\ImportPipeline;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Arr;
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
use function Pest\Laravel\getJson;
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

it('rejects a saved import plan whose steps are all removed', function () {
    ($this->createImportPlan)('Fixture Import', 'fixtureImport');
    $saved = app(ImportPlans::class)->getImportPlanByHandle('fixtureImport');

    ($this->saveImportPlan)([], ['uid' => $saved->uid])
        ->assertStatus(400)
        ->assertJsonPath('errors.steps.0', 'An import plan needs at least one step.');

    expect(app(ImportPlans::class)->getImportPlanByHandle('fixtureImport')->steps)->toHaveCount(1);
});

it('rejects a new import plan without any steps', function () {
    ($this->saveImportPlan)([])
        ->assertStatus(400)
        ->assertJsonPath('errors.steps.0', 'An import plan needs at least one step.');

    expect(app(ImportPlans::class)->getImportPlanByHandle('fixtureImport'))->toBeNull();
});

it('keeps an import plan’s steps when only its settings are saved', function () {
    ($this->createImportPlan)('Fixture Import', 'fixtureImport');
    $saved = app(ImportPlans::class)->getImportPlanByHandle('fixtureImport');

    $this->postJson(action([ImportPlansController::class, 'store']), [
        'uid' => $saved->uid,
        'name' => 'Renamed Import',
        'handle' => 'fixtureImport',
    ])->assertOk();

    $renamed = app(ImportPlans::class)->getImportPlanByHandle('fixtureImport');

    expect($renamed->name)->toBe('Renamed Import')
        ->and($renamed->steps)->toHaveCount(1);
});

it('edits an import plan’s steps alongside its name, handle and description, then reviews them', function () {
    ($this->createImportPlan)('Fixture Import', 'fixtureImport');

    $nodes = $this->get(route('craft.cp.import.edit', ['handle' => 'fixtureImport']))
        ->assertOk()
        ->viewData('page')['props']['ui']['nodes'];
    $controls = collect(Arr::flatten($nodes))
        ->filter(fn ($value) => in_array($value, ['name', 'handle', 'description', 'steps', 'review'], true))
        ->unique()->values()->all();

    expect($controls)->toBe(['name', 'handle', 'description', 'steps', 'review']);
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

        $this->suggest = fn (string $query) => getJson(action([ImportPlansController::class, 'sourceSuggestions'], ['query' => $query]))
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
        $response = postJson(action([ImportPlansController::class, 'stepSettings']), [
            'step' => ($this->entryStep)(),
        ])->assertOk();

        $source = collect($response->json('ui.nodes'))->first(fn (array $node) => ($node['control']['path'] ?? null) === ['source']);

        expect(array_column($source['control']['props']['textExpanderTriggers'], 'trigger'))->toBe(['./', '@']);
    });
});

it('forbids data source suggestions without permission to save import plans', function () {
    actingAs(UserModel::factory()->withPermissions(['accessCp', 'viewImportPlans'])->createElement());

    getJson(action([ImportPlansController::class, 'sourceSuggestions'], ['query' => './']))->assertForbidden();
});

it('fills in the default batch size, under its label in the advanced settings', function () {
    $response = postJson(action([ImportPlansController::class, 'stepSettings']), [
        'step' => ($this->entryStep)(),
    ])->assertOk();

    $advanced = collect($response->json('ui.nodes'))->first(fn (array $node) => ($node['props']['label'] ?? null) === 'Advanced');
    $field = collect($advanced['children'] ?? [])->first(fn (array $node) => ($node['control']['path'] ?? null) === ['batchSize']);

    expect($advanced['props']['collapsible'] ?? null)->toBeTrue()
        ->and($field['props']['label'])->toBe('Batch Size')
        ->and($response->json('ui.values.batchSize'))->toBe(ImportJob::DEFAULT_BATCH_SIZE);
});

it('keeps a step’s own batch size', function () {
    $response = postJson(action([ImportPlansController::class, 'stepSettings']), [
        'step' => ($this->entryStep)(overrides: ['batchSize' => 25]),
    ])->assertOk();

    expect($response->json('ui.values.batchSize'))->toBe(25);
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

describe('step pages', function () {
    beforeEach(function () {
        ($this->createImportPlan)('Fixture Import', 'fixtureImport');
        $this->plan = fn () => app(ImportPlans::class)->getImportPlanByHandle('fixtureImport');
        $this->saveStep = fn (array $step, array $overrides = []) => $this->postJson(
            action([ImportPlansController::class, 'storeStep']),
            array_merge(['planUid' => ($this->plan)()->uid, 'step' => $step], $overrides),
        );
    });

    it('renders a step on its own page', function () {
        $uid = ($this->plan)()->steps[0]->uid;

        $this->get(route('craft.cp.import.steps.edit', ['handle' => 'fixtureImport', 'uid' => $uid]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('import/StepEdit')
                ->where('step.uid', $uid)
                ->where('planUid', ($this->plan)()->uid));
    });

    it('404s for a step the plan doesn’t have', function () {
        $this->get(route('craft.cp.import.steps.edit', ['handle' => 'fixtureImport', 'uid' => Str::uuid()->toString()]))
            ->assertNotFound();
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

    it('adds a copy with a uid of its own when saved as a new step', function () {
        $existing = ($this->plan)()->serializeSteps()[0];

        ($this->saveStep)($existing, ['asNew' => true])->assertOk();

        $uids = array_map(fn ($step) => $step->uid, ($this->plan)()->steps);

        expect($uids)->toHaveCount(2)
            ->and($uids[0])->toBe($existing['uid'])
            ->and($uids[1])->not->toBe($existing['uid']);
    });

    it('reports a step’s errors against its uid in the plan', function () {
        $step = ($this->entryStep)(overrides: ['source' => 'tests/Fixtures/Import/does-not-exist.json']);

        $response = ($this->saveStep)($step)->assertStatus(400);

        expect(array_keys($response->json('errors')))->toContain("steps.{$step['uid']}.source")
            ->and(($this->plan)()->steps)->toHaveCount(1);
    });

    it('lands on the step it saved when no redirect is posted', function () {
        $step = ($this->entryStep)();

        $this->post(action([ImportPlansController::class, 'storeStep']), [
            'planUid' => ($this->plan)()->uid,
            'step' => $step,
        ])->assertRedirect(route('craft.cp.import.steps.edit', ['handle' => 'fixtureImport', 'uid' => $step['uid']]));
    });
});

describe('testing a step’s matching', function () {
    beforeEach(function () {
        $this->existing = fn (string $title) => EntryModel::factory()
            ->forSection($this->section)
            ->forEntryType($this->entryType)
            ->createElementWithFields(['title' => $title])
            ->element;

        $this->testMatch = fn (array $settings = []) => $this->postJson(
            action([ImportPlansController::class, 'testMatch']),
            ['step' => ($this->entryStep)(array_merge([
                'map' => ['title' => 'title'],
                'matchCriteria' => ['title' => '1'],
            ], $settings))],
        );
    });

    it('lists the existing elements the rows match, with the row each came from', function () {
        $entry = ($this->existing)('second file entry');

        ($this->testMatch)()
            ->assertOk()
            ->assertJsonPath('matches.0.id', $entry->id)
            ->assertJsonPath('matches.0.row', 2)
            ->assertJsonPath('matches.0.label', 'second file entry')
            ->assertJsonCount(1, 'matches')
            ->assertJsonPath('total', 3);
    });

    it('imports nothing while it tests', function () {
        ($this->testMatch)()->assertOk();

        expect(EntryElement::find()->title('first file entry')->status(null)->exists())->toBeFalse();
    });

    it('finds nothing when nothing is matched on', function () {
        ($this->existing)('second file entry');

        ($this->testMatch)(['matchCriteria' => []])
            ->assertOk()
            ->assertJsonCount(0, 'matches');
    });

    it('stops at the most matches it lists', function () {
        foreach (['first file entry', 'second file entry', 'third file entry'] as $title) {
            ($this->existing)($title);
        }

        $found = app(Import::class)->findMatches(
            new EntryImporter(($this->entryStep)(['map' => ['title' => 'title'], 'matchCriteria' => ['title' => '1']])),
            json_decode((string) file_get_contents(dirname(__DIR__, 4).'/'.$this->source), true),
            limit: 2,
        );

        expect(array_column($found['matches'], 'row'))->toBe([1, 2])
            ->and($found['checked'])->toBe(2);
    });

    it('refuses to test a step with nothing to import into', function () {
        $this->postJson(action([ImportPlansController::class, 'testMatch']), [
            'step' => [...($this->entryStep)(), 'settings' => ['site' => Sites::getPrimarySite()->handle]],
        ])->assertStatus(400);
    });
});

describe('bulk actions', function () {
    beforeEach(function () {
        ($this->createImportPlan)('First Import', 'firstImport');
        ($this->createImportPlan)('Second Import', 'secondImport');
        $this->uids = fn () => [
            app(ImportPlans::class)->getImportPlanByHandle('firstImport')->uid,
            app(ImportPlans::class)->getImportPlanByHandle('secondImport')->uid,
        ];
    });

    it('duplicates each selected plan', function () {
        $this->postJson(action([ImportPlansController::class, 'bulkDuplicate']), ['uids' => ($this->uids)()])
            ->assertOk()
            ->assertJsonPath('message', '2 import plans duplicated.');

        expect(app(ImportPlans::class)->getEditableImportPlans())->toHaveCount(4);
    });

    it('deletes each selected plan', function () {
        $this->postJson(action([ImportPlansController::class, 'bulkDestroy']), ['uids' => ($this->uids)()])
            ->assertOk()
            ->assertJsonPath('message', '2 import plans deleted.');

        expect(app(ImportPlans::class)->getImportPlanByHandle('firstImport'))->toBeNull()
            ->and(app(ImportPlans::class)->getImportPlanByHandle('secondImport'))->toBeNull();
    });

    it('starts each selected plan', function () {
        Bus::fake();

        $this->postJson(action([ImportPlansController::class, 'bulkRun']), ['uids' => ($this->uids)()])
            ->assertOk()
            ->assertJsonPath('message', '2 imports started.');
    });

    it('needs at least one plan selected', function () {
        $this->postJson(action([ImportPlansController::class, 'bulkDestroy']), ['uids' => []])
            ->assertStatus(422);
    });

    it('rejects a plan that doesn’t exist', function () {
        $this->postJson(action([ImportPlansController::class, 'bulkDestroy']), ['uids' => [Str::uuid()->toString()]])
            ->assertNotFound();
    });
});

it('duplicates an import plan that has no steps', function () {
    app(ImportPlans::class)->saveImportPlan(
        new ImportPlanData(['name' => 'Fixture Import', 'handle' => 'fixtureImport', 'editable' => true])->withoutRequiredSteps(),
    );
    $plan = app(ImportPlans::class)->getImportPlanByHandle('fixtureImport');

    $this->postJson(action([ImportPlansController::class, 'bulkDuplicate']), ['uids' => [$plan->uid]])
        ->assertOk();

    expect(app(ImportPlans::class)->getEditableImportPlans())->toHaveCount(2);
});

it('lists each plan’s steps in the index, linked to their own pages', function () {
    ($this->createImportPlan)('Fixture Import', 'fixtureImport');
    $plan = app(ImportPlans::class)->getImportPlanByHandle('fixtureImport');
    $row = collect($this->get(route('craft.cp.import.index'))->assertOk()
        ->viewData('page')['props']['editableImportPlans'])->firstWhere('handle', 'fixtureImport');

    expect($row['steps'])->toBe([[
        'label' => EntryImporter::displayName(),
        'source' => $this->source,
        'enabled' => true,
        'url' => route('craft.cp.import.steps.edit', ['handle' => 'fixtureImport', 'uid' => $plan->steps[0]->uid]),
    ]]);
});

it('shows each plan’s run state in the index', function () {
    ($this->createImportPlan)('Fixture Import', 'fixtureImport');
    $plan = app(ImportPlans::class)->getImportPlanByHandle('fixtureImport');
    $row = fn () => collect($this->get(route('craft.cp.import.index'))->assertOk()
        ->viewData('page')['props']['editableImportPlans'])->firstWhere('handle', 'fixtureImport');

    expect($row())->toMatchArray(['running' => false, 'runningJobUrl' => null, 'lastRunFailed' => false]);

    Bus::fake();
    app(Import::class)->dispatchImport($plan);

    expect($row()['running'])->toBeTrue()
        ->and($row()['runningJobUrl'])->toEndWith('/utilities/queue-manager');

    app(Import::class)->rememberRunningJob($plan, 'job-uuid');

    expect($row()['runningJobUrl'])->toEndWith('/utilities/queue-manager/job-uuid');

    app(Import::class)->unlock($plan);
    app(Import::class)->recordRunFinished($plan, failed: true);

    expect($row())->toMatchArray(['running' => false, 'lastRunFailed' => true]);
});

describe('an import plan’s review', function () {
    beforeEach(function () {
        $this->matchingStep = fn (array $overrides = []) => ($this->entryStep)([
            'map' => ['title' => 'title'],
            'matchCriteria' => ['title' => '1'],
        ], $overrides);

        $this->review = fn (array $data) => $this->postJson(action([ImportPlansController::class, 'reviewSteps']), $data)
            ->assertOk()
            ->json('steps');
    });

    it('links each of a saved plan’s steps to its own page, which comes back to the plan', function () {
        ($this->createImportPlan)('Fixture Import', 'fixtureImport');
        $uid = app(ImportPlans::class)->getImportPlanByHandle('fixtureImport')->steps[0]->uid;

        $props = $this->get(route('craft.cp.import.edit', ['handle' => 'fixtureImport']))->assertOk()->viewData('page')['props'];
        $stepPage = $this->get($props['stepPageUrls'][$uid])->assertOk()->viewData('page')['props'];

        expect(Crypt::decrypt($stepPage['redirectUrl']))->toBe(route('craft.cp.import.edit', ['handle' => 'fixtureImport']))
            ->and($props['run']['uid'] ?? null)->not->toBeNull();
    });

    it('reads a step’s data source for its first rows', function () {
        $preview = $this->postJson(action([ImportPlansController::class, 'sourcePreview']), ['step' => ($this->entryStep)()])
            ->assertOk()
            ->json();

        expect($preview)->toMatchArray(['total' => 3, 'error' => null])
            ->and($preview['columns'])->toContain('title', 'slug')
            ->and($preview['rows'])->toHaveCount(3)
            ->and($preview['rows'][0]['title'])->toBe('first file entry');
    });

    it('says why a step’s data source can’t be read', function () {
        $preview = $this->postJson(action([ImportPlansController::class, 'sourcePreview']), [
            'step' => ($this->entryStep)(overrides: ['source' => 'tests/Fixtures/Import/missing.json']),
        ])->assertOk()->json();

        expect($preview['rows'])->toBe([])
            ->and($preview['error'])->not->toBeNull();
    });

    it('creates a new plan in a wizard, which posts the whole plan when it’s done', function () {
        $this->get(route('craft.cp.import.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('import/Create')
                ->where('submit.url', action([ImportPlansController::class, 'store']))
                ->where('runUrl', action([ImportPlansController::class, 'run']))
                ->where('editUrl', route('craft.cp.import.edit', ['handle' => '__handle__']))
                ->has('importerTypes', count(app(Import::class)->getAllImporterTypes())));

        $controls = collect(Arr::flatten($this->get(route('craft.cp.import.create'))->viewData('page')['props']['ui']['nodes']))
            ->filter(fn ($value) => in_array($value, ['name', 'handle', 'description', 'steps'], true))
            ->unique()->values()->all();

        expect($controls)->toBe(['name', 'handle', 'description']);
    });

    it('leaves running out of the wizard for a user who can’t run plans', function () {
        actingAs(UserModel::factory()->withPermissions(['accessCp', 'viewImportPlans', 'saveImportPlans'])->createElement());

        $this->get(route('craft.cp.import.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('runUrl', null));
    });

    it('skips the rows that match something already there, for a step that only creates', function () {
        EntryModel::factory()->forSection($this->section)->forEntryType($this->entryType)
            ->createElementWithFields(['title' => 'second file entry']);

        $review = ($this->review)(['steps' => [($this->matchingStep)(['settings' => [
            'site' => Sites::getPrimarySite()->handle,
            'section' => $this->section->handle,
            'entryType' => $this->entryType->handle,
            'map' => ['title' => 'title'],
            'matchCriteria' => ['title' => '1'],
            'matchMode' => 'createOnly',
        ]])]])[0]['preview'];

        expect($review)->toMatchArray(['created' => 2, 'updated' => 0, 'skipped' => 1])
            ->and(collect($review['rows'])->pluck('action')->all())->toBe(['create', 'skip', 'create']);
    });

    it('skips the rows that match nothing, for a step that only updates', function () {
        EntryModel::factory()->forSection($this->section)->forEntryType($this->entryType)
            ->createElementWithFields(['title' => 'second file entry']);

        $review = ($this->review)(['steps' => [($this->matchingStep)(['settings' => [
            'site' => Sites::getPrimarySite()->handle,
            'section' => $this->section->handle,
            'entryType' => $this->entryType->handle,
            'map' => ['title' => 'title'],
            'matchCriteria' => ['title' => '1'],
            'matchMode' => 'updateOnly',
        ]])]])[0]['preview'];

        expect($review)->toMatchArray(['created' => 0, 'updated' => 1, 'skipped' => 2])
            ->and(collect($review['rows'])->pluck('action')->all())->toBe(['skip', 'update', 'skip']);
    });

    it('only creates what a create-only step doesn’t find already', function () {
        $existing = EntryModel::factory()
            ->forSection($this->section)
            ->forEntryType($this->entryType)
            ->createElementWithFields(['title' => 'second file entry', 'slug' => 'untouched'])
            ->element;
        $importer = ImportPlans::createImporter(($this->entryStep)([
            'map' => ['title' => 'title', 'slug' => 'slug'],
            'matchCriteria' => ['title' => '1'],
            'matchMode' => 'createOnly',
        ]));
        $importer->prepareForImport();
        $criteria = ImportHelper::normalizeMatchCriteriaFromImporterConfig($importer);

        app(Import::class)->importItem($importer, ['title' => 'second file entry', 'slug' => 'changed'], $criteria);
        app(Import::class)->importItem($importer, ['title' => 'a new entry', 'slug' => 'a-new-entry'], $criteria);

        expect(EntryElement::find()->id($existing->id)->status(null)->one()->slug)->toBe('untouched')
            ->and(EntryElement::find()->title('a new entry')->status(null)->exists())->toBeTrue();
    });

    it('reviews steps that are yet to be saved, numbered by their place in the plan', function () {
        $review = ($this->review)(['steps' => [
            ($this->matchingStep)(['enabled' => false]),
            ['uid' => Str::uuid()->toString(), 'type' => null, 'enabled' => true],
            ($this->matchingStep)(),
        ]]);

        expect($review)->toHaveCount(1)
            ->and($review[0])->toMatchArray(['number' => 3, 'source' => $this->source])
            ->and($review[0]['preview']['total'])->toBe(3);
    });

    it('reviews the saved plan for a user who can’t edit it', function () {
        ($this->saveImportPlan)([($this->matchingStep)()])->assertOk();
        $plan = app(ImportPlans::class)->getImportPlanByHandle('fixtureImport');
        actingAs(UserModel::factory()->withPermissions(['accessCp', 'viewImportPlans'])->createElement());

        $review = ($this->review)([
            'uid' => $plan->uid,
            'steps' => [($this->matchingStep)(['source' => 'tests/Fixtures/Import/elsewhere.json'])],
        ]);

        expect($review[0]['source'])->toBe($this->source);
    });

    it('leaves out what a step can only match on, since it’s never written', function () {
        $review = ($this->review)(['steps' => [($this->entryStep)([
            'map' => ['id' => 'title', 'title' => 'title'],
        ])]])[0]['preview'];

        expect(collect($review['columns'])->pluck('key')->all())->toContain('title')->not->toContain('id');
    });

    it('reviews the values each row fills in, and which rows create elements or update the ones they match', function () {
        $entry = EntryModel::factory()
            ->forSection($this->section)
            ->forEntryType($this->entryType)
            ->createElementWithFields(['title' => 'second file entry'])
            ->element;

        $review = ($this->review)(['steps' => [($this->matchingStep)()]])[0]['preview'];

        expect($review)->toMatchArray(['created' => 2, 'updated' => 1, 'total' => 3, 'error' => null])
            ->and(collect($review['columns'])->pluck('label')->all())->toContain('Title')
            ->and(collect($review['rows'])->pluck('action')->all())->toBe(['create', 'update', 'create'])
            ->and($review['rows'][1])->toMatchArray(['label' => 'second file entry', 'url' => $entry->getCpEditUrl()])
            ->and($review['rows'][0]['values'])->toMatchArray(['title' => 'first file entry']);

        expect(EntryElement::find()->title('first file entry')->status(null)->exists())->toBeFalse();
    });
});
