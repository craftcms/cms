<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\tasks;

use Craft;
use craft\app\base\Task;
use craft\app\base\ElementInterface;
use craft\app\db\Query;
use craft\app\elements\db\ElementQuery;
use craft\app\helpers\JsonHelper;
use yii\db\BatchQueryResult;

/**
 * DeleteStaleTemplateCaches represents a Delete Stale Template Caches background task.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class DeleteStaleTemplateCaches extends Task
{
	// Properties
	// =========================================================================

	/**
	 * @var integer|integer[] The element ID(s) whose caches need to be cleared
	 */
	public $elementId;

	/**
	 * @var ElementInterface|ElementInterface[] The element type(s) we're dealing with
	 */
	private $_elementType;

	/**
	 * @var BatchQueryResult The element criteria query result
	 */
	private $_result;

	/**
	 * @var integer[] The cache IDs that the task has already deleted
	 */
	private $_deletedCacheIds;

	/**
	 * @var
	 */
	private $_totalDeletedCriteriaRows;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getTotalSteps()
	{
		// What type of element(s) are we dealing with?
		$this->_elementType = Craft::$app->getElements()->getElementTypeById($this->elementId);

		if (!$this->_elementType)
		{
			return 0;
		}

		if (!is_array($this->elementId))
		{
			$this->elementId = [$this->elementId];
		}

		// Prep the query result
		$query = (new Query())
			->from('{{%templatecachecriteria}}');

		if (is_array($this->_elementType))
		{
			$query->where(['in', 'type', $this->_elementType]);
		}
		else
		{
			$query->where(['type' => $this->_elementType]);
		}

		$this->_result = $query->each();

		// Return the total number of rows
		return $query->count('id');
	}

	/**
	 * @inheritdoc
	 */
	public function runStep($step)
	{
		// Get the next row
		$this->_result->next();
		$row = $this->_result->current();

		// Make sure this row hasn't already been deleted by something else
		if ($row === false)
		{
			return true;
		}

		if (!in_array($row['cacheId'], $this->_deletedCacheIds))
		{
			$criteria = JsonHelper::decode($row['criteria']);
			/** @var ElementInterface $elementType */
			$elementType = $row['type'];
			/** @var ElementQuery $query */
			$query = $elementType::find()->configure($criteria);

			// Chance over-correcting a little for the sake of templates with pending elements,
			// whose caches should be recreated (see http://craftcms.stackexchange.com/a/2611/9)
			$query->status(null);

			$elementIds = $query->ids();
			$cacheIdsToDelete = [];

			foreach ($this->elementId as $elementId)
			{
				if (in_array($elementId, $elementIds))
				{
					$cacheIdsToDelete[] = $row['cacheId'];
					break;
				}
			}

			if ($cacheIdsToDelete)
			{
				Craft::$app->getTemplateCache()->deleteCacheById($cacheIdsToDelete);
				$this->_deletedCacheIds = array_merge($this->_deletedCacheIds, $cacheIdsToDelete);
			}
		}

		return true;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected function getDefaultDescription()
	{
		return Craft::t('app', 'Deleting stale template caches');
	}
}
