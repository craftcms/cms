<?php
namespace Craft;

/**
 * Delete Stale Template Caches Task
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
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

	// Public Methods
	// =========================================================================

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

		return $totalRows;
	}

	/**
	 * Runs a task step.
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

	// Protected Methods
	// =========================================================================

	/**
	 * Defines the settings.
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
	 * Returns a DbCommand object for selecting criteria that could be dropped
	 * by this task.
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
