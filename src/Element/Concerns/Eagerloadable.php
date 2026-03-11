<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use craft\base\ElementInterface;
use craft\elements\db\EagerLoadInfo;
use craft\elements\db\EagerLoadPlan;
use craft\helpers\ElementHelper;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Events\DefineEagerLoadingMap;
use CraftCms\Cms\Element\Events\SetEagerLoadedElements;
use CraftCms\Cms\Field\Contracts\EagerLoadingFieldInterface;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Language\Alias;
use yii\base\InvalidConfigException;

/**
 * Eagerloadable provides eager loading functionality for elements.
 *
 * @property-read ElementInterface[]|null $elementQueryResult All elements that the element was queried with
 *
 * @phpstan-import-type EagerLoadingMap from ElementInterface
 *
 * @internal
 */
trait Eagerloadable
{
    /**
     * @var ElementInterface[]|null All elements that the element was queried with.
     *
     * @since 5.0.0
     */
    public ?array $elementQueryResult = null;

    /**
     * @var EagerLoadInfo|null Info about the eager loading setup used to query this element.
     *
     * @since 5.0.0
     */
    public ?EagerLoadInfo $eagerLoadInfo = null;

    /**
     * @var array<string,ElementCollection>
     *
     * @see getEagerLoadedElements()
     * @see SetEagerLoadedElements()
     */
    private array $_eagerLoadedElements = [];

    /**
     * @var array<string,bool>
     *
     * @see getFieldValue()
     * @see setLazyEagerLoadedElements()
     */
    private array $_lazyEagerLoadedElements = [];

    /**
     * @var array<string,int>
     *
     * @see getEagerLoadedElementCount()
     * @see setEagerLoadedElementCount
     */
    private array $_eagerLoadedElementCounts = [];

