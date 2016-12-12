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
    public function init()
    {
        $this->sourcePath = '@bower/selectize/dist';
        $this->css = [
            'css/selectize.css',
        ];

        if (Craft::$app->getConfig()->get('useCompressedJs')) {
            $this->js[] = 'js/standalone/selectize.min.js';
        } else {
            $this->js[] = 'js/standalone/selectize.js';
        }

        parent::init();
    }
}
