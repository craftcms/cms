<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\logging;

use Craft;
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
			!Craft::$app->getRequest()->getIsConsoleRequest() &&
			!Craft::$app->request->isResourceRequest() &&
			!Craft::$app->request->isAjaxRequest() &&
			Craft::$app->config->get('devMode') &&
			in_array(HeaderHelper::getMimeType(), ['text/html', 'application/xhtml+xml'])
		)
		{
			$viewFile = Craft::$app->path->getCpTemplatesPath().'logging/'.$view.'-firebug.php';
			include(Craft::$app->findLocalizedFile($viewFile, 'en'));
		}
	}
}
