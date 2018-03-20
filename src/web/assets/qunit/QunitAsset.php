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
 */
class QunitAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@lib/qunit';

        $this->css = [
            'qunit-2.1.1.css',
        ];

        $this->js = [
            'qunit-2.1.1.js',
        ];

        parent::init();
    }
}
