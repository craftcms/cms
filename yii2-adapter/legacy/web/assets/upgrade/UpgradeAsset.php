<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\upgrade;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Asset bundle for the Upgrade utility
 *
 * @since 3.7.40
 */
class UpgradeAsset extends AssetBundle
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
        'css/UpgradeUtility.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'UpgradeUtility.js',
    ];
}
