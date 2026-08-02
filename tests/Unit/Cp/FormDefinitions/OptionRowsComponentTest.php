<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\ComponentRegistry;
use CraftCms\Cms\Cp\Components\Field;
use CraftCms\Cms\Cp\Components\OptionRows;
use CraftCms\Cms\Cp\FormDefinitions\FormDefinition;
use CraftCms\Cms\Cp\FormDefinitions\FormElementTypes;

it('renders option rows through the shared primitive', function () {
    $rows = [
        ['optgroup' => 'Published'],
        [
            'label' => 'Breaking News',
            'value' => 'breakingNews',
            'icon' => 'newspaper',
            'color' => 'ff0000',
            'default' => true,
        ],
    ];

    $html = OptionRows::make()
        ->name('options')
        ->value($rows)
        ->multipleDefaults()
        ->optgroups()
        ->icons()
        ->colors()
        ->readOnly()
        ->toHtml();

    expect($html)->toContainTag('craft-option-rows', [
        'name' => 'options',
        'value' => json_encode($rows, JSON_THROW_ON_ERROR),
        'multiple-defaults' => true,
        'optgroups' => true,
        'icons' => true,
        'colors' => true,
        'readonly' => true,
    ]);
});

it('registers and deterministically projects option rows without host-owned values', function () {
    $component = OptionRows::make()
        ->name('options')
        ->multipleDefaults()
        ->optgroups()
        ->icons()
        ->colors()
        ->attributes(['data' => ['setting' => 'options']]);
    $definition = FormDefinition::make([
        Field::make()->input($component),
    ]);
    $expected = [
        'elements' => [[
            'type' => 'craft:field',
            'children' => [[
                'type' => 'craft:option-rows',
                'name' => 'options',
                'props' => [
                    'multipleDefaults' => true,
                    'optgroups' => true,
                    'icons' => true,
                    'colors' => true,
                ],
                'attributes' => ['data' => ['setting' => 'options']],
            ]],
        ]],
    ];
    $firstProjection = $definition->toArray();
    $secondProjection = $definition->toArray();

    expect(app(ComponentRegistry::class)->make('option-rows'))->toBeInstanceOf(OptionRows::class)
        ->and(OptionRows::formElementType())->toBe('craft:option-rows')
        ->and(app(FormElementTypes::class)->isRegistered(OptionRows::formElementType()))->toBeTrue()
        ->and($firstProjection)->toBe($expected)
        ->and($secondProjection)->toBe($firstProjection);
});

it('rejects host-owned option row state during projection', function (OptionRows $component, string $option) {
    expect(fn () => FormDefinition::make([
        Field::make()->input($component),
    ])->toArray())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "%s" is not supported for Form Definition output.', OptionRows::class, $option),
    );
})->with([
    'rows' => [fn () => OptionRows::make()->name('options')->value([]), 'value'],
    'read-only state' => [fn () => OptionRows::make()->name('options')->readOnly(false), 'readOnly'],
]);

it('rejects invalid portable option row configuration', function (OptionRows $component, string $option) {
    expect(fn () => FormDefinition::make([
        Field::make()->input($component),
    ])->toArray())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "%s" is not supported for Form Definition output.', OptionRows::class, $option),
    );
})->with([
    'name' => [fn () => OptionRows::make(), 'name'],
    'multiple defaults' => [fn () => OptionRows::make()->name('options')->multipleDefaults(fn (): string => 'yes'), 'multipleDefaults'],
    'optgroups' => [fn () => OptionRows::make()->name('options')->optgroups(fn (): string => 'yes'), 'optgroups'],
    'icons' => [fn () => OptionRows::make()->name('options')->icons(fn (): string => 'yes'), 'icons'],
    'colors' => [fn () => OptionRows::make()->name('options')->colors(fn (): string => 'yes'), 'colors'],
]);

it('fails HTML rendering for invalid or unsupported row data', function (OptionRows $component, string $option) {
    expect(fn () => $component->toHtml())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "%s" is not supported for HTML output.', OptionRows::class, $option),
    );
})->with([
    'non-list rows' => [fn () => OptionRows::make()->value(['label' => 'News']), 'value'],
    'invalid row' => [fn () => OptionRows::make()->value([['label' => 'News']]), 'value[0]'],
    'optgroup row' => [fn () => OptionRows::make()->value([['optgroup' => 'Published']]), 'value[0].optgroup'],
    'icon row' => [fn () => OptionRows::make()->value([['label' => 'News', 'value' => 'news', 'icon' => 'newspaper']]), 'value[0].icon'],
    'color row' => [fn () => OptionRows::make()->value([['label' => 'News', 'value' => 'news', 'color' => 'ff0000']]), 'value[0].color'],
    'invalid default state' => [fn () => OptionRows::make()->value([['label' => 'News', 'value' => 'news', 'default' => 'yes']]), 'value[0].default'],
    'invalid disabled state' => [fn () => OptionRows::make()->value([['label' => 'News', 'value' => 'news', 'disabled' => 'yes']]), 'value[0].disabled'],
    'unknown row property' => [fn () => OptionRows::make()->value([['label' => 'News', 'value' => 'news', 'mystery' => true]]), 'value[0].mystery'],
]);
