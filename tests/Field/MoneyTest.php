<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Money;
use CraftCms\Cms\Support\Facades\I18N;
use Money\Currency;

beforeEach(function () {
    $this->field = new Money;
});

test('construct', function (array $config, array $expected) {
    $field = new Money($config);

    foreach ($expected as $attr => $value) {
        expect($field->$attr)->toBe($value);
    }
})->with([
    [
        [
            'currency' => 'USD',
            'defaultValue' => '123',
            'min' => '100',
            'max' => '456',
        ],
        [
            'currency' => 'USD',
            'defaultValue' => 123,
            'min' => 100,
            'max' => 456,
        ],
    ],
    [
        [
            'currency' => 'USD',
            'defaultValue' => ['locale' => 'nl', 'value' => '1.234,56'],
            'min' => ['locale' => 'nl', 'value' => '100'],
            'max' => ['locale' => 'nl', 'value' => '5000,00'],
        ],
        [
            'currency' => 'USD',
            'defaultValue' => 123456,
            'min' => 10000,
            'max' => 500000,
        ],
    ],
]);

test('normalizeValue', function (mixed $money, string $value, ?string $defaultValue) {
    $this->field->defaultValue = $defaultValue !== null ? (float) $defaultValue : null;
    $normalized = $this->field->normalizeValue($money, null);

    expect($normalized)->toBeInstanceOf(\Money\Money::class);
    expect($normalized->getAmount())->toBe($value);
})->with(fn () => [
    'money-object' => [new \Money\Money(100, new Currency('USD')), '100', null],
    'default-value' => [null, '123', '123'],
    'array-passed' => [
        [
            'value' => '1,23',
            'locale' => 'nl',
        ], '123', null,
    ],
]);

test('getTableAttributeHtml', function (mixed $value, string $expected, ?string $locale = null) {
    if ($locale) {
        $oldLocaleId = I18N::getFormattingLocale()->id;
        I18N::getFormattingLocale()->id = $locale;
    }

    $html = $this->field->getPreviewHtml($value, new \craft\elements\Entry);

    expect($html)->toBe($expected);

    if ($locale) {
        I18N::getFormattingLocale()->id = $oldLocaleId;
    }
})->with([
    [new \Money\Money('100', new Currency('USD')), '$1.00', null],
    ['$1.00', '$1.00', null],
    [new \Money\Money('100', new Currency('USD')), "US$\xc2\xa01,00", 'nl'],
])->todo('Rewrite when Entries and Locales are ported to Laravel');

test('serialize', function (?\Money\Money $value, ?string $expected) {
    $serialized = $this->field->serializeValue($value);

    if ($value instanceof \Money\Money) {
        expect($serialized)->toBeString();
    } else {
        expect($serialized)->toBeNull();
    }

    expect($serialized)->toBe($expected);
})->with([
    [null, null],
    [new \Money\Money('100', new Currency('USD')), '100'],
]);
