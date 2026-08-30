<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\mockclasses\fs;

use craft\base\Fs;
use craft\errors\FsException;
use craft\errors\FsObjectNotFoundException;
use craft\helpers\FileHelper;
use Generator;

/**
 * A filesystem that stores its files on the local disk, like [[\craft\fs\Local]], but deliberately
 * does *not* implement [[\craft\base\LocalFsInterface]], so it can be used in tests that need to
 * exercise code paths that only run for non-local filesystems (e.g. remote asset source caching).
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class MockNonLocalFs extends Fs
{
    /**
     * @var string Path to the root of this filesystem’s local folder.
     */
    public string $path;

    /**
     * @inheritdoc
     */
    public function getFileList(string $directory = '', bool $recursive = true): Generator
    {
        yield from [];
    }

    /**
     * @inheritdoc
     */
    public function getFileSize(string $uri): int
    {
        $size = @filesize($this->prefixPath($uri));
        if ($size === false) {
            throw new FsException("Unable to get file size for \"$uri\".");
        }
        return $size;
    }

    /**
     * @inheritdoc
     */
    public function getDateModified(string $uri): int
    {
        clearstatcache();
        $dateModified = @filemtime($this->prefixPath($uri));
        if ($dateModified === false) {
            throw new FsException("Unable to get date modified for \"$uri\".");
        }
        return $dateModified;
    }

    /**
     * @inheritdoc
     */
    public function write(string $path, string $contents, array $config = []): void
    {
        FileHelper::writeToFile($this->prefixPath($path), $contents);
    }

    /**
     * @inheritdoc
     */
    public function read(string $path): string
    {
        return file_get_contents($this->prefixPath($path));
    }

    /**
     * @inheritdoc
     */
    public function writeFileFromStream(string $path, $stream, array $config = []): void
    {
        $target = @fopen($this->prefixPath($path), 'wb');
        stream_copy_to_stream($stream, $target);
        fclose($target);
    }

    /**
     * @inheritdoc
     */
    public function fileExists(string $path): bool
    {
        return is_file($this->prefixPath($path));
    }

    /**
     * @inheritdoc
     */
    public function deleteFile(string $path): void
    {
        FileHelper::unlink($this->prefixPath($path));
    }

    /**
     * @inheritdoc
     */
    public function renameFile(string $path, string $newPath, array $config = []): void
    {
        rename($this->prefixPath($path), $this->prefixPath($newPath));
    }

    /**
     * @inheritdoc
     */
    public function copyFile(string $path, string $newPath, array $config = []): void
    {
        copy($this->prefixPath($path), $this->prefixPath($newPath));
    }

    /**
     * @inheritdoc
     */
    public function getFileStream(string $uriPath)
    {
        $stream = @fopen($this->prefixPath($uriPath), 'rb');
        if (!$stream) {
            throw new FsObjectNotFoundException("Unable to open \"$uriPath\".");
        }
        return $stream;
    }

    /**
     * @inheritdoc
     */
    public function directoryExists(string $path): bool
    {
        return is_dir($this->prefixPath($path));
    }

    /**
     * @inheritdoc
     */
    public function createDirectory(string $path, array $config = []): void
    {
        FileHelper::createDirectory($this->prefixPath($path));
    }

    /**
     * @inheritdoc
     */
    public function deleteDirectory(string $path): void
    {
        FileHelper::removeDirectory($this->prefixPath($path));
    }

    /**
     * @inheritdoc
     */
    public function renameDirectory(string $path, string $newName): void
    {
        rename($this->prefixPath($path), $this->prefixPath($newName));
    }

    private function prefixPath(string $path): string
    {
        return rtrim($this->path, '/') . '/' . ltrim($path, '/');
    }
}
