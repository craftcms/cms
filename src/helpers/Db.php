<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\base\Serializable;
use craft\db\Connection;
use craft\db\mysql\Schema as MysqlSchema;
use craft\db\Query;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\NotSupportedException;
use yii\db\Exception as DbException;
use yii\db\Schema;

/**
 * Class Db
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Db
{
    const SIMPLE_TYPE_NUMERIC = 'numeric';
    const SIMPLE_TYPE_TEXTUAL = 'textual';

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

    /**
     * Prepares an array or object’s values to be sent to the database.
     *
     * @param mixed $values The values to be prepared
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
     * @param bool $stripSeconds Whether the seconds should be omitted from the formatted string
     * @return string|null The prepped date, or `null` if it could not be prepared
     */
    public static function prepareDateForDb($date, bool $stripSeconds = false)
    {
        $date = DateTimeHelper::toDateTime($date);

        if ($date === false) {
            return null;
        }

        $date = clone $date;
        $date->setTimezone(new \DateTimeZone('UTC'));
        if ($stripSeconds) {
            return $date->format('Y-m-d H:i') . ':00';
        }
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Returns the minimum number allowed for a given column type.
     *
     * @param string $columnType
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
            return Schema::TYPE_DECIMAL . "({$length},{$decimals})";
        }

        // Figure out the smallest possible int column type that will fit our min/max
        foreach (self::$_integerSizeRanges as $type => list($typeMin, $typeMax)) {
            if ($min >= $typeMin && $max <= $typeMax) {
                return $type . "({$length})";
            }
        }

        throw new Exception("No integer column type can contain numbers between {$min} and {$max}");
    }

    /**
     * Returns the maximum number of bytes a given textual column type can hold for a given database.
     *
     * @param string $columnType The textual column type to check
     * @param Connection|null $db The database connection
     * @return int|null|false The storage capacity of the column type in bytes, null if unlimited, or false or it can't be determined.
     */
    public static function getTextualColumnStorageCapacity(string $columnType, Connection $db = null)
    {
        if ($db === null) {
            $db = self::db();
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
     * @param int $contentLength
     * @param Connection|null $db The database connection
     * @return string
     * @throws Exception if using an unsupported connection type
     */
    public static function getTextualColumnTypeByContentLength(int $contentLength, Connection $db = null): string
    {
        if ($db === null) {
            $db = self::db();
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
     * @return string The escaped param value.
     */
    public static function escapeParam(string $value): string
    {
        $value = str_replace([',', '*'], ['\,', '\*'], $value);

        // If the value starts with an operator, escape that too.
        foreach (self::$_operators as $operator) {
            if (stripos($value, $operator) === 0) {
                $value = "\\$value";
                break;
            }
        }

        return $value;
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
     * Values can begin with the operators `'not '`, `'!='`, `'<='`, `'>='`, `'<'`, `'>'`, or `'='`. If they don’t,
     * `'='` will be assumed.
     *
     * Values can also be set to either `':empty:'` or `':notempty:'` if you want to search for empty or non-empty
     * database values. (An “empty” value is either `NULL` or an empty string of text).
     *
     * @param string $column The database column that the param is targeting.
     * @param string|int|array $value The param value(s).
     * @param string $defaultOperator The default operator to apply to the values
     * (can be `not`, `!=`, `<=`, `>=`, `<`, `>`, or `=`)
     * @param bool $caseInsensitive Whether the resulting condition should be case-insensitive
     * @param string|null $columnType The database column type the param is targeting
     * @return mixed
     */
    public static function parseParam(string $column, $value, string $defaultOperator = '=', bool $caseInsensitive = false, string $columnType = null)
    {
        if (is_string($value) && preg_match('/^not\s*$/', $value)) {
            return '';
        }

        $value = self::_toArray($value);

        if (!count($value)) {
            return '';
        }

        $firstVal = strtolower(reset($value));
        $negate = false;

        switch ($firstVal) {
            case 'and':
            case 'or':
                $glue = $firstVal;
                array_shift($value);
                break;
            case 'not':
                $glue = 'and';
                $negate = true;
                array_shift($value);
                break;
            default:
                $glue = 'or';
        }

        $condition = [$glue];
        $isMysql = self::db()->getIsMysql();

        // Only PostgreSQL supports case-sensitive strings
        if ($isMysql) {
            $caseInsensitive = false;
        }

        $caseColumn = $caseInsensitive ? "lower([[{$column}]])" : $column;

        $inVals = [];
        $notInVals = [];

        foreach ($value as $val) {
            self::_normalizeEmptyValue($val);
            $operator = self::_parseParamOperator($val, $defaultOperator, $negate);

            if ($columnType === Schema::TYPE_BOOLEAN) {
                // Convert val to a boolean
                $val = ($val && $val !== ':empty:');
                if ($operator === '!=') {
                    $val = !$val;
                }
                $condition[] = [$column => (bool)$val];
                continue;
            }

            if ($val === ':empty:') {
                // If this is a textual column type, also check for empty strings
                if (
                    ($columnType === null && $isMysql) ||
                    ($columnType !== null && static::isTextualColumnType($columnType))
                ) {
                    $valCondition = [
                        'or',
                        [$column => null],
                        [$column => '']
                    ];
                } else {
                    $valCondition = [$column => null];
                }
                if ($operator === '!=') {
                    $valCondition = ['not', $valCondition];
                }
                $condition[] = $valCondition;
                continue;
            }

            if (is_string($val)) {
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
                    if ($caseInsensitive) {
                        $operator = $operator === '=' ? 'ilike' : 'not ilike';
                    } else {
                        $operator = $operator === '=' ? 'like' : 'not like';
                    }
                    $condition[] = [$operator, $column, $val, false];
                    continue;
                }

                if ($caseInsensitive) {
                    $val = mb_strtolower($val);
                }
            }

            // ['or', 1, 2, 3] => IN (1, 2, 3)
            if ($glue == 'or' && $operator === '=') {
                $inVals[] = $val;
                continue;
            }

            // ['and', '!=1', '!=2', '!=3'] => NOT IN (1, 2, 3)
            if ($glue == 'and' && $operator === '!=') {
                $notInVals[] = $val;
                continue;
            }

            $condition[] = [$operator, $caseColumn, $val];
        }

        if (!empty($inVals)) {
            $condition[] = self::_inCondition($caseColumn, $inVals);
        }

        if (!empty($notInVals)) {
            $condition[] = ['not', self::_inCondition($caseColumn, $notInVals)];
        }

        // Skip the glue if there's only one condition
        if (count($condition) === 2) {
            return $condition[1];
        }

        return $condition;
    }

    /**
     * Parses a query param value for a date/time column, and returns a
     * [[\yii\db\QueryInterface::where()]]-compatible condition.
     *
     * @param string $column The database column that the param is targeting.
     * @param string|array|\DateTime $value The param value
     * @param string $defaultOperator The default operator to apply to the values
     * (can be `not`, `!=`, `<=`, `>=`, `<`, `>`, or `=`)
     * @return mixed
     */
    public static function parseDateParam(string $column, $value, string $defaultOperator = '=')
    {
        $normalizedValues = [];

        $value = self::_toArray($value);

        if (!count($value)) {
            return '';
        }

        if (in_array($value[0], ['and', 'or', 'not'], true)) {
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

            $operator = self::_parseParamOperator($val, $defaultOperator);

            // Assume that date params are set in the system timezone
            $val = DateTimeHelper::toDateTime($val, true);

            $normalizedValues[] = $operator . static::prepareDateForDb($val);
        }

        return static::parseParam($column, $normalizedValues, $defaultOperator, false, Schema::TYPE_DATETIME);
    }

    /**
     * Parses a query param value for a boolean column and returns a
     * [[\yii\db\QueryInterface::where()]]-compatible condition.
     *
     * The follow values are supported:
     *
     * - `true` or `false`
     * - `:empty:` or `:notempty:` (normalizes to `false` and `true`)
     * - `'not x'` or `'!= x'` (normalizes to the opposite of the boolean value of `x`)
     * - Anything else (normalizes to the boolean value of itself)
     *
     * If `$defaultValue` is set, and it matches the normalized `$value`, then the resulting condition will match any
     * `null` values as well.
     *
     * @param string $column The database column that the param is targeting.
     * @param string|bool $value The param value
     * @param bool|null $defaultValue How `null` values should be treated
     * @return mixed
     * @since 3.4.15
     */
    public static function parseBooleanParam(string $column, $value, bool $defaultValue = null)
    {
        self::_normalizeEmptyValue($value);
        $operator = self::_parseParamOperator($value, '=');
        $value = $value === ':empty:' ? false : (bool)$value;
        if ($operator === '!=') {
            $value = !$value;
        }
        $condition = $condition[] = [$column => $value];
        if ($defaultValue === $value) {
            $condition = ['or', $condition, [$column => null]];
        }
        return $condition;
    }

    /**
     * Returns whether a given DB connection’s schema supports a column type.
     *
     * @param string $type
     * @param Connection|null $db
     * @return bool
     * @throws NotSupportedException
     */
    public static function isTypeSupported(string $type, Connection $db = null): bool
    {
        if ($db === null) {
            $db = self::db();
        }

        /** @var \craft\db\mysql\Schema|\craft\db\pgsql\Schema $schema */
        $schema = $db->getSchema();

        return isset($schema->typeMap[$type]);
    }

    /**
     * Creates and executes an `INSERT` SQL statement.
     *
     * The method will properly escape the column names, and bind the values to be inserted.
     *
     * @param string $table The table that new rows will be inserted into
     * @param array $columns The column data (name=>value) to be inserted into the table
     * @param bool $includeAuditColumns Whether to include the data for the audit columns
     * (`dateCreated`, `dateUpdated`, and `uid`)
     * @param Connection|null $db The database connection to use
     * @return int The number of rows affected by the execution
     * @throws DbException if execution failed
     * @since 3.5.0
     */
    public static function insert(string $table, array $columns, bool $includeAuditColumns = true, Connection $db = null): int
    {
        if ($db === null) {
            $db = self::db();
        }

        return $db->createCommand()
            ->insert($table, $columns, $includeAuditColumns)
            ->execute();
    }

    /**
     * Creates and executes a batch `INSERT` SQL statement.
     *
     * The method will properly escape the column names, and bind the values to be inserted.
     *
     * @param string $table The table that new rows will be inserted into
     * @param array $columns The column names
     * @param array $rows The rows to be batch inserted into the table
     * @param bool $includeAuditColumns Whether `dateCreated`, `dateUpdated`, and `uid` values should be added to $columns
     * @param Connection|null $db The database connection to use
     * @return int The number of rows affected by the execution
     * @throws DbException if execution failed
     * @since 3.5.0
     */
    public static function batchInsert(string $table, array $columns, array $rows, bool $includeAuditColumns = true, Connection $db = null): int
    {
        if ($db === null) {
            $db = self::db();
        }

        return $db->createCommand()
            ->batchInsert($table, $columns, $rows, $includeAuditColumns)
            ->execute();
    }

    /**
     * Creates and executes a command to insert rows into a database table if
     * they do not already exist (matching unique constraints),
     * or update them if they do.
     *
     * The method will properly escape the column names, and bind the values to be inserted.
     *
     * @param string $table the table that new rows will be inserted into/updated in
     * @param array|Query $insertColumns the column data (name => value) to be inserted into the table or instance
     * of [[Query]] to perform `INSERT INTO ... SELECT` SQL statement
     * @param array|bool $updateColumns the column data (name => value) to be updated if they already exist
     *
     * - If `true` is passed, the column data will be updated to match the insert column data.
     * - If `false` is passed, no update will be performed if the column data already exists.
     *
     * @param array $params the parameters to be bound to the command
     * @param bool $includeAuditColumns Whether `dateCreated`, `dateUpdated`, and `uid` values should be added to $columns
     * @param Connection|null $db The database connection to use
     * @return int The number of rows affected by the execution
     * @throws DbException if execution failed
     * @since 3.5.0
     */
    public static function upsert(string $table, $insertColumns, $updateColumns = true, array $params = [], bool $includeAuditColumns = true, Connection $db = null): int
    {
        if ($db === null) {
            $db = self::db();
        }

        return $db->createCommand()
            ->upsert($table, $insertColumns, $updateColumns, $params, $includeAuditColumns)
            ->execute();
    }

    /**
     * Creates and executes an `UPDATE` SQL statement.
     *
     * The method will properly escape the column names and bind the values to be updated.
     *
     * @param string $table The table to be updated
     * @param array $columns The column data (name => value) to be updated
     * @param string|array $condition The condition that will be put in the `WHERE` part. Please
     * refer to [[Query::where()]] on how to specify condition
     * @param array $params The parameters to be bound to the command
     * @param bool $includeAuditColumns Whether the `dateUpdated` value should be added to $columns
     * @param Connection|null $db The database connection to use
     * @return int The number of rows affected by the execution
     * @throws DbException if execution failed
     * @since 3.5.0
     */
    public static function update(string $table, array $columns, $condition = '', array $params = [], bool $includeAuditColumns = true, Connection $db = null): int
    {
        if ($db === null) {
            $db = self::db();
        }

        return $db->createCommand()
            ->update($table, $columns, $condition, $params, $includeAuditColumns)
            ->execute();
    }

    /**
     * Creates and executes a SQL statement for replacing some text with other text in a given table column.
     *
     * @param string $table The table to be updated
     * @param string $column The column to be searched
     * @param string $find The text to be searched for
     * @param string $replace The replacement text
     * @param string|array $condition The condition that will be put in the `WHERE` part. Please
     * refer to [[Query::where()]] on how to specify condition.
     * @param array $params The parameters to be bound to the command
     * @param Connection|null $db The database connection to use
     * @return int The number of rows affected by the execution
     * @throws DbException if execution failed
     * @since 3.5.0
     */
    public static function replace(string $table, string $column, string $find, string $replace, $condition = '', array $params = [], Connection $db = null): int
    {
        if ($db === null) {
            $db = self::db();
        }

        return $db->createCommand()
            ->replace($table, $column, $find, $replace, $condition, $params)
            ->execute();
    }

    /**
     * Creates and executes a `DELETE` SQL statement.
     *
     * @param string $table the table where the data will be deleted from
     * @param array|string $condition the conditions that will be put in the `WHERE` part. Please
     * refer to [[Query::where()]] on how to specify conditions.
     * @param array $params the parameters to be bound to the query.
     * @param Connection|null $db The database connection to use
     * @return int The number of rows affected by the execution
     * @throws DbException if execution failed
     * @since 3.5.0
     */
    public static function delete(string $table, $condition = '', array $params = [], Connection $db = null)
    {
        if ($db === null) {
            $db = self::db();
        }

        return $db->createCommand()
            ->delete($table, $condition, $params)
            ->execute();
    }

    /**
     * Creates and executes a `DELETE` SQL statement, but only if there are any rows to delete, avoiding deadlock issues
     * when deleting data from large tables.
     *
     * @param string $table the table where the data will be deleted from
     * @param string|array $condition the condition that will be put in the `WHERE` part. Please
     * refer to [[Query::where()]] on how to specify condition.
     * @param array $params the parameters to be bound to the command
     * @param Connection|null $db The database connection to use
     * @return int number of rows affected by the execution
     * @throws DbException execution failed
     * @since 3.0.12
     */
    public static function deleteIfExists(string $table, $condition = '', array $params = [], Connection $db = null): int
    {
        if ($db === null) {
            $db = self::db();
        }

        $exists = (new Query())
            ->from($table)
            ->where($condition, $params)
            ->exists($db);

        return $exists ? static::delete($table, $condition, $params, $db) : 0;
    }

    /**
     * Returns the `id` of a row in the given table by its `uid`.
     *
     * @param string $table
     * @param string $uid
     * @param Connection|null $db The database connection to use
     * @return int|null
     * @since 3.1.0
     */
    public static function idByUid(string $table, string $uid, Connection $db = null)
    {
        if ($db === null) {
            $db = self::db();
        }

        $id = (new Query())
            ->select(['id'])
            ->from([$table])
            ->where(['uid' => $uid])
            ->scalar($db);

        return (int)$id ?: null;
    }

    /**
     * Returns an array `uid`:`id` pairs from a given table, by their `uid`s.
     *
     * @param string $table
     * @param string[] $uids
     * @param Connection|null $db The database connection to use
     * @return string[]
     * @since 3.1.0
     */
    public static function idsByUids(string $table, array $uids, Connection $db = null): array
    {
        if ($db === null) {
            $db = self::db();
        }

        return (new Query())
            ->select(['uid', 'id'])
            ->from([$table])
            ->where(['uid' => $uids])
            ->pairs($db);
    }

    /**
     * Returns the `uid` of a row in the given table by its `id`.
     *
     * @param string $table
     * @param int $id
     * @param Connection|null $db The database connection to use
     * @return string|null
     * @since 3.1.0
     */
    public static function uidById(string $table, int $id, Connection $db = null)
    {
        if ($db === null) {
            $db = self::db();
        }

        $uid = (new Query())
            ->select(['uid'])
            ->from([$table])
            ->where(['id' => $id])
            ->scalar($db);

        return $uid ?: null;
    }

    /**
     * Returns an array `id`:`uid` pairs from a given table, by their `id`s.
     *
     * @param string $table
     * @param int[] $ids
     * @param Connection|null $db The database connection to use
     * @return string[]
     * @since 3.1.0
     */
    public static function uidsByIds(string $table, array $ids, Connection $db = null): array
    {
        if ($db === null) {
            $db = self::db();
        }

        return (new Query())
            ->select(['id', 'uid'])
            ->from([$table])
            ->where(['id' => $ids])
            ->pairs($db);
    }

    /**
     * Parses a DSN string and returns an array with the `driver` and any driver params, or just a single key.
     *
     * @param string $dsn
     * @param string|null $key The key that is needed from the DSN. If this is
     * @return array|string|false The full array, or the specific key value, or `false` if `$key` is a param that
     * doesn’t exist in the DSN string.
     * @throws InvalidArgumentException if $dsn is invalid
     * @since 3.4.0
     */
    public static function parseDsn(string $dsn, string $key = null)
    {
        if (($pos = strpos($dsn, ':')) === false) {
            throw new InvalidArgumentException('Invalid DSN: ' . $dsn);
        }

        $driver = strtolower(substr($dsn, 0, $pos));
        if ($key === 'driver') {
            return $driver;
        }
        if ($key === null) {
            $parsed = [
                'driver' => $driver,
            ];
        }

        $params = substr($dsn, $pos + 1);
        foreach (ArrayHelper::filterEmptyStringsFromArray(explode(';', $params)) as $param) {
            list($n, $v) = array_pad(explode('=', $param, 2), 2, '');
            if ($key === $n) {
                return $v;
            }
            if ($key === null) {
                $parsed[$n] = $v;
            }
        }
        if ($key === null) {
            return $parsed;
        }
        return false;
    }

    /**
     * Generates a DB config from a database connection URL.
     *
     * This can be used from `config/db.php`:
     * ---
     * ```php
     * $url = craft\helpers\App::env('DB_URL');
     * return craft\helpers\Db::url2config($url);
     * ```
     *
     * @param string $url
     * @return array
     * @since 3.4.0
     */
    public static function url2config(string $url): array
    {
        $parsed = parse_url($url);

        if (!isset($parsed['scheme'])) {
            throw new InvalidArgumentException('Invalid URL: ' . $url);
        }

        $config = [];

        // user & password
        if (isset($parsed['user'])) {
            $config['user'] = $parsed['user'];
        }
        if (isset($parsed['pass'])) {
            $config['password'] = $parsed['pass'];
        }

        // URL scheme => driver
        if (in_array(strtolower($parsed['scheme']), ['pgsql', 'postgres', 'postgresql'], true)) {
            $driver = Connection::DRIVER_PGSQL;
        } else {
            $driver = Connection::DRIVER_MYSQL;
        }

        // DSN params
        $checkParams = [
            'host' => 'host',
            'port' => 'port',
            'path' => 'dbname',
        ];
        $dsnParams = [];
        foreach ($checkParams as $urlParam => $dsnParam) {
            if (isset($parsed[$urlParam])) {
                $dsnParams[] = $dsnParam . '=' . trim($parsed[$urlParam], '/');
            }
        }

        $config['dsn'] = "{$driver}:" . implode(';', $dsnParams);

        return $config;
    }

    /**
     * Returns the main application's DB connection.
     *
     * @return Connection
     */
    private static function db(): Connection
    {
        return self::$_db ?? (self::$_db = Craft::$app->getDb());
    }

    /**
     * @var Connection|null;
     */
    private static $_db;

    /**
     * Resets the memoized database connection.
     *
     * @since 3.5.12.1
     */
    public static function reset()
    {
        if (self::$_db) {
            self::$_db->close();
        }
        self::$_db = null;
    }

    /**
     * Converts a given param value to an array.
     *
     * @param mixed $value
     * @return array
     */
    private static function _toArray($value): array
    {
        if ($value === null) {
            return [];
        }

        if ($value instanceof \DateTime) {
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
     * Normalizes “empty” values.
     *
     * @param mixed &$value The param value.
     */
    private static function _normalizeEmptyValue(&$value)
    {
        if ($value === null) {
            $value = ':empty:';
            return;
        }

        if (!is_string($value) || $value === ':empty:' || $value === 'not :empty:') {
            return;
        }

        $lower = strtolower($value);

        if ($lower === ':empty:') {
            $value = ':empty:';
        } else if ($lower === ':notempty:' || $lower === 'not :empty:') {
            $value = 'not :empty:';
        }
    }

    /**
     * Extracts the operator from a DB param and returns it.
     *
     * @param mixed &$value Te param value.
     * @param string $default The default operator to use
     * @param bool $negate Whether to reverse whatever the selected operator is
     * @return string The operator ('!=', '<=', '>=', '<', '>', or '=')
     */
    private static function _parseParamOperator(&$value, string $default, bool $negate = false): string
    {
        $op = null;

        if (is_string($value)) {
            foreach (self::$_operators as $operator) {
                // Does the value start with this operator?
                if (stripos($value, $operator) === 0) {
                    $value = mb_substr($value, strlen($operator));
                    $op = $operator === 'not ' ? '!=' : $operator;
                    break;
                }
                // Does it start with this operator, but escaped?
                if (stripos($value, "\\$operator") === 0) {
                    $value = substr($value, 1);
                    break;
                }
            }
        }

        if ($op === null) {
            $op = $default === 'not' || $default === 'not ' ? '!=' : $default;
        }

        if ($negate) {
            switch ($op) {
                case '!=':
                    return '=';
                case '<=':
                    return '>';
                case '>=':
                    return '<';
                case '<':
                    return '>=';
                case '>':
                    return '<=';
                case '=':
                    return '!=';
            }
        }

        return $op;
    }

    /**
     * @param string $column
     * @param array $values
     * @return array
     */
    private static function _inCondition(string $column, array $values): array
    {
        return [
            $column => count($values) === 1 ? $values[0] : $values,
        ];
    }
}
