<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Element\Queries\Exceptions\QueryAbortedException;
use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use Illuminate\Database\Query\Builder;

/**
 * @internal
 */
trait QueriesStatuses
{
    use QueriesPlaceholderElements;

    /**
     * @var bool Whether to return only archived elements.
     *
     * @used-by archived()
     */
    public bool $archived = false;

    /**
     * @var string|string[]|null The status(es) that the resulting elements must have.
     *
     * @used-by status()
     */
    public array|string|null $status = [
        Element::STATUS_ENABLED,
    ];

    protected function initQueriesStatuses(): void
    {
        $this->beforeQuery(static function (ElementQuery $elementQuery) {
            if ($elementQuery->archived) {
                $elementQuery->whereBool('elements.archived', true);

                return;
            }

            if ($elementQuery->elementType::hasStatuses()) {
                static::applyStatus($elementQuery, $elementQuery->status, $elementQuery);
            }

            // only set archived=false if 'archived' doesn't show up in the status param
            // (applyStatus() will normalize $elementQuery->status to an array if applicable)
            if (! is_array($elementQuery->status) || ! in_array($elementQuery->elementType::STATUS_ARCHIVED, $elementQuery->status)) {
                $elementQuery->whereBool('elements.archived', false);
            }
        });
    }

    public function archived(bool $value = true): static
    {
        $this->archived = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the {elements}’ statuses.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `'enabled'` _(default)_ | that are enabled.
     * | `'disabled'` | that are disabled.
     * | `['not', 'disabled']` | that are not disabled.
     *
     * ---
     *
     * ```twig
     * {# Fetch disabled {elements} #}
     * {% set {elements-var} = {twig-method}
     *   .status('disabled')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch disabled {elements}
     * ${elements-var} = {php-method}
     *     ->status('disabled')
     *     ->all();
     * ```
     *
     * @param  string|string[]|null  $value  The property value
     */
    public function status(array|string|null $value): static
    {
        $this->status = $value;

        return $this;
    }

    private function normalizeStatus(): void
    {
        if (! $this->status) {
            return;
        }

        if (! is_array($this->status)) {
            $this->status = str($this->status)->explode(',')->all();
        }
    }

    /**
     * Applies the 'status' param to the query being prepared.
     *
     * @param  ElementQuery<ElementInterface>  $elementQuery
     *
     * @throws QueryAbortedException
     */
    public static function applyStatus(BuilderContract $query, mixed $value, ElementQuery $elementQuery): void
    {
        if (! $value) {
            return;
        }

        if (! is_array($value)) {
            $value = str($value)->explode(',')->all();
        }

        $statuses = array_merge($value);
        $firstVal = strtolower((string) reset($statuses));
        $glue = 'or';

        if (in_array($firstVal, ['not', 'or'])) {
            $glue = $firstVal;
            array_shift($statuses);
        }

        if (! $statuses) {
            return;
        }

        $query->where(function (Builder $q) use ($elementQuery, $statuses, $glue) {
            foreach ($statuses as $status) {
                if ($glue === 'not') {
                    $q->whereNot($elementQuery->placeholderCondition($elementQuery->statusCondition($status)));
                } else {
                    $q->orWhere($elementQuery->placeholderCondition($elementQuery->statusCondition($status)));
                }
            }
        });
    }

    /**
     * Returns the condition that should be applied to the element query for a given status.
     *
     * For example, if you support a status called “pending”, which maps back to a `pending` database column that will
     * either be 0 or 1, this method could do this:
     *
     * ```php
     * protected function statusCondition($status)
     * {
     *     switch ($status) {
     *         case 'pending':
     *             return fn (Builder $q) => $q->where('mytable.pending', true);
     *         default:
     *             return parent::statusCondition($status);
     *     }
     * ```
     *
     * @param  string  $status  The status
     * @return Closure(Builder): Builder The status condition, or false if $status is an unsupported status
     *
     * @throws QueryAbortedException on unsupported status.
     */
    protected function statusCondition(string $status): Closure
    {
        $status = strtolower($status);

        return match ($status) {
            Element::STATUS_ENABLED => fn (Builder $q) => $q->whereBool('elements.enabled', true)->whereBool('elements_sites.enabled', true),
            Element::STATUS_DISABLED => fn (Builder $q) => $q->whereBool('elements.enabled', false)->orWhereBool('elements_sites.enabled', false),
            Element::STATUS_ARCHIVED => fn (Builder $q) => $q->whereBool('elements.archived', true),
            default => throw new QueryAbortedException('Unsupported status: '.$status),
        };
    }
}
