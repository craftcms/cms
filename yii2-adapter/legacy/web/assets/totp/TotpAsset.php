<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\totp;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * TOTP asset bundle
 */
class TotpAsset extends AssetBundle
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
        'totp.js',
    ];
}
