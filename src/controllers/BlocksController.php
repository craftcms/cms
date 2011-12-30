<?php

class BlocksController extends BaseController
{
	private $_templateMatch = null;

	public function run($actionId)
	{
		if (Blocks::app()->request->getCMSRequestType() == RequestType::Controller)
		{
			$requestController = null;
			$requestAction = null;

			// requestHandle will either be 'app' or {pluginHandle}
			if (isset(Blocks::app()->request->pathSegments[1]))
				$requestHandle = Blocks::app()->request->pathSegments[1];

			if (isset(Blocks::app()->request->pathSegments[2]))
				$requestController = Blocks::app()->request->pathSegments[2];

			if (isset(Blocks::app()->request->pathSegments[3]))
				$requestAction = Blocks::app()->request->pathSegments[3];

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
			// else can't find module/controller/action try index?  404?
		}
		else
		{
			// see if we can match a template on the file system.
			$this->_templateMatch = Blocks::app()->urlManager->getTemplateMatch();

			if ($this->_templateMatch !== null)
			{
				parent::run($actionId);
				$this->loadTemplate($this->_templateMatch->getRelativePath().'/'.$this->_templateMatch->getFileName());
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

	// required
	public function actionIndex()
	{
	}

	/**
	 * Convert all template vars to tags before sending them to the template
	 */
	public function loadTemplate($templatePath, $data = array(), $return = false)
	{
		if (!is_array($data))
			$data = array();

		foreach ($data as &$tag)
		{
			$tag = TemplateHelper::getVarTag($tag);
		}

		return parent::loadTemplate($templatePath, $data, $return);
	}
}
