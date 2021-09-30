<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\i18n\Translation;
use craft\queue\BaseJob;
use Throwable;

/**
 * GeneratePendingTransforms job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class GeneratePendingTransforms extends BaseJob
{
    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        // Get all of the pending transform index IDs
        $indexIds = Craft::$app->getAssetTransforms()->getPendingTransformIndexIds();

        $totalIndexes = count($indexIds);
        $assetTransformsService = Craft::$app->getAssetTransforms();

        foreach ($indexIds as $i => $id) {
            if ($index = $assetTransformsService->getTransformIndexModelById($id)) {
                $this->setProgress($queue, $i / $totalIndexes, Translation::prep('app', '{step, number} of {total, number}', [
                    'step' => $i + 1,
                    'total' => $totalIndexes,
                ]));

                // Don't let an exception stop us from processing the rest
                try {
                    $assetTransformsService->ensureTransformUrlByIndexModel($index);
                } catch (Throwable $e) {
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
