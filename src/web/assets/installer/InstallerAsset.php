<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\installer;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Support\Json;

/**
 * Asset bundle for the Installer
 */
class InstallerAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

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

        $redirect = Json::encode(app(GeneralConfig::class)->postCpLoginRedirect);
        $view->registerJs("window.postCpLoginRedirect = $redirect;");
    }
}
