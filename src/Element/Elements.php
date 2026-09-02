<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Data\EagerLoadPlan;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Exceptions\UnsupportedSiteException;
use CraftCms\Cms\Element\Operations\ElementCanonicalChanges;
use CraftCms\Cms\Element\Operations\ElementDeletions;
use CraftCms\Cms\Element\Operations\ElementDuplicates;
use CraftCms\Cms\Element\Operations\ElementEagerLoader;
use CraftCms\Cms\Element\Operations\ElementPlaceholders;
use CraftCms\Cms\Element\Operations\ElementRefs;
use CraftCms\Cms\Element\Operations\ElementUris;
use CraftCms\Cms\Element\Operations\ElementWrites;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\Exceptions\ElementNotFoundException;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Shared\Exceptions\OperationAbortedException;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use Tpetry\QueryExpressions\Function\String\Lower;
use Tpetry\QueryExpressions\Language\Alias;

class Elements
{
    public const string REF_TAG_PATTERN = '/
        \{                                      # Tags always begin with a `{`
            (?P<elementType>[\w\\\\]+)          # Ref handle or element type class
            \:(?P<ref>[^@\:\}\|]+)              # Identifier (ID, or another format supported by the element type)
            (?:@(?P<site>[^\:\}\|]+))?          # [Optional] Site handle, ID, or UUID
            (?:\:(?P<attr>[^\}\| ]+))?          # [Optional] Attribute, property, or field
            (?:\ *\|\|\ *(?P<fallback>[^\}]+))? # [Optional] Fallback text (if the ref fails to resolve)
        \}                                      # Tags always close with a `}`
    /x';

    public function __construct(
        private readonly ElementPlaceholders $placeholders,
        private readonly ElementTypes $elementTypes,
        private readonly ElementCaches $elementCaches,
    ) {}

    /**
     * Returns the class of an element with a given ID.
     *
     * @param  int  $elementId  The element’s ID
     * @return class-string<ElementInterface>|null The element’s class, or null if it could not be found
     */
    public function getElementTypeById(int $elementId): ?string
    {
        return $this->getElementTypeByKey('id', $elementId);
    }

    /**
     * Returns the class of an element with a given UID.
     *
     * @param  string  $uid  The element’s UID
     * @return string|null The element’s class, or null if it could not be found
     */
    public function getElementTypeByUid(string $uid): ?string
    {
        return $this->getElementTypeByKey('uid', $uid);
    }

    /**
     * Returns the class of an element with a given ID/UID.
     *
     * @param  string  $property  Either `id` or `uid`
     * @param  int|string  $elementId  The element’s ID/UID
     * @return string|null The element’s class, or null if it could not be found
     */
    public function getElementTypeByKey(string $property, int|string $elementId): ?string
    {
        return DB::table(Table::ELEMENTS)
            ->where($property, $elementId)
            ->value('type');
    }

    /**
     * Returns the classes of elements with the given IDs.
     *
     * @param  int[]  $elementIds  The elements’ IDs
     * @return string[]
     */
    public function getElementTypesByIds(array $elementIds): array
    {
        return DB::table(Table::ELEMENTS)
            ->whereIn('id', $elementIds)
            ->distinct()
            ->pluck('type')
            ->all();
    }

    /**
     * Returns all available element classes.
     *
     * @return class-string<ElementInterface>[] The available element classes.
     */
    public function getAllElementTypes(): array
    {
        return $this->elementTypes->types()->all();
    }

    /**
     * Returns an element class by its handle.
     *
     * @param  string  $refHandle  The element class handle
     * @return string|null The element class, or null if it could not be found
     */
    public function getElementTypeByRefHandle(string $refHandle): ?string
    {
        $class = $this->elementTypeByRefHandle($refHandle);

        // Special cases for categories/tags/globals, if they've been removed
        if ($class === false && in_array($refHandle, ['category', 'tag', 'globalset'])) {
            $class = Entry::class;
        }

        return $class ?: null;
    }

    private function elementTypeByRefHandle(string $refHandle): string|false
    {
        if (is_subclass_of($refHandle, ElementInterface::class)) {
            return $refHandle;
        }

        return $this->elementTypes->typeByRefHandle($refHandle) ?? false;
    }

    /**
     * Creates an element with a given config.
     *
     * @template T of ElementInterface
     *
     * @param  class-string<T>|array{type:class-string<T>}  $config  The element’s class name, or its config, with a `type` value
     * @return T The element
     */
    public function createElement(mixed $config): ElementInterface
    {
        if (is_string($config)) {
            $config = ['type' => $config];
        }

        return ComponentHelper::createComponent($config, ElementInterface::class);
    }

    /**
     * Creates an element query for a given element type.
     *
     * @param  class-string<ElementInterface>  $elementType  The element class
     * @return ElementQueryInterface The element query
     *
     * @throws InvalidArgumentException if $elementType is not a valid element
     */
    public function createElementQuery(string $elementType): ElementQueryInterface
    {
        if (! is_subclass_of($elementType, ElementInterface::class)) {
            throw new InvalidArgumentException("$elementType is not a valid element.");
        }

        return $elementType::find();
    }

    // Finding Elements
    // -------------------------------------------------------------------------
    /**
     * Returns an element by its ID.
     *
     * If no element type is provided, the method will first have to run a DB query to determine what type of element
     * the $id is, so you should definitely pass it if it’s known.
     * The element’s status will not be a factor when using this method.
     *
     * @template T of ElementInterface
     *
     * @param  int  $elementId  The element’s ID.
     * @param  class-string<T>|null  $elementType  The element class.
     * @param  int|string|int[]|null  $siteId  The site(s) to fetch the element in.
     *                                         Defaults to the current site.
     * @param  array<string,mixed>  $criteria
     * @return ($elementType is class-string<T> ? T|null : ElementInterface|null) The matching element, or `null`.
     */
    public function getElementById(
        int $elementId,
        ?string $elementType = null,
        array|int|string|null $siteId = null,
        array $criteria = [],
    ): ?ElementInterface {
        return $this->elementByKey('id', $elementId, $elementType, $siteId, $criteria);
    }

    /**
     * Returns an element by its UID.
     *
     * If no element type is provided, the method will first have to run a DB query to determine what type of element
     * the $uid is, so you should definitely pass it if it’s known.
     * The element’s status will not be a factor when using this method.
     *
     * @template T of ElementInterface
     *
     * @param  string  $uid  The element’s UID.
     * @param  class-string<T>|null  $elementType  The element class.
     * @param  int|string|int[]|null  $siteId  The site(s) to fetch the element in.
     *                                         Defaults to the current site.
     * @param  array<string,mixed>  $criteria
     * @return T|null The matching element, or `null`.
     */
    public function getElementByUid(
        string $uid,
        ?string $elementType = null,
        array|int|string|null $siteId = null,
        array $criteria = [],
    ): ?ElementInterface {
        return $this->elementByKey('uid', $uid, $elementType, $siteId, $criteria);
    }

    /**
     * Returns an element by its ID or UID.
     *
     * @template T of ElementInterface
     *
     * @param  string  $property  Either `id` or `uid`
     * @param  int|string  $elementId  The element’s ID/UID
     * @param  class-string<T>|null  $elementType  The element class.
     * @param  int|string|int[]|null  $siteId  The site(s) to fetch the element in.
     *                                         Defaults to the current site.
     * @param  array<string,mixed>  $criteria
     * @return T|null The matching element, or `null`.
     */
    private function elementByKey(
        string $property,
        int|string $elementId,
        ?string $elementType = null,
        array|int|string|null $siteId = null,
        array $criteria = [],
    ): ?ElementInterface {
        if (! $elementId) {
            return null;
        }

        $elementType ??= $this->getElementTypeByKey($property, $elementId);

        if ($elementType === null || ! class_exists($elementType)) {
            return null;
        }

        $query = $this->createElementQuery($elementType)
            ->siteId($siteId)
            ->status(null)
            ->drafts(null)
            ->provisionalDrafts(null)
            ->revisions(null);

        $query->$property = $elementId;

        Typecast::configure($query, $criteria);

        return $query->first();
    }

    /**
     * Returns an element by its URI.
     *
     * @param  string  $uri  The element’s URI.
     * @param  int|null  $siteId  The site to look for the URI in, and to return the element in.
     *                            Defaults to the current site.
     * @param  bool  $enabledOnly  Whether to only look for an enabled element. Defaults to `false`.
     * @return ElementInterface|null The matching element, or `null`.
     */
    public function getElementByUri(string $uri, ?int $siteId = null, bool $enabledOnly = false): ?ElementInterface
    {
        if ($uri === '') {
            $uri = Element::HOMEPAGE_URI;
        }

        $siteId ??= Sites::getCurrentSite()->id;

        // See if we already have a placeholder for this element URI
        $placeholder = $this->placeholders->getPlaceholderByUri($uri, $siteId);

        if ($placeholder !== null) {
            return $placeholder;
        }

        // First get the element ID and type
        $result = DB::table(new Alias(Table::ELEMENTS, 'elements'))
            ->select(['elements.id', 'elements.type'])
            ->join(new Alias(Table::ELEMENTS_SITES, 'elements_sites'), 'elements_sites.elementId', 'elements.id')
            ->where('elements_sites.siteId', $siteId)
            ->whereNull(['elements.draftId', 'elements.revisionId', 'elements.dateDeleted'])
            ->when(
                DB::isMysql(),
                fn (Builder $query) => $query->where('elements_sites.uri', $uri),
                fn (Builder $query) => $query->where(new Lower('elements_sites.uri'), mb_strtolower($uri)),
            )
            ->when(
                $enabledOnly,
                fn (Builder $query) => $query->where([
                    'elements_sites.enabled' => true,
                    'elements.enabled' => true,
                    'elements.archived' => false,
                ]),
            )
            ->first();

        if (! $result || ! is_a($result->type, ElementInterface::class, true)) {
            return null;
        }

        return $this->getElementById($result->id, $result->type, $siteId);
    }

    /**
     * Returns an element’s URI for a given site.
     *
     * @param  int  $elementId  The element’s ID.
     * @param  int  $siteId  The site to search for the element’s URI in.
     * @return string|null The element’s URI or `null` if the element doesn’t exist.
     */
    public function getElementUriForSite(int $elementId, int $siteId): ?string
    {
        return DB::table(Table::ELEMENTS_SITES)
            ->where('elementId', $elementId)
            ->where('siteId', $siteId)
            ->value('uri');
    }

    /**
     * Returns the site IDs that a given element is enabled in.
     *
     * @param  int  $elementId  The element’s ID.
     * @return int[] The site IDs that the element is enabled in. If the element could not be found, an empty array will be returned.
     */
    public function getEnabledSiteIdsForElement(int $elementId): array
    {
        return DB::table(Table::ELEMENTS_SITES)
            ->where('elementId', $elementId)
            ->where('enabled', true)
            ->pluck('siteId')
            ->all();
    }

    // Saving Elements
    // -------------------------------------------------------------------------
    /**
     * Handles all of the routine tasks that go along with saving elements.
     *
     * Those tasks include:
     *
     * - Validating its content (if $validateContent is `true`, or it’s left as `null` and the element is enabled)
     * - Ensuring the element has a title if its type [[Element::hasTitles()|has titles]], and giving it a
     *   default title in the event that $validateContent is set to `false`
     * - Saving a row in the `elements` table
     * - Assigning the element’s ID on the element model, if it’s a new element
     * - Assigning the element’s ID on the element’s content model, if there is one and it’s a new set of content
     * - Updating the search index with new keywords from the element’s content
     * - Setting a unique URI on the element, if it’s supposed to have one.
     * - Saving the element’s row(s) in the `elements_sites` and `content` tables
     * - Deleting any rows in the `elements_sites` and `content` tables that no longer need to be there
     * - Cleaning any template caches that the element was involved in
     *
     * The function will fire `beforeElementSave` and `afterElementSave` events, and will call `beforeSave()`
     *  and `afterSave()` methods on the passed-in element, giving the element opportunities to hook into the
     * save process.
     *
     * Example usage - creating a new entry:
     *
     * ```php
     * $entry = new Entry();
     * $entry->sectionId = 10;
     * $entry->typeId = 1;
     * $entry->authorId = 5;
     * $entry->enabled = true;
     * $entry->title = "Hello World!";
     * $entry->setFieldValues([
     *     'body' => "<p>I can’t believe I literally just called this “Hello World!”.</p>",
     * ]);
     * $success = Elements::saveElement($entry);
     * if (!$success) {
     *     Log::error('Couldn’t save the entry "'.$entry->title.'"', [__METHOD__]);
     * }
     * ```
     *
     * @param  ElementInterface  $element  The element that is being saved
     * @param  bool  $runValidation  Whether the element should be validated
     * @param  bool  $propagate  Whether the element should be saved across all of its supported sites
     *                           (this can only be disabled when updating an existing element)
     * @param  bool|null  $updateSearchIndex  Whether to update the element search index for the element
     *                                        (this will happen via a background job if this is a web request)
     * @param  bool  $forceTouch  Whether to force the `dateUpdated` timestamp to be updated for the element,
     *                            regardless of whether it’s being resaved
     * @param  bool|null  $crossSiteValidate  Whether the element should be validated across all supported sites
     * @param  bool  $saveContent  Whether all the element’s content should be saved. When false (default) only dirty fields will be saved.
     *
     * @throws ElementNotFoundException if $element has an invalid $id
     * @throws \Exception if the $element doesn’t have any supported sites
     * @throws Throwable if reasons
     */
    public function saveElement(
        ElementInterface $element,
        bool $runValidation = true,
        bool $propagate = true,
        ?bool $updateSearchIndex = null,
        bool $forceTouch = false,
        ?bool $crossSiteValidate = false,
        bool $saveContent = false,
    ): bool {
        return app(ElementWrites::class)->saveElement(
            $element,
            $runValidation,
            $propagate,
            $updateSearchIndex,
            $forceTouch,
            $crossSiteValidate,
            $saveContent,
        );
    }

    /**
     * Sets the URI on an element.
     *
     * @throws OperationAbortedException if a unique URI could not be found
     */
    public function setElementUri(ElementInterface $element): void
    {
        app(ElementUris::class)->setElementUri($element);
    }

    /**
     * Merges recent canonical element changes into a given derivative, such as a draft.
     *
     * @param  ElementInterface  $element  The derivative element
     */
    public function mergeCanonicalChanges(ElementInterface $element): void
    {
        app(ElementCanonicalChanges::class)->mergeCanonicalChanges($element);
    }

    /**
     * Updates the canonical element from a given derivative, such as a draft or revision.
     *
     * @template T of ElementInterface
     *
     * @param  T  $element  The derivative element
     * @param  array<string,mixed>  $newAttributes  Any attributes to apply to the canonical element
     * @return T The updated canonical element
     *
     * @throws InvalidArgumentException if the element is already a canonical element
     */
    public function updateCanonicalElement(ElementInterface $element, array $newAttributes = []): ElementInterface
    {
        return app(ElementCanonicalChanges::class)->updateCanonicalElement($element, $newAttributes);
    }

    /**
     * Resaves all elements that match a given element query.
     *
     * @param  ElementQueryInterface  $query  The element query to fetch elements with
     * @param  bool  $continueOnError  Whether to continue going if an error occurs
     * @param  bool  $skipRevisions  Whether elements that are (or belong to) a revision should be skipped
     * @param  bool|null  $updateSearchIndex  Whether to update the element search index for the element
     *                                        (this will happen via a background job if this is a web request)
     * @param  bool  $touch  Whether to update the `dateUpdated` timestamps for the elements
     *
     * @throws Throwable if reasons
     */
    public function resaveElements(
        ElementQueryInterface $query,
        bool $continueOnError = false,
        bool $skipRevisions = true,
        ?bool $updateSearchIndex = null,
        bool $touch = false,
    ): void {
        app(ElementWrites::class)->resaveElements($query, $continueOnError, $skipRevisions, $updateSearchIndex, $touch);
    }

    /**
     * Propagates all elements that match a given element query to another site(s).
     *
     * @param  ElementQueryInterface  $query  The element query to fetch elements with
     * @param  int|int[]|null  $siteIds  The site ID(s) that the elements should be propagated to. If null, elements will be
     * @param  bool  $continueOnError  Whether to continue going if an error occurs
     */
    public function propagateElements(
        ElementQueryInterface $query,
        array|int|null $siteIds = null,
        bool $continueOnError = false,
    ): void {
        app(ElementWrites::class)->propagateElements($query, $siteIds, $continueOnError);
    }

    /**
     * Propagates an element to a different site.
     *
     * @param  ElementInterface  $element  The element to propagate
     * @param  int  $siteId  The site ID that the element should be propagated to
     * @param  ElementInterface|false|null  $siteElement  The element loaded for the propagated site (only pass this if you
     *                                                    already had a reason to load it). Set to `false` if it is known to not exist yet.
     * @return ElementInterface The element in the target site
     *
     * @throws \Exception if the element couldn't be propagated
     * @throws UnsupportedSiteException if the element doesn’t support `$siteId`
     */
    public function propagateElement(
        ElementInterface $element,
        int $siteId,
        ElementInterface|false|null $siteElement = null,
    ): ElementInterface {
        return app(ElementWrites::class)->propagateElement($element, $siteId, $siteElement);
    }

    /**
     * Duplicates an element.
     *
     * @template T of ElementInterface
     *
     * @param  T  $element  the element to duplicate
     * @param  array<string,mixed>  $newAttributes  any attributes to apply to the duplicate. This can contain a `siteAttributes` key,
     *                                              set to an array of site-specific attribute array, indexed by site IDs.
     * @param  bool  $placeInStructure  whether to position the cloned element after the original one in its structure.
     *                                  (This will only happen if the duplicated element is canonical.)
     * @param  bool  $asUnpublishedDraft  whether the duplicate should be created as unpublished draft
     * @param  bool  $checkAuthorization  whether to ensure the current user is authorized to save the new element,
     *                                    once its new attributes have been applied to it
     * @param  bool  $copyModifiedFields  whether to copy modified attribute/field data over to the duplicated element
     * @return T the duplicated element
     *
     * @throws UnsupportedSiteException if the element is being duplicated into a site it doesn’t support
     * @throws InvalidElementException if saveElement() returns false for any of the sites
     * @throws HttpException if the user isn't authorized to save the duplicated element
     * @throws Throwable if reasons
     */
    public function duplicateElement(
        ElementInterface $element,
        array $newAttributes = [],
        bool $placeInStructure = true,
        bool $asUnpublishedDraft = false,
        bool $checkAuthorization = false,
        bool $copyModifiedFields = false,
    ): ElementInterface {
        return app(ElementDuplicates::class)->duplicateElement(
            $element,
            $newAttributes,
            $placeInStructure,
            $asUnpublishedDraft,
            $checkAuthorization,
            $copyModifiedFields,
        );
    }

    /**
     * Updates an element’s slug and URI, along with any descendants.
     *
     * @param  ElementInterface  $element  The element to update.
     * @param  bool  $updateOtherSites  Whether the element’s other sites should also be updated.
     * @param  bool  $updateDescendants  Whether the element’s descendants should also be updated.
     * @param  bool  $queue  Whether the element’s slug and URI should be updated via a job in the queue.
     *
     * @throws OperationAbortedException if a unique URI can’t be generated based on the element’s URI format
     */
    public function updateElementSlugAndUri(
        ElementInterface $element,
        bool $updateOtherSites = true,
        bool $updateDescendants = true,
        bool $queue = false,
    ): void {
        app(ElementUris::class)->updateElementSlugAndUri($element, $updateOtherSites, $updateDescendants, $queue);
    }

    /**
     * Updates an element’s slug and URI, for any sites besides the given one.
     *
     * @param  ElementInterface  $element  The element to update.
     */
    public function updateElementSlugAndUriInOtherSites(ElementInterface $element): void
    {
        app(ElementUris::class)->updateElementSlugAndUriInOtherSites($element);
    }

    /**
     * Updates an element’s descendants’ slugs and URIs.
     *
     * @param  ElementInterface  $element  The element whose descendants should be updated.
     * @param  bool  $updateOtherSites  Whether the element’s other sites should also be updated.
     * @param  bool  $queue  Whether the descendants’ slugs and URIs should be updated via a job in the queue.
     */
    public function updateDescendantSlugsAndUris(
        ElementInterface $element,
        bool $updateOtherSites = true,
        bool $queue = false,
    ): void {
        app(ElementUris::class)->updateDescendantSlugsAndUris($element, $updateOtherSites, $queue);
    }

    /**
     * Merges two elements together by their IDs.
     *
     * This method will update the following:
     * - Any relations involving the merged element
     * - Any structures that contain the merged element
     * - Any reference tags in textual custom fields referencing the merged element
     *
     * @param  int  $mergedElementId  The ID of the element that is going away.
     * @param  int  $prevailingElementId  The ID of the element that is sticking around.
     * @return bool Whether the elements were merged successfully.
     *
     * @throws ElementNotFoundException if one of the element IDs don’t exist.
     */
    public function mergeElementsByIds(int $mergedElementId, int $prevailingElementId): bool
    {
        return app(ElementDeletions::class)->mergeElementsByIds($mergedElementId, $prevailingElementId);
    }

    /**
     * Merges two elements together.
     *
     * This method will update the following:
     * - Any relations involving the merged element
     * - Any structures that contain the merged element
     * - Any reference tags in textual custom fields referencing the merged element
     *
     * @param  ElementInterface  $mergedElement  The element that is going away.
     * @param  ElementInterface  $prevailingElement  The element that is sticking around.
     * @return bool Whether the elements were merged successfully.
     */
    public function mergeElements(ElementInterface $mergedElement, ElementInterface $prevailingElement): bool
    {
        return app(ElementDeletions::class)->mergeElements($mergedElement, $prevailingElement);
    }

    /**
     * Deletes an element by its ID.
     *
     * @param  int  $elementId  The element’s ID
     * @param  class-string<ElementInterface>|null  $elementType  The element class.
     * @param  int|null  $siteId  The site to fetch the element in.
     *                            Defaults to the current site.
     * @param  bool  $hardDelete  Whether the element should be hard-deleted immediately, instead of soft-deleted
     * @return bool Whether the element was deleted successfully
     */
    public function deleteElementById(
        int $elementId,
        ?string $elementType = null,
        ?int $siteId = null,
        bool $hardDelete = false,
    ): bool {
        return app(ElementDeletions::class)->deleteElementById($elementId, $elementType, $siteId, $hardDelete);
    }

    /**
     * Deletes an element.
     *
     * @param  ElementInterface  $element  The element to be deleted
     * @param  bool  $hardDelete  Whether the element should be hard-deleted immediately, instead of soft-deleted
     * @return bool Whether the element was deleted successfully
     *
     * @throws Throwable
     */
    public function deleteElement(ElementInterface $element, bool $hardDelete = false): bool
    {
        return app(ElementDeletions::class)->deleteElement($element, $hardDelete);
    }

    /**
     * Deletes an element in the site it’s loaded in.
     */
    public function deleteElementForSite(ElementInterface $element): void
    {
        app(ElementDeletions::class)->deleteElementForSite($element);
    }

    /**
     * Deletes elements in the site they are currently loaded in.
     *
     * @param  ElementInterface[]  $elements
     *
     * @throws InvalidArgumentException if all elements don’t have the same type and site ID.
     */
    public function deleteElementsForSite(array $elements): void
    {
        app(ElementDeletions::class)->deleteElementsForSite($elements);
    }

    /**
     * Restores an element.
     *
     *
     * @return bool Whether the element was restored successfully
     */
    public function restoreElement(ElementInterface $element): bool
    {
        return app(ElementDeletions::class)->restoreElement($element);
    }

    /**
     * Restores multiple elements.
     *
     * @param  ElementInterface[]  $elements
     * @return bool Whether at least one element was restored successfully
     *
     * @throws UnsupportedSiteException if an element is being restored for a site it doesn’t support
     * @throws Throwable if reasons
     */
    public function restoreElements(array $elements): bool
    {
        return app(ElementDeletions::class)->restoreElements($elements);
    }

    /**
     * Reorders nested elements for a given owner element.
     *
     * @param  ElementInterface  $owner  The owner element
     * @param  ElementQueryInterface|ElementCollection<int, ElementInterface>  $nestedElements  The owner’s nested elements
     * @param  int[]  $elementIds  The nested element IDs that are being moved, in their new relative order
     * @param  int  $offset  The zero-based offset that `$elementIds` should be inserted at, relative to the owner’s
     *                       other nested elements
     */
    public function reorderNestedElements(
        ElementInterface $owner,
        ElementQueryInterface|ElementCollection $nestedElements,
        array $elementIds,
        int $offset,
    ): void {
        $elementIds = array_map(fn ($id) => (int) $id, $elementIds);

        if ($nestedElements instanceof ElementQueryInterface) {
            $oldSortOrders = (clone $nestedElements)
                ->status(null)
                ->asArray()
                ->select(['id', 'sortOrder'])
                ->pluck('sortOrder', 'id')
                ->all();
        } else {
            $oldSortOrders = $nestedElements
                ->keyBy(fn (ElementInterface $element) => $element->id)
                /** @phpstan-ignore-next-line */
                ->map(fn (NestedElementInterface $element) => $element->getSortOrder())
                ->all();
        }

        // Build the full list of IDs in the new sort order
        $allIds = array_diff(array_keys($oldSortOrders), $elementIds);
        array_splice($allIds, $offset, 0, $elementIds);

        // Update all the incorrect sort orders
        foreach ($allIds as $i => $id) {
            $sortOrder = $i + 1;
            if (! isset($oldSortOrders[$id]) || $sortOrder !== $oldSortOrders[$id]) {
                DB::table(Table::ELEMENTS_OWNERS)
                    ->where('ownerId', $owner->id)
                    ->where('elementId', $id)
                    ->update([
                        'sortOrder' => $sortOrder,
                    ]);
            }
        }

        $this->elementCaches->invalidateForElement($owner);
    }

    // Misc
    // -------------------------------------------------------------------------

    /**
     * Parses a string for element [reference tags](https://craftcms.com/docs/5.x/system/reference-tags.html).
     *
     * @param  string  $str  The string to parse
     * @param  int|null  $defaultSiteId  The default site ID to query the elements in
     * @return string The parsed string
     */
    public function parseRefs(string $str, ?int $defaultSiteId = null): string
    {
        return app(ElementRefs::class)->parseRefs($str, $defaultSiteId);
    }

    /**
     * Stores a placeholder element that element queries should use instead of populating a new element with a
     * matching ID and site ID.
     *
     * This is used by Live Preview and Sharing features.
     *
     * @param  ElementInterface  $element  The element currently being edited by Live Preview.
     *
     * @throws InvalidArgumentException if the element is missing an ID
     *
     * @see getPlaceholderElement()
     */
    public function setPlaceholderElement(ElementInterface $element): void
    {
        $this->placeholders->setPlaceholderElement($element);
    }

    /**
     * Returns all placeholder elements.
     *
     * @return ElementInterface[]
     */
    public function getPlaceholderElements(): array
    {
        return $this->placeholders->getPlaceholderElements();
    }

    /**
     * Returns a placeholder element by its ID and site ID.
     *
     * @param  int  $sourceId  The element’s ID
     * @param  int  $siteId  The element’s site ID
     * @return ElementInterface|null The placeholder element if one exists, or null.
     *
     * @see setPlaceholderElement()
     */
    public function getPlaceholderElement(int $sourceId, int $siteId): ?ElementInterface
    {
        return $this->placeholders->getPlaceholderElement($sourceId, $siteId);
    }

    /**
     * Normalizes a `with` element query param into an array of eager-loading plans.
     *
     *
     * @phpstan-param string|array<EagerLoadPlan|array<array-key,mixed>|string> $with
     *
     * @return EagerLoadPlan[]
     */
    public function createEagerLoadingPlans(string|array $with): array
    {
        return app(ElementEagerLoader::class)->createEagerLoadingPlans($with);
    }

    /**
     * Eager-loads additional elements onto a given set of elements.
     *
     * @param  class-string<ElementInterface>  $elementType  The root element type class
     * @param  ElementInterface[]|Collection<array-key,ElementInterface>  $elements  The root element models that should be updated with the eager-loaded elements
     * @param  array<int,string|array<array-key,mixed>|EagerLoadPlan>|string  $with  Dot-delimited paths of the elements that should be eager-loaded into the root elements
     */
    public function eagerLoadElements(string $elementType, array|Collection $elements, array|string $with): void
    {
        app(ElementEagerLoader::class)->eagerLoadElements($elementType, $elements, $with);
    }
}
