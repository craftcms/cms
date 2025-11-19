<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\userpermissions;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Asset bundle for user permissions
 */
class UserPermissionsAsset extends AssetBundle
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
    ];

    /**
     * @inheritdoc
     */
    public $css = [
        'css/UserPermissions.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'UserPermissions.js',
    ];
}
