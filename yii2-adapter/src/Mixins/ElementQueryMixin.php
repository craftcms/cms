<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Mixins;

use Closure;
use Craft;
use CraftCms\Cms\Database\Queries\ElementQuery;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Deprecator;
use yii\base\NotSupportedException;

class ElementQueryMixin
{
    public function getCachedResult(): Closure
    {
        return function() {
            Deprecator::log('ElementQuery-getCachedResult', 'Calling ->getCachedResult on an ElementQuery is deprecated. Use ->getResultOverride() instead.');

            /** @var ElementQuery $this */
            return $this->getResultOverride();
        };
    }

    public function setCachedResult(): Closure
    {
        return function(array $elements) {
            Deprecator::log('ElementQuery-setCachedResult', 'Calling ->setCachedResult on an ElementQuery is deprecated. Use ->setResultOverride() instead.');

            /** @var ElementQuery $this */
            $this->setResultOverride($elements);
        };
    }

    public function clearCachedResult(): Closure
    {
        return function() {
            Deprecator::log('ElementQuery-clearCachedResult', 'Calling ->clearCachedResult on an ElementQuery is deprecated. Use ->clearResultOverride() instead.');

            /** @var ElementQuery $this */
            $this->clearResultOverride();
        };
    }

    public function collect(): Closure
    {
        return function() {
            Deprecator::log('ElementQuery-collect', 'Calling ->collect on an ElementQuery is deprecated. ElementQuery now returns a collection by default.');

            return $this->get();
        };
    }

    public function scalar(): Closure
    {
        return function() {
            Deprecator::log('ElementQuery-scalar', 'Calling ->scalar on an ElementQuery is deprecated. Use ->value($column) instead.');

            return $this->value($this->query->getColumns()[0]);
        };
    }

    public function addOrderBy(): Closure
    {
        return function($columns) {
            Deprecator::log('ElementQuery-scalar', 'Calling ->scalar on an ElementQuery is deprecated. Use ->value($column) instead.');

            foreach (Arr::wrap($columns) as $column) {
                $this->orderBy($column);
            }

            return $this;
        };
    }

    public function afterPopulate(): Closure
    {
        return function(array $elements) {
            Deprecator::log('ElementQuery-afterPopulate', 'Calling ->afterPopulate on an ElementQuery is deprecated.');

            return $elements;
        };
    }

    public function andWhere(): Closure
    {
        return function($condition) {
            Deprecator::log('ElementQuery-andWhere', 'Calling ->andWhere on an ElementQuery is deprecated. Use ->where() instead.');

            $condition = Craft::$app->getDb()->getQueryBuilder()->buildWhere($condition, $params);

            if (!$condition) {
                return $this;
            }

            return $this->whereRaw($condition, $params);
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

            if (!$condition) {
                return $this;
            }

            $this->query->wheres = [];
            $this->subQuery->wheres = [];

            return $this->whereRaw($condition, $params);
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

            if (!$condition) {
                return $this;
            }

            return $this->whereRaw($condition, $params);
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

            if (!$condition) {
                return $this;
            }

            return $this->orWhereRaw($condition, $params);
        };
    }

    public function emulateExecution(): Closure
    {
        return function($value = true) {
            Deprecator::log('ElementQuery-emulateExecution', 'Calling ->emulateExecution on an ElementQuery is deprecated.');

            if ($value) {
                $this->setResultOverride([]);
            } else {
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
        if (!is_array($condition)) {
            return $condition;
        }

        if (!isset($condition[0])) {
            // hash format: 'column1' => 'value1', 'column2' => 'value2', ...
            foreach ($condition as $name => $value) {
                if ($this->isEmpty($value)) {
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
                    $subCondition = $this->filterCondition($operand);
                    if ($this->isEmpty($subCondition)) {
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
                    if ($this->isEmpty($condition[1]) || $this->isEmpty($condition[2])) {
                        return [];
                    }
                }
                break;
            default:
                if (array_key_exists(1, $condition) && $this->isEmpty($condition[1])) {
                    return [];
                }
        }

        array_unshift($condition, $operator);

        return $condition;
    }
}
