<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\base\Batchable;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\db\QueryBatcher;
use craft\db\Table;
use craft\errors\UnsupportedSiteException;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\i18n\Translation;
use craft\queue\BaseBatchedJob;
use craft\services\Structures;
use Throwable;

/**
 * ApplyNewPropagationMethod loads all elements that match a given criteria,
 * and resaves them to apply a new propagation method to them, duplicating them for any sites
 * where they would have been deleted in the process.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.8
 */
class ApplyNewPropagationMethod extends BaseBatchedJob
{
    /**
     * @var string The element type to use
     * @phpstan-var class-string<ElementInterface>
     */
    public string $elementType;

    /**
     * @var array|null The element criteria that determines which elements the
     * new propagation method should be applied to
     */
    public ?array $criteria = null;

    /** @internal */
    public array $duplicatedElementIds = [];

    /**
     * @inheritdoc
     */
    protected function loadData(): Batchable
    {
        /** @var string|ElementInterface $elementType */
        /** @phpstan-var class-string<ElementInterface>|ElementInterface $elementType */
        $elementType = $this->elementType;
        $query = $elementType::find()
            ->site('*')
            ->preferSites([Craft::$app->getSites()->getPrimarySite()->id])
            ->unique()
            ->status(null)
            ->drafts(null)
            ->provisionalDrafts(null)
            ->orderBy(['elements.id' => SORT_ASC]);

        if (!empty($this->criteria)) {
            Craft::configure($query, $this->criteria);
        }

        return new QueryBatcher($query);
    }

    /**
     * @inheritdoc
     */
    protected function processItem(mixed $item): void
    {
        // Skip revisions
        try {
            if (ElementHelper::isRevision($item)) {
                return;
            }
        } catch (Throwable) {
            return;
        }

        $elementsService = Craft::$app->getElements();
        $structuresService = Craft::$app->getStructures();
        $allSiteIds = Craft::$app->getSites()->getAllSiteIds();

        // See what sites the element should exist in going forward
        /** @var ElementInterface $item */
        $newSiteIds = ArrayHelper::getColumn(ElementHelper::supportedSitesForElement($item), 'siteId');

        // What other sites are there?
        $otherSiteIds = array_diff($allSiteIds, $newSiteIds);

        if (empty($otherSiteIds)) {
            return;
        }

        // Load the element in any sites that it's about to be deleted for
        $otherSiteElements = $item::find()
            ->id($item->id)
            ->siteId($otherSiteIds)
            ->structureId($item->structureId)
            ->status(null)
            ->drafts(null)
            ->provisionalDrafts(null)
            ->orderBy([])
            ->indexBy('siteId')
            ->all();

        if (empty($otherSiteElements)) {
            return;
        }

        // Remove their URIs so the duplicated elements can retain them w/out needing to increment them
        Db::update(Table::ELEMENTS_SITES, [
            'uri' => null,
        ], [
            'id' => ArrayHelper::getColumn($otherSiteElements, 'siteSettingsId'),
        ], [], false);

        // Duplicate those elements so their content can live on
        while (!empty($otherSiteElements)) {
            /** @var ElementInterface $otherSiteElement */
            $otherSiteElement = array_pop($otherSiteElements);
            try {
                $newElement = $elementsService->duplicateElement($otherSiteElement, [], false);
            } catch (UnsupportedSiteException $e) {
                // Just log it and move along
                Craft::warning(sprintf(
                    "Unable to duplicate “%s” to site %d: %s",
                    get_class($otherSiteElement),
                    $otherSiteElement->siteId,
                    $e->getMessage()
                ));
                Craft::$app->getErrorHandler()->logException($e);
                continue;
            }

            // Should we add the clone to the source element’s structure?
            if (
                $item->structureId &&
                $item->root &&
                !$newElement->root &&
                $newElement->structureId == $item->structureId
            ) {
                // If this is a root level element, insert the duplicate after the source
                if ($item->level == 1) {
                    $structuresService->moveAfter($item->structureId, $newElement, $item, Structures::MODE_INSERT);
                } else {
                    // Append the clone to the source's parent
                    $parentId = $item::find()
                        ->site('*')
                        ->ancestorOf($item->id)
                        ->ancestorDist(1)
                        ->unique()
                        ->status(null)
                        ->drafts(null)
                        ->provisionalDrafts(null)
                        ->select(['elements.id'])
                        ->scalar();

                    if ($parentId !== false) {
                        // If we've cloned the parent, use the clone's ID instead
                        if (isset($this->duplicatedElementIds[$parentId][$newElement->siteId])) {
                            $parentId = $this->duplicatedElementIds[$parentId][$newElement->siteId];
                        }

                        $structuresService->append($item->structureId, $newElement, $parentId, Structures::MODE_INSERT);
                    } else {
                        // Just append it to the root
                        $structuresService->appendToRoot($item->structureId, $newElement, Structures::MODE_INSERT);
                    }
                }
            }

            // This may support more than just the site it was saved in
            $newElementSiteIds = ArrayHelper::getColumn(ElementHelper::supportedSitesForElement($newElement), 'siteId');
            foreach ($newElementSiteIds as $newElementSiteId) {
                unset($otherSiteElements[$newElementSiteId]);
                $this->duplicatedElementIds[$item->id][$newElementSiteId] = $newElement->id;
            }
        }

        // Now resave the original element
        $item->setScenario(Element::SCENARIO_ESSENTIALS);
        $item->resaving = true;

        try {
            $elementsService->saveElement($item, updateSearchIndex: false);
        } catch (Throwable $e) {
            Craft::$app->getErrorHandler()->logException($e);
        }
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        return Translation::prep('app', 'Applying new propagation method to elements');
    }
}
