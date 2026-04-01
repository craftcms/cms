<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use craft\base\ElementInterface;
use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Actions\DeleteElementAction;
use CraftCms\Cms\Element\Actions\DeleteElementsForSiteAction;
use CraftCms\Cms\Element\Actions\DuplicateElementAction;
use CraftCms\Cms\Element\Actions\MergeCanonicalChangesAction;
use CraftCms\Cms\Element\Actions\MergeElementsAction;
use CraftCms\Cms\Element\Actions\PropagateElementsAction;
use CraftCms\Cms\Element\Actions\ResaveElementsAction;
use CraftCms\Cms\Element\Actions\SaveElementAction;
use CraftCms\Cms\Element\Actions\UpdateCanonicalElementAction;
use CraftCms\Cms\Element\Events\AfterUpdateSlugAndUri;
use CraftCms\Cms\Element\Events\BeforeUpdateSlugAndUri;
use CraftCms\Cms\Element\Events\SetElementUri;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Exceptions\UnsupportedSiteException;
use CraftCms\Cms\Element\Jobs\UpdateElementSlugsAndUris;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\Exceptions\ElementNotFoundException;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Shared\Exceptions\OperationAbortedException;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Typecast;
use Exception;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use Tpetry\QueryExpressions\Language\Alias;

#[Singleton]
class Elements
{
    /**
     * @see setPlaceholderElement()
     * @see getElementByUri()
     */
    private array $_placeholderUris;

    public function __construct(
        private readonly ElementCaches $elementCaches,
    ) {}

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
     * @return T|null The matching element, or `null`.
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

        $elementType ??= $this->elementTypeByKey($property, $elementId);

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
        if (isset($this->_placeholderUris[$uri][$siteId])) {
            return $this->_placeholderUris[$uri][$siteId];
        }

        // First get the element ID and type
        $result = DB::table(new Alias(Table::ELEMENTS, 'elements'))
            ->select(['elements.id', 'elements.type'])
            ->join(new Alias(Table::ELEMENTS_SITES, 'elements_sites'), 'elements_sites.elementId', 'elements.id')
            ->where('elements_sites.siteId', $siteId)
            ->whereNull(['elements.draftId', 'elements.revisionId', 'elements.dateDeleted'])
            ->where('elements_sites.uriLower', mb_strtolower($uri))
            ->when(
                $enabledOnly,
                fn (Builder $query) => $query->where([
                    'elements_sites.enabled' => true,
                    'elements.enabled' => true,
                    'elements.archived' => false,
                ]),
            )
            ->first();

