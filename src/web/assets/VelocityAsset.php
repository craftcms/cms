<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\assets;

use Craft;
use yii\web\AssetBundle;

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

        if (Craft::$app->getConfig()->get('useCompressedJs')) {
            $this->js[] = 'velocity.min.js';
        } else {
            $this->js[] = 'velocity.js';
        }

        parent::init();
    }
}
