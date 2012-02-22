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

		$loginInfo = new LoginForm();

		// Check to see if it's a submit.
		$loginInfo->loginName = Blocks::app()->request->getPost('loginName');
		$loginInfo->password = Blocks::app()->request->getPost('password');

		// validate user input and redirect to the previous page if valid
		if ($loginInfo->validate() && $loginInfo->login())
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

