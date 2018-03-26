<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use yii\base\ErrorException;

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

        // don't include the file modified time in the path in case this is a load-balanced environment
        $path = realpath($path);
        return sprintf('%x', crc32($path.'|'.$this->linkAssets));
    }

    /**
     * @inheritdoc
     */
    protected function publishDirectory($src, $options): array
    {
        // Make sure forceCopy is set (accurately) so we know whether we should update CSS files later
        if ($this->linkAssets) {
            $options['forceCopy'] = false;
        } else if (!isset($options['forceCopy']) && ($options['forceCopy'] = $this->forceCopy) == false) {
            // see if any of the files have been updated
            $dir = $this->hash($src);
            $dstDir = $this->basePath.DIRECTORY_SEPARATOR.$dir;
            if (!is_dir($dstDir) || FileHelper::hasAnythingChanged($src, $dstDir) === true) {
                $options['forceCopy'] = true;
            }
        }

        list($dir, $url) = parent::publishDirectory($src, $options);

        if ($options['forceCopy']) {
            $this->_addTimestampsToCssUrls($dir);
        }

        // A backslash can cause issues on Windows here.
        $url = str_replace('\\', '/', $url);

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

        if ($this->appendTimestamp && strpos($url, '?') === false && ($timestamp = @filemtime($src)) > 0) {
            $url .= '?v='.$timestamp;
        }

        return [$file, $url];
    }

    /**
     * Finds CSS files within the published directory and adds timestamps to any url()'s within them.
     *
     * @param string $dir
     */
    private function _addTimestampsToCssUrls(string $dir)
    {
        $timestamp = time();

        $cssFiles = FileHelper::findFiles($dir, [
            'only' => ['*.css'],
            'recursive' => true,
        ]);

        foreach ($cssFiles as $path) {
            $content = file_get_contents($path);
            $content = preg_replace_callback('/(url\(([\'"]?))(.+?)(\2\))/', function($match) use ($timestamp) {
                $url = $match[3];
                // Ignore root-relative, absolute, and data: URLs
                if (preg_match('/^(\/|https?:\/\/|data:)/', $url)) {
                    return $match[0];
                }
                $url = UrlHelper::urlWithParams($url, ['v' => $timestamp]);
                return $match[1].$url.$match[4];
            }, $content);
            file_put_contents($path, $content);
        }
    }
}
