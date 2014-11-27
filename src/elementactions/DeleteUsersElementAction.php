<?php
namespace Craft;

/**
 * Delete Users Element Action
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.elementactions
 * @since     2.3
 */
class DeleteUsersElementAction extends BaseElementAction
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
		return Craft::t('Delete…');
	}

	/**
	 * @inheritDoc IElementAction::isDestructive()
	 *
	 * @return bool
	 */
	public function isDestructive()
	{
		return true;
	}

	/**
	 * @inheritDoc IElementAction::getTriggerHtml()
	 *
	 * @return string|null
	 */
	public function getTriggerHtml()
	{
		$undeletableIds = JsonHelper::encode($this->_getUndeletableUserIds());

		$js = <<<EOT
(function()
{
	var trigger = new Craft.ElementActionTrigger({
		handle: 'DeleteUsers',
		batch: true,
		validateSelection: function(\$selectedItems)
		{
			for (var i = 0; i < \$selectedItems.length; i++)
			{
				if ($.inArray(\$selectedItems.eq(i).find('.element').data('id').toString(), $undeletableIds) != -1)
				{
					return false;
				}
			}

			return true;
		},
		activate: function(\$selectedItems)
		{
			var modal = new Craft.DeleteUserModal(Craft.elementIndex.getSelectedElementIds(), {
				onSubmit: function()
				{
					Craft.elementIndex.submitAction('DeleteUsers', Garnish.getPostData(modal.\$container));
					modal.hide();

					return false;
				}
			});
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
		$users = $criteria->find();
		$undeletableIds = $this->_getUndeletableUserIds();

		// Are we transfering the user's content to a different user?
		$transferContentToId = $this->getParams()->transferContentTo;

		if (is_array($transferContentToId) && isset($transferContentToId[0]))
		{
			$transferContentToId = $transferContentToId[0];
		}

		if ($transferContentToId)
		{
			$transferContentTo = craft()->users->getUserById($transferContentToId);

			if (!$transferContentTo)
			{
				throw new Exception(Craft::t('No user exists with the ID “{id}”.', array('id' => $transferContentTo)));
			}
		}
		else
		{
			$transferContentTo = null;
		}

		// Delete the users
		foreach ($users as $user)
		{
			if (!in_array($user->id, $undeletableIds))
			{
				craft()->users->deleteUser($user, $transferContentTo);
			}
		}

		$this->setMessage(Craft::t('Users deleted.'));

		return true;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseElementAction::defineParams()
	 *
	 * @return array
	 */
	protected function defineParams()
	{
		return array(
			'transferContentTo' => AttributeType::Mixed,
		);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns a list of the user IDs that can't be deleted.
	 *
	 * @return array
	 */
	private function _getUndeletableUserIds()
	{
		if (!craft()->userSession->isAdmin())
		{
			// Only admins can delete other admins
			return craft()->elements->getCriteria(ElementType::User, array(
				'admin' => true
			))->ids();
		}
		else
		{
			// Can't delete your own account from here
			return array(craft()->userSession->getUser()->id);
		}
	}
}
