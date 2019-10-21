<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 * @since 3.4.0
 */

namespace craft\assetpreviews;

use Craft;
use craft\base\AssetPreview;

/**
 * Provides functionality to preview images
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
class ImagePreview extends AssetPreview
{

    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getModalHtml(): string {

        $volume = $this->asset->getVolume();
        if ($volume->hasUrls) {
            $assetUrl = $this->asset->getUrl();
        } else {
            $source = $this->asset->getTransformSource();
            $assetUrl = Craft::$app->getAssetManager()->getPublishedUrl($source, true);
        }

        $view = Craft::$app->getView();
        return $view->renderTemplate('assets/_previews/image', [
            'asset' => $this->asset,
            'assetUrl' => $assetUrl
        ]);
    }
}
