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
 * ElementResizeDetector asset bundle.
 */
class ElementResizeDetectorAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@bower/element-resize-detector/dist';

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (Craft::$app->getConfig()->get('useCompressedJs')) {
            $this->js[] = 'element-resize-detector.min.js';
        } else {
            $this->js[] = 'element-resize-detector.js';
        }
    }
}
