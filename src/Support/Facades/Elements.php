<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static string<\craft\base\ElementInterface>|null getElementTypeById(int $elementId)
 * @method static string|null getElementTypeByUid(string $uid)
 * @method static string|null getElementTypeByKey(string $property, int|string $elementId)
 * @method static string[] getElementTypesByIds(int[] $elementIds)
 * @method static string<\craft\base\ElementInterface>[] getAllElementTypes()
 * @method static string|null getElementTypeByRefHandle(string $refHandle)
 * @method static \craft\base\ElementInterface createElement(string<\craft\base\ElementInterface>|array $config)
 * @method static \CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface createElementQuery(string<\craft\base\ElementInterface> $elementType)
 * @method static \craft\base\ElementInterface|null getElementById(int $elementId, string<\craft\base\ElementInterface>|null $elementType = null, int|string|int[]|null $siteId = null, array $criteria = [])
 * @method static \craft\base\ElementInterface|null getElementByUid(string $uid, string<\craft\base\ElementInterface>|null $elementType = null, int|string|int[]|null $siteId = null, array $criteria = [])
 * @method static \craft\base\ElementInterface|null getElementByUri(string $uri, int|null $siteId = null, bool $enabledOnly = false)
 * @method static string|null getElementUriForSite(int $elementId, int $siteId)
 * @method static int[] getEnabledSiteIdsForElement(int $elementId)
 * @method static bool saveElement(\craft\base\ElementInterface $element, bool $runValidation = true, bool $propagate = true, bool|null $updateSearchIndex = null, bool $forceTouch = false, bool|null $crossSiteValidate = false, bool $saveContent = false)
 * @method static void setElementUri(\craft\base\ElementInterface $element)
 * @method static void mergeCanonicalChanges(\craft\base\ElementInterface $element)
 * @method static \craft\base\ElementInterface updateCanonicalElement(\craft\base\ElementInterface $element, array $newAttributes = [])
 * @method static void resaveElements(\CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface $query, bool $continueOnError = false, bool $skipRevisions = true, bool|null $updateSearchIndex = null, bool $touch = false)
 * @method static void propagateElements(\CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface $query, int|int[]|null $siteIds = null, bool $continueOnError = false)
 * @method static \craft\base\ElementInterface propagateElement(\craft\base\ElementInterface $element, int $siteId, \craft\base\ElementInterface|false|null $siteElement = null)
 * @method static \craft\base\ElementInterface duplicateElement(\craft\base\ElementInterface $element, array $newAttributes = [], bool $placeInStructure = true, bool $asUnpublishedDraft = false, bool $checkAuthorization = false, bool $copyModifiedFields = false)
 * @method static void updateElementSlugAndUri(\craft\base\ElementInterface $element, bool $updateOtherSites = true, bool $updateDescendants = true, bool $queue = false)
 * @method static void updateElementSlugAndUriInOtherSites(\craft\base\ElementInterface $element)
 * @method static void updateDescendantSlugsAndUris(\craft\base\ElementInterface $element, bool $updateOtherSites = true, bool $queue = false)
 * @method static bool mergeElementsByIds(int $mergedElementId, int $prevailingElementId)
 * @method static bool mergeElements(\craft\base\ElementInterface $mergedElement, \craft\base\ElementInterface $prevailingElement)
 * @method static bool deleteElementById(int $elementId, string<\craft\base\ElementInterface>|null $elementType = null, int|null $siteId = null, bool $hardDelete = false)
 * @method static bool deleteElement(\craft\base\ElementInterface $element, bool $hardDelete = false)
 * @method static void deleteElementForSite(\craft\base\ElementInterface $element)
 * @method static void deleteElementsForSite(\craft\base\ElementInterface[] $elements)
 * @method static bool restoreElement(\craft\base\ElementInterface $element)
 * @method static bool restoreElements(\craft\base\ElementInterface[] $elements)
 * @method static string parseRefs(string $str, int|null $defaultSiteId = null)
 * @method static void setPlaceholderElement(\craft\base\ElementInterface $element)
 * @method static \craft\base\ElementInterface[] getPlaceholderElements()
 * @method static \craft\base\ElementInterface|null getPlaceholderElement(int $sourceId, int $siteId)
 * @method static \CraftCms\Cms\Element\Data\EagerLoadPlan[] createEagerLoadingPlans(array|string $with)
 * @method static void eagerLoadElements(string<\craft\base\ElementInterface> $elementType, \craft\base\ElementInterface[] $elements, array<string|array>|string|\CraftCms\Cms\Element\Data\EagerLoadPlan[] $with)
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
