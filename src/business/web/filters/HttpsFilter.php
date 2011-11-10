<?php

class HttpsFilter extends CFilter
{
	protected function preFilter($filterChain)
	{
		if (!Blocks::app()->getRequest()->isSecureConnection)
		{
			#Redirect to the secure version of the page.
			$url = 'https://'.Blocks::app()->request->serverName.Blocks::app()->request->requestUri;
			Blocks::app()->request->redirect($url);
			return false;
		}

		return true;
	}
}
