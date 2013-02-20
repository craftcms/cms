<?php
namespace Blocks;

/**
 * Handles field tasks
 */
class FieldsController extends BaseController
{
	// Groups
	// ======

	/**
	 * Saves a field group.
	 */
	public function actionSaveGroup()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$group = new FieldGroupModel();
		$group->id = blx()->request->getPost('id');
		$group->name = blx()->request->getRequiredPost('name');

		$isNewGroup = empty($group->id);

		if (blx()->fields->saveGroup($group))
		{
			if ($isNewGroup)
			{
				blx()->userSession->setNotice(Blocks::t('Group added.'));
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
	 */
	public function actionDeleteGroup()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$groupId = blx()->request->getRequiredPost('id');
		$success = blx()->fields->deleteGroupById($groupId);

		blx()->userSession->setNotice(Blocks::t('Group deleted.'));

		$this->returnJson(array(
			'success' => $success,
		));
	}

	// Fields
	// ======

	/**
	 * Saves a field.
	 */
	public function actionSaveField()
	{
		$this->requirePostRequest();

		$field = new FieldModel();

		$field->id           = blx()->request->getPost('fieldId');
		$field->groupId      = blx()->request->getRequiredPost('group');
		$field->name         = blx()->request->getPost('name');
		$field->handle       = blx()->request->getPost('handle');
		$field->instructions = blx()->request->getPost('instructions');
		$field->translatable = (bool) blx()->request->getPost('translatable');

		$field->type = blx()->request->getRequiredPost('type');

		$typeSettings = blx()->request->getPost('types');
		if (isset($typeSettings[$field->type]))
		{
			$field->settings = $typeSettings[$field->type];
		}

		if (blx()->fields->saveField($field))
		{
			blx()->userSession->setNotice(Blocks::t('Field saved.'));

			$this->redirectToPostedUrl(array(
				'fieldId' => $field->id,
				'groupId' => $field->groupId,
			));
		}
		else
		{
			blx()->userSession->setError(Blocks::t('Couldn’t save field.'));
		}

		// Reload the original template
		$this->renderRequestedTemplate(array(
			'field' => $field
		));
	}

	/**
	 * Deletes a field.
	 */
	public function actionDeleteField()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$fieldId = blx()->request->getRequiredPost('id');
		$success = blx()->fields->deleteFieldById($fieldId);
		$this->returnJson(array('success' => $success));
	}
}
