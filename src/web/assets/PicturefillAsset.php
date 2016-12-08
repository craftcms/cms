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
 * Picturefill asset bundle.
 */
class PicturefillAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@bower/picturefill/dist';

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (Craft::$app->getConfig()->get('useCompressedJs')) {
            $this->js[] = 'picturefill.min.js';
        } else {
            $this->js[] = 'picturefill.js';
        }
    }
}
