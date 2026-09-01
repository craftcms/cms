<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Field\Dropdown;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\Models\FieldLayout as FieldLayoutRecord;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Http\Controllers\Elements\ElementSourcesController;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\UserGroup;
use Illuminate\Testing\Fluent\AssertableJson;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

/** @return list<list<string>> */
function controlPaths(array $form): array
{
    return array_map(fn (array $control) => $control['path'], formControls($form));
}

/** @return array<string, mixed>|null */
function control(array $form, array $path): ?array
{
    return collect(formControls($form))->firstWhere('path', $path);
}

/** @return array<string, mixed> */
function controlProps(array $form, array $path): array
{
    return control($form, $path)['props'] ?? [];
}

/** @return array<string, mixed> */
function sourceValues(array $source): array
{
    return $source['form']['values']['sources'][$source['key']] ?? [];
}

/** @return list<array<string, mixed>> */
function formControls(array $form): array
{
    $controls = [];
    $walk = function (array $nodes) use (&$walk, &$controls): void {
        foreach ($nodes as $node) {
            if (isset($node['control'])) {
                $controls[] = $node['control'];
            }

            $walk($node['children'] ?? []);
        }
    };
    $walk($form['nodes'] ?? []);

    return $controls;
}

beforeEach(function () {
    actingAs(User::findOne());

    app(Fields::class)->refreshFields();
});

