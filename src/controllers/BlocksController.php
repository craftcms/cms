<?php

class BlocksController extends BaseController
{
	private $_templateMatch = null;

	public function run($actionId)
	{
		$this->_templateMatch = Blocks::app()->urlManager->getTemplateMatch();

		if ($this->_templateMatch !== null || Blocks::app()->request->getParam('c', null) !== null)
		{
			if ($this->_templateMatch !== null)
			{
				$tempController = $this->_templateMatch->getRelativePath();
				$tempAction = $this->_templateMatch->getFileName();
			}
			else
			{
				$tempController = Blocks::app()->request->getParam('c');
				$pathSegs = Blocks::app()->request->getPathSegments();
				$tempAction = $pathSegs[0];
			}

			// we found a matching controller for this request.
			if (($ca = Blocks::app()->createController($tempController)) !== null)
			{
				$this->setRequestController($ca[0]);
				// save the current controller and swap out the new one.
				$oldController = Blocks::app()->getController();
				Blocks::app()->setController($this->getRequestController());

				// there is an explicit request to a controller and action
				if (Blocks::app()->request->getParam('c', null) !== null || (($module = Blocks::app()->urlManager->getCurrentModule()) !== null && $module->getId() == 'install') || Blocks::app()->controller->id == 'update')
				{
					Blocks::app()->controller->init();
					Blocks::app()->controller->run($tempAction);
				}
				else
				{
					// controller request, but no action specified, so load the template.
					$this->loadTemplate($tempAction);
				}

				Blocks::app()->setController($oldController);
			}
			// no matching controller, so load the template.
			else
			{
				parent::run($actionId);
				$this->loadTemplate($tempController.'/'.$tempAction);
			}
		}
		else
		{
			throw new BlocksHttpException('404', 'Page not found.');
		}
	}

	// required
	public function actionIndex()
	{
	}
}
