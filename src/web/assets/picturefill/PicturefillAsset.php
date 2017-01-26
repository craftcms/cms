<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\assets\picturefill;

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
    public function init()
    {
        $this->sourcePath = '@bower/picturefill/dist';

        if (Craft::$app->getConfig()->get('useCompressedJs')) {
            $this->js[] = 'picturefill.min.js';
        } else {
            $this->js[] = 'picturefill.js';
        }

        parent::init();
    }
}