it('returns fully normalized source customization data', function () {
    $primarySite = Sites::getPrimarySite();
    // The Sites field is only offered on a multi-site install.
    Site::factory()->create();

    $field = Field::factory()->create([
        'name' => 'Preview Field',
        'handle' => 'previewField',
        'type' => Dropdown::class,
        'settings' => [
            'options' => [
                ['label' => 'Alpha', 'value' => 'alpha'],
                ['label' => 'Beta', 'value' => 'beta'],
            ],
        ],
    ]);

    FieldLayoutRecord::factory()
        ->forField($field)
        ->create([
            'type' => TestElementSourcesElement::class,
        ]);

    $userGroup = UserGroup::factory()->create([
        'name' => 'Editors',
        'handle' => 'editors',
        'uid' => Str::uuid()->toString(),
    ]);

    app(Fields::class)->refreshFields();

    app(ProjectConfig::class)->set(sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCES, TestElementSourcesElement::class), [
        [
            'type' => ElementSources::TYPE_HEADING,
            'heading' => 'Primary Sources',
        ],
        [
            'type' => ElementSources::TYPE_NATIVE,
            'key' => 'structured',
            'defaultSort' => 'slug',
        ],
        [
            'type' => ElementSources::TYPE_NATIVE,
            'key' => 'fallback',
        ],
        [
            'type' => ElementSources::TYPE_CUSTOM,
            'key' => 'custom:existing',
            'label' => 'Existing Custom',
            'defaultSort' => ['field:'.$field->uid, 'desc'],
            'condition' => [
                'class' => ElementCondition::class,
                'elementType' => TestElementSourcesElement::class,
                'conditionRules' => [],
            ],
            'sites' => [$primarySite->uid, 'missing-site'],
            'userGroups' => false,
        ],
        [
            'type' => ElementSources::TYPE_CUSTOM,
            'key' => 'custom:false-sites',
            'label' => 'No Sites',
            'sites' => false,
        ],
    ]);

    app(ProjectConfig::class)->set(sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCE_PAGES, TestElementSourcesElement::class), [
        'entries' => ['label' => 'Entries'],
    ]);

    $response = postJson(action([ElementSourcesController::class, 'show']), [
        'elementType' => TestElementSourcesElement::class,
    ]);

    $response->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('multiPage', true)
            ->where('elementTypeName', 'Test Element')
            ->where('pageSettings.entries.label', 'Entries')
            ->where('sources.0.type', ElementSources::TYPE_HEADING)
            ->where('sources.0.page', 'Test Elements')
            // ElementSources synthesizes a keyless blank heading as a
            // separator; it's regenerated on every read and isn't saveable.
            ->where('sources.0.form', null)
            ->where('sources.1.page', 'Test Elements')
            ->where('sources.1.form.scope', ['sources', 'structured'])
            // Everything the modal used to build its fields client-side now
            // arrives inside each source's Form.
            ->missing('viewModes')
            ->missing('baseSortOptions')
            ->missing('defaultSortOptions')
            ->missing('availableTableAttributes')
            ->missing('customFieldAttributes')
            ->missing('conditionBuilderHtml')
            ->missing('conditionBuilderJs')
            ->missing('userGroups')
            ->missing('headHtml')
            ->missing('bodyHtml')
            ->etc()
        );

    $sources = collect($response->json('sources'))->keyBy('key');
    $structured = $sources['structured'];
    $fallback = $sources['fallback'];
    $custom = $sources['custom:existing'];

    expect(controlPaths($structured['form']))->toBe([
        ['sources', 'structured', 'enabled'],
        ['sources', 'structured', 'defaultViewMode'],
        ['sources', 'structured', 'defaultSort', 'attr'],
        ['sources', 'structured', 'defaultSort', 'dir'],
        ['sources', 'structured', 'tableAttributes'],
    ])
        ->and(controlPaths($custom['form']))->toBe([
            ['sources', 'custom:existing', 'label'],
            ['sources', 'custom:existing', 'condition'],
            ['sources', 'custom:existing', 'defaultSort', 'attr'],
            ['sources', 'custom:existing', 'defaultSort', 'dir'],
            ['sources', 'custom:existing', 'tableAttributes'],
            ['sources', 'custom:existing', 'defaultViewMode'],
            ['sources', 'custom:existing', 'sites'],
            ['sources', 'custom:existing', 'userGroups'],
        ])
        ->and(controlPaths($sources['heading:content']['form'] ?? ['nodes' => []]))->toBe([]);

    // Seeded values, normalized the way store() expects them back.
    expect(sourceValues($structured))->toMatchArray([
        'enabled' => true,
        'defaultSort' => ['attr' => 'slug', 'dir' => 'asc'],
        'tableAttributes' => ['slug'],
    ])
        ->and(sourceValues($fallback)['defaultSort'])->toBe(['attr' => 'id', 'dir' => 'asc'])
        ->and(sourceValues($custom))->toMatchArray([
            'label' => 'Existing Custom',
            'defaultSort' => ['attr' => 'field:'.$field->uid, 'dir' => 'desc'],
            'sites' => [$primarySite->uid],
            'userGroups' => [],
        ])
        // An untouched condition builder posts back whatever was seeded, so the
        // seed has to carry its class.
        ->and(sourceValues($custom)['condition'])->toHaveKey('class')
        // An absent key means "all"; an explicit false means "none".
        ->and(sourceValues($sources['custom:false-sites'])['sites'])->toBe([])
        ->and(sourceValues($sources['custom:false-sites'])['userGroups'])->toBe('*');

    // Every Choice's options must serialize as a JSON list — an associative
    // array becomes an object, which the Vue control can't map over.
    foreach (formControls($custom['form']) as $control) {
        if ($control['component'] === 'craft:choice') {
            expect(array_is_list($control['props']['options']))
                ->toBeTrue(implode('.', $control['path']).' options must be a list');
        }
    }

    // Only a structured source offers structure ordering, and it has no
    // direction to pick.
    expect(controlProps($structured['form'], ['sources', 'structured', 'defaultSort', 'attr'])['options'][0]['value'])
        ->toBe('structure')
        ->and(controlProps($fallback['form'], ['sources', 'fallback', 'defaultSort', 'attr'])['options'][0]['value'])
        ->not->toBe('structure')
        ->and(control($structured['form'], ['sources', 'structured', 'defaultViewMode'])['props']['options'])
        ->toHaveCount(count(TestElementSourcesElement::indexViewModes()));

    // A new custom source can pick previewable custom fields as columns, since
    // its field layouts aren't known yet.
    $newSource = postJson(action([ElementSourcesController::class, 'form']), [
        'elementType' => TestElementSourcesElement::class,
        'sourceKey' => 'custom:new',
        'type' => ElementSources::TYPE_CUSTOM,
    ])->assertOk()->json('form');

    // Draining HtmlStack here would ship the whole CP asset bootstrap, whose
    // initializers target elements that only exist on a full page render.
    postJson(action([ElementSourcesController::class, 'form']), [
        'elementType' => TestElementSourcesElement::class,
        'sourceKey' => 'custom:new',
        'type' => ElementSources::TYPE_CUSTOM,
    ])->assertOk()
        ->assertJsonMissingPath('headHtml')
        ->assertJsonMissingPath('bodyHtml');

    expect($newSource['scope'])->toBe(['sources', 'custom:new'])
        ->and($newSource['values']['sources']['custom:new']['condition'])->toHaveKey('class')
        ->and(array_column(controlProps($newSource, ['sources', 'custom:new', 'tableAttributes'])['options'], 'value'))
        ->toContain('field:'.$field->uid);
});