    /**
     * Returns an eager-loading map for the source elements.
     *
     * Eager-loading mappings allow elements to support eager-loading other element types via an
     * `with` parameter on the element query.
     *
     * @param  ElementInterface[]  $sourceElements  An array of the source elements
     * @param  string  $handle  The property handle used to identify which target elements should be eager-loaded
     * @return array|null|false The eager-loading element ID mappings (in the form of `['sourceId' => ['targetId1', 'targetId2', ...]]`),
     *                          or `false` if the result should be ignored (and not used anywhere else), or `null` if the result should be ignored for this handle,
     *                          but still used elsewhere.
     *
     * @phpstan-return EagerLoadingMap|null|false
     *
     * @since 3.1.0
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle): array|null|false
    {
        switch ($handle) {
            case 'descendants':
            case 'children':
                return self::_mapDescendants($sourceElements, $handle === 'children');
            case 'ancestors':
            case 'parent':
                return self::_mapAncestors($sourceElements, $handle === 'parent');
            case 'localized':
                return self::_mapLocalized($sourceElements);
            case 'currentRevision':
                return self::_mapCurrentRevisions($sourceElements);
            case 'drafts':
                return self::_mapDrafts($sourceElements);
            case 'revisions':
                return self::_mapRevisions($sourceElements);
            case 'draftCreator':
                return self::_mapDraftCreators($sourceElements);
            case 'revisionCreator':
                return self::_mapRevisionCreators($sourceElements);
        }

        // Is $handle a custom field handle?
        // (Leave it up to the extended class to set the field context, if it shouldn't be 'global')
        if (str_contains($handle, ':')) {
            [$providerHandle, $fieldHandle] = explode(':', $handle, 2);
        } else {
            $providerHandle = null;
            $fieldHandle = $handle;
        }

        // Get all the custom fields by that handle
        $fields = [];
        foreach (static::fieldLayouts(null) as $fieldLayout) {
            if ($providerHandle === null || $fieldLayout->provider?->getHandle() === $providerHandle) {
                $layoutField = $fieldLayout->getFieldByHandle($fieldHandle);
                if ($layoutField) {
                    $fields[] = $layoutField;
                    if ($providerHandle !== null) {
                        break;
                    }
                }
            }
        }

        // If there were any matching fields, find the first one that's actually included in
        // at least one of the source elements' field layouts
        if (! empty($fields)) {
            foreach ($fields as $field) {
                if (! $field instanceof EagerLoadingFieldInterface) {
                    continue;
                }

                // filter out elements, if field is not part of its layout
                // https://github.com/craftcms/cms/issues/12539
                $fieldSourceElements = array_values(
                    array_filter($sourceElements, function ($sourceElement) use ($field) {
                        $layoutField = $sourceElement->getFieldLayout()?->getFieldByHandle($field->handle);

                        return $layoutField && $layoutField->id === $field->id;
                    }),
                );

                if (! empty($fieldSourceElements)) {
                    return $field->getEagerLoadingMap($fieldSourceElements);
                }
            }

            // None of the source elements include any of the matching fields
            return false;
        }

        // Fire a 'defineEagerLoadingMap' event
        event($event = new DefineEagerLoadingMap(
            elementType: static::class,
            sourceElements: $sourceElements,
            handle: $handle,
        ));

        if ($event->targetElementType !== null) {
            return [
                'elementType' => $event->targetElementType,
                'map' => $event->map,
                'criteria' => $event->criteria,
            ];
        }

        // return null so eager-loading is ignored for this handle
        return null;
    }

    /**
     * Returns an eager-loading map for the source elements' descendants.
     *
     * @param  ElementInterface[]  $sourceElements  An array of the source elements
     * @param  bool  $children  Whether only direct children should be included
     * @return array|null The eager-loading element ID mappings, or null if the result should be ignored
     */
    private static function _mapDescendants(array $sourceElements, bool $children): ?array
    {
        $elementStructureData = self::_structureDataForElements($sourceElements, $children);

        if (empty($elementStructureData)) {
            return null;
        }

        // Build the descendant condition & params
        $descendantStructureQuery = DB::table(Table::STRUCTUREELEMENTS);

        foreach ($elementStructureData as $elementStructureDatum) {
            $descendantStructureQuery->orWhere(function (Builder $query) use ($children, $elementStructureDatum) {
                $query->where('structureId', $elementStructureDatum['structureId'])
                    ->where('lft', '>', $elementStructureDatum['lft'])
                    ->where('rgt', '<', $elementStructureDatum['rgt'])
                    ->when($children, fn (Builder $query) => $query->where('level', $elementStructureDatum['level'] + 1));
            });
        }

        // Fetch the descendant data
        $descendantStructureQuery
            ->select(['structureId', 'lft', 'rgt', 'elementId'])
            ->when($children, fn (Builder $query) => $query->addSelect('level'))
            ->orderBy('lft');

        $descendantStructureData = $descendantStructureQuery
            ->get()
            ->map(fn (object $data) => (array) $data);

        // Map the elements to their descendants
        $map = [];
        foreach ($elementStructureData as $elementStructureDatum) {
            foreach ($descendantStructureData as $descendantStructureDatum) {
                if (! ($descendantStructureDatum['structureId'] == $elementStructureDatum['structureId'] && $descendantStructureDatum['lft'] > $elementStructureDatum['lft'] && $descendantStructureDatum['rgt'] < $elementStructureDatum['rgt'])) {
                    continue;
                }
                if (! (! $children || $descendantStructureDatum['level'] == $elementStructureDatum['level'] + 1)) {
                    continue;
                }
                if (! $descendantStructureDatum['elementId']) {
                    continue;
                }
                $map[] = [
                    'source' => $elementStructureDatum['elementId'],
                    'target' => $descendantStructureDatum['elementId'],
                ];
            }
        }

        return [
            'elementType' => static::class,
            'map' => $map,
        ];
    }

