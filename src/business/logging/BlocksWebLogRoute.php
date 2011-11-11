<?php

class BlocksWebLogRoute extends CWebLogRoute
{

	protected function render($view, $data)
	{
		$app = Blocks::app();
		$isAjax = $app->request->getIsAjaxRequest();

		if ($this->showInFireBug)
		{
			if ($isAjax && $this->ignoreAjaxInFireBug)
				return;

			$view .= '-firebug';
		}
		else if(!($app instanceof CWebApplication) || $isAjax)
			return;

		$viewFile = Blocks::app()->path->getCPTemplatePath().'logging/'.$view.'.php';
		include($app->findLocalizedFile($viewFile,'en'));
	}
}
