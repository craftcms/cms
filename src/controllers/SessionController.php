<?php
namespace Blocks;

/**
 * Handles session related tasks including logging in and out.
 */
class SessionController extends BaseController
{
	/**
	 * Displays the login template. If valid login information, redirects to previous template.
	 */
	public function actionLogin()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$loginName = Blocks::app()->request->getPost('loginName');
		$password = Blocks::app()->request->getPost('password');
		$rememberMe = (Blocks::app()->request->getPost('rememberMe') === 'y');

		// Attempt to log in
		$loginInfo = Blocks::app()->user->startLogin($loginName, $password, $rememberMe);

		// Did it work?
		if (Blocks::app()->user->isLoggedIn)
			$r = array(
				'success' => true,
				'redirectUrl' => Blocks::app()->user->returnUrl
			);
		else
			$r = array('error' => true);

		$this->returnJson($r);
	}

	public function actionLogout()
	{
		Blocks::app()->user->logout();
		$this->redirect('');
	}
}
