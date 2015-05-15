<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\base\ElementInterface;
use craft\app\dates\DateTime;
use craft\app\db\Query;
use craft\app\elements\db\ElementQuery;
use craft\app\events\Event;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\JsonHelper;
use craft\app\helpers\StringHelper;
use craft\app\helpers\UrlHelper;
use craft\app\tasks\DeleteStaleTemplateCaches;
use yii\base\Component;

/**
 * Class TemplateCache service.
 *
 * An instance of the TemplateCache service is globally accessible in Craft via [[Application::templateCache `Craft::$app->getTemplateCache()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class TemplateCache extends Component
{
	// Properties
	// =========================================================================

	/**
	 * The table that template caches are stored in.
	 *
	 * @var string
	 */
	private static $_templateCachesTable = '{{%templatecaches}}';

	/**
	 * The table that template cache-element relations are stored in.
	 *
	 * @var string
	 */
	private static $_templateCacheElementsTable = '{{%templatecacheelements}}';

	/**
	 * The table that queries used within template caches are stored in.
	 *
	 * @var string
	 */
	private static $_templateCacheCriteriaTable = '{{%templatecachecriteria}}';

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
	 * A list of element queries that are active within the existing caches.
	 *
	 * @var array
	 */
	private $_cacheQueryParams;

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
		// Take the opportunity to delete any expired caches
		$this->deleteExpiredCachesIfOverdue();

		$conditions = ['and', 'expiryDate > :now', 'cacheKey = :key', 'locale = :locale'];

		$params = [
			':now'    => DateTimeHelper::currentTimeForDb(),
			':key'    => $key,
			':locale' => Craft::$app->language
		];

		if (!$global)
		{
			$conditions[] = 'path = :path';
			$params[':path'] = $this->_getPath();
		}

		return (new Query())
			->select('body')
			->from(static::$_templateCachesTable)
			->where($conditions, $params)
			->scalar();
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
		// Is this the first time we've started caching?
		if ($this->_cacheQueryParams === null)
		{
			Event::on(ElementQuery::className(), ElementQuery::EVENT_AFTER_PREPARE, [$this, 'includeElementQueryInTemplateCaches']);
		}

		if (Craft::$app->getConfig()->get('cacheElementQueries'))
		{
			$this->_cacheQueryParams[$key] = [];
		}

		$this->_cacheElementIds[$key] = [];
	}

	/**
	 * Includes an element criteria in any active caches.
	 *
	 * @param Event $event The 'afterPrepare' element query event
	 *
	 * @return null
	 */
	public function includeElementQueryInTemplateCaches(Event $event)
	{
		if (!empty($this->_cacheQueryParams))
		{
			/** @var ElementQuery $query */
			$query = $event->sender;
			$params = $query->toArray();
			$hash = md5(serialize($params));

			foreach (array_keys($this->_cacheQueryParams) as $cacheKey)
			{
				$this->_cacheQueryParams[$cacheKey][$hash] = $params;
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
		// If there are any transform generation URLs in the body, don't cache it.
		// Can't use getResourceUrl() here because that will append ?d= or ?x= to the URL.
		if (StringHelper::contains($body, UrlHelper::getSiteUrl(Craft::$app->getConfig()->getResourceTrigger().'/transforms')))
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
			$duration = Craft::$app->getConfig()->getCacheDuration();

			if($duration <= 0)
			{
				$duration = 31536000; // 1 year
			}

			$duration += time();

			$expiration = new DateTime('@'.$duration);
		}

		// Save it
		$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;

		try
		{
			Craft::$app->getDb()->createCommand()->insert(static::$_templateCachesTable, [
				'cacheKey'   => $key,
				'locale'     => Craft::$app->language,
				'path'       => ($global ? null : $this->_getPath()),
				'expiryDate' => DateTimeHelper::formatTimeForDb($expiration),
				'body'       => $body
			], false)->execute();

			$cacheId = Craft::$app->getDb()->getLastInsertID();

			// Tag it with any element criteria that were output within the cache
			if (!empty($this->_cacheQueryParams[$key]))
			{
				$values = [];

				foreach ($this->_cacheQueryParams[$key] as $params)
				{
					$values[] = [$cacheId, $params['elementType'], JsonHelper::encode($params)];
				}

				Craft::$app->getDb()->createCommand()->batchInsert(
					static::$_templateCacheCriteriaTable,
					['cacheId', 'type', 'criteria'],
					$values,
					false
				)->execute();

				unset($this->_cacheQueryParams[$key]);
			}

			// Tag it with any element IDs that were output within the cache
			if (!empty($this->_cacheElementIds[$key]))
			{
				$values = [];

				foreach ($this->_cacheElementIds[$key] as $elementId)
				{
					$values[] = [$cacheId, $elementId];
				}

				Craft::$app->getDb()->createCommand()->batchInsert(
					static::$_templateCacheElementsTable,
					['cacheId', 'elementId'],
					$values,
					false
				)->execute();

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
		if ($this->_deletedAllCaches)
		{
			return false;
		}

		if (is_array($cacheId))
		{
			$condition = ['in', 'id', $cacheId];
			$params = [];
		}
		else
		{
			$condition = 'id = :id';
			$params = [':id' => $cacheId];
		}

		$affectedRows = Craft::$app->getDb()->createCommand()->delete(static::$_templateCachesTable, $condition, $params)->execute();
		return (bool) $affectedRows;
	}

	/**
	 * Deletes caches by a given element class.
	 *
	 * @param string $elementType The element class.
	 *
	 * @return bool
	 */
	public function deleteCachesByElementType($elementType)
	{
		if ($this->_deletedAllCaches || !empty($this->_deletedCachesByElementType[$elementType]))
		{
			return false;
		}

		$this->_deletedCachesByElementType[$elementType] = true;

		$cacheIds = (new Query())
			->select('cacheId')
			->from(static::$_templateCacheCriteriaTable)
			->where(['type' => $elementType])
			->column();

		if ($cacheIds)
		{
			Craft::$app->getDb()->createCommand()->delete(static::$_templateCachesTable, ['in', 'id', $cacheIds])->execute();
		}

		return true;
	}

	/**
	 * Deletes caches that include a given element(s).
	 *
	 * @param ElementInterface|ElementInterface[] $elements The element(s) whose caches should be deleted.
	 *
	 * @return bool
	 */
	public function deleteCachesByElement($elements)
	{
		if ($this->_deletedAllCaches)
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
			$elements = [$elements];
		}

		$deleteQueryCaches = empty($this->_deletedCachesByElementType[$firstElement::className()]);
		$elementIds = [];

		/** @var ElementInterface[] $elements */
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
		if ($this->_deletedAllCaches)
		{
			return false;
		}

		if (!$elementId)
		{
			return false;
		}

		if ($deleteQueryCaches && Craft::$app->getConfig()->get('cacheElementQueries'))
		{
			// If there are any pending DeleteStaleTemplateCaches tasks, just append this element to it
			/** @var DeleteStaleTemplateCaches $task */
			$task = Craft::$app->getTasks()->getNextPendingTask(DeleteStaleTemplateCaches::className());

			if ($task)
			{
				if (!is_array($task->elementId))
				{
					$task->elementId = [$task->elementId];
				}

				if (is_array($elementId))
				{
					$task->elementId = array_merge($task->elementId, $elementId);
				}
				else
				{
					$task->elementId[] = $elementId;
				}

				// Make sure there aren't any duplicate element IDs
				$task->elementId = array_unique($task->elementId);

				// Save the task
				Craft::$app->getTasks()->saveTask($task, false);
			}
			else
			{
				Craft::$app->getTasks()->queueTask([
					'type'      => DeleteStaleTemplateCaches::className(),
					'elementId' => $elementId
				]);
			}
		}

		$query = (new Query())
			->select('cacheId')
			->distinct(true)
			->from(static::$_templateCacheElementsTable);

		if (is_array($elementId))
		{
			$query->where(['in', 'elementId', $elementId]);
		}
		else
		{
			$query->where('elementId = :elementId', [':elementId' => $elementId]);
		}

		$cacheIds = $query->column();

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
	 * Deletes caches that include elements that match a given element query's parameters.
	 *
	 * @param ElementQuery $query The element query that should be used to find elements whose caches
	 *                            should be deleted.
	 *
	 * @return bool
	 */
	public function deleteCachesByElementQuery(ElementQuery $query)
	{
		if ($this->_deletedAllCaches)
		{
			return false;
		}

		$limit = $query->limit;
		$query->limit(null);
		$elementIds = $query->ids();
		$query->limit($limit);

		return $this->deleteCachesByElementId($elementIds);
	}

	/**
	 * Deletes any expired caches.
	 *
	 * @return bool
	 */
	public function deleteExpiredCaches()
	{
		if ($this->_deletedAllCaches || $this->_deletedExpiredCaches)
		{
			return false;
		}

		$affectedRows = Craft::$app->getDb()->createCommand()->delete(static::$_templateCachesTable,
			'expiryDate <= :now',
			['now' => DateTimeHelper::currentTimeForDb()]
		)->execute();

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
		if ($this->_deletedExpiredCaches)
		{
			return false;
		}

		$lastCleanupDate = Craft::$app->getCache()->get('lastTemplateCacheCleanupDate');

		if ($lastCleanupDate === false || DateTimeHelper::currentTimeStamp() - $lastCleanupDate > static::$_lastCleanupDateCacheDuration)
		{
			// Don't do it again for a while
			Craft::$app->getCache()->set('lastTemplateCacheCleanupDate', DateTimeHelper::currentTimeStamp(), static::$_lastCleanupDateCacheDuration);

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
		if ($this->_deletedAllCaches)
		{
			return false;
		}

		$this->_deletedAllCaches = true;

		$affectedRows = Craft::$app->getDb()->createCommand()->delete(static::$_templateCachesTable)->execute();
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
			if (Craft::$app->getRequest()->getIsCpRequest())
			{
				$this->_path = 'cp:';
			}
			else
			{
				$this->_path = 'site:';
			}

			$this->_path .= Craft::$app->getRequest()->getPathInfo();

			if (($pageNum = Craft::$app->getRequest()->getPageNum()) != 1)
			{
				$this->_path .= '/'.Craft::$app->getConfig()->get('pageTrigger').$pageNum;
			}

			if ($queryString = Craft::$app->getRequest()->getQueryString())
			{
				// Strip the path param
				$pathParam = Craft::$app->getConfig()->get('pathParam');
				$queryString = trim(preg_replace('/'.preg_quote($pathParam, '/').'=[^&]*/', '', $queryString), '&');

				if ($queryString)
				{
					$this->_path .= '?'.$queryString;
				}
			}
		}

		return $this->_path;
	}
}
