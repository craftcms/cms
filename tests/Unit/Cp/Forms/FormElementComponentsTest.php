<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\Button;
use CraftCms\Cms\Cp\Components\ComponentRegistry;
use CraftCms\Cms\Cp\Components\Field;
use CraftCms\Cms\Cp\Components\Lightswitch;
use CraftCms\Cms\Cp\Components\TextInput;
use CraftCms\Cms\Cp\Enums\Size;
use CraftCms\Cms\Cp\Forms\Condition;
use CraftCms\Cms\Cp\Forms\Contracts\FormElement;
use CraftCms\Cms\Cp\Forms\Form;
use CraftCms\Cms\Cp\Forms\FormElementTypes;
use Illuminate\Support\HtmlString;

it('projects a Field and Lightswitch alongside existing authoring objects', function () {
    $form = Form::make([
        Field::make(TextInput::make()->name('mode')),
        Field::make(fn (): Lightswitch => Lightswitch::make()
            ->name(fn (): string => 'enabled')
            ->label(fn (): string => 'Feature state')
            ->onLabel(fn (): string => 'Enabled')
            ->offLabel(fn (): string => 'Disabled')
            ->size(fn (): Size => Size::Small)
            ->attributes(['data' => ['control' => 'feature']]))
            ->key('feature-toggle')
            ->columnWidth(50)
            ->label(fn (): string => 'Feature')
            ->instructions(fn (): string => 'Controls the feature.')
            ->tip(fn (): string => 'Available immediately.')
            ->warning(fn (): string => 'Use with care.')
            ->required(fn (): bool => true)
            ->readOnly(fn (): bool => true)
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
            'type' => 'craft:field',
            'key' => 'feature-toggle',
            'width' => 50,
            'props' => [
                'label' => 'Feature',
                'instructions' => 'Controls the feature.',
                'tip' => 'Available immediately.',
                'warning' => 'Use with care.',
                'required' => true,
                'readOnly' => true,
            ],
            'children' => [[
                'type' => 'craft:lightswitch-input',
                'name' => 'enabled',
                'props' => [
                    'label' => 'Feature state',
                    'onLabel' => 'Enabled',
                    'offLabel' => 'Disabled',
                    'size' => 'small',
                ],
                'attributes' => ['data-control' => 'feature'],
            ]],
            'visibleWhen' => [
                'name' => 'mode',
                'operator' => 'equals',
                'value' => 'advanced',
            ],
        ]],
    ]);
});

it('constructs a Field around a Lightswitch through make', function () {
    $input = Lightswitch::make()->name('enabled')->label('Feature');
    $shorthand = Field::make($input)->label('Feature state');
    $fluent = Field::make()->label('Feature state')->input($input);
    $expectedForm = [
        'elements' => [[
            'type' => 'craft:field',
            'props' => ['label' => 'Feature state'],
            'children' => [[
                'type' => 'craft:lightswitch-input',
                'name' => 'enabled',
                'props' => ['label' => 'Feature'],
            ]],
        ]],
    ];

    expect($shorthand->toHtml())->toBe($fluent->toHtml())
        ->and(Form::make([$shorthand])->toArray())->toBe($expectedForm)
        ->and(Form::make([$fluent])->toArray())->toBe($expectedForm);
});

it('preserves zero-argument Field construction and registry configuration', function () {
    expect(Field::make()->toHtml())->toBe('<craft-field></craft-field>')
        ->and(app(ComponentRegistry::class)->make('field', ['label' => 'Feature'])->toHtml())
        ->toBe('<craft-field label="Feature"></craft-field>');
});

it('exposes Form Element component metadata through the component and Form Element Type registries', function () {
    $components = app(ComponentRegistry::class);
    $types = app(FormElementTypes::class);

    expect($components->make('field'))
        ->toBeInstanceOf(FormElement::class)
        ->and($components->make('lightswitch'))
        ->toBeInstanceOf(FormElement::class)
        ->and(Field::formElementType())->toBe('craft:field')
        ->and(Field::isFormElementContainer())->toBeTrue()
        ->and(Lightswitch::formElementType())->toBe('craft:lightswitch-input')
        ->and(Lightswitch::isFormElementContainer())->toBeFalse()
        ->and($types->isRegistered(Field::formElementType()))->toBeTrue()
        ->and($types->isContainer(Field::formElementType()))->toBeTrue()
        ->and($types->isRegistered(Lightswitch::formElementType()))->toBeTrue()
        ->and($types->isContainer(Lightswitch::formElementType()))->toBeFalse();
});

