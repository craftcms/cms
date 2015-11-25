<?php
namespace Craft;

craft()->requireEdition(Craft::Pro);

/**
 * The UserSettingsController class is a controller that handles various user group and user settings related tasks such as
 * creating, editing and deleting user groups and saving Craft user settings.
 *
 * Note that all actions in this controller require administrator access in order to execute.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.controllers
 * @since     1.0
 */
class UserSettingsController extends BaseController
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseController::init()
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function init()
	{
		// All user settings actions require an admin
		craft()->userSession->requireAdmin();
	}

	/**
	 * Saves a user group.
	 *
	 * @return null
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
	 *
	 * @return null
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
	 *
	 * @return null
	 */
	public function actionSaveUserSettings()
	{
		$this->requirePostRequest();

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
