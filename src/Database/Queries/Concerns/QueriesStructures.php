<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns;

use Craft;
use craft\base\ElementInterface;
use CraftCms\Cms\Database\Queries\ElementQuery;
use CraftCms\Cms\Database\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Database\Table;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Language\Alias;

/**
 * @mixin \CraftCms\Cms\Database\Queries\ElementQuery
 *
 * @internal
 */
trait QueriesStructures
{
    /**
     * @var bool Whether the elements must be “leaves” in the structure.
     *
     * @used-by leaves()
     */
    public bool $leaves = false;

    /**
     * @var bool|null Whether element structure data should automatically be left-joined into the query.
     *
     * @used-by withStructure()
     */
    public ?bool $withStructure = null;

    /**
     * @var mixed The structure ID that should be used to join in the structureelements table.
     *
     * @used-by structureId()
     */
    public mixed $structureId = null;

    /**
     * @var mixed The element’s level within the structure
     *
     * @used-by level()
     */
    public mixed $level = null;

    /**
     * @var bool|null Whether the resulting elements must have descendants.
     *
     * @used-by hasDescendants()
     */
    public ?bool $hasDescendants = null;

    /**
     * @var int|ElementInterface|null The element (or its ID) that results must be an ancestor of.
     *
     * @used-by ancestorOf()
     */
    public ElementInterface|int|null $ancestorOf = null;

    /**
     * @var int|null The maximum number of levels that results may be separated from [[ancestorOf]].
     *
     * @used-by ancestorDist()
     */
    public ?int $ancestorDist = null;

    /**
     * @var int|ElementInterface|null The element (or its ID) that results must be a descendant of.
     *
     * @used-by descendantOf()
     */
    public ElementInterface|int|null $descendantOf = null;

    /**
     * @var int|null The maximum number of levels that results may be separated from [[descendantOf]].
     *
     * @used-by descendantDist()
     */
    public ?int $descendantDist = null;

    /**
     * @var int|ElementInterface|null The element (or its ID) that the results must be a sibling of.
     *
     * @used-by siblingOf()
     */
    public ElementInterface|int|null $siblingOf = null;

    /**
     * @var int|ElementInterface|null The element (or its ID) that the result must be the previous sibling of.
     *
     * @used-by prevSiblingOf()
     */
    public ElementInterface|int|null $prevSiblingOf = null;

    /**
     * @var int|ElementInterface|null The element (or its ID) that the result must be the next sibling of.
     *
     * @used-by nextSiblingOf()
     */
    public ElementInterface|int|null $nextSiblingOf = null;

    /**
     * @var int|ElementInterface|null The element (or its ID) that the results must be positioned before.
     *
     * @used-by positionedBefore()
     */
    public ElementInterface|int|null $positionedBefore = null;

    /**
     * @var int|ElementInterface|null The element (or its ID) that the results must be positioned after.
     *
     * @used-by positionedAfter()
     */
    public ElementInterface|int|null $positionedAfter = null;

    protected function initQueriesStructures(): void
    {
        $this->beforeQuery(function (ElementQuery $elementQuery) {
            $this->applyStructureParams($elementQuery);
        });

        $this->afterQuery(function (Collection $collection) {
            if ($this->structureId) {
                return $collection->map(function ($element) {
                    $element->structureId = $this->structureId;

                    return $element;
                });
            }

            return $collection;
        });
    }

    /**
     * Explicitly determines whether the query should join in the structure data.
     */
    public function withStructure(bool $value = true): static
    {
        $this->withStructure = $value;

        return $this;
    }

