<?php
namespace Craft;

/**
 * DbCache implements a cache application component by storing cached data in a database.
 *
 * DbCache stores cache data in a DB table named {@link cacheTableName}.
 * If the table does not exist, it will be automatically created.
 *
 * DbCache relies on {@link http://www.php.net/manual/en/ref.pdo.php PDO} to access database.
 * By default, it will use the database connection information stored in your craft/config/db.php file.
 */
class DbCache extends \CDbCache
{
	/**
	 * Stores a value identified by a key into cache.  If the cache already contains such a key, the existing value and
	 * expiration time will be replaced with the new ones.
	 *
	 * @param string $id the key identifying the value to be cached
	 * @param mixed $value the value to be cached
	 * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @param \ICacheDependency $dependency dependency of the cached item. If the dependency changes, the item is labeled invalid.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	public function set($id, $value, $expire = null, $dependency = null)
	{
		if ($expire === null)
		{
			$expire = craft()->config->getCacheDuration();
		}

		return parent::set($id, $value, $expire, $dependency);
	}

	/**
	 * Stores a value identified by a key into cache if the cache does not contain this key.  Nothing will be done if the cache already contains the key.
	 *
	 * @param string $id the key identifying the value to be cached
	 * @param mixed $value the value to be cached
	 * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @param \ICacheDependency $dependency dependency of the cached item. If the dependency changes, the item is labeled invalid.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	public function add($id, $value, $expire = null, $dependency = null)
	{
		if ($expire === null)
		{
			$expire = craft()->config->getCacheDuration();
		}

		return parent::add($id, $value, $expire, $dependency);
	}

	/**
	 * @return DbConnection
	 */
	public function getDbConnection()
	{
		return craft()->db;
	}
}
