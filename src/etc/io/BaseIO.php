<?php
namespace Craft;

/**
 * Class BaseIO
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.io
 * @since     1.0
 */
abstract class BaseIO
{
	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * @var string
	 */
	private $_realPath;

	/**
	 * @var bool
	 */
	private $_isReadable;

	/**
	 * @var bool
	 */
	private $_isWritable;

	/**
	 * @var string
	 */
	private $_fullFolderName;

	/**
	 * @var string
	 */
	private $_folderNameOnly;

	/**
	 * @var
	 */
	private $_lastTimeModified;

	/**
	 * @var
	 */
	private $_owner;

	/**
	 * @var
	 */
	private $_group;

	/**
	 * @var
	 */
	private $_permissions;

	// Public Methods
	// =========================================================================

	/**
	 * @return mixed
	 */
	public function getRealPath()
	{
		if (!$this->_realPath)
		{
			$this->_realPath = IOHelper::getRealPath($this->path);
		}

		return $this->_realPath;
	}

	/**
	 * @return mixed
	 */
	public function isReadable()
	{
		if (!$this->_isReadable)
		{
			$this->_isReadable = IOHelper::isReadable($this->getRealPath());
		}

		return $this->_isReadable;
	}

	/**
	 * @return mixed
	 */
	public function isWritable()
	{
		if (!$this->_isWritable)
		{
			$this->_isWritable = IOHelper::isWritable($this->getRealPath());
		}

		return $this->_isWritable;
	}

	/**
	 * @return mixed
	 */
	public function getOwner()
	{
		if (!$this->_owner)
		{
			$this->_owner = IOHelper::getOwner($this->getRealPath());
		}

		return $this->_owner;
	}

	/**
	 * @return mixed
	 */
	public function getGroup()
	{
		if (!$this->_group)
		{
			$this->_group = IOHelper::getGroup($this->getRealPath());
		}

		return $this->_group;
	}

	/**
	 * @return mixed
	 */
	public function getPermissions()
	{
		if (!$this->_permissions)
		{
			$this->_permissions = IOHelper::getPermissions($this->getRealPath());
		}

		return $this->_permissions;
	}

	/**
	 * @return mixed
	 */
	public function getLastTimeModified()
	{
		if (!$this->_lastTimeModified)
		{
			$this->_lastTimeModified = IOHelper::getLastTimeModified($this->getRealPath());
		}

		return $this->_lastTimeModified;
	}

	/**
	 * @param $owner
	 *
	 * @return bool
	 */
	public function changeOwner($owner)
	{
		if (!IOHelper::changeOwner($this->getRealPath(), $owner))
		{
			return false;
		}

		return true;
	}

	/**
	 * @param $group
	 *
	 * @return bool
	 */
	public function changeGroup($group)
	{
		if (!IOHelper::changeGroup($this->getRealPath(), $group))
		{
			return false;
		}

		return true;
	}

	/**
	 * @param $permissions
	 *
	 * @return bool
	 */
	public function changePermissions($permissions)
	{
		if (!IOHelper::changePermissions($this->getRealPath(), $permissions))
		{
			return false;
		}

		return true;
	}

	/**
	 * @param string $newName
	 *
	 * @return bool
	 */
	public function rename($newName)
	{
		if (!IOHelper::rename($this->getRealPath(), $newName))
		{
			return false;
		}

		return true;
	}

	/**
	 * @param string $newPath
	 *
	 * @return bool
	 */
	public function move($newPath)
	{
		if (!IOHelper::move($this->getRealPath(), $newPath))
		{
			return false;
		}

		return true;
	}

	/**
	 * @param bool $fullPath
	 *
	 * @return string
	 */
	public function getFolderName($fullPath = true)
	{
		if ($fullPath)
		{
			if (!$this->_fullFolderName)
			{
				$this->_fullFolderName = IOHelper::getFolderName($this->getRealPath(), $fullPath);
			}

			return $this->_fullFolderName;
		}
		else
		{
			if (!$this->_folderNameOnly)
			{
				$this->_folderNameOnly = IOHelper::getFolderName($this->getRealPath(), $fullPath);
			}

			return $this->_folderNameOnly;
		}
	}
}
