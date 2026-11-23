<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\uploads;

use Craft;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\web\AssetBundle;
use craft\web\View;

/**
 * Defines `Craft.Uploads.FileUpload`, which uploads files through [[\craft\services\Uploads|upload sessions]], and
 * uploads files chosen in `<input type="file" data-upload-session>` inputs.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class UploadsAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $js = [
        'uploads.js',
    ];

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view): void
    {
        parent::registerAssetFiles($view);

        if (!$view instanceof View) {
            return;
        }

        $request = Craft::$app->getRequest();
        $defaults = ['url' => UrlHelper::actionUrl('uploads/start')];

        if ($request->enableCsrfValidation && !Craft::$app->getConfig()->getGeneral()->csrfOriginOnly) {
            // Forms' own CSRF inputs are used first, since this token is stale on statically cached pages
            $defaults['csrfTokenName'] = $request->csrfParam;
            $defaults['csrfToken'] = $request->getCsrfToken();
        }

        $view->registerJs('Object.assign(Craft.Uploads.defaults, ' . Json::encode($defaults) . ');', View::POS_END);
    }
}
