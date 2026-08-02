<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Forms;

use CraftCms\Cms\Cp\Forms\Data\VisibilityConditionData;
use CraftCms\Cms\Cp\Forms\Enums\ConditionOperator;

readonly class Condition
{
    private function __construct(
        private VisibilityConditionData $data,
    ) {}

    public static function equals(string $name, mixed $value): self
    {
        return self::comparison($name, ConditionOperator::Equals, $value);
    }

    public static function notEquals(string $name, mixed $value): self
    {
        return self::comparison($name, ConditionOperator::NotEquals, $value);
    }

    public static function lessThan(string $name, mixed $value): self
    {
        return self::comparison($name, ConditionOperator::LessThan, $value);
    }

    public static function lessThanOrEqual(string $name, mixed $value): self
    {
        return self::comparison($name, ConditionOperator::LessThanOrEqual, $value);
    }

    public static function greaterThan(string $name, mixed $value): self
    {
        return self::comparison($name, ConditionOperator::GreaterThan, $value);
    }

    public static function greaterThanOrEqual(string $name, mixed $value): self
    {
        return self::comparison($name, ConditionOperator::GreaterThanOrEqual, $value);
    }

    public static function beginsWith(string $name, mixed $value): self
    {
        return self::comparison($name, ConditionOperator::BeginsWith, $value);
    }

    public static function endsWith(string $name, mixed $value): self
    {
        return self::comparison($name, ConditionOperator::EndsWith, $value);
    }

    public static function contains(string $name, mixed $value): self
    {
        return self::comparison($name, ConditionOperator::Contains, $value);
    }

    public static function in(string $name, mixed $value): self
    {
        return self::comparison($name, ConditionOperator::In, $value);
    }

    public static function notIn(string $name, mixed $value): self
    {
        return self::comparison($name, ConditionOperator::NotIn, $value);
    }

    public static function empty(string $name): self
    {
        return self::comparisonWithoutValue($name, ConditionOperator::Empty);
    }

    public static function notEmpty(string $name): self
    {
        return self::comparisonWithoutValue($name, ConditionOperator::NotEmpty);
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

    private static function comparison(string $name, ConditionOperator $operator, mixed $value): self
    {
        return new self(new VisibilityConditionData([
            'name' => $name,
            'operator' => $operator->value,
            'value' => $value,
        ]));
    }

    private static function comparisonWithoutValue(string $name, ConditionOperator $operator): self
    {
        return new self(new VisibilityConditionData([
            'name' => $name,
            'operator' => $operator->value,
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
