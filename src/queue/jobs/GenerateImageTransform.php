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
        return Translation::prep('app', 'Generating image transform');
    }
}
