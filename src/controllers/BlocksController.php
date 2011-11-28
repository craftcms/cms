<?php

class BlocksController extends BaseController
{
	private $_templateMatch = null;

	public function run($actionId)
	{
		$this->_templateMatch = Blocks::app()->urlManager->getTemplateMatch();

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
			$newController = $this->getRequestController();
			Blocks::app()->setController($newController);

			if (($action = $newController->createAction($tempAction)) !== null)
			{
				if (($parent = $newController->getModule()) === null)
					$parent = Blocks::app();

				if ($parent->beforeControllerAction($newController, $action))
				{
					$newController->runActionWithFilters($action, $newController->filters());
					$parent->afterControllerAction($newController, $action);
				}
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

	// required
	public function actionIndex()
	{
	}
}
