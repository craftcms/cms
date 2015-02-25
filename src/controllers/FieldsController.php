<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\errors\HttpException;
use craft\app\models\Field as FieldModel;
use craft\app\models\FieldGroup as FieldGroupModel;
use craft\app\web\Controller;

/**
 * The FieldsController class is a controller that handles various field and field group related tasks such as saving
 * and deleting both fields and field groups.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FieldsController extends Controller
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc Controller::init()
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
		$group->id = Craft::$app->getRequest()->getBodyParam('id');
		$group->name = Craft::$app->getRequest()->getRequiredBodyParam('name');

		$isNewGroup = empty($group->id);

		if (Craft::$app->fields->saveGroup($group))
		{
			if ($isNewGroup)
			{
				Craft::$app->getSession()->setNotice(Craft::t('app', 'Group added.'));
			}

			$this->returnJson([
				'success' => true,
				'group'   => $group->getAttributes(),
			]);
		}
		else
		{
			$this->returnJson([
				'errors' => $group->getErrors(),
			]);
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

		$groupId = Craft::$app->getRequest()->getRequiredBodyParam('id');
		$success = Craft::$app->fields->deleteGroupById($groupId);

		Craft::$app->getSession()->setNotice(Craft::t('app', 'Group deleted.'));

		$this->returnJson([
			'success' => $success,
		]);
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

		$field->id           = Craft::$app->getRequest()->getBodyParam('fieldId');
		$field->groupId      = Craft::$app->getRequest()->getRequiredBodyParam('group');
		$field->name         = Craft::$app->getRequest()->getBodyParam('name');
		$field->handle       = Craft::$app->getRequest()->getBodyParam('handle');
		$field->instructions = Craft::$app->getRequest()->getBodyParam('instructions');
		$field->translatable = (bool) Craft::$app->getRequest()->getBodyParam('translatable');

		$field->type = Craft::$app->getRequest()->getRequiredBodyParam('type');

		$typeSettings = Craft::$app->getRequest()->getBodyParam('types');
		if (isset($typeSettings[$field->type]))
		{
			$field->settings = $typeSettings[$field->type];
		}

		if (Craft::$app->fields->saveField($field))
		{
			Craft::$app->getSession()->setNotice(Craft::t('app', 'Field saved.'));
			$this->redirectToPostedUrl($field);
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save field.'));
		}

		// Send the field back to the template
		Craft::$app->getUrlManager()->setRouteParams([
			'field' => $field
		]);
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

		$fieldId = Craft::$app->getRequest()->getRequiredBodyParam('id');
		$success = Craft::$app->fields->deleteFieldById($fieldId);
		$this->returnJson(['success' => $success]);
	}
}
