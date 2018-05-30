<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\xregexp;

use craft\web\AssetBundle;

/**
 * Xregexp asset bundle.
 */
class XregexpAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@lib/xregexp';

        $this->js = [
            'xregexp-all.js',
        ];

        parent::init();
    }
}
