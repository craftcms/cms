<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 * @since 3.4.x
 */
namespace craft\assetpreviews;

use Craft;
use craft\base\AssetPreviewHandler;

/**
 * Provides functionality to preview videos
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.x
 */
class Video extends AssetPreviewHandler
{
    /**
     * @inheritdoc
     */
    public function getPreviewHtml(): string
    {
        /** @var Volume $volume */
        $volume = $this->asset->getVolume();

        if ($volume->hasUrls) {
            $url = $this->asset->getUrl();
        } else {
            $source = $this->asset->getTransformSource();
            $url = Craft::$app->getAssetManager()->getPublishedUrl($source, true);
        }

        return Craft::$app->getView()->renderTemplate('assets/_previews/video', [
            'asset' => $this->asset,
            'url' => $url,
        ]);
    }
}
