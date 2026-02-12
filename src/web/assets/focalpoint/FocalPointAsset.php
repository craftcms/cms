<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\focalpoint;

use craft\web\AssetBundle;
use yii\web\JqueryAsset;

/**
 * Asset bundle for the Focal Point class.
 */
class FocalPointAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $depends = [
        JqueryAsset::class,
    ];

    public $css = [
        'css/FocalPoint.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'FocalPoint.js',
    ];
}
