<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Mixins;

use Closure;
use Craft;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Deprecator;
use yii\base\NotSupportedException;

class ElementQueryMixin
{
    public function getCachedResult(): Closure
    {
        return function() {
            Deprecator::log('ElementQuery-getCachedResult', 'Calling ->getCachedResult on an ElementQuery is deprecated. Use ->getResultOverride() instead.');

            /** @phpstan-ignore-next-line */
            return $this->getResultOverride();
        };
    }

    public function setCachedResult(): Closure
    {
        return function(array $elements) {
            Deprecator::log('ElementQuery-setCachedResult', 'Calling ->setCachedResult on an ElementQuery is deprecated. Use ->setResultOverride() instead.');

            /** @phpstan-ignore-next-line */
            $this->setResultOverride($elements);
        };
    }

    public function clearCachedResult(): Closure
    {
        return function() {
            Deprecator::log('ElementQuery-clearCachedResult', 'Calling ->clearCachedResult on an ElementQuery is deprecated. Use ->clearResultOverride() instead.');

            /** @phpstan-ignore-next-line */
            $this->clearResultOverride();
        };
    }

    public function collect(): Closure
    {
        return function() {
            Deprecator::log('ElementQuery-collect', 'Calling ->collect on an ElementQuery is deprecated. ElementQuery now returns a collection by default.');

            /** @phpstan-ignore-next-line */
            return $this->get();
        };
    }

    public function scalar(): Closure
    {
        return function() {
            Deprecator::log('ElementQuery-scalar', 'Calling ->scalar on an ElementQuery is deprecated. Use ->value($column) instead.');

            /** @phpstan-ignore-next-line */
            return $this->value($this->query->getColumns()[0]);
        };
    }

    public function column(): Closure
    {
        return function($column) {
            Deprecator::log('ElementQuery-column', 'Calling ->column on an ElementQuery is deprecated. Use ->pluck($column) instead.');

            /** @phpstan-ignore-next-line */
            return $this->pluck($this->query->getColumns()[0])->all();
        };
    }

    public function pairs(): Closure
    {
        return function() {
            Deprecator::log('ElementQuery-pairs', 'Calling ->pairs on an ElementQuery is deprecated. Use ->pluck($value, $key) instead.');

            /** @phpstan-ignore-next-line */
            return $this->pluck($this->query->getColumns()[1], $this->query->getColumns()[0])->all();
        };
    }

    public function addOrderBy(): Closure
    {
        return function($columns) {
            Deprecator::log('ElementQuery-scalar', 'Calling ->scalar on an ElementQuery is deprecated. Use ->value($column) instead.');

            foreach (Arr::wrap($columns) as $column) {
                /** @phpstan-ignore-next-line */
                $this->orderBy($column);
            }

            return $this;
        };
    }

    public function afterPopulate(): Closure
    {
        return function(array $elements) {
            Deprecator::log('ElementQuery-afterPopulate', 'Calling ->afterPopulate on an ElementQuery is deprecated.');

            /** @phpstan-ignore-next-line */
            return $this->hydrate($elements);
        };
    }

    public function andWhere(): Closure
    {
        return function($condition) {
            Deprecator::log('ElementQuery-andWhere', 'Calling ->andWhere on an ElementQuery is deprecated. Use ->where() instead.');

            $condition = Craft::$app->getDb()->getQueryBuilder()->buildWhere($condition, $params);

            // Yii uses named parameters, Laravel uses positional
            $condition = preg_replace('/:qp\d+/', '?', $condition);

            if (!$condition) {
                return $this;
            }

            /** @phpstan-ignore-next-line */
            return $this->whereRaw($condition, array_values($params));
        };
    }

    public function filterWhere(): Closure
    {
        return function($condition) {
            Deprecator::log('ElementQuery-filterWhere', 'Calling ->filterWhere on an ElementQuery is deprecated.');

            $condition = ElementQueryMixin::filterCondition($condition);

            if ($condition === []) {
                return $this;
            }

            $condition = Craft::$app->getDb()->getQueryBuilder()->buildWhere($condition, $params);

            // Yii uses named parameters, Laravel uses positional
            $condition = preg_replace('/:qp\d+/', '?', $condition);

            if (!$condition) {
                return $this;
            }

            /** @phpstan-ignore-next-line */
            $this->query->wheres = [];
            /** @phpstan-ignore-next-line */
            $this->subQuery->wheres = [];

            /** @phpstan-ignore-next-line */
            return $this->whereRaw($condition, array_values($params));
        };
    }

