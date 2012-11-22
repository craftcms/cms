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
		// Require user to be logged in on every page but /login in the control panel and account/password with a verification code
		if (blx()->request->isCpRequest())
		{
			$path = blx()->request->getPath();

			if ($path !== 'login')
			{
				if ($path !== blx()->account->accountVerificationPath && blx()->request->getParam('code', null) == null)
				{
					$this->requireLogin();
				}
			}
		}

		$this->renderRequestedTemplate();
	}
}
