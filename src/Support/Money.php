<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use CraftCms\Cms\Support\Facades\I18N;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money as MoneyLibrary;
use Money\Parser\DecimalMoneyParser;
use Money\Parser\IntlMoneyParser;
use NumberFormatter;

final class Money
{
    private static ISOCurrencies $isoCurrencies;

    public static function toMoney(mixed $value): MoneyLibrary|false
    {
        if ($value instanceof MoneyLibrary) {
            return $value;
        }

        if (! is_array($value) || empty($value) || (! array_key_exists('value', $value) || ! array_key_exists('currency', $value))) {
            return false;
        }

        if (isset($value['locale'])) {
            $value['value'] = I18N::normalizeNumber($value['value'], $value['locale']);
        }

        $currency = ! $value['currency'] instanceof Currency ? new Currency($value['currency']) : $value['currency'];

        return new DecimalMoneyParser(self::getIsoCurrencies())->parse(
            money: (string) $value['value'],
            fallbackCurrency: $currency,
        );
    }

    public static function toDecimal(mixed $value): string|false
    {
        if (! $value instanceof MoneyLibrary) {
            return false;
        }

        return new DecimalMoneyFormatter(self::getIsoCurrencies())->format($value);
    }

    public static function toString(mixed $value, ?string $formatLocale = null): string|false
    {
        if (is_string($value)) {
            return $value;
        }

        if (! $value instanceof MoneyLibrary) {
            return false;
        }

        $locale = $formatLocale ?? I18N::getFormattingLocale()->id;

        return new IntlMoneyFormatter(
            formatter: new NumberFormatter($locale, NumberFormatter::CURRENCY),
            currencies: self::getIsoCurrencies()
        )->format($value);
    }

    public static function toNumber(mixed $value, ?string $formatLocale = null): string|false
    {
        if (is_string($value)) {
            return $value;
        }

        if (! $value instanceof MoneyLibrary) {
            return false;
        }

        $locale = $formatLocale ?? I18N::getFormattingLocale()->id;

        return new IntlMoneyFormatter(
            formatter: new NumberFormatter($locale, NumberFormatter::DECIMAL),
            currencies: self::getIsoCurrencies(),
        )->format($value);
    }

    public static function normalizeString(string $value, ?Currency $fallbackCurrency = null): string
    {
        $locale = I18N::getFormattingLocale()->id;
        $numberFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $moneyParser = new IntlMoneyParser($numberFormatter, self::getIsoCurrencies());

        return $moneyParser->parse($value, $fallbackCurrency)->getAmount();
    }

    private static function getIsoCurrencies(): ISOCurrencies
    {
        return self::$isoCurrencies ??= new ISOCurrencies;
    }
}
