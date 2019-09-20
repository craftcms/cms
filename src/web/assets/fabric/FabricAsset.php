<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\fabric;

use craft\web\AssetBundle;

/**
 * Fabric asset bundle.
 */
class FabricAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@lib/fabric';

        $this->js = [
            'fabric.js',
        ];

        parent::init();
    }
}
