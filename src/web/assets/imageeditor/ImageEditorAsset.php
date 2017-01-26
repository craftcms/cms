<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\assets\imageeditor;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\fabric\FabricAsset;

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
}
