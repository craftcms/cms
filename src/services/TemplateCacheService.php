<?php
namespace Craft;

class TemplateCacheService extends BaseApplicationComponent
{
	private static $_templateCachesTable = 'templatecaches';
	private static $_templateCacheElementsTable = 'templatecacheelements';
	private static $_templateCacheCriteriaTable = 'templatecachecriteria';
	private static $_lastCleanupDateCacheDuration = 86400;

	private $_path;
	private $_cacheCriteria;
	private $_cacheElementIds;
	private $_deletedExpiredCaches = false;

	/**
	 * Returns a cached template by its key.
	 *
	 * @param string $key
	 * @param bool   $global
	 * @return string|null
	 */
	public function getTemplateCache($key, $global)
	{
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

		return craft()->db->createCommand()
			->select('body')
			->from(static::$_templateCachesTable)
			->where($conditions, $params)
			->queryScalar();
	}

	/**
	 * Starts a new template cache.
	 *
	 * @param string $key
	 */
	public function startTemplateCache($key)
	{
		$this->_cacheCriteria[$key] = array();
		$this->_cacheElementIds[$key] = array();
	}

	/**
	 * Includes an element criteria in any active caches.
	 *
	 * @param ElementCriteriaModel $criteria
	 */
	public function includeCriteriaInTemplateCaches(ElementCriteriaModel $criteria)
	{
		if (!empty($this->_cacheCriteria))
		{
			foreach (array_keys($this->_cacheCriteria) as $key)
			{
				if (array_search($criteria, $this->_cacheCriteria[$key]) === false)
				{
					$this->_cacheCriteria[$key][] = $criteria;
				}
			}
		}
	}

	/**
	 * Includes an element in any active caches.
	 *
	 * @param int $elementId
	 */
	public function includeElementInTemplateCaches($elementId)
	{
		if (!empty($this->_cacheElementIds))
		{
			foreach (array_keys($this->_cacheElementIds) as $key)
			{
				if (array_search($elementId, $this->_cacheElementIds[$key]) === false)
				{
					$this->_cacheElementIds[$key][] = $elementId;
				}
			}
		}
	}

