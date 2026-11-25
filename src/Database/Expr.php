<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database;

use CraftCms\Cms\Database\Expressions\Cast;
use CraftCms\Cms\Database\Expressions\FixedOrderExpression;
use CraftCms\Cms\Database\Expressions\JsonContains;
use CraftCms\Cms\Database\Expressions\JsonExtract;
use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Database\Query\ConditionExpression;
use Illuminate\Contracts\Database\Query\Expression;
use Tpetry\QueryExpressions\Function\Aggregate\Avg;
use Tpetry\QueryExpressions\Function\Aggregate\Count;
use Tpetry\QueryExpressions\Function\Aggregate\CountFilter;
use Tpetry\QueryExpressions\Function\Aggregate\Max;
use Tpetry\QueryExpressions\Function\Aggregate\Min;
use Tpetry\QueryExpressions\Function\Aggregate\Sum;
use Tpetry\QueryExpressions\Function\Aggregate\SumFilter;
use Tpetry\QueryExpressions\Function\Comparison\StrListContains;
use Tpetry\QueryExpressions\Function\Conditional\Coalesce;
use Tpetry\QueryExpressions\Function\Conditional\Greatest;
use Tpetry\QueryExpressions\Function\Conditional\Least;
use Tpetry\QueryExpressions\Function\Math\Abs;
use Tpetry\QueryExpressions\Function\String\Concat;
use Tpetry\QueryExpressions\Function\String\Lower;
use Tpetry\QueryExpressions\Function\String\Upper;
use Tpetry\QueryExpressions\Function\String\Uuid4;
use Tpetry\QueryExpressions\Function\Time\ExtractDatePart;
use Tpetry\QueryExpressions\Function\Time\Now;
use Tpetry\QueryExpressions\Function\Time\TimestampBin;
use Tpetry\QueryExpressions\Language\Alias;
use Tpetry\QueryExpressions\Language\CaseGroup;
use Tpetry\QueryExpressions\Language\CaseRule;
use Tpetry\QueryExpressions\Operator\Arithmetic\Add;
use Tpetry\QueryExpressions\Operator\Arithmetic\Divide;
use Tpetry\QueryExpressions\Operator\Arithmetic\Modulo;
use Tpetry\QueryExpressions\Operator\Arithmetic\Multiply;
use Tpetry\QueryExpressions\Operator\Arithmetic\Power;
use Tpetry\QueryExpressions\Operator\Arithmetic\Subtract;
use Tpetry\QueryExpressions\Operator\Bitwise\BitAnd;
use Tpetry\QueryExpressions\Operator\Bitwise\BitNot;
use Tpetry\QueryExpressions\Operator\Bitwise\BitOr;
use Tpetry\QueryExpressions\Operator\Bitwise\BitXor;
use Tpetry\QueryExpressions\Operator\Bitwise\ShiftLeft;
use Tpetry\QueryExpressions\Operator\Bitwise\ShiftRight;
use Tpetry\QueryExpressions\Operator\Comparison\Between;
use Tpetry\QueryExpressions\Operator\Comparison\DistinctFrom;
use Tpetry\QueryExpressions\Operator\Comparison\Equal;
use Tpetry\QueryExpressions\Operator\Comparison\GreaterThan;
use Tpetry\QueryExpressions\Operator\Comparison\GreaterThanOrEqual;
use Tpetry\QueryExpressions\Operator\Comparison\IsNull;
use Tpetry\QueryExpressions\Operator\Comparison\LessThan;
use Tpetry\QueryExpressions\Operator\Comparison\LessThanOrEqual;
use Tpetry\QueryExpressions\Operator\Comparison\NotDistinctFrom;
use Tpetry\QueryExpressions\Operator\Comparison\NotEqual;
use Tpetry\QueryExpressions\Operator\Comparison\NotIsNull;
use Tpetry\QueryExpressions\Operator\Logical\CondAnd;
use Tpetry\QueryExpressions\Operator\Logical\CondNot;
use Tpetry\QueryExpressions\Operator\Logical\CondOr;
use Tpetry\QueryExpressions\Operator\Logical\CondXor;
use Tpetry\QueryExpressions\Value\Number;
use Tpetry\QueryExpressions\Value\Value;

