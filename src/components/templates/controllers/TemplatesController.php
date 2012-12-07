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

			if ($path != blx()->user->loginUrl && !($path == blx()->account->getAccountVerificationPath() && blx()->request->getParam('code')))
			{
				$this->requireLogin();
			}
		}

		$this->renderRequestedTemplate();
	}
}
