<?php
namespace Blocks;

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
			$template = $templateMatch->getRelativePath().'/'.$templateMatch->getFileName();
			$this->loadTemplate($template, Blocks::app()->urlManager->templateTags);

		}
		else
			throw new HttpException(404);
	}

	/**
	 * Required
	 */
	public function actionIndex()
	{
	}
}
