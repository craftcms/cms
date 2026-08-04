<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\recoverycodes;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Recovery codes asset bundle
 * @deprecated in 6.0
 */
class RecoveryCodesAsset extends AssetBundle
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
    public $js = [
        'recoverycodes.js',
    ];
}
