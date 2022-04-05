<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\base\ElementInterface;
use craft\db\Table;
use craft\errors\UnsupportedSiteException;
use craft\events\BatchElementActionEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\queue\BaseJob;
use craft\services\Elements;
use craft\services\Structures;

/**
 * ApplyNewPropagationMethod loads all elements that match a given criteria,
 * and resaves them to apply a new propagation method to them, duplicating them for any sites
 * where they would have been deleted in the process.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.8
 */
class ApplyNewPropagationMethod extends BaseJob
{
    /**
     * @var string|ElementInterface The element type to use
     */
    public $elementType;

    /**
     * @var array|null The element criteria that determines which elements the
     * new propagation method should be applied to
     */
    public $criteria;

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        /** @var string|ElementInterface $elementType */
        $elementType = $this->elementType;
        $query = $elementType::find()
            ->site('*')
            ->preferSites([Craft::$app->getSites()->getPrimarySite()->id])
            ->unique()
            ->anyStatus()
            ->drafts(null)
            ->provisionalDrafts(null);

        if (!empty($this->criteria)) {
            Craft::configure($query, $this->criteria);
        }

        $total = $query->count();
        $elementsService = Craft::$app->getElements();
        $structuresService = Craft::$app->getStructures();
        $allSiteIds = Craft::$app->getSites()->getAllSiteIds();

        $duplicatedElementIds = [];

        $callback = function(BatchElementActionEvent $e) use (
            $elementType,
            $queue,
            $query,
            $total,
            $elementsService,
            $structuresService,
            $allSiteIds,
            &$duplicatedElementIds
        ) {
            if ($e->query === $query) {
                $this->setProgress($queue, ($e->position - 1) / $total, Craft::t('app', '{step, number} of {total, number}', [
                    'step' => $e->position,
                    'total' => $total,
                ]));

                // See what sites the element should exist in going forward
                $newSiteIds = ArrayHelper::getColumn(ElementHelper::supportedSitesForElement($e->element), 'siteId');

                // What other sites are there?
                $otherSiteIds = array_diff($allSiteIds, $newSiteIds);

                if (empty($otherSiteIds)) {
                    return;
                }

                // Load the element in any sites that it's about to be deleted for
                $element = $e->element;
                $otherSiteElements = $elementType::find()
                    ->id($element->id)
                    ->siteId($otherSiteIds)
                    ->structureId($element->structureId)
                    ->anyStatus()
                    ->drafts(null)
                    ->provisionalDrafts(null)
                    ->orderBy(null)
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
                    $otherSiteElement = array_pop($otherSiteElements);
                    try {
                        $newElement = $elementsService->duplicateElement($otherSiteElement, [], false);
                    } catch (UnsupportedSiteException $e) {
                        // Just log it and move along
                        Craft::warning("Unable to duplicate “{$otherSiteElement}” to site $otherSiteElement->siteId: " . $e->getMessage());
                        Craft::$app->getErrorHandler()->logException($e);
                        continue;
                    }

                    // Should we add the clone to the source element's structure?
                    if (
                        $element->structureId &&
                        $element->root &&
                        !$newElement->root &&
                        $newElement->structureId == $element->structureId
                    ) {
                        // If this is a root level element, insert the duplicate after the source
                        if ($element->level == 1) {
                            $structuresService->moveAfter($element->structureId, $newElement, $element, Structures::MODE_INSERT);
                        } else {
                            // Append the clone to the source's parent
                            $parentId = $elementType::find()
                                ->ancestorOf($element->id)
                                ->ancestorDist(1)
                                ->select(['elements.id'])
                                ->site('*')
                                ->unique()
                                ->anyStatus()
                                ->drafts(null)
                                ->provisionalDrafts(null)
                                ->scalar();

                            if ($parentId !== false) {
                                // If we've cloned the parent, use the clone's ID instead
                                if (isset($duplicatedElementIds[$parentId][$newElement->siteId])) {
                                    $parentId = $duplicatedElementIds[$parentId][$newElement->siteId];
                                }

                                $structuresService->append($element->structureId, $newElement, $parentId, Structures::MODE_INSERT);
                            } else {
                                // Just append it to the root
                                $structuresService->appendToRoot($element->structureId, $newElement, Structures::MODE_INSERT);
                            }
                        }
                    }

                    // This may support more than just the site it was saved in
                    $newElementSiteIds = ArrayHelper::getColumn(ElementHelper::supportedSitesForElement($newElement), 'siteId');
                    foreach ($newElementSiteIds as $newElementSiteId) {
                        unset($otherSiteElements[$newElementSiteId]);
                        $duplicatedElementIds[$element->id][$newElementSiteId] = $newElement->id;
                    }
                }
            }
        };

        $elementsService->on(Elements::EVENT_BEFORE_RESAVE_ELEMENT, $callback);
        $elementsService->resaveElements($query);
        $elementsService->off(Elements::EVENT_BEFORE_RESAVE_ELEMENT, $callback);
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('app', 'Applying new propagation method to elements');
    }
}
