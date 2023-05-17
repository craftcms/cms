<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use DateTime;

/**
 * Class QueryParam
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class QueryParam
{
    public const GLUE_AND = 'and';
    public const GLUE_OR = 'or';
    public const GLUE_NOT = 'not';

    public static function parse(mixed $value): self
    {
        $param = new self();

        if (is_string($value) && preg_match('/^not\s*$/', $value)) {
            return $param;
        }

        $value = self::toArray($value);

        if (empty($value)) {
            return $param;
        }

        $param->glue = Db::extractGlue($value) ?? self::GLUE_OR;
        return $param;
    }

    private static function toArray(mixed $value): array
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

            // Remove any empty elements and reset the keys
            return array_values(ArrayHelper::filterEmptyStringsFromArray($value));
        }

        return ArrayHelper::toArray($value);
    }

    /**
     * @var string[]
     */
    public array $values = [];
    /**
     * @var string `and` or `or`
     */
    public string $glue = self::GLUE_OR;
}
