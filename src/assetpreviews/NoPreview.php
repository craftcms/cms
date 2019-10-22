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
 * When there's no preview available
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
class NoPreview extends AssetPreview
{

    // Public Methods
    // =========================================================================


    /**
     * @inheritDoc
     */
    public function getModalHtml(): string
    {
        $view = Craft::$app->getView();
        return $view->renderTemplate('assets/_previews/no_preview');
    }
}
