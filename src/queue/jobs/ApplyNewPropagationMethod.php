<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\base\ElementInterface;
use craft\errors\UnsupportedSiteException;
use craft\events\BatchElementActionEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\ElementHelper;
use craft\queue\BaseJob;
use craft\services\Elements;

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
            ->siteId('*')
            ->unique()
            ->anyStatus();

        if (!empty($this->criteria)) {
            Craft::configure($query, $this->criteria);
        }

        $total = $query->count();
        $elementsService = Craft::$app->getElements();
        $allSiteIds = Craft::$app->getSites()->getAllSiteIds();

        $callback = function(BatchElementActionEvent $e) use ($elementType, $queue, $query, $total, $elementsService, $allSiteIds) {
            if ($e->query === $query) {
                $this->setProgress($queue, ($e->position - 1) / $total, Craft::t('app', '{step} of {total}', [
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
                    ->orderBy(null)
                    ->indexBy('siteId')
                    ->all();

                // Duplicate those blocks so their content can live on
                while (!empty($otherSiteElements)) {
                    $otherSiteElement = array_pop($otherSiteElements);
                    try {
                        /** @var Element $newElement */
                        $newElement = $elementsService->duplicateElement($otherSiteElement);
                    } catch (UnsupportedSiteException $e) {
                        // Just log it and move along
                        Craft::warning("Unable to duplicate “{$otherSiteElement}” to site $otherSiteElement->siteId: " . $e->getMessage());
                        Craft::$app->getErrorHandler()->logException($e);
                        continue;
                    }

                    // This may support more than just the site it was saved in
                    $newElementSiteIds = ArrayHelper::getColumn(ElementHelper::supportedSitesForElement($newElement), 'siteId');
                    foreach ($newElementSiteIds as $newBlockSiteId) {
                        unset($otherSiteElements[$newBlockSiteId]);
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
