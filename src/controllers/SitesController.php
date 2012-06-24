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

		$siteId = blx()->request->getPost('site_id');

		$siteSettings['name']     = blx()->request->getPost('name');
		$siteSettings['handle']   = blx()->request->getPost('handle');
		$siteSettings['url']      = blx()->request->getPost('url');
		$siteSettings['language'] = blx()->request->getPost('language');

		$site = blx()->sites->saveSite($siteSettings, $siteId);

		if (!$site->errors)
		{
			blx()->user->setMessage(MessageType::Notice, 'Site saved.');
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->user->setMessage(MessageType::Error, 'Couldn’t save site.');
		}

		$this->loadRequestedTemplate(array('site' => $site));
	}
}
