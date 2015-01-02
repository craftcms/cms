<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elementactions;

use craft\app\Craft;
use craft\app\models\ElementCriteria as ElementCriteriaModel;

/**
 * Delete Users Element Action
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class DeleteUsers extends BaseElementAction
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
	 * @inheritDoc ElementActionInterface::getTriggerHtml()
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
	 * @inheritDoc ElementActionInterface::performAction()
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
