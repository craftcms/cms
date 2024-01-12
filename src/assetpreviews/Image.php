<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\assetpreviews;

use Craft;
use craft\base\AssetPreviewHandler;
use craft\helpers\UrlHelper;
use yii\base\NotSupportedException;

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
        try {
            $url = Craft::$app->getAssets()->getImagePreviewUrl($this->asset, 1000, 1000);
        } catch (NotSupportedException) {
            $url = UrlHelper::actionUrl('assets/edit-image', [
                'assetId' => $this->asset->id,
                'size' => 1000,
            ]);
        }

        return Craft::$app->getView()->renderTemplate('assets/_previews/image.twig',
            array_merge([
                'asset' => $this->asset,
                'url' => $url,
            ], $variables)
        );
    }
}
