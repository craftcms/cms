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

		$groupPackage = new UserGroupPackage();
		$groupPackage->id = blx()->request->getPost('groupId');
		$groupPackage->name = blx()->request->getPost('name');
		$groupPackage->handle = blx()->request->getPost('handle');

		// Did it save?
		if ($groupPackage->save())
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
			'group' => $groupPackage
		));
	}

	/**
	 * Deletes a user group.
	 */
	public function actionDeleteGroup()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$groupId = blx()->request->getRequiredPost('groupId');

		blx()->userGroups->deleteGroupById($groupId);

		$this->returnJson(array('success' => true));
	}
}
