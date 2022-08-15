<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use yii\base\InvalidArgumentException;

/**
 * Class Number
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
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
            1 => Craft::t('app', 'one'),
            2 => Craft::t('app', 'two'),
            3 => Craft::t('app', 'three'),
            4 => Craft::t('app', 'four'),
            5 => Craft::t('app', 'five'),
            6 => Craft::t('app', 'six'),
            7 => Craft::t('app', 'seven'),
            8 => Craft::t('app', 'eight'),
            9 => Craft::t('app', 'nine'),
        ];

        if (isset($numberWordMap[$num])) {
            return $numberWordMap[$num];
        }

        return (string)$num;
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
     */
    public static function isIntOrFloat(mixed $value): bool
    {
        return (
            is_int($value) ||
            is_float($value) ||
            (is_string($value) && preg_match('/^([1-9]\d*|0|([1-9]\d*|0)?\.\d+)$/', $value))
        );
    }

    /**
     * Returns whether the given number lacks decimal points when typecast to a float.
     *
     * @param float|int|string $value
     * @return bool
     * @throws InvalidArgumentException if $value isn’t numeric
     * @since 4.0.0
     */
    public static function isInt(float|int|string $value): bool
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException('Only numeric values can be typecast to an integer or float.');
        }

        return (float)(int)$value === (float)$value;
    }

    /**
     * Typecasts the given number into an integer or a float.
     *
     * @param float|int|string $value
     * @return int|float
     * @throws InvalidArgumentException if $value isn’t numeric
     * @since 4.0.0
     */
    public static function toIntOrFloat(float|int|string $value): float|int
    {
        return static::isInt($value) ? (int)$value : (float)$value;
    }
}