    /**
     * Returns an eager-loading map for the source elements' ancestors.
     *
     * @param  ElementInterface[]  $sourceElements  An array of the source elements
     * @param  bool  $parents  Whether only direct parents should be included
     * @return array|null The eager-loading element ID mappings, or null if the result should be ignored
     */
    private static function _mapAncestors(array $sourceElements, bool $parents): ?array
    {
        $elementStructureData = self::_structureDataForElements($sourceElements, $parents);

        if (empty($elementStructureData)) {
            return null;
        }

        $ancestorStructureQuery = DB::table(Table::STRUCTUREELEMENTS);

        // Build the ancestor condition & params
        foreach ($elementStructureData as $elementStructureDatum) {
            $ancestorStructureQuery->orWhere(function (Builder $query) use ($elementStructureDatum, $parents) {
                $query->where('structureId', $elementStructureDatum['structureId'])
                    ->where('lft', '<', $elementStructureDatum['lft'])
                    ->where('rgt', '>', $elementStructureDatum['rgt'])
                    ->when($parents, fn (Builder $query) => $query->where('level', $elementStructureDatum['level'] - 1));
            });
        }

        // Fetch the ancestor data
        $ancestorStructureQuery
            ->select(['structureId', 'lft', 'rgt', 'elementId'])
            ->when($parents, fn (Builder $query) => $query->addSelect('level'))
            ->orderBy('lft');

        $ancestorStructureData = $ancestorStructureQuery
            ->get()
            ->map(fn (object $data) => (array) $data);

        // Map the elements to their ancestors
        $map = [];
        foreach ($elementStructureData as $elementStructureDatum) {
            foreach ($ancestorStructureData as $ancestorStructureDatum) {
                if (
                    $ancestorStructureDatum['structureId'] == $elementStructureDatum['structureId'] &&
                    $ancestorStructureDatum['lft'] < $elementStructureDatum['lft'] &&
                    $ancestorStructureDatum['rgt'] > $elementStructureDatum['rgt'] &&
                    (! $parents || $ancestorStructureDatum['level'] == $elementStructureDatum['level'] - 1)
                ) {
                    if ($ancestorStructureDatum['elementId']) {
                        $map[] = [
                            'source' => $elementStructureDatum['elementId'],
                            'target' => $ancestorStructureDatum['elementId'],
                        ];
                    }

                    // If we're just fetching the parents, then we're done with this element
                    if ($parents) {
                        break;
                    }
                }
            }
        }

        return [
            'elementType' => static::class,
            'map' => $map,
        ];
    }

    /**
     * @param  ElementInterface[]  $elements
     */
    private static function _structureDataForElements(array $elements, bool $withLevel): array
    {
        $data = [];
        $fetchDataForIds = [];

        foreach ($elements as $element) {
            if (isset($element->structureId, $element->lft, $element->rgt, $element->level)) {
                $data[] = [
                    'structureId' => $element->structureId,
                    'elementId' => $element->id,
                    'lft' => $element->lft,
                    'rgt' => $element->rgt,
                    'level' => $element->level,
                ];
            } else {
                $fetchDataForIds[] = $element->id;
            }
        }

        if (! empty($fetchDataForIds)) {
            $fetched = DB::table(Table::STRUCTUREELEMENTS)
                ->whereIn('elementId', $fetchDataForIds)
                ->select(['structureId', 'elementId', 'lft', 'rgt'])
                ->when($withLevel, fn (Builder $query) => $query->addSelect('level'))
                ->get()
                ->map(fn (object $data) => (array) $data)
                ->all();

            array_push($data, ...$fetched);
        }

        return $data;
    }

    /**
     * Returns an eager-loading map for the source elements in other locales.
     *
     * @param  ElementInterface[]  $sourceElements  An array of the source elements
     * @return array The eager-loading element ID mappings
     */
    private static function _mapLocalized(array $sourceElements): array
    {
        $sourceSiteId = $sourceElements[0]->siteId;
        $otherSiteIds = [];
        foreach (Sites::getAllSites() as $site) {
            if ($site->id !== $sourceSiteId) {
                $otherSiteIds[] = $site->id;
            }
        }

        // Map the source elements to themselves
        $map = [];
        if (! empty($otherSiteIds)) {
            foreach ($sourceElements as $element) {
                $map[] = [
                    'source' => $element->id,
                    'target' => $element->id,
                ];
            }
        }

        return [
            'elementType' => static::class,
            'map' => $map,
            'criteria' => [
                'siteId' => $otherSiteIds,
                'drafts' => null,
                'provisionalDrafts' => null,
                'revisions' => null,
            ],
        ];
    }

    /**
     * Returns an eager-loading map for the source elements' current revisions.
     *
     * @param  ElementInterface[]  $sourceElements  An array of the source elements
     * @return array The eager-loading element ID mappings
     */
    private static function _mapCurrentRevisions(array $sourceElements): array
    {
        // Get the source element IDs
        $sourceElementIds = array_map(fn (ElementInterface $element) => $element->id, $sourceElements);

        $map = DB::table(Table::ELEMENTS, 're')
            ->join(new Alias(Table::REVISIONS, 'r'), 'r.id', '=', 're.revisionId')
            ->join(new Alias(Table::ELEMENTS, 'se'), 'se.id', '=', 'r.canonicalId')
            ->whereColumn('re.dateCreated', '=', 'se.dateUpdated')
            ->whereIn('se.id', $sourceElementIds)
            ->select([
                'se.id as source',
                're.id as target',
            ])
            ->get()
            ->map(fn (object $data) => (array) $data);

        return [
            'elementType' => static::class,
            'map' => $map,
            'criteria' => ['revisions' => true, 'status' => null],
        ];
    }

