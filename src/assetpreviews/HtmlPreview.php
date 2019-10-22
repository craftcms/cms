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
use craft\elements\Asset;

/**
 * Provides functionality to preview text files as HTML
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
class HtmlPreview extends AssetPreview
{

    private $template = '';
    private $foot = '';

    // Public Methods
    // =========================================================================

    public function init()
    {
        $localCopy = $this->asset->getCopyOfFile();
        $content = htmlspecialchars(file_get_contents($localCopy));
        unlink($localCopy);
        $language = $this->asset->kind === Asset::KIND_HTML ? 'markup' : $this->asset->kind;
        $view = Craft::$app->getView();
        $this->template = $view->renderTemplate('assets/_previews/html', [
            'asset' => $this->asset,
            'language' => $language,
            'content' => $content
        ]);

        $this->foot = $view->getBodyHtml();

        parent::init();
    }


    /**
     * @inheritDoc
     */
    public function getModalHtml(): string
    {
        return $this->template;
    }

    public function getFootHtml()
    {
        return $this->foot;
    }
}
