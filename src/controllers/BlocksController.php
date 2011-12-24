<?php

class BlocksController extends BaseController
{
	private $_templateMatch = null;

	public function run($actionId)
	{
		// if there is a C && A in the URL params, attempt to create that controller and action
		// if there is a template match, then just use that.
		$requestController = Blocks::app()->request->getParam('c', null);
		$requestAction = Blocks::app()->request->getParam('a', null);
//		$requestModule = Blocks::app()->request->getParam('m', null);

//		$parent = Blocks::app();
//		if (Blocks::app()->urlManager->currentModule !== null)
//		{
//			$parent = Blocks::app()->urlManager->currentModule;
//			$requestController
//		}

		if ($requestController !== null && $requestAction !== null)
			{
				// we found a matching controller for this request.
				if (($ca = Blocks::app()->createController($requestController)) !== null)
				{
					$newController = $ca[0];

					if (($action = $newController->createAction($requestAction)) !== null)
					{
						$this->setRequestController($newController);

						$newController->init();

						if (($parent = $newController->getModule()) === null)
							$parent = Blocks::app();

						if ($parent->beforeControllerAction($newController, $action))
						{
							$newController->runActionWithFilters($action, $newController->filters());
							$parent->afterControllerAction($newController, $action);
						}
					}
				}
			}
			else
			{
				$this->_templateMatch = Blocks::app()->urlManager->getTemplateMatch();

				if ($this->_templateMatch !== null)
				{
					parent::run($actionId);
					$this->loadTemplate($this->_templateMatch->getRelativePath().'/'.$this->_templateMatch->getFileName());

					//$tempController = $this->_templateMatch->getRelativePath();
					//$tempAction = $this->_templateMatch->getFileName();
				}
				else
				{
					//$tempController = Blocks::app()->request->getParam('c');
					//$pathSegs = Blocks::app()->request->getPathSegments();
					//$tempAction = $pathSegs[0];
				}
			}


		// save the current controller and swap out the new one.
		// $oldController = Blocks::app()->getController();
		// Blocks::app()->setController($newController);

		//	else
		//	{
		// controller request, but no action specified, so load the template.
		//		$this->loadTemplate($tempAction);
		//	}
		//	Blocks::app()->setController($oldController);
		//}
	//}
	}

	// required
	public function actionIndex()
	{
	}
}
