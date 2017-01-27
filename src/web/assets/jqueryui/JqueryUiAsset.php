<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\assets\jqueryui;

use craft\web\assets\AssetBundle;

/**
 * jQuery UI asset bundle.
 */
class JqueryUiAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@lib/jquery-ui';

        $this->js = [
            'jquery-ui'.$this->dotJs(),
        ];

        parent::init();
    }
}
