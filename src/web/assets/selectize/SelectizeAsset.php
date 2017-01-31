<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\assets\selectize;

use craft\web\AssetBundle;

/**
 * Selectize asset bundle.
 */
class SelectizeAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@bower/selectize/dist';

        $this->css = [
            'css/selectize.css',
        ];

        $this->js = [
            'js/standalone/selectize'.$this->dotJs(),
        ];

        parent::init();
    }
}
