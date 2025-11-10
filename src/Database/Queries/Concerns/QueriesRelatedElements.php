<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns;

use craft\base\FieldInterface;
use craft\db\QueryAbortedException;
use craft\elements\db\ElementRelationParamParser;
use CraftCms\Cms\Support\Arr;
use Illuminate\Database\Query\Builder;

/**
 * @mixin \CraftCms\Cms\Database\Queries\ElementQuery
 *
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
        $this->applyRelatedToParam();
        $this->applyNotRelatedToParam();
    }

    private function applyRelatedToParam(): void
    {
        if (! $this->relatedTo) {
            return;
        }

        $this->beforeQuery(function (Builder $query) {
            $parser = new ElementRelationParamParser([
                'fields' => $this->customFields ? Arr::keyBy(
                    $this->customFields,
                    fn (FieldInterface $field) => $field->layoutElement?->getOriginalHandle() ?? $field->handle,
                ) : [],
            ]);

            $condition = $parser->parse($this->relatedTo, $this->siteId !== '*' ? $this->siteId : null);

            if ($condition === false) {
                throw new QueryAbortedException;
            }

            $this->subQuery->where($condition);
        });
    }

    private function applyNotRelatedToParam(): void
    {
        if (! $this->notRelatedTo) {
            return;
        }

        $this->beforeQuery(function () {
            $notRelatedToParam = $this->notRelatedTo;

            $parser = new ElementRelationParamParser([
                'fields' => $this->customFields ? Arr::keyBy(
                    $this->customFields,
                    fn (FieldInterface $field) => $field->layoutElement?->getOriginalHandle() ?? $field->handle,
                ) : [],
            ]);

            $condition = $parser->parse($notRelatedToParam, $this->siteId !== '*' ? $this->siteId : null);

            if ($condition === false) {
                // just don't modify the query
                return;
            }

            $this->subQuery->whereNot($condition);
        });
    }

    /**
     * {@inheritdoc}
     *
     * @uses $notRelatedTo
     */
    public function notRelatedTo($value): static
    {
        $this->notRelatedTo = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @uses $notRelatedTo
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
     * {@inheritdoc}
     *
     * @uses $relatedTo
     */
    public function relatedTo($value): static
    {
        $this->relatedTo = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws NotSupportedException
     *
     * @uses $relatedTo
     */
    public function andRelatedTo($value): static
    {
        $relatedTo = $this->_andRelatedToCriteria($value, $this->relatedTo);

        if ($relatedTo === false) {
            return $this;
        }

        return $this->relatedTo($relatedTo);
    }

    /**
     * @throws NotSupportedException
     */
    private function _andRelatedToCriteria($value, $currentValue): mixed
    {
        if (! $value) {
            return false;
        }

        if (! $currentValue) {
            return $value;
        }

        // Normalize so element/targetElement/sourceElement values get pushed down to the 2nd level
        $relatedTo = ElementRelationParamParser::normalizeRelatedToParam($currentValue);
        $criteriaCount = count($relatedTo) - 1;

        // Not possible to switch from `or` to `and` if there are multiple criteria
        if ($relatedTo[0] === 'or' && $criteriaCount > 1) {
            throw new NotSupportedException('It’s not possible to combine “or” and “and” relatedTo conditions.');
        }

        $relatedTo[0] = $criteriaCount > 0 ? 'and' : 'or';
        $relatedTo[] = ElementRelationParamParser::normalizeRelatedToCriteria($value);

        return $relatedTo;
    }
}
