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
		if (blx()->request->isCpRequest())
		{
			// Make sure the user has access to the CP
			blx()->userSession->requireLogin();
			blx()->userSession->requirePermission('accessCp');
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
