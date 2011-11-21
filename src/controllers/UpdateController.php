<?php

class UpdateController extends BaseController
{
	public function actionIndex()
	{
		$responseVersionInfo = Blocks::app()->site->versionCheck();

		if($responseVersionInfo != null)
		{
			$this->loadTemplate('update/index', array('model' => $responseVersionInfo));
		}
	}

	public function actionCoreUpdate()
	{
		if (!Blocks::app()->request->isPostRequest)
			$this->redirect('index');

		if (Blocks::app()->request->getPost('blocksLatestVersionNo', null) === null || Blocks::app()->request->getPost('blocksLatestBuildNo', null) === null)
			throw new BlocksException('There was a problem updating to the latest Blocks version.  Please try again.');

		$latestVersionNumber = Blocks::app()->request->getPost('blocksLatestVersionNo');
		$latestBuildNumber = Blocks::app()->request->getPost('blocksLatestBuildNo');

		try
		{
			$coreUpdater = new CoreUpdater($latestVersionNumber, $latestBuildNumber, Blocks::getEdition());
			if ($coreUpdater->start())
				Blocks::app()->user->setFlash('notice', 'Update Successful!');

			$this->redirect('index');
		}
		catch (BlocksException $ex)
		{
			Blocks::app()->user->setFlash('error', $ex->getMessage());
			$this->redirect('index');
		}
	}
}
