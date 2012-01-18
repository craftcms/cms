<?php

/**
 *
 */
class TemplateController extends BaseController
{
	/**
	 * @param $actionId
	 */
	public function run($actionId)
	{
		Blocks::app()->urlManager->processTemplateMatching();
		$templateMatch = Blocks::app()->urlManager->templateMatch;

		// see if we can match a template on the file system.
		if ($templateMatch !== null)
		{
			parent::run($actionId);
			$this->loadTemplate($templateMatch->getRelativePath().'/'.$templateMatch->getFileName());
		}
		else
			throw new bHttpException(404);
	}

	/**
	 * Required
	 */
	public function actionIndex()
	{
	}
}
