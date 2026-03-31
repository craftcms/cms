<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use craft\base\ElementInterface;
use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tpetry\QueryExpressions\Language\Alias;

#[Singleton]
class Elements
{
    /**
     * @see setPlaceholderElement()
     * @see getElementByUri()
     */
    private array $_placeholderUris;

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
}
