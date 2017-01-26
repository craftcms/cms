<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\assets\velocity;

use craft\web\AssetBundle;

/**
 * Velocity asset bundle.
 */
class VelocityAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@bower/velocity';

        $this->js = [
            'velocity'.$this->dotJs(),
        ];

        parent::init();
    }
}
