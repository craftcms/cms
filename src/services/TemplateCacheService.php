<?php
namespace Craft;

/**
 * Class TemplateCacheService
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.services
 * @since     2.0
 */
class TemplateCacheService extends BaseApplicationComponent
{
	// Properties
	// =========================================================================

	/**
	 * The table that template caches are stored in.
	 *
	 * @var string
	 */
	private static $_templateCachesTable = 'templatecaches';

	/**
	 * The table that template cache-element relations are stored in.
	 *
	 * @var string
	 */
	private static $_templateCacheElementsTable = 'templatecacheelements';

	/**
	 * The table that queries used within template caches are stored in.
	 *
	 * @var string
	 */
	private static $_templateCacheCriteriaTable = 'templatecachecriteria';

	/**
	 * The duration (in seconds) between the times when Craft will delete any expired template caches.
	 *
	 * @var int
	 */
	private static $_lastCleanupDateCacheDuration = 86400;

	/**
	 * The current request's path, as it will be stored in the templatecaches table.
	 *
	 * @var string
	 */
	private $_path;

	/**
	 * A list of queries (and their criteria attributes) that are active within the existing caches.
	 *
	 * @var array
	 */
	private $_cacheCriteria;

	/**
	 * A list of element IDs that are active within the existing caches.
	 *
	 * @var array
	 */
	private $_cacheElementIds;

	/**
	 * Whether expired caches have already been deleted in this request.
	 *
	 * @var bool
	 */
	private $_deletedExpiredCaches = false;

	/**
	 * Whether all caches have been deleted in this request.
	 *
	 * @var bool
	 */
	private $_deletedAllCaches = false;

	/**
	 * Whether all caches have been deleted, on a per-element type basis, in this request.
	 *
	 * @var bool
	 */
	private $_deletedCachesByElementType;

	// Public Methods
	// =========================================================================

	/**
	 * Returns a cached template by its key.
	 *
	 * @param string $key    The template cache key
	 * @param bool   $global Whether the cache would have been stored globally.
	 *
	 * @return string|null
	 */
	public function getTemplateCache($key, $global)
	{
		// Make sure template caching is enabled.
		if (!$this->_isTemplateCachingEnabled())
		{
			return;
		}

		// Take the opportunity to delete any expired caches
		$this->deleteExpiredCachesIfOverdue();

		$conditions = array('and', 'expiryDate > :now', 'cacheKey = :key', 'locale = :locale');

		$params = array(
			':now'    => DateTimeHelper::currentTimeForDb(),
			':key'    => $key,
			':locale' => craft()->language
		);

		if (!$global)
		{
			$conditions[] = 'path = :path';
			$params[':path'] = $this->_getPath();
		}

		$cachedBody = craft()->db->createCommand()
			->select('body')
			->from(static::$_templateCachesTable)
			->where($conditions, $params)
			->queryScalar();

		return ($cachedBody !== false ? $cachedBody : null);
	}

	/**
	 * Starts a new template cache.
	 *
	 * @param string $key The template cache key.
	 *
	 * @return null
	 */
	public function startTemplateCache($key)
	{
		// Make sure template caching is enabled.
		if (!$this->_isTemplateCachingEnabled())
		{
			return;
		}

		if (craft()->config->get('cacheElementQueries'))
		{
			$this->_cacheCriteria[$key] = array();
		}

		$this->_cacheElementIds[$key] = array();
	}

	/**
	 * Includes an element criteria in any active caches.
	 *
	 * @param ElementCriteriaModel $criteria The element criteria.
	 *
	 * @return null
	 */
	public function includeCriteriaInTemplateCaches(ElementCriteriaModel $criteria)
	{
		// Make sure template caching is enabled.
		if (!$this->_isTemplateCachingEnabled())
		{
			return;
		}

		if (!empty($this->_cacheCriteria))
		{
			$criteriaHash = spl_object_hash($criteria);

			foreach (array_keys($this->_cacheCriteria) as $cacheKey)
			{
				$this->_cacheCriteria[$cacheKey][$criteriaHash] = $criteria;
			}
		}
	}

