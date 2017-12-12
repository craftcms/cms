<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
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

    const SIMPLE_TYPE_NUMERIC = 'numeric';
    const SIMPLE_TYPE_TEXTUAL = 'textual';

    // Properties
    // =========================================================================

    /**
     * @var array
     */
    private static $_operators = ['not ', '!=', '<=', '>=', '<', '>', '='];

    /**
     * @var string[] Numeric column types
     */
    private static $_numericColumnTypes = [
        Schema::TYPE_SMALLINT,
        Schema::TYPE_INTEGER,
        Schema::TYPE_BIGINT,
        Schema::TYPE_FLOAT,
        Schema::TYPE_DOUBLE,
        Schema::TYPE_DECIMAL,
        Schema::TYPE_BOOLEAN,
    ];

    /**
     * @var string[] Textual column types
     */
    private static $_textualColumnTypes = [
        Schema::TYPE_CHAR,
        Schema::TYPE_STRING,
        Schema::TYPE_TEXT,

        // MySQL-specific ones:
        MysqlSchema::TYPE_TINYTEXT,
        MysqlSchema::TYPE_MEDIUMTEXT,
        MysqlSchema::TYPE_LONGTEXT,
        MysqlSchema::TYPE_ENUM,
    ];

    /**
     * @var array Types of integer columns and how many bytes they can store
     */
    private static $_integerSizeRanges = [
        Schema::TYPE_SMALLINT => [-32768, 32767],
        Schema::TYPE_INTEGER => [-2147483648, 2147483647],
        Schema::TYPE_BIGINT => [-9223372036854775808, 9223372036854775807],
    ];

    /**
     * @var array Types of MySQL textual columns and how many bytes they can store
     */
    private static $_mysqlTextSizes = [
        MysqlSchema::TYPE_TINYTEXT => 255,
        Schema::TYPE_TEXT => 65535,
        MysqlSchema::TYPE_MEDIUMTEXT => 16777215,
        MysqlSchema::TYPE_LONGTEXT => 4294967295,
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
     * Returns the minimum number allowed for a given column type.
     *
     * @param string $columnType
     *
     * @return int|false The min allowed number, or false if it can't be determined
     */
    public static function getMinAllowedValueForNumericColumn($columnType)
    {
        $shortColumnType = self::parseColumnType($columnType);

        if (isset(self::$_integerSizeRanges[$shortColumnType])) {
            return self::$_integerSizeRanges[$shortColumnType][0];
        }

        // ¯\_(ツ)_/¯
        return false;
    }

    /**
     * Returns the maximum number allowed for a given column type.
     *
     * @param string $columnType
     *
     * @return int|false The max allowed number, or false if it can't be determined
     */
    public static function getMaxAllowedValueForNumericColumn($columnType)
    {
        $shortColumnType = self::parseColumnType($columnType);

        if (isset(self::$_integerSizeRanges[$shortColumnType])) {
            return self::$_integerSizeRanges[$shortColumnType][1];
        }

        // ¯\_(ツ)_/¯
        return false;
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
     * @return int|null|false The storage capacity of the column type in bytes, null if unlimited, or false or it can't be determined.
     */
    public static function getTextualColumnStorageCapacity(string $columnType, Connection $db = null)
    {
        if ($db === null) {
            $db = Craft::$app->getDb();
        }

        $shortColumnType = self::parseColumnType($columnType);

        // CHAR and STRING depend on the defined length
        if ($shortColumnType === Schema::TYPE_CHAR || $shortColumnType === Schema::TYPE_STRING) {
            // Convert to the physical column type for the DB driver, to get the default length mixed in
            $physicalColumnType = $db->getQueryBuilder()->getColumnType($columnType);
            if (($length = self::parseColumnLength($physicalColumnType)) !== null) {
                return $length;
            }

            // ¯\_(ツ)_/¯
            return false;
        }

        if ($db->getIsMysql()) {
            if (isset(self::$_mysqlTextSizes[$shortColumnType])) {
                return self::$_mysqlTextSizes[$shortColumnType];
            }

            // ENUM depends on the options
            if ($shortColumnType === MysqlSchema::TYPE_ENUM) {
                return null;
            }

            // ¯\_(ツ)_/¯
            return false;
        }

        // PostgreSQL doesn't impose a limit for text fields
        if ($shortColumnType === Schema::TYPE_TEXT) {
            // TEXT columns are variable-length in 'grez
            return null;
        }

        // ¯\_(ツ)_/¯
        return false;
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

        if ($db->getIsMysql()) {
            // MySQL supports a bunch of non-standard text types
            if ($contentLength <= self::$_mysqlTextSizes[MysqlSchema::TYPE_TINYTEXT]) {
                return Schema::TYPE_STRING;
            }

            if ($contentLength <= self::$_mysqlTextSizes[Schema::TYPE_TEXT]) {
                return Schema::TYPE_TEXT;
            }

            if ($contentLength <= self::$_mysqlTextSizes[MysqlSchema::TYPE_MEDIUMTEXT]) {
                return MysqlSchema::TYPE_MEDIUMTEXT;
            }

            return MysqlSchema::TYPE_LONGTEXT;
        }

        return Schema::TYPE_TEXT;
    }

    /**
     * Parses a column type definition and returns just the column type, if it can be determined.
     *
     * @param string $columnType
     *
     * @return string|null
     */
    public static function parseColumnType($columnType)
    {
        if (!preg_match('/^\w+/', $columnType, $matches)) {
            return null;
        }

        return strtolower($matches[0]);
    }

    /**
     * Parses a column type definition and returns just the column length/size.
     *
     * @param string $columnType
     *
     * @return int|null
     */
    public static function parseColumnLength($columnType)
    {
        if (!preg_match('/^\w+\((\d+)\)/', $columnType, $matches)) {
            return null;
        }

        return (int)$matches[1];
    }

    /**
     * Returns a simplified version of a given column type.
     *
     * @param string $columnType
     *
     * @return string
     */
    public static function getSimplifiedColumnType($columnType)
    {
        if (($shortColumnType = self::parseColumnType($columnType)) === null) {
            return $columnType;
        }

        // Numeric?
        if (in_array($shortColumnType, self::$_numericColumnTypes, true)) {
            return self::SIMPLE_TYPE_NUMERIC;
        }

        // Textual?
        if (in_array($shortColumnType, self::$_textualColumnTypes, true)) {
            return self::SIMPLE_TYPE_TEXTUAL;
        }

        return $shortColumnType;
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
     * Returns whether the given column type is numeric.
     *
     * @param string $columnType
     *
     * @return bool
     */
    public static function isNumericColumnType(string $columnType): bool
    {
        return in_array(self::parseColumnLength($columnType), self::$_numericColumnTypes, true);
    }

    /**
     * Returns whether the given column type is textual.
     *
     * @param string $columnType
     *
     * @return bool
     */
    public static function isTextualColumnType(string $columnType): bool
    {
        return in_array(self::parseColumnLength($columnType), self::$_textualColumnTypes, true);
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

        $value = self::_toArray($value);

        if (!count($value)) {
            return '';
        }

        $firstVal = StringHelper::toLowerCase(reset($value));

        if ($firstVal === 'and' || $firstVal === 'or') {
            $glue = array_shift($value);
        } else {
            $glue = 'or';
        }

        $condition = [$glue];
        $isMysql = Craft::$app->getDb()->getIsMysql();

        foreach ($value as $val) {
            self::_normalizeEmptyValue($val);
            $operator = self::_parseParamOperator($val);

            if (StringHelper::toLowerCase($val) === ':empty:') {
                if ($operator === '=') {
                    if ($isMysql) {
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
                    if ($isMysql) {
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
                                $column => null,
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

        $value = self::_toArray($value);

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
     * Converts a given param value to an array.
     *
     * @param mixed $value
     *
     * @return array
     */
    private static function _toArray($value): array
    {
        if ($value === null) {
            return [];
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
            $value = array_merge(array_filter($value));

            return $value;
        }

        return ArrayHelper::toArray($value);
    }

    /**
     * Normalizes “empty” values.
     *
     * @param string|null &$value The param value.
     */
    private static function _normalizeEmptyValue(string &$value = null)
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
