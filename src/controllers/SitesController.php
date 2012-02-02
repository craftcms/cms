<?php
namespace Blocks;

/**
 * Handles site management tasks
 */
class SitesController extends BaseController
{
	/**
	 * Saves a site
	 */
	public function actionSave()
	{
		$this->requirePostRequest();

		// Are we editing an existing site?
		$postSiteId = Blocks::app()->request->getPost('site_id');
		if ($postSiteId)
			$site = Site::model()->findByPk($postSiteId);

		// Otherwise create a new site
		if (empty($site))
			$site = new Site;

		$postSite = Blocks::app()->request->getPost('site');
		$site->name = $postSite['name'];
		$site->handle = $postSite['handle'];
		$site->url = $postSite['url'];

		if ($site->save())
		{
			Blocks::app()->user->setMessage(MessageStatus::Success, 'Site saved successfully.');

			$url = Blocks::app()->request->getPost('redirect');
			if ($url !== null)
				$this->redirect($url);
		}

		$this->loadRequestedTemplate(array('site' => $site));
	}
}
