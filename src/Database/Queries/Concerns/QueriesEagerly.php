<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns;

use craft\base\ElementInterface;
use craft\elements\db\EagerLoadPlan;
use Illuminate\Support\Collection;

/**
 * @mixin \CraftCms\Cms\Database\Queries\ElementQuery
 *
 * @internal
 */
trait QueriesEagerly
{
    /**
     * @var string|array|null The eager-loading declaration.
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
     * @var bool Whether the query should be used to eager-load results for the [[$eagerSourceElement|source element]]
     *           and any other elements in its collection.
     *
     * @used-by eagerly()
     */
    public bool $eagerly = false;

    protected function initQueriesEagerly(): void
    {
        $this->afterQuery(function (Collection $elements) {
            if (! $this->with) {
                return $elements;
            }

            $elementsService = \Craft::$app->getElements();
            $elementsService->eagerLoadElements($this->elementType, $elements->all(), $this->with);

            return $elements;
        });
    }

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
    public function with(array|string|null $value): static
    {
        if (is_null($value)) {
            $this->with = null;

            return $this;
        }

        $this->with ??= [];

        if (is_string($this->with)) {
            $this->with = str($this->with)->explode(',')->all();
        }

        if (! is_array($value)) {
            $value = str($value)->explode(',')->all();
        }

        $this->with = array_merge($this->with, $value);

        return $this;
    }

    /**
     * Causes the query to return matching {elements} eager-loaded with related elements, in addition to the elements that were already specified by [[with()]]..
     */
    public function andWith(array|string|null $value): static
    {
        if (! is_null($value)) {
            return $this;
        }

        return $this->with($value);
    }

    /**
     * Causes the query to be used to eager-load results for the query’s source element
     * and any other elements in its collection.
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

    protected function eagerLoad(bool $count = false, array $criteria = []): Collection|int|null
    {
        if (
            ! $this->eagerly ||
            ! isset($this->eagerLoadSourceElement->elementQueryResult, $this->eagerLoadHandle) ||
            count($this->eagerLoadSourceElement->elementQueryResult) < 2
        ) {
            return null;
        }

        $alias = $this->eagerLoadAlias ?? "eagerly:$this->eagerLoadHandle";

        // see if it was already eager-loaded
        $eagerLoaded = match ($count) {
            true => $this->wasCountEagerLoaded($alias),
            false => $this->wasEagerLoaded($alias),
        };

        if (! $eagerLoaded) {
            \Craft::$app->getElements()->eagerLoadElements(
                $this->eagerLoadSourceElement::class,
                $this->eagerLoadSourceElement->elementQueryResult,
                [
                    new EagerLoadPlan([
                        'handle' => $this->eagerLoadHandle,
                        'alias' => $alias,
                        'criteria' => $criteria + $this->getCriteria() + ['with' => $this->with],
                        'all' => ! $count,
                        'count' => $count,
                        'lazy' => true,
                    ]),
                ],
            );
        }

        if ($count) {
            return $this->eagerLoadSourceElement->getEagerLoadedElementCount($alias);
        }

        return $this->eagerLoadSourceElement->getEagerLoadedElements($alias);
    }
}
