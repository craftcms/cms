<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\assets\richtext;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\redactor\RedactorAsset;

/**
 * Asset bundle for Rich Text fields
 */
class RichTextAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = __DIR__.'/dist';

        $this->depends = [
            CpAsset::class,
            RedactorAsset::class,
        ];

        $this->js = [
            'RichTextInput'.$this->dotJs(),
        ];

        parent::init();
    }
}
