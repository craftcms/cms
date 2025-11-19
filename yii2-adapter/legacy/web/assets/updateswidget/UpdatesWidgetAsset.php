<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\updateswidget;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\View;

/**
 * Asset bundle for Updates widgets
 */
class UpdatesWidgetAsset extends AssetBundle
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
    public $js = [
        'UpdatesWidget.js',
    ];

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view): void
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $view->registerTranslations('app', [
                'One update available!',
                '{total} updates available!',
                'Go to Updates',
                'Congrats! You’re up to date.',
                'Check again',
            ]);
        }
    }
}
