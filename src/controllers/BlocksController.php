<?php

/**
 *
 */
class BlocksController extends BaseController
{
	/**
	 * @param $actionId
	 */
	public function run($actionId)
	{
		if (Blocks::app()->mode == AppMode::Action)
		{
			if (!isset(Blocks::app()->request->pathSegments[2]))
				throw new BlocksHttpException(404);

			// requestHandle will either be 'app' or {pluginHandle}
			$requestHandle = Blocks::app()->request->pathSegments[1];
			$requestController = Blocks::app()->request->pathSegments[2];

			if (isset(Blocks::app()->request->pathSegments[2]))
				$requestAction = Blocks::app()->request->pathSegments[3];
			else
				$requestAction = 'index';

			// we found a matching controller for this request.
			if (($ca = Blocks::app()->createController($requestController)) !== null)
			{
				$newController = $ca[0];

				if (($action = $newController->createAction($requestAction)) !== null)
				{
					$this->setRequestController($newController);

					$newController->init();

					if (($parent = $newController->module) === null)
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
			// see if we can match a template on the file system.
			if (($templateMatch = Blocks::app()->urlManager->templateMatch) !== null)
			{
				parent::run($actionId);
				$this->loadTemplate($templateMatch->getRelativePath().'/'.$templateMatch->getFileName());
			}
		}

//		$requestModule = Blocks::app()->request->getParam('m', null);

//		$parent = Blocks::app();
//		if (Blocks::app()->urlManager->currentModule !== null)
//		{
//			$parent = Blocks::app()->urlManager->currentModule;
//			$requestController
//		}
	}

	/**
	 * Required
	 */
	public function actionIndex()
	{
	}
}
