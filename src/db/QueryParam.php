<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use craft\helpers\ArrayHelper;
use DateTime;

/**
 * Class QueryParam
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
final class QueryParam
{
    public const AND = 'and';
    public const OR = 'or';
    public const NOT = 'not';

    /**
     * Parses a given query param, separating it into an array of values and the logical operator (`and`, `or`, `not`).
     *
     * @param mixed $value
     * @return self
     */
    public static function parse(mixed $value): self
    {
        $param = new self();

        if (is_string($value) && preg_match('/^not\s*$/', $value)) {
            return $param;
        }

        $values = self::toArray($value);

        if (empty($values)) {
            return $param;
        }

        $param->operator = self::extractOperator($values) ?? self::OR;
        $param->values = $values;
        return $param;
    }

    public static function toArray(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        if ($value instanceof DateTime) {
            return [$value];
        }

        if (is_string($value)) {
            // Split it on the non-escaped commas
            $value = preg_split('/(?<!\\\),/', $value);

            // Remove any of the backslashes used to escape the commas
            foreach ($value as $key => $val) {
                // Remove leading/trailing whitespace
                $val = trim($val);

                // Remove any backslashes used to escape commas
                $val = str_replace('\,', ',', $val);

                $value[$key] = $val;
            }

            // Split the first value if it begins with an operator
            $firstValue = $value[0];
            if (str_contains($firstValue, ' ')) {
                $parts = explode(' ', $firstValue);
                $operator = self::extractOperator($parts);
                if ($operator !== null) {
                    $value[0] = implode(' ', $parts);
                    array_unshift($value, $operator);
                }
            }

            // Remove any empty elements and reset the keys
            return array_values(ArrayHelper::filterEmptyStringsFromArray($value));
        }

        return ArrayHelper::toArray($value);
    }

    /**
     * Extracts the logic operator (`and`, `or`, or `not`) from the beginning of an array.
     *
     * @param array $values
     * @return string|null
     * @since 3.7.40
     */
    public static function extractOperator(array &$values): ?string
    {
        $firstVal = reset($values);

        if (!is_string($firstVal)) {
            return null;
        }

        $firstVal = strtolower($firstVal);

        if (!in_array($firstVal, [self::AND, self::OR, self::NOT], true)) {
            return null;
        }

        array_shift($values);
        return $firstVal;
    }

    /**
     * @var array The param values.
     */
    public array $values = [];

    /**
     * @var string The logical operator that the values should be combined with (`and`, `or`, or `not`).
     */
    public string $operator = self::OR;
}
