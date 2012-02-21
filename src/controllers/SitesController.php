<?php
namespace Blocks;

/**
 * Handles site management tasks
 */
class SitesController extends BaseController
{
	/**
	 * All site actions require the user to be logged in
	 */
	public function run()
	{
		$this->requireLogin();
	}

	/**
	 * Saves a site
	 */
	public function actionSave()
	{
		$this->requirePostRequest();

		// Are we editing an existing site?
		$siteId = Blocks::app()->request->getPost('site_id');
		if ($siteId)
			$site = Blocks::app()->sites->getSiteById($siteId);

		// Otherwise create a new site
		if (empty($site))
			$site = new Site;

		$site->name = Blocks::app()->request->getPost('name');
		$site->handle = Blocks::app()->request->getPost('handle');
		$site->url = Blocks::app()->request->getPost('url');

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
