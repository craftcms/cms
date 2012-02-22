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
		// Require user to be logged in on every page but /login in the control panel
		if (Blocks::app()->request->mode == RequestMode::CP)
			if (Blocks::app()->request->path !== 'login')
					$this->requireLogin();

		$this->loadRequestedTemplate();
	}
}
