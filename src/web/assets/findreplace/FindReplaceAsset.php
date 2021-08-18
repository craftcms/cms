<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\findreplace;

use craft\web\assets\cp\CpAsset;
use yii\web\AssetBundle;

/**
 * Asset bundle for the Find & Replace utility
 */
class FindReplaceAsset extends AssetBundle
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
        'FindAndReplaceUtility.min.js',
    ];
}
