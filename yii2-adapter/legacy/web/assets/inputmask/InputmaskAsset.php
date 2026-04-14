<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\inputmask;

use craft\web\InternalAssetBundle;

/**
 * Inputmask asset bundle
 *
 * @since 4.5.7
 */
class InputmaskAsset extends InternalAssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $js = [
        'jquery.inputmask.bundle.js',
    ];
}
