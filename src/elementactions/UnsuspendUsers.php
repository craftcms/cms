<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elementactions;

use craft\app\Craft;
use craft\app\enums\UserStatus;
use craft\app\models\ElementCriteria as ElementCriteriaModel;

/**
 * Unsuspend Users Element Action
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
