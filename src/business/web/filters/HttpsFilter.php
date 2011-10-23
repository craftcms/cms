<?php

class HttpsFilter extends CFilter
{
	protected function preFilter($filterChain)
	{
		if (!Blocks::app()->getRequest()->isSecureConnection)
		{
			#Redirect to the secure version of the page.
			$url = 'https://'.Blocks::app()->getRequest()->serverName.Blocks::app()->getRequest()->requestUri;
			Blocks::app()->request->redirect($url);
			return false;
		}

		return true;
	}
}
