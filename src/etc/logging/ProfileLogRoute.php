<?php
namespace Craft;

/**
 * Class ProfileLogRoute
 *
 * @package craft.app.etc.logging
 */
class ProfileLogRoute extends \CProfileLogRoute
{
	/**
	 * @param $view
	 * @param $data
	 * @return mixed
	 */
	protected function render($view, $data)
	{
		if (
			!craft()->isConsole() &&
			!craft()->request->isResourceRequest() &&
			!craft()->request->isAjaxRequest() &&
			craft()->config->get('devMode') &&
			in_array(HeaderHelper::getMimeType(), array('text/html', 'application/xhtml+xml'))
		)
		{
			$viewFile = craft()->path->getCpTemplatesPath().'logging/'.$view.'-firebug.php';
			include(craft()->findLocalizedFile($viewFile, 'en'));
		}
	}
}
