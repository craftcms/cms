<?php
namespace Craft;

/**
 * Suspend Users Element Action
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.elementactions
 * @since     2.3
 */
class SuspendUsersElementAction extends BaseElementAction
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Suspend');
	}

	/**
	 * @inheritDoc IElementAction::getTriggerHtml()
	 *
	 * @return string|null
	 */
	public function getTriggerHtml()
	{
		$userId = JsonHelper::encode(craft()->userSession->getUser()->id);

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

		craft()->templates->includeJs($js);
	}

	/**
	 * @inheritDoc IElementAction::performAction()
	 *
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return bool
	 */
	public function performAction(ElementCriteriaModel $criteria)
	{
		// Get the users that aren't already suspended
		$criteria->status = array(
			UserStatus::Active,
			UserStatus::Locked,
			UserStatus::Pending,
		);
		$users = $criteria->find();

		foreach ($users as $user)
		{
			if (!$user->isCurrent())
			{
				craft()->users->suspendUser($user);
			}
		}

		$this->setMessage(Craft::t('Users suspended.'));

		return true;
	}
}
