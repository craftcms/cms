<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Data\EagerLoadPlan;
use CraftCms\Cms\Support\Facades\Elements;
use Illuminate\Database\Query\Builder;
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

    private function queryState(Builder $query): string
    {
        return hash('sha256', $query->toSql().serialize($query->getBindings()));
    }
}
