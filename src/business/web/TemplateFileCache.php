<?php

class TemplateFileCache extends CFileCache
{
	/**
	 * @var string the directory to store cache files. Defaults to null, meaning
	 * using 'protected/runtime/cache' as the directory.
	 */
//	public $cachePath;

	/**
	 * @var string cache file suffix. Defaults to '.php'.
	 */
//	public $cacheFileSuffix = '.php';

	/**
	 * Initializes this application component.
	 * This method is required by the {@link IApplicationComponent} interface.
	 * It checks the availability of memcache.
	 * @throws CException if APC cache extension is not loaded or is disabled.
	 */
/*	public function init()
	{
		parent::init();

		switch (Blocks::app()->mode)
		{
			case AppMode::Site:
				$this->cachePath = Blocks::app()->config->getBlocksRuntimePath().'cached'.DIRECTORY_SEPARATOR.'translated_site_templates'.DIRECTORY_SEPARATOR;
				break;

			case AppMode::CP:
				$this->cachePath = Blocks::app()->config->getBlocksRuntimePath().'cached'.DIRECTORY_SEPARATOR.'translated_cp_templates'.DIRECTORY_SEPARATOR;
				break;

			default:
				$this->cachePath = Blocks::app()->getRuntimePath().DIRECTORY_SEPARATOR.'cache';
		}

		if (!is_dir($this->cachePath))
			mkdir($this->cachePath, 0777, true);
	}*/

	/*public function add($id, $value, $expire = 0, $dependency = null)
	{
		Blocks::trace('Adding "'.$id.'" to cache','system.caching.'.get_class($this));
		return $this->addValue($this->generateUniqueKey($id), $value);
	}*/

	/**
	 * Deletes all values from cache.
	 * This is the implementation of the method declared in the parent class.
	 * @return boolean whether the flush operation was successful.
	 * @since 1.1.5
	 */
	/*protected function flushValues()
	{
		$this->gc(false);
		return true;
	}*/

	/*protected function generateUniqueKey($templatePath)
	{
		// The key for cache is the relative path of the template minus the extension.
		$templateCachePath = Blocks::app()->config->getBlocksTemplatePath();
		$templatePath = str_replace('\\', '/', $templatePath);
		$templateCachePath = str_replace('\\', '/', $templateCachePath);
		$relativePath = substr($templatePath, strlen($templateCachePath));
		return substr($relativePath, 0, strpos($relativePath, '.'));
	}
*/
	/**
	 * Retrieves a value from cache with a specified key.
	 * This is the implementation of the method declared in the parent class.
	 * @param string $key a unique key identifying the cached value
	 * @return string the value stored in cache, false if the value is not in the cache or expired.
	 */
	/*protected function getValue($key)
	{
		$cacheFile = $this->getCacheFile($key);

		if (($time = @filemtime($cacheFile)) > time())
			return file_get_contents($cacheFile);
		else if($time > 0)
			@unlink($cacheFile);
		return false;
	}*/

	/**
	 * Stores a value identified by a key in cache.
	 * This is the implementation of the method declared in the parent class.
	 *
	 * @param string $key the key identifying the value to be cached
	 * @param string $value the value to be cached
	 * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	/*protected function setValue($key, $value, $expire = null)
	{
		$cacheFile = $this->getCacheFile($key);

		@mkdir(dirname($cacheFile), 0777, true);

		if(@file_put_contents($cacheFile, $value, LOCK_EX) !== false)
		{
			@chmod($cacheFile, 0777);
			return @touch($cacheFile);
		}
		else
			return false;
	}*/

	/**
	 * Stores a value identified by a key into cache if the cache does not contain this key.
	 * This is the implementation of the method declared in the parent class.
	 *
	 * @param string $key the key identifying the value to be cached
	 * @param string $value the value to be cached
	 * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	/*protected function addValue($key, $value, $expire = null)
	{
		$cacheFile = $this->getCacheFile($key);

		if(@filemtime($cacheFile) > time())
			return false;

		return $this->setValue($key, $value);
	}*/

	/**
	 * Deletes a value with the specified key from cache
	 * This is the implementation of the method declared in the parent class.
	 * @param string $key the key of the value to be deleted
	 * @return boolean if no error happens during deletion
	 */
	/*protected function deleteValue($key)
	{
		$cacheFile = $this->getCacheFile($key);
		return @unlink($cacheFile);
	}*/

	/**
	 * Returns the cache file path given the cache key.
	 * @param string $key cache key
	 * @return string the cache file path
	 */
	/*protected function getCacheFile($key)
	{
		return $this->cachePath.$key.$this->cacheFileSuffix;
	}

	public function get($id)
	{
		if (($value = $this->getValue($this->generateUniqueKey($id))) !== false)
		{
			Blocks::trace('Serving "'.$id.'" from cache','system.caching.'.get_class($this));
			return $value;
		}

		return false;
	}

	public function getTemplateCachePath()
	{
		return $this->cachePath;
	}*/
}
