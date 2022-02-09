<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use Money\Parser\DecimalMoneyParser;
use NumberFormatter;

/**
 * Class MoneyHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class MoneyHelper
{
    /**
     * @var ISOCurrencies
     */
    private static ISOCurrencies $_isoCurrencies;

    /**
     * @param Money|array $value
     * @return false|Money
     */
    public static function toMoney($value)
    {
        if ($value instanceof Money) {
            return $value;
        }

        if (!is_array($value) || empty($value) || (!array_key_exists('value', $value) || !array_key_exists('currency', $value))) {
            return false;
        }

        if (isset($value['locale'])) {
            $value['value'] = Localization::normalizeNumber($value['value'], $value['locale']);
        }

        return (new DecimalMoneyParser(self::_getIsoCurrencies()))
            ->parse((string)$value['value'], $value['currency']);
    }

    /**
     * Convert money object to standard decimal string.
     *
     * @param string|Money $value
     * @return false|string
     */
    public static function toDecimal($value)
    {
        if (!$value instanceof Money) {
            return false;
        }

        return (new DecimalMoneyFormatter(self::_getIsoCurrencies()))->format($value);
    }

    /**
     * Convert money object to localized currency string.
     *
     * @param $value
     * @param string|null $formatLocale
     * @return false|string
     */
    public static function toString($value, ?string $formatLocale = null)
    {
        if (is_string($value)) {
            return $value;
        }

        if (!$value instanceof Money) {
            return false;
        }

        $locale = $formatLocale ?? Craft::$app->getFormattingLocale()->id;

        $numberFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        return (new IntlMoneyFormatter($numberFormatter, self::_getIsoCurrencies()))->format($value);
    }

    /**
     * Convert money object to localized decimal string.
     *
     * @param $value
     * @param string|null $formatLocale
     * @return false|string
     */
    public static function toNumber($value, ?string $formatLocale = null)
    {
        if (is_string($value)) {
            return $value;
        }

        if (!$value instanceof Money) {
            return false;
        }

        $locale = $formatLocale ?? Craft::$app->getFormattingLocale()->id;

        $numberFormatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
        return (new IntlMoneyFormatter($numberFormatter, self::_getIsoCurrencies()))->format($value);
    }

    /**
     * @return ISOCurrencies
     */
    private static function _getIsoCurrencies(): ISOCurrencies
    {
        if (!isset(self::$_isoCurrencies)) {
            self::$_isoCurrencies = new ISOCurrencies();
        }

        return self::$_isoCurrencies;
    }
}
