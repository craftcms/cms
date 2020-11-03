<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\elements\MatrixBlock;
use craft\events\BatchElementActionEvent;
use craft\fields\Matrix;
use craft\queue\BaseJob;
use craft\services\Elements;

/**
 * ApplyMatrixPropagationMethod job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.18
 * @deprecated in 3.4.8. Use [[ApplyNewPropagationMethod]] instead.
 */
class ApplyMatrixPropagationMethod extends BaseJob
{
    /**
     * @var int The Matrix field ID
     */
    public $fieldId;

    /**
     * @var string The field’s old propagation method
     */
    public $oldPropagationMethod;

    /**
     * @var string The field’s new propagation method
     */
    public $newPropagationMethod;

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        $query = MatrixBlock::find()
            ->fieldId($this->fieldId)
            ->siteId('*')
            ->unique()
            ->anyStatus();

        $total = $query->count();
        $matrixService = Craft::$app->getMatrix();
        $elementsService = Craft::$app->getElements();

        $callback = function(BatchElementActionEvent $e) use ($queue, $query, $total, $matrixService, $elementsService) {
            if ($e->query === $query) {
                $this->setProgress($queue, ($e->position - 1) / $total, Craft::t('app', '{step} of {total}', [
                    'step' => $e->position,
                    'total' => $total,
                ]));

                if ($this->oldPropagationMethod === Matrix::PROPAGATION_METHOD_NONE) {
                    // Blocks only lived in a single site to begin with, so there's nothing else to do here
                    return;
                }

                /** @var MatrixBlock $block */
                $block = $e->element;
                $owner = $block->getOwner();

                $oldSiteIds = $matrixService->getSupportedSiteIds($this->oldPropagationMethod, $owner);
                $newSiteIds = $matrixService->getSupportedSiteIds($this->newPropagationMethod, $owner);
                $removedSiteIds = array_diff($oldSiteIds, $newSiteIds);

                if (!empty($removedSiteIds)) {
                    // Fetch the block in each of the sites that it will be removed in
                    $otherSiteBlocks = MatrixBlock::find()
                        ->id($block->id)
                        ->fieldId($this->fieldId)
                        ->siteId($removedSiteIds)
                        ->anyStatus()
                        ->indexBy('siteId')
                        ->all();

                    // Duplicate those blocks so their content can live on
                    while (!empty($otherSiteBlocks)) {
                        $otherSiteBlock = array_pop($otherSiteBlocks);
                        /** @var MatrixBlock $newBlock */
                        $newBlock = $elementsService->duplicateElement($otherSiteBlock);
                        // This may support more than just the site it was saved in
                        $newBlockSiteIds = $matrixService->getSupportedSiteIds($this->newPropagationMethod, $newBlock->getOwner());
                        foreach ($newBlockSiteIds as $newBlockSiteId) {
                            unset($otherSiteBlocks[$newBlockSiteId]);
                        }
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
        return Craft::t('app', 'Applying new propagation method to Matrix blocks');
    }
}
