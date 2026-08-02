<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\Button;
use CraftCms\Cms\Cp\Components\ComponentRegistry;
use CraftCms\Cms\Cp\Components\Field;
use CraftCms\Cms\Cp\Components\FormContainer;
use CraftCms\Cms\Cp\Components\Group;
use CraftCms\Cms\Cp\Components\Tab;
use CraftCms\Cms\Cp\Components\Tabs;
use CraftCms\Cms\Cp\Components\TextInput;
use CraftCms\Cms\Cp\FormDefinitions\Condition;
use CraftCms\Cms\Cp\FormDefinitions\Contracts\ProjectableFormElement;
use CraftCms\Cms\Cp\FormDefinitions\FormDefinition;
use CraftCms\Cms\Cp\FormDefinitions\FormElementTypes;

it('projects ordered nested containers with their portable presentation state', function () {
    $definition = FormDefinition::make([
        Group::make(fn (): array => [
            Field::make()->input(TextInput::make()->name('enabled')),
            Tabs::make([
                Tab::make('content', fn (): string => 'Content', [
                    Field::make()->input(TextInput::make()->name('title')),
                ])
                    ->columnWidth(60)
                    ->attributes(['data' => ['container' => 'content']])
                    ->visibleWhen(Condition::equals('enabled', true)),
                Tab::make('metadata', 'Metadata', [
                    Field::make()->input(TextInput::make()->name('slug')),
                ])->hasErrors(fn (): bool => true),
            ])
                ->key('settings-tabs')
                ->columnWidth(90)
                ->attributes(['data' => ['container' => 'tabs']])
                ->visibleWhen(Condition::equals('enabled', true)),
        ])
            ->key('settings')
            ->columnWidth(75)
            ->attributes(['data' => ['container' => 'settings']])
            ->visibleWhen(Condition::equals('enabled', true)),
    ]);

    expect($definition->toArray())->toBe([
        'elements' => [[
            'type' => 'craft:group',
            'key' => 'settings',
            'width' => 75,
            'attributes' => ['data' => ['container' => 'settings']],
            'children' => [[
                'type' => 'craft:field',
                'children' => [[
                    'type' => 'craft:text-input',
                    'name' => 'enabled',
                ]],
            ], [
                'type' => 'craft:tabs',
                'key' => 'settings-tabs',
                'width' => 90,
                'attributes' => ['data' => ['container' => 'tabs']],
                'children' => [[
                    'type' => 'craft:tab',
                    'key' => 'content',
                    'width' => 60,
                    'props' => ['label' => 'Content'],
                    'attributes' => ['data' => ['container' => 'content']],
                    'children' => [[
                        'type' => 'craft:field',
                        'children' => [[
                            'type' => 'craft:text-input',
                            'name' => 'title',
                        ]],
                    ]],
                    'visibleWhen' => [
                        'name' => 'enabled',
                        'operator' => 'equals',
                        'value' => true,
                    ],
                ], [
                    'type' => 'craft:tab',
                    'key' => 'metadata',
                    'props' => [
                        'label' => 'Metadata',
                        'hasErrors' => true,
                    ],
                    'children' => [[
                        'type' => 'craft:field',
                        'children' => [[
                            'type' => 'craft:text-input',
                            'name' => 'slug',
                        ]],
                    ]],
                ]],
                'visibleWhen' => [
                    'name' => 'enabled',
                    'operator' => 'equals',
                    'value' => true,
                ],
            ]],
            'visibleWhen' => [
                'name' => 'enabled',
                'operator' => 'equals',
                'value' => true,
            ],
        ]],
    ]);
});

it('renders nested containers through the shared browser primitives', function () {
    $html = Group::make([
        Tabs::make([
            Tab::make('content', 'Content', [
                Field::make()
                    ->label('Title')
                    ->input(TextInput::make()->name('title')),
            ])->hasErrors(),
        ])->key('settings-tabs'),
    ])
        ->key('settings')
        ->columnWidth(75)
        ->attributes(['data' => ['container' => 'settings']])
        ->toHtml();

    expect($html)
        ->toContainTag('craft-field-group', [
            'data-container' => 'settings',
            'data-form-element-key' => 'settings',
            'style' => 'width: 75%;',
        ])
        ->toContainTag('craft-tabs', [
            'data-form-element-key' => 'settings-tabs',
        ])
        ->toContainTag('craft-tab', [
            'slot' => 'tab',
            'style' => 'display: none;',
        ])
        ->toContainTag('craft-indicator', [
            'fill' => 'danger',
            'label' => 'Contains errors',
        ])
        ->toContainTag('craft-field-group', [
            'slot' => 'panel',
            'data-form-tab-panel' => 'content',
        ])
        ->toContainTag('craft-field', ['label' => 'Title'])
        ->toContainTag('craft-input', ['name' => 'title']);
});

it('identifies containers and invalid descendants during projection', function (
    FormContainer $container,
    string $message,
) {
    expect(fn () => FormDefinition::make([$container])->toArray())
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'group non-projectable child' => [
        fn () => Group::make([Button::make()]),
        sprintf(
            '%s child at index 0 (%s) must be %s for Form Definition output.',
            Group::class,
            Button::class,
            ProjectableFormElement::class,
        ),
    ],
    'tab non-projectable child' => [
        fn () => Tabs::make([
            Tab::make('content', 'Content', [Button::make()]),
        ]),
        sprintf(
            '%s child at index 0 (%s) must be %s for Form Definition output.',
            Tab::class,
            Button::class,
            ProjectableFormElement::class,
        ),
    ],
    'tabs non-tab child' => [
        fn () => Tabs::make([Group::make()]),
        sprintf(
            '%s child at index 0 (%s) must be %s for Form Definition output.',
            Tabs::class,
            Group::class,
            Tab::class,
        ),
    ],
]);

