<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\pjax;

use yii\widgets\PjaxAsset as YiiPjaxAsset;

/**
 * Pjax asset bundle.
 *
 * @since 4.0.
 */
class PjaxAsset extends YiiPjaxAsset
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';
}