<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns;

use CraftCms\Cms\Database\Expressions\FixedOrderExpression;
use CraftCms\Cms\Database\Expressions\OrderByPlaceholderExpression;
use CraftCms\Cms\Database\Queries\ElementQuery;
use CraftCms\Cms\Database\Queries\Exceptions\QueryAbortedException;
use Illuminate\Database\Query\Expression;
use Tpetry\QueryExpressions\Language\CaseGroup;
use Tpetry\QueryExpressions\Language\CaseRule;
use Tpetry\QueryExpressions\Operator\Comparison\Equal;
use Tpetry\QueryExpressions\Operator\Logical\CondAnd;
use Tpetry\QueryExpressions\Value\Value;
use yii\base\InvalidValueException;

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

    public function ids(): array
    {
        return $this->pluck('elements.id')->all();
    }

    protected function initFormatsResults(): void
    {
        $this->query->orderBy(new OrderByPlaceholderExpression);
    }

    protected function applyOrderByParams(ElementQuery $elementQuery): void
    {
        $this->orderBySearchResults($elementQuery);
        $this->applyDefaultOrder($elementQuery);

        if ($elementQuery->inReverse) {
            $orders = $elementQuery->query->orders;

            $elementQuery->query->reorder();

            foreach (array_reverse($orders) as $order) {
                // If it's an expression we can't reverse it
                if ($order['column'] instanceof Expression) {
                    $elementQuery->query->orderBy($order['column']);

                    continue;
                }

                $elementQuery->query->orderBy($order['column'], $order['direction'] === 'asc' ? 'desc' : 'asc');
            }
        }

        $this->parseOrderColumnMappings($elementQuery);
    }

    private function applyDefaultOrder(ElementQuery $elementQuery): void
    {
        $orders = $elementQuery->query->orders;

        if (is_null($orders)) {
            return;
        }

        $elementQuery->query->orders = array_filter(
            array: $orders,
            callback: fn ($order) => ! $order['column'] instanceof OrderByPlaceholderExpression,
        );

        // Order by was set
        if (count($elementQuery->query->orders) > 0) {
            return;
        }

        if ($elementQuery->fixedOrder) {
            throw_if(empty($elementQuery->id), QueryAbortedException::class);

            if (! is_array($ids = $elementQuery->id)) {
                $ids = is_string($ids) ? str($ids)->explode(',')->all() : [$ids];
            }

            $elementQuery->query->orderBy(new FixedOrderExpression('elements.id', $ids));

            return;
        }

        if ($elementQuery->revisions) {
            $elementQuery->query->orderByDesc('num');

            return;
        }

        if ($elementQuery->shouldJoinStructureData()) {
            $elementQuery->query->orderBy('structureelements.lft');
        }

        foreach ($elementQuery->defaultOrderBy as $column => $direction) {
            $elementQuery->query->orderBy($column, match ($direction) {
                SORT_ASC, 'asc' => 'asc',
                SORT_DESC, 'desc' => 'desc',
                default => throw new QueryAbortedException('Invalid sort direction: '.$direction),
            });
        }
    }

    private function parseOrderColumnMappings(ElementQuery $elementQuery): void
    {
        $orders = $elementQuery->query->orders;

        if (is_null($orders)) {
            return;
        }

        $elementQuery->query->orders = array_map(function ($order) use ($elementQuery) {
            if (! is_string($order['column'])) {
                return $order;
            }

            $order['column'] = $elementQuery->columnMap[$order['column']] ?? $order['column'];

            return $order;
        }, $orders);
    }

    private function orderBySearchResults(ElementQuery $elementQuery): void
    {
        $elementQuery->query->orders = array_filter(
            $elementQuery->query->orders ?? [],
            fn (array $order) => $order['column'] !== 'score',
        );

        if (! $elementQuery->searchResults) {
            return;
        }

        $keys = array_keys($elementQuery->searchResults);

        $i = -1;

        $rules = [];

        foreach ($keys as $i => $key) {
            [$elementId, $siteId] = array_pad(explode('-', (string) $key, 2), 2, null);

            if ($siteId === null) {
                throw new InvalidValueException("Invalid element search score key: \"$key\". Search scores should be indexed by element ID and site ID (e.g. \"100-1\").");
            }

            $rules[] = new CaseRule(
                result: new Value($i),
                condition: new CondAnd(
                    value1: new Equal('elements.id', new Value($elementId)),
                    value2: new Equal('elements_sites.siteId', new Value($siteId)),
                ),
            );
        }

        $elementQuery->query->orderBy(new CaseGroup($rules, new Value($i + 1)));
    }
}
