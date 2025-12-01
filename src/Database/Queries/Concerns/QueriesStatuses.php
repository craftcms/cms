<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns;

use Closure;
use craft\base\Element;
use CraftCms\Cms\Database\Queries\ElementQuery;
use CraftCms\Cms\Database\Queries\Exceptions\QueryAbortedException;
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
        $this->beforeQuery(function (ElementQuery $elementQuery) {
            if ($elementQuery->archived) {
                $elementQuery->subQuery->where('elements.archived', true);

                return;
            }

            $this->applyStatusParam($elementQuery);

            // only set archived=false if 'archived' doesn't show up in the status param
            // (_applyStatusParam() will normalize $this->status to an array if applicable)
            if (! is_array($elementQuery->status) || ! in_array($elementQuery->elementType::STATUS_ARCHIVED, $elementQuery->status)) {
                $elementQuery->subQuery->where('elements.archived', false);
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

    /**
     * Applies the 'status' param to the query being prepared.
     *
     * @throws QueryAbortedException
     */
    private function applyStatusParam(ElementQuery $elementQuery): void
    {
        if (! $elementQuery->status || ! $elementQuery->elementType::hasStatuses()) {
            return;
        }

        // Normalize the status param
        if (! is_array($elementQuery->status)) {
            $elementQuery->status = str($elementQuery->status)->explode(',')->all();
        }

        $statuses = array_merge($elementQuery->status);
        $firstVal = strtolower((string) reset($statuses));
        $glue = 'or';

        if (in_array($firstVal, ['not', 'or'])) {
            $glue = $firstVal;
            array_shift($statuses);
        }

        if (! $statuses) {
            return;
        }

        if ($negate = ($glue === 'not')) {
            $glue = 'and';
        }

        $elementQuery->subQuery->where(function (Builder $query) use ($statuses, $negate, $glue) {
            foreach ($statuses as $status) {
                match (true) {
                    $glue === 'or' && $negate === false => $query->orWhere($this->placeholderCondition($this->statusCondition($status))),
                    $glue === 'or' && $negate === true => $query->orWhereNot($this->placeholderCondition($this->statusCondition($status))),
                    $negate === false => $query->where($this->placeholderCondition($this->statusCondition($status))),
                    $negate === true => $query->whereNot($this->placeholderCondition($this->statusCondition($status))),
                };
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
     * @throws \CraftCms\Cms\Database\Queries\Exceptions\QueryAbortedException on unsupported status.
     */
    protected function statusCondition(string $status): Closure
    {
        $status = strtolower($status);

        return match ($status) {
            Element::STATUS_ENABLED => fn (Builder $q) => $q->where('elements.enabled', true)->where('elements_sites.enabled', true),
            Element::STATUS_DISABLED => fn (Builder $q) => $q->where('elements.enabled', false)->orWhere('elements_sites.enabled', false),
            Element::STATUS_ARCHIVED => fn (Builder $q) => $q->where('elements.archived', true),
            default => throw new QueryAbortedException('Unsupported status: '.$status),
        };
    }
}
