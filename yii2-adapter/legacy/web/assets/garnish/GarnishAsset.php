<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\garnish;

use craft\web\AssetBundle;
use craft\web\assets\jquerytouchevents\JqueryTouchEventsAsset;
use craft\web\assets\velocity\VelocityAsset;
use yii\web\JqueryAsset;

/**
 * Garnish asset bundle.
 */
class GarnishAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = '@assetBundles/garnish/dist';
        $this->depends = [
            JqueryAsset::class,
            JqueryTouchEventsAsset::class,
            VelocityAsset::class,
        ];

        $this->js = [
            'garnish.js',
        ];

        parent::init();
    }
}
