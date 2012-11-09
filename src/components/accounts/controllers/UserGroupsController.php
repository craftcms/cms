<?php
namespace Blocks;

/**
 * Handles user group tasks
 */
class UserGroupsController extends BaseController
{
	/**
	 * Saves a user group.
	 */
	public function actionSaveGroup()
	{
		$this->requirePostRequest();

		$group = new UserGroupModel();
		$group->id = blx()->request->getPost('groupId');
		$group->name = blx()->request->getPost('name');
		$group->handle = blx()->request->getPost('handle');

		// Did it save?
		if (blx()->userGroups->saveGroup($group))
		{
			blx()->user->setNotice(Blocks::t('Group saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save group.'));
		}

		// Reload the original template
		$this->renderRequestedTemplate(array(
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

		$groupId = blx()->request->getRequiredPost('id');

		blx()->userGroups->deleteGroupById($groupId);

		$this->returnJson(array('success' => true));
	}
}
