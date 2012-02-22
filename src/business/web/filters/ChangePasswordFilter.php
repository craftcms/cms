<?php
namespace Blocks;

/**
 *
 */
class ChangePasswordFilter extends \CFilter
{
	/**
	 * @access protected
	 * @param $filterChain
	 * @return bool
	 */
	protected function preFilter($filterChain)
	{
		if (Blocks::app()->request->mode == RequestMode::CP)
		{
			if (($seg = Blocks::app()->request->getPathSegment(1, null)) !== null)
			{
				$ignore = array('install', 'setup');
				if (in_array($seg, $ignore))
					return true;
			}

			$ignore = array('login', 'logout', 'account/password');
			$url = Blocks::app()->request->getPathInfo();
			if (in_array($url, $ignore))
				return true;

			if (Blocks::app()->user->isLoggedIn)
			{
				$user = Blocks::app()->user->user;

				if ($user !== null)
				{
					if ($user->password_reset_required)
					{
						Blocks::app()->user->setMessage(MessageStatus::Notice, 'You must change your password before you can proceed.');

						if (!Blocks::app()->request->getIsAjaxRequest())
							Blocks::app()->user->setReturnUrl(Blocks::app()->request->getUrl());

						Blocks::app()->request->redirect(UrlHelper::generateUrl('account/password'));
					}
				}
				else
					throw new HttpException(403, 'You must change your password in order to continue.');
			}
		}

		return true;
	}
}
