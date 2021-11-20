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
     * @var bool Whether Craft is configured to serve compressed JavaScript files
     */
    private static $_useCompressedJs;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->_updateResourcePaths();
    }

    /**
     * Prepend dev server to resources if required
     *
     * @return void
     * @since 3.7.22
     */
    private function _updateResourcePaths(): void
    {
        if (!empty($this->js)) {
            $this->js = array_map([$this, '_prependDevServer'], $this->js);
        }

        if (!empty($this->css)) {
            $this->css = array_map([$this, '_prependDevServer'], $this->css);
        }
    }

    /**
     * Prefix the string with the dev server host if the dev server is running.
     *
     * @param string $filePath
     * @return string
     * @since 3.7.22
     */
    private function _prependDevServer(string $filePath): string
    {
        $devServer = rtrim(Craft::$app->getWebpack()->getDevServer(static::class), '/');
        return ($devServer ? $devServer . '/' : '') . $filePath;
    }

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
