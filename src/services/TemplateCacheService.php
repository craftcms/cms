<?php
namespace Craft;

class TemplateCacheService extends BaseApplicationComponent
{
	private static $_templateCachesTable = 'templatecaches';
	private static $_templateCacheElementsTable = 'templatecacheelements';

	private $_cacheElementIds;

	/**
	 * Returns a cached template by its key.
	 *
	 * @param string $key
	 * @param bool   $global
	 * @return string|null
	 */
	public function getTemplateCache($key, $global)
	{
		$conditions = array('and', 'expires > :now', 'cacheKey = :key');
		$params = array(
			':now' => DateTimeHelper::currentTimeForDb(),
			':key' => $key
		);

		if (!$global)
		{
			$conditions[] = 'uri = :path';
			$params[':path'] = craft()->request->getPath();
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
		$this->_cacheElementIds[$key] = array();
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
				'cacheKey' => $key,
				'uri'      => ($global ? null : craft()->request->getPath()),
				'expires'  => DateTimeHelper::formatTimeForDb($expiration),
				'body'     => $body
			), false);

			$cacheId = craft()->db->getLastInsertID();

			if (isset($this->_cacheElementIds[$key]))
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
	 * Deletes caches that involve a given element ID.
	 *
	 * @param int $elementId
	 */
	public function deleteCachesWithElement($elementId)
	{
		$cacheIds = craft()->db->createCommand()
			->selectDistinct('cacheId')
			->from(static::$_templateCacheElementsTable)
			->where('elementId = :elementId', array(':elementId' => $elementId))
			->queryColumn();

		if ($cacheIds)
		{
			craft()->db->createCommand()->delete(static::$_templateCachesTable, array('in', 'id', $cacheIds));
		}
	}
}