<?php
namespace Craft;

/**
 *
 */
class TemplatesController extends BaseController
{
	public $allowAnonymous = array('actionIndex', 'actionOffline');

	/**
	 * Required
	 */
	public function actionIndex()
	{
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

		$this->renderRequestedTemplate();
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
	 * @return bool
	 */
	private function _isValidManualUpdatePath()
	{
		// Is this a manual Craft update?
		if (craft()->request->getPath() == 'updates/go/craft' && craft()->request->getParam('manual', null) == 1)
		{
			// Extra check in case someone manually comes to the url.
			if (craft()->updates->isCraftDbUpdateNeeded())
			{
				return true;
			}
		}

		// Is this a manual plugin update?
		$segments = craft()->request->getSegments();
		if (count($segments) == 3 && $segments[0] == 'updates' && $segments[1] == 'go' && craft()->request->getParam('manual', null) == 1)
		{
			if (($plugin = craft()->plugins->getPlugin($segments[2])) !== null)
			{
				// Extra check in case someone manually comes to the url.
				if (craft()->plugins->doesPluginRequireDatabaseUpdate($plugin))
				{
					return true;
				}
			}
		}

		return false;
	}
}
