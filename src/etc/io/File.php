<?php
namespace Craft;

/**
 * Class File
 *
 * @package craft.app.etc.io
 */
class File extends BaseIO
{
	private $_baseName;
	private $_fileName;
	private $_extension;
	private $_mimeType;
	private $_size;
	private $_isEmpty;
	private $_arrayContents;
	private $_stringContents;
	private $_md5;

	/**
	 * @param $path
	 */
	public function __construct($path)
	{
		clearstatcache();
		$this->path = $path;
	}

	/**
	 * @param bool $includeExtension
	 * @return mixed
	 */
	public function getFileName($includeExtension = true)
	{
		if ($includeExtension)
		{
			if (!$this->_fileName)
			{
				$this->_fileName = IOHelper::getFileName($this->getRealPath(), $includeExtension);
			}

			return $this->_fileName;
		}
		else
		{
			if (!$this->_baseName)
			{
				$this->_baseName = IOHelper::getFileName($this->getRealPath(), $includeExtension);
			}

			return $this->_baseName;
		}
	}

	/**
	 * @return mixed
	 */
	public function getExtension()
	{
		if (!$this->_extension)
		{
			$this->_extension = IOHelper::getExtension($this->getRealPath());
		}

		return $this->_extension;
	}

	/**
	 * @return mixed
	 */
	public function getMimeType()
	{
		if (!$this->_mimeType)
		{
			$this->_mimeType = IOHelper::getMimeType($this->getRealPath());
		}

		return $this->_mimeType;
	}

	/**
	 * @return mixed
	 */
	public function getSize()
	{
		if (!$this->_size)
		{
			$this->_size = IOHelper::getFileSize($this->getRealPath());
		}

		return $this->_size;
	}

	/**
	 * @return mixed
	 */
	public function isEmpty()
	{
		if (!$this->_isEmpty)
		{
			$this->_isEmpty = IOHelper::isFileEmpty($this->getRealPath());
		}

		return $this->_isEmpty;
	}

	/**
	 * @param bool $array
	 * @return mixed
	 */
	public function getContents($array = false)
	{
		if ($array)
		{
			if (!$this->_arrayContents)
			{
				$this->_arrayContents = IOHelper::getFileContents($this->getRealPath(), $array);
			}

			return $this->_arrayContents;
		}
		else
		{
			if (!$this->_stringContents)
			{
				$this->_stringContents = IOHelper::getFileContents($this->getRealPath(), $array);
			}

			return $this->_stringContents;
		}
	}

	/**
	 * @param $contents
	 * @param $append
	 * @return bool
	 */
	public function write($contents, $append)
	{
		if (!IOHelper::writeToFile($this->getRealPath(), $contents, false, $append))
		{
			return false;
		}

		return true;
	}

	/**
	 * @param $destination
	 * @return bool
	 */
	public function copy($destination)
	{
		if (!IOHelper::copyFile($this->getRealPath(), $destination))
		{
			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public function clear()
	{
		if (!IOHelper::clearFile($this->getRealPath()))
		{
			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public function delete()
	{
		if (!IOHelper::deleteFile($this->getRealPath()))
		{
			return false;
		}

		return true;
	}

	/**
	 * @return mixed
	 */
	public function getMD5()
	{
		if (!$this->_md5)
		{
			$this->_md5 = IOHelper::getFileMD5($this->getRealPath());
		}

		return $this->_md5;
	}

	/**
	 * @return bool
	 */
	public function touch()
	{
		if (!IOHelper::touch($this->getRealPath()))
		{
			return false;
		}

		return true;
	}
}