        return $result ? $this->getElementById($result->id, $result->type, $siteId) : null;
    }

    /**
     * Returns the class of an element with a given ID.
     *
     * @param  int  $elementId  The element’s ID
     * @return class-string<ElementInterface>|null The element’s class, or null if it could not be found
     */
    public function getElementTypeById(int $elementId): ?string
    {
        return $this->elementTypeByKey('id', $elementId);
    }

    /**
     * Returns the class of an element with a given UID.
     *
     * @param  string  $uid  The element’s UID
     * @return string|null The element’s class, or null if it could not be found
     *
     * @since 3.5.13
     */
    public function getElementTypeByUid(string $uid): ?string
    {
        return $this->elementTypeByKey('uid', $uid);
    }

    /**
     * Returns the class of an element with a given ID/UID.
     *
     * @param  string  $property  Either `id` or `uid`
     * @param  int|string  $elementId  The element’s ID/UID
     * @return string|null The element’s class, or null if it could not be found
     */
    private function elementTypeByKey(string $property, int|string $elementId): ?string
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
     * @throws Exception if the $element doesn’t have any supported sites
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
        // Force propagation for new elements
        $propagate = ! $element->id || $propagate;

        // Not currently being duplicated
        $duplicateOf = $element->duplicateOf;
        $element->duplicateOf = null;

        // Force isNewForSite = false here, in case the element is getting saved recursively
        // (see https://github.com/craftcms/cms/issues/15517)
        $isNewForSite = $element->isNewForSite;
        $element->isNewForSite = false;

        $success = app(SaveElementAction::class)->handle(
            $element,
            $runValidation,
            $propagate,
            $updateSearchIndex,
            forceTouch: $forceTouch,
            crossSiteValidate: $crossSiteValidate,
            saveContent: $saveContent,
        );

        $element->duplicateOf = $duplicateOf;
        $element->isNewForSite = $isNewForSite;

        return $success;
    }

    /**
     * Sets the URI on an element.
     *
     * @throws OperationAbortedException if a unique URI could not be found
     */
    public function setElementUri(ElementInterface $element): void
    {
        event($event = new SetElementUri($element));

        if ($event->handled) {
            return;
        }

        ElementHelper::setUniqueUri($element);
    }

    /**
     * Merges recent canonical element changes into a given derivative, such as a draft.
     *
     * @param  ElementInterface  $element  The derivative element
     */
    public function mergeCanonicalChanges(ElementInterface $element): void
    {
        app(MergeCanonicalChangesAction::class)->handle($element);
    }

    /**
     * Updates the canonical element from a given derivative, such as a draft or revision.
     *
     * @template T of ElementInterface
     *
     * @param  T  $element  The derivative element
     * @param  array  $newAttributes  Any attributes to apply to the canonical element
     * @return T The updated canonical element
     *
     * @throws InvalidArgumentException if the element is already a canonical element
     */
    public function updateCanonicalElement(ElementInterface $element, array $newAttributes = []): ElementInterface
    {
        return app(UpdateCanonicalElementAction::class)->handle($element, $newAttributes);
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
        app(ResaveElementsAction::class)->handle($query, $continueOnError, $skipRevisions, $updateSearchIndex, $touch);
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
        app(PropagateElementsAction::class)->handle($query, $siteIds, $continueOnError);
    }

    /**
     * Duplicates an element.
     *
     * @template T of ElementInterface
     *
     * @param  T  $element  the element to duplicate
     * @param  array  $newAttributes  any attributes to apply to the duplicate. This can contain a `siteAttributes` key,
     *                                set to an array of site-specific attribute array, indexed by site IDs.
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
        return app(DuplicateElementAction::class)->handle($element, $newAttributes, $placeInStructure, $asUnpublishedDraft, $checkAuthorization, $copyModifiedFields);
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
        if ($queue) {
            dispatch(new UpdateElementSlugsAndUris(
                $element::class,
                $element->id,
                $element->siteId,
                $updateOtherSites,
                $updateDescendants,
            ));

            return;
        }

        if ($element::hasUris()) {
            $this->setElementUri($element);
        }

        event(new BeforeUpdateSlugAndUri($element));

        DB::table(Table::ELEMENTS_SITES)
            ->where('elementId', $element->id)
            ->where('siteId', $element->siteId)
            ->update([
                'slug' => $element->slug,
                'uri' => $element->uri,
                'dateUpdated' => now(),
            ]);

        event(new AfterUpdateSlugAndUri($element));

        // Invalidate any caches involving this element
        $this->elementCaches->invalidateForElement($element);

        if ($updateOtherSites) {
            $this->updateElementSlugAndUriInOtherSites($element);
        }

        if ($updateDescendants) {
            $this->updateDescendantSlugsAndUris($element, $updateOtherSites);
        }
    }

    /**
     * Updates an element’s slug and URI, for any sites besides the given one.
     *
     * @param  ElementInterface  $element  The element to update.
     */
    public function updateElementSlugAndUriInOtherSites(ElementInterface $element): void
    {
        foreach (Sites::getAllSiteIds() as $siteId) {
            if ($siteId === $element->siteId) {
                continue;
            }

            $elementInOtherSite = $element->getLocalizedQuery()
                ->siteId($siteId)
                ->one();

            if ($elementInOtherSite) {
                $this->updateElementSlugAndUri($elementInOtherSite, false, false);
            }
        }
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
        $query = $this->createElementQuery($element::class)
            ->descendantOf($element)
            ->descendantDist(1)
            ->status(null)
            ->siteId($element->siteId);

        if ($queue) {
            $childIds = $query->ids();

            if (! empty($childIds)) {
                dispatch(new UpdateElementSlugsAndUris(
                    elementType: $element::class,
                    elementId: $childIds,
                    siteId: $element->siteId,
                    updateOtherSites: $updateOtherSites,
                    updateDescendants: true,
                ));
            }

            return;
        }

        $query->each(fn (ElementInterface $child) => $this->updateElementSlugAndUri(
            element: $child,
            updateOtherSites: $updateOtherSites,
            updateDescendants: true,
            queue: false,
        ));
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
        // Get the elements
        if (! $mergedElement = $this->getElementById($mergedElementId)) {
            throw new ElementNotFoundException("No element exists with the ID '$mergedElementId'");
        }

        if (! $prevailingElement = $this->getElementById($prevailingElementId)) {
            throw new ElementNotFoundException("No element exists with the ID '$prevailingElementId'");
        }

        // Merge them
        return $this->mergeElements($mergedElement, $prevailingElement);
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
        return app(MergeElementsAction::class)->handle($mergedElement, $prevailingElement);
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
        $elementType ??= $this->getElementTypeById($elementId);

        if ($elementType === null) {
            return false;
        }

        if ($siteId === null && $elementType::isLocalized() && Sites::isMultiSite()) {
            // Get a site this element is enabled in
            $siteId = (int) DB::table(Table::ELEMENTS_SITES)
                ->where('elementId', $elementId)
                ->value('siteId');

            if ($siteId === 0) {
                return false;
            }
        }

        $element = $this->getElementById($elementId, $elementType, $siteId);

        if (! $element) {
            return false;
        }

        return $this->deleteElement($element, $hardDelete);
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
        return app(DeleteElementAction::class)->handle($element, $hardDelete);
    }

    /**
     * Deletes an element in the site it’s loaded in.
     */
    public function deleteElementForSite(ElementInterface $element): void
    {
        $this->deleteElementsForSite([$element]);
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
        app(DeleteElementsForSiteAction::class)->handle($elements);
    }
}
