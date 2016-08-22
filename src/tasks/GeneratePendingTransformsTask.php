<?php
namespace Craft;

/**
 * Generate Pending Transforms Task
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @link      http://craftcms.com
 * @package   craft.app.tasks
 * @since     2.0
 */
class GeneratePendingTransformsTask extends BaseTask
{
	private $_indexIds;

	/**
	 * @inheritDoc ITask::getDescription()
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return Craft::t('Generating pending image transforms');
	}

	/**
	 * @inheritDoc ITask::getTotalSteps()
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{
		// Get all of the pending transform index IDs
		$this->_indexIds = craft()->assetTransforms->getPendingTransformIndexIds();

		return count($this->_indexIds);
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
		// Don't let an exception stop us from processing the rest
		try
		{
			$index = craft()->assetTransforms->getTransformIndexModelById($this->_indexIds[$step]);

			// No transform means a probably already finished transform.
			if (!$index)
			{
				return true;
			}

			craft()->assetTransforms->ensureTransformUrlByIndexModel($index);
		}
		catch (\Exception $e) { }

		return true;
	}
}