    /**
     * Determines which structure data should be joined into the query.
     *
     * @internal
     */
    public function structureId(?int $value = null): static
    {
        $this->structureId = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the {elements}’ level within the structure.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `1` | with a level of 1.
     * | `'not 1'` | not with a level of 1.
     * | `'>= 3'` | with a level greater than or equal to 3.
     * | `[1, 2]` | with a level of 1 or 2.
     * | `[null, 1]` | without a level, or a level of 1.
     * | `['not', 1, 2]` | not with level of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} positioned at level 3 or above #}
     * {% set {elements-var} = {twig-method}
     *   .level('>= 3')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} positioned at level 3 or above
     * ${elements-var} = {php-method}
     *     ->level('>= 3')
     *     ->all();
     * ```
     */
    public function level($value = null): static
    {
        $this->level = $value;

        return $this;
    }

    /**
     * Narrows the query results based on whether the {elements} have any descendants in their structure.
     *
     * (This has the opposite effect of calling [[leaves()]].)
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} that have descendants #}
     * {% set {elements-var} = {twig-method}
     *   .hasDescendants()
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} that have descendants
     * ${elements-var} = {php-method}
     *     ->hasDescendants()
     *     ->all();
     * ```
     */
    public function hasDescendants(bool $value = true): static
    {
        $this->hasDescendants = $value;

        return $this;
    }

    /**
     * Narrows the query results based on whether the {elements} are “leaves” ({elements} with no descendants).
     *
     * (This has the opposite effect of calling [[hasDescendants()]].)
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} that have no descendants #}
     * {% set {elements-var} = {twig-method}
     *   .leaves()
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} that have no descendants
     * ${elements-var} = {php-method}
     *     ->leaves()
     *     ->all();
     * ```
     */
    public function leaves(bool $value = true): static
    {
        $this->leaves = $value;

        return $this;
    }

    /**
     * Narrows the query results to only {elements} that are ancestors of another {element} in its structure.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `1` | above the {element} with an ID of 1.
     * | a [[{element-class}]] object | above the {element} represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} above this one #}
     * {% set {elements-var} = {twig-method}
     *   .ancestorOf({myElement})
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} above this one
     * ${elements-var} = {php-method}
     *     ->ancestorOf(${myElement})
     *     ->all();
     * ```
     *
     * ---
     *
     * ::: tip
     * This can be combined with [[ancestorDist()]] if you want to limit how far away the ancestor {elements} can be.
     * :::
     */
    public function ancestorOf(ElementInterface|int|null $value): static
    {
        $this->ancestorOf = $value;

        return $this;
    }

    /**
     * Narrows the query results to only {elements} that are up to a certain distance away from the {element} specified by [[ancestorOf()]].
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} above this one #}
     * {% set {elements-var} = {twig-method}
     *   .ancestorOf({myElement})
     *   .ancestorDist(3)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} above this one
     * ${elements-var} = {php-method}
     *     ->ancestorOf(${myElement})
     *     ->ancestorDist(3)
     *     ->all();
     * ```
     */
    public function ancestorDist(?int $value = null): static
    {
        $this->ancestorDist = $value;

        return $this;
    }

    /**
     * Narrows the query results to only {elements} that are descendants of another {element} in its structure.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `1` | below the {element} with an ID of 1.
     * | a [[{element-class}]] object | below the {element} represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} below this one #}
     * {% set {elements-var} = {twig-method}
     *   .descendantOf({myElement})
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} below this one
     * ${elements-var} = {php-method}
     *     ->descendantOf(${myElement})
     *     ->all();
     * ```
     *
     * ---
     *
     * ::: tip
     * This can be combined with [[descendantDist()]] if you want to limit how far away the descendant {elements} can be.
     * :::
     */
    public function descendantOf(ElementInterface|int|null $value): static
    {
        $this->descendantOf = $value;

        return $this;
    }

    /**
     * Narrows the query results to only {elements} that are up to a certain distance away from the {element} specified by [[descendantOf()]].
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} below this one #}
     * {% set {elements-var} = {twig-method}
     *   .descendantOf({myElement})
     *   .descendantDist(3)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} below this one
     * ${elements-var} = {php-method}
     *     ->descendantOf(${myElement})
     *     ->descendantDist(3)
     *     ->all();
     * ```
     */
    public function descendantDist(?int $value = null): static
    {
        $this->descendantDist = $value;

        return $this;
    }