	/**
	 * Includes an element in any active caches.
	 *
	 * @param int $elementId The element ID.
	 *
	 * @return null
	 */
	public function includeElementInTemplateCaches($elementId)
	{
		// Make sure template caching is enabled.
		if (!$this->_isTemplateCachingEnabled())
		{
			return;
		}

		if (!empty($this->_cacheElementIds))
		{
			foreach (array_keys($this->_cacheElementIds) as $cacheKey)
			{
				if (array_search($elementId, $this->_cacheElementIds[$cacheKey]) === false)
				{
					$this->_cacheElementIds[$cacheKey][] = $elementId;
				}
			}
		}
	}

	/**
	 * Ends a template cache.
	 *
	 * @param string      $key        The template cache key.
	 * @param bool        $global     Whether the cache should be stored globally.
	 * @param string|null $duration   How long the cache should be stored for.
	 * @param mixed|null  $expiration When the cache should expire.
	 * @param string      $body       The contents of the cache.
	 *
	 * @throws \Exception
	 * @return null
	 */
	public function endTemplateCache($key, $global, $duration, $expiration, $body)
	{
		// Make sure template caching is enabled.
		if (!$this->_isTemplateCachingEnabled())
		{
			return;
		}

		// If there are any transform generation URLs in the body, don't cache it.
		// stripslashes($body) in case the URL has been JS-encoded or something.
		// Can't use getResourceUrl() here because that will append ?d= or ?x= to the URL.
		if (strpos(stripslashes($body), UrlHelper::getSiteUrl(craft()->config->getResourceTrigger().'/transforms')))
		{
			return;
		}

		// Encode any 4-byte UTF-8 characters
		$body = StringHelper::encodeMb4($body);

		// Figure out the expiration date
		if ($duration)
		{
			$expiration = new DateTime($duration);
		}

		if (!$expiration)
		{
			$duration = craft()->config->getCacheDuration();

			if($duration <= 0)
			{
				$duration = 31536000; // 1 year
			}

			$duration += time();

			$expiration = new DateTime('@'.$duration);
		}

		// Save it
		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			craft()->db->createCommand()->insert(static::$_templateCachesTable, array(
				'cacheKey'   => $key,
				'locale'     => craft()->language,
				'path'       => ($global ? null : $this->_getPath()),
				'expiryDate' => DateTimeHelper::formatTimeForDb($expiration),
				'body'       => $body
			), false);

			$cacheId = craft()->db->getLastInsertID();

			// Tag it with any element criteria that were output within the cache
			if (!empty($this->_cacheCriteria[$key]))
			{
				$values = array();

				foreach ($this->_cacheCriteria[$key] as $criteria)
				{
					$flattenedCriteria = $criteria->getAttributes(null, true);

					$values[] = array($cacheId, $criteria->getElementType()->getClassHandle(), JsonHelper::encode($flattenedCriteria));
				}

				craft()->db->createCommand()->insertAll(static::$_templateCacheCriteriaTable, array('cacheId', 'type', 'criteria'), $values, false);

				unset($this->_cacheCriteria[$key]);
			}

			// Tag it with any element IDs that were output within the cache
			if (!empty($this->_cacheElementIds[$key]))
			{
				$values = array();

				foreach ($this->_cacheElementIds[$key] as $elementId)
				{
					$values[] = array($cacheId, $elementId);
				}

				craft()->db->createCommand()->insertAll(static::$_templateCacheElementsTable, array('cacheId', 'elementId'), $values, false);

				unset($this->_cacheElementIds[$key]);
			}

			if ($transaction !== null)
			{
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}
	}

	/**
	 * Deletes a cache by its ID(s).
	 *
	 * @param int|array $cacheId The cache ID.
	 *
	 * @return bool
	 */
	public function deleteCacheById($cacheId)
	{
		if ($this->_deletedAllCaches || !$this->_isTemplateCachingEnabled())
		{
			return false;
		}

		if (is_array($cacheId))
		{
			$condition = array('in', 'id', $cacheId);
			$params = array();
		}
		else
		{
			$condition = 'id = :id';
			$params = array(':id' => $cacheId);
		}

		$affectedRows = craft()->db->createCommand()->delete(static::$_templateCachesTable, $condition, $params);
		return (bool) $affectedRows;
	}

