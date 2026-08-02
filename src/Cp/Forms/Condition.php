<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Forms;

use CraftCms\Cms\Cp\Forms\Data\VisibilityConditionData;

readonly class Condition
{
    private function __construct(
        private VisibilityConditionData $data,
    ) {}

    public static function equals(string $name, mixed $value): self
    {
        return self::comparison($name, 'equals', $value);
    }

    public static function notEquals(string $name, mixed $value): self
    {
        return self::comparison($name, 'notEquals', $value);
    }

    public static function lessThan(string $name, mixed $value): self
    {
        return self::comparison($name, 'lessThan', $value);
    }

    public static function lessThanOrEqual(string $name, mixed $value): self
    {
        return self::comparison($name, 'lessThanOrEqual', $value);
    }

    public static function greaterThan(string $name, mixed $value): self
    {
        return self::comparison($name, 'greaterThan', $value);
    }

    public static function greaterThanOrEqual(string $name, mixed $value): self
    {
        return self::comparison($name, 'greaterThanOrEqual', $value);
    }

    public static function beginsWith(string $name, mixed $value): self
    {
        return self::comparison($name, 'beginsWith', $value);
    }

    public static function endsWith(string $name, mixed $value): self
    {
        return self::comparison($name, 'endsWith', $value);
    }

    public static function contains(string $name, mixed $value): self
    {
        return self::comparison($name, 'contains', $value);
    }

    public static function in(string $name, mixed $value): self
    {
        return self::comparison($name, 'in', $value);
    }

    public static function notIn(string $name, mixed $value): self
    {
        return self::comparison($name, 'notIn', $value);
    }

    public static function empty(string $name): self
    {
        return self::comparisonWithoutValue($name, 'empty');
    }

    public static function notEmpty(string $name): self
    {
        return self::comparisonWithoutValue($name, 'notEmpty');
    }

    public static function all(self ...$conditions): self
    {
        return self::group('all', $conditions);
    }

    public static function any(self ...$conditions): self
    {
        return self::group('any', $conditions);
    }

    public function toData(): VisibilityConditionData
    {
        return $this->data;
    }

    private static function comparison(string $name, string $operator, mixed $value): self
    {
        return new self(new VisibilityConditionData([
            'name' => $name,
            'operator' => $operator,
            'value' => $value,
        ]));
    }

    private static function comparisonWithoutValue(string $name, string $operator): self
    {
        return new self(new VisibilityConditionData([
            'name' => $name,
            'operator' => $operator,
        ]));
    }

    /**
     * @param  string  $operator  Group operator.
     * @param  list<self>  $conditions  Grouped conditions.
     */
    private static function group(string $operator, array $conditions): self
    {
        return new self(new VisibilityConditionData([
            $operator => array_map(
                fn (self $condition): VisibilityConditionData => $condition->toData(),
                $conditions,
            ),
        ]));
    }
}
