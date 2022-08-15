<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\conditionbuilder;

use craft\web\AssetBundle;
use craft\web\assets\htmx\HtmxAsset;

/**
 * Condition Builder asset bundle.
 */
class ConditionBuilderAsset extends AssetBundle
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
        'ConditionBuilder.js',
    ];
}
