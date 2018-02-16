<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\helpers\FileHelper;

/**
 * @inheritdoc
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetManager extends \yii\web\AssetManager
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the published path of a file/directory path.
     *
     * @param string $sourcePath directory or file path being published
     * @param bool $publish whether the directory or file should be published, if not already
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
     * @param bool $publish whether the directory or file should be published, if not already
     * @param string|null $filePath A file path, relative to $sourcePath if $sourcePath is a directory, that should be appended to the returned URL.
     * @return string|false the published URL for the file or directory, or false if $publish is false and the file or directory does not exist
     */
    public function getPublishedUrl($sourcePath, bool $publish = false, $filePath = null)
    {
        if ($publish === true) {
            list(, $url) = $this->publish($sourcePath);
        } else {
            $url = parent::getPublishedUrl($sourcePath);
        }

        if ($filePath !== null) {
            $url .= '/'.$filePath;

            // Should we append a timestamp?
            if ($this->appendTimestamp) {
                $fullPath = FileHelper::normalizePath(Craft::getAlias($sourcePath).DIRECTORY_SEPARATOR.$filePath);
                if (($timestamp = @filemtime($fullPath)) > 0) {
                    $url .= '?v='.$timestamp;
                }
            }
        }

        return $url;
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

        // Return as two directories - one representing the path, and a subdirectory representing the modified time
        $path = realpath($path);
        $mtime = FileHelper::lastModifiedTime($path);

        return sprintf('%x', crc32($path)).DIRECTORY_SEPARATOR.sprintf('%x', crc32($mtime));
    }

    /**
     * @inheritdoc
     */
    protected function publishDirectory($src, $options): array
    {
        list($dir, $url) = parent::publishDirectory($src, $options);

        // A backslash can cause issues on Windows here.
        $url = str_replace('\\', '/', $url);

        // Clear out any older instances of the same directory
        $this->_clearOldDirs($dir);

        return [$dir, $url];
    }

    /**
     * @inheritdoc
     */
    protected function publishFile($src)
    {
        list($file, $url) = parent::publishFile($src);

        // A backslash can cause issues on Windows here.
        $url = str_replace('\\', '/', $url);

        // Clear out any older instances of the same file
        $this->_clearOldDirs(dirname($file));

        if ($this->appendTimestamp && strpos($url, '?') === false && ($timestamp = @filemtime($src)) > 0) {
            $url .= '?v='.$timestamp;
        }

        return [$file, $url];
    }

    /**
     * Deletes outdated published directories that live alongside a newly-published one.
     *
     * @param string $newDir The directory that was just published
     */
    private function _clearOldDirs($newDir)
    {
        // Does this look like it was named using our hash()?
        $name = basename($newDir);
        if (preg_match('/^[a-f0-9]{8}$/', $name)) {
            $parent = dirname($newDir);
            if (preg_match('/^[a-f0-9]{8}$/', basename($parent))) {
                FileHelper::clearDirectory($parent, [
                    'except' => [$name]
                ]);
            }
        }
    }
}
