<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\elements\Asset;
use craft\i18n\Translation;
use craft\imagetransforms\ImageTransformer;
use craft\queue\BaseJob;
use Throwable;

/**
 * GeneratePendingTransforms job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 4.4.0. [[GenerateImageTransform]] should be used instead.
 */
class GeneratePendingTransforms extends BaseJob
{
    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $transformer = Craft::createObject(ImageTransformer::class);

        // Get all the pending transform index IDs
        $indexIds = $transformer->getPendingTransformIndexIds();
        $totalIndexes = count($indexIds);

        foreach ($indexIds as $i => $id) {
            $this->setProgress($queue, $i / $totalIndexes, Translation::prep('app', '{step, number} of {total, number}', [
                'step' => $i + 1,
                'total' => $totalIndexes,
            ]));

            $index = $transformer->getTransformIndexModelById($id);

            // Make sure it hasn't been generated yet and isn't currently in progress
            if ($index && !$index->fileExists && !$index->inProgress) {
                // Don't let an exception stop us from processing the rest
                try {
                    $asset = Asset::findOne(['id' => $index->assetId]);
                    if ($asset) {
                        $transformer->getTransformUrl($asset, $index->getTransform(), true);
                    }
                } catch (Throwable) {
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        return Translation::prep('app', 'Generating pending image transforms');
    }
}
