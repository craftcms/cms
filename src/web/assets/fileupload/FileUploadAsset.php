<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\fileupload;

use craft\web\AssetBundle;
use craft\web\assets\jqueryui\JqueryUiAsset;

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

        $this->depends = [
            JqueryUiAsset::class,
        ];

        $this->js = [
            'jquery.fileupload.js',
        ];

        parent::init();
    }
}
