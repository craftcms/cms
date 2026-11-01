<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Expressions\OrderByPlaceholderExpression;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Data\EagerLoadPlan;
use CraftCms\Cms\Element\Enums\ElementQueryParamType;
use CraftCms\Cms\Element\Queries\Attributes\ElementQueryParam;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Contracts\Database\Query\Expression as ExpressionContract;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;
use Throwable;

/**
 * @internal
 */
trait QueriesEagerly
{
    /**
     * @var string|array<array-key, mixed>|null The eager-loading declaration.
     *
     * See [Eager-Loading Elements](https://craftcms.com/docs/5.x/development/eager-loading.html) for supported syntax options.
     *
     * @used-by with()
     * @used-by andWith()
     */
    public array|string|null $with = null;

    /**
     * @var ElementInterface|null The source element that this query is fetching relations for.
     */
    public ?ElementInterface $eagerLoadSourceElement = null;

    /**
     * @var string|null The handle that could be used to eager-load the query's target elmeents.
     */
    public ?string $eagerLoadHandle = null;

    /**
     * @var string|null The eager-loading alias that should be used.
     */
    public ?string $eagerLoadAlias = null;

    /**
     * @var bool|null Whether the query should be used to eager-load results for the [[$eagerSourceElement|source element]]
     *                and any other elements in its collection. If `null`, the <config5:autoEagerLoadElements> config setting will be used.
     *
     * @used-by eagerly()
     */
    public ?bool $eagerly = null;

    /** @var string[] */
    private array $eagerLoadCriteriaExclusions = [
        'eagerLoadAlias',
        'eagerLoadHandle',
        'eagerLoadSourceElement',
        'queryCacheDependency',
        'queryCacheDuration',
        'ruleset',
    ];

    private ?string $eagerLoadQueryState = null;

    private int $eagerLoadBeforeQueryCallbackCount = 0;

    /**
     * The query's orders at the time {@see prepForEagerLoading()} was called.
     *
     * @var array<int, mixed>|null
     *
     * @see eagerLoad()
     */
    private ?array $eagerLoadOrders = null;

    /**
     * Causes the query to return matching {elements} eager-loaded with related elements.
     *
     * See [Eager-Loading Elements](https://craftcms.com/docs/5.x/development/eager-loading.html) for a full explanation of how to work with this parameter.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} eager-loaded with the "Related" field’s relations #}
     * {% set {elements-var} = {twig-method}
     *   .with(['related'])
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} eager-loaded with the "Related" field’s relations
     * ${elements-var} = {php-method}
     *     ->with(['related'])
     *     ->all();
     * ```
     */
    /** @param array<array-key, mixed>|string|null $value */
    #[ElementQueryParam(ElementQueryParamType::StringList, 'Eager-loads the given handles or paths for the returned elements.')]
    public function with(array|string|null $value): static
    {
        $this->with = $value;

        return $this;
    }

    /**
     * Causes the query to return matching {elements} eager-loaded with related elements, in addition to the elements that were already specified by [[with()]]..
     */
    /** @param array<array-key, mixed>|string|null $value */
    public function andWith(array|string|null $value): static
    {
        if (empty($this->with)) {
            $this->with = [$value];

            return $this;
        }

        if (is_string($this->with)) {
            $this->with = str($this->with)->explode(',')->all();
        }

        $this->with[] = $value;

        return $this;
    }

    /**
     * Controls whether the query should eager-load results for the query’s source element
     * and any other elements in its collection. Pass `false` to disable automatic eager loading.
     *
     * @param  string|bool  $value  The property value. If a string, the value will be used as the eager-loading alias.
     */
    public function eagerly(string|bool $value = true): static
    {
        $this->eagerly = $value !== false;
        $this->eagerLoadAlias = is_string($value) ? $value : null;

        return $this;
    }

    /**
     * Prepares the query for lazy eager loading.
     *
     * @param  string  $handle  The eager loading handle the query is for
     * @param  ElementInterface  $sourceElement  One of the source elements the query is fetching elements for
     */
    public function prepForEagerLoading(string $handle, ElementInterface $sourceElement): static
    {
        // Prefix the handle with the provider's handle, if there is one
        $providerHandle = $sourceElement->getFieldLayout()?->provider?->getHandle();

        $this->eagerLoadHandle = $providerHandle ? "$providerHandle:$handle" : $handle;
        $this->eagerLoadSourceElement = $sourceElement;
        $this->eagerLoadQueryState = $this->queryState($this->getQuery());
        $this->eagerLoadBeforeQueryCallbackCount = count($this->beforeQueryCallbacks);

        // Remember the current orders, so eagerLoad() can tell whether they were customized afterward
        $this->eagerLoadOrders = $this->getQuery()->orders;

        return $this;
    }

    /** @param string[]|string $criteria */
    public function excludeEagerLoadCriteria(array|string $criteria): static
    {
        $criteria = is_array($criteria) ? $criteria : [$criteria];
        $this->eagerLoadCriteriaExclusions = array_values(array_unique([
            ...$this->eagerLoadCriteriaExclusions,
            ...$criteria,
        ]));

        return $this;
    }

    /**
     * Returns whether the query results were already eager loaded by the query's source element.
     */
    public function wasEagerLoaded(?string $alias = null): bool
    {
        if (! isset($this->eagerLoadHandle, $this->eagerLoadSourceElement)) {
            return false;
        }

        if ($alias !== null) {
            return $this->eagerLoadSourceElement->hasEagerLoadedElements($alias);
        }

        $planHandle = $this->eagerLoadHandle;
        if (str_contains((string) $planHandle, ':')) {
            $planHandle = explode(':', (string) $planHandle, 2)[1];
        }

        return $this->eagerLoadSourceElement->hasEagerLoadedElements($planHandle);
    }

