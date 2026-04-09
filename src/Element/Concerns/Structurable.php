<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Events\AfterMoveInStructure;
use CraftCms\Cms\Element\Events\BeforeMoveInStructure;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Support\Attributes\Importable;
use CraftCms\Cms\Support\Facades\ElementCaches;
use CraftCms\Cms\Support\Typecast;

/**
 * Provides structure-related functionality for elements.
 *
 * This trait handles hierarchical relationships including parent/child,
 * ancestor/descendant, and sibling relationships within element structures.
 *
 * @property ElementQueryInterface $ancestors The element’s ancestors
 * @property ElementQueryInterface $children The element’s children
 * @property ElementQueryInterface $descendants The element’s descendants
 * @property bool $hasDescendants Whether the element has descendants
 * @property ElementInterface|null $next The next element relative to this one, from a given set of criteria
 * @property ElementInterface|null $nextSibling The element’s next sibling
 * @property ElementInterface|null $parent The element’s parent
 * @property int|null $parentId The element’s parent’s ID
 * @property ElementInterface|null $prev The previous element relative to this one, from a given set of criteria
 * @property ElementInterface|null $prevSibling The element’s previous sibling
 * @property ElementQueryInterface $siblings All of the element’s siblings
 * @property int $totalDescendants The total number of descendants that the element has
 *
 * @internal
 */
trait Structurable
{
    public ?int $structureId = null;

    public ?int $root = null;

    public ?int $lft = null;

    public ?int $rgt = null;

    public ?int $level = null;

    private ElementInterface|false|null $_nextElement = null;

    private ElementInterface|false|null $_prevElement = null;

    #[Importable('parentId')]
    private int|false|null $_parentId = null;

    private ElementInterface|false|null $_parent = null;

    private ?bool $_hasNewParent = null;

    private ElementInterface|false|null $_prevSibling = null;

    private ElementInterface|false|null $_nextSibling = null;

    public function getNext($criteria = false): ?ElementInterface
    {
        if ($criteria !== false || ! isset($this->_nextElement)) {
            return $this->_getRelativeElement($criteria, direction: 1);
        }

        return $this->_nextElement ?: null;
    }

    public function getPrev($criteria = false): ?ElementInterface
    {
        if ($criteria !== false || ! isset($this->_prevElement)) {
            return $this->_getRelativeElement($criteria, direction: -1);
        }

        return $this->_prevElement ?: null;
    }

    public function setNext($element): void
    {
        $this->_nextElement = $element;
    }

    public function setPrev($element): void
    {
        $this->_prevElement = $element;
    }

    public function getParentId(): ?int
    {
        if (isset($this->_parentId)) {
            return $this->_parentId ?: null;
        }

        return $this->getParent()?->id;
    }

    public function setParentId(mixed $parentId): void
    {
        if (is_array($parentId)) {
            $parentId = reset($parentId);
        }

        $this->_parentId = $parentId ?: false;
        $this->_parent = null;
    }

    public function getParent(): ?ElementInterface
    {
        if (isset($this->_parent)) {
            return $this->_parent ?: null;
        }

        if (isset($this->_parentId)) {
            if ($this->_parentId === false) {
                return null;
            }

            $this->_parent = static::find()
                ->id($this->_parentId)
                ->structureId($this->structureId)
                ->siteId($this->siteId)
                ->status(null)
                ->one() ?? false;

            return $this->_parent ?: null;
        }

        $ancestors = $this->getAncestors(1);

        $this->_parent = $ancestors instanceof ElementCollection
            ? $ancestors->first()
            : $ancestors->status(null)->one();

        return $this->_parent ?: null;
    }

    public function getParentUri(): ?string
    {
        $parent = $this->getParent();

        return $parent && $parent->uri !== Element::HOMEPAGE_URI ? $parent->uri : null;
    }

    public function setParent(?ElementInterface $parent): void
    {
        $this->_parent = $parent;

        if ($parent) {
            $this->level = $parent->level + 1;
            $this->_parentId = $parent->id;

            return;
        }

        $this->level = 1;
        $this->_parentId = false;
    }

    protected function hasNewParent(): bool
    {
        return $this->_hasNewParent ??= $this->_checkForNewParent();
    }

    private function _checkForNewParent(): bool
    {
        if (! $this->structureId) {
            return false;
        }

        if (! isset($this->id) && ! $this->isProvisionalDraft) {
            return true;
        }

        if (! isset($this->_parentId)) {
            return false;
        }

        $element = $this->getIsDerivative() && ! isset($this->lft)
            ? $this->getCanonical(true)
            : $this;

        if (! $this->_parentId && $element->level !== 1) {
            return true;
        }

        if ($this->_parentId && $element->level === 1) {
            return true;
        }

        return $this->_parentId !== static::find()
            ->ancestorOf($element)
            ->ancestorDist(1)
            ->siteId($element->siteId)
            ->status(null)
            ->select('elements.id')
            ->first()?->id;
    }

    public function getAncestors(?int $dist = null): ElementQueryInterface|ElementCollection
    {
        if (($ancestors = $this->getEagerLoadedElements('ancestors')) !== null) {
            return $dist === null
                ? $ancestors
                : $ancestors->filter(fn (ElementInterface $element) => $element->level >= $this->level - $dist);
        }

        return $this->ancestors()->ancestorDist($dist);
    }

