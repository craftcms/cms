<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\jqueryui;

use craft\web\AssetBundle;
use yii\web\JqueryAsset;

/**
 * jQuery UI asset bundle.
 */
class JqueryUiAsset extends AssetBundle
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

    /**
     * @inheritdoc
     */
    public $js = [
        'jquery-ui.js',
    ];
}
