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

		$siteId = b()->request->getPost('site_id');

		$siteSettings['name']     = b()->request->getPost('name');
		$siteSettings['handle']   = b()->request->getPost('handle');
		$siteSettings['url']      = b()->request->getPost('url');
		$siteSettings['language'] = b()->request->getPost('language');

		$site = b()->sites->saveSite($siteSettings, $siteId);

		if (!$site->errors)
		{
			b()->user->setMessage(MessageType::Notice, 'Site saved.');
			$this->redirectToPostedUrl();
		}
		else
		{
			b()->user->setMessage(MessageType::Error, 'Couldn’t save site.');
		}

		$this->loadRequestedTemplate(array('site' => $site));
	}
}