    protected function ancestors(): ElementQueryInterface
    {
        return static::find()
            ->structureId($this->structureId)
            ->ancestorOf($this)
            ->siteId($this->siteId);
    }

    public function getDescendants(?int $dist = null): ElementQueryInterface|ElementCollection
    {
        if (($descendants = $this->getEagerLoadedElements('descendants')) !== null) {
            return $dist === null
                ? $descendants
                : $descendants->filter(fn (ElementInterface $element) => $element->level <= $this->level + $dist);
        }

        return $this->descendants()->descendantDist($dist);
    }

    protected function descendants(): ElementQueryInterface
    {
        return static::find()
            ->structureId($this->structureId)
            ->descendantOf($this)
            ->siteId($this->siteId);
    }

    public function getChildren(): ElementQueryInterface|ElementCollection
    {
        return $this->getEagerLoadedElements('children') ?? $this->getDescendants(1);
    }

    public function getSiblings(): ElementQueryInterface|ElementCollection
    {
        return static::find()
            ->structureId($this->structureId)
            ->siblingOf($this)
            ->siteId($this->siteId);
    }

    public function getPrevSibling(): ?ElementInterface
    {
        if (! isset($this->_prevSibling)) {
            $this->_prevSibling = $this->findSibling('prevSiblingOf') ?? false;
        }

        return $this->_prevSibling ?: null;
    }

    public function getNextSibling(): ?ElementInterface
    {
        if (! isset($this->_nextSibling)) {
            $this->_nextSibling = $this->findSibling('nextSiblingOf') ?? false;
        }

        return $this->_nextSibling ?: null;
    }

    private function findSibling(string $relation): ?ElementInterface
    {
        /** @var ElementQuery $query */
        $query = static::find();
        $query->structureId = $this->structureId;
        $query->{$relation} = $this;
        $query->siteId = $this->siteId;
        $query->status(null);

        return $query->one();
    }

    public function getHasDescendants(): bool
    {
        $descendants = $this->getDescendants();

        return $descendants instanceof ElementCollection
            ? $descendants->isNotEmpty()
            : $descendants->exists();
    }

    public function getTotalDescendants(): int
    {
        return $this->getDescendants()->count();
    }

    public function isAncestorOf(ElementInterface $element): bool
    {
        $canonical = $this->getCanonical();

        return $canonical->root === $element->root
            && $canonical->lft < $element->lft
            && $canonical->rgt > $element->rgt;
    }

    public function isDescendantOf(ElementInterface $element): bool
    {
        return $this->root === $element->root
            && $this->lft > $element->lft
            && $this->rgt < $element->rgt;
    }

    public function isParentOf(ElementInterface $element): bool
    {
        $canonical = $this->getCanonical();

        return $canonical->root === $element->root
            && $canonical->level === $element->level - 1
            && $canonical->isAncestorOf($element);
    }

    public function isChildOf(ElementInterface $element): bool
    {
        return $this->root === $element->root
            && $this->level === $element->level + 1
            && $this->isDescendantOf($element);
    }

    public function isSiblingOf(ElementInterface $element): bool
    {
        if ($this->root !== $element->root || ! isset($this->level) || $this->level !== $element->level) {
            return false;
        }

        if ($this->level === 1 || $this->isPrevSiblingOf($element) || $this->isNextSiblingOf($element)) {
            return true;
        }

        $parent = $this->getParent();

        return $parent && $element->isDescendantOf($parent);
    }

    public function isPrevSiblingOf(ElementInterface $element): bool
    {
        return $this->root === $element->root
            && $this->level === $element->level
            && $this->rgt === $element->lft - 1;
    }

    public function isNextSiblingOf(ElementInterface $element): bool
    {
        return $this->root === $element->root
            && $this->level === $element->level
            && $this->lft === $element->rgt + 1;
    }

    public function beforeMoveInStructure(int $structureId): bool
    {
        // Fire a 'beforeMoveInStructure' event
        event($event = new BeforeMoveInStructure($this, $structureId));

        return $event->isValid;
    }

    public function afterMoveInStructure(int $structureId): void
    {
        // Fire an 'afterMoveInStructure' event
        event(new AfterMoveInStructure($this, $structureId));

        // Invalidate caches for this element
        ElementCaches::invalidateForElement($this);
    }

    private function _getRelativeElement(mixed $criteria, int $direction): ?ElementInterface
    {
        if (! isset($this->id)) {
            return null;
        }

        if ($criteria instanceof ElementQueryInterface) {
            $query = clone $criteria;
        } else {
            $query = static::find()
                ->siteId($this->siteId);

            if ($criteria) {
                Typecast::configure($query, $criteria);
            }
        }

        /** @var ElementQuery $query */
        $elementIds = $query->cache()->ids();
        $key = array_search($this->getCanonicalId(), $elementIds, false);

        if ($key === false || ! isset($elementIds[$key + $direction])) {
            return null;
        }

        return $query
            ->id($elementIds[$key + $direction])
            ->one();
    }
}
