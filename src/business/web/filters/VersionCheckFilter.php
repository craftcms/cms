<?php

class VersionCheckFilter extends CFilter
{
	protected function preFilter($filterChain)
	{
		// Only run on the CP side.
		if (Blocks::app()->request->getCMSRequestType() == RequestType::Site)
			return true;

		if (Blocks::app()->controller->id == 'update')
			return true;

		// Don't execute this if we're already in the install module on the default controller.
		if (Blocks::app()->controller->getModule() !== null)
			if (Blocks::app()->controller->getModule()->id == 'install' && Blocks::app()->controller->id == 'default')
				return true;

		if (Blocks::app()->controller->id == 'site' && Blocks::app()->controller->action->id == 'error')
			return true;

		if (($keys = Blocks::app()->site->getSiteLicenseKeys()) == null || empty($keys))
		{
			$blocksUpdateInfo['blocksLicenseStatus'] = LicenseKeyStatus::MissingKey;
			Blocks::app()->request->setBlocksUpdateInfo($blocksUpdateInfo);
			return true;
		}

		if (Blocks::app()->config('devMode'))
		{
			Blocks::app()->fileCache->delete('blocksUpdateInfo');
			$blocksUpdateInfo = Blocks::app()->site->versionCheck();
		}
		else
		{
			$blocksUpdateInfo = Blocks::app()->fileCache->get('blocksUpdateInfo');
			if ($blocksUpdateInfo === false)
			{
				$blocksUpdateInfo = Blocks::app()->site->versionCheck();
				// set cache expiry to 24 hours. 86400 seconds.
				Blocks::app()->fileCache->set('blocksUpdateInfo', $blocksUpdateInfo, 86400);
			}
		}

		if ($blocksUpdateInfo !== null)
			Blocks::app()->request->setBlocksUpdateInfo($blocksUpdateInfo);

		return true;
	}
}
