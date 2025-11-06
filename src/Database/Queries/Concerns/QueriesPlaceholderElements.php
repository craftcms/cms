<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns;

use Closure;
use Illuminate\Database\Query\Builder;

/**
 * @mixin \CraftCms\Cms\Database\Queries\ElementQuery
 *
 * @internal
 */
trait QueriesPlaceholderElements
{
    /**
     * @var bool Whether to ignore placeholder elements when populating the results.
     *
     * @used-by ignorePlaceholders()
     */
    public bool $ignorePlaceholders = false;

    /**
     * @var Closure(Builder): Builder|false|null The placeholder condition for this query.
     *
     * @see placeholderCondition()
     */
    private Closure|false|null $placeholderCondition = null;

    /**
     * @var mixed The [[siteId]] param used at the time the placeholder condition was generated.
     *
     * @see placeholderCondition()
     */
    private mixed $placeholderSiteIds = null;

    /**
     * {@inheritdoc}
     *
     * @uses $ignorePlaceholders
     */
    public function ignorePlaceholders(bool $value = true): static
    {
        $this->ignorePlaceholders = $value;

        return $this;
    }

    /**
     * Combines the given condition with an alternative condition if there are any relevant placeholder elements.
     *
     * @param  Closure(Builder): Builder  $condition
     * @return Closure(Builder): Builder
     */
    protected function placeholderCondition(Closure $condition): Closure
    {
        if ($this->ignorePlaceholders) {
            return $condition;
        }

        if (! isset($this->placeholderCondition) || $this->siteId !== $this->placeholderSiteIds) {
            $placeholderSourceIds = [];
            $placeholderElements = \Craft::$app->getElements()->getPlaceholderElements();
            if (! empty($placeholderElements)) {
                $siteIds = array_flip((array) $this->siteId);
                foreach ($placeholderElements as $element) {
                    if ($element instanceof $this->elementType && isset($siteIds[$element->siteId])) {
                        $placeholderSourceIds[] = $element->getCanonicalId();
                    }
                }
            }

            if (! empty($placeholderSourceIds)) {
                $this->placeholderCondition = fn (Builder $q) => $q->whereIn('elements.id', $placeholderSourceIds);
            } else {
                $this->placeholderCondition = false;
            }
            $this->placeholderSiteIds = is_array($this->siteId) ? array_merge($this->siteId) : $this->siteId;
        }

        if ($this->placeholderCondition === false) {
            return $condition;
        }

        return fn (Builder $q) => $q->where($condition($q))->orWhere($this->placeholderCondition);
    }
}
