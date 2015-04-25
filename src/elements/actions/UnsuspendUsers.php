<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elements\actions;

use Craft;
use craft\app\base\ElementAction;
use craft\app\elements\db\ElementQueryInterface;
use craft\app\elements\User;

/**
 * UnsuspendUsers represents an Unsuspend Users element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UnsuspendUsers extends ElementAction
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getTriggerLabel()
	{
		return Craft::t('app', 'Unsuspend');
	}

	/**
	 * @inheritdoc
	 */
	public function performAction(ElementQueryInterface $query)
	{
		// Get the users that are suspended
		$query->status(User::STATUS_SUSPENDED);
		/** @var User[] $users */
		$users = $query->all();

		foreach ($users as $user)
		{
			Craft::$app->getUsers()->unsuspendUser($user);
		}

		$this->setMessage(Craft::t('app', 'Users unsuspended.'));

		return true;
	}
}
