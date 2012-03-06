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
	public function init()
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
		$siteId = b()->request->getPost('site_id');
		if ($siteId)
			$site = b()->sites->getSiteById($siteId);

		// Otherwise create a new site
		if (empty($site))
			$site = new Site;

		$site->name = b()->request->getPost('name');
		$site->handle = b()->request->getPost('handle');
		$site->url = b()->request->getPost('url');

		if ($site->save())
		{
			b()->user->setMessage(MessageStatus::Success, 'Site saved successfully.');

			$url = b()->request->getPost('redirect');
			if ($url !== null)
				$this->redirect($url);
		}

		$this->loadRequestedTemplate(array('site' => $site));
	}
}
