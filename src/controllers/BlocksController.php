<?php

/**
 *
 */
class BlocksController extends BaseController
{
	/**
	 * @access public
	 *
	 * @param $actionId
	 */
	public function run($actionId)
	{
		if (Blocks::app()->mode == AppMode::Action)
		{
			$requestController = null;
			$requestAction = null;

			// requestHandle will either be 'app' or {pluginHandle}
			// pathInfo format.
			if (Blocks::app()->request->isServerPathInfoRequest)
			{
				if (isset(Blocks::app()->request->pathSegments[1]))
					$requestHandle = Blocks::app()->request->pathSegments[1];

				if (isset(Blocks::app()->request->pathSegments[2]))
					$requestController = Blocks::app()->request->pathSegments[2];

				if (isset(Blocks::app()->request->pathSegments[3]))
					$requestAction = Blocks::app()->request->pathSegments[3];

				if ($requestAction == null)
					$requestAction = $actionId;
			}
			else
			{
				// queryString format.
				if (($path = Blocks::app()->request->getParam(Blocks::app()->config('pathVar'), null)) !== null)
				{
					$pathSegs = explode('/', $path);
					if (isset($pathSegs[1]))
						$requestHandle = $pathSegs[1];

					if (isset($pathSegs[2]))
						$requestController = $pathSegs[2];

					if (isset($pathSegs[3]))
						$requestAction = $pathSegs[3];

					if ($requestAction == null)
						$requestAction = 'index';
				}
			}

			if ($requestController !== null && $requestAction !== null) // and requestHandle == app
			{
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
			// else can't find module/controller/action try index?  404?
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
	 * @access public
	 *
	 * Required
	 */
	public function actionIndex()
	{
	}
}
