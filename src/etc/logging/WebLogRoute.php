<?php
namespace Craft;

/**
 * Class WebLogRoute
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.logging
 * @since     1.0
 */
class WebLogRoute extends \CWebLogRoute
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc
	 */
	public function processLogs($logs)
	{
		foreach ($logs as &$log)
		{
			$log[0] = LoggingHelper::redact($log[0]);
		}

		parent::processLogs($logs);
	}

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
