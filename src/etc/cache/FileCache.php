<?php
namespace Craft;

/**
 * Class FileCache
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.cache
 * @since     1.0
 */
class FileCache extends \CFileCache
{
	// Properties
	// =========================================================================

	/**
	 * @var bool
	 */
	private $_gced = false;

	/**
	 * @var
	 */
	private $_originalKey;

	// Public Methods
	// =========================================================================

	/**
	 * Override so we can set a custom file cache path.
	 *
	 * @return null
	 */
	public function init()
	{
		if (!$this->cachePath)
		{
			$this->cachePath = craft()->path->getCachePath();
		}

		parent::init();
	}

	/**
	 * Stores a value identified by a key into cache. If the cache already contains such a key, the existing value and
	 * expiration time will be replaced with the new ones.
	 *
	 * @param string             $id         The key identifying the value to be cached
	 * @param mixed              $value      The value to be cached
	 * @param int                $expire     The number of seconds in which the cached value will expire. 0 means never
	 *                                       expire.
	 * @param \ICacheDependency $dependency Dependency of the cached item. If the dependency changes, the item is
	 *                                       labeled invalid.
	 *
	 * @return bool true if the value is successfully stored into cache, false otherwise.
	 */
	public function set($id, $value, $expire = null, $dependency = null)
	{
		$this->_originalKey = $id;

		return parent::set($id, $value, $expire, $dependency);
	}

	/**
	 * Stores a value identified by a key into cache if the cache does not contain this key. Nothing will be done if the
	 * cache already contains the key.
	 *
	 * @param string             $id         The key identifying the value to be cached
	 * @param mixed              $value      The value to be cached
	 * @param int                $expire     The number of seconds in which the cached value will expire. 0 means never
	 *                                       expire.
	 * @param \ICacheDependency $dependency Dependency of the cached item. If the dependency changes, the item is
	 *                                       labeled invalid.
	 *
	 * @return bool true if the value is successfully stored into cache, false otherwise.
	 */
	public function add($id, $value, $expire = null, $dependency = null)
	{
		$this->_originalKey = $id;

		return parent::add($id, $value, $expire, $dependency);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Stores a value identified by a key in cache. This is the implementation of the method declared in the parent
	 * class.
	 *
	 * @param string  $key    The key identifying the value to be cached
	 * @param string  $value  The value to be cached
	 * @param int     $expire The number of seconds in which the cached value will expire. 0 means never expire.
	 *
	 * @return bool true if the value is successfully stored into cache, false otherwise.
	 */
	protected function setValue($key, $value, $expire)
	{
		if (!$this->_gced && mt_rand(0, 1000000) < $this->getGCProbability())
		{
			$this->gc();
			$this->_gced = true;
		}

		if($expire <= 0)
		{
			$expire = 31536000; // 1 year
		}

		$expire += time();

		$cacheFile = $this->getCacheFile($key);

		if ($this->directoryLevel > 0)
		{
			IOHelper::createFolder(IOHelper::getFolderName($cacheFile));
		}

		if ($this->_originalKey == 'useWriteFileLock')
		{
			if (IOHelper::writeToFile($cacheFile, $value, true, false, true) !== false)
			{
				IOHelper::changePermissions($cacheFile, craft()->config->get('defaultFilePermissions'));
				return IOHelper::touch($cacheFile, $expire);
			}
			else
			{
				return false;
			}
		}
		else
		{
			if (IOHelper::writeToFile($cacheFile, $this->embedExpiry ? $expire.$value : $value) !== false)
			{
				IOHelper::changePermissions($cacheFile, craft()->config->get('defaultFilePermissions'));
				return $this->embedExpiry ? true : IOHelper::touch($cacheFile, $expire);
			}
			else
			{
				return false;
			}
		}
	}
}
