<?php
/**
 * The base class for all asset Volumes. All Volume types must extend this class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */

namespace craft\base;

use Craft;
use craft\errors\AssetException;
use craft\errors\VolumeException;
use craft\errors\VolumeObjectExistsException;
use craft\errors\VolumeObjectNotFoundException;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;

/**
 * FlysystemVolume is the base class for Flysystem-based volume classes.
 */
abstract class FlysystemVolume extends Volume
{
    // Properties
    // =========================================================================

    /**
     * @var bool Whether the Flysystem adapter expects folder names to have trailing slashes
     */
    protected $foldersHaveTrailingSlashes = true;

    /**
     * @var AdapterInterface|null The Flysystem adapter, created by [[createAdapter()]]
     */
    private $_adapter;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getFileList(string $directory, bool $recursive): array
    {
        $fileList = $this->filesystem()->listContents($directory, $recursive);
        $output = [];

        foreach ($fileList as $entry) {
            $output[$entry['path']] = $entry;
        }

        return $output;
    }

    /**
     * @inheritdoc
     */
    public function getFileMetadata(string $uri): array
    {
        try {
            return $this->filesystem()->getMetadata($uri);
        } catch (FileNotFoundException $e) {
            throw new VolumeObjectNotFoundException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function createFileByStream(string $path, $stream, array $config)
    {
        try {
            $config = $this->addFileMetadataToConfig($config);
            $success = $this->filesystem()->writeStream($path, $stream, $config);
        } catch (FileExistsException $e) {
            throw new VolumeObjectExistsException($e->getMessage(), 0, $e);
        }

        if (!$success) {
            throw new VolumeException('Couldn’t create file at '.$path);
        }
    }

    /**
     * @inheritdoc
     */
    public function updateFileByStream(string $path, $stream, array $config)
    {
        try {
            $config = $this->addFileMetadataToConfig($config);
            $success = $this->filesystem()->updateStream($path, $stream, $config);
        } catch (FileNotFoundException $e) {
            throw new VolumeObjectNotFoundException($e->getMessage(), 0, $e);
        }

        if (!$success) {
            throw new VolumeException('Couldn’t update '.$path);
        }
    }

    /**
     * @inheritdoc
     */
    public function fileExists(string $path): bool
    {
        return $this->filesystem()->has($path);
    }

    /**
     * @inheritdoc
     */
    public function deleteFile(string $path)
    {
        try {
            $success = $this->filesystem()->delete($path);
        } catch (FileNotFoundException $e) {
            // Make a note of it, but otherwise - mission accomplished!
            Craft::info($e->getMessage(), __METHOD__);
            $success = true;
        }

        if (!$success) {
            throw new VolumeException('Couldn’t delete '.$path);
        }

        $this->invalidateCdnPath($path);
    }

    /**
     * @inheritdoc
     */
    public function renameFile(string $path, string $newPath)
    {
        try {
            $success = $this->filesystem()->rename($path, $newPath);
        } catch (FileExistsException $e) {
            throw new VolumeObjectExistsException($e->getMessage(), 0, $e);
        } catch (FileNotFoundException $e) {
            throw new VolumeObjectNotFoundException($e->getMessage(), 0, $e);
        }

        if (!$success) {
            throw new VolumeException('Couldn’t rename '.$path.' to '.$newPath);
        }
    }

    /**
     * @inheritdoc
     */
    public function copyFile(string $path, string $newPath)
    {
        try {
            $success = $this->filesystem()->copy($path, $newPath);
        } catch (FileExistsException $e) {
            throw new VolumeObjectExistsException($e->getMessage(), 0, $e);
        } catch (FileNotFoundException $e) {
            throw new VolumeObjectNotFoundException($e->getMessage(), 0, $e);
        }

        if (!$success) {
            throw new VolumeException('Couldn’t copy '.$path.' to '.$newPath);
        }
    }

    /**
     * @inheritdoc
     */
    public function getFileStream(string $uriPath)
    {
        $stream = $this->filesystem(['disable_asserts' => true])->readStream($uriPath);

        if (!$stream) {
            throw new AssetException('Could not open create the stream for “'.$uriPath.'”');
        }

        return $stream;
    }

    /**
     * @inheritdoc
     */
    public function saveFileLocally(string $uriPath, string $targetPath): int
    {
        $stream = $this->getFileStream($uriPath);
        $outputStream = fopen($targetPath, 'wb');

        $bytes = stream_copy_to_stream($stream, $outputStream);

        fclose($stream);
        fclose($outputStream);

        return $bytes;
    }

    /**
     * @inheritdoc
     */
    public function folderExists(string $path): bool
    {
        // Calling adapter directly instead of filesystem to avoid losing the trailing slash (if any)
        return $this->adapter()->has(rtrim($path, '/').($this->foldersHaveTrailingSlashes ? '/' : ''));
    }

    /**
     * @inheritdoc
     */
    public function createDir(string $path)
    {
        if ($this->folderExists($path)) {
            throw new VolumeObjectExistsException(Craft::t('app', 'Folder “{folder}” already exists on the volume!', [
                'folder' => $path
            ]));
        }

        if (!$this->filesystem()->createDir($path)) {
            throw new VolumeException('Couldn’t create '.$path);
        }
    }

    /**
     * @inheritdoc
     */
    public function deleteDir(string $path)
    {
        try {
            $success = $this->filesystem()->deleteDir($path);
        } catch (\Throwable $e) {
            throw new VolumeException($e->getMessage(), 0, $e);
        }

        if (!$success) {
            throw new VolumeException('Couldn’t delete '.$path);
        }
    }

    /**
     * @inheritdoc
     */
    public function renameDir(string $path, string $newName)
    {
        // Get the list of dir contents
        $fileList = $this->getFileList($path, true);
        $directoryList = [$path];

        $parts = explode('/', $path);

        array_pop($parts);
        $parts[] = $newName;

        $newPath = implode('/', $parts);

        $pattern = '/^'.preg_quote($path, '/').'/';

        // Rename every file and build a list of directories
        foreach ($fileList as $object) {
            if ($object['type'] !== 'dir') {
                $objectPath = preg_replace($pattern, $newPath, $object['path']);
                $this->renameFile($object['path'], $objectPath);
            } else {
                $directoryList[] = $object['path'];
            }
        }

        // It's possible for a folder object to not exist on remote volumes, so to throw an exception
        // we must make sure that there are no files AS WELL as no folder.
        if (empty($fileList) && !$this->folderExists($path)) {
            throw new VolumeObjectNotFoundException('No folder exists at path: '.$path);
        }

        // The files are moved, but the directories remain. Delete them.
        foreach ($directoryList as $dir) {
            try {
                $this->deleteDir($dir);
            } catch (\Throwable $e) {
                // This really varies between volume types and whether folders are virtual or real
                // So just in case, catch the exception, log it and then move on
                Craft::warning($e->getMessage());
                continue;
            }
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates and returns a Flysystem adapter instance based on the stored settings.
     *
     * @return \League\Flysystem\AdapterInterface The Flysystem adapter.
     */
    abstract protected function createAdapter();

    /**
     * Returns the Flysystem adapter instance.
     *
     * @return \League\Flysystem\AdapterInterface The Flysystem adapter.
     */
    protected function adapter()
    {
        if ($this->_adapter !== null) {
            return $this->_adapter;
        }

        return $this->_adapter = $this->createAdapter();
    }

    /**
     * Returns Flysystem filesystem configured with the Flysystem adapter.
     *
     * @param array $config
     * @return Filesystem The Flysystem filesystem.
     */
    protected function filesystem(array $config = []): Filesystem
    {
        // Constructing a Filesystem is super cheap and we always get the config we want, so no caching.
        return new Filesystem($this->adapter(), new Config($config));
    }

    /**
     * Adds file metadata to the config array.
     *
     * @param array $config
     * @return array
     */
    protected function addFileMetadataToConfig(array $config): array
    {
        $config = array_merge($config, [
            'visibility' => $this->visibility()
        ]);

        return $config;
    }

    /**
     * Invalidate a CDN path on the Volume.
     *
     * @param string $path the path to invalidate
     * @return bool
     */
    protected function invalidateCdnPath(string $path): bool
    {
        return true;
    }

    /**
     * Returns the visibility setting for the Volume.
     *
     * @return string
     */
    protected function visibility(): string
    {
        return $this->hasUrls ? AdapterInterface::VISIBILITY_PUBLIC : AdapterInterface::VISIBILITY_PRIVATE;
    }
}
