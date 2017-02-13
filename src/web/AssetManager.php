<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web;

use Craft;
use craft\helpers\FileHelper;

/**
 * @inheritdoc
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class AssetManager extends \yii\web\AssetManager
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the published path of a file/directory path.
     *
     * @param string $sourcePath directory or file path being published
     * @param bool   $publish    whether the directory or file should be published, if not already
     *
     * @return string|false the published file or directory path, or false if $publish is false and the file or directory does not exist
     */
    public function getPublishedPath($sourcePath, bool $publish = false)
    {
        if ($publish === true) {
            list($path) = $this->publish($sourcePath);

            return $path;
        }

        return parent::getPublishedPath($sourcePath);
    }

    /**
     * Returns the URL of a published file/directory path.
     *
     * @param string $sourcePath directory or file path being published
     * @param bool   $publish    whether the directory or file should be published, if not already
     *
     * @return string|false the published URL for the file or directory, or false if $publish is false and the file or directory does not exist
     */
    public function getPublishedUrl($sourcePath, bool $publish = false)
    {
        if ($publish === true) {
            list(, $url) = $this->publish($sourcePath);

            return $url;
        }

        return parent::getPublishedUrl($sourcePath);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function hash($path)
    {
        if (is_callable($this->hashCallback)) {
            return call_user_func($this->hashCallback, $path);
        }

        $path = (is_file($path) ? dirname($path) : $path).FileHelper::lastModifiedTime($path);

        return sprintf('%x', crc32($path.Craft::getVersion()));
    }
}
