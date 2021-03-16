<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\punycode;

use craft\web\AssetBundle;

/**
 * Punycode asset bundle.
 *
 * @since 4.0.
 */
class PunycodeAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $js = [
        'punycode.js',
    ];
}