it('re-resolves a source Form from posted settings', function () {
    app(ProjectConfig::class)->set(sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCES, TestElementSourcesElement::class), [
        [
            'type' => ElementSources::TYPE_NATIVE,
            'key' => 'structured',
        ],
    ]);

    $form = postJson(action([ElementSourcesController::class, 'form']), [
        'elementType' => TestElementSourcesElement::class,
        'sourceKey' => 'structured',
        'type' => ElementSources::TYPE_NATIVE,
        'settings' => [
            'defaultSort' => ['attr' => 'structure', 'dir' => 'desc'],
        ],
    ])->assertOk()->json('form');

    expect(control($form, ['sources', 'structured', 'defaultSort', 'dir'])['mode'])->toBe('disabled');
});

it('stores normalized source settings for multi-page sources', function () {
    $projectConfig = app(ProjectConfig::class);

    $projectConfig->set(sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCES, TestElementSourcesElement::class), [
        [
            'key' => 'native-disabled',
            'type' => ElementSources::TYPE_NATIVE,
            'page' => 'Archived',
            'disabled' => true,
            'tableAttributes' => ['slug'],
        ],
    ]);

    $response = postJson(action([ElementSourcesController::class, 'store']), [
        'elementType' => TestElementSourcesElement::class,
        'sourceOrder' => [
            'native-enabled',
            'custom:new',
            'heading:content',
            'native-disabled',
            'custom:missing',
        ],
        'sourcePages' => [
            'native-enabled' => 'Archived',
            'custom:new' => 'Content',
            'heading:content' => 'Content',
            'native-disabled' => 'Archived',
        ],
        'pageSettings' => [
            'Content' => [
                'label' => 'Content',
                'description' => '',
            ],
            'Archived' => [
                'label' => 'Archived',
                'description' => null,
            ],
        ],
        'sources' => [
            'native-enabled' => [
                'tableAttributes' => ['', 'slug'],
                'defaultSort' => ['slug', 'desc'],
                'defaultViewMode' => 'cards',
                'enabled' => true,
            ],
            'custom:new' => [
                'label' => 'Fresh Custom',
                'tableAttributes' => ['', 'postDate'],
                'defaultSort' => ['postDate', 'desc'],
                'defaultViewMode' => 'cards',
                'condition' => [
                    'class' => ElementCondition::class,
                    'elementType' => TestElementSourcesElement::class,
                    'conditionRules' => [],
                ],
                'sites' => 'not-an-array',
                'userGroups' => ['group-editors'],
            ],
            'heading:content' => [
                'heading' => 'Content Heading',
            ],
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('message', t('Source settings saved'))
        ->assertJsonPath('disabledSourceKeys.0', 'native-disabled');

    expect(normalizeConfig($projectConfig->get(sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCES, TestElementSourcesElement::class))))->toBe(normalizeConfig([
        [
            'type' => ElementSources::TYPE_CUSTOM,
            'key' => 'custom:new',
            'page' => 'Content',
            'tableAttributes' => ['postDate'],
            'defaultSort' => ['postDate', 'desc'],
            'defaultViewMode' => 'cards',
            'label' => 'Fresh Custom',
            'condition' => [
                'elementType' => TestElementSourcesElement::class,
                'fieldContext' => 'global',
                'class' => ElementCondition::class,
            ],
            'sites' => false,
            'userGroups' => ['group-editors'],
        ],
        [
            'type' => ElementSources::TYPE_HEADING,
            'key' => 'heading:content',
            'page' => 'Content',
            'heading' => 'Content Heading',
        ],
        [
            'type' => ElementSources::TYPE_NATIVE,
            'key' => 'native-enabled',
            'page' => 'Archived',
            'tableAttributes' => ['slug'],
            'defaultSort' => ['slug', 'desc'],
            'defaultViewMode' => 'cards',
            'disabled' => false,
        ],
        [
            'type' => ElementSources::TYPE_NATIVE,
            'key' => 'native-disabled',
            'page' => 'Archived',
            'tableAttributes' => ['slug'],
            'disabled' => true,
        ],
    ]))
        ->and(normalizeConfig($projectConfig->get(sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCE_PAGES, TestElementSourcesElement::class))))->toBe(normalizeConfig([
            'Content' => ['label' => 'Content'],
            'Archived' => ['label' => 'Archived'],
        ]));
});