	/**
	 * Ends a template cache.
	 *
	 * @param string      $key
	 * @param bool        $global
	 * @param string|null $duration
	 * @param mixed|null  $expiration
	 * @param string      $body
	 */
	public function endTemplateCache($key, $global, $duration, $expiration, $body)
	{
		// If there are any transform generation URLs in the body, don't cache it
		if (strpos($body, 'assets/generateTransform'))
		{
			return;
		}

		// Figure out the expiration date
		if ($duration)
		{
			$expiration = new DateTime($duration);
		}

		if (!$expiration)
		{
			$timestamp = time() + craft()->config->getCacheDuration();
			$expiration = new DateTime('@'.$timestamp);
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
				// Stop capturing those before we do anything else
				$allCriteria = $this->_cacheCriteria[$key];
				unset($this->_cacheCriteria[$key]);

				$values = array();

				foreach ($allCriteria as $criteria)
				{
					// Do some normalization so we can save this to the DB safely
					$preppedCriteria = $this->_prepCriteria($criteria, $key);

					$values[] = array($cacheId, $criteria->getElementType()->getClassHandle(), JsonHelper::encode($preppedCriteria));
				}

				craft()->db->createCommand()->insertAll(static::$_templateCacheCriteriaTable, array('cacheId', 'type', 'criteria'), $values, false);
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
	 * @param int|array $cacheId
	 * @return bool
	 */
	public function deleteCacheById($cacheId)
	{
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
	 * Deletes caches that include an a given element ID(s).
	 *
	 * @param int|array $elementId
	 * @return bool
	 */
	public function deleteCachesByElementId($elementId)
	{
		if (!$elementId)
		{
			return false;
		}

		// Queue up a task to search through the cached criteria
		craft()->tasks->createTask('DeleteStaleTemplateCaches', null, array(
			'elementId' => $elementId
		));

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
	 * @param ElementCriteriaModel $criteria
	 * @return bool
	 */
	public function deleteCachesByCriteria(ElementCriteriaModel $criteria)
	{
		$criteria->limit = null;
		$elementIds = $criteria->ids();
		return $this->deleteCachesByElementId($elementIds);
	}

	/**
	 * Deletes any expired caches.
	 *
	 * @return bool
	 */
	public function deleteExpiredCaches()
	{
		// Ignore if we've already done this once during the request
		if ($this->_deletedExpiredCaches)
		{
			return false;
		}

		$affectedRows = craft()->db->createCommand()->delete(static::$_templateCachesTable,
			array('expiryDate <= :now'),
			array('now' => DateTimeHelper::currentTimeForDb())
		);

		// Make like an elephant...
		craft()->cache->set('lastTemplateCacheCleanupDate', DateTimeHelper::currentTimeStamp(), static::$_lastCleanupDateCacheDuration);
		$this->_deletedExpiredCaches = true;

		return $affectedRows;
	}

	/**
	 * Deletes any expired caches if we haven't already done that within the past 24 hours.
	 *
	 * @return bool
	 */
	public function deleteExpiredCachesIfOverdue()
	{
		// Ignore if we've already done this once during the request
		if ($this->_deletedExpiredCaches)
		{
			return false;
		}

		$lastCleanupDate = craft()->cache->get('lastTemplateCacheCleanupDate');

		if ($lastCleanupDate === false || DateTimeHelper::currentTimeStamp() - $lastCleanupDate > static::$_lastCleanupDateCacheDuration)
		{
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
		$affectedRows = craft()->db->createCommand()->delete(static::$_templateCachesTable);
		return (bool) $affectedRows;
	}

	/**
	 * Returns the current request path, including a "site:" or "cp:" prefix.
	 *
	 * @access private
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

			if ($queryString = craft()->request->getQueryString())
			{
				// Strip the path param
				$queryString = trim(preg_replace('/'.craft()->urlManager->pathParam.'=[^&]*/', '', $queryString), '&');

				if ($queryString)
				{
					$this->_path .= '?'.$queryString;
				}
			}
		}

		return $this->_path;
	}

	/**
	 * Flattens an ElementCriteriaModel into an array to be JSON-encoded.
	 *
	 * @access private
	 * @param ElementCriteriaModel
	 * @param string key
	 * @return array
	 */
	private function _prepCriteria(ElementCriteriaModel $criteria, $key)
	{
		// Add any IDs to the current cache's list of element IDs, just to be safe
		$elementIds = $criteria->ids();
		$this->_cacheElementIds[$key] = array_merge($this->_cacheElementIds[$key], $elementIds);

		$attributes = $criteria->getAttributes();
		$attributes['__criteria__'] = $criteria->getElementType()->getClassHandle();

		foreach (array('ancestorOf', 'descendantOf', 'siblingOf', 'prevSiblingOf', 'nextSiblingOf') as $param)
		{
			if ($attributes[$param] instanceof BaseElementModel)
			{
				$attributes[$param] = $attributes[$param]->id;
			}
		}

		if (!empty($attributes['relatedTo']))
		{
			// Ensure the criteria is an array
			$attributes['relatedTo'] = ArrayHelper::stringToArray($attributes['relatedTo']);

			if (isset($attributes['relatedTo']['element']) || isset($attributes['relatedTo']['sourceElement']) || isset($attributes['relatedTo']['targetElement']))
			{
				$attributes['relatedTo'] = array($attributes['relatedTo']);
			}

			foreach ($attributes['relatedTo'] as $relatedToKey => $relCriteria)
			{
				if (!is_array($relCriteria))
				{
					$relCriteria = array('element' => $relCriteria);
				}

				foreach (array('element', 'sourceElement', 'targetElement') as $elementParam)
				{
					if (isset($relCriteria[$elementParam]))
					{
						$normalizedElements = array();
						$elements = ArrayHelper::stringToArray($relCriteria[$elementParam]);

						foreach ($elements as $element)
						{
							if (is_numeric($element))
							{
								$normalizedElements[] = $element;
							}
							else if ($element instanceof BaseElementModel)
							{
								$normalizedElements[] = $element->id;
							}
							else if ($element instanceof ElementCriteriaModel)
							{
								$normalizedElements = array_merge($normalizedElements, $this->_prepCriteria($element, $key));
							}
						}

						$relCriteria[$elementParam] = $normalizedElements;
						break;
					}
				}

				$attributes['relatedTo'][$relatedToKey] = $relCriteria;
			}
		}

		return $attributes;
	}
}