    /**
     * Narrows the query results to only {elements} that are siblings of another {element} in its structure.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `1` | beside the {element} with an ID of 1.
     * | a [[{element-class}]] object | beside the {element} represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} beside this one #}
     * {% set {elements-var} = {twig-method}
     *   .siblingOf({myElement})
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} beside this one
     * ${elements-var} = {php-method}
     *     ->siblingOf(${myElement})
     *     ->all();
     * ```
     */
    public function siblingOf(ElementInterface|int|null $value): static
    {
        $this->siblingOf = $value;

        return $this;
    }

    /**
     * Narrows the query results to only the {element} that comes immediately before another {element} in its structure.
     *
     * Possible values include:
     *
     * | Value | Fetches the {element}…
     * | - | -
     * | `1` | before the {element} with an ID of 1.
     * | a [[{element-class}]] object | before the {element} represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch the previous {element} #}
     * {% set {element-var} = {twig-method}
     *   .prevSiblingOf({myElement})
     *   .one() %}
     * ```
     *
     * ```php
     * // Fetch the previous {element}
     * ${element-var} = {php-method}
     *     ->prevSiblingOf(${myElement})
     *     ->one();
     * ```
     */
    public function prevSiblingOf(ElementInterface|int|null $value): static
    {
        $this->prevSiblingOf = $value;

        return $this;
    }

    /**
     * Narrows the query results to only the {element} that comes immediately after another {element} in its structure.
     *
     * Possible values include:
     *
     * | Value | Fetches the {element}…
     * | - | -
     * | `1` | after the {element} with an ID of 1.
     * | a [[{element-class}]] object | after the {element} represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch the next {element} #}
     * {% set {element-var} = {twig-method}
     *   .nextSiblingOf({myElement})
     *   .one() %}
     * ```
     *
     * ```php
     * // Fetch the next {element}
     * ${element-var} = {php-method}
     *     ->nextSiblingOf(${myElement})
     *     ->one();
     * ```
     */
    public function nextSiblingOf(ElementInterface|int|null $value): static
    {
        $this->nextSiblingOf = $value;

        return $this;
    }

    /**
     * Narrows the query results to only {elements} that are positioned before another {element} in its structure.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `1` | before the {element} with an ID of 1.
     * | a [[{element-class}]] object | before the {element} represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} before this one #}
     * {% set {elements-var} = {twig-method}
     *   .positionedBefore({myElement})
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} before this one
     * ${elements-var} = {php-method}
     *     ->positionedBefore(${myElement})
     *     ->all();
     * ```
     */
    public function positionedBefore(ElementInterface|int|null $value): static
    {
        $this->positionedBefore = $value;

        return $this;
    }

    /**
     * Narrows the query results to only {elements} that are positioned after another {element} in its structure.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `1` | after the {element} with an ID of 1.
     * | a [[{element-class}]] object | after the {element} represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} after this one #}
     * {% set {elements-var} = {twig-method}
     *   .positionedAfter({myElement})
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} after this one
     * ${elements-var} = {php-method}
     *     ->positionedAfter(${myElement})
     *     ->all();
     * ```
     */
    public function positionedAfter(ElementInterface|int|null $value): static
    {
        $this->positionedAfter = $value;

        return $this;
    }

    private function shouldJoinStructureData(): bool
    {
        return
            ! $this->revisions &&
            ($this->withStructure ?? ($this->structureId && ! $this->trashed));
    }

