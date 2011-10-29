<?php

class UpdateController extends BaseController
{
	public function actionIndex()
	{
		$responseVersionInfo = Blocks::app()->coreRepo->versionCheck();

		if($responseVersionInfo != null)
		{
			$this->render('index', array('model' => $responseVersionInfo));
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

	public function actionResumeUpdate($manifestId, $status)
	{
		if (StringHelper::IsNullOrEmpty($manifestId) || StringHelper::IsNullOrEmpty($status))
			throw new BlocksException('There was a problem updating to the latest Blocks version.  Please try again.');

		try
		{
			$coreUpdater = new CoreUpdater(null, null, null);
			$coreUpdater->resume($manifestId, $status);
		}
		catch (BlocksException $ex)
		{
			Blocks::app()->user->setFlash('error', $ex->getMessage());
			$this->redirect('index');
		}

		Blocks::app()->user->setFlash('notice', 'Update Successful!');
		$this->redirect('index');
	}

	public function actionUpdaterUpdate($manifestId)
	{
		if (StringHelper::IsNullOrEmpty($manifestId) || StringHelper::IsNullOrEmpty($manifestId))
			throw new BlocksException('There was a problem updating to the latest Blocks version.  Please try again.');

		try
		{
			$updateUpdater = new UpdateUpdater($manifestId);
			$updateUpdater->start();
		}
		catch (BlocksException $ex)
		{
			Blocks::app()->user->setFlash('error', $ex->getMessage());
		}

		$this->redirect('index');
	}
}
