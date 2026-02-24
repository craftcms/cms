<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\updates;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Asset bundle for the Updates utility
 */
class UpdatesAsset extends AssetBundle
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
        'css/UpdatesUtility.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'UpdatesUtility.js',
    ];
}
