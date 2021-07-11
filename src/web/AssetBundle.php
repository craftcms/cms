<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

/**
 * @inheritdoc
 * @deprecated in 4.0.0. Use [[\yii\web\AssetBundle]] instead.
 */
class AssetBundle extends \yii\web\AssetBundle
{
    /**
     * Returns '.min.js'.
     *
     * @return string
     * @deprecated in 3.5.0
     */
    protected function dotJs(): string
    {
        return '.min.js';
    }
}
