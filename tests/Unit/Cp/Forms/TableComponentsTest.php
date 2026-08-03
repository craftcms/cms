<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\ComponentRegistry;
use CraftCms\Cms\Cp\Components\EditableTable;
use CraftCms\Cms\Cp\Components\Field;
use CraftCms\Cms\Cp\Forms\Form;
use CraftCms\Cms\Cp\Forms\FormElementTypes;

it('renders editable table values through the existing controller element', function () {
    $columns = [
        ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
        ['key' => 'icon', 'label' => 'Icon', 'type' => 'icon'],
        ['key' => 'published', 'label' => 'Published', 'type' => 'checkbox', 'radioMode' => true, 'toggle' => ['!icon']],
    ];
    $value = [
        ['rowId' => 'story-row', 'title' => 'Lead story', 'published' => true],
    ];
    $html = EditableTable::make()
        ->name('rows')
        ->sourceName('storyRows')
        ->value($value)
        ->columns($columns)
        ->addRowLabel('Add story')
        ->defaultRow(['published' => false])
        ->includeRowId()
        ->readOnly()
        ->toHtml();

    expect($html)->toContainTag('craft-editable-table', [
        'name' => 'rows',
    ])->toContainTag('tr', ['data-id' => '0'])
        ->toContainTag('input', [
            'name' => 'rows[0][rowId]',
            'value' => 'story-row',
        ]);
});

it('renders an empty editable table through the existing controller element', function () {
    expect(EditableTable::make()->toHtml())->toContainTag('craft-editable-table', [
        'name' => 'editableTable',
    ])->toContainTag('table');
});

it('renders fixed keyed rows through the editable table', function () {
    $columns = [
        ['key' => 'uriFormat', 'label' => 'Entry URI Format', 'type' => 'text', 'placeholder' => 'Leave blank', 'code' => true],
        ['key' => 'template', 'label' => 'Template', 'type' => 'text', 'code' => true],
    ];
    $rows = [['key' => 'english', 'label' => 'English']];
    $value = ['english' => ['uriFormat' => 'news/{slug}', 'template' => 'entries/article']];
    $html = EditableTable::make()
        ->name('siteSettings')
        ->value($value)
        ->columns($columns)
        ->fixedRows($rows)
        ->keyed()
        ->readOnly()
        ->toHtml();

    expect($html)->toContainTag('craft-editable-table', [
        'name' => 'siteSettings',
        'keyed' => true,
    ])->toContainTag('tr', ['data-id' => 'english']);
});

it('registers and deterministically projects the existing editable table markup', function () {
    $editable = EditableTable::make()
        ->name('columns')
        ->sourceName('columnForms')
        ->columns([['key' => 'heading', 'label' => 'Heading', 'type' => 'text']])
        ->addRowLabel('Add a column')
        ->defaultRow(['heading' => ''])
        ->keyed()
        ->definesColumns()
        ->attributes(['data' => ['setting' => 'columns']]);
    $form = Form::make([
        Field::make($editable),
    ]);
    $firstProjection = $form->toArray();
    $tableHtml = $firstProjection['elements'][0]['children'][0]['props']['tableHtml'];
    unset($firstProjection['elements'][0]['children'][0]['props']['tableHtml']);

    $expected = [
        'elements' => [[
            'type' => 'craft:field',
            'children' => [[
                'type' => 'craft:editable-table-input',
                'name' => 'columns',
                'props' => [
                    'sourceName' => 'columnForms',
                    'columns' => [['key' => 'heading', 'label' => 'Heading', 'type' => 'text']],
                    'addRowLabel' => 'Add a column',
                    'defaultRow' => ['heading' => ''],
                    'keyed' => true,
                    'definesColumns' => true,
                ],
                'attributes' => ['data' => ['setting' => 'columns']],
            ]],
        ]],
    ];
    expect(app(ComponentRegistry::class)->make('editable-table'))->toBeInstanceOf(EditableTable::class)
        ->and(app(FormElementTypes::class)->isRegistered(EditableTable::formElementType()))->toBeTrue()
        ->and($tableHtml)->toContainTag('craft-editable-table', ['name' => 'columns'])
        ->and($firstProjection)->toBe($expected)
        ->and($form->toArray()['elements'][0]['children'][0]['props']['tableHtml'])->toBe($tableHtml);
});

it('rejects invalid portable table configuration before projection', function (EditableTable $component, string $option) {
    expect(fn () => Form::make([
        Field::make($component),
    ])->toArray())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "%s" is invalid for Form output.', $component::class, $option),
    );
})->with([
    'editable name' => [fn () => EditableTable::make()->name(null), 'name'],
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
    'invalid editable toggle' => [
        fn () => EditableTable::make()->name('rows')->columns([
            ['key' => 'value', 'label' => 'Value', 'type' => 'text', 'toggle' => ['value', 1]],
        ]),
        'columns[0].toggle',
    ],
    'invalid fixed row' => [
        fn () => EditableTable::make()->name('rows')->fixedRows([
            ['key' => 'english', 'label' => 'English', 'unknown' => true],
        ]),
        'fixedRows[0]',
    ],
]);

it('rejects invalid HTML table configuration before rendering', function (EditableTable $component, string $option) {
    expect(fn () => $component->toHtml())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "%s" is invalid for HTML output.', $component::class, $option),
    );
})->with([
    'editable value' => [
        fn () => EditableTable::make()->value([['value' => fn () => 'value']]),
        'value[0][value]',
    ],
    'keyed value' => [
        fn () => EditableTable::make()->keyed()->value(['english' => ['uri' => fn () => 'value']]),
        'value[english][uri]',
    ],
    'ordered keyed value' => [
        fn () => EditableTable::make()->keyed()->value([['uri' => 'news/{slug}']]),
        'value',
    ],
]);
