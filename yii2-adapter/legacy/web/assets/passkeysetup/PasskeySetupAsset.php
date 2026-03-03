<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\passkeysetup;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Passkey setup asset bundle.
 *
 * @since 5.0.0
 */
class PasskeySetupAsset extends AssetBundle
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
        'PasskeySetup.js',
    ];
}
