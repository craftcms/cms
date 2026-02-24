<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use craft\helpers\DateTimeHelper;
use CraftCms\Cms\Database\QueryParam;
use CraftCms\Cms\Support\Money as MoneyHelper;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;
use Money\Money;
use Serializable;
use Tpetry\QueryExpressions\Function\String\Lower;

final readonly class Query
{
    const string SIMPLE_TYPE_NUMERIC = 'numeric';

    const string SIMPLE_TYPE_TEXTUAL = 'textual';

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

    const string TYPE_JSONB = 'jsonb';

    private const array NUMERIC_COLUMN_TYPES = [
        self::TYPE_TINYINT,
        self::TYPE_SMALLINT,
        self::TYPE_INTEGER,
        self::TYPE_BIGINT,
        self::TYPE_FLOAT,
        self::TYPE_DOUBLE,
        self::TYPE_DECIMAL,
    ];

    private const array TEXTUAL_COLUMN_TYPES = [
        self::TYPE_CHAR,
        self::TYPE_STRING,
        self::TYPE_TEXT,

        // MySQL-specific ones:
        self::TYPE_TINYTEXT,
        self::TYPE_MEDIUMTEXT,
        self::TYPE_LONGTEXT,
        self::TYPE_ENUM,
    ];

    private const array OPERATORS = ['not ', '!=', '<=', '>=', '<', '>', '='];

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
     * @param  Builder  $query  The query builder to apply the param to.
     * @param  string|Expression  $column  The database column that the param is targeting.
     * @param  string|int|array  $param  The param value(s).
     * @param  string  $defaultOperator  The default operator to apply to the values
     *                                   (can be `not`, `!=`, `<=`, `>=`, `<`, `>`, or `=`)
     * @param  bool  $caseInsensitive  Whether the resulting condition should be case-insensitive
     * @param  self::TYPE_*  $columnType  The database column type the param is targeting
     */
    public static function whereParam(
        Builder $query,
        string|Expression $column,
        mixed $param,
        string $defaultOperator = '=',
        bool $caseInsensitive = false,
        ?string $columnType = null,
        string $boolean = 'and',
    ): Builder {
        $parsed = QueryParam::parse($param);

        if (empty($parsed->values)) {
            return $query;
        }

        $parsedColumnType = $columnType
            ? self::parseColumnType($columnType)
            : null;

        /** @var \Illuminate\Database\Connection $connection */
        $connection = $query->getConnection();
        $isMysql = $connection->isMysql();
        $isPgsql = $connection->isPgsql();

        // Only PostgreSQL supports case-sensitive strings on non-JSON column values
        if ($isMysql && $columnType !== self::TYPE_JSON) {
            $caseInsensitive = false;
        }

        $caseColumn = $caseInsensitive
            ? new Lower($column)
            : $column;

        $query->where(function (Builder $query) use ($caseColumn, $isMysql, $isPgsql, $parsedColumnType, $columnType, $defaultOperator, $parsed, $column, $caseInsensitive) {
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
                        $method = $operator === '!=' ? 'whereNot' : 'where';

                        $query->$method(function (Builder $query) use ($column) {
                            $query->whereNull($column)->orWhere($column, '');
                        }, boolean: $boolean);

                        continue;
                    }

                    $method = $operator === '!=' ? 'whereNotNull' : 'whereNull';

                    $query->$method($column, boolean: $boolean);

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
                        if ($caseInsensitive && $isPgsql) {
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
        }, boolean: $boolean);

        return $query;
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
     * @param  Builder  $query  The query builder to apply the param to.
     * @param  string|Expression  $column  The database column that the param is targeting.
     * @param  string|string[]  $value  The param value
     * @param  string  $defaultOperator  The default operator to apply to the values
     *                                   (can be `not`, `!=`, `<=`, `>=`, `<`, `>`, or `=`)
     * @param  string|null  $columnType  The database column type the param is targeting
     */
    public static function whereNumericParam(
        Builder $query,
        string|Expression $column,
        mixed $value,
        string $defaultOperator = '=',
        ?string $columnType = self::TYPE_INTEGER,
        string $boolean = 'and',
    ): Builder {
        return self::whereParam($query, $column, $value, $defaultOperator, false, $columnType, $boolean);
    }

    /**
     * Parses a query param value for a date/time column,
     * and applies it to the Query builder.
     *
     * @param  Builder  $query  The query builder to apply the param to.
     * @param  string|Expression  $column  The database column that the param is targeting.
     * @param  string|array|DateTimeInterface  $value  The param value
     * @param  string  $defaultOperator  The default operator to apply to the values
     *                                   (can be `not`, `!=`, `<=`, `>=`, `<`, `>`, or `=`)
     */
    public static function whereDateParam(
        Builder $query,
        string|Expression $column,
        mixed $value,
        string $defaultOperator = '=',
        string $boolean = 'and',
    ): Builder {
        $param = QueryParam::parse($value);

        if (empty($param->values)) {
            return $query;
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

        return self::whereParam($query, $column, $normalizedValues, $defaultOperator, false, self::TYPE_DATETIME, $boolean);
    }

    /**
     * Parses a query param value for a money column, and returns a
     * [[\yii\db\QueryInterface::where()]]-compatible condition.
     *
     * @param  Builder  $query  The query builder to apply the param to.
     * @param  string|Expression  $column  The database column that the param is targeting.
     * @param  string  $currency  The currency code to use for the money object.
     * @param  string|array|Money  $value  The param value
     * @param  string  $defaultOperator  The default operator to apply to the values
     *                                   (can be `not`, `!=`, `<=`, `>=`, `<`, `>`, or `=`)
     */
    public static function whereMoneyParam(
        Builder $query,
        string|Expression $column,
        string $currency,
        mixed $value,
        string $defaultOperator = '=',
        string $boolean = 'and',
    ): Builder {
        $param = QueryParam::parse($value);

        if (empty($param->values)) {
            return $query;
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
            $val = MoneyHelper::toMoney(['value' => $val, 'currency' => $currency]);

            $normalizedValues[] = $operator.$val->getAmount();
        }

        return self::whereParam($query, $column, $normalizedValues, $defaultOperator, false, self::TYPE_DATETIME, $boolean);
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
     * @param  Builder  $query  The query builder to apply the param to.
     * @param  string|Expression  $column  The database column that the param is targeting.
     * @param  string|bool|null|array<string|bool|null>  $value  The param value
     * @param  bool|null  $defaultValue  How `null` values should be treated
     * @param  string  $columnType  The database column type the param is targeting
     */
    public static function whereBooleanParam(
        Builder $query,
        string|Expression $column,
        mixed $value,
        ?bool $defaultValue = null,
        string $columnType = self::TYPE_BOOLEAN,
        string $boolean = 'and',
    ): Builder {
        if (is_array($value)) {
            foreach ($value as $val) {
                $query->orWhere(fn (Builder $query) => self::whereBooleanParam($query, $column, $val, $defaultValue, $columnType));
            }

            return $query;
        }

        if ($value !== null) {
            $value = self::normalizeEmptyValue($value);
            $operator = self::parseParamOperator($value, '=');
            $value = $value && $value !== ':empty:';
        } else {
            $operator = self::parseParamOperator($value, '=');
        }

        if ($operator === '!=' && is_bool($value)) {
            $value = ! $value;
        }

        if ($columnType === self::TYPE_JSON) {
            /** @phpstan-ignore-next-line */
            $value = match ($value) {
                true => 'true',
                false => 'false',
                null => null,
            };
            $defaultValue = match ($defaultValue) {
                true => 'true',
                false => 'false',
                null => null,
            };
        }

        return $query->where(function (Builder $query) use ($column, $value, $defaultValue) {
            $query
                ->where($column, $value)
                ->when(
                    $defaultValue === $value && $value !== null,
                    fn (Builder $query) => $query->orWhereNull($column),
                );
        }, boolean: $boolean);
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
     * Parses a column type definition and returns just the column length/size.
     */
    public static function parseColumnLength(string $columnType): ?int
    {
        if (! preg_match('/^\w+\((\d+)\)/', $columnType, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * Parses a decimal column type definition and returns just the column precision and scale.
     *
     * @return array{0:int,1:int}|null
     */
    public static function parseColumnPrecisionAndScale(string $columnType): ?array
    {
        if (! preg_match('/^\w+\((\d+),\s*(\d+)\)/', $columnType, $matches)) {
            return null;
        }

        return [(int) $matches[1], (int) $matches[2]];
    }

    /**
     * Returns a simplified version of a given column type.
     */
    public static function getSimplifiedColumnType(string $columnType): string
    {
        if (($shortColumnType = self::parseColumnType($columnType)) === null) {
            return $columnType;
        }

        // Numeric?
        if (in_array($shortColumnType, self::NUMERIC_COLUMN_TYPES, true)) {
            return self::SIMPLE_TYPE_NUMERIC;
        }

        // Textual?
        if (in_array($shortColumnType, self::TEXTUAL_COLUMN_TYPES, true)) {
            return self::SIMPLE_TYPE_TEXTUAL;
        }

        return $shortColumnType;
    }

    /**
     * Returns whether two column type definitions are relatively compatible with each other.
     */
    public static function areColumnTypesCompatible(string $typeA, string $typeB): bool
    {
        return self::getSimplifiedColumnType($typeA) === self::getSimplifiedColumnType($typeB);
    }

    /**
     * Normalizes a param value with a provided resolver function, unless the resolver function ever returns
     * an empty value.
     *
     * If the original param value began with `and`, `or`, or `not`, that will be preserved.
     *
     * @param  mixed  $value  The param value to be normalized
     * @param  callable  $resolver  Method to resolve non-model values to models
     * @return bool Whether the value was normalized
     */
    public static function normalizeParam(mixed &$value, callable $resolver): bool
    {
        if ($value === null) {
            return true;
        }

        if (! is_array($value)) {
            $testValue = [$value];
            if (self::normalizeParam($testValue, $resolver)) {
                $value = $testValue;

                return true;
            }

            return false;
        }

        $normalized = [];

        foreach ($value as $item) {
            if (
                empty($normalized) &&
                is_string($item) &&
                in_array(strtolower($item), [QueryParam::OR, QueryParam::AND, QueryParam::NOT], true)
            ) {
                $normalized[] = strtolower($item);

                continue;
            }

            $item = $resolver($item);
            if (! $item) {
                // The value couldn't be normalized in full, so bail
                return false;
            }

            $normalized[] = $item;
        }

        $value = $normalized;

        return true;
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
            foreach (self::OPERATORS as $operator) {
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
            default => throw new InvalidArgumentException("Invalid operator: $op"),
        };
    }

    /**
     * Returns whether the given column type is numeric.
     */
    public static function isNumericColumnType(string $columnType): bool
    {
        return in_array(self::parseColumnType($columnType), self::NUMERIC_COLUMN_TYPES, true);
    }

    /**
     * Returns whether the given column type is textual.
     */
    public static function isTextualColumnType(string $columnType): bool
    {
        return in_array(self::parseColumnType($columnType), self::TEXTUAL_COLUMN_TYPES, true);
    }

    /**
     * Escapes commas, asterisks, and colons in a string, so they are not treated as special characters in
     * [[whereParam()]].
     *
     * @param  string  $value  The param value.
     * @return string The escaped param value.
     */
    public static function escapeParam(string $value): string
    {
        if (in_array(strtolower($value), [':empty:', 'not :empty:', ':notempty:'])) {
            return "\\$value";
        }

        $value = preg_replace('/(?<!\\\)[,*]/', '\\\$0', $value);

        // If the value starts with an operator, escape that too.
        foreach (self::OPERATORS as $operator) {
            if (stripos((string) $value, $operator) === 0) {
                $value = "\\$value";

                break;
            }
        }

        return $value;
    }

    /**
     * Unescapes commas, asterisks, and colons added to a string via [[escapeParam()]].
     */
    public static function unescapeParam(string $value): string
    {
        $value = preg_replace('/\\\([,*:])/', '$1', $value);

        // If the value starts with an escaped operator, unescape that too.
        foreach (self::OPERATORS as $operator) {
            if (stripos((string) $value, "\\$operator") === 0) {
                $value = ltrim((string) $value, '\\');
                break;
            }
        }

        return $value;
    }

    /**
     * Escapes underscores within a value for a `LIKE` condition.
     */
    public static function escapeForLike(string $value): string
    {
        return preg_replace('/(?<!\\\)_/', '\\_', $value);
    }

    /**
     * Escapes commas in a string so the value doesn’t get interpreted as an array by {@see self::whereParam()}.
     */
    public static function escapeCommas(string $value): string
    {
        return preg_replace('/(?<!\\\),/', '\\\$0', $value);
    }

    /**
     * Prepares an array or object’s values to be sent to the database.
     */
    public static function prepareValuesForDb(mixed $values): array
    {
        // Normalize to an array
        $values = Arr::toArray($values, [], false);

        foreach ($values as $key => $value) {
            $values[$key] = self::prepareValueForDb($value);
        }

        return $values;
    }

    /**
     * Prepares a value to be sent to the database.
     *
     * @param  mixed  $value  The value to be prepared
     * @param  string|null  $columnType  The type of column the value will be stored in
     * @return mixed The prepped value
     */
    public static function prepareValueForDb(mixed $value, ?string $columnType = null): mixed
    {
        // Leave expressions alone
        if ($value instanceof Expression) {
            return $value;
        }

        // If the object explicitly defines its savable value, use that
        if ($value instanceof Serializable) {
            return $value->serialize();
        }

        // Only DateTime objects and ISO-8601 strings should automatically be detected as dates
        if ($value instanceof DateTimeInterface || DateTimeHelper::isIso8601($value)) {
            return self::prepareDateForDb($value);
        }

        // If this isn’t a JSON column and the value is an object or array, JSON-encode it
        if (is_object($value) || is_array($value)) {
            if (in_array($columnType, [self::TYPE_JSON, self::TYPE_JSONB])) {
                return Arr::toArray($value);
            }

            return Json::encode($value);
        }

        if ($columnType && self::isNumericColumnType($columnType) && is_bool($value)) {
            return (int) $value;
        }

        return $value;
    }

    /**
     * Prepares a date to be sent to the database.
     *
     * @param  mixed  $date  The date to be prepared
     * @return string|null The prepped date, or `null` if it could not be prepared
     */
    public static function prepareDateForDb(mixed $date): ?string
    {
        $date = DateTimeHelper::toDateTime($date);

        if ($date === false) {
            return null;
        }

        $date = clone $date;
        $date->setTimezone(new DateTimeZone('UTC'));

        return $date->format('Y-m-d H:i:s');
    }
}
