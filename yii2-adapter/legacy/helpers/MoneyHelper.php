<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Money\Currency;
use Money\Money;

/**
 * Class MoneyHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Money} instead.
 */
class MoneyHelper
{
    /**
     * @param mixed $value
     * @return Money|false
     */
    public static function toMoney(mixed $value): Money|false
    {
        return \CraftCms\Cms\Support\Money::toMoney($value);
    }

    /**
     * Convert money object to standard decimal string.
     *
     * @param mixed $value
     * @return string|false
     */
    public static function toDecimal(mixed $value): string|false
    {
        return \CraftCms\Cms\Support\Money::toDecimal($value);
    }

    /**
     * Convert money object to localized currency string.
     *
     * @param mixed $value
     * @param string|null $formatLocale
     * @return string|false
     */
    public static function toString(mixed $value, ?string $formatLocale = null): string|false
    {
        return \CraftCms\Cms\Support\Money::toString($value, $formatLocale);
    }

    /**
     * Convert money object to localized decimal string.
     *
     * @param mixed $value
     * @param string|null $formatLocale
     * @return string|false
     */
    public static function toNumber(mixed $value, ?string $formatLocale = null): string|false
    {
        return \CraftCms\Cms\Support\Money::toNumber($value, $formatLocale);
    }

    /**
     * @param string $value
     * @param Currency|null $fallbackCurrency
     * @return string
     * @since 5.2.9
     */
    public static function normalizeString(string $value, ?Currency $fallbackCurrency = null): string
    {
        return \CraftCms\Cms\Support\Money::normalizeString($value, $fallbackCurrency);
    }
}
