<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\tasks;

use Craft;

/**
 * The generate pending transforms task.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class GeneratePendingTransforms extends BaseTask
{
	private $_indexIds;

	/**
	 * @inheritDoc TaskInterface::getDescription()
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return Craft::t('app', 'Generating pending image transforms');
	}

	/**
	 * @inheritDoc TaskInterface::getTotalSteps()
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{
		// Get all of the pending transform index IDs
		$this->_indexIds = Craft::$app->assetTransforms->getPendingTransformIndexIds();

		return count($this->_indexIds);
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
		// Don't let an exception stop us from processing the rest
		try
		{
			$index = Craft::$app->assetTransforms->getTransformIndexModelById($this->_indexIds[$step]);
			Craft::$app->assetTransforms->ensureTransformUrlByIndexModel($index);
		}
		catch (\Exception $e) { }

		return true;
	}
}
