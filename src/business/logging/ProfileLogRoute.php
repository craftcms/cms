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
		$isAjax = b()->request->getIsAjaxRequest();
		$mimeType = b()->request->getMimeType();

		if ($this->showInFireBug)
		{
			if ($isAjax && $this->ignoreAjaxInFireBug)
				return;

			$view .= '-firebug';
		}
		else if(!(b() instanceof \CWebApplication) || $isAjax)
			return;

		if ($mimeType !== 'text/html')
			return;

		$viewFile = b()->path->getAppTemplatesPath().'logging/'.$view.'.php';
		include(b()->findLocalizedFile($viewFile,'en'));
	}
}
