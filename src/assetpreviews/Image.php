<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 * @since 3.4.0
 */

namespace craft\assetpreviews;

use Craft;
use craft\base\AssetPreviewHandler;
use craft\helpers\UrlHelper;

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
    public function getPreviewHtml(): string
    {
        $volume = $this->asset->getVolume();

        if ($volume->hasUrls) {
            $url = $this->asset->getUrl();
        } else {
            $url = UrlHelper::actionUrl('assets/thumb', [
                'uid' => $this->asset->uid,
                'width' => $this->asset->getWidth(),
                'height' => $this->asset->getHeight(),
            ]);
        }

        return Craft::$app->getView()->renderTemplate('assets/_previews/image', [
            'asset' => $this->asset,
            'url' => $url,
        ]);
    }
}
