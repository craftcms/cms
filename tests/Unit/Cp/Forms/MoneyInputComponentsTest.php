<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\ComponentRegistry;
use CraftCms\Cms\Cp\Components\Field;
use CraftCms\Cms\Cp\Components\MoneyInput;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Cp\Forms\Contracts\FormElement;
use CraftCms\Cms\Cp\Forms\Form;
use CraftCms\Cms\Cp\Forms\FormElementTypes;
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

it('registers and projects the money component', function () {
    $registry = app(ComponentRegistry::class);
    $types = app(FormElementTypes::class);
    $form = Form::make([
        Field::make(MoneyInput::make()
            ->name('amount')
            ->currency('USD')
            ->fractionDigits(2)
            ->placeholder('0.00')),
    ]);

    expect($registry->make('money-input'))->toBeInstanceOf(MoneyInput::class)
        ->and(MoneyInput::formElementType())->toBe('craft:money-input')
        ->and($types->isRegistered(MoneyInput::formElementType()))->toBeTrue()
        ->and($form->toArray())->toBe([
            'elements' => [[
                'type' => 'craft:field',
                'children' => [[
                    'type' => 'craft:money-input',
                    'name' => 'amount',
                    'props' => [
                        'currency' => 'USD',
                        'fractionDigits' => 2,
                        'minorUnits' => true,
                        'placeholder' => '0.00',
                    ],
                ]],
            ]],
        ]);
});

it('keeps host values and HTML-only options out of specialized projection', function (FormElement $component) {
    expect(Form::make([
        Field::make($component),
    ])->toArray())->toBe([
        'elements' => [[
            'type' => 'craft:field',
            'children' => [[
                'type' => 'craft:money-input',
                'name' => 'amount',
                'props' => [
                    'fractionDigits' => 2,
                    'minorUnits' => true,
                ],
            ]],
        ]],
    ]);
})->with([
    'money value' => [fn () => MoneyInput::make()->name('amount')->value(null)],
    'money locale' => [fn () => MoneyInput::make()->name('amount')->formattingLocale('en-US')],
    'money currency label' => [fn () => MoneyInput::make()->name('amount')->currencyLabel('US dollars')],
]);

it('rejects invalid portable specialized configuration', function (FormElement $component, string $option) {
    expect(fn () => Form::make([
        Field::make($component),
    ])->toArray())->toThrow(
        InvalidArgumentException::class,
        sprintf('%s option "%s" is not supported for Form output.', $component::class, $option),
    );
})->with([
    'money currency' => [fn () => MoneyInput::make()->name('amount')->currency(fn (): array => []), 'currency'],
    'money fraction digits' => [fn () => MoneyInput::make()->name('amount')->fractionDigits(-1), 'fractionDigits'],
]);
