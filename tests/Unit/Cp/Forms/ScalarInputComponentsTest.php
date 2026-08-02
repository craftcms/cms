<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\ComponentRegistry;
use CraftCms\Cms\Cp\Components\DateInput;
use CraftCms\Cms\Cp\Components\Field;
use CraftCms\Cms\Cp\Components\NumberInput;
use CraftCms\Cms\Cp\Components\TextInput;
use CraftCms\Cms\Cp\Components\TimeInput;
use CraftCms\Cms\Cp\Forms\Contracts\FormElement;
use CraftCms\Cms\Cp\Forms\Form;
use CraftCms\Cms\Cp\Forms\FormElementTypes;

it('renders each scalar component through the shared input primitive', function (FormElement $component, string $type) {
    $html = $component->toHtml();

    expect($html)->toContainTag('craft-input')
        ->and($html)->toContainTag('input', [
            'slot' => 'input',
            'type' => $type,
            'name' => 'setting',
            'value' => 'configured',
        ]);
})->with([
    'text' => [fn () => TextInput::make()->name('setting')->value('configured'), 'text'],
    'number' => [fn () => NumberInput::make()->name('setting')->value('configured'), 'number'],
    'date' => [fn () => DateInput::make()->name('setting')->value('configured'), 'date'],
    'time' => [fn () => TimeInput::make()->name('setting')->value('configured'), 'time'],
]);

it('registers each scalar component with one stable Form Element Type', function (
    string $componentName,
    string $class,
    string $type,
) {
    $component = app(ComponentRegistry::class)->make($componentName);
    $types = app(FormElementTypes::class);

    expect($component)->toBeInstanceOf($class)
        ->and($component)->toBeInstanceOf(FormElement::class)
        ->and($class::formElementType())->toBe($type)
        ->and($class::isFormElementContainer())->toBeFalse()
        ->and($types->isRegistered($type))->toBeTrue()
        ->and($types->isContainer($type))->toBeFalse();
})->with([
    'text' => ['text-input', TextInput::class, 'craft:text-input'],
    'number' => ['number-input', NumberInput::class, 'craft:number-input'],
    'date' => ['date-input', DateInput::class, 'craft:date-input'],
    'time' => ['time-input', TimeInput::class, 'craft:time-input'],
]);

it('projects scalar names, attributes, placeholders, constraints, and null values', function () {
    $form = Form::make([
        Field::make(TextInput::make()
            ->name(fn (): string => 'title')
            ->placeholder(fn (): string => 'Article title')
            ->attributes([
                'class' => 'code',
                'data' => ['mode' => 'plain'],
                'aria' => ['label' => 'Title'],
            ])),
        Field::make(NumberInput::make()
            ->name('limit')
            ->min(fn (): int => 0)
            ->max(fn (): int => 100)
            ->step(fn (): float => 0.5)),
        Field::make(DateInput::make()->name('startDate')),
        Field::make(TimeInput::make()->name('startTime')),
        Field::make(TextInput::make()->name('nullableText')->placeholder(null)),
        Field::make(NumberInput::make()->name('nullableNumber')->min(null)->max(null)->step(null)),
    ]);

    expect($form->toArray())->toBe([
        'elements' => [[
            'type' => 'craft:field',
            'children' => [[
                'type' => 'craft:text-input',
                'name' => 'title',
                'props' => ['placeholder' => 'Article title'],
                'attributes' => [
                    'class' => 'code',
                    'data' => ['mode' => 'plain'],
                    'aria' => ['label' => 'Title'],
                ],
            ]],
        ], [
            'type' => 'craft:field',
            'children' => [[
                'type' => 'craft:number-input',
                'name' => 'limit',
                'props' => ['type' => 'number', 'min' => 0, 'max' => 100, 'step' => 0.5],
            ]],
        ], [
            'type' => 'craft:field',
            'children' => [[
                'type' => 'craft:date-input',
                'name' => 'startDate',
                'props' => ['type' => 'date'],
            ]],
        ], [
            'type' => 'craft:field',
            'children' => [[
                'type' => 'craft:time-input',
                'name' => 'startTime',
                'props' => ['type' => 'time'],
            ]],
        ], [
            'type' => 'craft:field',
            'children' => [[
                'type' => 'craft:text-input',
                'name' => 'nullableText',
            ]],
        ], [
            'type' => 'craft:field',
            'children' => [[
                'type' => 'craft:number-input',
                'name' => 'nullableNumber',
                'props' => ['type' => 'number'],
            ]],
        ]],
    ]);
});

it('requires a local Input Name for scalar projection', function (FormElement $component) {
    expect(fn () => Form::make([Field::make($component)])->toArray())
        ->toThrow(
            InvalidArgumentException::class,
            sprintf('%s option "name" is not supported for Form output.', $component::class),
        );
})->with([
    'text' => [TextInput::make()],
    'number' => [NumberInput::make()],
    'date' => [DateInput::make()],
    'time' => [TimeInput::make()],
]);

it('rejects explicitly configured host-owned scalar state during projection', function (
    FormElement $component,
    string $option,
) {
    expect(fn () => Form::make([
        Field::make($component),
    ])->toArray())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "%s" is not supported for Form output.', $component::class, $option),
    );
})->with([
    'current value' => [fn () => TextInput::make()->name('setting')->value(null), 'value'],
    'effective read-only state' => [fn () => NumberInput::make()->name('setting')->readOnly(false), 'readOnly'],
    'final ID' => [fn () => DateInput::make()->name('setting')->id(null), 'id'],
    'accessibility reference' => [fn () => TimeInput::make()->name('setting')->describedBy(null), 'describedBy'],
    'native input attributes' => [fn () => TextInput::make()->name('setting')->inputAttributes([]), 'inputAttributes'],
]);

it('rejects host-owned scalar attributes during projection', function (string $attribute) {
    $component = TextInput::make()
        ->name('setting')
        ->attributes([$attribute => 'configured']);

    expect(fn () => Form::make([Field::make($component)])->toArray())
        ->toThrow(
            InvalidArgumentException::class,
            sprintf(
                '%s option "attributes.%s" is not supported for Form output.',
                TextInput::class,
                $attribute,
            ),
        );
})->with([
    'final name' => 'name',
    'final ID' => 'id',
    'current value' => 'value',
    'effective read-only state' => 'readonly',
    'semantic type' => 'type',
]);

it('rejects invalid portable scalar values', function (FormElement $component, string $option) {
    expect(fn () => Form::make([Field::make($component)])->toArray())
        ->toThrow(
            InvalidArgumentException::class,
            sprintf('%s option "%s" is not supported for Form output.', $component::class, $option),
        );
})->with([
    'text placeholder' => [fn () => TextInput::make()->name('setting')->placeholder(fn (): stdClass => new stdClass), 'placeholder'],
    'number minimum' => [fn () => NumberInput::make()->name('setting')->min('zero'), 'min'],
]);

it('does not allow a semantic scalar component to change its native input type', function () {
    $component = TextInput::make()->name('setting')->type('number');

    expect(fn () => $component->toHtml())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "type" is not supported for HTML output.', TextInput::class),
    )->and(fn () => Form::make([Field::make($component)])->toArray())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "type" is not supported for Form output.', TextInput::class),
    );
});

it('does not allow native input attributes to override a semantic scalar type', function () {
    $component = TextInput::make()->name('setting')->inputAttributes(['type' => 'number']);

    expect(fn () => $component->toHtml())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "inputAttributes.type" is not supported for HTML output.', TextInput::class),
    );
});
