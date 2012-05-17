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
		if (!b()->request->getIsSecureConnection())
		{
			// Redirect to the secure version of the page.
			$url = 'https://'.b()->request->getServerName().b()->request->getRequestUri();
			b()->request->redirect($url);
			return false;
		}

		return true;
	}
}
