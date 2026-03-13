<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\behaviors\EventBehavior;
use craft\db\FixedOrderExpression;
use craft\db\Table as DbTable;
use craft\elements\db\ElementQuery;
use craft\elements\db\OrderByPlaceholderExpression;
use craft\events\CancelableEvent;
use craft\helpers\ElementHelper;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Structures;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Typecast;
use Override;

/**
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\BaseRelationField} instead.
 */
abstract class BaseRelationField extends \CraftCms\Cms\Field\BaseRelationField
{
    use \craft\base\LegacyEventConstants;

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function isValueEmpty(mixed $value, ElementInterface $element): bool
    {
        /** @var ElementQueryInterface|ElementCollection $value */
        if ($value instanceof ElementQueryInterface) {
            return !$this->_all($value, $element)->exists();
        }

        return $value->isEmpty();
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        // If we're propagating a value, and we don't show the site menu,
        // only save relations to elements in the current site.
        // (see https://github.com/craftcms/cms/issues/15459)
        if (
            $value instanceof ElementQueryInterface &&
            $element?->propagating &&
            $element->isNewForSite &&
            !$element->resaving &&
            !$element->isNewSite &&
            !$this->targetSiteId &&
            !$this->showSiteMenu
        ) {
            $value = $this->_all($value, $element)
                ->siteId($this->targetSiteId($element))
                ->ids();
        }

        if ($value instanceof ElementQueryInterface || $value instanceof ElementCollection) {
            return $value;
        }

        $class = static::elementType();
        /** @var ElementQuery $query */
        $query = $class::find()
            ->siteId($this->targetSiteId($element));

        if (is_array($value)) {
            $value = array_values(array_filter($value));
            $query->andWhere(['elements.id' => $value]);
            if (!empty($value)) {
                $query->orderBy([new FixedOrderExpression('elements.id', $value, Craft::$app->getDb())]);
            }
        } elseif ($value === null && $element?->id && $this->fetchRelationsFromDbTable($element)) {
            // If $value is null, the element + field haven’t been saved since updating to Craft 5.3+,
            // or since the field was added to the field layout,
            // or the value was added to not first instance of the field.
            // So only actually look at the `relations` table
            // if this is the first instance of the field that was ever added to the field layout
            // and none of the other instances (which would have been added later on) have a value.
            if (!$this->allowMultipleSources && $this->source) {
                $source = ElementHelper::findSource($class, $this->source, ElementSources::CONTEXT_FIELD);

                // Does the source specify any criteria attributes?
                if (isset($source['criteria'])) {
                    Typecast::configure($query, $source['criteria']);
                }
            }

            $relationsAlias = sprintf('relations_%s', Str::random(10));

            $query->attachBehavior(self::class, new EventBehavior([
                ElementQuery::EVENT_AFTER_PREPARE => function(
                    CancelableEvent $event,
                    ElementQuery $query,
                ) use ($element, $relationsAlias) {
                    if ($query->id === null) {
                        // Make these changes directly on the prepared queries, so `sortOrder` doesn't ever make it into
                        // the criteria. Otherwise, if the query ends up A) getting executed normally, then B) getting
                        // eager-loaded with eagerly(), the `orderBy` value referencing the join table will get applied
                        // to the eager-loading query and cause a SQL error.
                        foreach ([$query->query, $query->subQuery] as $q) {
                            $q->innerJoin(
                                [$relationsAlias => DbTable::RELATIONS],
                                [
                                    'and',
                                    "[[$relationsAlias.targetId]] = [[elements.id]]",
                                    [
                                        "$relationsAlias.sourceId" => $element->id,
                                        "$relationsAlias.fieldId" => $this->id,
                                    ],
                                    [
                                        'or',
                                        ["$relationsAlias.sourceSiteId" => null],
                                        ["$relationsAlias.sourceSiteId" => $element->siteId],
                                    ],
                                ],
                            );

                            if (
                                $this->sortable &&
                                !$this->maintainHierarchy &&
                                count($query->orderBy ?? []) === 1 &&
                                ($query->orderBy[0] ?? null) instanceof OrderByPlaceholderExpression
                            ) {
                                $q->orderBy(["$relationsAlias.sortOrder" => SORT_ASC]);
                            }
                        }
                    }
                },
            ]));
        } else {
            $query->id(false);
        }

        // Prepare the query for lazy eager loading, but only when element exists
        if ($element !== null) {
            $query->prepForEagerLoading($this->handle, $element);
        }

        if ($this->allowLimit && $this->maxRelations) {
            $query->limit($this->maxRelations);
        }

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function getRelationTargetIds(ElementInterface $element): array
    {
        /** @var ElementQueryInterface|ElementCollection $value */
        $value = $element->getFieldValue($this->handle);

        // $value will be an element query and its $id will be set if we're saving new relations
        if ($value instanceof ElementCollection) {
            $targetIds = $value->map(fn(ElementInterface $element) => $element->id)->all();
        } elseif (
            is_array($value->id) &&
            Arr::isNumeric($value->id)
        ) {
            $targetIds = $value->id ?: [];
        } elseif (
            isset($value->where['elements.id']) &&
            Arr::isNumeric($value->where['elements.id'])
        ) {
            $targetIds = $value->where['elements.id'] ?: [];
        } else {
            // just running $this->_all()->ids() will cause the query to get adjusted
            // see https://github.com/craftcms/cms/issues/14674 for details
            $targetIds = $this->_all($value, $element)
                ->get()
                ->map(fn(ElementInterface $element) => $element->id)
                ->all();
        }

        if ($this->maintainHierarchy) {
            $class = static::elementType();

            /** @var ElementInterface[] $structureElements */
            $structureElements = $class::find()
                ->id($targetIds)
                ->drafts(null)
                ->revisions(null)
                ->provisionalDrafts(null)
                ->status(null)
                ->site('*')
                ->unique()
                ->all();

            // Fill in any gaps
            Structures::fillGapsInElements($structureElements);

            // Enforce the branch limit
            if ($this->branchLimit) {
                Structures::applyBranchLimitToElements($structureElements, $this->branchLimit);
            }

            $targetIds = array_map(fn(ElementInterface $element) => $element->id, $structureElements);
        }

        return $targetIds;
    }

    /**
     * Returns a clone of the element query value, prepped to include disabled and cross-site elements.
     */
    private function _all(ElementQueryInterface $query, ?ElementInterface $element = null): ElementQueryInterface
    {
        $clone = (clone $query)
            ->drafts(null)
            ->status(null)
            ->site('*')
            ->limit(null)
            ->unique()
            ->eagerly(false);
        if ($element !== null) {
            $clone->preferSites([$this->targetSiteId($element)]);
        }

        return $clone;
    }
}
