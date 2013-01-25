<?php
namespace Blocks;

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
		if (blx()->request->isCpRequest() &&
			// The only time we'll allow anonymous access to the CP is in the middle of a manual update.
			// (We'll allow access to updates/go/blocks?manual=1)
			!(
				blx()->request->getPath() == 'updates/go/blocks' &&
				blx()->request->getParam('manual', null) == 1
			)
		)
		{
			// Make sure the user has access to the CP
			blx()->userSession->requireLogin();
			blx()->userSession->requirePermission('accessCp');

			// If they're accessing a plugin's section, make sure that they have permission to do so
			$firstSeg = blx()->request->getSegment(1);
			if ($firstSeg)
			{
				$plugin = $plugin = blx()->plugins->getPlugin($firstSeg);
				if ($plugin)
				{
					blx()->userSession->requirePermission('accessPlugin-'.$plugin->getClassHandle());
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
		if (blx()->request->isSiteRequest())
		{
			if (!IOHelper::fileExists(blx()->path->getSiteTemplatesPath().'offline.html'))
			{
				// Set PathService to use the CP templates path instead
				blx()->path->setTemplatesPath(blx()->path->getCpTemplatesPath());
			}
		}

		// Output the offline template
		$this->renderTemplate('offline');
	}
}
