<?php
namespace craft\app\elementactions;

use \craft\app\models\ElementCriteria as ElementCriteriaModel;

/**
 * Unsuspend Users Element Action
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.elementactions
 * @since     2.3
 */
class UnsuspendUsers extends BaseElementAction
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
		return Craft::t('Unsuspend');
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
		// Get the users that are suspended
		$criteria->status = UserStatus::Suspended;
		$users = $criteria->find();

		foreach ($users as $user)
		{
			craft()->users->unsuspendUser($user);
		}

		$this->setMessage(Craft::t('Users unsuspended.'));

		return true;
	}
}
