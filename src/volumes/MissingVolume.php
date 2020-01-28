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
use yii\base\NotSupportedException;

/**
 * MissingVolume represents a volume with an invalid class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
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
    public function getFileList(string $directory, bool $recursive): array
    {
        throw new NotSupportedException('getFileList() is not implemented.');
    }

    /**
     * @inheritdoc
     */
    public function getFileMetadata(string $uri): array
    {
        throw new NotSupportedException('getFileMetadata() is not implemented.');
    }

    /**
     * @inheritdoc
     */
    public function createFileByStream(string $path, $stream, array $config)
    {
        throw new NotSupportedException('createFileByStream() is not implemented.');
    }

    /**
     * @inheritdoc
     */
    public function updateFileByStream(string $path, $stream, array $config)
    {
        throw new NotSupportedException('updateFileByStream() is not implemented.');
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
    public function deleteFile(string $path)
    {
        throw new NotSupportedException('deleteFile() is not implemented.');
    }

    /**
     * @inheritdoc
     */
    public function renameFile(string $path, string $newPath)
    {
        throw new NotSupportedException('renameFile() is not implemented.');
    }

    /**
     * @inheritdoc
     */
    public function copyFile(string $path, string $newPath)
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
    public function folderExists(string $path): bool
    {
        throw new NotSupportedException('folderExists() is not implemented.');
    }

    /**
     * @inheritdoc
     */
    public function createDir(string $path)
    {
        throw new NotSupportedException('createDir() is not implemented.');
    }

    /**
     * @inheritdoc
     */
    public function deleteDir(string $path)
    {
        throw new NotSupportedException('deleteDir() is not implemented.');
    }

    /**
     * @inheritdoc
     */
    public function renameDir(string $path, string $newName)
    {
        throw new NotSupportedException('renameDir() is not implemented.');
    }
}
