<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\FormDefinitions\Condition;
use CraftCms\Cms\Cp\FormDefinitions\Data\FormElementData;
use CraftCms\Cms\Cp\FormDefinitions\Data\VisibilityConditionData;
use CraftCms\Cms\Cp\FormDefinitions\Elements\FormElement;
use CraftCms\Cms\Cp\FormDefinitions\Elements\Group;
use CraftCms\Cms\Cp\FormDefinitions\Elements\InputElement;
use CraftCms\Cms\Cp\FormDefinitions\Elements\Tab;
use CraftCms\Cms\Cp\FormDefinitions\Elements\Tabs;
use CraftCms\Cms\Cp\FormDefinitions\FormDefinition;

it('projects a native text setting through an explicit field container', function () {
    $definition = FormDefinition::make([
        TextInput::make('handle')
            ->label(fn (): string => 'Handle')
            ->instructions('How templates refer to this component.')
            ->placeholder('myComponent')
            ->width(50)
            ->readOnly()
            ->attributes([
                'autocomplete' => 'off',
                'data-setting' => 'handle',
            ]),
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
                    TextInput::make('title'),
                ]),
                Tab::make('metadata', 'Metadata', [
                    TextInput::make('slug'),
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
        Group::make([TextInput::make('title')])->key('settings'),
        Group::make([TextInput::make('slug')])->key('settings'),
    ]);

    expect(fn () => $definition->toArray())
        ->toThrow(
            InvalidArgumentException::class,
            'Form Element Type "craft:group" at elements[1]: duplicate sibling key "settings".',
        );
});

it('rejects malformed container structures', function (FormElement $element, string $message) {
    expect(fn () => FormDefinition::make([$element])->toArray())
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'tabs without tabs' => [
        fn () => Tabs::make([TextInput::make('title')]),
        'Form Element Type "craft:field" at elements[0].children[0]: only Tab Form Elements may be direct children of Tabs.',
    ],
    'empty tabs' => [
        fn () => Tabs::make([]),
        'Form Element Type "craft:tabs" at elements[0]: Tabs must contain at least one Tab.',
    ],
    'tab outside tabs' => [
        fn () => Group::make([Tab::make('content', 'Content', [])]),
        'Form Element Type "craft:tab" at elements[0].children[0]: a Tab must be a direct child of Tabs.',
    ],
    'empty tab key' => [
        fn () => Tabs::make([Tab::make('', 'Content', [])]),
        'Form Element Type "craft:tab" at elements[0].children[0]: key cannot be empty.',
    ],
    'missing tab key' => [
        fn () => Tabs::make([
            TestFormElement::make('craft:tab', props: ['label' => 'Content']),
        ]),
        'Form Element Type "craft:tab" at elements[0].children[0]: a Tab must define a stable key.',
    ],
    'empty tab label' => [
        fn () => Tabs::make([Tab::make('content', '', [])]),
        'Form Element Type "craft:tab" at elements[0].children[0]: a Tab must define a resolved label.',
    ],
    'container inside field' => [
        fn () => TestFormElement::make('craft:field', children: [
            Group::make([TextInput::make('title')]),
        ]),
        'Form Element Type "craft:field" at elements[0]: a Field Container must contain exactly one input.',
    ],
]);

it('keeps host workflow data outside the serialized definition', function () {
    $json = json_encode(
        FormDefinition::make([TextInput::make('handle')->label('Handle')]),
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
        TextInput::make('enabled'),
        TextInput::make('mode'),
        TextInput::make('label')->visibleWhen(Condition::all(
            Condition::equals('enabled', true),
            Condition::any(
                Condition::equals('mode', 'manual'),
                Condition::notEquals('mode', 'automatic'),
            ),
        )),
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
        TextInput::make('source'),
        TextInput::make('target')->visibleWhen(Condition::equals('source', 'trim')),
    ]);

    expect($definition->toArray()['elements'][1]['visibleWhen'])->toBe([
        'name' => 'source',
        'operator' => 'equals',
        'value' => 'trim',
    ]);
});

it('projects every visibility comparison operator', function (Condition $condition, array $expected) {
    $definition = FormDefinition::make([
        TextInput::make('source'),
        TextInput::make('target')->visibleWhen($condition),
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
        TestFormElement::make('craft:field', children: [
            TestFormElement::make('craft:text-input', name: 'target'),
        ], visibleWhen: new VisibilityConditionData($condition)),
    ]);

    expect(fn () => $definition->toArray())
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'empty all group' => [
        ['all' => []],
        'Form Element Type "craft:field" at elements[0].visibleWhen: all groups cannot be empty.',
    ],
    'unsupported operator' => [
        ['name' => 'target', 'operator' => 'matches', 'value' => 'x'],
        'Form Element Type "craft:field" at elements[0].visibleWhen: unsupported operator "matches".',
    ],
    'executable value' => [
        ['name' => 'target', 'operator' => 'equals', 'value' => fn () => true],
        'Form Element Type "craft:field" at elements[0].visibleWhen: value cannot be executable.',
    ],
    'numeric operator with string value' => [
        ['name' => 'target', 'operator' => 'lessThan', 'value' => '1'],
        'Form Element Type "craft:field" at elements[0].visibleWhen: lessThan requires a numeric value.',
    ],
    'text operator with array value' => [
        ['name' => 'target', 'operator' => 'beginsWith', 'value' => ['a']],
        'Form Element Type "craft:field" at elements[0].visibleWhen: beginsWith requires a string value.',
    ],
    'membership operator with scalar value' => [
        ['name' => 'target', 'operator' => 'in', 'value' => 'a'],
        'Form Element Type "craft:field" at elements[0].visibleWhen: in requires a list of scalar values.',
    ],
    'emptiness operator with value' => [
        ['name' => 'target', 'operator' => 'empty', 'value' => null],
        'Form Element Type "craft:field" at elements[0].visibleWhen: empty does not accept a value.',
    ],
]);

