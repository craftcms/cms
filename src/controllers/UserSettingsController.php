<?php
namespace Craft;

craft()->requireEdition(Craft::Pro);

/**
 * Handles user group tasks
 */
class UserSettingsController extends BaseController
{
	/**
	 * Saves a user group.
	 */
	public function actionSaveGroup()
	{
		$this->requirePostRequest();

		$group = new UserGroupModel();
		$group->id = craft()->request->getPost('groupId');
		$group->name = craft()->request->getPost('name');
		$group->handle = craft()->request->getPost('handle');

		// Did it save?
		if (craft()->userGroups->saveGroup($group))
		{
			// Save the new permissions
			$permissions = craft()->request->getPost('permissions', array());
			craft()->userPermissions->saveGroupPermissions($group->id, $permissions);

			craft()->userSession->setNotice(Craft::t('Group saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save group.'));
		}

		// Send the group back to the template
		craft()->urlManager->setRouteVariables(array(
			'group' => $group
		));
	}

	/**
	 * Deletes a user group.
	 */
	public function actionDeleteGroup()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$groupId = craft()->request->getRequiredPost('id');

		craft()->userGroups->deleteGroupById($groupId);

		$this->returnJson(array('success' => true));
	}

	/**
	 * Saves the system user settings.
	 */
	public function actionSaveUserSettings()
	{
		$this->requirePostRequest();
		$this->requireAdmin();

		$settings['requireEmailVerification'] = (bool) craft()->request->getPost('requireEmailVerification');
		$settings['allowPublicRegistration'] = (bool) craft()->request->getPost('allowPublicRegistration');
		$settings['defaultGroup'] = craft()->request->getPost('defaultGroup');

		if (craft()->systemSettings->saveSettings('users', $settings))
		{
			craft()->userSession->setNotice(Craft::t('User settings saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save user settings.'));

			// Send the settings back to the template
			craft()->urlManager->setRouteVariables(array(
				'settings' => $settings
			));
		}
	}
}
