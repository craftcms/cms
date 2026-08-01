<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\ColorPalette;
use CraftCms\Cms\Cp\Components\ComponentRegistry;
use CraftCms\Cms\Cp\Components\Field;
use CraftCms\Cms\Cp\Components\MoneyInput;
use CraftCms\Cms\Cp\FormDefinitions\Contracts\ProjectableFormElement;
use CraftCms\Cms\Cp\FormDefinitions\FormDefinition;
use CraftCms\Cms\Cp\FormDefinitions\FormElementTypes;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Support\Facades\Deprecator;

it('renders money through the shared primitive with its structured value contract', function () {
    $html = MoneyInput::make()
        ->id('price')
        ->name('price')
        ->value('12,50')
        ->currency('USD')
        ->currencyLabel('(USD) $')
        ->fractionDigits(2)
        ->decimalSeparator(',')
        ->groupSeparator('.')
        ->placeholder('0,00')
        ->formattingLocale('nl-BE')
        ->readOnly()
        ->toHtml();

    expect($html)->toContainTag('craft-input-money', [
        'currency' => 'USD',
        'currency-label' => '(USD) $',
        'decimal-separator' => ',',
        'fraction-digits' => '2',
        'group-separator' => '.',
        'name' => 'price[value]',
        'placeholder' => '0,00',
        'readonly' => true,
    ])->and($html)->toContainTag('input', [
        'slot' => 'input',
        'id' => 'price',
        'name' => 'price[value]',
        'inputmode' => 'decimal',
        'type' => 'text',
        'value' => '12,50',
    ])->and($html)->toContainTag('input', [
        'type' => 'hidden',
        'name' => 'price[locale]',
        'value' => 'nl-BE',
    ]);
});

it('maps the legacy money surface onto the MoneyInput component', function () {
    $html = FormFields::moneyInputHtml([
        'id' => 'amount',
        'name' => 'amount',
        'value' => '19,95',
        'currency' => 'EUR',
        'currencyLabel' => '(EUR) €',
        'decimals' => 2,
        'formattingLocale' => 'nl-BE',
        'decimalSeparator' => ',',
        'groupSeparator' => '.',
        'containerAttributes' => ['data' => ['money-container' => true]],
        'inputAttributes' => ['data' => ['setting' => 'amount']],
    ]);

    expect($html)->toContainTag('craft-input-money', [
        'currency' => 'EUR',
        'currency-label' => '(EUR) €',
        'data-money-container' => true,
        'decimal-separator' => ',',
        'group-separator' => '.',
    ])->and($html)->toContainTag('input', [
        'id' => 'amount',
        'name' => 'amount[value]',
        'data-setting' => 'amount',
    ])->and($html)->toContainTag('input', [
        'name' => 'amount[locale]',
        'value' => 'nl-BE',
    ]);
});

it('deprecates legacy money behavior that the shared primitive cannot preserve', function () {
    Deprecator::shouldReceive('log')
        ->once()
        ->with('money-config-jsSettings', Mockery::type('string'));
    Deprecator::shouldReceive('log')
        ->once()
        ->with('money-config-showClear', Mockery::type('string'));

    FormFields::moneyFromConfig([
        'jsSettings' => ['maskOptions' => ['autoGroup' => true]],
        'showClear' => false,
    ]);
});

it('renders color palette rows through the shared primitive without losing nullable values', function () {
    $palette = [
        ['color' => '#ff0000', 'label' => 'Red', 'default' => true],
        ['color' => null, 'label' => null, 'default' => false],
    ];

    $html = ColorPalette::make()
        ->name('palette')
        ->value($palette)
        ->readOnly()
        ->toHtml();

    expect($html)->toContainTag('craft-color-palette', [
        'name' => 'palette',
        'value' => json_encode($palette, JSON_THROW_ON_ERROR),
        'readonly' => true,
    ]);
});

it('registers and projects money and color palette components', function () {
    $registry = app(ComponentRegistry::class);
    $types = app(FormElementTypes::class);
    $definition = FormDefinition::make([
        Field::make()->input(
            MoneyInput::make()
                ->name('amount')
                ->currency('USD')
                ->fractionDigits(2)
                ->placeholder('0.00'),
        ),
        Field::make()->input(ColorPalette::make()->name('palette')),
    ]);

    expect($registry->make('money-input'))->toBeInstanceOf(MoneyInput::class)
        ->and($registry->make('color-palette'))->toBeInstanceOf(ColorPalette::class)
        ->and(MoneyInput::formElementType())->toBe('craft:money-input')
        ->and(ColorPalette::formElementType())->toBe('craft:color-palette-input')
        ->and($types->isRegistered(MoneyInput::formElementType()))->toBeTrue()
        ->and($types->isRegistered(ColorPalette::formElementType()))->toBeTrue()
        ->and($definition->toArray())->toBe([
            'elements' => [[
                'type' => 'craft:field',
                'children' => [[
                    'type' => 'craft:money-input',
                    'name' => 'amount',
                    'props' => [
                        'currency' => 'USD',
                        'fractionDigits' => 2,
                        'placeholder' => '0.00',
                    ],
                ]],
            ], [
                'type' => 'craft:field',
                'children' => [[
                    'type' => 'craft:color-palette-input',
                    'name' => 'palette',
                ]],
            ]],
        ]);
});

it('keeps host values and HTML-only options out of specialized projection', function (
    ProjectableFormElement $component,
    string $option,
) {
    expect(fn () => FormDefinition::make([
        Field::make()->input($component),
    ])->toArray())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "%s" is not supported for Form Definition output.', $component::class, $option),
    );
})->with([
    'money value' => [fn () => MoneyInput::make()->name('amount')->value(null), 'value'],
    'money locale' => [fn () => MoneyInput::make()->name('amount')->formattingLocale('en-US'), 'formattingLocale'],
    'money currency label' => [fn () => MoneyInput::make()->name('amount')->currencyLabel('US dollars'), 'currencyLabel'],
    'palette value' => [fn () => ColorPalette::make()->name('palette')->value([]), 'value'],
    'palette read-only state' => [fn () => ColorPalette::make()->name('palette')->readOnly(false), 'readOnly'],
]);

it('rejects invalid portable specialized configuration', function (ProjectableFormElement $component, string $option) {
    expect(fn () => FormDefinition::make([
        Field::make()->input($component),
    ])->toArray())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "%s" is not supported for Form Definition output.', $component::class, $option),
    );
})->with([
    'money currency' => [fn () => MoneyInput::make()->name('amount')->currency(fn (): array => []), 'currency'],
    'money fraction digits' => [fn () => MoneyInput::make()->name('amount')->fractionDigits(-1), 'fractionDigits'],
]);
