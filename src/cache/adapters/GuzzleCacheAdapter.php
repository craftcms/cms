<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\cache\adapters;

use Craft;
use \Guzzle\Cache\CacheAdapterInterface;

/**
 * GuzzleCacheAdapter implements the Guzzle CacheAdapterInterface.
 *
 * The adapter allows Craft cache mechanism to be used where
 * GuzzleCacheAdapterInterface is expected.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.cache.adapters
 * @since     3.0
 */
class GuzzleCacheAdapter implements CacheAdapterInterface
{

	/**
	 * Test if an entry exists in the cache.
	 *
	 * @param string $id cache id The cache id of the entry to check for.
	 * @param array $options Array of cache adapter options
	 *
	 * @return bool Returns TRUE if a cache entry exists for the given cache id, FALSE otherwise.
	 */
	public function contains($id, array $options = null)
	{
		return Craft::$app->cache->exists($id);
	}

	/**
	 * Deletes a cache entry.
	 *
	 * @param string $id cache id
	 * @param array $options Array of cache adapter options
	 *
	 * @return bool TRUE on success, FALSE on failure
	 */
	public function delete($id, array $options = null)
	{
		return Craft::$app->cache->delete($id);
	}

	/**
	 * Fetches an entry from the cache.
	 *
	 * @param string $id cache id The id of the cache entry to fetch.
	 * @param array $options Array of cache adapter options
	 *
	 * @return string The cached data or FALSE, if no cache entry exists for the given id.
	 */
	public function fetch($id, array $options = null)
	{
		return Craft::$app->cache->get($id);
	}

	/**
	 * Puts data into the cache.
	 *
	 * @param string $id The cache id
	 * @param string $data The cache entry/data
	 * @param int|bool $lifeTime The lifetime. If != false, sets a specific lifetime for this cache entry
	 * @param array $options Array of cache adapter options
	 *
	 * @return bool TRUE if the entry was successfully stored in the cache, FALSE otherwise.
	 */
	public function save($id, $data, $lifeTime = false, array $options = null)
	{
		return Craft::$app->cache->set($id, $data);
	}
}
