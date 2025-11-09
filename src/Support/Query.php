<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use craft\db\QueryParam;
use DateTimeInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;
use Tpetry\QueryExpressions\Function\String\Lower;

final class Query
{
    const string TYPE_CHAR = 'char';

    const string TYPE_ENUM = 'enum';

    const string TYPE_STRING = 'string';

    const string TYPE_TINYTEXT = 'tinytext';

    const string TYPE_MEDIUMTEXT = 'mediumtext';

    const string TYPE_LONGTEXT = 'longtext';

    const string TYPE_TEXT = 'text';

    const string TYPE_TINYINT = 'tinyint';

    const string TYPE_SMALLINT = 'smallint';

    const string TYPE_INTEGER = 'integer';

    const string TYPE_BIGINT = 'bigint';

    const string TYPE_FLOAT = 'float';

    const string TYPE_DOUBLE = 'double';

    const string TYPE_DECIMAL = 'decimal';

    const string TYPE_DATETIME = 'datetime';

    const string TYPE_TIMESTAMP = 'timestamp';

    const string TYPE_TIME = 'time';

    const string TYPE_DATE = 'date';

    const string TYPE_BINARY = 'binary';

    const string TYPE_BOOLEAN = 'boolean';

    const string TYPE_MONEY = 'money';

    const string TYPE_JSON = 'json';

    /**
     * @var string[] Numeric column types
     */
    private static array $numericColumnTypes = [
        self::TYPE_TINYINT,
        self::TYPE_SMALLINT,
        self::TYPE_INTEGER,
        self::TYPE_BIGINT,
        self::TYPE_FLOAT,
        self::TYPE_DOUBLE,
        self::TYPE_DECIMAL,
    ];

    /**
     * @var string[] Textual column types
     */
    private static array $textualColumnTypes = [
        self::TYPE_CHAR,
        self::TYPE_STRING,
        self::TYPE_TEXT,

        // MySQL-specific ones:
        self::TYPE_TINYTEXT,
        self::TYPE_MEDIUMTEXT,
        self::TYPE_LONGTEXT,
        self::TYPE_ENUM,
    ];

    /**
     * @var string[]
     */
    private static array $operators = ['not ', '!=', '<=', '>=', '<', '>', '='];

