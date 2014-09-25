<?php
namespace Craft;

/**
 * The FieldsController class is a controller that handles various field and field group related tasks such as saving
 * and deleting both fields and field groups.
 *
 * Note that all actions in the controller require an authenticated Craft session via {@link BaseController::allowAnonymous}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.controllers
 * @since     1.0
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
		craft()->userSession->requireAdmin();
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
		$group->id = craft()->request->getPost('id');
		$group->name = craft()->request->getRequiredPost('name');

		$isNewGroup = empty($group->id);

		if (craft()->fields->saveGroup($group))
		{
			if ($isNewGroup)
			{
				craft()->userSession->setNotice(Craft::t('Group added.'));
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

		$groupId = craft()->request->getRequiredPost('id');
		$success = craft()->fields->deleteGroupById($groupId);

		craft()->userSession->setNotice(Craft::t('Group deleted.'));

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

		$field->id           = craft()->request->getPost('fieldId');
		$field->groupId      = craft()->request->getRequiredPost('group');
		$field->name         = craft()->request->getPost('name');
		$field->handle       = craft()->request->getPost('handle');
		$field->instructions = craft()->request->getPost('instructions');
		$field->translatable = (bool) craft()->request->getPost('translatable');

		$field->type = craft()->request->getRequiredPost('type');

		$typeSettings = craft()->request->getPost('types');
		if (isset($typeSettings[$field->type]))
		{
			$field->settings = $typeSettings[$field->type];
		}

		if (craft()->fields->saveField($field))
		{
			craft()->userSession->setNotice(Craft::t('Field saved.'));

			if (isset($_POST['redirect']) && mb_strpos($_POST['redirect'], '{fieldId}') !== false)
			{
				craft()->deprecator->log('FieldsController::saveField():fieldId_redirect', 'The {fieldId} token within the ‘redirect’ param on fields/saveField requests has been deprecated. Use {id} instead.');
				$_POST['redirect'] = str_replace('{fieldId}', '{id}', $_POST['redirect']);
			}

			$this->redirectToPostedUrl($field);
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save field.'));
		}

		// Send the field back to the template
		craft()->urlManager->setRouteVariables(array(
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

		$fieldId = craft()->request->getRequiredPost('id');
		$success = craft()->fields->deleteFieldById($fieldId);
		$this->returnJson(array('success' => $success));
	}
}
