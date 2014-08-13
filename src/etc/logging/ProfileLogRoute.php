<?php
namespace Craft;

/**
 * Class ProfileLogRoute
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.logging
 * @since     1.0
 */
class ProfileLogRoute extends \CProfileLogRoute
{
	// Protected Methods
	// =========================================================================

	/**
	 * @param $view
	 * @param $data
	 *
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
