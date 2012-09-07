<?php
namespace Blocks;

/**
 *
 */
class ProfileLogRoute extends \CProfileLogRoute
{
	/**
	 * @access protected
	 * @param $view
	 * @param $data
	 * @return mixed
	 */
	protected function render($view, $data)
	{
		$isAjax = blx()->request->isAjaxRequest();
		$mimeType = blx()->request->getMimeType();

		if ($this->showInFireBug)
		{
			if ($isAjax && $this->ignoreAjaxInFireBug)
				return;

			$view .= '-firebug';
		}
		else if(!(blx() instanceof \CWebApplication) || $isAjax)
			return;

		if ($mimeType !== 'text/html')
			return;

		$viewFile = blx()->path->getCpTemplatesPath().'logging/'.$view.'.php';
		include(blx()->findLocalizedFile($viewFile,'en'));
	}
}
