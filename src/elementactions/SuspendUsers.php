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
use craft\app\helpers\JsonHelper;

/**
 * Suspend Users Element Action
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SuspendUsers extends BaseElementAction
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
		return Craft::t('app', 'Suspend');
	}

	/**
	 * @inheritDoc ElementActionInterface::getTriggerHtml()
	 *
	 * @return string|null
	 */
	public function getTriggerHtml()
	{
		$userId = JsonHelper::encode(Craft::$app->getUser()->getIdentity()->id);

		$js = <<<EOT
(function()
{
	var trigger = new Craft.ElementActionTrigger({
		handle: 'SuspendUsers',
		batch: true,
		validateSelection: function(\$selectedItems)
		{
			for (var i = 0; i < \$selectedItems.length; i++)
			{
				if (\$selectedItems.eq(i).find('.element').data('id') == $userId)
				{
					return false;
				}
			}

			return true;
		}
	});
})();
EOT;

		Craft::$app->templates->includeJs($js);
	}

	/**
	 * @inheritdoc
	 */
	public function performAction(ElementQueryInterface $query)
	{
		// Get the users that aren't already suspended
		$query->status = [
			UserStatus::Active,
			UserStatus::Locked,
			UserStatus::Pending,
		];

		/** @var User[] $users */
		$users = $query->all();

		foreach ($users as $user)
		{
			if (!$user->isCurrent())
			{
				Craft::$app->users->suspendUser($user);
			}
		}

		$this->setMessage(Craft::t('app', 'Users suspended.'));

		return true;
	}
}