it('composes nested definitions with local Input Name prefixes and no Binding Scope', function () {
    $nested = FormDefinition::make([
        Field::make()->input(TextInput::make()->name('enabled')),
        Field::make()
            ->visibleWhen(Condition::equals('enabled', true))
            ->input(TextInput::make()->name('title')),
    ]);
    $definition = FormDefinition::make([
        Field::make()->input(TextInput::make()->name('mode')),
        Group::fromDefinition($nested, 'nested')
            ->key('nested-settings')
            ->visibleWhen(Condition::equals('mode', 'advanced')),
    ]);

    expect($definition->toArray())->toBe([
        'elements' => [[
            'type' => 'craft:field',
            'children' => [[
                'type' => 'craft:text-input',
                'name' => 'mode',
            ]],
        ], [
            'type' => 'craft:group',
            'key' => 'nested-settings',
            'children' => [[
                'type' => 'craft:field',
                'children' => [[
                    'type' => 'craft:text-input',
                    'name' => 'nested.enabled',
                ]],
            ], [
                'type' => 'craft:field',
                'children' => [[
                    'type' => 'craft:text-input',
                    'name' => 'nested.title',
                ]],
                'visibleWhen' => [
                    'name' => 'nested.enabled',
                    'operator' => 'equals',
                    'value' => true,
                ],
            ]],
            'visibleWhen' => [
                'name' => 'mode',
                'operator' => 'equals',
                'value' => 'advanced',
            ],
        ]],
    ])->and(json_encode($definition, JSON_THROW_ON_ERROR))
        ->not->toContain('bindingScope');
});

it('projects repeatedly without mutating containers or descendants', function () {
    $input = TextInput::make()->name('title');
    $field = Field::make()->label('Title')->input($input);
    $tab = Tab::make('content', 'Content', [$field]);
    $tabs = Tabs::make([$tab]);
    $group = Group::make([$tabs])->key('settings');
    $html = $group->toHtml();
    $inputHtml = $input->toHtml();
    $definition = FormDefinition::make([$group]);
    $first = $definition->toArray();

    expect($definition->toArray())->toBe($first)
        ->and($group->toHtml())->toBe($html)
        ->and($input->toHtml())->toBe($inputHtml);
});

it('materializes one-shot child iterables without consuming the component configuration', function () {
    $children = (function (): Generator {
        yield Field::make()->input(TextInput::make()->name('title'));
    })();
    $definition = FormDefinition::make([
        Group::make($children),
    ]);
    $first = $definition->toArray();

    expect($definition->toArray())->toBe($first);
});

it('registers every projectable container with matching type metadata', function () {
    $components = app(ComponentRegistry::class);
    $types = app(FormElementTypes::class);

    expect($components->make('group'))->toBeInstanceOf(Group::class)
        ->and($components->make('tabs'))->toBeInstanceOf(Tabs::class)
        ->and($components->make('tab'))->toBeInstanceOf(Tab::class)
        ->and($types->isRegistered(Group::formElementType()))->toBeTrue()
        ->and($types->isRegistered(Tabs::formElementType()))->toBeTrue()
        ->and($types->isRegistered(Tab::formElementType()))->toBeTrue()
        ->and($types->isContainer(Group::formElementType()))->toBeTrue()
        ->and($types->isContainer(Tabs::formElementType()))->toBeTrue()
        ->and($types->isContainer(Tab::formElementType()))->toBeTrue();
});

it('keeps tab selection and structural slots host-owned during projection', function (
    FormContainer $container,
    string $component,
    string $option,
) {
    expect(fn () => FormDefinition::make([$container])->toArray())
        ->toThrow(
            InvalidArgumentException::class,
            sprintf('%s option "%s" is not supported for Form Definition output.', $component, $option),
        );
})->with([
    'selected tab' => [
        fn () => Tabs::make([Tab::make('content', 'Content')])->attributes(['selected-index' => 0]),
        Tabs::class,
        'attributes.selected-index',
    ],
    'tab structural slot' => [
        fn () => Tabs::make([
            Tab::make('content', 'Content')->attributes(['slot' => 'other']),
        ]),
        Tab::class,
        'attributes.slot',
    ],
    'group structural slot setter' => [
        fn () => Group::make()->slot('other'),
        Group::class,
        'slot',
    ],
]);

it('rejects unsupported container options during HTML rendering', function (
    FormContainer $container,
    string $option,
) {
    expect(fn () => $container->toHtml())
        ->toThrow(
            InvalidArgumentException::class,
            sprintf('%s option "%s" is not supported for HTML output.', $container::class, $option),
        );
})->with([
    'group visibility' => [
        fn () => Group::make()->visibleWhen(Condition::equals('enabled', true)),
        'visibleWhen',
    ],
    'nested definition' => [
        fn () => Group::fromDefinition(FormDefinition::make([]), 'nested'),
        'definition',
    ],
    'invalid lazy key' => [
        fn () => Group::make()->key(fn (): stdClass => new stdClass),
        'key',
    ],
    'invalid lazy width' => [
        fn () => Group::make()->columnWidth(fn (): string => 'wide'),
        'columnWidth',
    ],
]);

it('rejects rendering a Tab outside Tabs', function () {
    expect(fn () => Tab::make('content', 'Content')->toHtml())
        ->toThrow(
            InvalidArgumentException::class,
            sprintf('%s must be rendered within %s.', Tab::class, Tabs::class),
        );
});

it('rejects rendering Tabs without a Tab', function () {
    expect(fn () => Tabs::make()->toHtml())
        ->toThrow(
            InvalidArgumentException::class,
            sprintf('%s option "children" is not supported for HTML output.', Tabs::class),
        );
});
