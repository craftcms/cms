<?php
namespace Blocks;

/**
 *
 */
class WebLogRoute extends \CWebLogRoute
{

	/**
	 * @access protected
	 * @param $view
	 * @param $data
	 * @return mixed
	 */
	protected function render($view, $data)
	{
		$app = Blocks::app();
		$isAjax = $app->request->isAjaxRequest;

		if ($this->showInFireBug)
		{
			if ($isAjax && $this->ignoreAjaxInFireBug)
				return;

			$view .= '-firebug';
		}
		else if(!($app instanceof \CWebApplication) || $isAjax)
			return;

		$viewFile = Blocks::app()->path->cpTemplatePath.'logging/'.$view.'.php';
		include($app->findLocalizedFile($viewFile,'en'));
	}
}
