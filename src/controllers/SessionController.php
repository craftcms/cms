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
		// TODO:  uncomment when we're ready to ajaxify login
		//$this->requireAjaxRequest();

		$loginName = Blocks::app()->request->getPost('loginName');
		$password = Blocks::app()->request->getPost('password');
		$rememberMe = (Blocks::app()->request->getPost('rememberMe') === 'y');

		if (($loginInfo = Blocks::app()->user->startLogin($loginName, $password, $rememberMe)))
			$this->redirect(Blocks::app()->user->returnUrl);

		// display the login form
		$this->loadTemplate('login', array('loginInfo' => $loginInfo));
	}

	public function actionLogout()
	{
		Blocks::app()->user->logout();
		$this->redirect('');
	}
}

