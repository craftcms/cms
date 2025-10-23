<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\installer;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Json;

/**
 * Asset bundle for the Installer
 */
class InstallerAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@assetBundles/installer/dist';

    /**
     * @inheritdoc
     */
    public $depends = [
        CpAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public $css = [
        'css/install.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'install.js',
    ];

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view): void
    {
        parent::registerAssetFiles($view);

        $redirect = Json::encode(Cms::config()->postCpLoginRedirect);
        $view->registerJs("window.postCpLoginRedirect = $redirect;");
    }
}
