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
	////////////////////
	// PROTECTED METHODS
	////////////////////

	/**
	 * @param $view
	 * @param $data
	 *
	 * @return mixed
	 */
	protected function render($view, $data)
	{
		$isAjax = craft()->request->isAjaxRequest();
		$mimeType = craft()->request->getMimeType();

		if (craft()->config->get('devMode') && !craft()->request->isResourceRequest())
		{
			if ($this->showInFireBug)
			{
				if ($isAjax && $this->ignoreAjaxInFireBug)
				{
					return;
				}

				$view .= '-firebug';
			}
			else if(!(craft() instanceof \CWebApplication) || $isAjax)
			{
				return;
			}

			if ($mimeType !== 'text/html')
			{
				return;
			}

			$viewFile = craft()->path->getCpTemplatesPath().'logging/'.$view.'.php';
			include(craft()->findLocalizedFile($viewFile, 'en'));
		}
	}
}
