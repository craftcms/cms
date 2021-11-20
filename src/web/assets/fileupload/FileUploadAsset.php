<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\fileupload;

use craft\web\assets\jqueryui\JqueryUiAsset;
use yii\web\AssetBundle;

/**
 * File Upload asset bundle.
 */
class FileUploadAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = __DIR__ . '/dist';

        $this->depends = [
            JqueryUiAsset::class,
        ];

        $this->js = [
            'jquery.fileupload.js',
        ];

        parent::init();
    }
}
