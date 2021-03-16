<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\volumes;

use craft\base\MissingComponentInterface;
use craft\base\MissingComponentTrait;
use craft\base\Volume;
use craft\errors\VolumeException;
use yii\base\NotSupportedException;

/**
 * MissingVolume represents a volume with an invalid class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 *
 * @property-read false $rootUrl
 */
class MissingVolume extends Volume implements MissingComponentInterface
{
    use MissingComponentTrait;

    /**
     * @inheritdoc
     */
    public function getRootUrl()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getFileList(string $directory = '', bool $recursive = true): array
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
