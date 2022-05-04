<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
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
    public function init(): void
    {
        $this->sourcePath = __dir__ . '/dist';

        $this->css = [
            'css/selectize.css',
        ];

        $this->js = [
            'selectize.js',
        ];

        parent::init();
    }
}