	/**
	 * Deletes caches by a given element type.
	 *
	 * @param string $elementType The element type handle.
	 *
	 * @return bool
	 */
	public function deleteCachesByElementType($elementType)
	{
		if ($this->_deletedAllCaches || !empty($this->_deletedCachesByElementType[$elementType]) || !$this->_isTemplateCachingEnabled())
		{
			return false;
		}

		$this->_deletedCachesByElementType[$elementType] = true;

		$cacheIds = craft()->db->createCommand()
			->select('cacheId')
			->from(static::$_templateCacheCriteriaTable)
			->where(array('type' => $elementType))
			->queryColumn();

		if ($cacheIds)
		{
			craft()->db->createCommand()->delete(static::$_templateCachesTable, array('in', 'id', $cacheIds));
		}

		return true;
	}

	/**
	 * Deletes caches that include a given element(s).
	 *
	 * @param BaseElementModel|BaseElementModel[] $elements The element(s) whose caches should be deleted.
	 *
	 * @return bool
	 */
	public function deleteCachesByElement($elements)
	{
		if ($this->_deletedAllCaches || !$this->_isTemplateCachingEnabled())
		{
			return false;
		}

		if (!$elements)
		{
			return false;
		}

		if (is_array($elements))
		{
			$firstElement = ArrayHelper::getFirstValue($elements);
		}
		else
		{
			$firstElement = $elements;
			$elements = array($elements);
		}

		$deleteQueryCaches = empty($this->_deletedCachesByElementType[$firstElement->getElementType()]);
		$elementIds = array();

		foreach ($elements as $element)
		{
			$elementIds[] = $element->id;
		}

		return $this->deleteCachesByElementId($elementIds, $deleteQueryCaches);
	}