/**
 * Factory methods for database-independent query expressions, from
 * [tpetry/laravel-query-expressions](https://github.com/tpetry/laravel-query-expressions) and Craft.
 *
 * ```php
 * DB::table('discounts')
 *     ->select(Expr::alias(Expr::coalesce([Expr::sum('uses'), Expr::number(0)]), 'uses'))
 *     ->orderBy(Expr::isNotNull('catalogPricingRuleId'));
 * ```
 *
 * Strings are column names. Wrap literal values in {@see self::value()} or {@see self::number()}.
 *
 * @since 6.0.0
 */
class Expr
{
    public static function value(string|int|float|bool|null $value, bool $binary = false): Value
    {
        return new Value($value, $binary);
    }

    public static function number(int|float $value): Number
    {
        return new Number($value);
    }

    public static function alias(string|Expression $expression, string $name): Alias
    {
        return new Alias($expression, $name);
    }

    /**
     * Returns Craft’s cast expression, which also accepts types that aren’t portable.
     *
     * @param  string  $type  `bigint`, `double`, `float` and `int` are translated for each database. Other types are
     *                        passed through as they are, such as `char` or `decimal(14,4)`.
     */
    public static function cast(string|Expression $expression, string $type): Cast
    {
        return new Cast($expression, $type);
    }

    /**
     * @param  non-empty-array<int, CaseRule>  $when
     */
    public static function case(array $when, string|Expression|null $else = null): CaseGroup
    {
        return new CaseGroup($when, $else);
    }

    public static function caseRule(string|Expression $result, ConditionExpression $condition): CaseRule
    {
        return new CaseRule($result, $condition);
    }

    public static function add(string|Expression $value1, string|Expression $value2, string|Expression ...$values): Add
    {
        return new Add($value1, $value2, ...$values);
    }

    public static function subtract(string|Expression $value1, string|Expression $value2, string|Expression ...$values): Subtract
    {
        return new Subtract($value1, $value2, ...$values);
    }

    public static function multiply(string|Expression $value1, string|Expression $value2, string|Expression ...$values): Multiply
    {
        return new Multiply($value1, $value2, ...$values);
    }

    public static function divide(string|Expression $value1, string|Expression $value2, string|Expression ...$values): Divide
    {
        return new Divide($value1, $value2, ...$values);
    }

    public static function modulo(string|Expression $value1, string|Expression $value2, string|Expression ...$values): Modulo
    {
        return new Modulo($value1, $value2, ...$values);
    }

    public static function power(string|Expression $value1, string|Expression $value2, string|Expression ...$values): Power
    {
        return new Power($value1, $value2, ...$values);
    }

    public static function bitAnd(string|Expression $value1, string|Expression $value2): BitAnd
    {
        return new BitAnd($value1, $value2);
    }

    public static function bitNot(string|Expression $value): BitNot
    {
        return new BitNot($value);
    }

    public static function bitOr(string|Expression $value1, string|Expression $value2): BitOr
    {
        return new BitOr($value1, $value2);
    }

    public static function bitXor(string|Expression $value1, string|Expression $value2): BitXor
    {
        return new BitXor($value1, $value2);
    }

    public static function shiftLeft(string|Expression $value, string|Expression $times): ShiftLeft
    {
        return new ShiftLeft($value, $times);
    }

    public static function shiftRight(string|Expression $value, string|Expression $times): ShiftRight
    {
        return new ShiftRight($value, $times);
    }

    public static function between(string|Expression $value, string|Expression $min, string|Expression $max): Between
    {
        return new Between($value, $min, $max);
    }

    public static function distinctFrom(string|Expression $value1, string|Expression $value2): DistinctFrom
    {
        return new DistinctFrom($value1, $value2);
    }

    public static function notDistinctFrom(string|Expression $value1, string|Expression $value2): NotDistinctFrom
    {
        return new NotDistinctFrom($value1, $value2);
    }

    public static function equal(string|Expression $value1, string|Expression $value2): Equal
    {
        return new Equal($value1, $value2);
    }

    public static function notEqual(string|Expression $value1, string|Expression $value2): NotEqual
    {
        return new NotEqual($value1, $value2);
    }

