<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\Field;
use CraftCms\Cms\Cp\Components\Group;
use CraftCms\Cms\Cp\Components\Tab;
use CraftCms\Cms\Cp\Components\Tabs;
use CraftCms\Cms\Cp\Components\TextInput;
use CraftCms\Cms\Cp\Components\ViewComponent;
use CraftCms\Cms\Cp\FormDefinitions\Condition;
use CraftCms\Cms\Cp\FormDefinitions\Contracts\ProjectableFormElement;
use CraftCms\Cms\Cp\FormDefinitions\Data\FormElementData;
use CraftCms\Cms\Cp\FormDefinitions\Data\VisibilityConditionData;
use CraftCms\Cms\Cp\FormDefinitions\FormDefinition;
use CraftCms\Cms\Cp\FormDefinitions\FormElementTypes;

beforeEach(function () {
    app()->forgetInstance(FormElementTypes::class);
    app(FormElementTypes::class)->register(TestProjectableContainer::class);
});

it('projects a native text setting through an explicit field container', function () {
    $definition = FormDefinition::make([
        Field::make()
            ->label(fn (): string => 'Handle')
            ->instructions('How templates refer to this component.')
            ->columnWidth(50)
            ->readOnly()
            ->input(
                TextInput::make()
                    ->name('handle')
                    ->placeholder('myComponent')
                    ->attributes([
                        'autocomplete' => 'off',
                        'data-setting' => 'handle',
                    ]),
            ),
    ]);

    expect($definition->toArray())->toBe([
        'elements' => [[
            'type' => 'craft:field',
            'width' => 50,
            'props' => [
                'label' => 'Handle',
                'instructions' => 'How templates refer to this component.',
                'readOnly' => true,
            ],
            'children' => [[
                'type' => 'craft:text-input',
                'name' => 'handle',
                'props' => [
                    'placeholder' => 'myComponent',
                ],
                'attributes' => [
                    'autocomplete' => 'off',
                    'data-setting' => 'handle',
                ],
            ]],
        ]],
    ]);
});

