<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\updater;

use craft\web\assets\cp\CpAsset;
use craft\web\View;
use yii\web\AssetBundle;

/**
 * Asset bundle for the Updater
 */
class UpdaterAsset extends AssetBundle
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
        'css/Updater.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'Updater.min.js',
    ];

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view): void
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $view->registerTranslations('app', [
                'A fatal error has occurred:',
                'Status:',
                'Response:',
                'Send for help',
                'Troubleshoot',
            ]);
        }
    }
}
