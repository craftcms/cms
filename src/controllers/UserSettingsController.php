<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\errors\HttpException;
use craft\app\models\UserGroup as UserGroupModel;
use craft\app\web\Controller;

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
class UserSettingsController extends Controller
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 * @throws HttpException if the user isn’t an admin
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
		if (Craft::$app->getUserGroups()->saveGroup($group))
		{
			// Save the new permissions
			$permissions = Craft::$app->getRequest()->getBodyParam('permissions', []);
			Craft::$app->getUserPermissions()->saveGroupPermissions($group->id, $permissions);

			Craft::$app->getSession()->setNotice(Craft::t('app', 'Group saved.'));
			return $this->redirectToPostedUrl();
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save group.'));
		}

		// Send the group back to the template
		Craft::$app->getUrlManager()->setRouteParams([
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

		Craft::$app->getUserGroups()->deleteGroupById($groupId);

		return $this->asJson(['success' => true]);
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

		if (Craft::$app->getSystemSettings()->saveSettings('users', $settings))
		{
			Craft::$app->getSession()->setNotice(Craft::t('app', 'User settings saved.'));
			return $this->redirectToPostedUrl();
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save user settings.'));

			// Send the settings back to the template
			Craft::$app->getUrlManager()->setRouteParams([
				'settings' => $settings
			]);
		}
	}
}
