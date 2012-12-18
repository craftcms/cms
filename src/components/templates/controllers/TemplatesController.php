<?php
namespace Blocks;

/**
 *
 */
class TemplatesController extends BaseController
{
	public $allowAnonymous = array('actionIndex', 'actionOffline');

	/**
	 * Required
	 */
	public function actionIndex()
	{
		if (blx()->request->isCpRequest())
		{
			// Make sure the user has access to the CP
			blx()->user->requireLogin();
			blx()->user->requirePermission('accessCp');
		}

		$this->renderRequestedTemplate();
	}
}
