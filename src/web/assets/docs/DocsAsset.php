<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\docs;

use craft\web\AssetBundle;
use craft\web\assets\htmx\HtmxAsset;
use craft\web\View;

/**
 * Asset bundle for the Documentation widget
 */
class DocsAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $depends = [
        HtmxAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public $css = [
        'css/DocsWidget.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'DocsWidget.js',
    ];

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view): void
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $view->registerTranslations('app', [
                'Search the documentation',
            ]);
        }
    }
}
