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
		$app = b();
		$isAjax = $app->request->isAjaxRequest;

		if ($this->showInFireBug)
		{
			if ($isAjax && $this->ignoreAjaxInFireBug)
				return;

			$view .= '-firebug';
		}
		else if(!($app instanceof \CWebApplication) || $isAjax)
			return;

		$viewFile = b()->path->cpTemplatePath.'logging/'.$view.'.php';
		include($app->findLocalizedFile($viewFile,'en'));
	}
}
