<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\htmx;

use craft\web\assets\cp\CpAsset;
use craft\web\View;
use yii\web\AssetBundle;

/**
 * Htmx asset bundle.
 */
class HtmxAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = '@lib/htmx';

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'htmx.js',
        ];

        parent::init();
    }
}
