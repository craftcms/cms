<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\assetpreviews;

use Craft;
use craft\base\AssetPreviewHandler;
use craft\elements\Asset;
use craft\helpers\FileHelper;
use craft\helpers\Html;

/**
 * Provides functionality to preview text files as HTML
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
class Text extends AssetPreviewHandler
{
    /**
     * @inheritdoc
     */
    public function getPreviewHtml(array $variables = []): string
    {
        $localCopy = $this->asset->getCopyOfFile();
        $contents = Html::encode(file_get_contents($localCopy));
        FileHelper::unlink($localCopy);
        $language = $this->asset->kind === Asset::KIND_HTML ? 'markup' : $this->asset->kind;

        return Craft::$app->getView()->renderTemplate('assets/_previews/text.twig',
            array_merge([
                'asset' => $this->asset,
                'language' => $language,
                'contents' => $contents,
            ], $variables)
        );
    }
}
