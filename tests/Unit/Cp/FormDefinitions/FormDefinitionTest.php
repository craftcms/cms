<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\FormDefinitions\Elements\FormElement;
use CraftCms\Cms\Cp\FormDefinitions\Elements\TextInput;
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
        'Form Element Type "plugin:unknown" at elements[0]: unknown Form Element Type.',
    ],
    'nested unknown type' => [
        fn () => TestFormElement::make('craft:field', children: [
            TestFormElement::make('plugin:unknown', name: 'title'),
        ]),
        'Form Element Type "plugin:unknown" at elements[0].children[0]: unknown Form Element Type.',
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
        'Form Element Type "craft:field" at elements[0]: a Field Container must contain exactly one input.',
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
    ): self {
        return new self($type, $name, $props, $children);
    }

    public function type(): string
    {
        return $this->elementType;
    }

    protected function props(): array
    {
        return $this->elementProps;
    }

    protected function children(): array
    {
        return $this->elementChildren;
    }
}
