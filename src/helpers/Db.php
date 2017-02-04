<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\helpers;

use Craft;
use craft\base\Serializable;
use craft\db\Connection;
use craft\db\mysql\Schema as MysqlSchema;
use yii\base\Exception;
use yii\db\Schema;

/**
 * Class Db
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Db
{
    // Constants
    // =========================================================================

    const TYPE_NUMERIC = 'numeric';

    // Properties
    // =========================================================================

    /**
     * @var array
     */
    private static $_operators = ['not ', '!=', '<=', '>=', '<', '>', '='];

    /**
     * @var array Types of integer columns and how many bytes they can store
     */
    private static $_integerSizeRanges = [
        Schema::TYPE_SMALLINT => [-32768, 32767],
        Schema::TYPE_INTEGER => [-2147483648, 2147483647],
        Schema::TYPE_BIGINT => [-9223372036854775808, 9223372036854775807],
    ];

    /**
     * @var array Column type => simplified type mapping
     */
    private static $_simplifiedColumnTypes = [
        Schema::TYPE_PK => self::TYPE_NUMERIC,
        Schema::TYPE_UPK => self::TYPE_NUMERIC,
        Schema::TYPE_BIGPK => self::TYPE_NUMERIC,
        Schema::TYPE_UBIGPK => self::TYPE_NUMERIC,
        Schema::TYPE_CHAR => Schema::TYPE_STRING,
        Schema::TYPE_STRING => Schema::TYPE_STRING,
        Schema::TYPE_TEXT => Schema::TYPE_STRING,
        Schema::TYPE_SMALLINT => self::TYPE_NUMERIC,
        Schema::TYPE_INTEGER => self::TYPE_NUMERIC,
        Schema::TYPE_BIGINT => self::TYPE_NUMERIC,
        Schema::TYPE_FLOAT => self::TYPE_NUMERIC,
        Schema::TYPE_DOUBLE => self::TYPE_NUMERIC,
        Schema::TYPE_DECIMAL => self::TYPE_NUMERIC,
        Schema::TYPE_BOOLEAN => self::TYPE_NUMERIC,
        MysqlSchema::TYPE_TINYTEXT => Schema::TYPE_STRING,
        MysqlSchema::TYPE_MEDIUMTEXT => Schema::TYPE_STRING,
        MysqlSchema::TYPE_LONGTEXT => Schema::TYPE_STRING,
        MysqlSchema::TYPE_ENUM => Schema::TYPE_STRING,
    ];

    // Public Methods
    // =========================================================================

    /**
     * Prepares an array or object’s values to be sent to the database.
     *
     * @param mixed $values The values to be prepared
     *
     * @return array The prepared values
     */
    public static function prepareValuesForDb($values): array
    {
        // Normalize to an array
        $values = ArrayHelper::toArray($values);

        foreach ($values as $key => $value) {
            $values[$key] = static::prepareValueForDb($value);
        }

        return $values;
    }

    /**
     * Prepares a value to be sent to the database.
     *
     * @param mixed $value The value to be prepared
     *
     * @return mixed The prepped value
     */
    public static function prepareValueForDb($value)
    {
        // If the object explicitly defines its savable value, use that
        if ($value instanceof Serializable) {
            return $value->serialize();
        }

        // Only DateTime objects and ISO-8601 strings should automatically be detected as dates
        if ($value instanceof \DateTime || DateTimeHelper::isIso8601($value)) {
            return static::prepareDateForDb($value);
        }

        // If it's an object or array, just JSON-encode it
        if (is_object($value) || is_array($value)) {
            return Json::encode($value);
        }

        return $value;
    }

    /**
     * Prepares a date to be sent to the database.
     *
     * @param mixed $date The date to be prepared
     *
     * @return string|null The prepped date, or `null` if it could not be prepared
     */
    public static function prepareDateForDb($date)
    {
        $date = DateTimeHelper::toDateTime($date);

        if ($date !== false) {
            $timezone = $date->getTimezone();
            $date->setTimezone(new \DateTimeZone('UTC'));
            $formattedDate = $date->format('Y-m-d H:i:s');
            $date->setTimezone($timezone);

            return $formattedDate;
        }

        return null;
    }

    /**
     * Returns a number column type, taking the min, max, and number of decimal points into account.
     *
     * @param int|null $min
     * @param int|null $max
     * @param int|null $decimals
     *
     * @return string
     * @throws Exception if no column types can contain this
     */
    public static function getNumericalColumnType(int $min = null, int $max = null, int $decimals = null): string
    {
        // Normalize the arguments
        if (!is_numeric($min)) {
            $min = self::$_integerSizeRanges[Schema::TYPE_INTEGER][0];
        }

        if (!is_numeric($max)) {
            $max = self::$_integerSizeRanges[Schema::TYPE_INTEGER][1];
        }

        $decimals = is_numeric($decimals) && $decimals > 0 ? (int)$decimals : 0;

        // Figure out the max length
        $maxAbsSize = (int)max(abs($min), abs($max));
        $length = ($maxAbsSize ? mb_strlen($maxAbsSize) : 0) + $decimals;

        // Decimal or int?
        if ($decimals > 0) {
            return Schema::TYPE_DECIMAL."({$length},{$decimals})";
        }

        // Figure out the smallest possible int column type that will fit our min/max
        foreach (self::$_integerSizeRanges as $type => list($typeMin, $typeMax)) {
            if ($min >= $typeMin && $max <= $typeMax) {
                return $type."({$length})";
            }
        }

        throw new Exception("No integer column type can contain numbers between {$min} and {$max}");
    }

    /**
     * Returns the maximum number of bytes a given textual column type can hold for a given database.
     *
     * @param string          $columnType The textual column type to check
     * @param Connection|null $db         The database connection
     *
     * @return int|null The storage capacity of the column type in bytes. If unlimited, null is returned.
     * @throws Exception if given an unknown column type/database combination
     */
    public static function getTextualColumnStorageCapacity(string $columnType, Connection $db = null)
    {
        if ($db === null) {
            $db = Craft::$app->getDb();
        }

        switch ($db->getDriverName()) {
            case Connection::DRIVER_MYSQL:
                switch ($columnType) {
                    case MysqlSchema::TYPE_TINYTEXT:
                        // 255 bytes
                        return 255;
                    case Schema::TYPE_TEXT:
                        // 65k
                        return 65535;
                    case MysqlSchema::TYPE_MEDIUMTEXT:
                        // 16MB
                        return 16777215;
                    case MysqlSchema::TYPE_LONGTEXT:
                        // 4GB
                        return 4294967295;
                    default:
                        throw new Exception('Unknown textual column type: '.$columnType);
                }
            case Connection::DRIVER_PGSQL:
                return null;
            default:
                throw new Exception('Unsupported connection type: '.$db->getDriverName());
        }
    }

    /**
     * Given a length of a piece of content, returns the underlying database column type to use for saving.
     *
     * @param int             $contentLength
     * @param Connection|null $db The database connection
     *
     * @return string
     * @throws Exception if using an unsupported connection type
     */
    public static function getTextualColumnTypeByContentLength(int $contentLength, Connection $db = null): string
    {
        if ($db === null) {
            $db = Craft::$app->getDb();
        }

        switch ($db->getDriverName()) {
            case Connection::DRIVER_MYSQL:
                if ($contentLength <= static::getTextualColumnStorageCapacity(MysqlSchema::TYPE_TINYTEXT)) {
                    return Schema::TYPE_STRING;
                }

                if ($contentLength <= static::getTextualColumnStorageCapacity(Schema::TYPE_TEXT)) {
                    return Schema::TYPE_TEXT;
                }

                if ($contentLength <= static::getTextualColumnStorageCapacity(MysqlSchema::TYPE_MEDIUMTEXT)) {
                    // Yii doesn't support 'mediumtext' so we use our own.
                    return MysqlSchema::TYPE_MEDIUMTEXT;
                }

                // Yii doesn't support 'longtext' so we use our own.
                return MysqlSchema::TYPE_LONGTEXT;
            case Connection::DRIVER_PGSQL:
                return Schema::TYPE_TEXT;
            default:
                throw new Exception('Unsupported connection type: '.$db->getDriverName());
        }
    }

    /**
     * Returns a simplified version of a given column type.
     *
     * @param string $type
     *
     * @return string
     */
    public static function getSimplifiedColumnType($type)
    {
        if (!preg_match('/^\w+/', $type, $matches)) {
            return $type;
        }

        return self::$_simplifiedColumnTypes[$matches[0]] ?? $matches[0];
    }

    /**
     * Returns whether two column type definitions are relatively compatible with each other.
     *
     * @param string $typeA
     * @param string $typeB
     *
     * @return bool
     */
    public static function areColumnTypesCompatible($typeA, $typeB)
    {
        return static::getSimplifiedColumnType($typeA) === static::getSimplifiedColumnType($typeB);

    }

    /**
     * Escapes commas and asterisks in a string so they are not treated as special characters in
     * [[Db::parseParam()]].
     *
     * @param string $value The param value.
     *
     * @return string The escaped param value.
     */
    public static function escapeParam(string $value): string
    {
        return str_replace([',', '*'], ['\,', '\*'], $value);
    }

    /**
     * Parses a query param value and returns a [[\yii\db\QueryInterface::where()]]-compatible condition.
     *
     * If the `$value` is a string, it will automatically be converted to an array, split on any commas within the
     * string (via [[ArrayHelper::toArray()]]). If that is not desired behavior, you can escape the comma
     * with a backslash before it.
     *
     * The first value can be set to either `'and'` or `'or'` to define whether *all* of the values must match, or
     * *any*. If it’s neither `'and'` nor `'or'`, then `'or'` will be assumed.
     *
     * Values can begin with the operators `'not '`, `'!='`, `'<='`, `'>='`, `'<'`, `'>'`, or `'='`. If they don’t,
     * `'='` will be assumed.
     *
     * Values can also be set to either `':empty:'` or `':notempty:'` if you want to search for empty or non-empty
     * database values. (An “empty” value is either NULL or an empty string of text).
     *
     * @param string           $column The database column that the param is targeting.
     * @param string|int|array $value  The param value(s).
     *
     * @return mixed
     */
    public static function parseParam(string $column, $value)
    {
        // Need to do a strict check here in case $value = true
        if ($value === 'not ') {
            return '';
        }

        $value = ArrayHelper::toArray($value);

        if (!count($value)) {
            return '';
        }

        $firstVal = StringHelper::toLowerCase(reset($value));

        if ($firstVal === 'and' || $firstVal === 'or') {
            $conditionOperator = array_shift($value);
        } else {
            $conditionOperator = 'or';
        }

        $condition = [$conditionOperator];
        $driver = Craft::$app->getDb()->getDriverName();

        foreach ($value as $val) {
            self::_normalizeEmptyValue($val);
            $operator = self::_parseParamOperator($val);

            if (StringHelper::toLowerCase($val) === ':empty:') {
                if ($operator === '=') {
                    if ($driver === Connection::DRIVER_MYSQL) {
                        $condition[] = [
                            'or',
                            [$column => null],
                            [$column => '']
                        ];
                    } else {
                        // Because PostgreSQL chokes if you do a string check on an int column
                        $condition[] = [$column => null];
                    }
                } else {
                    if ($driver === Connection::DRIVER_MYSQL) {
                        $condition[] = [
                            'not',
                            [
                                'or',
                                [$column => null],
                                [$column => '']
                            ]
                        ];
                    } else {
                        // Because PostgreSQL chokes if you do a string check on an int column
                        $condition[] = [
                            'not',
                            [
                                [$column => null],
                            ]
                        ];
                    }
                }
            } else {
                // Trim any whitespace from the value
                $val = trim($val);

                // This could be a LIKE condition
                if ($operator === '=' || $operator === '!=') {
                    $val = preg_replace('/^\*|(?<!\\\)\*$/', '%', $val, -1, $count);
                    $like = (bool)$count;
                } else {
                    $like = false;
                }

                // Unescape any asterisks
                $val = str_replace('\*', '*', $val);

                if ($like) {
                    $condition[] = [
                        $operator === '=' ? 'like' : 'not like',
                        $column,
                        $val,
                        false
                    ];
                } else {
                    $condition[] = [$operator, $column, $val];
                }
            }
        }

        return $condition;
    }

    /**
     * Normalizes date params and then sends them off to parseParam().
     *
     * @param string                 $column
     * @param string|array|\DateTime $value
     *
     * @return mixed
     */
    public static function parseDateParam(string $column, $value)
    {
        $normalizedValues = [];

        $value = ArrayHelper::toArray($value);

        if (!count($value)) {
            return '';
        }

        if ($value[0] === 'and' || $value[0] === 'or') {
            $normalizedValues[] = $value[0];
            array_shift($value);
        }

        foreach ($value as $val) {
            // Is this an empty value?
            self::_normalizeEmptyValue($val);

            if ($val === ':empty:' || $val === 'not :empty:') {
                $normalizedValues[] = $val;

                // Sneak out early
                continue;
            }

            if (is_string($val)) {
                $operator = self::_parseParamOperator($val);
            } else {
                $operator = '=';
            }

            // Assume that date params are set in the system timezone
            $val = DateTimeHelper::toDateTime($val, true);

            $normalizedValues[] = $operator.static::prepareDateForDb($val);
        }

        return static::parseParam($column, $normalizedValues);
    }

    /**
     * Returns whether a given DB connection’s schema supports a column type.
     *
     * @param string          $type
     * @param Connection|null $db
     *
     * @return bool
     */
    public static function isTypeSupported(string $type, Connection $db = null): bool
    {
        if ($db === null) {
            $db = Craft::$app->getDb();
        }

        /** @var \craft\db\mysql\Schema|\craft\db\pgsql\Schema $schema */
        $schema = $db->getSchema();

        return isset($schema->typeMap[$type]);
    }

    // Private Methods
    // =========================================================================

    /**
     * Normalizes “empty” values.
     *
     * @param string &$value The param value.
     */
    private static function _normalizeEmptyValue(string &$value)
    {
        if ($value === null) {
            $value = ':empty:';
        } else if (StringHelper::toLowerCase($value) === ':notempty:') {
            $value = 'not :empty:';
        }
    }

    /**
     * Extracts the operator from a DB param and returns it.
     *
     * @param string &$value Te param value.
     *
     * @return string The operator.
     */
    private static function _parseParamOperator(string &$value): string
    {
        foreach (self::$_operators as $testOperator) {
            // Does the value start with this operator?
            if (strpos(StringHelper::toLowerCase($value), $testOperator) === 0) {
                $value = mb_substr($value, strlen($testOperator));

                if ($testOperator === 'not ') {
                    return '!=';
                }

                return $testOperator;
            }
        }

        return '=';
    }
}
