<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\i18n;

/**
 * @inheritdoc
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.6
 */
class FormatConverter extends \yii\helpers\FormatConverter
{
    /**
     * @inheritdoc
     */
    public static function convertDatePhpToIcu($pattern): string
    {
        // Special cases for standalone values
        return match ($pattern) {
            'n' => 'L',
            'm' => 'LL',
            'M' => 'LLL',
            'F' => 'LLLL',
            'D' => 'ccc',
            'l' => 'cccc',
            default => parent::convertDatePhpToIcu($pattern),
        };
    }

    /**
     * Converts a date format pattern from [PHP date format][] to a human-readable format.
     *
     * The following patterns are supported:
     *
     * - `d`/`j` (`DD`)
     * - `m`/`n` (`MM`)
     * - `Y` (`YYYY`)
     * - `y` (`YY`)
     * - `a`/`A` (`AM/PM`)
     * - `g`/`G`/`h`/`H` (`HH`)
     * - `i` (`MM`)
     * - `s` (`SS`)
     *
     * [php date format]: https://www.php.net/manual/en/datetime.format.php
     *
     * @param string $pattern date format pattern in php date()-function format.
     * @return string The converted date format pattern.
     * @since 4.3.0
     */
    public static function convertDatePhpToHuman(string $pattern): string
    {
        // https://www.php.net/manual/en/function.date.php
        return strtr($pattern, [
            // Day
            'd' => 'DD', // Day of the month, 2 digits with leading zeros (01 through 31)
            'j' => 'DD', // Day of the month without leading zeros (1 through 31)
            // Month
            'm' => 'MM', // Numeric representation of a month, with leading zeros (01 through 12)
            'n' => 'MM', // Numeric representation of a month, without leading zeros (1 through 12)
            // Year
            'Y' => 'YYYY', // A full numeric representation of a year, 4 digits (Examples: 1999 or 2003)
            'y' => 'YY', // A two digit representation of a year (Examples: 99 or 03)
            // Time
            'a' => 'AM/PM', // Lowercase Ante meridiem and Post meridiem (am or pm)
            'A' => 'AM/PM', // Uppercase Ante meridiem and Post meridiem (AM or PM)
            'g' => 'HH', // 12-hour format of an hour without leading zeros (1 through 12)
            'G' => 'HH', // 24-hour format of an hour without leading zeros (0 through 23)
            'h' => 'HH', // 12-hour format of an hour with leading zeros (01 through 12)
            'H' => 'HH', // 24-hour format of an hour with leading zeros (00 through 23)
            'i' => 'MM', // Minutes with leading zeros (00 through 59)
            's' => 'SS', // Seconds, with leading zeros (00 through 59)
        ]);
    }
}
