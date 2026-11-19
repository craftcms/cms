<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use CraftCms\Cms\Database\Expressions\OrderByPlaceholderExpression;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Data\EagerLoadInfo;
use CraftCms\Cms\Element\Data\EagerLoadPlan;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Events\ElementEagerLoadingMapResolving;
use CraftCms\Cms\Element\Events\SetEagerLoadedElements;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Field\Contracts\EagerLoadingFieldInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Contracts\RelationalFieldInterface;
use CraftCms\Cms\Field\Contracts\TracksReferencesFieldInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\ElementCaches;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

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
     */
    public ?array $elementQueryResult = null;

    /**
     * @var EagerLoadInfo|null Info about the eager loading setup used to query this element.
     */
    public ?EagerLoadInfo $eagerLoadInfo = null;

    /**
     * @var array<string,ElementCollection<array-key,ElementInterface>>
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
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle): array|null|false
    {
        if (str_starts_with($handle, 'relatedBy:')) {
            return self::_mapRelatedBy($sourceElements, substr($handle, strlen('relatedBy:')));
        }

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
        event($event = new ElementEagerLoadingMapResolving(
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
     * @return EagerLoadingMap|null The eager-loading element ID mappings, or null if the result should be ignored
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
                if (! ($descendantStructureDatum['structureId'] === $elementStructureDatum['structureId'] && $descendantStructureDatum['lft'] > $elementStructureDatum['lft'] && $descendantStructureDatum['rgt'] < $elementStructureDatum['rgt'])) {
                    continue;
                }
                if (! (! $children || $descendantStructureDatum['level'] === $elementStructureDatum['level'] + 1)) {
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
     * @return EagerLoadingMap|null The eager-loading element ID mappings, or null if the result should be ignored
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
                    $ancestorStructureDatum['structureId'] === $elementStructureDatum['structureId'] &&
                    $ancestorStructureDatum['lft'] < $elementStructureDatum['lft'] &&
                    $ancestorStructureDatum['rgt'] > $elementStructureDatum['rgt'] &&
                    (! $parents || $ancestorStructureDatum['level'] === $elementStructureDatum['level'] - 1)
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
     * @return array<int,array{structureId:int,elementId:int,lft:int,rgt:int,level?:int}>
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
     * @return EagerLoadingMap The eager-loading element ID mappings
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
     * @return EagerLoadingMap The eager-loading element ID mappings
     */
    private static function _mapCurrentRevisions(array $sourceElements): array
    {
        // Get the source element IDs
        $sourceElementIds = array_map(fn (ElementInterface $element) => $element->id, $sourceElements);

        $map = DB::table(Table::ELEMENTS, 're')
            ->join(Table::REVISIONS.' as r', 'r.id', '=', 're.revisionId')
            ->join(Table::ELEMENTS.' as se', 'se.id', '=', 'r.canonicalId')
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
     * @return EagerLoadingMap The eager-loading element ID mappings
     */
    private static function _mapDrafts(array $sourceElements): array
    {
        // Get the source element IDs
        $sourceElementIds = array_map(fn (ElementInterface $element) => $element->id, $sourceElements);

        $map = DB::table(Table::DRAFTS, 'd')
            ->join(Table::ELEMENTS.' as e', 'e.draftId', '=', 'd.id')
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
     * @return EagerLoadingMap The eager-loading element ID mappings
     */
    private static function _mapRevisions(array $sourceElements): array
    {
        // Get the source element IDs
        $sourceElementIds = array_map(fn (ElementInterface $element) => $element->id, $sourceElements);

        $map = DB::table(Table::REVISIONS, 'r')
            ->join(Table::ELEMENTS.' as e', 'e.revisionId', '=', 'r.id')
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
     * @return EagerLoadingMap The eager-loading element ID mappings
     */
    private static function _mapDraftCreators(array $sourceElements): array
    {
        // Get the source element IDs
        $sourceElementIds = array_map(fn (ElementInterface $element) => $element->id, $sourceElements);

        $map = DB::table(Table::ELEMENTS, 'e')
            ->join(Table::DRAFTS.' as d', 'd.id', '=', 'e.draftId')
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
     * @return EagerLoadingMap The eager-loading element ID mappings
     */
    private static function _mapRevisionCreators(array $sourceElements): array
    {
        // Get the source element IDs
        $sourceElementIds = array_map(fn (ElementInterface $element) => $element->id, $sourceElements);

        $map = DB::table(Table::ELEMENTS, 'e')
            ->join(Table::REVISIONS.' as r', 'r.id', '=', 'e.revisionId')
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
     * Returns an eager-loading map for the elements that relate to or reference the source elements through a field.
     *
     * @param  ElementInterface[]  $sourceElements  An array of the source elements
     * @param  string  $fieldHandle  The handle of the field that holds the relations or references
     * @return EagerLoadingMap|false The eager-loading element ID mappings, or `false` if the field doesn’t store relations or references
     */
    private static function _mapRelatedBy(array $sourceElements, string $fieldHandle): array|false
    {
        $field = Fields::getFieldByHandle($fieldHandle);

        $tables = array_keys(array_filter([
            Table::RELATIONS => $field instanceof RelationalFieldInterface,
            Table::FIELDREFERENCES => $field instanceof TracksReferencesFieldInterface,
        ]));

        if (empty($tables)) {
            Log::warning("Attempting to eager-load relatedBy elements for a field that doesn’t store relations or references: $fieldHandle");

            return false;
        }

        // Any element that uses the field can start relating to the source elements, so caches need to be
        // invalidated when any of them are saved, as they are for a relatedBy() query
        if (ElementCaches::isCollectingCacheInfo()) {
            ElementCaches::collectCacheTags(array_map(
                fn (string $elementType) => "element::$elementType::*",
                self::_elementTypesWithField($field),
            ));
        }

        // Relations and references point to canonical elements
        $sourceElementIdsByCanonicalId = [];
        foreach ($sourceElements as $sourceElement) {
            $sourceElementIdsByCanonicalId[$sourceElement->getCanonicalId()][] = $sourceElement->id;
        }

        // The sites each relation or reference was saved for, indexed by the related element and the target,
        // unless it counts in every site
        $siteIds = [];
        $countsInEverySite = [];
        $elementTypes = [];

        foreach ($tables as $table) {
            $rows = DB::table($table, 'r')
                ->join(Table::ELEMENTS.' as e', 'e.id', '=', 'r.sourceId')
                ->select(['r.sourceId', 'r.targetId', 'r.sourceSiteId', 'e.type'])
                ->where('r.fieldId', $field->id)
                ->whereIn('r.targetId', array_keys($sourceElementIdsByCanonicalId))
                ->orderBy('r.sourceId')
                ->get();

            foreach ($rows as $row) {
                $elementTypes[$row->sourceId] = $row->type;

                // Relations and references stored for one site count for every site of elements that aren't
                // localized, such as users, which store them for the primary site
                $siteId = $row->sourceSiteId !== null && (! is_subclass_of($row->type, ElementInterface::class) || $row->type::isLocalized())
                    ? (int) $row->sourceSiteId
                    : null;

                $siteIds[$row->sourceId][$row->targetId] ??= [];

                if ($siteId === null) {
                    $countsInEverySite[$row->sourceId][$row->targetId] = true;
                } else {
                    $siteIds[$row->sourceId][$row->targetId][] = $siteId;
                }
            }
        }

        // The eager loader only keeps the mappings saved for one of the sites it queries, so the results match
        // the `relatedTo` param's, even when the query asks for another site than the source elements'
        $map = [];
        foreach ($siteIds as $relatedElementId => $siteIdsByTargetId) {
            foreach ($siteIdsByTargetId as $targetId => $targetSiteIds) {
                foreach ($sourceElementIdsByCanonicalId[$targetId] as $sourceElementId) {
                    $mapping = ['source' => $sourceElementId, 'target' => $relatedElementId, 'elementType' => $elementTypes[$relatedElementId]];
                    if (! isset($countsInEverySite[$relatedElementId][$targetId])) {
                        $mapping['siteId'] = $targetSiteIds;
                    }
                    $map[] = $mapping;
                }
            }
        }

        // Each mapping has its element's type, so the elements are grouped by type. They're returned in each
        // element type's default order, as they are when relatedBy() queries them.
        return [
            'map' => $map,
            'criteria' => ['orderBy' => new OrderByPlaceholderExpression],
        ];
    }

    /**
     * Returns the elements that relate to or reference this element through a field, such as the entries that
     * select a category in their `categories` field.
     *
     * The field can be a relation field or a reference-tracking field, such as a Link field. Pass a path like
     * `myMatrix.categories` to get the owners of the nested entries that hold the field.
     *
     * Eager-load the elements with `relatedBy:<fieldHandle>`, such as `with(['relatedBy:categories'])`, or with
     * `eagerly()` on the returned query. Matrix fields and paths, and queries for an element type passed to this
     * method, can’t be eager-loaded.
     *
     * @param  string  $field  The field handle
     * @param  class-string<ElementInterface>|null  $elementType  The type of the elements to return. Required when
     *                                                            more than one element type uses the field.
     * @return ElementQueryInterface|ElementCollection<array-key,ElementInterface>
     *
     * @throws InvalidArgumentException if the field doesn’t exist, or the element type can’t be determined
     */
    public function relatedBy(string $field, ?string $elementType = null): ElementQueryInterface|ElementCollection
    {
        $eagerLoadedElements = $this->getEagerLoadedElements("relatedBy:$field");

        if ($eagerLoadedElements !== null) {
            return $elementType === null
                ? $eagerLoadedElements
                : $eagerLoadedElements->filter(fn (ElementInterface $element) => $element instanceof $elementType)->values();
        }

        // Only fields that store relations or references can be eager-loaded, not Matrix fields
        $eagerLoadable = $elementType === null && ! str_contains($field, '.') && (
            ($fieldObject = Fields::getFieldByHandle($field)) instanceof RelationalFieldInterface ||
            $fieldObject instanceof TracksReferencesFieldInterface
        );
        $elementType ??= self::_relatedByElementType($field);

        $relatedTo = ['targetElement' => $this, 'field' => $field];

        // The inverse condition is applied when the query runs rather than through the `relatedTo` param, so it
        // stays out of the eager-loading criteria (it only targets this element), while any `relatedTo` or
        // `andRelatedTo()` criteria the caller adds still apply to eager-loaded results.
        $query = Elements::createElementQuery($elementType)
            ->siteId($this->siteId)
            ->beforeQuery(static fn (ElementQuery $elementQuery) => ElementQuery::applyRelatedTo($elementQuery, $relatedTo, $elementQuery));

        // Let `eagerly()` load the elements for all of this element's query results at once, when the field is
        // used by a single element type. The handle doesn't get the field layout provider's prefix, as the
        // inverse relations are the same for every provider.
        if ($eagerLoadable) {
            // The eager loader queries each source element's own site, unless the query asks for another one
            $query->prepForEagerLoading("relatedBy:$field", $this)
                ->excludeImplicitEagerLoadCriteria('siteId');
            $query->eagerLoadHandle = "relatedBy:$field";
        }

        return $query;
    }

    /**
     * Returns the element type whose field layouts include a field.
     *
     * @return class-string<ElementInterface>
     *
     * @throws InvalidArgumentException if the field doesn’t exist, or it isn’t used by exactly one element type
     */
    private static function _relatedByElementType(string $fieldPath): string
    {
        $fieldHandle = explode('.', $fieldPath, 2)[0];
        $field = Fields::getFieldByHandle($fieldHandle);

        if (! $field) {
            throw new InvalidArgumentException("Invalid field handle: $fieldHandle");
        }

        $elementTypes = self::_elementTypesWithField($field);

        if (count($elementTypes) !== 1) {
            throw new InvalidArgumentException(
                empty($elementTypes)
                    ? "No element type uses the field “{$fieldHandle}”, so pass the element type to relatedBy()."
                    : "More than one element type uses the field “{$fieldHandle}”, so pass the element type to relatedBy().",
            );
        }

        return $elementTypes[0];
    }

    /**
     * Returns the element types whose field layouts include a field.
     *
     * @return list<class-string<ElementInterface>>
     */
    private static function _elementTypesWithField(FieldInterface $field): array
    {
        return Fields::getAllLayouts()
            ->filter(fn (FieldLayout $layout) => $layout->type !== null && Arr::first(
                $layout->getCustomFields(),
                fn (FieldInterface $layoutField) => $layoutField->id === $field->id,
            ) !== null)
            ->pluck('type')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Returns whether this element has any eager-loaded elements for a given handle.
     *
     * @param  string  $handle  The handle to check for
     * @return bool Whether the eager-loaded elements exist
     *
     * @see SetEagerLoadedElements()
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
     * @return ElementCollection<array-key,ElementInterface>|null The eager-loaded elements, or null if they don't exist
     *
     * @see SetEagerLoadedElements()
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
        } catch (Throwable) {
            return null;
        }
    }
}
