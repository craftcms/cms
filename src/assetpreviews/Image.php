<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\assetpreviews;

use Craft;
use craft\base\AssetPreviewHandler;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\UrlHelper;
use craft\models\ImageTransform;

/**
 * Provides functionality to preview images.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
class Image extends AssetPreviewHandler
{
    /**
     * @inheritdoc
     */
    public function getPreviewHtml(array $variables = []): string
    {
        $originalWidth = (int)$this->asset->getWidth();
        $originalHeight = (int)$this->asset->getHeight();
        [$width, $height] = AssetsHelper::scaledDimensions((int)$this->asset->getWidth(), (int)$this->asset->getHeight(), 1000, 1000);

        // Can we just use the main asset URL?
        if (
            $this->asset->getVolume()->getFs()->hasUrls &&
            $originalWidth <= $width &&
            $originalHeight <= $height
        ) {
            $url = $this->asset->getUrl();
        } else {
            $transform = new ImageTransform([
                'width' => $width,
                'height' => $height,
                'mode' => 'crop',
            ]);
            $url = $transform->getImageTransformer()->getTransformUrl($this->asset, $transform, true);
        }

        return Craft::$app->getView()->renderTemplate('assets/_previews/image',
            array_merge([
                'asset' => $this->asset,
                'url' => $url,
            ], $variables)
        );
    }
}
