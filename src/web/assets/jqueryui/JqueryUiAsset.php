<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\jqueryui;

use craft\web\AssetBundle;
use yii\web\JqueryAsset;

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

        $this->depends = [
            JqueryAsset::class,
        ];

        $this->js = [
            'jquery-ui'.$this->dotJs(),
        ];

        parent::init();
    }
}
