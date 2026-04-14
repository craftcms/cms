<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\fabric;

use craft\web\InternalAssetBundle;

/**
 * Fabric asset bundle.
 */
class FabricAsset extends InternalAssetBundle
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = __DIR__ . '/dist';

        $this->js = [
            'fabric.js',
        ];

        parent::init();
    }
}
