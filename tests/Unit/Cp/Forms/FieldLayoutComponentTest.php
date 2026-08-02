<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\ComponentRegistry;
use CraftCms\Cms\Cp\Components\Field;
use CraftCms\Cms\Cp\Components\FieldLayout;
use CraftCms\Cms\Cp\Forms\Form;
use CraftCms\Cms\Cp\Forms\FormElementTypes;

it('renders host-owned field layout values through the shared primitive', function () {
    $value = [
        'uid' => 'content-layout',
        'tabs' => [[
            'uid' => 'content-tab',
            'name' => 'Content',
            'elements' => [['uid' => 'title-element', 'type' => 'TitleField']],
        ]],
        'generatedFields' => [['uid' => 'reading-time', 'name' => 'Reading time']],
    ];
    $availableElements = [[
        'key' => 'field:title',
        'label' => 'Title',
        'value' => ['type' => 'TitleField'],
        'multiple' => false,
    ]];

    $html = FieldLayout::make()
        ->name('settings[fieldLayouts][content-layout]')
        ->value($value)
        ->availableElements($availableElements)
        ->withGeneratedFields()
        ->readOnly()
        ->toHtml();

    expect($html)->toContainTag('craft-field-layout', [
        'name' => 'settings[fieldLayouts][content-layout]',
        'value' => json_encode($value, JSON_THROW_ON_ERROR),
        'available-elements' => json_encode($availableElements, JSON_THROW_ON_ERROR),
        'with-generated-fields' => true,
        'readonly' => true,
    ]);
});

it('registers and deterministically projects portable field layout configuration', function () {
    $component = FieldLayout::make()
        ->name('fieldLayouts.content-layout')
        ->availableElements([
            [
                'key' => 'field:title',
                'label' => 'Title',
                'value' => ['type' => 'TitleField'],
                'multiple' => false,
            ],
        ])
        ->withGeneratedFields()
        ->attributes(['data' => ['setting' => 'field-layout']]);
    $form = Form::make([
        Field::make($component),
    ]);
    $expected = [
        'elements' => [[
            'type' => 'craft:field',
            'children' => [[
                'type' => 'craft:field-layout-input',
                'name' => 'fieldLayouts.content-layout',
                'props' => [
                    'availableElements' => [[
                        'key' => 'field:title',
                        'label' => 'Title',
                        'value' => ['type' => 'TitleField'],
                        'multiple' => false,
                    ]],
                    'withGeneratedFields' => true,
                ],
                'attributes' => ['data' => ['setting' => 'field-layout']],
            ]],
        ]],
    ];
    $firstProjection = $form->toArray();

    expect(app(ComponentRegistry::class)->make('field-layout'))->toBeInstanceOf(FieldLayout::class)
        ->and(app(FormElementTypes::class)->isRegistered(FieldLayout::formElementType()))->toBeTrue()
        ->and($firstProjection)->toBe($expected)
        ->and($form->toArray())->toBe($firstProjection);
});

it('preserves the complete default transport contract', function () {
    $form = Form::make([
        Field::make(FieldLayout::make()->name('fieldLayout')),
    ]);

    expect($form->toArray())->toBe([
        'elements' => [[
            'type' => 'craft:field',
            'children' => [[
                'type' => 'craft:field-layout-input',
                'name' => 'fieldLayout',
                'props' => [
                    'availableElements' => [],
                    'withGeneratedFields' => false,
                ],
            ]],
        ]],
    ]);
});

it('rejects host-owned field layout state during projection', function (FieldLayout $component, string $option) {
    expect(fn () => Form::make([
        Field::make($component),
    ])->toArray())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "%s" is not supported for Form output.', FieldLayout::class, $option),
    );
})->with([
    'current layout value' => [fn () => FieldLayout::make()->name('fieldLayout')->value([]), 'value'],
    'authorization state' => [fn () => FieldLayout::make()->name('fieldLayout')->readOnly(false), 'readOnly'],
]);

it('rejects invalid portable field layout configuration', function (FieldLayout $component, string $option) {
    expect(fn () => Form::make([
        Field::make($component),
    ])->toArray())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "%s" is not supported for Form output.', FieldLayout::class, $option),
    );
})->with([
    'name' => [fn () => FieldLayout::make(), 'name'],
    'available element shape' => [
        fn () => FieldLayout::make()->name('fieldLayout')->availableElements([
            ['key' => 'field:title', 'label' => 'Title', 'value' => ['type' => 'TitleField']],
        ]),
        'availableElements[0]',
    ],
    'non-serializable element value' => [
        fn () => FieldLayout::make()->name('fieldLayout')->availableElements([
            [
                'key' => 'field:title',
                'label' => 'Title',
                'value' => ['type' => new stdClass],
                'multiple' => false,
            ],
        ]),
        'availableElements[0].value[type]',
    ],
    'generated field option' => [
        fn () => FieldLayout::make()->name('fieldLayout')->withGeneratedFields(fn (): string => 'yes'),
        'withGeneratedFields',
    ],
    'host-owned attribute' => [
        fn () => FieldLayout::make()->name('fieldLayout')->attributes(['id' => 'field-layout']),
        'attributes.id',
    ],
]);

it('fails HTML rendering for invalid host-owned field layout state', function (FieldLayout $component, string $option) {
    expect(fn () => $component->toHtml())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "%s" is not supported for HTML output.', FieldLayout::class, $option),
    );
})->with([
    'list value' => [fn () => FieldLayout::make()->value([['uid' => 'layout']]), 'value'],
    'non-serializable value' => [
        fn () => FieldLayout::make()->value(['tabs' => new stdClass]),
        'value[tabs]',
    ],
    'read-only state' => [fn () => FieldLayout::make()->readOnly(fn (): string => 'yes'), 'readOnly'],
]);
