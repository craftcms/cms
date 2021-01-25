<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\helpers\Queue;
use craft\queue\BaseJob;

/**
 * GeneratePendingTransforms job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class GeneratePendingTransforms extends BaseJob
{
    public $limit;

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        // Get all of the pending transform index IDs
        $indexIds = Craft::$app->getAssetTransforms()->getPendingTransformIndexIds($this->limit);

        $totalIndexes = count($indexIds);
        $assetTransformsService = Craft::$app->getAssetTransforms();

        foreach ($indexIds as $i => $id) {
            if ($index = $assetTransformsService->getTransformIndexModelById($id)) {
                $this->setProgress($queue, $i / $totalIndexes, Craft::t('app', '{step} of {total}', [
                    'step' => $i + 1,
                    'total' => $totalIndexes,
                ]));

                // Don't let an exception stop us from processing the rest
                try {
                    $assetTransformsService->ensureTransformUrlByIndexModel($index);
                } catch (\Throwable $e) {
                }
            }
        }

        $pendingImageTransformCount = Craft::$app->getAssetTransforms()->countPendingTransformIndexIds();

        if ($pendingImageTransformCount > 0) {
            Queue::push(new GeneratePendingTransforms(['limit' => $this->limit]));
        }
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('app', 'Generating pending image transforms');
    }
}
