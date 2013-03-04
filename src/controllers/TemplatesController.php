<?php
namespace Craft;

/**
 *
 */
class TemplatesController extends BaseController
{
	public $allowAnonymous = array('actionRender', 'actionOffline');

	private $_template;
	/* HIDE */
	//private $_templateSegments;
	/* end HIDE */
	private $_manualUpdateTemplate = 'updates/_go';

	/**
	 * Required
	 */
	public function actionRender($template, array $variables = array())
	{
		$this->_template = $template;
		/* HIDE */
		//$this->_templateSegments = array_filter(explode('/', $this->_template));
		/* end HIDE */

		if (craft()->request->isCpRequest() &&
			// The only time we'll allow anonymous access to the CP is in the middle of a manual update.
			!($this->_isValidManualUpdatePath())
		)
		{
			// Make sure the user has access to the CP
			craft()->userSession->requireLogin();
			craft()->userSession->requirePermission('accessCp');

			// If they're accessing a plugin's section, make sure that they have permission to do so
			$firstSeg = craft()->request->getSegment(1);
			if ($firstSeg)
			{
				$plugin = $plugin = craft()->plugins->getPlugin($firstSeg);
				if ($plugin)
				{
					craft()->userSession->requirePermission('accessPlugin-'.$plugin->getClassHandle());
				}
			}
		}

		try
		{
			$output = $this->renderTemplate($this->_template, $variables, true);

			// Set the Content-Type header
			$mimeType = craft()->request->getMimeType();
			header('Content-Type: '.$mimeType.'; charset=utf-8');

			// Output to the browser!
			echo $output;

			// End the request
			craft()->end();
		}
		catch (TemplateLoaderException $e)
		{
			if ($e->template == $this->_template)
			{
				throw new HttpException(404);
			}
			else
			{
				throw $e;
			}
		}
	}

	/**
	 * Shows the 'offline' template.
	 */
	public function actionOffline()
	{
		// If this is a site request, make sure the offline template exists
		if (craft()->request->isSiteRequest())
		{
			if (!IOHelper::fileExists(craft()->path->getSiteTemplatesPath().'offline.html'))
			{
				// Set PathService to use the CP templates path instead
				craft()->path->setTemplatesPath(craft()->path->getCpTemplatesPath());
			}
		}

		// Output the offline template
		$this->renderTemplate('offline');
	}

	/**
	 * Renders the Manual Update notification template.
	 */
	public function actionManualUpdateNotification()
	{
		$this->actionRender('_special/dbupdate');
	}

	/**
	 * Renders the Manual Update template.
	 */
	public function actionManualUpdate()
	{
		$this->actionRender($this->_manualUpdateTemplate, array(
			'handle' => craft()->request->getSegment(2)
		));
	}

	/**
	 * Renders the Breakpoint Update notification template.
	 */
	public function actionBreakpointUpdateNotification()
	{
		$this->actionRender('_special/breakpointupdate', array(
			'minBuild'      => CRAFT_MIN_BUILD_REQUIRED,
			'targetVersion' => BLOCKS_VERSION,
			'targetBuild'   => BLOCKS_BUILD
		));
	}

	/**
	 * @return bool
	 */
	private function _isValidManualUpdatePath()
	{
		// Is this a manual Craft update?
		if ($this->_template == $this->_manualUpdateTemplate && craft()->request->getParam('manual', null) == 1)
		{
			// Extra check in case someone manually comes to the url.
			if (craft()->updates->isCraftDbUpdateNeeded())
			{
				return true;
			}
		}

		/* HIDE */
		// Is this a manual plugin update?
		/*if (count($this->_templateSegments) == 3 && $this->_templateSegments[0] == 'updates' && $this->_templateSegments[1] == 'go' && craft()->request->getParam('manual', null) == 1)
		{
			if (($plugin = craft()->plugins->getPlugin($this->_templateSegments[2])) !== null)
			{
				// Extra check in case someone manually comes to the url.
				if (craft()->plugins->doesPluginRequireDatabaseUpdate($plugin))
				{
					return true;
				}
			}
		}*/
		/* end HIDE */

		return false;
	}
}
