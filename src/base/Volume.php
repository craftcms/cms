<?php
/**
 * The base class for all asset source types.  Any asset source type must extend this class.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright  Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license    http://buildwithcraft.com/license Craft License Agreement
 * @see        http://buildwithcraft.com
 * @package    craft.app.base
 * @since      1.0
 */

namespace craft\app\base;

use Craft;
use craft\app\errors\VolumeObjectExistsException;
use craft\app\errors\VolumeObjectNotFoundException;
use craft\app\errors\VolumeFolderExistsException;
use craft\app\helpers\Io;
use League\Flysystem\AdapterInterface;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use League\Flysystem\RootViolationException;

abstract class Volume extends SavableComponent implements VolumeInterface
{
    // Traits
    // =========================================================================

    use VolumeTrait;

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
                'class' => 'craft\app\behaviors\FieldLayoutBehavior',
                'elementType' => 'craft\app\elements\Asset'
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
        $rules = parent::rules();
        $rules[] = [
            ['id'],
            'number',
            'min' => -2147483648,
            'max' => 2147483647,
            'integerOnly' => true
        ];
        $rules[] = [
            ['handle'],
            'craft\\app\\validators\\Handle',
            'reservedWords' => [
                'id',
                'dateCreated',
                'dateUpdated',
                'uid',
                'title'
            ]
        ];
        $rules[] = [
            ['fieldLayoutId'],
            'number',
            'min' => -2147483648,
            'max' => 2147483647,
            'integerOnly' => true
        ];
        $rules[] = [['handle'], 'string', 'max' => 255];
        $rules[] = [
            [
                'id',
                'type',
                'settings',
                'name',
                'handle',
                'sortOrder',
                'fieldLayoutId',
                'url'
            ],
            'safe',
            'on' => 'search'
        ];
        $rules[] = [['name', 'handle', 'url'], 'required'];

        return $rules;
    }

    // Public Methods
    // =========================================================================

    public function getFileList($directory)
    {
        return $this->getFilesystem()->listContents($directory, true);
    }

    /**
     * @inheritdoc
     */
    public function createFileByStream($path, $stream)
    {
        try {
            return $this->getFilesystem()->writeStream($path, $stream,
                [
                    'visibility' => AdapterInterface::VISIBILITY_PUBLIC
                ]
            );
        } catch (FileExistsException $exception) {
            throw new VolumeObjectExistsException($exception->getMessage());
        }
    }

    /**
     * @inheritdoc
     */
    public function updateFileByStream($path, $stream)
    {
        return $this->getFilesystem()->updateStream($path, $stream);
    }

    /**
     * @inheritdoc
     */
    public function createOrUpdateFile($path, $contents)
    {
        return $this->getFilesystem()->put($path, $contents);
    }

    /**
     * @inheritdoc
     */
    public function getFileContents($path)
    {
        return $this->getFilesystem()->read($path);
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
        return $this->getFilesystem()->has($path);
    }

    /**
     * @inheritdoc
     */
    public function deleteFile($path)
    {
        try {
            return $this->getFilesystem()->delete($path);
        } catch (FileNotFoundException $exception) {
            Craft::info($exception->getMessage());

            // No file - no problem.
            return true;
        }
    }

    /**
     * @inheritdoc
     */
    public function getContentsAndDeleteFile($path)
    {
        return $this->getFilesystem()->readAndDelete($path);
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
                array('path' => $path)));
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
    public function getMimeType($path)
    {
        return $this->getFilesystem()->getMimetype($path);
    }

    /**
     * @inheritdoc
     */
    public function getTimestamp($path)
    {
        return $this->getAdapter()->getTimestamp($path);
    }

    /**
     * @inheritdoc
     */
    public function getSize($path)
    {
        return $this->getFilesystem()->getSize($path);
    }

    /**
     * @inheritdoc
     */
    public function createDir($path)
    {
        if ($this->getAdapter()->has(rtrim($path,
                '/').($this->foldersHaveTrailingSlashes ? '/' : ''))
        ) {
            throw new VolumeFolderExistsException(Craft::t('app',
                "Folder “{folder}” already exists on the source!",
                array('folder' => $path)));
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
        $fileList = $this->getAdapter()->listContents($path, true);

        $folders = [];

        $parts = explode("/", $path);

        array_pop($parts);
        array_push($parts, $newName);

        $newPath = join("/", $parts);

        $pattern = '/^'.preg_quote($path, '/').'/';

        foreach ($fileList as $object) {
            if ($object['type'] != 'dir') {
                $objectPath = preg_replace($pattern, $newPath, $object['path']);
                $this->renameFile($object['path'], $objectPath);
            } else {
                $folders[$object['path']] = true;
            }
        }

        foreach ($folders as $path => $value) {
            $this->deleteDir($path);
        }
    }

    /**
     * Save a file from the source's uriPath to a local target path.
     *
     * @param $uriPath
     * @param $targetPath
     *
     * @return integer $bytes amount of bytes copied
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
}