    /**
     * Returns an eager-loading map for the source elements' current drafts.
     *
     * @param  ElementInterface[]  $sourceElements  An array of the source elements
     * @return array The eager-loading element ID mappings
     */
    private static function _mapDrafts(array $sourceElements): array
    {
        // Get the source element IDs
        $sourceElementIds = array_map(fn (ElementInterface $element) => $element->id, $sourceElements);

        $map = DB::table(Table::DRAFTS, 'd')
            ->join(new Alias(Table::ELEMENTS, 'e'), 'e.draftId', '=', 'd.id')
            ->whereIn('d.canonicalId', $sourceElementIds)
            ->select([
                'd.canonicalId as source',
                'e.id as target',
            ])
            ->get()
            ->map(fn (object $data) => (array) $data);

        return [
            'elementType' => static::class,
            'map' => $map,
            'criteria' => ['drafts' => true],
        ];
    }

    /**
     * Returns an eager-loading map for the source elements' current revisions.
     *
     * @param  ElementInterface[]  $sourceElements  An array of the source elements
     * @return array The eager-loading element ID mappings
     */
    private static function _mapRevisions(array $sourceElements): array
    {
        // Get the source element IDs
        $sourceElementIds = array_map(fn (ElementInterface $element) => $element->id, $sourceElements);

        $map = DB::table(Table::REVISIONS, 'r')
            ->join(new Alias(Table::ELEMENTS, 'e'), 'e.revisionId', '=', 'r.id')
            ->whereIn('r.canonicalId', $sourceElementIds)
            ->select([
                'r.canonicalId as source',
                'e.id as target',
            ])
            ->get()
            ->map(fn (object $data) => (array) $data);

        return [
            'elementType' => static::class,
            'map' => $map,
            'criteria' => ['revisions' => true],
        ];
    }

    /**
     * Returns an eager-loading map for the source elements' draft creators.
     *
     * @param  ElementInterface[]  $sourceElements  An array of the source elements
     * @return array The eager-loading element ID mappings
     */
    private static function _mapDraftCreators(array $sourceElements): array
    {
        // Get the source element IDs
        $sourceElementIds = array_map(fn (ElementInterface $element) => $element->id, $sourceElements);

        $map = DB::table(Table::ELEMENTS, 'e')
            ->join(new Alias(Table::DRAFTS, 'd'), 'd.id', '=', 'e.draftId')
            ->whereIn('e.id', $sourceElementIds)
            ->whereNotNull('d.creatorId')
            ->select([
                'e.id as source',
                'd.creatorId as target',
            ])
            ->get()
            ->map(fn (object $data) => (array) $data);

        return [
            'elementType' => User::class,
            'map' => $map,
        ];
    }

    /**
     * Returns an eager-loading map for the source elements' revision creators.
     *
     * @param  ElementInterface[]  $sourceElements  An array of the source elements
     * @return array The eager-loading element ID mappings
     */
    private static function _mapRevisionCreators(array $sourceElements): array
    {
        // Get the source element IDs
        $sourceElementIds = array_map(fn (ElementInterface $element) => $element->id, $sourceElements);

        $map = DB::table(Table::ELEMENTS, 'e')
            ->join(new Alias(Table::REVISIONS, 'r'), 'r.id', '=', 'e.revisionId')
            ->whereIn('e.id', $sourceElementIds)
            ->whereNotNull('r.creatorId')
            ->select([
                'e.id as source',
                'r.creatorId as target',
            ])
            ->get()
            ->map(fn (object $data) => (array) $data);

        return [
            'elementType' => User::class,
            'map' => $map,
        ];
    }

    /**
     * Returns whether this element has any eager-loaded elements for a given handle.
     *
     * @param  string  $handle  The handle to check for
     * @return bool Whether the eager-loaded elements exist
     *
     * @see SetEagerLoadedElements()
     * @since 3.5.0
     */
    public function hasEagerLoadedElements(string $handle): bool
    {
        if (! isset($this->_eagerLoadedElements[$handle])) {
            // See if we have it stored with the field layout provider's handle
            $providerHandle = $this->providerHandle();
            if ($providerHandle !== null && isset($this->_eagerLoadedElements["$providerHandle:$handle"])) {
                $handle = "$providerHandle:$handle";
            }
        }

        return isset($this->_eagerLoadedElements[$handle]);
    }

