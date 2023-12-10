<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\userphoto;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\fileupload\FileUploadAsset;

/**
 * Asset bundle for user photo fields
 */
class UserPhotoAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $depends = [
        CpAsset::class,
        FileUploadAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public $css = [
        'css/UserPhotoInput.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'UserPhotoInput.js',
    ];
}
