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
use craft\app\errors\VolumeFileExistsException;
use craft\app\errors\VolumeFileNotFoundException;
use craft\app\errors\VolumeFolderExistsException;
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
	use FieldLayoutTrait;

	// Public Methods
	// =========================================================================

	/**
	 * Returns whether this source stores files locally on the server.
	 *
	 * @return bool Whether files are stored locally.
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
		$rules[] = [['id'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true];
		$rules[] = [['handle'], 'craft\\app\\validators\\Handle', 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']];
		$rules[] = [['fieldLayoutId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true];
		$rules[] = [['handle'], 'string', 'max' => 255];
		$rules[] = [['id', 'type', 'settings', 'name', 'handle', 'sortOrder', 'fieldLayoutId', 'url'], 'safe', 'on' => 'search'];
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
		try
		{
			return $this->getFilesystem()->writeStream($path, $stream, array
				(
					'visibility' => AdapterInterface::VISIBILITY_PUBLIC
				)
			);
		}
		catch (FileExistsException $exception)
		{
			throw new VolumeFileExistsException($exception->getMessage());
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
	public function deleteFile($path)
	{
		try
		{
			return $this->getFilesystem()->delete($path);
		}
		catch (FileNotFoundException $exception)
		{
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
		try
		{
			return $this->getFilesystem()->rename($path, $newPath);
		}
		catch (FileExistsException $exception)
		{
			throw new VolumeFileExistsException($exception->getMessage());
		}
		catch (FileNotFoundException $exception)
		{
			throw new VolumeFileNotFoundException(Craft::t('app', 'File was not found while attempting to rename {path}!', array('path' => $path)));
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
		if ($this->getAdapter()->has(rtrim($path, '/') . ($this->foldersHaveTrailingSlashes ? '/' : '')))
		{
			throw new VolumeFolderExistsException(Craft::t("Folder “{folder}” already exists on the source!", array('folder' => $path)));
		}

		return $this->getFilesystem()->createDir($path);
	}

	/**
	 * @inheritdoc
	 */
	public function deleteDir($path)
	{
		try
		{
			return $this->getFilesystem()->deleteDir($path);
		}
		catch (RootViolationException $exception)
		{
			return false;
		}
	}

	/**
	 * Save a file from the source's uriPath to a local target path.
	 *
	 * @param $uriPath
	 * @param $targetPath
	 *
	 * @return int $bytes amount of bytes copied
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
		if (!isset($this->_adapter))
		{
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
		if (!isset($this->_filesystem))
		{
			$this->_filesystem = new Filesystem($this->getAdapter());
		}

		return $this->_filesystem;
	}
}
