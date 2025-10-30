<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Money;
use Money\Currency;
use Money\Money as MoneyLibrary;

test('toMoney', function (mixed $value, MoneyLibrary|false $expected) {
    expect(Money::toMoney($value))->toEqualCanonicalizing($expected);
})->with([
    [null, false],
    [MoneyLibrary::USD(100), new MoneyLibrary(100, new Currency('USD'))],
    [['value' => '1.25', 'currency' => 'USD'], MoneyLibrary::USD('125')],
    [['value' => '1.234,56', 'currency' => 'USD', 'locale' => 'nl'], MoneyLibrary::USD('123456')],
]);

test('toDecimal', function (mixed $value, mixed $expected) {
    expect(Money::toDecimal($value))->toEqualCanonicalizing($expected);
})->with([
    [MoneyLibrary::USD('123456'), '1234.56'],
    [MoneyLibrary::JPY('123456'), '123456'],
    [MoneyLibrary::BHD('123456'), '123.456'],
    [null, false],
]);

test('toNumber', function (mixed $value, mixed $expected, ?string $locale = null) {
    if ($locale) {
        app()->setLocale($locale);
    }

    expect(Money::toNumber($value))->toEqualCanonicalizing($expected);
})->with([
    [null, false, null],
    ['1,234.56', '1,234.56', null],
    [MoneyLibrary::USD('123456'), '1,234.56', null],
    [MoneyLibrary::USD('123456'), '1.234,56', 'nl'],
]);

test('toString', function (mixed $value, mixed $expected, ?string $locale = null) {
    if ($locale) {
        app()->setLocale($locale);
    }

    expect(Money::toString($value))->toEqualCanonicalizing($expected);
})->with([
    [null, false, null],
    ['1,234.56', '1,234.56', null],
    [MoneyLibrary::USD('123456'), '$1,234.56', null],
    [MoneyLibrary::USD('123456'), "US$\xc2\xa01.234,56", 'nl'],
]);