it('preserves blank heading values when storing and returning source settings', function () {
    $projectConfig = app(ProjectConfig::class);

    $response = postJson(action([ElementSourcesController::class, 'store']), [
        'elementType' => TestElementSourcesElement::class,
        'sourceOrder' => [
            'heading:blank',
            'native-enabled',
        ],
        'sourcePages' => [
            'heading:blank' => 'Content',
            'native-enabled' => 'Content',
        ],
        'pageSettings' => [
            'Content' => [
                'label' => 'Content',
            ],
        ],
        'sources' => [
            'heading:blank' => [
                'heading' => '',
            ],
            'native-enabled' => [
                'tableAttributes' => ['slug'],
                'enabled' => true,
            ],
        ],
    ]);

    $response->assertOk();

    expect(normalizeConfig($projectConfig->get(sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCES, TestElementSourcesElement::class))))->toBe(normalizeConfig([
        [
            'type' => ElementSources::TYPE_HEADING,
            'key' => 'heading:blank',
            'page' => 'Content',
            'heading' => '',
        ],
        [
            'type' => ElementSources::TYPE_NATIVE,
            'key' => 'native-enabled',
            'page' => 'Content',
            'tableAttributes' => ['slug'],
            'disabled' => false,
        ],
    ]));

    postJson(action([ElementSourcesController::class, 'show']), [
        'elementType' => TestElementSourcesElement::class,
    ])
        ->assertOk()
        ->assertJsonPath('sources.0.type', ElementSources::TYPE_HEADING)
        ->assertJsonPath('sources.0.heading', '');

    $projectConfig->set(sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCES, TestElementSourcesElement::class), [
        [
            'type' => ElementSources::TYPE_HEADING,
            'key' => 'heading:malformed',
            'page' => 'Content',
        ],
        [
            'type' => ElementSources::TYPE_NATIVE,
            'key' => 'native-enabled',
            'page' => 'Content',
        ],
    ]);

    postJson(action([ElementSourcesController::class, 'show']), [
        'elementType' => TestElementSourcesElement::class,
    ])
        ->assertOk()
        ->assertJsonPath('sources.0.type', ElementSources::TYPE_HEADING)
        ->assertJsonPath('sources.0.heading', '');
});

