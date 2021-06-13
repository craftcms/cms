<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\db\Table;
use craft\errors\DbConnectException;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use yii\db\Exception as DbException;

/**
 * @inheritdoc
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class AssetManager extends \yii\web\AssetManager
{
    /**
     * Returns the published path of a file/directory path.
     *
     * @param string $sourcePath directory or file path being published
     * @param bool $publish whether the directory or file should be published, if not already
     * @return string|false the published file or directory path, or false if $publish is false and the file or directory does not exist
     * @todo remove this in Craft 4 (nothing is using $publish anymore)
     */
    public function getPublishedPath($sourcePath, bool $publish = false)
    {
        if ($publish === true) {
            [$path] = $this->publish($sourcePath);
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
            [, $url] = $this->publish($sourcePath);
        } else {
            $url = parent::getPublishedUrl($sourcePath);
        }

        if ($filePath !== null) {
            $url .= '/' . $filePath;

            // Should we append a timestamp?
            if ($this->appendTimestamp) {
                $fullPath = FileHelper::normalizePath(Craft::getAlias($sourcePath) . DIRECTORY_SEPARATOR . $filePath);
                if (($timestamp = @filemtime($fullPath)) > 0) {
                    $url .= '?v=' . $timestamp;
                }
            }
        }

        return $url;
    }

    /**
     * @inheritdoc
     */
    protected function hash($path)
    {
        if (is_callable($this->hashCallback)) {
            return call_user_func($this->hashCallback, $path);
        }

        $dir = is_file($path) ? dirname($path) : $path;
        $alias = Craft::alias($dir);
        $hash = sprintf('%x', crc32($alias . '|' . FileHelper::lastModifiedTime($path) . '|' . $this->linkAssets));

        // Store the hash for later
        try {
            Db::upsert(Table::RESOURCEPATHS, [
                'hash' => $hash,
            ], [
                'path' => $alias,
            ], [], false);
        } catch (DbException $e) {
            // Craft is either not installed or not updated to 3.0.3+ yet
        } catch (DbConnectException $e) {
            // Craft is either not installed or not updated to 3.0.3+ yet
        }

        return $hash;
    }

    /**
     * @inheritdoc
     */
    protected function publishDirectory($src, $options): array
    {
        [$dir, $url] = parent::publishDirectory($src, $options);

        // A backslash can cause issues on Windows here.
        $url = str_replace('\\', '/', $url);

        return [$dir, $url];
    }

    /**
     * @inheritdoc
     */
    protected function publishFile($src)
    {
        [$file, $url] = parent::publishFile($src);

        // A backslash can cause issues on Windows here.
        $url = str_replace('\\', '/', $url);

        if ($this->appendTimestamp && strpos($url, '?') === false && ($timestamp = @filemtime($src)) > 0) {
            $url .= '?v=' . $timestamp;
        }

        return [$file, $url];
    }
}
