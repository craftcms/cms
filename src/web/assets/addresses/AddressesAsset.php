<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\addresses;

use craft\web\AssetBundle;
use craft\web\assets\htmx\HtmxAsset;

/**
 * Condition Builder asset bundle.
 */
class AddressesAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $depends = [
        HtmxAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'Addresses.js',
    ];
}
