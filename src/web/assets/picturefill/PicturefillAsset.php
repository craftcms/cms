<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\picturefill;

use craft\web\AssetBundle;

/**
 * Picturefill asset bundle.
 */
class PicturefillAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = __DIR__ . '/dist';

        $this->js = [
            'picturefill.js',
        ];

        parent::init();
    }
}
