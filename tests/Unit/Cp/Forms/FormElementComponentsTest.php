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
    'missing input' => [fn () => Field::make(null), 'input'],
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

it('rejects host-owned Lightswitch attributes', function (string $attribute) {
    $form = Form::make([
        Field::make(Lightswitch::make()
            ->name('enabled')
            ->attributes([$attribute => 'configured'])),
    ]);

    expect(fn () => $form->toArray())
        ->toThrow(
            InvalidArgumentException::class,
            sprintf(
                '%s option "attributes.%s" is not supported for Form output.',
                Lightswitch::class,
                $attribute,
            ),
        );
})->with([
    'current value' => 'checked',
    'final ID' => 'id',
    'accessibility reference' => 'aria-labelledby',
    'submission value' => 'value',
]);

it('rejects nested host-owned Lightswitch accessibility attributes', function () {
    $form = Form::make([
        Field::make(Lightswitch::make()
            ->name('enabled')
            ->attributes(['aria' => ['labelledby' => 'external-label']])),
    ]);

    expect(fn () => $form->toArray())
        ->toThrow(
            InvalidArgumentException::class,
            sprintf(
                '%s option "attributes.aria-labelledby" is not supported for Form output.',
                Lightswitch::class,
            ),
        );
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

it('rejects explicitly configured Field HTML-only options during projection', function (Field $field, string $option) {
    $field->input(Lightswitch::make()->name('enabled'));

    expect(fn () => Form::make([$field])->toArray())
        ->toThrow(
            InvalidArgumentException::class,
            sprintf('%s option "%s" is not supported for Form output.', Field::class, $option),
        );
})->with([
    'attributes' => [fn () => Field::make()->attributes([]), 'attributes'],
    'disabled default' => [fn () => Field::make()->disabled(false), 'disabled'],
    'id default' => [fn () => Field::make()->id(null), 'id'],
    'slot' => [fn () => Field::make()->slot('content'), 'slot'],
    'instructions position default' => [fn () => Field::make()->instructionsPosition('before'), 'instructionsPosition'],
    'translatable default' => [fn () => Field::make()->translatable(false), 'translatable'],
    'fieldset default' => [fn () => Field::make()->fieldset(false), 'fieldset'],
    'status default' => [fn () => Field::make()->status(null), 'status'],
    'orientation default' => [fn () => Field::make()->orientation(null), 'orientation'],
    'HTML width default' => [fn () => Field::make()->width(null), 'width'],
    'heading prefix default' => [fn () => Field::make()->headingPrefix(null), 'headingPrefix'],
    'heading suffix default' => [fn () => Field::make()->headingSuffix(null), 'headingSuffix'],
    'errors default' => [fn () => Field::make()->errors([]), 'errors'],
    'label extra default' => [fn () => Field::make()->labelExtra(null), 'labelExtra'],
]);

it('rejects explicitly configured Lightswitch HTML-only options during projection', function (
    Lightswitch $lightswitch,
    string $option,
) {
    $lightswitch->name('enabled');

    expect(fn () => Form::make([Field::make($lightswitch)])->toArray())
        ->toThrow(
            InvalidArgumentException::class,
            sprintf('%s option "%s" is not supported for Form output.', Lightswitch::class, $option),
        );
})->with([
    'checked default' => [fn () => Lightswitch::make()->on(false), 'on'],
    'indeterminate default' => [fn () => Lightswitch::make()->indeterminate(false), 'indeterminate'],
    'disabled default' => [fn () => Lightswitch::make()->disabled(false), 'disabled'],
    'id default' => [fn () => Lightswitch::make()->id(null), 'id'],
    'slot' => [fn () => Lightswitch::make()->slot('content'), 'slot'],
    'button attributes default' => [fn () => Lightswitch::make()->buttonAttributes([]), 'buttonAttributes'],
    'posting value default' => [fn () => Lightswitch::make()->value('1'), 'value'],
    'indeterminate value default' => [fn () => Lightswitch::make()->indeterminateValue('-'), 'indeterminateValue'],
    'toggle default' => [fn () => Lightswitch::make()->toggle(null), 'toggle'],
    'reverse toggle default' => [fn () => Lightswitch::make()->reverseToggle(null), 'reverseToggle'],
    'labelled by default' => [fn () => Lightswitch::make()->labelledBy(null), 'labelledBy'],
    'described by default' => [fn () => Lightswitch::make()->describedBy(null), 'describedBy'],
    'instructions default' => [fn () => Lightswitch::make()->instructions(null), 'instructions'],
]);

it('rejects Form-only Field options during HTML rendering', function (Field $field, string $option) {
    expect(fn () => $field->toHtml())
        ->toThrow(
            InvalidArgumentException::class,
            sprintf('%s option "%s" is not supported for HTML output.', Field::class, $option),
        );
})->with([
    'key default' => [fn () => Field::make()->key(null), 'key'],
    'column width default' => [fn () => Field::make()->columnWidth(null), 'columnWidth'],
    'visibility default' => [fn () => Field::make()->visibleWhen(null), 'visibleWhen'],
]);