    private function applyStructureParams(ElementQuery $elementQuery): void
    {
        if (! $elementQuery->shouldJoinStructureData()) {
            $structureParams = [
                'hasDescendants',
                'ancestorOf',
                'descendantOf',
                'siblingOf',
                'prevSiblingOf',
                'nextSiblingOf',
                'positionedBefore',
                'positionedAfter',
                'level',
            ];

            foreach ($structureParams as $param) {
                if ($elementQuery->$param !== null) {
                    throw new QueryAbortedException("Unable to apply the '$param' param because 'structureId' isn't set");
                }
            }

            return;
        }

        $elementQuery->query->addSelect([
            'structureelements.root as root',
            'structureelements.lft as lft',
            'structureelements.rgt as rgt',
            'structureelements.level as level',
        ]);

        if ($elementQuery->structureId) {
            $elementQuery->query->leftJoin(new Alias(Table::STRUCTUREELEMENTS, 'structureelements'), fn (JoinClause $join) => $join
                ->on('structureelements.elementId', '=', 'subquery.elementsId')
                ->where('structureelements.structureId', $elementQuery->structureId));

            $elementQuery->subQuery->leftJoin(new Alias(Table::STRUCTUREELEMENTS, 'structureelements'), fn (JoinClause $join) => $join
                ->on('structureelements.elementId', '=', 'elements.id')
                ->where('structureelements.structureId', $elementQuery->structureId));
        } else {
            $elementQuery->query
                ->addSelect('structureelements.structureId as structureId')
                ->leftJoin(new Alias(Table::STRUCTUREELEMENTS, 'structureelements'), fn (JoinClause $join) => $join
                    ->on('structureelements.elementId', '=', 'subquery.elementsId')
                    ->where('structureelements.structureId', '=', 'subquery.structureId'));

            $existsQuery = DB::table(Table::STRUCTURES)
                // Use index hints to specify index so Mysql does not select the less
                // performant one (dateDeleted).
                ->when(
                    DB::getDriverName() === 'mysql',
                    fn (Builder $query) => $query->useIndex('primary'),
                )
                ->whereColumn('id', 'structureelements.structureId')
                ->whereNull('dateDeleted');

            $elementQuery->subQuery
                ->addSelect('structureelements.structureId as structureId')
                ->leftJoin(new Alias(Table::STRUCTUREELEMENTS, 'structureelements'), fn (JoinClause $join) => $join
                    ->on('structureelements.elementId', '=', 'elements.id')
                    ->whereExists($existsQuery)
                );
        }

        if (isset($elementQuery->hasDescendants)) {
            $elementQuery->subQuery->when(
                $elementQuery->hasDescendants,
                fn (Builder $query) => $elementQuery->where('structureelements.rgt', '>', DB::raw('structureelements.lft + 1')),
                fn (Builder $query) => $elementQuery->where('structureelements.rgt', '=', DB::raw('structureelements.lft + 1')),
            );
        }

        if ($elementQuery->ancestorOf) {
            $ancestorOf = $elementQuery->normalizeStructureParamValue('ancestorOf');

            $elementQuery->subQuery
                ->where('structureelements.lft', '<', $ancestorOf->lft)
                ->where('structureelements.rgt', '>', $ancestorOf->rgt)
                ->where('structureelements.root', $ancestorOf->root)
                ->when(
                    $elementQuery->ancestorDist,
                    fn (Builder $q) => $q->where('structureelements.level', '>=', $ancestorOf->level - $elementQuery->ancestorDist)
                );
        }

        if ($elementQuery->descendantOf) {
            $descendantOf = $elementQuery->normalizeStructureParamValue('descendantOf');

            $elementQuery->subQuery
                ->where('structureelements.lft', '>', $descendantOf->lft)
                ->where('structureelements.rgt', '<', $descendantOf->rgt)
                ->where('structureelements.root', $descendantOf->root)
                ->when(
                    $elementQuery->descendantDist,
                    fn (Builder $q) => $q->where('structureelements.level', '<=', $descendantOf->level + $elementQuery->descendantDist)
                );
        }

        foreach (['siblingOf', 'prevSiblingOf', 'nextSiblingOf'] as $param) {
            if (! $elementQuery->$param) {
                continue;
            }

            $siblingOf = $elementQuery->normalizeStructureParamValue($param);

            $elementQuery->subQuery
                ->where('structureelements.level', $siblingOf->level)
                ->where('structureelements.root', $siblingOf->root)
                ->whereNot('structureelements.elementId', $siblingOf->id);

            if ($siblingOf->level !== 1) {
                $parent = $siblingOf->getParent();

                if (! $parent) {
                    throw new QueryAbortedException;
                }

                $elementQuery->subQuery
                    ->where('structureelements.lft', '>', $parent->lft)
                    ->where('structureelements.rgt', '>', $parent->rgt);
            }

            switch ($param) {
                case 'prevSiblingOf':
                    $elementQuery->orderByDesc('structureelements.lft');
                    $elementQuery->subQuery
                        ->where('structureelements.lft', '<', $siblingOf->lft)
                        ->orderByDesc('structureelements.lft')
                        ->limit(1);
                    break;
                case 'nextSiblingOf':
                    $elementQuery->orderBy('structureelements.lft');
                    $elementQuery->subQuery
                        ->where('structureelements.lft', '>', $siblingOf->lft)
                        ->orderBy('structureelements.lft')
                        ->limit(1);
                    break;
            }
        }

        if ($elementQuery->positionedBefore) {
            $positionedBefore = $elementQuery->normalizeStructureParamValue('positionedBefore');

            $elementQuery->subQuery
                ->where('structureelements.lft', '<', $positionedBefore->lft)
                ->where('structureelements.root', $positionedBefore->root);
        }

        if ($elementQuery->positionedAfter) {
            $positionedAfter = $elementQuery->normalizeStructureParamValue('positionedAfter');

            $elementQuery->subQuery
                ->where('structureelements.lft', '>', $positionedAfter->rgt)
                ->where('structureelements.root', $positionedAfter->root);
        }

        if (isset($elementQuery->level)) {
            $allowNull = is_array($elementQuery->level) && in_array(null, $elementQuery->level, true);

            $elementQuery->subQuery->when(
                value: $allowNull,
                callback: fn (Builder $q) => $q->where(function (Builder $q) use ($elementQuery) {
                    $q->whereNumericParam('structureelements.level', array_filter($elementQuery->level, fn ($v) => $v !== null))
                        ->orWhereNull('structureelements.level');
                }),
                default: fn (Builder $q) => $q->whereNumericParam('structureelements.level', $elementQuery->level),
            );
        }

        if ($elementQuery->leaves) {
            $elementQuery->subQuery->where('structureelements.rgt', DB::raw('structureelements.lft + 1'));
        }
    }

    /**
     * Normalizes a structure param value to either an Element object or false.
     *
     * @param  string  $property  The parameter’s property name.
     * @param  class-string<ElementInterface>  $class  The element class
     * @return ElementInterface The normalized element
     *
     * @throws QueryAbortedException if the element can't be found
     */
    private function normalizeStructureParamValue(string $property): ElementInterface
    {
        $element = $this->$property;

        if ($element === false) {
            throw new QueryAbortedException;
        }

        if ($element instanceof ElementInterface && ! $element->lft) {
            $element = $element->getCanonicalId();

            if ($element === null) {
                throw new QueryAbortedException;
            }
        }

        if (! $element instanceof ElementInterface) {
            $element = Craft::$app->getElements()->getElementById($element, $this->elementType, $this->siteId, [
                'structureId' => $this->structureId,
            ]);

            if ($element === null) {
                $this->$property = false;
                throw new QueryAbortedException;
            }
        }

        if (! $element->lft) {
            if ($element->getIsDerivative()) {
                $element = $element->getCanonical(true);
            }

            if (! $element->lft) {
                $this->$property = false;
                throw new QueryAbortedException;
            }
        }

        return $this->$property = $element;
    }
}
