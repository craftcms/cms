<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Mixins;

use Closure;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use CraftCms\Cms\Support\Arr;
use CraftCms\Yii2Adapter\LooseEnds\LegacyUsage;
use yii\db\Query;
use function CraftCms\Yii2Adapter\Helpers\Queries\buildCondition;

class ElementQueryMixin
{
    public function getCachedResult(): Closure
    {
        return function() {
            LegacyUsage::record('ElementQuery-getCachedResult', 'Calling ->getCachedResult on an ElementQuery is deprecated. Use ->getResultOverride() instead.');

            /** @phpstan-ignore-next-line */
            return $this->getResultOverride();
        };
    }

    public function setCachedResult(): Closure
    {
        return function(array $elements) {
            LegacyUsage::record('ElementQuery-setCachedResult', 'Calling ->setCachedResult on an ElementQuery is deprecated. Use ->setResultOverride() instead.');

            /** @phpstan-ignore-next-line */
            $this->setResultOverride($elements);
        };
    }

    public function clearCachedResult(): Closure
    {
        return function() {
            LegacyUsage::record('ElementQuery-clearCachedResult', 'Calling ->clearCachedResult on an ElementQuery is deprecated. Use ->clearResultOverride() instead.');

            /** @phpstan-ignore-next-line */
            $this->clearResultOverride();
        };
    }

    public function collect(): Closure
    {
        return function() {
            LegacyUsage::record('ElementQuery-collect', 'Calling ->collect on an ElementQuery is deprecated. ElementQuery now returns a collection by default.');

            /** @phpstan-ignore-next-line */
            return $this->get();
        };
    }

    public function scalar(): Closure
    {
        return function() {
            LegacyUsage::record('ElementQuery-scalar', 'Calling ->scalar on an ElementQuery is deprecated. Use ->pluck($column)->first() for the raw value, or ->value($column) for the element’s value.');

            /** @phpstan-ignore-next-line */
            $row = $this->toBase()->first();

            // The first column of the first row, as Yii’s scalar() returned
            return $row === null ? null : Arr::first((array) $row);
        };
    }

    public function column(): Closure
    {
        return function($db = null) {
            LegacyUsage::record('ElementQuery-column', 'Calling ->column on an ElementQuery is deprecated. Use ->pluck($column) instead.');

            /** @phpstan-ignore-next-line */
            return $this->pluck($this->query->getColumns()[0])->all();
        };
    }

    public function pairs(): Closure
    {
        return function() {
            LegacyUsage::record('ElementQuery-pairs', 'Calling ->pairs on an ElementQuery is deprecated. Use ->pluck($value, $key) instead.');

            /** @phpstan-ignore-next-line */
            return $this->pluck($this->query->getColumns()[1], $this->query->getColumns()[0])->all();
        };
    }

    public function addOrderBy(): Closure
    {
        return function($columns) {
            LegacyUsage::record('ElementQuery-addOrderBy', 'Calling ->addOrderBy on an ElementQuery is deprecated. Use ->orderBy($column) instead.');

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
            LegacyUsage::record('ElementQuery-afterPopulate', 'Calling ->afterPopulate on an ElementQuery is deprecated.');

            /** @phpstan-ignore-next-line */
            return $this->hydrate($elements);
        };
    }

    public function andWhere(): Closure
    {
        return function($condition) {
            LegacyUsage::record('ElementQuery-andWhere', 'Calling ->andWhere on an ElementQuery is deprecated. Use ->where() instead.');

            [$sql, $bindings] = buildCondition($condition);

            if ($sql === '') {
                return $this;
            }

            /** @phpstan-ignore-next-line */
            return $this->whereRaw("($sql)", $bindings);
        };
    }

    public function filterWhere(): Closure
    {
        return function($condition) {
            LegacyUsage::record('ElementQuery-filterWhere', 'Calling ->filterWhere on an ElementQuery is deprecated.');

            $condition = (new Query())->filterWhere($condition)->where ?? [];

            if ($condition === []) {
                return $this;
            }

            [$sql, $bindings] = buildCondition($condition);

            if ($sql === '') {
                return $this;
            }

            /** @phpstan-ignore-next-line */
            $this->query->wheres = [];
            /** @phpstan-ignore-next-line */
            $this->query->bindings['where'] = [];

            /** @phpstan-ignore-next-line */
            return $this->whereRaw("($sql)", $bindings);
        };
    }

    public function andFilterWhere(): Closure
    {
        return function($condition) {
            LegacyUsage::record('ElementQuery-andFilterWhere', 'Calling ->andFilterWhere on an ElementQuery is deprecated. Use ->where() instead.');

            $condition = (new Query())->filterWhere($condition)->where ?? [];

            if ($condition === []) {
                return $this;
            }

            [$sql, $bindings] = buildCondition($condition);

            if ($sql === '') {
                return $this;
            }

            /** @phpstan-ignore-next-line */
            return $this->whereRaw("($sql)", $bindings);
        };
    }

    public function orFilterWhere(): Closure
    {
        return function($condition) {
            LegacyUsage::record('ElementQuery-orFilterWhere', 'Calling ->orFilterWhere on an ElementQuery is deprecated. Use ->orWhere() instead.');

            $condition = (new Query())->filterWhere($condition)->where ?? [];

            if ($condition === []) {
                return $this;
            }

            [$sql, $bindings] = buildCondition($condition);

            if ($sql === '') {
                return $this;
            }

            /** @phpstan-ignore-next-line */
            return $this->orWhereRaw("($sql)", $bindings);
        };
    }

    public function emulateExecution(): Closure
    {
        return function($value = true) {
            LegacyUsage::record('ElementQuery-emulateExecution', 'Calling ->emulateExecution on an ElementQuery is deprecated.');

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
     * Craft 5's `joinElementTable()`, which has no Craft 6 equivalent to defer to.
     *
     * Throws rather than joining the table, because doing half the job here would
     * be worse than doing none: Craft 5 callers paired this join with conditions
     * staged on `$subQuery`, which no longer exists, so the join alone would
     * satisfy the ORDER BY and quietly return unfiltered rows. A query that
     * can't be honoured should fail, not lie.
     */
    public function joinElementTable(): Closure
    {
        return function(string $table) {
            LegacyUsage::record(
                'ElementQuery-joinElementTable',
                'Calling ->joinElementTable on an ElementQuery is deprecated. Declare the table with the `$table` property on the element query class instead.',
            );

            throw new NotSupportedException(sprintf(
                'joinElementTable(\'%s\') is not supported as of Craft 6. Declare the element’s table with the `$table` property on the element query class, which joins it automatically, and apply joins and conditions to `$query`.',
                $table,
            ));
        };
    }
}
