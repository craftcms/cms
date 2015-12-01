<?php
namespace Craft;

/**
 * Delete Stale Template Caches Task
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.tasks
 * @since     2.0
 */
class DeleteStaleTemplateCachesTask extends BaseTask
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_elementIds;

	/**
	 * @var
	 */
	private $_elementType;

	/**
	 * @var
	 */
	private $_batch;

	/**
	 * @var
	 */
	private $_batchRows;

	/**
	 * @var
	 */
	private $_noMoreRows;

	/**
	 * @var
	 */
	private $_deletedCacheIds;

	/**
	 * @var
	 */
	private $_totalDeletedCriteriaRows;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ITask::getDescription()
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return Craft::t('Deleting stale template caches');
	}

	/**
	 * @inheritDoc ITask::getTotalSteps()
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{
		$elementId = $this->getSettings()->elementId;

		// What type of element(s) are we dealing with?
		$this->_elementType = craft()->elements->getElementTypeById($elementId);

		if (!$this->_elementType)
		{
			return 0;
		}

		if (is_array($elementId))
		{
			$this->_elementIds = $elementId;
		}
		else
		{
			$this->_elementIds = array($elementId);
		}

		// Figure out how many rows we're dealing with
		$totalRows = $this->_getQuery()->count('id');
		$this->_batch = 0;
		$this->_noMoreRows = false;
		$this->_deletedCacheIds = array();
		$this->_totalDeletedCriteriaRows = 0;

		return $totalRows;
	}

	/**
	 * @inheritDoc ITask::runStep()
	 *
	 * @param int $step
	 *
	 * @return bool
	 */
	public function runStep($step)
	{
		// Do we need to grab a fresh batch?
		if (empty($this->_batchRows))
		{
			if (!$this->_noMoreRows)
			{
				$this->_batch++;
				$this->_batchRows = $this->_getQuery()
					->order('id')
					->offset(100*($this->_batch-1) - $this->_totalDeletedCriteriaRows)
					->limit(100)
					->queryAll();

				// Still no more rows?
				if (!$this->_batchRows)
				{
					$this->_noMoreRows = true;
				}
			}

			if ($this->_noMoreRows)
			{
				return true;
			}
		}

		$row = array_shift($this->_batchRows);

		// Have we already deleted this cache?
		if (in_array($row['cacheId'], $this->_deletedCacheIds))
		{
			$this->_totalDeletedCriteriaRows++;
		}
		else
		{
			// Create an ElementCriteriaModel that resembles the one that led to this query
			$params = JsonHelper::decode($row['criteria']);
			$criteria = craft()->elements->getCriteria($row['type'], $params);

			// Chance overcorrecting a little for the sake of templates with pending elements,
			// whose caches should be recreated (see http://craftcms.stackexchange.com/a/2611/9)
			$criteria->status = null;

			// See if any of the updated elements would get fetched by this query
			if (array_intersect($criteria->ids(), $this->_elementIds))
			{
				// Delete this cache
				craft()->templateCache->deleteCacheById($row['cacheId']);
				$this->_deletedCacheIds[] = $row['cacheId'];
				$this->_totalDeletedCriteriaRows++;
			}
		}

		return true;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseSavableComponentType::defineSettings()
	 *
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'elementId' => AttributeType::Mixed,
		);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns a DbCommand object for selecting criteria that could be dropped by this task.
	 *
	 * @return DbCommand
	 */
	private function _getQuery()
	{
		$query = craft()->db->createCommand()
			->from('templatecachecriteria');

		if (is_array($this->_elementType))
		{
			$query->where(array('in', 'type', $this->_elementType));
		}
		else
		{
			$query->where('type = :type', array(':type' => $this->_elementType));
		}

		return $query;
	}
}
