<?php
namespace Craft;

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

				if (($userAgent = craft()->request->getUserAgent()) !== null && preg_match('/msie [5-9]/i', $userAgent))
				{
					echo '<script type="text/javascript">';
					echo IOHelper::getFileContents((IOHelper::getFolderName(__FILE__).'/../vendors/console-normalizer/normalizeconsole.min.js'));
					echo "</script>\n";
				}
			}
			else if (!(craft() instanceof \CWebApplication) || $isAjax)
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
