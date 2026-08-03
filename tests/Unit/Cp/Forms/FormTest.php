<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\Field;
use CraftCms\Cms\Cp\Components\Group;
use CraftCms\Cms\Cp\Components\Tab;
use CraftCms\Cms\Cp\Components\Tabs;
use CraftCms\Cms\Cp\Components\TextInput;
use CraftCms\Cms\Cp\Components\ViewComponent;
use CraftCms\Cms\Cp\Forms\Condition;
use CraftCms\Cms\Cp\Forms\Contracts\FormDefinition;
use CraftCms\Cms\Cp\Forms\Contracts\FormElement;
use CraftCms\Cms\Cp\Forms\Data\FormElementData;
use CraftCms\Cms\Cp\Forms\Data\VisibilityConditionData;
use CraftCms\Cms\Cp\Forms\Form;
use CraftCms\Cms\Cp\Forms\FormElementTypes;

beforeEach(function () {
    app()->forgetInstance(FormElementTypes::class);
    app(FormElementTypes::class)->register(TestFormElementContainer::class);
});

it('projects a native text setting through an explicit field container', function () {
    $form = Form::make([
        Field::make(TextInput::make()
            ->name('handle')
            ->placeholder('myComponent')
            ->attributes([
                'autocomplete' => 'off',
                'data-setting' => 'handle',
            ]))
            ->label(fn (): string => 'Handle')
            ->instructions('How templates refer to this component.')
            ->columnWidth(50)
            ->readOnly(),
    ]);

    expect($form)->toBeInstanceOf(FormDefinition::class)
        ->and(Form::fromDefinition($form))->toBe($form)
        ->and($form->toArray())->toBe([
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
    $form = Form::make([
        Group::make([
            Tabs::make([
                Tab::make('content', fn (): string => 'Content', [
                    Field::make(TextInput::make()->name('title')),
                ]),
                Tab::make('metadata', 'Metadata', [
                    Field::make(TextInput::make()->name('slug')),
                ])->hasErrors(),
            ])->key('settings-tabs'),
        ])->key('settings'),
    ]);

    expect($form->toArray())->toBe([
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
    $form = Form::make([
        Group::make([Field::make(TextInput::make()->name('title'))])->key('settings'),
        Group::make([Field::make(TextInput::make()->name('slug'))])->key('settings'),
    ]);

    expect(fn () => $form->toArray())
        ->toThrow(
            InvalidArgumentException::class,
            'Form Element Type "craft:group" at elements[1]: duplicate sibling key "settings".',
        );
});

it('rejects malformed container structures', function (FormElement $element, string $message) {
    expect(fn () => Form::make([$element])->toArray())
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'empty tabs' => [
        fn () => Tabs::make([]),
        'Form Element Type "craft:tabs" at elements[0]: Tabs must contain at least one Tab.',
    ],
    'tab outside tabs' => [
        fn () => Group::make([Tab::make('content', 'Content', [])]),
        sprintf(
            '%s child at index 0 (%s) must be a non-Tab form element for Form output.',
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

it('keeps host workflow data outside the serialized form', function () {
    $json = json_encode(
        Form::make([
            Field::make(TextInput::make()->name('handle'))
                ->label('Handle'),
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
    $form = Form::make([
        Field::make(TextInput::make()->name('enabled')),
        Field::make(TextInput::make()->name('mode')),
        Field::make(TextInput::make()->name('label'))
            ->visibleWhen(Condition::all(
                Condition::equals('enabled', true),
                Condition::any(
                    Condition::equals('mode', 'manual'),
                    Condition::notEquals('mode', 'automatic'),
                ),
            )),
    ]);

    expect($form->toArray()['elements'][2]['visibleWhen'])->toBe([
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
    $form = Form::make([
        Field::make(TextInput::make()->name('source')),
        Field::make(TextInput::make()->name('target'))
            ->visibleWhen(Condition::equals('source', 'trim')),
    ]);

    expect($form->toArray()['elements'][1]['visibleWhen'])->toBe([
        'name' => 'source',
        'operator' => 'equals',
        'value' => 'trim',
    ]);
});

it('projects every visibility comparison operator', function (Condition $condition, array $expected) {
    $form = Form::make([
        Field::make(TextInput::make()->name('source')),
        Field::make(TextInput::make()->name('target'))
            ->visibleWhen($condition),
    ]);

    expect($form->toArray()['elements'][1]['visibleWhen'])->toBe($expected);
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
    $form = Form::make([
        TestFormElementContainer::make()
            ->children([
                Field::make(TextInput::make()->name('target'))->toFormElementData(),
            ])
            ->visibility(new VisibilityConditionData($condition)),
    ]);

    expect(fn () => $form->toArray())
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
    $form = Form::make([
        Field::make(TextInput::make()->name('target'))
            ->visibleWhen(Condition::equals('missing', true)),
    ]);

    expect(fn () => $form->toArray())
        ->toThrow(
            InvalidArgumentException::class,
            'Form Element Type "craft:field" at elements[0].visibleWhen: unresolved Input Name "missing".',
        );
});

it('rejects duplicate input names with type and tree location context', function () {
    $form = Form::make([
        Field::make(TextInput::make()->name('handle')),
        Field::make(TextInput::make()->name('handle')),
    ]);

    expect(fn () => $form->toArray())
        ->toThrow(
            InvalidArgumentException::class,
            'Form Element Type "craft:text-input" at elements[1].children[0]: duplicate Input Name "handle".',
        );
});

it('rejects host-owned renderer props with type and tree location context', function (string $prop) {
    $form = Form::make([
        TestFormElementContainer::make()->children([
            new FormElementData(
                type: 'craft:text-input',
                name: 'title',
                props: [$prop => 'configured'],
            ),
        ]),
    ]);

    expect(fn () => $form->toArray())
        ->toThrow(
            InvalidArgumentException::class,
            "Form Element Type \"craft:text-input\" at elements[0].children[0]: renderer prop \"{$prop}\" is owned by the Form host.",
        );
})->with([
    'current value' => 'modelValue',
    'kebab-case current value' => 'model-value',
    'read-only state' => 'readonly',
    'final name' => 'name',
    'final ID' => 'id',
    'required state' => 'required',
    'description reference' => 'aria-describedby',
    'label reference' => 'aria-labelledby',
    'accessible required state' => 'aria-required',
]);

it('rejects host-owned renderer attributes with type and tree location context', function (
    array $attributes,
    string $attribute,
) {
    $form = Form::make([
        TestFormElementContainer::make()->children([
            new FormElementData(
                type: 'craft:text-input',
                name: 'title',
                attributes: $attributes,
            ),
        ]),
    ]);

    expect(fn () => $form->toArray())
        ->toThrow(
            InvalidArgumentException::class,
            "Form Element Type \"craft:text-input\" at elements[0].children[0]: renderer attribute \"{$attribute}\" is owned by the Form host.",
        );
})->with([
    'final name' => [['name' => 'configured'], 'name'],
    'final ID' => [['id' => 'configured'], 'id'],
    'read-only state' => [['readonly' => false], 'readonly'],
    'required state' => [['required' => false], 'required'],
    'description reference' => [['aria-describedby' => 'configured'], 'aria-describedby'],
    'label reference' => [['aria-labelledby' => 'configured'], 'aria-labelledby'],
    'accessible required state' => [['aria-required' => 'false'], 'aria-required'],
    'grouped accessibility state' => [['aria' => ['required' => 'false']], 'aria-required'],
]);

it('rejects invalid widths and non-serializable portable data', function (
    FormElement $element,
    string $message,
) {
    expect(fn () => Form::make([$element])->toArray())
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'invalid width' => [
        fn () => Field::make(TextInput::make()->name('title'))
            ->columnWidth(0),
        'Form Element Type "craft:field" at elements[0]: width must be between 1 and 100.',
    ],
    'non-serializable props' => [
        fn () => TestFormElementContainer::make()->props(['bad' => new stdClass]),
        'Form Element Type "application:test-container" at elements[0]: props.bad is not serializable.',
    ],
    'non-serializable attributes' => [
        fn () => Field::make(TextInput::make()
            ->name('title')
            ->attributes(['bad' => fn () => null])),
        'Form Element Type "craft:text-input" at elements[0].children[0]: attributes.bad is not serializable.',
    ],
]);

class TestFormElementContainer extends ViewComponent implements FormElement
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
        return 'test-form-element-container';
    }
}
