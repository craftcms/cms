<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\velocity;

use craft\web\InternalAssetBundle;

/**
 * Velocity asset bundle.
 */
class VelocityAsset extends InternalAssetBundle
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = __DIR__ . '/dist';

        $this->js = [
            'velocity.js',
        ];

        parent::init();
    }
}
