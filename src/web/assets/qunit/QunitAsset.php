<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\qunit;

use craft\web\AssetBundle;

/**
 * Qunit asset bundle.
 *
 * Qunit asset files are manually added to the `dist` directory.
 */
class QunitAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = __DIR__ . '/dist';

        $this->css = [
            'qunit-2.1.1.css',
        ];

        $this->js = [
            'qunit-2.1.1.js',
        ];

        parent::init();
    }
}
