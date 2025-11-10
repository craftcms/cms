<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns;

use CraftCms\Cms\Database\Expressions\FixedOrderExpression;
use CraftCms\Cms\Database\Expressions\OrderByPlaceholderExpression;
use CraftCms\Cms\Database\Queries\ElementQuery;
use CraftCms\Cms\Database\Queries\Exceptions\QueryAbortedException;

/**
 * @mixin \CraftCms\Cms\Database\Queries\ElementQuery
 *
 * @internal
 */
trait FormatsResults
{
    /**
     * @var array The default [[orderBy]] value to use if [[orderBy]] is empty but not null.
     */
    protected array $defaultOrderBy = [
        'elements.dateCreated' => SORT_DESC,
        'elements.id' => SORT_DESC,
    ];

    /**
     * @var bool Whether the results should be queried in reverse.
     *
     * @used-by inReverse()
     */
    protected bool $inReverse = false;

    /**
     * @var bool Whether to return each element as an array. If false (default), an object
     *           of [[elementType]] will be created to represent each element.
     *
     * @used-by asArray()
     */
    public bool $asArray = false;

    /**
     * @var bool Whether results should be returned in the order specified by [[id]].
     *
     * @used-by fixedOrder()
     */
    public bool $fixedOrder = false;

    /**
     * Causes the query results to be returned in reverse order.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} in reverse #}
     * {% set {elements-var} = {twig-method}
     *   .inReverse()
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} in reverse
     * ${elements-var} = {php-method}
     *     ->inReverse()
     *     ->all();
     * ```
     *
     * @param  bool  $value  The property value
     * @return static self reference
     */
    public function inReverse(bool $value = true): static
    {
        $this->inReverse = $value;

        return $this;
    }

    /**
     * Causes the query to return matching {elements} as arrays of data, rather than [[{element-class}]] objects.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} as arrays #}
     * {% set {elements-var} = {twig-method}
     *   .asArray()
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} as arrays
     * ${elements-var} = {php-method}
     *     ->asArray()
     *     ->all();
     * ```
     *
     * @param  bool  $value  The property value (defaults to true)
     * @return static self reference
     */
    public function asArray(bool $value = true): static
    {
        $this->asArray = $value;

        return $this;
    }

    /**
     * Causes the query results to be returned in the order specified by [[id()]].
     *
     * ::: tip
     * If no IDs were passed to [[id()]], setting this to `true` will result in an empty result set.
     * :::
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} in a specific order #}
     * {% set {elements-var} = {twig-method}
     *   .id([1, 2, 3, 4, 5])
     *   .fixedOrder()
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} in a specific order
     * ${elements-var} = {php-method}
     *     ->id([1, 2, 3, 4, 5])
     *     ->fixedOrder()
     *     ->all();
     * ```
     *
     * @param  bool  $value  The property value (defaults to true)
     * @return static self reference
     */
    public function fixedOrder(bool $value = true): static
    {
        $this->fixedOrder = $value;

        return $this;
    }

    protected function initFormatsResults(): void
    {
        $this->query->orderBy(new OrderByPlaceholderExpression);

        $this->beforeQuery(function (ElementQuery $query) {
            $this->applyDefaultOrder($query);

            if ($this->inReverse) {
                $orders = $query->query->orders;

                $query->query->reorder();

                foreach (array_reverse($orders) as $order) {
                    $query->query->orderBy($order['column'], $order['direction'] === 'asc' ? 'desc' : 'asc');
                }
            }

            $this->parseOrderColumnMappings($query);
        });
    }

    private function applyDefaultOrder(ElementQuery $query): void
    {
        $orders = $query->query->orders;

        if (is_null($orders)) {
            return;
        }

        $query->query->orders = array_filter(
            array: $orders,
            callback: fn ($order) => ! $order['column'] instanceof OrderByPlaceholderExpression,
        );

        // Order by was set
        if (count($query->query->orders) > 0) {
            return;
        }

        if ($this->fixedOrder) {
            throw_if(empty($this->id), QueryAbortedException::class);

            if (! is_array($ids = $this->id)) {
                $ids = is_string($ids) ? str($ids)->explode(',')->all() : [$ids];
            }

            $query->query->orderBy(new FixedOrderExpression('elements.id', $ids));

            return;
        }

        if ($this->revisions) {
            $query->query->orderByDesc('num');

            return;
        }

        if ($this->shouldJoinStructureData()) {
            $query->query->orderBy('structureelements.lft');

            foreach ($this->defaultOrderBy as $column => $direction) {
                $query->query->orderBy($column, $direction === SORT_ASC ? 'asc' : 'desc');
            }

            return;
        }

        foreach ($this->defaultOrderBy as $column => $direction) {
            $query->query->orderBy($column, match ($direction) {
                SORT_ASC, 'asc' => 'asc',
                SORT_DESC, 'desc' => 'desc',
                default => throw new QueryAbortedException('Invalid sort direction: '.$direction),
            });
        }
    }

    private function parseOrderColumnMappings(ElementQuery $query): void
    {
        $orders = $query->query->orders;

        if (is_null($orders)) {
            return;
        }

        $query->query->orders = array_map(function ($order) {
            if (! is_string($order['column'])) {
                return $order;
            }

            $order['column'] = $this->columnMap[$order['column']] ?? $order['column'];

            return $order;
        }, $orders);
    }
}
