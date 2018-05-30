<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\queue\BaseJob;

/**
 * GeneratePendingTransforms job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class GeneratePendingTransforms extends BaseJob
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        // Get all of the pending transform index IDs
        $indexIds = Craft::$app->getAssetTransforms()->getPendingTransformIndexIds();

        $totalIndexes = count($indexIds);
        $assetTransformsService = Craft::$app->getAssetTransforms();

        foreach ($indexIds as $i => $id) {
            if ($index = $assetTransformsService->getTransformIndexModelById($id)) {
                $this->setProgress($queue, $i / $totalIndexes);

                // Don't let an exception stop us from processing the rest
                try {
                    $assetTransformsService->ensureTransformUrlByIndexModel($index);
                } catch (\Throwable $e) {
                }
            }
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('app', 'Generating pending image transforms');
    }
}
