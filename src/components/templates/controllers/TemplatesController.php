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
			blx()->userSession->requireLogin();
			blx()->userSession->requirePermission('accessCp');
		}

		$this->renderRequestedTemplate();
	}
}