    public function andFilterWhere(): Closure
    {
        return function($condition) {
            Deprecator::log('ElementQuery-andFilterWhere', 'Calling ->andFilterWhere on an ElementQuery is deprecated. Use ->where() instead.');

            $condition = ElementQueryMixin::filterCondition($condition);

            if ($condition === []) {
                return $this;
            }

            $condition = Craft::$app->getDb()->getQueryBuilder()->buildWhere($condition, $params);

            // Yii uses named parameters, Laravel uses positional
            $condition = preg_replace('/:qp\d+/', '?', $condition);

            if (!$condition) {
                return $this;
            }

            /** @phpstan-ignore-next-line */
            return $this->whereRaw($condition, array_values($params));
        };
    }

    public function orFilterWhere(): Closure
    {
        return function($condition) {
            Deprecator::log('ElementQuery-orFilterWhere', 'Calling ->orFilterWhere on an ElementQuery is deprecated. Use ->orWhere() instead.');

            $condition = ElementQueryMixin::filterCondition($condition);

            if ($condition === []) {
                return $this;
            }

            $condition = Craft::$app->getDb()->getQueryBuilder()->buildWhere($condition, $params);

            // Yii uses named parameters, Laravel uses positional
            $condition = preg_replace('/:qp\d+/', '?', $condition);

            if (!$condition) {
                return $this;
            }

            /** @phpstan-ignore-next-line */
            return $this->orWhereRaw($condition, array_values($params));
        };
    }

    public function emulateExecution(): Closure
    {
        return function($value = true) {
            Deprecator::log('ElementQuery-emulateExecution', 'Calling ->emulateExecution on an ElementQuery is deprecated.');

            if ($value) {
                /** @phpstan-ignore-next-line */
                $this->setResultOverride([]);
            } else {
                /** @phpstan-ignore-next-line */
                $this->clearResultOverride();
            }

            return $this;
        };
    }

    /**
     * Removes [[isEmpty()|empty operands]] from the given query condition.
     *
     * @param array $condition the original condition
     *
     * @return array the condition with [[isEmpty()|empty operands]] removed.
     * @throws NotSupportedException if the condition operator is not supported
     */
    private static function filterCondition(array $condition): array
    {
        if (!isset($condition[0])) {
            // hash format: 'column1' => 'value1', 'column2' => 'value2', ...
            foreach ($condition as $name => $value) {
                if (self::isEmpty($value)) {
                    unset($condition[$name]);
                }
            }

            return $condition;
        }

        // operator format: operator, operand 1, operand 2, ...

        $operator = array_shift($condition);

        switch (strtoupper($operator)) {
            case 'NOT':
            case 'AND':
            case 'OR':
                foreach ($condition as $i => $operand) {
                    $subCondition = self::filterCondition($operand);
                    if (self::isEmpty($subCondition)) {
                        unset($condition[$i]);
                    } else {
                        $condition[$i] = $subCondition;
                    }
                }

                if (empty($condition)) {
                    return [];
                }
                break;
            case 'BETWEEN':
            case 'NOT BETWEEN':
                if (array_key_exists(1, $condition) && array_key_exists(2, $condition)) {
                    if (self::isEmpty($condition[1]) || self::isEmpty($condition[2])) {
                        return [];
                    }
                }
                break;
            default:
                if (array_key_exists(1, $condition) && self::isEmpty($condition[1])) {
                    return [];
                }
        }

        array_unshift($condition, $operator);

        return $condition;
    }

    /**
     * Returns a value indicating whether the give value is "empty".
     *
     * The value is considered "empty", if one of the following conditions is satisfied:
     *
     * - it is `null`,
     * - an empty string (`''`),
     * - a string containing only whitespace characters,
     * - or an empty array.
     *
     * @param mixed $value
     * @return bool if the value is empty
     */
    private static function isEmpty(mixed $value): bool
    {
        return $value === '' || $value === [] || $value === null || is_string($value) && trim($value) === '';
    }
}