    /**
     * Parses a query param value and applies it to a query builder.
     *
     * If the `$value` is a string, it will automatically be converted to an array, split on any commas within the
     * string (via [[Arr::toArray()]]). If that is not desired behavior, you can escape the comma
     * with a backslash before it.
     *
     * The first value can be set to either `and`, `or`, or `not` to define whether *all*, *any*, or *none* of the values must match.
     * (`or` will be assumed by default.)
     *
     * Values can begin with the operators `'not '`, `'!='`, `'<='`, `'>='`, `'<'`, `'>'`, or `'='`. If they don’t,
     * `'='` will be assumed.
     *
     * Values can also be set to either `':empty:'` or `':notempty:'` if you want to search for empty or non-empty
     * database values. (An “empty” value is either `NULL` or an empty string of text).
     *
     * @param  string  $column  The database column that the param is targeting.
     * @param  string|int|array  $value  The param value(s).
     * @param  string  $defaultOperator  The default operator to apply to the values
     *                                   (can be `not`, `!=`, `<=`, `>=`, `<`, `>`, or `=`)
     * @param  bool  $caseInsensitive  Whether the resulting condition should be case-insensitive
     * @param  self::TYPE_*  $columnType  The database column type the param is targeting
     */
    public static function whereParam(
        Builder $query,
        string $column,
        mixed $param,
        string $defaultOperator = '=',
        bool $caseInsensitive = false,
        ?string $columnType = null,
    ): void {
        $parsed = QueryParam::parse($param);

        if (empty($parsed->values)) {
            return;
        }

        $parsedColumnType = $columnType
            ? self::parseColumnType($columnType)
            : null;

        $isMysql = $query->getConnection()->getDriverName() === 'mysql';

        // Only PostgreSQL supports case-sensitive strings on non-JSON column values
        if ($isMysql && $columnType !== self::TYPE_JSON) {
            $caseInsensitive = false;
        }

        $caseColumn = $caseInsensitive
            ? $column
            : new Lower($column);

        $query->where(function (Builder $query) use ($caseColumn, $isMysql, $parsedColumnType, $columnType, $defaultOperator, $parsed, $column, $caseInsensitive) {
            $boolean = match ($parsed->operator) {
                QueryParam::AND, QueryParam::NOT => 'and',
                default => 'or',
            };

            $inVals = [];
            $notInVals = [];

            foreach ($parsed->values as $value) {
                $value = self::normalizeEmptyValue($value);
                $operator = self::parseParamOperator($value, $defaultOperator, $parsed->operator === QueryParam::NOT);

                if ($columnType !== null) {
                    if ($parsedColumnType === self::TYPE_BOOLEAN) {
                        // Convert val to a boolean
                        $value = ($value && $value !== ':empty:');
                        if ($operator === '!=') {
                            $value = ! $value;
                        }
                        $query->where($column, $value, boolean: $boolean);

                        continue;
                    }

                    if (
                        $value !== ':empty:' &&
                        ! is_numeric($value) &&
                        self::isNumericColumnType($columnType)
                    ) {
                        throw new InvalidArgumentException("Invalid numeric value: $value");
                    }
                }

                if ($value === ':empty:') {
                    // If this is a textual column type, also check for empty strings
                    if (
                        ($columnType === null && $isMysql) ||
                        ($columnType !== null && self::isTextualColumnType($columnType))
                    ) {
                        $query->where(function (Builder $query) use ($column) {
                            $query->whereNull($column)->orWhere($column, '');
                        }, boolean: $boolean);

                        continue;
                    }

                    $query->whereNull($column, boolean: $boolean);

                    continue;
                }

                if (is_string($value)) {
                    // Trim any whitespace from the value
                    $value = trim($value);

                    // This could be a LIKE condition
                    if ($operator === '=' || $operator === '!=') {
                        $value = preg_replace('/^\*|(?<!\\\)\*$/', '%', $value, -1, $count);
                        $like = (bool) $count;
                    } else {
                        $like = false;
                    }

                    // Unescape any asterisks and :empty:/:notempty:
                    if (in_array(strtolower($value), ['\\:empty:', '\\:notempty:', '\\not :empty:'])) {
                        $value = ltrim($value, '\\');
                    } else {
                        $value = str_replace('\*', '*', $value);
                    }

                    // if we're prepping to compare to a timestamp - ensure the value is a number not a string
                    if ($parsedColumnType === self::TYPE_TIMESTAMP) {
                        $value = (int) $value;
                    }

                    if ($like) {
                        if ($caseInsensitive && ! $isMysql) {
                            $operator = $operator === '=' ? 'ilike' : 'not ilike';
                        } else {
                            $operator = $operator === '=' ? 'like' : 'not like';
                        }

                        if ($caseInsensitive && $isMysql) {
                            $query->where($caseColumn, $operator, self::escapeForLike($value), boolean: $boolean);

                            continue;
                        }

                        $query->where($column, $operator, self::escapeForLike($value), boolean: $boolean);

                        continue;
                    }

                    if ($caseInsensitive) {
                        $value = mb_strtolower($value);
                    }
                }

                // ['or', 1, 2, 3] => IN (1, 2, 3)
                if (strtolower($parsed->operator) === QueryParam::OR && $operator === '=') {
                    $inVals[] = $value;

                    continue;
                }

                // ['and', '!=1', '!=2', '!=3'] => NOT IN (1, 2, 3)
                if (strtolower($parsed->operator) === QueryParam::AND && $operator === '!=') {
                    $notInVals[] = $value;

                    continue;
                }

                $query->where($caseColumn, $operator, $value, boolean: $boolean);
            }

            if (! empty($inVals)) {
                $query->whereIn($caseColumn, $inVals, boolean: $boolean);
            }

            if (! empty($notInVals)) {
                $query->whereNotIn($caseColumn, $notInVals, boolean: $boolean);
            }
        });
    }

    /**
     * Applies a query param value for a numeric column to a Query builder.
     *
     * The follow values are supported:
     *
     * - A number
     * - `:empty:` or `:notempty:`
     * - `'not x'` or `'!= x'`
     * - `'> x'`, `'>= x'`, `'< x'`, or `'<= x'`, or a combination of those
     *
     * @param  string  $column  The database column that the param is targeting.
     * @param  string|string[]  $value  The param value
     * @param  string  $defaultOperator  The default operator to apply to the values
     *                                   (can be `not`, `!=`, `<=`, `>=`, `<`, `>`, or `=`)
     * @param  string|null  $columnType  The database column type the param is targeting
     */
    public static function whereNumericParam(
        Builder $query,
        string $column,
        mixed $value,
        string $defaultOperator = '=',
        ?string $columnType = self::TYPE_INTEGER,
    ): void {
        self::whereParam($query, $column, $value, $defaultOperator, false, $columnType);
    }