    /**
     * Returns whether the query result count was already eager loaded by the query's source element.
     */
    public function wasCountEagerLoaded(?string $alias = null): bool
    {
        if (! isset($this->eagerLoadHandle, $this->eagerLoadSourceElement)) {
            return false;
        }

        if ($alias !== null) {
            return $this->eagerLoadSourceElement->getEagerLoadedElementCount($alias) !== null;
        }

        $planHandle = $this->eagerLoadHandle;
        if (str_contains((string) $planHandle, ':')) {
            $planHandle = explode(':', (string) $planHandle, 2)[1];
        }

        return $this->eagerLoadSourceElement->getEagerLoadedElementCount($planHandle) !== null;
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @param  array<int, string>|string  $columns
     * @return Collection<int, ElementInterface>|int|null
     */
    protected function eagerLoad(bool $count = false, array $criteria = [], array|string $columns = ['*']): Collection|int|null
    {
        $automatically = $this->eagerly === null;

        if (
            $this->eagerly === false ||
            ($automatically && ! Cms::config()->autoEagerLoadElements) ||
            ($automatically && (request()->isCpRequest() || TemplateMode::is(TemplateMode::Cp))) ||
            ($automatically && ($this->asArray || ! in_array($columns, ['*', ['*']], true) || $this->getResultOverride() !== null)) ||
            ! isset($this->eagerLoadSourceElement->elementQueryResult, $this->eagerLoadHandle) ||
            count($this->eagerLoadSourceElement->elementQueryResult) < 2
        ) {
            return null;
        }

        if (
            $automatically &&
            (
                $this->eagerLoadQueryState !== $this->queryState($this->queryBeforePrepare ?? $this->getQuery()) ||
                $this->eagerLoadBeforeQueryCallbackCount !== count($this->beforeQueryCallbacksBeforePrepare ?? $this->beforeQueryCallbacks)
            )
        ) {
            return null;
        }

        $criteria += array_diff_key(
            $this->getCriteria(),
            array_flip($this->eagerLoadCriteriaExclusions),
        ) + ['with' => $this->with];

        if ($this->eagerLoadAlias !== null) {
            $alias = $this->eagerLoadAlias;
        } elseif ($automatically) {
            try {
                $alias = sprintf('eagerly:%s:%s', $this->eagerLoadHandle, hash('sha256', serialize($criteria)));
            } catch (Throwable) {
                return null;
            }
        } else {
            $alias = "eagerly:$this->eagerLoadHandle";
        }

        // see if it was already eager-loaded
        $eagerLoaded = match ($count) {
            true => $this->wasCountEagerLoaded($alias),
            false => $this->wasEagerLoaded($alias),
        };

        if (! $eagerLoaded) {
            $query = $this->queryBeforePrepare ?? $this->getQuery();

            if (($orderBy = $this->eagerLoadOrderBy($query)) !== null) {
                $criteria += ['orderBy' => $orderBy];
            }

            // `offset` and `limit` aren't included in getCriteria() either
            $criteria += array_filter([
                'offset' => $query->offset,
                'limit' => $query->limit,
            ], fn ($value) => $value !== null);

            Elements::eagerLoadElements(
                $this->eagerLoadSourceElement::class,
                $this->eagerLoadSourceElement->elementQueryResult,
                [
                    new EagerLoadPlan(
                        handle: $this->eagerLoadHandle,
                        alias: $alias,
                        criteria: $criteria,
                        all: ! $count,
                        count: $count,
                        lazy: true,
                    ),
                ],
            );
        }

        if ($count) {
            return $this->eagerLoadSourceElement->getEagerLoadedElementCount($alias);
        }

        return $this->eagerLoadSourceElement->getEagerLoadedElements($alias);
    }

    /**
     * Returns the query's orders as `orderBy` criteria for an eager-loading plan, if they were customized after
     * {@see prepForEagerLoading()} was called.
     *
     * Orders that were in place by then only apply to this query's source element, and the eager-loading map's order
     * is used in their place. They also take precedence over any orders added after them, so those are only passed
     * along if the original orders were replaced (e.g. via `reorder()`). A default order held by the order
     * placeholder (e.g. a relation field's fixed order) is replaced by any added orders, so those are passed along.
     *
     * Raw orders are passed as expressions. Returns `null` if any of them can't be carried over to another query,
     * such as raw orders with bindings, or subquery orders.
     *
     * @return list<array{0: string|ExpressionContract, 1: 'asc'|'desc'|null}>|null
     */
    private function eagerLoadOrderBy(Builder $query): ?array
    {
        $withoutPlaceholder = fn (?array $orders) => array_values(array_filter(
            $orders ?? [],
            fn (array $order) => ! ($order['column'] ?? null) instanceof OrderByPlaceholderExpression,
        ));

        $orders = $withoutPlaceholder($query->orders);
        $prepOrders = $withoutPlaceholder($this->eagerLoadOrders);

        if (
            $orders === [] ||
            ($prepOrders !== [] && array_slice($orders, 0, count($prepOrders)) === $prepOrders) ||
            ! empty($query->getRawBindings()['order'])
        ) {
            return null;
        }

        $orderBy = [];

        foreach ($orders as $order) {
            if (($order['type'] ?? null) === 'Raw') {
                $orderBy[] = [new Expression($order['sql']), null];
            } elseif (isset($order['column'], $order['direction']) && (is_string($order['column']) || $order['column'] instanceof ExpressionContract)) {
                $orderBy[] = [$order['column'], $order['direction']];
            } else {
                return null;
            }
        }

        return $orderBy;
    }

    private function queryState(Builder $query): string
    {
        return hash('sha256', $query->toSql().serialize($query->getBindings()));
    }
}