    public static function greaterThan(string|Expression $value1, string|Expression $value2): GreaterThan
    {
        return new GreaterThan($value1, $value2);
    }

    public static function greaterThanOrEqual(string|Expression $value1, string|Expression $value2): GreaterThanOrEqual
    {
        return new GreaterThanOrEqual($value1, $value2);
    }

    public static function lessThan(string|Expression $value1, string|Expression $value2): LessThan
    {
        return new LessThan($value1, $value2);
    }

    public static function lessThanOrEqual(string|Expression $value1, string|Expression $value2): LessThanOrEqual
    {
        return new LessThanOrEqual($value1, $value2);
    }

    public static function isNull(string|Expression $value): IsNull
    {
        return new IsNull($value);
    }

    public static function isNotNull(string|Expression $value): NotIsNull
    {
        return new NotIsNull($value);
    }

    public static function strListContains(string|Expression $strList, string|Expression $str): StrListContains
    {
        return new StrListContains($strList, $str);
    }

    public static function and(Expression $value1, Expression $value2): CondAnd
    {
        return new CondAnd($value1, $value2);
    }

    public static function or(Expression $value1, Expression $value2): CondOr
    {
        return new CondOr($value1, $value2);
    }

    public static function not(Expression $value): CondNot
    {
        return new CondNot($value);
    }

    public static function xor(Expression $value1, Expression $value2): CondXor
    {
        return new CondXor($value1, $value2);
    }

    public static function avg(string|Expression $value): Avg
    {
        return new Avg($value);
    }

    public static function count(string|Expression $value, bool $distinct = false): Count
    {
        return new Count($value, $distinct);
    }

    public static function countFilter(Expression $filter): CountFilter
    {
        return new CountFilter($filter);
    }

    public static function max(string|Expression $value): Max
    {
        return new Max($value);
    }

    public static function min(string|Expression $value): Min
    {
        return new Min($value);
    }

    public static function sum(string|Expression $value): Sum
    {
        return new Sum($value);
    }

    public static function sumFilter(string|Expression $value, Expression $filter): SumFilter
    {
        return new SumFilter($value, $filter);
    }

    /**
     * @param  non-empty-array<int, string|Expression>  $expressions
     */
    public static function coalesce(array $expressions): Coalesce
    {
        return new Coalesce($expressions);
    }

    /**
     * @param  non-empty-array<int, string|Expression>  $expressions
     */
    public static function greatest(array $expressions): Greatest
    {
        return new Greatest($expressions);
    }

    /**
     * @param  non-empty-array<int, string|Expression>  $expressions
     */
    public static function least(array $expressions): Least
    {
        return new Least($expressions);
    }

    public static function abs(string|Expression $expression): Abs
    {
        return new Abs($expression);
    }

    /**
     * @param  non-empty-array<int, string|Expression>  $expressions
     */
    public static function concat(array $expressions): Concat
    {
        return new Concat($expressions);
    }

    public static function lower(string|Expression $expression): Lower
    {
        return new Lower($expression);
    }

    public static function upper(string|Expression $expression): Upper
    {
        return new Upper($expression);
    }

    public static function uuid4(): Uuid4
    {
        return new Uuid4;
    }

    /**
     * @param  'year'  $part
     */
    public static function extractDatePart(string|Expression $column, string $part): ExtractDatePart
    {
        return new ExtractDatePart($column, $part);
    }

    public static function now(): Now
    {
        return new Now;
    }

    public static function timestampBin(string|Expression $expression, DateInterval $step, ?DateTimeInterface $origin = null): TimestampBin
    {
        return new TimestampBin($expression, $step, $origin);
    }

    /**
     * @param  string|list<string>  $path
     */
    public static function jsonExtract(string|Expression $expression, string|array $path): JsonExtract
    {
        return new JsonExtract($expression, $path);
    }

    public static function jsonContains(string|Expression $expression, mixed $value): JsonContains
    {
        return new JsonContains($expression, $value);
    }

    /**
     * @param  list<mixed>  $values
     */
    public static function fixedOrder(string|Expression $column, array $values): FixedOrderExpression
    {
        return new FixedOrderExpression($column, $values);
    }
}
