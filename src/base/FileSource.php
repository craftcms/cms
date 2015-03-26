<?php
namespace craft\app\base;

use Craft;
use craft\app\errors\VolumeFileExistsException;
use craft\app\errors\VolumeFolderExistsException;
use \League\Flysystem\AdapterInterface;
use \League\Flysystem\Filesystem;
use \League\Flysystem\FileExistsException;
use \League\Flysystem\FileNotFoundException;

/**
 * Flysystem adapter-based file source type base class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.filesourcetypes
 * @since     3.0
 */
abstract class FileSource  extends SavableComponent implements FileSourceInterface
{
	// Properties
	// =========================================================================

	/**
	 * The Flysystem adapter, created by {@link createAdapter()}.
	 *
	 * @var AdapterInterface
	 */
	private $_adapter;

	/**
	 * The Flysystem filesystem.
	 *
	 * @var Filesystem
	 */
	private $_filesystem;

	/**
	 * Set to true if the Adapter expects folder names to have trailing slashes
	 *
	 * @var bool
	 */
	 protected $foldersHaveTrailingSlashes = true;

	// Public Methods
	// =========================================================================

	public function getFileList($directory)
	{
		return $this->getFilesystem()->listContents($directory, true);
	}

	/**
	 * @inheritDoc IFileSourceType::createFile()
	 */
	public function createFile($path, $stream)
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
	 * @inheritDoc IFileSourceType::updateFile()
	 */
	public function updateFile($path, $contents)
	{
		return $this->getFilesystem()->update($path, $contents);
	}

	/**
	 * @inheritDoc IFileSourceType::createOrUpdateFile()
	 */
	public function createOrUpdateFile($path, $contents)
	{
		return $this->getFilesystem()->put($path, $contents);
	}

	/**
	 * @inheritDoc IFileSourceType::getFileContents()
	 */
	public function getFileContents($path)
	{
		return $this->getFilesystem()->read($path);
	}

	/**
	 * @inheritDoc IFileSourceType::fileExists()
	 */
	public function fileExists($path)
	{
		return $this->getFilesystem()->has($path);
	}

	/**
	 * @inheritDoc IFileSourceType::deleteFile()
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
	 * @inheritDoc IFileSourceType::getContentsAndDeleteFile()
	 */
	public function getContentsAndDeleteFile($path)
	{
		return $this->getFilesystem()->readAndDelete($path);
	}

	/**
	 * @inheritDoc IFileSourceType::renameFile()
	 */
	public function renameFile($path, $newPath)
	{
		return $this->getFilesystem()->rename($path, $newPath);
	}

	/**
	 * @inheritDoc IFileSourceType::copyFile()
	 */
	public function copyFile($path, $newPath)
	{
		return $this->getFilesystem()->copy($path, $newPath);
	}

	/**
	 * @inheritDoc IFileSourceType::getMimeType()
	 */
	public function getMimeType($path)
	{
		return $this->getFilesystem()->getMimetype($path);
	}

	/**
	 * @inheritDoc IFileSourceType::getTimestamp()
	 */
	public function getTimestamp($path)
	{
		return $this->getAdapter()->getTimestamp($path);
	}

	/**
	 * @inheritDoc IFileSourceType::getSize()
	 */
	public function getSize($path)
	{
		return $this->getFilesystem()->getSize($path);
	}

	/**
	 * @inheritDoc IFileSourceType::createDir()
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
	 * @inheritDoc IFileSourceType::deleteDir()
	 */
	public function deleteDir($path)
	{
		try
		{
			return $this->getFilesystem()->deleteDir($path);
		}
		catch (\League\Flysystem\RootViolationException $exception)
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
			$this->_filesystem = new \League\Flysystem\Filesystem($this->getAdapter());
		}

		return $this->_filesystem;
	}
}
