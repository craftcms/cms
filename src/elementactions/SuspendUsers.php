<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elementactions;

use craft\app\Craft;
use craft\app\enums\UserStatus;
use craft\app\helpers\JsonHelper;
use craft\app\models\ElementCriteria as ElementCriteriaModel;

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
		return Craft::t('Suspend');
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
	 * @inheritDoc ElementActionInterface::performAction()
	 *
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return bool
	 */
	public function performAction(ElementCriteriaModel $criteria)
	{
		// Get the users that aren't already suspended
		$criteria->status = [
			UserStatus::Active,
			UserStatus::Locked,
			UserStatus::Pending,
		];
		$users = $criteria->find();

		foreach ($users as $user)
		{
			if (!$user->isCurrent())
			{
				Craft::$app->users->suspendUser($user);
			}
		}

		$this->setMessage(Craft::t('Users suspended.'));

		return true;
	}
}
