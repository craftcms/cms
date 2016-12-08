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
 * Selectize asset bundle.
 */
class SelectizeAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@bower/selectize/dist';

    /**
     * @inheritdoc
     */
    public $css = [
        'css/selectize.css',
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (Craft::$app->getConfig()->get('useCompressedJs')) {
            $this->js[] = 'js/standalone/selectize.min.js';
        } else {
            $this->js[] = 'js/standalone/selectize.js';
        }
    }
}
