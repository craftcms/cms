<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\errors\HttpException;
use craft\app\models\UserGroup as UserGroupModel;

Craft::$app->requireEdition(Craft::Pro);

/**
 * The TagsController class is a controller that handles various user group and user settings related tasks such as
 * creating, editing and deleting user groups and saving Craft user settings.
 *
 * Note that all actions in this controller require administrator access in order to execute.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
		$this->requireAdmin();
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
		$group->id = Craft::$app->getRequest()->getBodyParam('groupId');
		$group->name = Craft::$app->getRequest()->getBodyParam('name');
		$group->handle = Craft::$app->getRequest()->getBodyParam('handle');

		// Did it save?
		if (Craft::$app->userGroups->saveGroup($group))
		{
			// Save the new permissions
			$permissions = Craft::$app->getRequest()->getBodyParam('permissions', []);
			Craft::$app->userPermissions->saveGroupPermissions($group->id, $permissions);

			Craft::$app->getSession()->setNotice(Craft::t('Group saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('Couldn’t save group.'));
		}

		// Send the group back to the template
		Craft::$app->getUrlManeger()->setRouteVariables([
			'group' => $group
		]);
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

		$groupId = Craft::$app->getRequest()->getRequiredBodyParam('id');

		Craft::$app->userGroups->deleteGroupById($groupId);

		$this->returnJson(['success' => true]);
	}

	/**
	 * Saves the system user settings.
	 *
	 * @return null
	 */
	public function actionSaveUserSettings()
	{
		$this->requirePostRequest();

		$settings['requireEmailVerification'] = (bool) Craft::$app->getRequest()->getBodyParam('requireEmailVerification');
		$settings['allowPublicRegistration'] = (bool) Craft::$app->getRequest()->getBodyParam('allowPublicRegistration');
		$settings['defaultGroup'] = Craft::$app->getRequest()->getBodyParam('defaultGroup');

		if (Craft::$app->systemSettings->saveSettings('users', $settings))
		{
			Craft::$app->getSession()->setNotice(Craft::t('User settings saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('Couldn’t save user settings.'));

			// Send the settings back to the template
			Craft::$app->getUrlManeger()->setRouteVariables([
				'settings' => $settings
			]);
		}
	}
}
