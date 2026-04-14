<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\garnish;

use craft\web\InternalAssetBundle;
use craft\web\assets\elementresizedetector\ElementResizeDetectorAsset;
use craft\web\assets\jquerytouchevents\JqueryTouchEventsAsset;
use craft\web\assets\velocity\VelocityAsset;
use craft\web\assets\jquery\JqueryAsset;

/**
 * Garnish asset bundle.
 */
class GarnishAsset extends InternalAssetBundle
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = __DIR__ . '/dist';
        $this->depends = [
            ElementResizeDetectorAsset::class,
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
