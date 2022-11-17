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
 * GeneratePendingTransform job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.3
 */
class GeneratePendingTransform extends BaseJob
{
    /**
     * @var int The pending transform index ID
     */
    public int $indexId;

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $transformer = Craft::createObject(ImageTransformer::class);

        if ($index = $transformer->getTransformIndexModelById($this->indexId)) {
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

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        return Translation::prep('app', 'Generating pending image transform');
    }
}
