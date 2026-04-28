<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use InvalidArgumentException;
use RuntimeException;
use function CraftCms\Cms\t;

/**
 * Class Number
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0
 */
class Number
{
    /**
     * Returns the "word" version of a number
     *
     * @param int $num The number
     * @return string The number word, or the original number if it's >= 10
     */
    public static function word(int $num): string
    {
        $numberWordMap = [
            1 => t('one'),
            2 => t('two'),
            3 => t('three'),
            4 => t('four'),
            5 => t('five'),
            6 => t('six'),
            7 => t('seven'),
            8 => t('eight'),
            9 => t('nine'),
        ];

        return $numberWordMap[$num] ?? (string)$num;
    }


    /**
     * Returns the uppercase alphabetic version of a number
     *
     * @param int $num The number
     * @return string The alphabetic version of the number
     */
    public static function upperAlpha(int $num): string
    {
        $num--;
        $alpha = '';

        while ($num >= 0) {
            $ascii = ($num % 26) + 65;
            $alpha = chr($ascii) . $alpha;

            $num = (int)($num / 26) - 1;
        }

        return $alpha;
    }

    /**
     * Returns the lowercase alphabetic version of a number
     *
     * @param int $num The number
     * @return string The alphabetic version of the number
     */
    public static function lowerAlpha(int $num): string
    {
        $alpha = static::upperAlpha($num);

        return mb_strtolower($alpha);
    }

    /**
     * Returns the uppercase roman numeral version of a number
     *
     * @param int $num The number
     * @return string The roman numeral version of the number
     */
    public static function upperRoman(int $num): string
    {
        $roman = '';

        $map = [
            'M' => 1000,
            'CM' => 900,
            'D' => 500,
            'CD' => 400,
            'C' => 100,
            'XC' => 90,
            'L' => 50,
            'XL' => 40,
            'X' => 10,
            'IX' => 9,
            'V' => 5,
            'IV' => 4,
            'I' => 1,
        ];

        foreach ($map as $k => $v) {
            while ($num >= $v) {
                $roman .= $k;
                $num -= $v;
            }
        }

        return $roman;
    }

    /**
     * Returns the lowercase roman numeral version of a number
     *
     * @param int $num The number
     * @return string The roman numeral version of the number
     */
    public static function lowerRoman(int $num): string
    {
        $roman = static::upperRoman($num);

        return mb_strtolower($roman);
    }

    /**
     * Returns the numeric value of a variable.
     *
     * If the variable is an object with a __toString() method, the numeric value of its string representation will be
     * returned.
     *
     * @param mixed $var
     * @return float|int|string
     */
    public static function makeNumeric(mixed $var): float|int|string
    {
        if (is_numeric($var)) {
            return $var;
        }

        if (is_object($var) && method_exists($var, '__toString')) {
            return static::makeNumeric($var->__toString());
        }

        return (int)!empty($var);
    }

    /**
     * Returns whether the given value is an int or float, or a string that represents an int or float.
     *
     * @param mixed $value
     * @return bool
     * @since 4.0.5
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Typecast::isIntOrFloat()} instead.
     */
    public static function isIntOrFloat(mixed $value): bool
    {
        return \CraftCms\Cms\Support\Typecast::isIntOrFloat($value);
    }

    /**
     * Returns whether the given number lacks decimal points when typecast to a float.
     *
     * @param float|int|string $value
     * @return bool
     * @throws InvalidArgumentException if $value isn’t numeric
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Typecast::isInt()} instead.
     */
    public static function isInt(float|int|string $value): bool
    {
        try {
            return \CraftCms\Cms\Support\Typecast::isInt($value);
        } catch (RuntimeException $e) {
            throw new InvalidArgumentException($e->getMessage());
        }
    }

    /**
     * Typecasts the given number into an integer or a float.
     *
     * @param float|int|string $value
     * @return int|float
     * @throws InvalidArgumentException if $value isn’t numeric
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Typecast::toIntOrFloat()} instead.
     */
    public static function toIntOrFloat(float|int|string $value): float|int
    {
        try {
            return \CraftCms\Cms\Support\Typecast::toIntOrFloat($value);
        } catch (RuntimeException $e) {
            throw new InvalidArgumentException($e->getMessage());
        }
    }
}