it('stores single-page source settings without page reordering', function () {
    $projectConfig = app(ProjectConfig::class);

    $response = postJson(action([ElementSourcesController::class, 'store']), [
        'elementType' => TestSinglePageElementSourcesElement::class,
        'sourceOrder' => ['native-only'],
        'sources' => [
            'native-only' => [
                'tableAttributes' => [],
                'enabled' => false,
            ],
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('disabledSourceKeys.0', 'native-only');

    expect(normalizeConfig($projectConfig->get(sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCES, TestSinglePageElementSourcesElement::class))))->toBe(normalizeConfig([
        [
            'type' => ElementSources::TYPE_NATIVE,
            'key' => 'native-only',
            'tableAttributes' => '-',
            'disabled' => true,
        ],
    ]))
        ->and($projectConfig->get(sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCE_PAGES, TestSinglePageElementSourcesElement::class)))->toBeNull();
});

it('normalizes posted table attribute options to attribute keys', function () {
    $projectConfig = app(ProjectConfig::class);

    $response = postJson(action([ElementSourcesController::class, 'store']), [
        'elementType' => User::class,
        'sourceOrder' => ['*'],
        'sources' => [
            '*' => [
                'tableAttributes' => [
                    '',
                    'email',
                    ['label' => 'Groups', 'value' => 'groups'],
                ],
                'enabled' => true,
            ],
        ],
    ]);

    $response->assertOk();

    expect($projectConfig->get(sprintf('%s.%s.0.tableAttributes', ProjectConfig::PATH_ELEMENT_SOURCES, User::class)))
        ->toBe(['email', 'groups']);
});

function normalizeConfig(mixed $value): mixed
{
    if (! is_array($value)) {
        return $value;
    }

    if (array_is_list($value)) {
        return array_map(normalizeConfig(...), $value);
    }

    ksort($value);

    foreach ($value as $key => $nestedValue) {
        $value[$key] = normalizeConfig($nestedValue);
    }

    return $value;
}

class TestElementSourcesElement extends Element
{
    #[Override]
    public static function displayName(): string
    {
        return 'Test Element';
    }

    #[Override]
    public static function pluralDisplayName(): string
    {
        return 'Test Elements';
    }

    #[Override]
    public static function multiPageSources(): bool
    {
        return true;
    }

    #[Override]
    protected static function defineSources(string $context): array
    {
        return [
            [
                'heading' => 'Primary Sources',
            ],
            [
                'key' => 'structured',
                'label' => 'Structured',
                'structureId' => 1,
            ],
            [
                'key' => 'fallback',
                'label' => 'Fallback',
            ],
        ];
    }

    #[Override]
    protected static function defineFieldLayouts(?string $source): array
    {
        return match ($source) {
            'structured' => [],
            'fallback' => [],
            default => app(Fields::class)->getLayoutsByType(static::class)->all(),
        };
    }

    #[Override]
    protected static function defineTableAttributes(): array
    {
        return [
            'title' => ['label' => 'Test Element'],
            'slug' => ['label' => 'Slug'],
            'postDate' => ['label' => 'Post Date'],
        ];
    }

    #[Override]
    protected static function defineDefaultTableAttributes(string $source): array
    {
        return match ($source) {
            'structured' => ['slug'],
            default => ['title'],
        };
    }

    #[Override]
    public function getCanonical(bool $anySite = false): ElementInterface
    {
        return $this;
    }
}

class TestSinglePageElementSourcesElement extends TestElementSourcesElement
{
    #[Override]
    public static function multiPageSources(): bool
    {
        return false;
    }
}

it('records an “all” source scope by omitting the key', function () {
    $projectConfig = app(ProjectConfig::class);

    postJson(action([ElementSourcesController::class, 'store']), [
        'elementType' => TestElementSourcesElement::class,
        'sourceOrder' => ['custom:everywhere'],
        'sourcePages' => ['custom:everywhere' => 'Content'],
        'pageSettings' => ['Content' => ['label' => 'Content']],
        'sources' => [
            'custom:everywhere' => [
                'label' => 'Everywhere',
                'condition' => [
                    'class' => ElementCondition::class,
                    'elementType' => TestElementSourcesElement::class,
                    'conditionRules' => [],
                ],
                // The “All” checkbox posts the token as the control's whole
                // array; an untouched scope posts back the bare token it was
                // seeded with. Both mean “all”.
                'sites' => [Choice::ALL_VALUE],
                'userGroups' => Choice::ALL_VALUE,
            ],
        ],
    ])->assertOk();

    $config = collect($projectConfig->get(sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCES, TestElementSourcesElement::class)))
        ->keyBy('key');

    expect($config['custom:everywhere'])->not->toHaveKeys(['sites', 'userGroups']);
});

it('normalizes the Form defaultSort shape and an empty source scope', function () {
    $projectConfig = app(ProjectConfig::class);

    postJson(action([ElementSourcesController::class, 'store']), [
        'elementType' => TestElementSourcesElement::class,
        'sourceOrder' => ['native-enabled', 'custom:scoped'],
        'sourcePages' => [
            'native-enabled' => 'Content',
            'custom:scoped' => 'Content',
        ],
        'pageSettings' => ['Content' => ['label' => 'Content']],
        'sources' => [
            'native-enabled' => [
                'tableAttributes' => ['slug'],
                'defaultSort' => ['attr' => 'slug', 'dir' => 'desc'],
                'enabled' => true,
            ],
            'custom:scoped' => [
                'label' => 'Scoped',
                'tableAttributes' => ['slug'],
                'condition' => [
                    'class' => ElementCondition::class,
                    'elementType' => TestElementSourcesElement::class,
                    'conditionRules' => [],
                ],
                // An empty selection means “none”, not “all”.
                'sites' => [],
                'userGroups' => [],
            ],
        ],
    ])->assertOk();

    $config = collect($projectConfig->get(sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCES, TestElementSourcesElement::class)))
        ->keyBy('key');

    expect($config['native-enabled']['defaultSort'])->toBe(['slug', 'desc'])
        ->and($config['custom:scoped']['sites'])->toBeFalse()
        ->and($config['custom:scoped']['userGroups'])->toBeFalse();
});
