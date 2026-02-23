<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fs\bridge;

use craft\base\BaseFsInterface;
use CraftCms\Cms\Filesystem\Data\FsListing;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToListContents;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use Throwable;

final class LegacyFsFlysystemAdapter implements FilesystemAdapter
{
    public const string DISK_DRIVER = 'craft-fs-bridge';

    public function __construct(
        private readonly BaseFsInterface $filesystem,
    ) {
    }

    public function fileExists(string $path): bool
    {
        try {
            return $this->filesystem->fileExists($path);
        } catch (Throwable $e) {
            throw UnableToCheckExistence::forLocation($path, $e);
        }
    }

    public function directoryExists(string $path): bool
    {
        try {
            return $this->filesystem->directoryExists($path);
        } catch (Throwable $e) {
            throw UnableToCheckExistence::forLocation($path, $e);
        }
    }

    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $this->filesystem->write($path, $contents, $config->toArray());
        } catch (Throwable $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        if (!is_resource($contents)) {
            throw UnableToWriteFile::atLocation($path, 'Invalid stream resource provided.');
        }

        try {
            $this->filesystem->writeFileFromStream($path, $contents, $config->toArray());
        } catch (Throwable $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function read(string $path): string
    {
        try {
            return $this->filesystem->read($path);
        } catch (Throwable $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }
    }

    public function readStream(string $path)
    {
        try {
            $stream = $this->filesystem->getFileStream($path);
        } catch (Throwable $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }

        if (!is_resource($stream)) {
            throw UnableToReadFile::fromLocation($path, 'Failed to read stream.');
        }

        return $stream;
    }

    public function delete(string $path): void
    {
        try {
            $this->filesystem->deleteFile($path);
        } catch (Throwable $e) {
            throw UnableToDeleteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function deleteDirectory(string $path): void
    {
        try {
            $this->filesystem->deleteDirectory($path);
        } catch (Throwable $e) {
            throw UnableToDeleteDirectory::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        try {
            $this->filesystem->createDirectory($path, $config->toArray());
        } catch (Throwable $e) {
            throw UnableToCreateDirectory::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        throw UnableToSetVisibility::atLocation($path, 'Legacy filesystem visibility is not configurable.');
    }

    public function visibility(string $path): FileAttributes
    {
        throw UnableToRetrieveMetadata::visibility($path, 'Legacy filesystem visibility metadata is not available.');
    }

    public function mimeType(string $path): FileAttributes
    {
        throw UnableToRetrieveMetadata::mimeType($path, 'Legacy filesystem MIME type metadata is not available.');
    }

    public function lastModified(string $path): FileAttributes
    {
        try {
            return new FileAttributes($path, lastModified: $this->filesystem->getDateModified($path));
        } catch (Throwable $e) {
            throw UnableToRetrieveMetadata::lastModified($path, $e->getMessage(), $e);
        }
    }

    public function fileSize(string $path): FileAttributes
    {
        try {
            return new FileAttributes($path, fileSize: $this->filesystem->getFileSize($path));
        } catch (Throwable $e) {
            throw UnableToRetrieveMetadata::fileSize($path, $e->getMessage(), $e);
        }
    }

    /**
     * @return iterable<StorageAttributes>
     */
    public function listContents(string $path, bool $deep): iterable
    {
        try {
            foreach ($this->filesystem->getFileList($path, $deep) as $item) {
                if (!$item instanceof FsListing) {
                    continue;
                }

                $uri = $item->getUri();

                if ($item->getIsDir()) {
                    yield new DirectoryAttributes($uri, lastModified: $item->getDateModified());
                    continue;
                }

                yield new FileAttributes(
                    $uri,
                    fileSize: $item->getFileSize(),
                    lastModified: $item->getDateModified(),
                );
            }
        } catch (Throwable $e) {
            throw UnableToListContents::atLocation($path, $deep, $e);
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->filesystem->renameFile($source, $destination, $config->toArray());
        } catch (Throwable $e) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $e);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $this->filesystem->copyFile($source, $destination, $config->toArray());
        } catch (Throwable $e) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $e);
        }
    }
}
