<?php
namespace Blocks;

/**
 *
 */
class TemplateController extends Controller
{
	/**
	 * Required
	 */
	public function actionIndex()
	{
		// Require user to be logged in on every page but /login in the control panel and account/password with an activation code
		if (b()->request->mode == RequestMode::CP)
		{
			$path = b()->request->path;
			if ($path !== 'login')
				if ($path !== b()->users->verifyAccountUrl && b()->request->getParam('code', null) == null)
					$this->requireLogin();
		}

		$this->loadRequestedTemplate();
	}

	/**
	 * Display the offline template.
	 */
	public function actionOffline()
	{
		if (($path = b()->config->offlinePath) !== null)
			$templateName = pathinfo($path, PATHINFO_FILENAME);
		else
			$templateName = '_offline';

		b()->setViewPath(b()->path->offlineTemplatePath);
		$this->loadTemplate($templateName, array(), false);
		b()->setViewPath(null);
	}
}
