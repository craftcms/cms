<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\Checkbox;
use CraftCms\Cms\Cp\Components\CheckboxSelect;
use CraftCms\Cms\Cp\Components\Combobox;
use CraftCms\Cms\Cp\Components\ComponentRegistry;
use CraftCms\Cms\Cp\Components\Field;
use CraftCms\Cms\Cp\Components\Select;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Cp\Forms\Contracts\FormElement;
use CraftCms\Cms\Cp\Forms\Form;
use CraftCms\Cms\Cp\Forms\FormElementTypes;

it('renders select options, groups, prompts, and disabled choices through craft-select', function () {
    $html = Select::make()
        ->id('status')
        ->name('status')
        ->value('published')
        ->options([
            ['label' => 'Choose a status', 'value' => null],
            [
                'type' => 'optgroup',
                'label' => 'Visible',
                'options' => [
                    ['label' => 'Published', 'value' => 'published', 'data' => ['status' => 'live']],
                    ['label' => 'Archived', 'value' => 'archived', 'disabled' => true],
                ],
            ],
        ])
        ->toHtml();

    expect($html)->toContainTag('craft-select', [
        'name' => 'status',
        'model-value' => 'published',
    ])->and($html)->toContainTag('select', [
        'slot' => 'input',
        'id' => 'status',
        'name' => 'status',
    ])->and($html)->toContainTag('option', [
        'value' => '',
    ])->and($html)->toContainTag('optgroup', [
        'label' => 'Visible',
    ])->and($html)->toContainTag('option', [
        'value' => 'published',
        'selected' => true,
        'data-status' => 'live',
    ])->and($html)->toContainTag('option', [
        'value' => 'archived',
        'disabled' => true,
    ]);
});

it('maps the legacy select surface onto the Select component', function () {
    $html = FormFields::selectHtml([
        'id' => 'status',
        'name' => 'status',
        'value' => 'archived',
        'options' => [
            ['label' => 'Choose a status', 'value' => null],
            ['optgroup' => 'Visible'],
            ['label' => 'Published', 'value' => 'published'],
            ['label' => 'Archived', 'value' => 'archived', 'disabled' => true],
        ],
        'class' => ['extra'],
        'containerAttributes' => ['data' => ['control' => 'status']],
        'inputAttributes' => ['data' => ['input' => 'status']],
    ]);

    expect($html)->toContainTag('craft-select', [
        'class' => 'extra select cp-select',
        'data-control' => 'status',
    ])->and($html)->toContainTag('select', [
        'id' => 'status',
        'name' => 'status',
        'data-input' => 'status',
    ])->and($html)->toContainTag('optgroup', ['label' => 'Visible'])
        ->and($html)->toContainTag('option', [
            'value' => 'archived',
            'selected' => true,
            'disabled' => true,
        ]);
});

it('renders combobox options and alias guidance through craft-combobox', function () {
    $html = Combobox::make()
        ->id('path')
        ->name('path')
        ->value('@storage/uploads')
        ->options([
            [
                'type' => 'optgroup',
                'label' => 'Aliases',
                'options' => [
                    ['label' => '@storage', 'value' => '@storage'],
                ],
            ],
        ])
        ->placeholder('/path/to/folder')
        ->allowAliases()
        ->toHtml();

    expect($html)->toContainTag('craft-combobox', [
        'id' => 'path',
        'name' => 'path',
        'model-value' => '@storage/uploads',
        'placeholder' => '/path/to/folder',
        'options' => json_encode([[
            'type' => 'optgroup',
            'label' => 'Aliases',
            'options' => [
                ['label' => '@storage', 'value' => '@storage'],
            ],
        ]], JSON_THROW_ON_ERROR),
    ])
        ->and($html)->toContainTag('craft-callout', ['slot' => 'after'])
        ->and($html)->toContain('This can begin with an environment variable or alias.');
});

it('renders checkbox selection order, all behavior, and sortable presentation', function () {
    $html = CheckboxSelect::make()
        ->id('sources')
        ->name('sources')
        ->options([
            ['label' => 'All', 'value' => '*'],
            ['label' => 'Images', 'value' => 'images', 'color' => '#ff0000'],
            ['label' => 'Documents', 'value' => 'documents'],
            ['label' => 'Locked', 'value' => 'locked', 'disabled' => true],
        ])
        ->values(['documents', 'images'])
        ->allOption('*')
        ->sortable()
        ->toHtml();

    expect($html)->toStartWith('<craft-sortable-checkbox-select>')
        ->and($html)->toContainTag('fieldset', [
            'id' => 'sources',
            'class' => 'cp-checkbox-select',
        ])
        ->and($html)->toContain('value="documents" checked')
        ->and(strpos($html, 'value="documents"'))->toBeLessThan(strpos($html, 'value="images"'))
        ->and($html)->toContain('background-color: #ff0000')
        ->and($html)->toContain('value="locked" disabled')
        ->and($html)->toContain('data-option-disabled="true"');

    $allHtml = CheckboxSelect::make()
        ->id('all-sources')
        ->name('sources')
        ->options([
            ['label' => 'All', 'value' => '*'],
            ['label' => 'Images', 'value' => 'images'],
        ])
        ->values('*')
        ->allOption('*')
        ->toHtml();

    expect($allHtml)->toContain('value="*" checked')
        ->and($allHtml)->toContain('value="images" checked disabled');
});

