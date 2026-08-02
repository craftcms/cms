<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\EditableTable;
use CraftCms\Cms\Field\Addresses;
use CraftCms\Cms\Field\Markdown;
use CraftCms\Cms\Field\Table;

it('projects every address field setting through native form elements', function () {
    $field = new Addresses([
        'minAddresses' => 1,
        'maxAddresses' => 3,
        'viewMode' => Addresses::VIEW_MODE_INDEX,
    ]);

    $definition = $field->getSettingsFormDefinition(true)?->toArray();
    $inputs = specializedFieldInputs($definition);

    expect(array_keys($inputs))->toBe([
        'minAddresses',
        'maxAddresses',
        'viewMode',
    ])->and($inputs['viewMode']['props']['options'])->toBe([
        ['label' => 'Cards', 'value' => Addresses::VIEW_MODE_CARDS],
        ['label' => 'Index', 'value' => Addresses::VIEW_MODE_INDEX],
    ])->and($field->getSettings())->toMatchArray([
        'minAddresses' => 1,
        'maxAddresses' => 3,
        'viewMode' => Addresses::VIEW_MODE_INDEX,
    ]);

    foreach (specializedFieldContainers($definition) as $fieldContainer) {
        expect($fieldContainer['props']['readOnly'])->toBeTrue();
    }
});

it('projects every markdown option and conditional setting without legacy markup', function () {
    $field = new class(['flavor' => 'gfm-comment', 'inlineOnly' => true, 'showToolbar' => true, 'toolbarButtons' => ['bold', 'link'], 'showStats' => true, 'placeholder' => 'Write something', 'initialRows' => 12, 'charLimit' => 500, 'linkSettingsTypes' => ['url'], 'linkSettingsShowLabelField' => true, 'linkSettingsAdvancedFields' => ['title'], 'availableVolumes' => ['volume-one'], 'uploadVolume' => 'volume-one', 'showUnpermittedVolumes' => true, 'showUnpermittedFiles' => true, 'encode' => false, 'sanitizeHtml' => true, 'htmlSanitizer' => 'default']) extends Markdown
    {
        public function volumeOptions(): array
        {
            return [
                ['label' => 'Uploads', 'value' => 'volume-one'],
                ['label' => 'Documents', 'value' => 'volume-two'],
            ];
        }
    };

    $definition = $field->getSettingsFormDefinition(false)?->toArray();
    $inputs = specializedFieldInputs($definition);

    expect(array_keys($inputs))->toContain(
        'flavor',
        'inlineOnly',
        'showToolbar',
        'toolbarButtons',
        'showStats',
        'placeholder',
        'initialRows',
        'charLimit',
        'byteLimit',
        'linkSettings.types',
        'linkSettings.typeSettings.url.allowRootRelativeUrls',
        'linkSettings.typeSettings.url.allowAnchors',
        'linkSettings.typeSettings.url.allowCustomSchemes',
        'linkSettings.showLabelField',
        'linkSettings.advancedFields',
        'availableVolumes',
        'showUnpermittedVolumes',
        'showUnpermittedFiles',
        'uploadVolume',
        'encode',
        'sanitizeHtml',
        'htmlSanitizer',
    )->and(specializedFieldContainer($definition, 'flavor')['visibleWhen'])->toBe([
        'name' => 'encode',
        'operator' => 'equals',
        'value' => false,
    ])->and(specializedFieldContainer($definition, 'toolbarButtons')['visibleWhen'])->toBe([
        'name' => 'showToolbar',
        'operator' => 'equals',
        'value' => true,
    ])->and(specializedFieldContainer($definition, 'htmlSanitizer')['visibleWhen'])->toBe([
        'name' => 'sanitizeHtml',
        'operator' => 'equals',
        'value' => true,
    ])->and($inputs['toolbarButtons']['props']['options'][0])->toMatchArray([
        'label' => 'Bold',
        'value' => 'bold',
        'icon' => 'bold',
    ])->and($inputs['availableVolumes']['props'])->toBe([
        'options' => [
            ['label' => 'All', 'value' => '*'],
            ['label' => 'Uploads', 'value' => 'volume-one'],
            ['label' => 'Documents', 'value' => 'volume-two'],
        ],
        'allOption' => '*',
    ])->and($field->toolbarButtons)->toBe(['bold', 'link'])
        ->and($field->charLimit)->toBe(500)
        ->and($field->linkSettingsTypes)->toBe(['url'])
        ->and($field->availableVolumes)->toBe(['volume-one'])
        ->and($field->uploadVolume)->toBe('volume-one')
        ->and(json_encode($definition, JSON_THROW_ON_ERROR))->not->toContain('<script', '<div');
});

it('reports when markdown has no volumes and omits upload settings', function () {
    $field = new class extends Markdown
    {
        public function volumeOptions(): array
        {
            return [];
        }
    };

    $definition = $field->getSettingsFormDefinition(false)?->toArray();
    $inputs = specializedFieldInputs($definition);
    $availableVolumes = specializedFieldContainer($definition, 'availableVolumes');

    expect($inputs)->not->toHaveKey('uploadVolume')
        ->and($inputs['availableVolumes']['props']['options'])->toBe([])
        ->and($availableVolumes['props']['warning'])->toBe('No volumes exist yet.')
        ->and($availableVolumes['props']['readOnly'])->toBeTrue();
});

