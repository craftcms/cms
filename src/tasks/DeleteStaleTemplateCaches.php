<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\tasks;

use Craft;
use craft\app\db\Command;
use craft\app\db\Query;
use craft\app\enums\AttributeType;
use craft\app\helpers\JsonHelper;

/**
 * A task that deletes stale template caches.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class DeleteStaleTemplateCaches extends BaseTask
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
	 * @inheritDoc TaskInterface::getDescription()
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return Craft::t('app', 'Deleting stale template caches');
	}

	/**
	 * @inheritDoc TaskInterface::getTotalSteps()
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{
		$elementId = $this->getSettings()->elementId;

		// What type of element(s) are we dealing with?
		$this->_elementType = Craft::$app->elements->getElementTypeById($elementId);

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
			$this->_elementIds = [$elementId];
		}

		// Figure out how many rows we're dealing with
		$totalRows = $this->_getQuery()->count('id');
		$this->_batch = 0;
		$this->_noMoreRows = false;
		$this->_deletedCacheIds = [];

		return $totalRows;
	}

	/**
	 * @inheritDoc TaskInterface::runStep()
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
					->all();

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
			$criteria = Craft::$app->elements->getCriteria($row['type'], $params);

			// Chance overcorrecting a little for the sake of templates with pending elements,
			// whose caches should be recreated (see http://craftcms.stackexchange.com/a/2611/9)
			$criteria->status = null;

			$criteriaElementIds = $criteria->ids();
			$cacheIdsToDelete = [];

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
				Craft::$app->templateCache->deleteCacheById($cacheIdsToDelete);
				$this->_deletedCacheIds = array_merge($this->_deletedCacheIds, $cacheIdsToDelete);
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
		return [
			'elementId' => AttributeType::Mixed,
		];
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns a Query object for selecting criteria that could be dropped by this task.
	 *
	 * @return Query
	 */
	private function _getQuery()
	{
		$query = (new Query())
			->from('templatecachecriteria');

		if (is_array($this->_elementType))
		{
			$query->where(['in', 'type', $this->_elementType]);
		}
		else
		{
			$query->where('type = :type', [':type' => $this->_elementType]);
		}

		return $query;
	}
}
