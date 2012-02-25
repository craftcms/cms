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
		if (Blocks::app()->request->mode == RequestMode::CP)
		{
			$path = Blocks::app()->request->path;
			if ($path !== 'login')
				if ($path !== Blocks::app()->users->verifyAccountUrl && Blocks::app()->request->getParam('code', null) == null)
					if ($path !== Blocks::app()->users->forgotPasswordUrl)
						$this->requireLogin();
		}

		$this->loadRequestedTemplate();
	}
}
