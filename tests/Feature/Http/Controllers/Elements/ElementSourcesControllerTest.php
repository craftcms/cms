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
use CraftCms\Cms\Http\Controllers\Elements\ElementSourcesController;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\UserGroup;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    app(Fields::class)->refreshFields();
});

it('returns fully normalized source customization data', function () {
    $primarySite = Sites::getPrimarySite();

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
        'uid' => 'group-editors',
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
        ->assertJsonPath('multiPage', true)
        ->assertJsonPath('sources.0.type', ElementSources::TYPE_HEADING)
        ->assertJsonPath('sources.0.page', 'Test Elements')
        ->assertJsonPath('sources.1.page', 'Test Elements')
        ->assertJsonPath('sources.1.sortOptions.0.attr', 'structure')
        ->assertJsonPath('sources.1.defaultSort.0', 'slug')
        ->assertJsonPath('sources.1.defaultSort.1', 'asc')
        ->assertJsonPath('sources.1.tableAttributes.0.0', 'slug')
        ->assertJsonPath('sources.1.tableAttributes.0.1', 'Slug')
        ->assertJsonPath('sources.2.defaultSort.0', 'id')
        ->assertJsonPath('sources.2.defaultSort.1', 'asc')
        ->assertJsonPath('sources.3.defaultSort.0', 'field:'.$field->uid)
        ->assertJsonPath('sources.3.defaultSort.1', 'desc')
        ->assertJsonPath('sources.3.sites.0', $primarySite->uid)
        ->assertJsonPath('sources.3.userGroups', [])
        ->assertJsonMissingPath('sources.3.condition')
        ->assertJsonPath('sources.4.sites', [])
        ->assertJsonPath('pageSettings.entries.label', 'Entries')
        ->assertJsonPath('defaultSortOptions.0.attr', 'field:'.$field->uid)
        ->assertJsonPath('availableTableAttributes.0.0', 'title')
        ->assertJsonPath('customFieldAttributes.0.0', 'field:'.$field->uid)
        ->assertJsonPath('customFieldAttributes.0.1', 'Preview Field')
        ->assertJsonPath('elementTypeName', 'Test Element')
        ->assertJsonPath('userGroups.0.label', t($userGroup->name, category: 'site'))
        ->assertJsonPath('userGroups.0.value', $userGroup->uid);

    $payload = $response->json();
    $structuredSortAttrs = array_column($payload['sources'][1]['sortOptions'], 'attr');
    $baseSortAttrs = array_column($payload['baseSortOptions'], 'attr');
    $viewModes = collect($payload['viewModes']);

    expect($payload['sources'][3]['conditionBuilderHtml'])->toContain('condition-container')
        ->and($payload['sources'][3]['conditionBuilderJs'])->toContain('Craft.initUiElements')
        ->and($payload['sources'][1]['availableTableAttributes'])->toBe([])
        ->and($payload['sources'][3]['tableAttributes'][0])->toBe(['title', 'Test Element'])
        ->and($structuredSortAttrs)->toContain('title', 'slug', 'postDate')
        ->and($baseSortAttrs)->toContain('id', 'title', 'slug', 'postDate')
        ->and($viewModes->contains(fn (array $viewMode) => $viewMode['mode'] === 'table' && is_string($viewMode['iconSvg'])))->toBeTrue()
        ->and($payload['conditionBuilderHtml'])->toContain('__SOURCE_KEY__')
        ->and($payload['conditionBuilderJs'])->toContain('Craft.initUiElements')
        ->and($payload['headHtml'])->toBeString()
        ->and($payload['bodyHtml'])->toBeString();
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
