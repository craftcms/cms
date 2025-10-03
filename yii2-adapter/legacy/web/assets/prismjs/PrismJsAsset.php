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
 *
 * Prism JS files are manually added to the `dist` directory.
 */
class PrismJsAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = __DIR__ . '/dist';

        $this->js = [
            'prism.js',
        ];

        $this->css = [
            'prism.css',
        ];

        parent::init();
    }
}
