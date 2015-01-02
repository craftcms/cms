<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\logging;

use craft\app\helpers\HeaderHelper;

/**
 * Class ProfileLogRoute
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
