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
 * D3 asset bundle.
 */
class D3Asset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@bower/d3';

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (Craft::$app->getConfig()->get('useCompressedJs')) {
            $this->js[] = 'd3.min.js';
        } else {
            $this->js[] = 'd3.js';
        }
    }
}
