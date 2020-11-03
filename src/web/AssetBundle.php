<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;

/**
 * @inheritdoc
 */
class AssetBundle extends \yii\web\AssetBundle
{
    /**
     * @var bool Whether Craft is configured to serve compressed JavaScript files
     */
    private static $_useCompressedJs;

    /**
     * Returns whether Craft is configured to serve compressed JavaScript files
     *
     * @return bool
     * @deprecated in 3.5.0
     */
    protected function useCompressedJs(): bool
    {
        if (self::$_useCompressedJs !== null) {
            return self::$_useCompressedJs;
        }

        return self::$_useCompressedJs = (bool)Craft::$app->getConfig()->getGeneral()->useCompressedJs;
    }

    /**
     * Returns '.min.js' if Craft is configured to serve compressed JavaScript files, otherwise '.js'.
     *
     * @return string
     * @deprecated in 3.5.0
     */
    protected function dotJs(): string
    {
        return $this->useCompressedJs() ? '.min.js' : '.js';
    }
}
