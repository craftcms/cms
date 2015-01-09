<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elementactions;

use Craft;
use craft\app\models\ElementCriteria as ElementCriteriaModel;

/**
 * Delete Assets Element Action
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class DeleteAssets extends BaseElementAction
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ComponentTypeInterface::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Delete…');
	}

	/**
	 * @inheritDoc ElementActionInterface::isDestructive()
	 *
	 * @return bool
	 */
	public function isDestructive()
	{
		return true;
	}

	/**
	 * @inheritDoc ElementActionInterface::getConfirmationMessage()
	 *
	 * @return string|null
	 */
	public function getConfirmationMessage()
	{
		return Craft::t('Are you sure you want to delete the selected assets?');
	}

	/**
	 * @inheritDoc ElementActionInterface::performAction()
	 *
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return bool
	 */
	public function performAction(ElementCriteriaModel $criteria)
	{
		Craft::$app->assets->deleteFiles($criteria->ids());

		$this->setMessage(Craft::t('Assets deleted.'));

		return true;
	}
}
