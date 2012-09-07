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
		if (!blx()->request->isSecureConnection())
		{
			// Redirect to the secure version of the page.
			$url = 'https://'.blx()->request->getServerName().blx()->request->getRequestUri();
			blx()->request->redirect($url);
			return false;
		}

		return true;
	}
}