it('rejects unresolved visibility input names with type and tree location context', function () {
    $definition = FormDefinition::make([
        TextInput::make('target')->visibleWhen(Condition::equals('missing', true)),
    ]);

    expect(fn () => $definition->toArray())
        ->toThrow(
            InvalidArgumentException::class,
            'Form Element Type "craft:field" at elements[0].visibleWhen: unresolved Input Name "missing".',
        );
});

it('rejects duplicate input names with type and tree location context', function () {
    $definition = FormDefinition::make([
        TextInput::make('handle'),
        TextInput::make('handle'),
    ]);

    expect(fn () => $definition->toArray())
        ->toThrow(
            InvalidArgumentException::class,
            'Form Element Type "craft:text-input" at elements[1].children[0]: duplicate Input Name "handle".',
        );
});

it('rejects malformed elements with type and tree location context', function (
    FormElement $element,
    string $message,
) {
    expect(fn () => FormDefinition::make([$element])->toArray())
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'unknown type' => [
        fn () => TestFormElement::make('plugin:unknown'),
        'Form Element Type "plugin:unknown" at elements[0]: unknown or unregistered Form Element Type.',
    ],
    'nested unknown type' => [
        fn () => TestFormElement::make('craft:field', children: [
            TestFormElement::make('plugin:unknown', name: 'title'),
        ]),
        'Form Element Type "plugin:unknown" at elements[0].children[0]: unknown or unregistered Form Element Type.',
    ],
    'invalid width' => [
        fn () => TextInput::make('title')->width(0),
        'Form Element Type "craft:field" at elements[0]: width must be between 1 and 100.',
    ],
    'input with children' => [
        fn () => TestFormElement::make('craft:field', children: [
            TestFormElement::make(
                'craft:text-input',
                name: 'title',
                children: [TestFormElement::make('craft:text-input', name: 'nested')],
            ),
        ]),
        'Form Element Type "craft:text-input" at elements[0].children[0]: this type cannot contain children.',
    ],
    'nested field instead of input' => [
        fn () => TestFormElement::make('craft:field', children: [
            TestFormElement::make('craft:field', name: 'title', children: [
                TestFormElement::make('craft:text-input', name: 'nested'),
            ]),
        ]),
        'Form Element Type "craft:field" at elements[0].children[0]: a Field Container must contain exactly one input.',
    ],
    'unwrapped input' => [
        fn () => TestFormElement::make('craft:text-input', name: 'title'),
        'Form Element Type "craft:text-input" at elements[0]: an input must be wrapped in a Field Container.',
    ],
    'field without one input child' => [
        fn () => TestFormElement::make('craft:field'),
        'Form Element Type "craft:field" at elements[0]: a Field Container must contain exactly one input.',
    ],
    'non-serializable props' => [
        fn () => TestFormElement::make('craft:field', children: [
            TestFormElement::make('craft:text-input', name: 'title', props: ['bad' => new stdClass]),
        ]),
        'Form Element Type "craft:text-input" at elements[0].children[0]: props.bad is not serializable.',
    ],
    'non-serializable attributes' => [
        fn () => TextInput::make('title')->attributes(['bad' => fn () => null]),
        'Form Element Type "craft:text-input" at elements[0].children[0]: attributes.bad is not serializable.',
    ],
]);

class TestFormElement extends FormElement
{
    /**
     * @param  string  $elementType  Test Form Element Type.
     * @param  ?string  $name  Test Input Name.
     * @param  array<string, mixed>  $elementProps  Test renderer props.
     * @param  list<FormElement>  $elementChildren  Test child elements.
     */
    private function __construct(
        private readonly string $elementType,
        ?string $name,
        private readonly array $elementProps,
        private readonly array $elementChildren,
        private readonly ?VisibilityConditionData $elementVisibleWhen,
    ) {
        parent::__construct($name);
    }

    /**
     * @param  string  $type  Test Form Element Type.
     * @param  ?string  $name  Test Input Name.
     * @param  array<string, mixed>  $props  Test renderer props.
     * @param  list<FormElement>  $children  Test child elements.
     */
    public static function make(
        string $type,
        ?string $name = null,
        array $props = [],
        array $children = [],
        ?VisibilityConditionData $visibleWhen = null,
    ): self {
        return new self($type, $name, $props, $children, $visibleWhen);
    }

    public static function type(): string
    {
        return 'test:element';
    }

    #[Override]
    protected function props(): array
    {
        return $this->elementProps;
    }

    #[Override]
    protected function children(): array
    {
        return $this->elementChildren;
    }

    #[Override]
    public function toData(): FormElementData
    {
        return new FormElementData(
            type: $this->elementType,
            name: $this->name,
            width: $this->width,
            props: $this->elementProps === [] ? null : $this->elementProps,
            attributes: $this->elementAttributes === [] ? null : $this->elementAttributes,
            children: $this->elementChildren === []
                ? null
                : array_map(
                    fn (FormElement $element): FormElementData => $element->toData(),
                    $this->elementChildren,
                ),
            visibleWhen: $this->elementVisibleWhen,
        );
    }
}

class TextInput extends InputElement
{
    private ?string $placeholder = null;

    public static function type(): string
    {
        return 'craft:text-input';
    }

    public function placeholder(?string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    #[Override]
    protected function props(): array
    {
        return array_filter([
            'placeholder' => $this->placeholder,
        ], fn (mixed $value): bool => $value !== null);
    }
}
