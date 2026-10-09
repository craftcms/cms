<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns;

use CraftCms\Cms\Database\ElementRelationParamFilter;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Support\Arr;
use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use Illuminate\Database\Query\Builder;
use RuntimeException;

/**
 * @internal
 */
trait QueriesRelatedElements
{
    /**
     * @var mixed The element relation criteria.
     *
     * See [Relations](https://craftcms.com/docs/5.x/system/relations.html) for supported syntax options.
     *
     * @used-by relatedTo()
     */
    public mixed $relatedTo = null;

    /**
     * @var mixed The element relation criteria.
     *
     * See [Relations](https://craftcms.com/docs/5.x/system/relations.html) for supported syntax options.
     *
     * @used-by notRelatedTo()
     */
    public mixed $notRelatedTo = null;

    protected function initQueriesRelatedElements(): void
    {
        $this->beforeQuery(static function (ElementQuery $elementQuery) {
            static::applyRelatedTo($elementQuery, $elementQuery->relatedTo, $elementQuery);
            static::applyNotRelatedTo($elementQuery, $elementQuery->notRelatedTo, $elementQuery);
        });
    }

    /** @param ElementQuery<ElementInterface> $elementQuery */
    public static function applyRelatedTo(BuilderContract $query, mixed $value, ElementQuery $elementQuery): void
    {
        if (! $value) {
            return;
        }

        $applied = new ElementRelationParamFilter(
            fields: $elementQuery->customFields
                ? Arr::keyBy(
                    $elementQuery->customFields,
                    fn (FieldInterface $field) => $field->layoutElement?->getOriginalHandle() ?? $field->handle,
                )
                : []
        )->apply(
            query: $query,
            relatedToParam: $value,
            siteId: $elementQuery->siteId !== '*' ? $elementQuery->siteId : null
        );

        if (! $applied) {
            $query->whereRaw('0 = 1');

            return;
        }
    }

    /** @param ElementQuery<ElementInterface> $elementQuery */
    public static function applyNotRelatedTo(BuilderContract $query, mixed $value, ElementQuery $elementQuery): void
    {
        if (! $value) {
            return;
        }

        $query->whereNot(function (Builder $q) use ($elementQuery, $value) {
            new ElementRelationParamFilter(
                fields: $elementQuery->customFields
                    ? Arr::keyBy(
                        $elementQuery->customFields,
                        fn (FieldInterface $field) => $field->layoutElement?->getOriginalHandle() ?? $field->handle,
                    )
                    : []
            )->apply(
                query: $q,
                relatedToParam: $value,
                siteId: $elementQuery->siteId !== '*' ? $elementQuery->siteId : null,
                matchNoneWhenInvalid: false,
            );
        });
    }

    /**
     * Narrows the query results to only {elements} that are not related to certain other elements.
     *
     * See [Relations](https://craftcms.com/docs/5.x/system/relations.html) for a full explanation of how to work with this parameter.
     *
     * ---
     *
     * ```twig
     * {# Fetch all {elements} that are related to myEntry #}
     * {% set {elements-var} = {twig-method}
     *   .notRelatedTo(myEntry)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch all {elements} that are related to $myEntry
     * ${elements-var} = {php-method}
     *     ->notRelatedTo($myEntry)
     *     ->all();
     * ```
     */
    public function notRelatedTo($value): static
    {
        $this->notRelatedTo = $value;

        return $this;
    }

    /**
     * Narrows the query results to only {elements} that are not related to certain other elements.
     *
     * See [Relations](https://craftcms.com/docs/5.x/system/relations.html) for a full explanation of how to work with this parameter.
     *
     * ---
     *
     * ```twig
     * {# Fetch all {elements} that are related to myCategoryA and not myCategoryB #}
     * {% set {elements-var} = {twig-method}
     *   .relatedTo(myCategoryA)
     *   .andNotRelatedTo(myCategoryB)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch all {elements} that are related to $myCategoryA and not $myCategoryB
     * ${elements-var} = {php-method}
     *     ->relatedTo($myCategoryA)
     *     ->andNotRelatedTo($myCategoryB)
     *     ->all();
     * ```
     */
    public function andNotRelatedTo($value): static
    {
        $relatedTo = $this->_andRelatedToCriteria($value, $this->notRelatedTo);

        if ($relatedTo === false) {
            return $this;
        }

        return $this->notRelatedTo($relatedTo);
    }

    /**
     * Narrows the query results to only {elements} that are related to certain other elements.
     *
     * See [Relations](https://craftcms.com/docs/5.x/system/relations.html) for a full explanation of how to work with this parameter.
     *
     * ---
     *
     * ```twig
     * {# Fetch all {elements} that are related to myCategory #}
     * {% set {elements-var} = {twig-method}
     *   .relatedTo(myCategory)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch all {elements} that are related to $myCategory
     * ${elements-var} = {php-method}
     *     ->relatedTo($myCategory)
     *     ->all();
     * ```
     */
    public function relatedTo($value): static
    {
        $this->relatedTo = $value;

        return $this;
    }

    /**
     * Narrows the query results to only {elements} that are related to certain other elements.
     *
     * See [Relations](https://craftcms.com/docs/5.x/system/relations.html) for a full explanation of how to work with this parameter.
     *
     * ---
     *
     * ```twig
     * {# Fetch all {elements} that are related to myCategoryA and myCategoryB #}
     * {% set {elements-var} = {twig-method}
     *   .relatedTo(myCategoryA)
     *   .andRelatedTo(myCategoryB)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch all {elements} that are related to $myCategoryA and $myCategoryB
     * ${elements-var} = {php-method}
     *     ->relatedTo($myCategoryA)
     *     ->andRelatedTo($myCategoryB)
     *     ->all();
     * ```
     */
    public function andRelatedTo($value): static
    {
        $relatedTo = $this->_andRelatedToCriteria($value, $this->relatedTo);

        if ($relatedTo === false) {
            return $this;
        }

        return $this->relatedTo($relatedTo);
    }

    private function _andRelatedToCriteria(mixed $value, mixed $currentValue): mixed
    {
        if (! $value) {
            return false;
        }

        if (! $currentValue) {
            return $value;
        }

        // Normalize so element/targetElement/sourceElement values get pushed down to the 2nd level
        $relatedTo = ElementRelationParamFilter::normalizeRelatedToParam($currentValue);
        $criteriaCount = count($relatedTo) - 1;

        // Not possible to switch from `or` to `and` if there are multiple criteria
        if ($relatedTo[0] === 'or' && $criteriaCount > 1) {
            throw new RuntimeException('It’s not possible to combine “or” and “and” relatedTo conditions.');
        }

        $relatedTo[0] = $criteriaCount > 0 ? 'and' : 'or';
        $relatedTo[] = ElementRelationParamFilter::normalizeRelatedToCriteria($value);

        return $relatedTo;
    }
}
