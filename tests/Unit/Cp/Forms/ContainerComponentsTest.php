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
use CraftCms\Cms\Cp\Forms\Condition;
use CraftCms\Cms\Cp\Forms\Contracts\FormElement;
use CraftCms\Cms\Cp\Forms\Form;
use CraftCms\Cms\Cp\Forms\FormElementTypes;

it('projects ordered nested containers with their portable presentation state', function () {
    $form = Form::make([
        Group::make(fn (): array => [
            Field::make(TextInput::make()->name('enabled')),
            Tabs::make([
                Tab::make('content', fn (): string => 'Content', [
                    Field::make(TextInput::make()->name('title')),
                ])
                    ->columnWidth(60)
                    ->attributes(['data' => ['container' => 'content']])
                    ->visibleWhen(Condition::equals('enabled', true)),
                Tab::make('metadata', 'Metadata', [
                    Field::make(TextInput::make()->name('slug')),
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

    expect($form->toArray())->toBe([
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
                Field::make(TextInput::make()->name('title'))
                    ->label('Title'),
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
    expect(fn () => Form::make([$container])->toArray())
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'group non-Form-Element child' => [
        fn () => Group::make([Button::make()]),
        sprintf(
            '%s child at index 0 (%s) must be %s for Form output.',
            Group::class,
            Button::class,
            FormElement::class,
        ),
    ],
    'tab non-Form-Element child' => [
        fn () => Tabs::make([
            Tab::make('content', 'Content', [Button::make()]),
        ]),
        sprintf(
            '%s child at index 0 (%s) must be %s for Form output.',
            Tab::class,
            Button::class,
            FormElement::class,
        ),
    ],
    'tabs non-tab child' => [
        fn () => Tabs::make([Group::make()]),
        sprintf(
            '%s child at index 0 (%s) must be %s for Form output.',
            Tabs::class,
            Group::class,
            Tab::class,
        ),
    ],
]);

it('composes nested forms with local Input Name prefixes and no Binding Scope', function () {
    $nested = Form::make([
        Field::make(TextInput::make()->name('enabled')),
        Field::make(TextInput::make()->name('title'))
            ->visibleWhen(Condition::equals('enabled', true)),
    ]);
    $form = Form::make([
        Field::make(TextInput::make()->name('mode')),
        Group::fromForm($nested, 'nested')
            ->key('nested-settings')
            ->visibleWhen(Condition::equals('mode', 'advanced')),
    ]);

    expect($form->toArray())->toBe([
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
    ])->and(json_encode($form, JSON_THROW_ON_ERROR))
        ->not->toContain('bindingScope');
});

it('projects repeatedly without mutating containers or descendants', function () {
    $input = TextInput::make()->name('title');
    $field = Field::make($input)->label('Title');
    $tab = Tab::make('content', 'Content', [$field]);
    $tabs = Tabs::make([$tab]);
    $group = Group::make([$tabs])->key('settings');
    $html = $group->toHtml();
    $inputHtml = $input->toHtml();
    $form = Form::make([$group]);
    $first = $form->toArray();

    expect($form->toArray())->toBe($first)
        ->and($group->toHtml())->toBe($html)
        ->and($input->toHtml())->toBe($inputHtml);
});

it('materializes one-shot child iterables without consuming the component configuration', function () {
    $children = (function (): Generator {
        yield Field::make(TextInput::make()->name('title'));
    })();
    $form = Form::make([
        Group::make($children),
    ]);
    $first = $form->toArray();

    expect($form->toArray())->toBe($first);
});

it('registers every Form Element container with matching type metadata', function () {
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

it('ignores tab selection and structural slots during projection', function (FormContainer $container) {
    expect(Form::make([$container])->toArray())->toBeArray();
})->with([
    'selected tab' => [
        fn () => Tabs::make([Tab::make('content', 'Content')])->attributes(['selected-index' => 0]),
    ],
    'tab structural slot' => [
        fn () => Tabs::make([
            Tab::make('content', 'Content')->attributes(['slot' => 'other']),
        ]),
    ],
    'group structural slot setter' => [
        fn () => Group::make()->slot('other'),
    ],
]);

it('ignores Form-only container options during HTML rendering', function () {
    expect(Group::make()
        ->visibleWhen(Condition::equals('enabled', true))
        ->toHtml())->toBeString();
});

it('rejects a nested Form during HTML rendering', function () {
    expect(fn () => Group::fromForm(Form::make([]), 'nested')->toHtml())
        ->toThrow(
            InvalidArgumentException::class,
            sprintf('%s option "form" is invalid for HTML output.', Group::class),
        );
});

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
            sprintf('%s option "children" is invalid for HTML output.', Tabs::class),
        );
});
