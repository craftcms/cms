<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\ComponentRegistry;
use CraftCms\Cms\Cp\Components\Field;
use CraftCms\Cms\Cp\Components\ObjectSelect;
use CraftCms\Cms\Cp\FormDefinitions\FormDefinition;
use CraftCms\Cms\Cp\FormDefinitions\FormElementTypes;

it('renders object selection through the shared primitive', function () {
    $options = [
        ['key' => 'article', 'label' => 'Article', 'value' => ['uid' => 'article']],
        ['key' => 'page', 'label' => 'Page', 'value' => ['uid' => 'page']],
    ];
    $value = [['uid' => 'article', 'name' => 'Article']];

    $html = ObjectSelect::make()
        ->name('entryTypes')
        ->value($value)
        ->options($options)
        ->identityKey('uid')
        ->readOnly()
        ->toHtml();

    expect($html)->toContainTag('craft-object-select', [
        'name' => 'entryTypes',
        'value' => json_encode($value, JSON_THROW_ON_ERROR),
        'options' => json_encode($options, JSON_THROW_ON_ERROR),
        'identity-key' => 'uid',
        'readonly' => true,
    ]);
});

it('registers and deterministically projects object selection without host-owned values', function () {
    $component = ObjectSelect::make()
        ->name('entryTypes')
        ->options([
            ['key' => 'article', 'label' => 'Article', 'value' => ['uid' => 'article']],
            ['key' => 'page', 'label' => 'Page', 'value' => ['uid' => 'page']],
        ])
        ->identityKey('uid')
        ->attributes(['data' => ['setting' => 'entryTypes']]);
    $definition = FormDefinition::make([
        Field::make()->input($component),
    ]);
    $expected = [
        'elements' => [[
            'type' => 'craft:field',
            'children' => [[
                'type' => 'craft:object-select-input',
                'name' => 'entryTypes',
                'props' => [
                    'options' => [
                        ['key' => 'article', 'label' => 'Article', 'value' => ['uid' => 'article']],
                        ['key' => 'page', 'label' => 'Page', 'value' => ['uid' => 'page']],
                    ],
                    'identityKey' => 'uid',
                ],
                'attributes' => ['data' => ['setting' => 'entryTypes']],
            ]],
        ]],
    ];
    $firstProjection = $definition->toArray();

    expect(app(ComponentRegistry::class)->make('object-select'))->toBeInstanceOf(ObjectSelect::class)
        ->and(app(FormElementTypes::class)->isRegistered(ObjectSelect::formElementType()))->toBeTrue()
        ->and($firstProjection)->toBe($expected)
        ->and($definition->toArray())->toBe($firstProjection);
});

it('rejects host-owned object selection state during projection', function (ObjectSelect $component, string $option) {
    expect(fn () => FormDefinition::make([
        Field::make()->input($component),
    ])->toArray())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "%s" is not supported for Form Definition output.', ObjectSelect::class, $option),
    );
})->with([
    'selected objects' => [fn () => ObjectSelect::make()->name('entryTypes')->value([]), 'value'],
    'read-only state' => [fn () => ObjectSelect::make()->name('entryTypes')->readOnly(false), 'readOnly'],
]);

it('rejects invalid portable object selection configuration', function (ObjectSelect $component, string $option) {
    expect(fn () => FormDefinition::make([
        Field::make()->input($component),
    ])->toArray())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "%s" is not supported for Form Definition output.', ObjectSelect::class, $option),
    );
})->with([
    'name' => [fn () => ObjectSelect::make()->identityKey('uid'), 'name'],
    'identity key' => [fn () => ObjectSelect::make()->name('entryTypes'), 'identityKey'],
    'option shape' => [
        fn () => ObjectSelect::make()->name('entryTypes')->identityKey('uid')->options([
            ['key' => 'article', 'label' => 'Article'],
        ]),
        'options[0]',
    ],
    'non-serializable option value' => [
        fn () => ObjectSelect::make()->name('entryTypes')->identityKey('uid')->options([
            ['key' => 'article', 'label' => 'Article', 'value' => new stdClass],
        ]),
        'options[0].value',
    ],
    'host-owned attribute' => [
        fn () => ObjectSelect::make()
            ->name('entryTypes')
            ->identityKey('uid')
            ->attributes(['id' => 'entry-types']),
        'attributes.id',
    ],
    'nested host-owned accessibility attribute' => [
        fn () => ObjectSelect::make()
            ->name('entryTypes')
            ->identityKey('uid')
            ->attributes(['aria' => ['labelledby' => 'entry-types-label']]),
        'attributes.aria-labelledby',
    ],
]);

it('fails HTML rendering for non-serializable object selection state', function (ObjectSelect $component, string $option) {
    expect(fn () => $component->toHtml())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "%s" is not supported for HTML output.', ObjectSelect::class, $option),
    );
})->with([
    'selected object' => [
        fn () => ObjectSelect::make()->identityKey('uid')->value([new stdClass]),
        'value[0]',
    ],
    'option object' => [
        fn () => ObjectSelect::make()->identityKey('uid')->options([
            ['key' => 'article', 'label' => 'Article', 'value' => new stdClass],
        ]),
        'options[0].value',
    ],
    'identity key' => [fn () => ObjectSelect::make()->identityKey(fn (): int => 1), 'identityKey'],
    'read-only state' => [fn () => ObjectSelect::make()->identityKey('uid')->readOnly(fn (): string => 'yes'), 'readOnly'],
]);
