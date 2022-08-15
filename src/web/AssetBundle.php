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
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (Craft::$app->getConfig()->getGeneral()->devMode) {
            $this->_updateResourcePaths();
        }
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
     * @param array|string $filePath
     * @return string|array
     * @since 3.7.22
     */
    private function _prependDevServer(array|string $filePath): array|string
    {
        // Handle $filePath being an array: https://www.yiiframework.com/doc/api/2.0/yii-web-assetbundle#$js-detail
        if (is_array($filePath)) {
            $fileArray = $filePath;
            $filePath = $filePath[0];
        }

        $devServer = rtrim(Craft::$app->getWebpack()->getDevServer(static::class), '/');
        $filePath = ($devServer ? $devServer . '/' : '') . $filePath;

        if (isset($fileArray)) {
            $fileArray[0] = $filePath;
            $filePath = $fileArray;
        }

        return $filePath;
    }
}
