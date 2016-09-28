<?php
/**
 * The base class for all asset Volumes.  Any Volume type must extend this class.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright  Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license    http://craftcms.com/license Craft License Agreement
 * @see        http://craftcms.com
 * @package    craft.app.base
 * @since      1.0
 */

namespace craft\app\base;

use Craft;
use craft\app\behaviors\FieldLayoutTrait;
use craft\app\errors\VolumeObjectExistsException;
use craft\app\errors\VolumeObjectNotFoundException;
use craft\app\records\Volume as VolumeRecord;
use craft\app\validators\HandleValidator;
use craft\app\validators\UniqueValidator;
use League\Flysystem\AdapterInterface;
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
     * @var boolean Whether the Flysystem adapter expects folder names to have trailing slashes
     */
    protected $foldersHaveTrailingSlashes = true;

    /**
     * @var AdapterInterface The Flysystem adapter, created by [[createAdapter()]]
     */
    private $_adapter;

    /**
     * @var Filesystem The Flysystem filesystem
     */
    private $_filesystem;

    // Public Methods
    // =========================================================================

    /**
     * Returns whether this volume stores files locally on the server.
     *
     * @return boolean Whether files are stored locally.
     */
    public static function isLocal()
    {
        return false;
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'fieldLayout' => [
                'class' => \craft\app\behaviors\FieldLayoutBehavior::class,
                'elementType' => \craft\app\elements\Asset::class
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
            [['type'], 'string', 'max' => 150],
            [['name', 'handle', 'type'], 'required'],
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
    public function getFileList($directory, $recursive = true)
    {
        return $this->getFilesystem()->listContents($directory, $recursive);
    }

    /**
     * @inheritdoc
     */
    public function createFileByStream($path, $stream, $config = [])
    {
        try {
            $config = $this->addFileMetadataToConfig($config);
            return $this->getFilesystem()->writeStream($path, $stream, $config);
        } catch (FileExistsException $exception) {
            throw new VolumeObjectExistsException($exception->getMessage());
        }
    }

    /**
     * @inheritdoc
     */
    public function updateFileByStream($path, $stream, $config = [])
    {
        try {
            $config = $this->addFileMetadataToConfig($config);
            return $this->getFilesystem()->updateStream($path, $stream, $config);
        } catch (FileNotFoundException $exception) {
            throw new VolumeObjectNotFoundException($exception->getMessage());
        }
    }

    /**
     * @inheritdoc
     */
    public function fileExists($path)
    {
        return $this->getFilesystem()->has($path);
    }

    /**
     * @inheritdoc
     */
    public function folderExists($path)
    {
        return $this->getAdapter()->has(rtrim($path,
                '/').($this->foldersHaveTrailingSlashes ? '/' : ''));
    }

    /**
     * @inheritdoc
     */
    public function deleteFile($path)
    {
        try {
            $result = $this->getFilesystem()->delete($path);
        } catch (FileNotFoundException $exception) {
            // Make a note of it, but otherwise - mission accomplished!
            Craft::info($exception->getMessage());
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
    public function renameFile($path, $newPath)
    {
        try {
            return $this->getFilesystem()->rename($path, $newPath);
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
    public function copyFile($path, $newPath)
    {
        return $this->getFilesystem()->copy($path, $newPath);
    }

    /**
     * @inheritdoc
     */
    public function createDir($path)
    {
        if ($this->folderExists($path)) {
            throw new VolumeObjectExistsException(Craft::t('app',
                "Folder “{folder}” already exists on the volume!",
                ['folder' => $path]));
        }

        return $this->getFilesystem()->createDir($path);
    }

    /**
     * @inheritdoc
     */
    public function deleteDir($path)
    {
        try {
            return $this->getFilesystem()->deleteDir($path);
        } catch (\Exception $exception) {
            // We catch all Exceptions because most of the times these will be 3rd party exceptions.
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function renameDir($path, $newName)
    {
        if (!$this->folderExists($path)) {
            throw new VolumeObjectNotFoundException(Craft::t('app',
                "Folder “{folder}” cannot be found on the volume.",
                ['folder' => $path]));
        }

        // Get the list of dir contents
        $fileList = $this->getFileList($path);
        $directoryList = [];

        $parts = explode("/", $path);

        array_pop($parts);
        array_push($parts, $newName);

        $newPath = join("/", $parts);

        $pattern = '/^'.preg_quote($path, '/').'/';

        // Rename every file and build a list of directories
        foreach ($fileList as $object) {
            if ($object['type'] != 'dir') {
                $objectPath = preg_replace($pattern, $newPath, $object['path']);
                $this->renameFile($object['path'], $objectPath);
            } else {
                $directoryList[] = $object['path'];
            }
        }

        // The files are moved, but the directories remain. Delete them.
        foreach ($directoryList as $path) {
            $this->deleteDir($path);
        }
    }

    /**
     * @inheritdoc
     */
    public function saveFileLocally($uriPath, $targetPath)
    {
        $stream = $this->getFilesystem()->readStream($uriPath);
        $outputStream = fopen($targetPath, 'wb');

        rewind($stream);
        $bytes = stream_copy_to_stream($stream, $outputStream);

        fclose($stream);
        fclose($outputStream);

        return $bytes;
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
    protected function getAdapter()
    {
        if (!isset($this->_adapter)) {
            $this->_adapter = $this->createAdapter();
        }

        return $this->_adapter;
    }

    /**
     * Returns the Flysystem adapter instance.
     *
     * @return \League\Flysystem\Filesystem The Flysystem filesystem.
     */
    protected function getFilesystem()
    {
        if (!isset($this->_filesystem)) {
            $this->_filesystem = new Filesystem($this->getAdapter());
        }

        return $this->_filesystem;
    }

    /**
     * Adds file metadata to the config array.
     *
     * @param array $config
     *
     * @return array
     */
    protected function addFileMetadataToConfig($config)
    {
        $config = array_merge($config,
            ['visibility' => $this->getVisibilitySetting()]);

        return $config;
    }

    /**
     * Invalidate a CDN path on the Volume.
     *
     * @param string $path the path to invalidate
     *
     * @return boolean
     */
    protected function invalidateCdnPath($path)
    {
        return true;
    }

    /**
     * Returns the visibility setting for the Volume.
     *
     * @return string
     */
    protected function getVisibilitySetting()
    {
        return $this->hasUrls ? AdapterInterface::VISIBILITY_PUBLIC : AdapterInterface::VISIBILITY_PRIVATE;
    }
}
