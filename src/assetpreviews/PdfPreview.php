<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\assetpreviews;

use Craft;
use craft\base\AssetPreview;

/**
 * Provides functionality to preview PDFs
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
class PdfPreview extends AssetPreview
{
    /**
     * @inheritDoc
     */
    public function getModalHtml(): string
    {
        $volume = $this->asset->getVolume();

        if ($volume->hasUrls) {
            $assetUrl = $this->asset->getUrl();
        } else {
            $assetUrl = $this->asset->getCopyOfFile();
        }

        $view = Craft::$app->getView();
        return $view->renderTemplate('assets/_previews/pdf', [
            'asset' => $this->asset,
            'assetUrl' => $assetUrl
        ]);
    }
}