it('projects visual groups and tabs with resolved labels and stable keys', function () {
    $definition = FormDefinition::make([
        Group::make([
            Tabs::make([
                Tab::make('content', fn (): string => 'Content', [
                    Field::make()->input(TextInput::make()->name('title')),
                ]),
                Tab::make('metadata', 'Metadata', [
                    Field::make()->input(TextInput::make()->name('slug')),
                ])->hasErrors(),
            ])->key('settings-tabs'),
        ])->key('settings'),
    ]);

    expect($definition->toArray())->toBe([
        'elements' => [[
            'type' => 'craft:group',
            'key' => 'settings',
            'children' => [[
                'type' => 'craft:tabs',
                'key' => 'settings-tabs',
                'children' => [[
                    'type' => 'craft:tab',
                    'key' => 'content',
                    'props' => ['label' => 'Content'],
                    'children' => [[
                        'type' => 'craft:field',
                        'children' => [[
                            'type' => 'craft:text-input',
                            'name' => 'title',
                        ]],
                    ]],
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
            ]],
        ]],
    ]);
});

it('rejects duplicate sibling keys', function () {
    $definition = FormDefinition::make([
        Group::make([Field::make()->input(TextInput::make()->name('title'))])->key('settings'),
        Group::make([Field::make()->input(TextInput::make()->name('slug'))])->key('settings'),
    ]);

    expect(fn () => $definition->toArray())
        ->toThrow(
            InvalidArgumentException::class,
            'Form Element Type "craft:group" at elements[1]: duplicate sibling key "settings".',
        );
});

it('rejects malformed container structures', function (ProjectableFormElement $element, string $message) {
    expect(fn () => FormDefinition::make([$element])->toArray())
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'empty tabs' => [
        fn () => Tabs::make([]),
        'Form Element Type "craft:tabs" at elements[0]: Tabs must contain at least one Tab.',
    ],
    'tab outside tabs' => [
        fn () => Group::make([Tab::make('content', 'Content', [])]),
        sprintf(
            '%s child at index 0 (%s) must be a non-Tab form element for Form Definition output.',
            Group::class,
            Tab::class,
        ),
    ],
    'empty tab key' => [
        fn () => Tabs::make([Tab::make('', 'Content', [])]),
        'Form Element Type "craft:tab" at elements[0].children[0]: key cannot be empty.',
    ],
    'missing tab key' => [
        fn () => Tabs::make([Tab::make(null, 'Content')]),
        'Form Element Type "craft:tab" at elements[0].children[0]: a Tab must define a stable key.',
    ],
    'empty tab label' => [
        fn () => Tabs::make([Tab::make('content', '', [])]),
        'Form Element Type "craft:tab" at elements[0].children[0]: a Tab must define a resolved label.',
    ],
]);

it('keeps host workflow data outside the serialized definition', function () {
    $json = json_encode(
        FormDefinition::make([
            Field::make()
                ->label('Handle')
                ->input(TextInput::make()->name('handle')),
        ]),
        JSON_THROW_ON_ERROR,
    );

    expect($json)
        ->not->toContain('value')
        ->not->toContain('error')
        ->not->toContain('bindingScope')
        ->not->toContain('route')
        ->not->toContain('persist');
});

it('projects nested visibility comparisons and groups', function () {
    $definition = FormDefinition::make([
        Field::make()->input(TextInput::make()->name('enabled')),
        Field::make()->input(TextInput::make()->name('mode')),
        Field::make()
            ->visibleWhen(Condition::all(
                Condition::equals('enabled', true),
                Condition::any(
                    Condition::equals('mode', 'manual'),
                    Condition::notEquals('mode', 'automatic'),
                ),
            ))
            ->input(TextInput::make()->name('label')),
    ]);

    expect($definition->toArray()['elements'][2]['visibleWhen'])->toBe([
        'all' => [
            ['name' => 'enabled', 'operator' => 'equals', 'value' => true],
            ['any' => [
                ['name' => 'mode', 'operator' => 'equals', 'value' => 'manual'],
                ['name' => 'mode', 'operator' => 'notEquals', 'value' => 'automatic'],
            ]],
        ],
    ]);
});

it('keeps callable-looking strings as visibility data', function () {
    $definition = FormDefinition::make([
        Field::make()->input(TextInput::make()->name('source')),
        Field::make()
            ->visibleWhen(Condition::equals('source', 'trim'))
            ->input(TextInput::make()->name('target')),
    ]);

    expect($definition->toArray()['elements'][1]['visibleWhen'])->toBe([
        'name' => 'source',
        'operator' => 'equals',
        'value' => 'trim',
    ]);
});

it('projects every visibility comparison operator', function (Condition $condition, array $expected) {
    $definition = FormDefinition::make([
        Field::make()->input(TextInput::make()->name('source')),
        Field::make()
            ->visibleWhen($condition)
            ->input(TextInput::make()->name('target')),
    ]);

    expect($definition->toArray()['elements'][1]['visibleWhen'])->toBe($expected);
})->with([
    'equals' => [Condition::equals('source', 1), ['name' => 'source', 'operator' => 'equals', 'value' => 1]],
    'not equals' => [Condition::notEquals('source', 1), ['name' => 'source', 'operator' => 'notEquals', 'value' => 1]],
    'less than' => [Condition::lessThan('source', 1), ['name' => 'source', 'operator' => 'lessThan', 'value' => 1]],
    'less than or equal' => [Condition::lessThanOrEqual('source', 1), ['name' => 'source', 'operator' => 'lessThanOrEqual', 'value' => 1]],
    'greater than' => [Condition::greaterThan('source', 1), ['name' => 'source', 'operator' => 'greaterThan', 'value' => 1]],
    'greater than or equal' => [Condition::greaterThanOrEqual('source', 1), ['name' => 'source', 'operator' => 'greaterThanOrEqual', 'value' => 1]],
    'begins with' => [Condition::beginsWith('source', 'a'), ['name' => 'source', 'operator' => 'beginsWith', 'value' => 'a']],
    'ends with' => [Condition::endsWith('source', 'a'), ['name' => 'source', 'operator' => 'endsWith', 'value' => 'a']],
    'contains' => [Condition::contains('source', 'a'), ['name' => 'source', 'operator' => 'contains', 'value' => 'a']],
    'in' => [Condition::in('source', ['a']), ['name' => 'source', 'operator' => 'in', 'value' => ['a']]],
    'not in' => [Condition::notIn('source', ['a']), ['name' => 'source', 'operator' => 'notIn', 'value' => ['a']]],
    'empty' => [Condition::empty('source'), ['name' => 'source', 'operator' => 'empty']],
    'not empty' => [Condition::notEmpty('source'), ['name' => 'source', 'operator' => 'notEmpty']],
]);

it('rejects malformed visibility conditions with type and tree location context', function (
    array $condition,
    string $message,
) {
    $definition = FormDefinition::make([
        TestProjectableContainer::make()
            ->children([
                Field::make()->input(TextInput::make()->name('target'))->toFormElementData(),
            ])
            ->visibility(new VisibilityConditionData($condition)),
    ]);

    expect(fn () => $definition->toArray())
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'empty all group' => [
        ['all' => []],
        'Form Element Type "application:test-container" at elements[0].visibleWhen: all groups cannot be empty.',
    ],
    'unsupported operator' => [
        ['name' => 'target', 'operator' => 'matches', 'value' => 'x'],
        'Form Element Type "application:test-container" at elements[0].visibleWhen: unsupported operator "matches".',
    ],
    'executable value' => [
        ['name' => 'target', 'operator' => 'equals', 'value' => fn () => true],
        'Form Element Type "application:test-container" at elements[0].visibleWhen: value cannot be executable.',
    ],
    'numeric operator with string value' => [
        ['name' => 'target', 'operator' => 'lessThan', 'value' => '1'],
        'Form Element Type "application:test-container" at elements[0].visibleWhen: lessThan requires a numeric value.',
    ],
    'text operator with array value' => [
        ['name' => 'target', 'operator' => 'beginsWith', 'value' => ['a']],
        'Form Element Type "application:test-container" at elements[0].visibleWhen: beginsWith requires a string value.',
    ],
    'membership operator with scalar value' => [
        ['name' => 'target', 'operator' => 'in', 'value' => 'a'],
        'Form Element Type "application:test-container" at elements[0].visibleWhen: in requires a list of scalar values.',
    ],
    'emptiness operator with value' => [
        ['name' => 'target', 'operator' => 'empty', 'value' => null],
        'Form Element Type "application:test-container" at elements[0].visibleWhen: empty does not accept a value.',
    ],
]);

it('rejects unresolved visibility input names with type and tree location context', function () {
    $definition = FormDefinition::make([
        Field::make()
            ->visibleWhen(Condition::equals('missing', true))
            ->input(TextInput::make()->name('target')),
    ]);

    expect(fn () => $definition->toArray())
        ->toThrow(
            InvalidArgumentException::class,
            'Form Element Type "craft:field" at elements[0].visibleWhen: unresolved Input Name "missing".',
        );
});

it('rejects duplicate input names with type and tree location context', function () {
    $definition = FormDefinition::make([
        Field::make()->input(TextInput::make()->name('handle')),
        Field::make()->input(TextInput::make()->name('handle')),
    ]);

    expect(fn () => $definition->toArray())
        ->toThrow(
            InvalidArgumentException::class,
            'Form Element Type "craft:text-input" at elements[1].children[0]: duplicate Input Name "handle".',
        );
});

it('rejects invalid widths and non-serializable portable data', function (
    ProjectableFormElement $element,
    string $message,
) {
    expect(fn () => FormDefinition::make([$element])->toArray())
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'invalid width' => [
        fn () => Field::make()
            ->columnWidth(0)
            ->input(TextInput::make()->name('title')),
        'Form Element Type "craft:field" at elements[0]: width must be between 1 and 100.',
    ],
    'non-serializable props' => [
        fn () => TestProjectableContainer::make()->props(['bad' => new stdClass]),
        'Form Element Type "application:test-container" at elements[0]: props.bad is not serializable.',
    ],
    'non-serializable attributes' => [
        fn () => Field::make()->input(
            TextInput::make()
                ->name('title')
                ->attributes(['bad' => fn () => null]),
        ),
        'Form Element Type "craft:text-input" at elements[0].children[0]: attributes.bad is not serializable.',
    ],
]);

class TestProjectableContainer extends ViewComponent implements ProjectableFormElement
{
    private ?array $elementProps = null;

    private ?array $elementChildren = null;

    private ?VisibilityConditionData $elementVisibility = null;

    public static function formElementType(): string
    {
        return 'application:test-container';
    }

    public static function isFormElementContainer(): bool
    {
        return true;
    }

    public function props(array $props): static
    {
        $this->elementProps = $props;

        return $this;
    }

    public function children(array $children): static
    {
        $this->elementChildren = $children;

        return $this;
    }

    public function visibility(VisibilityConditionData $visibility): static
    {
        $this->elementVisibility = $visibility;

        return $this;
    }

    public function toFormElementData(): FormElementData
    {
        return new FormElementData(
            type: self::formElementType(),
            props: $this->elementProps,
            children: $this->elementChildren,
            visibleWhen: $this->elementVisibility,
        );
    }

    #[Override]
    protected function tagName(): string
    {
        return 'test-projectable-container';
    }
}