it('projects repeatedly without mutating either component', function () {
    $input = Lightswitch::make()->name('enabled')->label('Enabled');
    $field = Field::make($input)->label('Feature');
    $html = $field->toHtml();

    $first = Form::make([$field])->toArray();
    $second = Form::make([$field])->toArray();

    expect($second)->toBe($first)
        ->and($field->toHtml())->toBe($html)
        ->and($input->toHtml())->not->toContainTag('craft-switch', ['slot' => 'input']);
});

it('keeps raw Field input available for HTML and rejects it for Form output', function () {
    $field = Field::make('<input name="enabled">');

    expect($field->toHtml())->toContainTag('input', ['name' => 'enabled', 'slot' => 'input'])
        ->and(fn () => Form::make([$field])->toArray())
        ->toThrow(
            InvalidArgumentException::class,
            sprintf('%s option "input" is not supported for Form output.', Field::class),
        );
});

it('rejects non-Form-Element Field inputs and portable raw markup', function (Field $field, string $option) {
    expect(fn () => Form::make([$field])->toArray())
        ->toThrow(
            InvalidArgumentException::class,
            sprintf('%s option "%s" is not supported for Form output.', Field::class, $option),
        );
})->with([
    'missing input' => [fn () => Field::make()->input(null), 'input'],
    'non-Form-Element component' => [fn () => Field::make(Button::make()), 'input'],
    'multiple inputs' => [
        fn () => Field::make(fn (): array => [
            Lightswitch::make()->name('first'),
            Lightswitch::make()->name('second'),
        ]),
        'input',
    ],
    'raw label markup' => [
        fn () => Field::make(Lightswitch::make()->name('enabled'))
            ->label(new HtmlString('<strong>Feature</strong>')),
        'label',
    ],
    'unsupported label object' => [
        fn () => Field::make(Lightswitch::make()->name('enabled'))
            ->label(fn (): stdClass => new stdClass),
        'label',
    ],
    'unsupported required value' => [
        fn () => Field::make(Lightswitch::make()->name('enabled'))
            ->required(fn (): stdClass => new stdClass),
        'required',
    ],
]);

it('requires a local Lightswitch Input Name for Form output', function () {
    expect(fn () => Form::make([
        Field::make(Lightswitch::make()),
    ])->toArray())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "name" is not supported for Form output.', Lightswitch::class),
    );
});

it('rejects executable projected attributes', function () {
    $form = Form::make([
        Field::make(Lightswitch::make()
            ->name('enabled')
            ->attributes(['bad' => fn (): string => 'value'])),
    ]);

    expect(fn () => $form->toArray())
        ->toThrow(
            InvalidArgumentException::class,
            'Form Element Type "craft:lightswitch-input" at elements[0].children[0]: attributes.bad is not serializable.',
        );
});

it('ignores host-owned Lightswitch attributes', function () {
    $form = Form::make([
        Field::make(Lightswitch::make()
            ->name('enabled')
            ->attributes([
                'checked' => true,
                'id' => 'configured',
                'value' => 'configured',
                'aria' => ['labelledby' => 'external-label'],
            ])),
    ]);

    expect($form->toArray())->toBe([
        'elements' => [[
            'type' => 'craft:field',
            'children' => [[
                'type' => 'craft:lightswitch-input',
                'name' => 'enabled',
            ]],
        ]],
    ]);
});

it('rejects Lightswitch sizes unsupported by the Form renderer', function () {
    $form = Form::make([
        Field::make(Lightswitch::make()->name('enabled')->size(Size::Large)),
    ]);

    expect(fn () => $form->toArray())
        ->toThrow(
            InvalidArgumentException::class,
            sprintf('%s option "size" is not supported for Form output.', Lightswitch::class),
        );
});

it('ignores output-specific component options', function () {
    $input = Lightswitch::make()
        ->name('enabled')
        ->on()
        ->indeterminate()
        ->disabled()
        ->id('enabled-input')
        ->slot('content')
        ->buttonAttributes(['class' => 'configured'])
        ->value('yes')
        ->toggle('settings');
    $field = Field::make($input)
        ->attributes(['class' => 'configured'])
        ->instructionsPosition('after')
        ->translatable()
        ->key('feature')
        ->columnWidth(50)
        ->visibleWhen(Condition::equals('available', true));

    expect(Form::make([
        Field::make(TextInput::make()->name('available')),
        $field,
    ])->toArray())->toBe([
        'elements' => [[
            'type' => 'craft:field',
            'children' => [[
                'type' => 'craft:text-input',
                'name' => 'available',
            ]],
        ], [
            'type' => 'craft:field',
            'key' => 'feature',
            'width' => 50,
            'children' => [[
                'type' => 'craft:lightswitch-input',
                'name' => 'enabled',
            ]],
            'visibleWhen' => [
                'name' => 'available',
                'operator' => 'equals',
                'value' => true,
            ],
        ]],
    ])->and($field->toHtml())->toContainTag('craft-field');
});
