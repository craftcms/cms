<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elementactions;

use Craft;
use craft\app\elements\db\ElementQueryInterface;
use craft\app\elements\User;
use craft\app\enums\UserStatus;

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
		return Craft::t('app', 'Unsuspend');
	}

	/**
	 * @inheritdoc
	 */
	public function performAction(ElementQueryInterface $query)
	{
		// Get the users that are suspended
		$query->status(UserStatus::Suspended);
		/** @var User[] $users */
		$users = $query->find();

		foreach ($users as $user)
		{
			Craft::$app->users->unsuspendUser($user);
		}

		$this->setMessage(Craft::t('app', 'Users unsuspended.'));

		return true;
	}
}
