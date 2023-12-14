<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\craftsupport;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\View;

/**
 * Asset bundle for the Craft Support widget
 */
class CraftSupportAsset extends AssetBundle
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
        'css/CraftSupportWidget.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'CraftSupportWidget.js',
    ];

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view): void
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $view->registerTranslations('app', [
                'Contact Developer Support',
                'Message sent successfully.',
            ]);
        }
    }
}
