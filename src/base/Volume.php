<?php
/**
 * The base class for all asset Volumes.  All Volume types must extend this class.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since      3.0
 */

namespace craft\base;

use Craft;
use craft\behaviors\FieldLayoutBehavior;
use craft\behaviors\FieldLayoutTrait;
use craft\elements\Asset;
use craft\errors\AssetException;
use craft\errors\VolumeObjectExistsException;
use craft\errors\VolumeObjectNotFoundException;
use craft\records\Volume as VolumeRecord;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;

/**
 * Class Volume
 */
abstract class Volume extends SavableComponent implements VolumeInterface
{
    // Traits
    // =========================================================================

    use VolumeTrait;
    use FieldLayoutTrait;

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
    public function behaviors()
    {
        return [
            'fieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => Asset::class
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'url' => Craft::t('app', 'URL'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [
            [['id', 'fieldLayoutId'], 'number', 'integerOnly' => true],
            [['name', 'handle'], UniqueValidator::class, 'targetClass' => VolumeRecord::class],
            [['hasUrls'], 'boolean'],
            [['name', 'handle', 'url'], 'string', 'max' => 255],
            [['name', 'handle'], 'required'],
            [
                ['handle'],
                HandleValidator::class,
                'reservedWords' => [
                    'id',
                    'dateCreated',
                    'dateUpdated',
                    'uid',
                    'title'
                ]
            ],
        ];

        // Require URLs for public Volumes.
        if ($this->hasUrls) {
            $rules[] = [['url'], 'required'];
        }

        return $rules;
    }

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
        } catch (FileNotFoundException $exception) {
            throw new VolumeObjectNotFoundException($exception->getMessage());
        }
    }

    /**
     * @inheritdoc
     */
    public function createFileByStream(string $path, $stream, array $config): bool
    {
        try {
            $config = $this->addFileMetadataToConfig($config);

            return $this->filesystem()->writeStream($path, $stream, $config);
        } catch (FileExistsException $exception) {
            throw new VolumeObjectExistsException($exception->getMessage());
        }
    }

    /**
     * @inheritdoc
     */
    public function updateFileByStream(string $path, $stream, array $config): bool
    {
        try {
            $config = $this->addFileMetadataToConfig($config);

            return $this->filesystem()->updateStream($path, $stream, $config);
        } catch (FileNotFoundException $exception) {
            throw new VolumeObjectNotFoundException($exception->getMessage());
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
    public function deleteFile(string $path): bool
    {
        try {
            $result = $this->filesystem()->delete($path);
        } catch (FileNotFoundException $exception) {
            // Make a note of it, but otherwise - mission accomplished!
            Craft::info($exception->getMessage(), __METHOD__);
            $result = true;
        }

        if ($result) {
            $this->invalidateCdnPath($path);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function renameFile(string $path, string $newPath): bool
    {
        try {
            return $this->filesystem()->rename($path, $newPath);
        } catch (FileExistsException $exception) {
            throw new VolumeObjectExistsException($exception->getMessage());
        } catch (FileNotFoundException $exception) {
            throw new VolumeObjectNotFoundException(Craft::t('app',
                'File was not found while attempting to rename {path}!',
                ['path' => $path]));
        }
    }

    /**
     * @inheritdoc
     */
    public function copyFile(string $path, string $newPath): bool
    {
        return $this->filesystem()->copy($path, $newPath);
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
    public function createDir(string $path): bool
    {
        if ($this->folderExists($path)) {
            throw new VolumeObjectExistsException(Craft::t('app',
                'Folder “{folder}” already exists on the volume!',
                ['folder' => $path]));
        }

        return $this->filesystem()->createDir($path);
    }

    /**
     * @inheritdoc
     */
    public function deleteDir(string $path): bool
    {
        try {
            return $this->filesystem()->deleteDir($path);
        } catch (\Exception $exception) {
            // We catch all Exceptions because most of the times these will be 3rd party exceptions.
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function renameDir(string $path, string $newName): bool
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
            throw new VolumeObjectNotFoundException(Craft::t('app',
                'Folder “{folder}” cannot be found on the volume.',
                ['folder' => $path]));
        }

        // The files are moved, but the directories remain. Delete them.
        foreach ($directoryList as $dir) {
            $this->deleteDir($dir);
        }

        return true;
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
     * Returns the Flysystem adapter instance.
     *
     * @param array $config
     *
     * @return \League\Flysystem\Filesystem The Flysystem filesystem.
     */
    protected function filesystem(array $config = [])
    {
        // Constructing a Filesystem is super cheap and we always get the config we want, so no caching.
        return new Filesystem($this->adapter(), new Config($config));
    }

    /**
     * Adds file metadata to the config array.
     *
     * @param array $config
     *
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
     *
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