it('posts an empty checkbox-select value when no choice is selected', function () {
    $html = CheckboxSelect::make()
        ->id('sources')
        ->name('sources')
        ->options([
            ['label' => 'Images', 'value' => 'images'],
        ])
        ->toHtml();

    expect(substr_count($html, 'type="hidden"'))->toBe(1)
        ->and($html)->toContainTag('input', [
            'type' => 'hidden',
            'name' => 'sources',
            'value' => '',
        ])
        ->and($html)->toContain('type="checkbox"')
        ->and($html)->toContain('name="sources[]"');
});

it('registers each choice component with one stable Form Element Type', function (
    string $componentName,
    string $class,
    string $type,
) {
    $component = app(ComponentRegistry::class)->make($componentName);
    $types = app(FormElementTypes::class);

    expect($component)->toBeInstanceOf($class)
        ->and($component)->toBeInstanceOf(FormElement::class)
        ->and($class::formElementType())->toBe($type)
        ->and($class::isFormElementContainer())->toBeFalse()
        ->and($types->isRegistered($type))->toBeTrue()
        ->and($types->isContainer($type))->toBeFalse();
})->with([
    'select' => ['select', Select::class, 'craft:select-input'],
    'combobox' => ['combobox', Combobox::class, 'craft:combobox-input'],
    'checkbox select' => ['checkbox-select', CheckboxSelect::class, 'craft:checkbox-select-input'],
]);

it('projects immutable choice option data and portable presentation', function () {
    $selectOptions = [
        ['label' => 'Choose a status', 'value' => null],
        [
            'type' => 'optgroup',
            'label' => 'Visible',
            'options' => [
                ['label' => 'Published', 'value' => 'published'],
                ['label' => 'Archived', 'value' => 'archived', 'disabled' => true],
            ],
        ],
        [
            'type' => 'optgroup',
            'label' => 'Unavailable',
            'disabled' => true,
            'options' => [
                ['label' => 'Draft', 'value' => 'draft', 'data' => ['reason' => 'restricted']],
            ],
        ],
    ];
    $comboboxOptions = [[
        'type' => 'optgroup',
        'label' => 'Aliases',
        'options' => [
            ['label' => '@storage', 'value' => '@storage', 'data' => ['hint' => '/srv/storage']],
        ],
    ]];
    $checkboxOptions = [
        ['label' => 'All', 'value' => '*'],
        ['label' => 'Images', 'value' => 'images', 'color' => '#ff0000'],
        ['label' => 'Documents', 'value' => 'documents', 'disabled' => true],
    ];

    $form = Form::make([
        Field::make(Select::make()
            ->name('status')
            ->options(fn (): array => $selectOptions)
            ->attributes(['data' => ['setting' => 'status']])),
        Field::make(Combobox::make()
            ->name('path')
            ->options($comboboxOptions)
            ->placeholder('/path/to/folder')
            ->limit(25)
            ->clearable())
            ->tip('This can begin with an environment variable or alias.'),
        Field::make(CheckboxSelect::make()
            ->name('sources')
            ->options($checkboxOptions)
            ->allOption('*')
            ->sortable()),
    ]);

    expect($form->toArray())->toBe([
        'elements' => [[
            'type' => 'craft:field',
            'children' => [[
                'type' => 'craft:select-input',
                'name' => 'status',
                'props' => ['options' => $selectOptions],
                'attributes' => ['data' => ['setting' => 'status']],
            ]],
        ], [
            'type' => 'craft:field',
            'props' => [
                'tip' => 'This can begin with an environment variable or alias.',
            ],
            'children' => [[
                'type' => 'craft:combobox-input',
                'name' => 'path',
                'props' => [
                    'options' => $comboboxOptions,
                    'placeholder' => '/path/to/folder',
                    'limit' => 25,
                    'clearable' => true,
                ],
            ]],
        ], [
            'type' => 'craft:field',
            'children' => [[
                'type' => 'craft:checkbox-select-input',
                'name' => 'sources',
                'props' => [
                    'options' => $checkboxOptions,
                    'allOption' => '*',
                    'sortable' => true,
                ],
            ]],
        ]],
    ]);

    $selectOptions[0]['label'] = 'Mutated';

    expect($form->toArray()['elements'][0]['children'][0]['props']['options'][0]['label'])
        ->toBe('Choose a status');
});

it('requires a local Input Name for choice projection', function (FormElement $component) {
    expect(fn () => Form::make([Field::make($component)])->toArray())
        ->toThrow(
            InvalidArgumentException::class,
            sprintf('%s option "name" is invalid for Form output.', $component::class),
        );
})->with([
    'select' => [fn () => Select::make()->options([])],
    'combobox' => [fn () => Combobox::make()->options([])],
    'checkbox select' => [fn () => CheckboxSelect::make()->options([])],
]);

it('ignores host-owned choice state during projection', function (FormElement $component) {
    expect(Form::make([
        Field::make($component),
    ])->toArray())->toHaveKey('elements.0.children.0.name');
})->with([
    'select value' => [fn () => Select::make()->name('status')->options([])->value(null)],
    'combobox value' => [fn () => Combobox::make()->name('path')->options([])->value(null)],
    'combobox alias guidance' => [fn () => Combobox::make()->name('path')->options([])->allowAliases()],
    'checkbox values' => [fn () => CheckboxSelect::make()->name('sources')->options([])->values([])],
]);

it('rejects non-portable checkbox options during projection', function () {
    $component = CheckboxSelect::make()->name('sources')->options([
        Checkbox::make()->label('Images')->value('images'),
    ]);

    expect(fn () => Form::make([Field::make($component)])->toArray())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "options" is invalid for Form output.', CheckboxSelect::class),
    );
});
