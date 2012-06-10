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
		$isAjax = blx()->request->getIsAjaxRequest();
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

		$viewFile = blx()->path->getAppTemplatesPath().'logging/'.$view.'.php';
		include(blx()->findLocalizedFile($viewFile,'en'));
	}
}
