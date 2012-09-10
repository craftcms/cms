<?php
namespace Blocks;

/**
 *
 */
class File
{
	private $_filePath;
	private $_realPath;
	private $_isReadable;
	private $_isWritable;
	private $_baseName;
	private $_fileName;
	private $_extension;
	private $_fullDirName;
	private $_dirNameOnly;
	private $_mimeType;
	private $_lastTimeModified;
	private $_size;
	private $_isEmpty;
	private $_owner;
	private $_group;
	private $_permissions;
	private $_contents;
	private $_md5;

	/**
	 * @param $filePath
	 */
	public function __construct($filePath)
	{
		clearstatcache();

		if (IOHelper::fileExists($filePath))
			$this->_filePath;

		return false;
	}

	public function getRealPath()
	{
		if (!$this->_realPath)
			$this->_realPath = IOHelper::getRealPath($this->_filePath);

		return $this->_realPath;
	}

	public function isReadable()
	{
		if (!$this->_isReadable)
			$this->_isReadable = IOHelper::isReadable($this->getRealPath());

		return $this->_isReadable;
	}

	public function isWritable()
	{
		if (!$this->_isWritable)
			$this->_isWritable = IOHelper::isWritable($this->getRealPath());

		return $this->_isWritable;
	}

	public function getFileName($includeExtension = true)
	{
		if ($includeExtension)
		{
			if (!$this->_fileName)
				$this->_fileName = IOHelper::getFileName($this->getRealPath(), $includeExtension);

			return $this->_fileName;
		}
		else
		{
			if (!$this->_baseName)
				$this->_baseName = IOHelper::getFileName($this->getRealPath(), $includeExtension);
		}
	}

	public function getExtension()
	{
		if (!$this->_extension)
			$this->_extension = IOHelper::getExtension($this->getRealPath());
	}

	public function getFolderName($fullPath = true)
	{
		if ($fullPath)
		{
			if (!$this->_fullDirName)
				$this->_fullDirName = IOHelper::getFolderName($this->getRealPath(), $fullPath);

			return $this->_fullDirName;
		}
		else
		{
			if (!$this->_dirNameOnly)
				$this->_dirNameOnly = IOHelper::getFolderName($this->getRealPath(), $fullPath);

			return $this->_dirNameOnly;
		}
	}

	public function getMimeType()
	{
		if (!$this->_mimeType)
			$this->_mimeType = IOHelper::getMimeType($this->getRealPath());

		return $this->_mimeType;
	}

	public function getLastTimeModified()
	{
		if (!$this->_lastTimeModified)
			$this->_lastTimeModified = IOHelper::getLastTimeModified($this->getRealPath());

		return $this->_lastTimeModified;
	}

	public function getSize()
	{
		if (!$this->_size)
			$this->_size = IOHelper::getFileSize($this->getRealPath());

		return $this->_size;
	}

	public function isEmpty()
	{
		if (!$this->_isEmpty)
			$this->_isEmpty = IOHelper::isFileEmpty($this->getRealPath());

		return $this->_isEmpty;
	}

	public function getOwner()
	{
		if (!$this->_owner)
			$this->_owner = IOHelper::getOwner($this->getRealPath());

		return $this->_owner;
	}

	public function getGroup()
	{
		if (!$this->_group)
			$this->_group = IOHelper::getGroup($this->getRealPath());

		return $this->_group;
	}

	public function getPermissions()
	{
		if (!$this->_permissions)
			$this->_permissions = IOHelper::getPermissions($this->getRealPath());

		return $this->_permissions;
	}

	public function getContents()
	{
		if (!$this->_contents)
			$this->_contents = IOHelper::getFileContents($this->getRealPath());

		return $this->_contents;
	}

	public function write($contents)
	{
		if (!IOHelper::writeToFile($this->getRealPath(), $contents, false))
			return false;

		return true;
	}

	public function changeOwner($owner)
	{
		if (!IOHelper::changeOwner($this->getRealPath(), $owner))
			return false;

		return true;
	}

	public function changeGroup($group)
	{
		if (!IOHelper::changeGroup($this->getRealPath(), $group))
			return false;

		return true;
	}

	public function changePermissions($permissions)
	{
		if (!IOHelper::changePermissions($this->getRealPath(), $permissions))
			return false;

		return true;
	}

	public function copy($destination)
	{
		if (!IOHelper::copyFile($this->getRealPath(), $destination))
			return false;

		return true;
	}

	public function rename($newName)
	{
		if (!IOHelper::rename($this->getRealPath(), $newName))
			return false;

		return true;
	}

	public function move($newPath)
	{
		if (!IOHelper::move($this->getRealpath(), $newPath))
			return false;

		return true;
	}

	public function clear()
	{
		if (!IOHelper::clearFile($this->getRealPath()))
			return false;

		return true;
	}

	public function delete()
	{
		if (!IOHelper::deleteFile($this->getRealPath()))
			return false;

		return true;
	}

	public function getMD5()
	{
		if (!$this->_md5)
			$this->_md5 = IOHelper::getMD5($this->getRealPath());

		return $this->_md5;
	}
}
