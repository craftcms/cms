<?php
namespace Blocks;

/**
 *
 */
class HttpsFilter extends \CFilter
{
	/**
	 * @access protected
	 * @param $filterChain
	 * @return bool
	 */
	protected function preFilter($filterChain)
	{
		if (!Blocks::app()->request->isSecureConnection)
		{
			// Redirect to the secure version of the page.
			$url = 'https://'.Blocks::app()->request->serverName.Blocks::app()->request->requestUri;
			Blocks::app()->request->redirect($url);
			return false;
		}

		return true;
	}
}
