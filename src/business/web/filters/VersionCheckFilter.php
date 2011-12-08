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

		Blocks::app()->request->getBlocksUpdateInfo(true);
		return true;
	}
}
