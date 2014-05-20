<?php
namespace Craft;

/**
 * Delete Stale Template Caches Task
 */
class DeleteStaleTemplateCachesTask extends BaseTask
{
	private $_elementIds;
	private $_elementType;

	private $_batch;
	private $_batchRows;
	private $_noMoreRows;
	private $_deletedCacheIds;

	/**
	 * Returns the default description for this task.
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return Craft::t('Deleting stale template caches');
	}

	/**
	 * Defines the settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'elementId' => AttributeType::Mixed,
		);
	}

	/**
	 * Gets the total number of steps for this task.
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

		if (is_array($this->_elementIds))
		{
			$this->_elementIds = $elementId;
		}
		else
		{
			$this->_elementIds = array($this->_elementIds);
		}

		// Figure out how many rows we're dealing with
		$totalRows = $this->_getQuery()->count('id');
		$this->_batch = 0;
		$this->_noMoreRows = false;
		$this->_deletedCacheIds = array();

		return $totalRows;
	}

	/**
	 * Runs a task step.
	 *
	 * @param int $step
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
					->offset(100*($this->_batch-1))
					->limit(100*$this->_batch)
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

		if (!in_array($row['cacheId'], $this->_deletedCacheIds))
		{
			$params = JsonHelper::decode($row['criteria']);
			$criteria = craft()->elements->getCriteria($row['type'], $params);
			$criteriaElementIds = $criteria->ids();
			$cacheIdsToDelete = array();

			foreach ($this->_elementIds as $elementId)
			{
				if (in_array($elementId, $criteriaElementIds))
				{
					$cacheIdsToDelete[] = $row['cacheId'];
					break;
				}
			}

			if ($cacheIdsToDelete)
			{
				craft()->templateCache->deleteCacheById($cacheIdsToDelete);
				$this->_deletedCacheIds = array_merge($this->_deletedCacheIds, $cacheIdsToDelete);
			}
		}

		return true;
	}

	/**
	 * Returns a DbCommand object for selecing criteria that could be dropped by this task.
	 *
	 * @access private
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