	/**
	 * Deletes caches that include an a given element ID(s).
	 *
	 * @param int|array $elementId         The ID of the element(s) whose caches should be cleared.
	 * @param bool      $deleteQueryCaches Whether a DeleteStaleTemplateCaches task should be created, deleting any
	 *                                     query caches that may now involve this element, but hadn't previously.
	 *                                     (Defaults to `true`.)
	 *
	 * @return bool
	 */
	public function deleteCachesByElementId($elementId, $deleteQueryCaches = true)
	{
		if ($this->_deletedAllCaches || !$this->_isTemplateCachingEnabled())
		{
			return false;
		}

		if (!$elementId)
		{
			return false;
		}

		if ($deleteQueryCaches && craft()->config->get('cacheElementQueries'))
		{
			// If there are any pending DeleteStaleTemplateCaches tasks, just append this element to it
			$task = craft()->tasks->getNextPendingTask('DeleteStaleTemplateCaches');

			if ($task && is_array($task->settings))
			{
				$settings = $task->settings;

				if (!is_array($settings['elementId']))
				{
					$settings['elementId'] = array($settings['elementId']);
				}

				if (is_array($elementId))
				{
					$settings['elementId'] = array_merge($settings['elementId'], $elementId);
				}
				else
				{
					$settings['elementId'][] = $elementId;
				}

				// Make sure there aren't any duplicate element IDs
				$settings['elementId'] = array_unique($settings['elementId']);

				// Set the new settings and save the task
				$task->settings = $settings;
				craft()->tasks->saveTask($task, false);
			}
			else
			{
				craft()->tasks->createTask('DeleteStaleTemplateCaches', null, array(
					'elementId' => $elementId
				));
			}
		}

		$query = craft()->db->createCommand()
			->selectDistinct('cacheId')
			->from(static::$_templateCacheElementsTable);

		if (is_array($elementId))
		{
			$query->where(array('in', 'elementId', $elementId));
		}
		else
		{
			$query->where('elementId = :elementId', array(':elementId' => $elementId));
		}

		$cacheIds = $query->queryColumn();

		if ($cacheIds)
		{
			return $this->deleteCacheById($cacheIds);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Deletes caches that include elements that match a given criteria.
	 *
	 * @param ElementCriteriaModel $criteria The criteria that should be used to find elements whose caches should be
	 *                                       deleted.
	 *
	 * @return bool
	 */
	public function deleteCachesByCriteria(ElementCriteriaModel $criteria)
	{
		if ($this->_deletedAllCaches || !$this->_isTemplateCachingEnabled())
		{
			return false;
		}

		$criteria->limit = null;
		$elementIds = $criteria->ids();

		return $this->deleteCachesByElementId($elementIds);
	}

	/**
	 * Deletes a cache by its key(s).
	 *
	 * @param int|array $key The cache key(s) to delete.
	 *
	 * @return bool
	 */
	public function deleteCachesByKey($key)
	{
		if ($this->_deletedAllCaches || !$this->_isTemplateCachingEnabled())
		{
			return false;
		}

		if (is_array($key))
		{
			$condition = array('in', 'cacheKey', $key);
			$params = array();
		}
		else
		{
			$condition = 'cacheKey = :cacheKey';
			$params = array(':cacheKey' => $key);
		}

		$affectedRows = craft()->db->createCommand()->delete(static::$_templateCachesTable, $condition, $params);
		return (bool) $affectedRows;
	}

	/**
	 * Deletes any expired caches.
	 *
	 * @return bool
	 */
	public function deleteExpiredCaches()
	{
		if ($this->_deletedAllCaches || $this->_deletedExpiredCaches || !$this->_isTemplateCachingEnabled())
		{
			return false;
		}

		$affectedRows = craft()->db->createCommand()->delete(static::$_templateCachesTable,
			'expiryDate <= :now',
			array('now' => DateTimeHelper::currentTimeForDb())
		);

		$this->_deletedExpiredCaches = true;

		return (bool) $affectedRows;
	}

	/**
	 * Deletes any expired caches if we haven't already done that within the past 24 hours.
	 *
	 * @return bool
	 */
	public function deleteExpiredCachesIfOverdue()
	{
		// Ignore if we've already done this once during the request
		if ($this->_deletedExpiredCaches || !$this->_isTemplateCachingEnabled())
		{
			return false;
		}

		$lastCleanupDate = craft()->cache->get('lastTemplateCacheCleanupDate');

		if ($lastCleanupDate === false || DateTimeHelper::currentTimeStamp() - $lastCleanupDate > static::$_lastCleanupDateCacheDuration)
		{
			// Don't do it again for a while
			craft()->cache->set('lastTemplateCacheCleanupDate', DateTimeHelper::currentTimeStamp(), static::$_lastCleanupDateCacheDuration);

			return $this->deleteExpiredCaches();
		}
		else
		{
			$this->_deletedExpiredCaches = true;
			return false;
		}
	}

	/**
	 * Deletes all the template caches.
	 *
	 * @return bool
	 */
	public function deleteAllCaches()
	{
		if ($this->_deletedAllCaches || !$this->_isTemplateCachingEnabled())
		{
			return false;
		}

		$this->_deletedAllCaches = true;

		$affectedRows = craft()->db->createCommand()->delete(static::$_templateCachesTable);
		return (bool) $affectedRows;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the current request path, including a "site:" or "cp:" prefix.
	 *
	 * @return string
	 */
	private function _getPath()
	{
		if (!isset($this->_path))
		{
			if (craft()->request->isCpRequest())
			{
				$this->_path = 'cp:';
			}
			else
			{
				$this->_path = 'site:';
			}

			$this->_path .= craft()->request->getPath();

			if (($pageNum = craft()->request->getPageNum()) != 1)
			{
				$this->_path .= '/'.craft()->config->get('pageTrigger').$pageNum;
			}

			// Get the querystring without the path param.
			if ($queryString = craft()->request->getQueryStringWithoutPath())
			{
				$queryString = trim($queryString, '&');

				if ($queryString)
				{
					$this->_path .= '?'.$queryString;
				}
			}
		}

		return $this->_path;
	}

	/**
	 * @return bool
	 */
	private function _isTemplateCachingEnabled()
	{
		if (craft()->config->get('enableTemplateCaching'))
		{
			return true;
		}
	}
}
