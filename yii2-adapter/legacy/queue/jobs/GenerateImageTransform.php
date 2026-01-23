<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\imagetransforms\ImageTransformer;
use craft\queue\BaseJob;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Support\Facades\I18N;
use Throwable;

/**
 * GenerateImageTransform job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class GenerateImageTransform extends BaseJob
{
    /**
     * @var int The transform ID
     */
    public int $transformId;

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $transformer = Craft::createObject(ImageTransformer::class);
        $index = $transformer->getTransformIndexModelById($this->transformId);

        if ($index && !$index->fileExists) {
            // Don't let an exception stop us from processing the rest
            try {
                /** @var Asset|null $asset */
                $asset = Asset::find()->id($index->assetId)->one();
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
        return I18N::prep('Generating image transform');
    }
}
