<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\prismjs;

use craft\web\AssetBundle;

/**
 * PrismJs asset bundle.
 */
class PrismJsAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@lib/prismjs';

        $this->js = [
            'prism.js',
        ];

        $this->css = [
            'prism.css',
        ];

        parent::init();
    }
}
