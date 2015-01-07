<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use craft\app\Craft;
use craft\app\models\Field          as FieldModel;
use craft\app\models\FieldGroup     as FieldGroupModel;
use craft\app\errors\HttpException;

/**
 * The FieldsController class is a controller that handles various field and field group related tasks such as saving
 * and deleting both fields and field groups.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[BaseController::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FieldsController extends BaseController
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
		// All field actions require an admin
		$this->requireAdmin();
	}

	// Groups
	// -------------------------------------------------------------------------

	/**
	 * Saves a field group.
	 *
	 * @return null
	 */
	public function actionSaveGroup()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$group = new FieldGroupModel();
		$group->id = Craft::$app->request->getPost('id');
		$group->name = Craft::$app->request->getRequiredPost('name');

		$isNewGroup = empty($group->id);

		if (Craft::$app->fields->saveGroup($group))
		{
			if ($isNewGroup)
			{
				Craft::$app->getSession()->setNotice(Craft::t('Group added.'));
			}

			$this->returnJson(array(
				'success' => true,
				'group'   => $group->getAttributes(),
			));
		}
		else
		{
			$this->returnJson(array(
				'errors' => $group->getErrors(),
			));
		}
	}

	/**
	 * Deletes a field group.
	 *
	 * @return null
	 */
	public function actionDeleteGroup()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$groupId = Craft::$app->request->getRequiredPost('id');
		$success = Craft::$app->fields->deleteGroupById($groupId);

		Craft::$app->getSession()->setNotice(Craft::t('Group deleted.'));

		$this->returnJson(array(
			'success' => $success,
		));
	}

	// Fields
	// -------------------------------------------------------------------------

	/**
	 * Saves a field.
	 *
	 * @return null
	 */
	public function actionSaveField()
	{
		$this->requirePostRequest();

		$field = new FieldModel();

		$field->id           = Craft::$app->request->getPost('fieldId');
		$field->groupId      = Craft::$app->request->getRequiredPost('group');
		$field->name         = Craft::$app->request->getPost('name');
		$field->handle       = Craft::$app->request->getPost('handle');
		$field->instructions = Craft::$app->request->getPost('instructions');
		$field->translatable = (bool) Craft::$app->request->getPost('translatable');

		$field->type = Craft::$app->request->getRequiredPost('type');

		$typeSettings = Craft::$app->request->getPost('types');
		if (isset($typeSettings[$field->type]))
		{
			$field->settings = $typeSettings[$field->type];
		}

		if (Craft::$app->fields->saveField($field))
		{
			Craft::$app->getSession()->setNotice(Craft::t('Field saved.'));
			$this->redirectToPostedUrl($field);
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('Couldn’t save field.'));
		}

		// Send the field back to the template
		Craft::$app->urlManager->setRouteVariables(array(
			'field' => $field
		));
	}

	/**
	 * Deletes a field.
	 *
	 * @return null
	 */
	public function actionDeleteField()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$fieldId = Craft::$app->request->getRequiredPost('id');
		$success = Craft::$app->fields->deleteFieldById($fieldId);
		$this->returnJson(array('success' => $success));
	}
}
