<?php
namespace Craft;

/**
 * Class WebLogRoute
 *
 * @package craft.app.etc.logging
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
		if (
			!craft()->isConsole() &&
			!craft()->request->isResourceRequest() &&
			!craft()->request->isAjaxRequest() &&
			craft()->config->get('devMode') &&
			in_array(HeaderHelper::getMimeType(), array('text/html', 'application/xhtml+xml'))
		)
		{
			if (($userAgent = craft()->request->getUserAgent()) !== null && preg_match('/msie [5-9]/i', $userAgent))
			{
				echo '<script type="text/javascript">';
				echo IOHelper::getFileContents((IOHelper::getFolderName(__FILE__).'/../vendors/console-normalizer/normalizeconsole.min.js'));
				echo "</script>\n";
			}
			else
			{
				$viewFile = craft()->path->getCpTemplatesPath().'logging/'.$view.'-firebug.php';
				include(craft()->findLocalizedFile($viewFile, 'en'));
			}
		}
	}
}
