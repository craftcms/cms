<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\ComponentRegistry;
use CraftCms\Cms\Cp\Components\EditableTable;
use CraftCms\Cms\Cp\Components\Field;
use CraftCms\Cms\Cp\Components\KeyedTable;
use CraftCms\Cms\Cp\FormDefinitions\FormDefinition;
use CraftCms\Cms\Cp\FormDefinitions\FormElementTypes;

it('renders editable table configuration and host values through the shared primitive', function () {
    $columns = [
        ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
        ['key' => 'published', 'label' => 'Published', 'type' => 'checkbox'],
    ];
    $value = [
        ['rowId' => 'story-row', 'title' => 'Lead story', 'published' => true],
    ];
    $html = EditableTable::make()
        ->name('rows')
        ->value($value)
        ->columns($columns)
        ->addRowLabel('Add story')
        ->defaultRow(['published' => false])
        ->includeRowId()
        ->readOnly()
        ->toHtml();

    expect($html)->toContainTag('craft-editable-table', [
        'name' => 'rows',
        'value' => json_encode($value, JSON_THROW_ON_ERROR),
        'columns' => json_encode($columns, JSON_THROW_ON_ERROR),
        'add-row-label' => 'Add story',
        'default-row' => json_encode(['published' => false], JSON_THROW_ON_ERROR),
        'include-row-id' => true,
        'readonly' => true,
    ]);
});

it('renders an empty editable table default row as a JSON object', function () {
    expect(EditableTable::make()->toHtml())->toContainTag('craft-editable-table', [
        'default-row' => '{}',
    ]);
});

it('renders keyed table configuration and host values through the shared primitive', function () {
    $columns = [
        ['key' => 'uriFormat', 'label' => 'Entry URI Format', 'placeholder' => 'Leave blank', 'code' => true],
        ['key' => 'template', 'label' => 'Template', 'code' => true],
    ];
    $rows = [['key' => 'english', 'label' => 'English']];
    $value = ['english' => ['uriFormat' => 'news/{slug}', 'template' => 'entries/article']];
    $html = KeyedTable::make()
        ->name('siteSettings')
        ->value($value)
        ->columns($columns)
        ->rows($rows)
        ->readOnly()
        ->toHtml();

    expect($html)->toContainTag('craft-keyed-table', [
        'name' => 'siteSettings',
        'value' => json_encode($value, JSON_THROW_ON_ERROR),
        'columns' => json_encode($columns, JSON_THROW_ON_ERROR),
        'rows' => json_encode($rows, JSON_THROW_ON_ERROR),
        'readonly' => true,
    ]);
});

it('renders an empty keyed table value as a JSON object', function () {
    expect(KeyedTable::make()->toHtml())->toContainTag('craft-keyed-table', [
        'value' => '{}',
    ]);
});

it('registers and deterministically projects both table contracts without host state', function () {
    $editable = EditableTable::make()
        ->name('columns')
        ->columns([['key' => 'heading', 'label' => 'Heading', 'type' => 'text']])
        ->addRowLabel('Add a column')
        ->defaultRow(['heading' => ''])
        ->keyed()
        ->definesColumns()
        ->attributes(['data' => ['setting' => 'columns']]);
    $keyed = KeyedTable::make()
        ->name('sites')
        ->columns([['key' => 'uri', 'label' => 'URI']])
        ->rows([['key' => 'english', 'label' => 'English']]);
    $definition = FormDefinition::make([
        Field::make()->input($editable),
        Field::make()->input($keyed),
    ]);
    $expected = [
        'elements' => [[
            'type' => 'craft:field',
            'children' => [[
                'type' => 'craft:editable-table-input',
                'name' => 'columns',
                'props' => [
                    'columns' => [['key' => 'heading', 'label' => 'Heading', 'type' => 'text']],
                    'addRowLabel' => 'Add a column',
                    'defaultRow' => ['heading' => ''],
                    'keyed' => true,
                    'definesColumns' => true,
                ],
                'attributes' => ['data' => ['setting' => 'columns']],
            ]],
        ], [
            'type' => 'craft:field',
            'children' => [[
                'type' => 'craft:keyed-table-input',
                'name' => 'sites',
                'props' => [
                    'columns' => [['key' => 'uri', 'label' => 'URI']],
                    'rows' => [['key' => 'english', 'label' => 'English']],
                ],
            ]],
        ]],
    ];
    $firstProjection = $definition->toArray();

    expect(app(ComponentRegistry::class)->make('editable-table'))->toBeInstanceOf(EditableTable::class)
        ->and(app(ComponentRegistry::class)->make('keyed-table'))->toBeInstanceOf(KeyedTable::class)
        ->and(app(FormElementTypes::class)->isRegistered(EditableTable::formElementType()))->toBeTrue()
        ->and(app(FormElementTypes::class)->isRegistered(KeyedTable::formElementType()))->toBeTrue()
        ->and($firstProjection)->toBe($expected)
        ->and($definition->toArray())->toBe($firstProjection);
});

it('rejects host-owned table state during projection', function (EditableTable|KeyedTable $component, string $option) {
    expect(fn () => FormDefinition::make([
        Field::make()->input($component),
    ])->toArray())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "%s" is not supported for Form Definition output.', $component::class, $option),
    );
})->with([
    'editable values' => [fn () => EditableTable::make()->name('rows')->value([]), 'value'],
    'editable read-only state' => [fn () => EditableTable::make()->name('rows')->readOnly(false), 'readOnly'],
    'keyed values' => [fn () => KeyedTable::make()->name('rows')->value([]), 'value'],
    'keyed read-only state' => [fn () => KeyedTable::make()->name('rows')->readOnly(false), 'readOnly'],
]);

it('rejects invalid portable table configuration before projection', function (EditableTable|KeyedTable $component, string $option) {
    expect(fn () => FormDefinition::make([
        Field::make()->input($component),
    ])->toArray())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "%s" is not supported for Form Definition output.', $component::class, $option),
    );
})->with([
    'editable name' => [fn () => EditableTable::make(), 'name'],
    'unsupported editable column' => [
        fn () => EditableTable::make()->name('rows')->columns([
            ['key' => 'value', 'label' => 'Value', 'type' => 'unsupported'],
        ]),
        'columns[0].type',
    ],
    'non-serializable editable default' => [
        fn () => EditableTable::make()->name('rows')->defaultRow(['value' => fn () => 'value']),
        'defaultRow[value]',
    ],
    'keyed name' => [fn () => KeyedTable::make(), 'name'],
    'invalid keyed column' => [
        fn () => KeyedTable::make()->name('rows')->columns([
            ['key' => 'value', 'label' => 'Value', 'code' => 'yes'],
        ]),
        'columns[0].code',
    ],
    'invalid keyed row' => [
        fn () => KeyedTable::make()->name('rows')->rows([
            ['key' => 'english', 'label' => 'English', 'unknown' => true],
        ]),
        'rows[0].unknown',
    ],
]);

it('rejects non-serializable HTML table values before rendering', function (EditableTable|KeyedTable $component, string $option) {
    expect(fn () => $component->toHtml())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "%s" is not supported for HTML output.', $component::class, $option),
    );
})->with([
    'editable value' => [
        fn () => EditableTable::make()->value([['value' => fn () => 'value']]),
        'value[0][value]',
    ],
    'keyed value' => [
        fn () => KeyedTable::make()->value(['english' => ['uri' => fn () => 'value']]),
        'value[english][uri]',
    ],
    'ordered keyed value' => [
        fn () => KeyedTable::make()->value([['uri' => 'news/{slug}']]),
        'value',
    ],
]);
