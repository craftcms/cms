<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\assets\jcrop;

use craft\web\assets\AssetBundle;

/**
 * Jcrop asset bundle.
 */
class JcropAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@lib/jcrop';

        $this->css = [
            'jquery.Jcrop.min.css',
        ];

        $this->js = [
            'jquery.Jcrop'.$this->dotJs(),
        ];

        parent::init();
    }
}
