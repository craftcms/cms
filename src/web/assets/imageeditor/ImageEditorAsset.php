<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\web\assets\imageeditor;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\fabric\FabricAsset;
use craft\web\View;

/**
 * Asset bundle for the Dashboard
 */
class ImageEditorAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = __DIR__.'/dist';

        $this->depends = [
            FabricAsset::class,
            CpAsset::class,
        ];

        $this->css = [
            'image_editor.css',
        ];

        $this->js = [
            'AssetImageEditor'.$this->dotJs(),
            'SlideRuleInput'.$this->dotJs(),
        ];

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view)
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $view->registerTranslations('app', [
                'Save as a new asset',
                'Could not load the image editor.'
            ]);
        }
    }
}
