<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

/**
 * Console helper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Console extends \yii\helpers\Console
{
    /**
     * Prints a string to STDOUT.
     *
     * You may optionally format the string with ANSI codes by
     * passing additional parameters using the constants defined in [[\yii\helpers\Console]].
     * Example:
     *
     * ```php
     * Console::stdout('This will be red and underlined.', Console::FG_RED, Console::UNDERLINE);
     * ```
     *
     * @param string $string the string to print
     * @return int|bool Number of bytes printed or false on error
     */
    public static function stdout($string)
    {
        if (static::streamSupportsAnsiColors(\STDOUT)) {
            $args = func_get_args();
            array_shift($args);
            $string = self::ansiFormat($string, $args);
        }

        return parent::stdout($string);
    }
}
