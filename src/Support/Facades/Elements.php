<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static string|null getElementTypeById(int $elementId)
 * @method static string|null getElementTypeByUid(string $uid)
 * @method static string|null getElementTypeByKey(string $property, int|string $elementId)
 * @method static string[] getElementTypesByIds(int[] $elementIds)
 * @method static string[] getAllElementTypes()
 * @method static string|null getElementTypeByRefHandle(string $refHandle)
 * @method static \CraftCms\Cms\Element\Contracts\ElementInterface createElement(string|array $config)
 * @method static \CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface createElementQuery(string $elementType)
 * @method static \CraftCms\Cms\Element\Contracts\ElementInterface|null|\CraftCms\Cms\Element\Contracts\ElementInterface|null getElementById(int $elementId, string|null $elementType = null, int|string|int[]|null $siteId = null, array $criteria = [])
 * @method static \CraftCms\Cms\Element\Contracts\ElementInterface|null getElementByUid(string $uid, string|null $elementType = null, int|string|int[]|null $siteId = null, array $criteria = [])
 * @method static \CraftCms\Cms\Element\Contracts\ElementInterface|null getElementByUri(string $uri, int|null $siteId = null, bool $enabledOnly = false)
 * @method static string|null getElementUriForSite(int $elementId, int $siteId)
 * @method static int[] getEnabledSiteIdsForElement(int $elementId)
 * @method static bool saveElement(\CraftCms\Cms\Element\Contracts\ElementInterface $element, bool $runValidation = true, bool $propagate = true, bool|null $updateSearchIndex = null, bool $forceTouch = false, bool|null $crossSiteValidate = false, bool $saveContent = false)
 * @method static void setElementUri(\CraftCms\Cms\Element\Contracts\ElementInterface $element)
 * @method static void mergeCanonicalChanges(\CraftCms\Cms\Element\Contracts\ElementInterface $element)
 * @method static \CraftCms\Cms\Element\Contracts\ElementInterface updateCanonicalElement(\CraftCms\Cms\Element\Contracts\ElementInterface $element, array $newAttributes = [])
 * @method static void resaveElements(\CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface $query, bool $continueOnError = false, bool $skipRevisions = true, bool|null $updateSearchIndex = null, bool $touch = false)
 * @method static void propagateElements(\CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface $query, int|int[]|null $siteIds = null, bool $continueOnError = false)
 * @method static \CraftCms\Cms\Element\Contracts\ElementInterface propagateElement(\CraftCms\Cms\Element\Contracts\ElementInterface $element, int $siteId, \CraftCms\Cms\Element\Contracts\ElementInterface|false|null $siteElement = null)
 * @method static \CraftCms\Cms\Element\Contracts\ElementInterface duplicateElement(\CraftCms\Cms\Element\Contracts\ElementInterface $element, array $newAttributes = [], bool $placeInStructure = true, bool $asUnpublishedDraft = false, bool $checkAuthorization = false, bool $copyModifiedFields = false)
 * @method static void updateElementSlugAndUri(\CraftCms\Cms\Element\Contracts\ElementInterface $element, bool $updateOtherSites = true, bool $updateDescendants = true, bool $queue = false)
 * @method static void updateElementSlugAndUriInOtherSites(\CraftCms\Cms\Element\Contracts\ElementInterface $element)
 * @method static void updateDescendantSlugsAndUris(\CraftCms\Cms\Element\Contracts\ElementInterface $element, bool $updateOtherSites = true, bool $queue = false)
 * @method static bool mergeElementsByIds(int $mergedElementId, int $prevailingElementId)
 * @method static bool mergeElements(\CraftCms\Cms\Element\Contracts\ElementInterface $mergedElement, \CraftCms\Cms\Element\Contracts\ElementInterface $prevailingElement)
 * @method static bool deleteElementById(int $elementId, string|null $elementType = null, int|null $siteId = null, bool $hardDelete = false)
 * @method static bool deleteElement(\CraftCms\Cms\Element\Contracts\ElementInterface $element, bool $hardDelete = false)
 * @method static void deleteElementForSite(\CraftCms\Cms\Element\Contracts\ElementInterface $element)
 * @method static void deleteElementsForSite(\CraftCms\Cms\Element\Contracts\ElementInterface[] $elements)
 * @method static bool restoreElement(\CraftCms\Cms\Element\Contracts\ElementInterface $element)
 * @method static bool restoreElements(\CraftCms\Cms\Element\Contracts\ElementInterface[] $elements)
 * @method static void reorderNestedElements(\CraftCms\Cms\Element\Contracts\ElementInterface $owner, \CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface|\CraftCms\Cms\Element\ElementCollection $nestedElements, int[] $elementIds, int $offset)
 * @method static string parseRefs(string $str, int|null $defaultSiteId = null)
 * @method static void setPlaceholderElement(\CraftCms\Cms\Element\Contracts\ElementInterface $element)
 * @method static \CraftCms\Cms\Element\Contracts\ElementInterface[] getPlaceholderElements()
 * @method static \CraftCms\Cms\Element\Contracts\ElementInterface|null getPlaceholderElement(int $sourceId, int $siteId)
 * @method static \CraftCms\Cms\Element\Data\EagerLoadPlan[] createEagerLoadingPlans(array|string $with)
 * @method static void eagerLoadElements(string $elementType, \CraftCms\Cms\Element\Contracts\ElementInterface[]|\Illuminate\Support\Collection $elements, array|string $with)
 *
 * @see \CraftCms\Cms\Element\Elements
 */
class Elements extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Element\Elements::class;
    }
}
