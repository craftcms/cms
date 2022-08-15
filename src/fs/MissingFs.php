<?php

/** @noinspection PhpInconsistentReturnPointsInspection */
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fs;

use craft\base\Fs;
use craft\base\FsInterface;
use craft\base\MissingComponentInterface;
use craft\base\MissingComponentTrait;
use Generator;
use yii\base\NotSupportedException;

/**
 * MissingFs represents a filesystem with an invalid class.
 *
 * @property class-string<FsInterface> $expectedType
 * @property-read false $rootUrl
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class MissingFs extends Fs implements MissingComponentInterface
{
    use MissingComponentTrait;

    /**
     * @inheritdoc
     */
    public function getRootUrl(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getFileList(string $directory = '', bool $recursive = true): Generator
    {
        throw new NotSupportedException('getFileList() is not implemented.');
    }

    /**
     * @inheritdoc
     */
    public function fileExists(string $path): bool
    {
        throw new NotSupportedException('fileExists() is not implemented.');
    }

    /**
     * @inheritdoc
     */
    public function deleteFile(string $path): void
    {
        throw new NotSupportedException('deleteFile() is not implemented.');
    }

    /**
     * @inheritdoc
     */
    public function renameFile(string $path, string $newPath): void
    {
        throw new NotSupportedException('renameFile() is not implemented.');
    }

    /**
     * @inheritdoc
     */
    public function copyFile(string $path, string $newPath): void
    {
        throw new NotSupportedException('copyFile() is not implemented.');
    }

    /**
     * @inheritdoc
     */
    public function saveFileLocally(string $uriPath, string $targetPath): int
    {
        throw new NotSupportedException('saveFileLocally() is not implemented.');
    }

    /**
     * @inheritdoc
     */
    public function getFileStream(string $uriPath)
    {
        throw new NotSupportedException('getFileStream() is not implemented.');
    }

    /**
     * @inheritdoc
     */
    public function read(string $path): string
    {
        throw new NotSupportedException('read() is not implemented.');
    }

    /**
     * @inheritdoc
     */
    public function write(string $path, string $contents, array $config = []): void
    {
        throw new NotSupportedException('write() is not implemented.');
    }

    /**
     * @inheritdoc
     */
    public function directoryExists(string $path): bool
    {
        throw new NotSupportedException('directoryExists() is not implemented.');
    }

    /**
     * @inheritdoc
     */
    public function createDirectory(string $path, array $config = []): void
    {
        throw new NotSupportedException('createDirectory() is not implemented.');
    }

    /**
     * @inheritdoc
     */
    public function deleteDirectory(string $path): void
    {
        throw new NotSupportedException('deleteDirectory() is not implemented.');
    }

    /**
     * @inheritdoc
     */
    public function renameDirectory(string $path, string $newName): void
    {
        throw new NotSupportedException('renameDirectory() is not implemented.');
    }

    /**
     * @inheritdoc
     */
    public function getFileSize(string $uri): int
    {
        throw new NotSupportedException('getFileSize() is not implemented.');
    }

    /**
     * @inheritdoc
     */
    public function getDateModified(string $uri): int
    {
        throw new NotSupportedException('getDateModified() is not implemented.');
    }

    /**
     * @inheritdoc
     */
    public function writeFileFromStream(string $path, $stream, array $config = []): void
    {
        throw new NotSupportedException('writeFileFromStream() is not implemented.');
    }
}
