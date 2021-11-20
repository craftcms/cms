<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\deprecationerrors;

use craft\web\assets\cp\CpAsset;
use yii\web\AssetBundle;

/**
 * Asset bundle for the Deprecation Warnings utility
 */
class DeprecationErrorsAsset extends AssetBundle
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
        'css/deprecator.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'deprecator.min.js',
    ];
}
