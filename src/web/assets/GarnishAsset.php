<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\assets;

use Craft;
use yii\web\AssetBundle;
use yii\web\JqueryAsset;

/**
 * Garnish asset bundle.
 */
class GarnishAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@bower/garnishjs/dist';
        $this->depends = [
            ElementResizeDetectorAsset::class,
            JqueryAsset::class,
            JqueryTouchEventsAsset::class,
            VelocityAsset::class,
        ];

        if (Craft::$app->getConfig()->get('useCompressedJs')) {
            $this->js[] = 'garnish.min.js';
        } else {
            $this->js[] = 'garnish.js';
        }

        parent::init();
    }
}
