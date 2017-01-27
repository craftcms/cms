<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\assets\colorpicker;

use craft\web\AssetBundle;

/**
 * Colorpicker asset bundle.
 */
class ColorpickerAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@lib/colorpicker';

        $this->css = [
            'css/colorpicker.css',
        ];

        $this->js = [
            'js/colorpicker'.$this->dotJs(),
        ];

        parent::init();
    }
}
