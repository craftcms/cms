<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\totp;

use craft\web\InternalAssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * TOTP asset bundle
 */
class TotpAsset extends InternalAssetBundle
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
