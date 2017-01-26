<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\assets\fileupload;

use craft\web\AssetBundle;

/**
 * File Upload asset bundle.
 */
class FileUploadAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@lib/fileupload';

        $this->js = [
            'jquery.ui.widget.js',
            'jquery.fileupload.js',
        ];

        parent::init();
    }
}
