<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\assets\redactor;

use craft\web\assets\AssetBundle;

/**
 * Redactor asset bundle.
 */
class RedactorAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@lib/redactor';

        // TODO: Create compressed versions of redactor.css and redactor.js with our hacks
        $this->css = [
            'redactor.css',
        ];

        $this->js = [
            'redactor.js',
        ];

        parent::init();
    }
}