it('projects ordered table columns and type-specific default inputs', function () {
    $columns = [
        'headline' => [
            'heading' => 'Headline',
            'handle' => 'headline',
            'width' => '40%',
            'type' => 'singleline',
        ],
        'published' => [
            'heading' => 'Published?',
            'handle' => 'published',
            'width' => '15%',
            'type' => 'checkbox',
        ],
        'category' => [
            'heading' => 'Category',
            'handle' => 'category',
            'width' => '45%',
            'type' => 'select',
            'options' => [
                ['label' => 'News', 'value' => 'news', 'default' => true],
                ['label' => 'Opinion', 'value' => 'opinion', 'default' => false],
            ],
        ],
    ];
    $defaults = [
        [
            'rowId' => 'first-row',
            'headline' => 'Lead story',
            'published' => true,
            'category' => 'news',
        ],
        [
            'rowId' => 'second-row',
            'headline' => 'Analysis',
            'published' => false,
            'category' => 'opinion',
        ],
    ];
    $field = new Table([
        'columns' => $columns,
        'defaults' => $defaults,
        'staticRows' => false,
        'minRows' => 1,
        'maxRows' => 5,
        'addRowLabel' => 'Add story',
    ]);

    $definition = $field->getSettingsFormDefinition(true)?->toArray();
    $inputs = specializedFieldInputs($definition);

    expect(array_keys($inputs))->toBe([
        'columns',
        'defaults',
        'staticRows',
        'minRows',
        'maxRows',
        'addRowLabel',
    ])->and($inputs['columns']['type'])->toBe('craft:editable-table-input')
        ->and($inputs['columns']['props']['sourceName'])->toBe('columns')
        ->and($inputs['columns']['props']['keyed'])->toBeTrue()
        ->and($inputs['columns']['props']['addRowLabel'])->toBe('Add a column')
        ->and(array_column($inputs['columns']['props']['columns'], 'key'))->toBe([
            'heading',
            'handle',
            'width',
            'type',
        ])->and($inputs['columns']['props']['columns'][3]['options'])->toContain([
            'label' => 'Dropdown',
            'value' => 'select',
        ])->and($inputs['defaults']['type'])->toBe('craft:editable-table-input')
        ->and($inputs['defaults']['props']['sourceName'])->toBe('defaults')
        ->and($inputs['defaults']['props']['includeRowId'])->toBeTrue()
        ->and($inputs['defaults']['props']['columns'])->toBe([
            ['key' => 'headline', 'label' => 'Headline', 'type' => 'text', 'width' => '40%'],
            ['key' => 'published', 'label' => 'Published?', 'type' => 'checkbox', 'width' => '15%'],
            [
                'key' => 'category',
                'label' => 'Category',
                'type' => 'select',
                'width' => '45%',
                'options' => [
                    ['label' => 'News', 'value' => 'news', 'default' => true],
                    ['label' => 'Opinion', 'value' => 'opinion', 'default' => false],
                ],
            ],
        ])->and(specializedFieldContainer($definition, 'minRows')['visibleWhen'])->toBe([
            'name' => 'staticRows',
            'operator' => 'equals',
            'value' => false,
        ])->and(specializedFieldContainer($definition, 'maxRows')['visibleWhen'])->toBe([
            'name' => 'staticRows',
            'operator' => 'equals',
            'value' => false,
        ])->and(specializedFieldContainer($definition, 'addRowLabel')['visibleWhen'])->toBe([
            'name' => 'staticRows',
            'operator' => 'equals',
            'value' => false,
        ])->and($field->columns)->toBe($columns)
        ->and($field->defaults)->toBe($defaults);
});

it('rejects unsupported editable table column types', function () {
    expect(fn () => EditableTable::make()->name('settings')->columns([
        ['key' => 'value', 'label' => 'Value', 'type' => 'unsupported'],
    ])->toFormElementData())->toThrow(InvalidArgumentException::class, 'columns[0].type');
});

function specializedFieldInputs(?array $definition): array
{
    $inputs = [];

    foreach (specializedFormElements($definition) as $element) {
        if (isset($element['name'])) {
            $inputs[$element['name']] = $element;
        }
    }

    return $inputs;
}

function specializedFieldContainers(?array $definition): array
{
    return array_values(array_filter(
        specializedFormElements($definition),
        fn (array $element): bool => $element['type'] === 'craft:field',
    ));
}

function specializedFormElements(?array $definition): array
{
    $flattened = [];
    $visit = function (array $elements) use (&$flattened, &$visit): void {
        foreach ($elements as $element) {
            $flattened[] = $element;
            $visit($element['children'] ?? []);
        }
    };
    $visit($definition['elements'] ?? []);

    return $flattened;
}

function specializedFieldContainer(?array $definition, string $name): array
{
    return array_find(
        specializedFieldContainers($definition),
        fn (array $field): bool => $field['children'][0]['name'] === $name,
    );
}
