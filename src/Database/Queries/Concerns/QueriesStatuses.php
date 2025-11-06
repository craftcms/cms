<?php

namespace CraftCms\Cms\Database\Queries\Concerns;

use Closure;
use craft\base\Element;
use CraftCms\Cms\Database\Queries\Exceptions\QueryAbortedException;
use Illuminate\Database\Query\Builder;

/**
 * @mixin \CraftCms\Cms\Database\Queries\ElementQuery
 */
trait QueriesStatuses
{
    /**
     * @var bool Whether to return only archived elements.
     * @used-by archived()
     */
    public bool $archived = false;

    /**
     * @var string|string[]|null The status(es) that the resulting elements must have.
     * @used-by status()
     */
    public array|string|null $status = [
        Element::STATUS_ENABLED,
    ];

    protected function initializeQueriesStatuses(): void
    {
        $this->query->beforeQuery(function () {
            if ($this->archived) {
                $this->subQuery->where('elements.archived', true);
                return;
            }

            $this->applyStatusParam();

            // only set archived=false if 'archived' doesn't show up in the status param
            // (_applyStatusParam() will normalize $this->status to an array if applicable)
            if (!is_array($this->status) || !in_array($this->elementType::STATUS_ARCHIVED, $this->status)) {
                $this->subQuery->where('elements.archived', false);
            }
        });
    }

    /**
     * @inheritdoc
     * @uses $archived
     */
    public function archived(bool $value = true): static
    {
        $this->archived = $value;

        return $this;
    }

    /**
     * @inheritdoc
     * @uses $status
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
    private function applyStatusParam(): void
    {
        if (!$this->status || !$this->elementType::hasStatuses()) {
            return;
        }

        // Normalize the status param
        if (! is_array($this->status)) {
            $this->status = str($this->status)->explode(',')->all();
        }

        $statuses = array_merge($this->status);

        $firstVal = strtolower(reset($statuses));
        if (in_array($firstVal, ['not', 'or'])) {
            $glue = $firstVal;
            array_shift($statuses);
            if (!$statuses) {
                return;
            }
        } else {
            $glue = 'or';
        }

        if ($negate = ($glue === 'not')) {
            $glue = 'and';
        }

        $this->subQuery->where(function (Builder $query) use ($statuses, $negate, $glue) {
            foreach ($statuses as $status) {
                $query->where(
                    column: $this->placeholderCondition($this->statusCondition($status)),
                    operator: $negate ? '!=' : '=',
                    boolean: $glue,
                );
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
     * @param string $status The status
     *
     * @return Closure(Builder): Builder The status condition, or false if $status is an unsupported status
     * @throws \CraftCms\Cms\Database\Queries\Exceptions\QueryAbortedException on unsupported status.
     */
    protected function statusCondition(string $status): Closure
    {
        $status = strtolower($status);

        return match ($status) {
            Element::STATUS_ENABLED => fn (Builder $q) => $q->where('elements.enabled', true)->where('elements_sites.enabled', true),
            Element::STATUS_DISABLED => fn (Builder $q) => $q->where('elements.enabled', false)->orWhere('elements_sites.enabled', false),
            Element::STATUS_ARCHIVED => fn (Builder $q) => $q->where('elements.archived', true),
            default => throw new QueryAbortedException('Unsupported status: ' . $status),
        };
    }
}
