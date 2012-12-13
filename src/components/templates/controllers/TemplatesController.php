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
			// Require user to be logged in on every page but /login and /verify
			$path = blx()->request->getPath();

			if ($path != blx()->user->loginUrl && !($path == blx()->accounts->getAccountVerificationPath() && blx()->request->getParam('code')))
			{
				$this->requireLogin();

				// Make sure the user has access to the CP
				blx()->user->requirePermission('accessCp');
			}
		}

		$this->renderRequestedTemplate();
	}
}