    /**
     * Returns eager-loaded elements for a given handle.
     *
     * @param  string  $handle  The handle to check for
     * @return ElementCollection|null The eager-loaded elements, or null if they don't exist
     *
     * @see SetEagerLoadedElements()
     * @since 3.5.0
     */
    public function getEagerLoadedElements(string $handle): ?ElementCollection
    {
        if (! isset($this->_eagerLoadedElements[$handle])) {
            // See if we have it stored with the field layout provider's handle
            $providerHandle = $this->providerHandle();
            if ($providerHandle !== null && isset($this->_eagerLoadedElements["$providerHandle:$handle"])) {
                $handle = "$providerHandle:$handle";
            } else {
                return null;
            }
        }

        $elements = $this->_eagerLoadedElements[$handle];
        ElementHelper::setNextPrevOnElements($elements);

        return $elements;
    }

    /**
     * Sets eager-loaded elements for a given handle.
     *
     * @param  string  $handle  The handle to store the elements under
     * @param  ElementInterface[]  $elements  The eager-loaded elements
     * @param  EagerLoadPlan  $plan  The eager-load plan that was used to load the elements
     *
     * @see getEagerLoadedElements()
     * @since 3.5.0
     */
    public function setEagerLoadedElements(string $handle, array $elements, EagerLoadPlan $plan): void
    {
        switch ($plan->handle) {
            case 'parent':
                $this->_parent = $elements[0] ?? false;
                break;
            case 'currentRevision':
                $this->currentRevision = $elements[0] ?? false;
                break;
            case 'draftCreator':
                /** @var User[] $elements */
                $this->setDraftCreator($elements[0] ?? null);
                break;
            case 'revisionCreator':
                /** @var User[] $elements */
                $this->setRevisionCreator($elements[0] ?? null);
                break;
            default:
                // Fire a 'setEagerLoadedElements' event
                event($event = new SetEagerLoadedElements(
                    element: $this,
                    handle: $handle,
                    elements: $elements,
                    plan: $plan,
                ));

                if ($event->handled) {
                    break;
                }

                // No takers. Just store it in the internal array then.
                $this->_eagerLoadedElements[$handle] = ElementCollection::make($elements);
        }
    }

    /**
     * Sets whether eager-loaded elements should be lazy-loaded for a given handle.
     *
     * @param  string  $handle  The handle to mark as lazy-loaded
     * @param  bool  $value  Whether the elements should be lazy-loaded
     *
     * @since 5.0.0
     */
    public function setLazyEagerLoadedElements(string $handle, bool $value = true): void
    {
        $this->_lazyEagerLoadedElements[$handle] = $value;
    }

    /**
     * Returns the number of eager-loaded elements for a given handle.
     *
     * @param  string  $handle  The handle to check for
     * @return int|null The number of eager-loaded elements, or null if they don't exist
     *
     * @see setEagerLoadedElementCount()
     * @since 3.5.0
     */
    public function getEagerLoadedElementCount(string $handle): ?int
    {
        if (! isset($this->_eagerLoadedElementCounts[$handle])) {
            // See if we have it stored with the field layout provider's handle
            $providerHandle = $this->providerHandle();
            if ($providerHandle !== null && isset($this->_eagerLoadedElementCounts["$providerHandle:$handle"])) {
                $handle = "$providerHandle:$handle";
            }
        }

        return $this->_eagerLoadedElementCounts[$handle] ?? null;
    }

    /**
     * Sets the number of eager-loaded elements for a given handle.
     *
     * @param  string  $handle  The handle to store the count under
     * @param  int  $count  The number of eager-loaded elements
     *
     * @see getEagerLoadedElementCount()
     * @since 3.5.0
     */
    public function setEagerLoadedElementCount(string $handle, int $count): void
    {
        $this->_eagerLoadedElementCounts[$handle] = $count;
    }

    /**
     * Returns the field layout provider handle for this element.
     *
     * @return string|null The provider handle, or null if not available
     */
    private function providerHandle(): ?string
    {
        try {
            return $this->getFieldLayout()?->provider?->getHandle();
        } catch (InvalidConfigException) {
            return null;
        }
    }
}
