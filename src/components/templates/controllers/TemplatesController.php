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

	/**
	 * Display the offline template.
	 */
	public function actionOffline()
	{
		if (($path = blx()->config->offlinePath) !== null)
		{
			$templateName = IOHelper::getFileName($path, false);
		}
		else
		{
			$templateName = '_offline';
		}

		// Temporarily swap the templates path
		$originalTemplatesPath = blx()->path->getTemplatesPath();
		blx()->path->setTemplatesPath(blx()->path->getOfflineTemplatePath());

		$this->renderTemplate($templateName, array(), false);

		// Set it back to the original
		blx()->path->setTemplatesPath($originalTemplatesPath);
		blx()->setViewPath(null);
	}
}