    /**
     * Parses a query param value for a date/time column, and returns a
     * [[\yii\db\QueryInterface::where()]]-compatible condition.
     *
     * @param  string  $column  The database column that the param is targeting.
     * @param  string|array|DateTimeInterface  $value  The param value
     * @param  string  $defaultOperator  The default operator to apply to the values
     *                                   (can be `not`, `!=`, `<=`, `>=`, `<`, `>`, or `=`)
     */
    public static function whereDateParam(
        Builder $query,
        string $column,
        mixed $value,
        string $defaultOperator = '='
    ): void {
        $param = QueryParam::parse($value);

        if (empty($param->values)) {
            return;
        }

        $normalizedValues = [$param->operator];

        foreach ($param->values as $val) {
            // Is this an empty value?
            $val = self::normalizeEmptyValue($val);

            if ($val === ':empty:' || $val === 'not :empty:') {
                $normalizedValues[] = $val;

                // Sneak out early
                continue;
            }

            $operator = self::parseParamOperator($val, $defaultOperator);

            // Assume that date params are set in the system timezone
            $val = Date::parse($val);

            $normalizedValues[] = $operator.$val;
        }

        self::whereParam($query, $column, $normalizedValues, $defaultOperator, false, self::TYPE_DATETIME);
    }

    /**
     * Parses a column type definition and returns just the column type, if it can be determined.
     */
    public static function parseColumnType(string $columnType): ?string
    {
        if (! preg_match('/^\w+/', $columnType, $matches)) {
            return null;
        }

        return strtolower($matches[0]);
    }

    /**
     * Normalizes “empty” values.
     *
     * @param  mixed  $value  The param value.
     * @return mixed $value The normalized value.
     */
    private static function normalizeEmptyValue(mixed $value): mixed
    {
        if ($value === null) {
            return ':empty:';
        }

        if (! is_string($value) || $value === ':empty:' || $value === 'not :empty:') {
            return $value;
        }

        $lower = strtolower($value);

        if ($lower === ':empty:') {
            return ':empty:';
        }

        if ($lower === ':notempty:' || $lower === 'not :empty:') {
            return 'not :empty:';
        }

        return $value;
    }

    /**
     * Extracts the operator from a DB param and returns it.
     *
     * @param  mixed  $value  Te param value.
     * @param  string  $default  The default operator to use
     * @param  bool  $negate  Whether to reverse whatever the selected operator is
     * @return string The operator ('!=', '<=', '>=', '<', '>', or '=')
     */
    private static function parseParamOperator(mixed &$value, string $default, bool $negate = false): string
    {
        $op = null;

        if (is_string($value)) {
            foreach (self::$operators as $operator) {
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

        $op ??= trim($default) === 'not' ? '!=' : $default;

        return match (true) {
            ! $negate => $op,
            $op === '!=' => '=',
            $op === '<=' => '>',
            $op === '>=' => '<',
            $op === '<' => '>=',
            $op === '>' => '<=',
            $op === '=' => '!=',
        };
    }

    /**
     * Returns whether the given column type is numeric.
     */
    public static function isNumericColumnType(string $columnType): bool
    {
        return in_array(self::parseColumnType($columnType), self::$numericColumnTypes, true);
    }

    /**
     * Returns whether the given column type is textual.
     */
    public static function isTextualColumnType(string $columnType): bool
    {
        return in_array(self::parseColumnType($columnType), self::$textualColumnTypes, true);
    }

    /**
     * Escapes underscores within a value for a `LIKE` condition.
     */
    public static function escapeForLike(string $value): string
    {
        return preg_replace('/(?<!\\\)_/', '\\_', $value);
    }

    /**
     * Escapes commas in a string so the value doesn’t get interpreted as an array by [[parseParam()]].
     */
    public static function escapeCommas(string $value): string
    {
        return preg_replace('/(?<!\\\),/', '\\\$0', $value);
    }
}
