<?php

/**
 * Handles session related tasks including logging in and out.
 */
class bSessionController extends bBaseController
{
	/**
	 * Displays the login template. If valid login information, redirects to previous template.
	 */
	public function actionLogin()
	{
		$model = new bLoginForm();

		// Check to see if it's a submit.
		if(Blocks::app()->request->isPostRequest)
		{
			$model->loginName = Blocks::app()->request->getPost('loginName');
			$model->password = Blocks::app()->request->getPost('password');

			// validate user input and redirect to the previous page if valid
			if($model->validate() && $model->login())
				$this->redirect(Blocks::app()->user->returnUrl);
		}

		// display the login form
		$this->loadTemplate('login', array('model' => $model));
	}

	public function actionLogout()
	{
		Blocks::app()->user->logout();
	}
}

