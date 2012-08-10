<?php
namespace Blocks;

/**
 *
 */
class TemplateController extends BaseController
{
	/**
	 * Required
	 */
	public function actionIndex()
	{
		// Require user to be logged in on every page but /login in the control panel and account/password with an activation code
		if (blx()->request->getMode() == RequestMode::CP)
		{
			$path = blx()->request->getPath();
			if ($path !== 'login')
				if ($path !== blx()->users->getVerifyAccountUrl() && blx()->request->getParam('code', null) == null)
					$this->requireLogin();
		}

		$this->renderRequestedTemplate();
	}

	/**
	 * Display the offline template.
	 */
	public function actionOffline()
	{
		if (($path = blx()->config->offlinePath) !== null)
			$templateName = pathinfo($path, PATHINFO_FILENAME);
		else
			$templateName = '_offline';

		blx()->setViewPath(blx()->path->getOfflineTemplatePath());
		$this->renderTemplate($templateName, array(), false);
		blx()->setViewPath(null);
	}
}
