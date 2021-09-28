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
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $js = [
        'jquery.fileupload.js',
    ];

    /**
     * @inheritdoc
     */
    public $depends = [
        JqueryUiAsset::class,
    ];
}
