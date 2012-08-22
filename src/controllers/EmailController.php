<?php
namespace Blocks;

/**
 * Handles email related tasks.
 */
class EmailController extends BaseController
{
	/**
	 *
	 */
	public function actionSendTestEmail()
	{
		$user = blx()->accounts->getCurrentUser();
		blx()->email->sendEmailByKey($user, 'forgot_password');
	}
}
