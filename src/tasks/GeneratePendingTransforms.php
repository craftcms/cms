<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\tasks;

use Craft;
use craft\app\base\Task;

/**
 * GeneratePendingTransforms represents a Generate Pending Transforms background task.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class GeneratePendingTransforms extends Task
{
	// Properties
	// =========================================================================

	/**
	 * @var integer[] The pending transform index IDs
	 */
	private $_indexIds;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getTotalSteps()
	{
		// Get all of the pending transform index IDs
		$this->_indexIds = Craft::$app->getAssetTransforms()->getPendingTransformIndexIds();

		return count($this->_indexIds);
	}

	/**
	 * @inheritdoc
	 */
	public function runStep($step)
	{
		// Don't let an exception stop us from processing the rest
		try
		{
			$index = Craft::$app->getAssetTransforms()->getTransformIndexModelById($this->_indexIds[$step]);
			Craft::$app->getAssetTransforms()->ensureTransformUrlByIndexModel($index);
		}
		catch (\Exception $e) { }

		return true;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected function getDefaultDescription()
	{
		return Craft::t('app', 'Generating pending image transforms');
	}
}
