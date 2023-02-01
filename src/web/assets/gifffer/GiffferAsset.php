<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\gifffer;

use craft\web\AssetBundle;

/**
 * Gifffer asset bundle.
 */
class GiffferAsset extends AssetBundle
{
    /** @inheritdoc */
    public $sourcePath = __DIR__ . '/dist';

    /** @inheritdoc */
    public $js = [
        'gifffer.js',
    ];
}
