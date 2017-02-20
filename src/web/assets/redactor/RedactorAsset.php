<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\assets\redactor;

use craft\web\AssetBundle;
use yii\web\JqueryAsset;

/**
 * Redactor asset bundle.
 */
class RedactorAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@lib/redactor';

        $this->depends = [
            JqueryAsset::class,
        ];

        $this->css = [
            'redactor.min.css',
        ];

        $this->js = [
            'redactor'.$this->dotJs(),
        ];

        parent::init();
    }
}
