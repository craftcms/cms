<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\htmx;

use yii\web\AssetBundle;

/**
 * Sortable asset bundle.
 */
class HtmxAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = '@lib/htmx';

        $this->css = [];

        $this->js = [
            'htmx.js',
        ];

        parent::init();
    }
}
