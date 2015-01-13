<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\logging;

use Craft;
use craft\app\helpers\HeaderHelper;
use craft\app\helpers\IOHelper;

/**
 * Class WebLogRoute
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class WebLogRoute extends \CWebLogRoute
{
	// Public Methods
	// =========================================================================

	/**
	 * @param $view
	 * @param $data
	 *
	 * @return mixed
	 */
	protected function render($view, $data)
	{
		$request = Craft::$app->getRequest();
		if (
			!$request->getIsConsoleRequest() &&
			!$request->getIsResourceRequest() &&
			!$request->getIsAjax() &&
			Craft::$app->config->get('devMode') &&
			in_array(HeaderHelper::getMimeType(), ['text/html', 'application/xhtml+xml'])
		)
		{
			if (($userAgent = Craft::$app->request->getUserAgent()) !== null && preg_match('/msie [5-9]/i', $userAgent))
			{
				echo '<script type="text/javascript">';
				echo IOHelper::getFileContents((IOHelper::getFolderName(__FILE__).'/../vendors/console-normalizer/normalizeconsole.min.js'));
				echo "</script>\n";
			}
			else
			{
				$viewFile = Craft::$app->path->getCpTemplatesPath().'logging/'.$view.'-firebug.php';
				include(Craft::$app->findLocalizedFile($viewFile, 'en'));
			}
		}
	}
}
