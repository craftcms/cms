<?php

//class SiteController extends BaseController
//{
	/**
	 * Declares class-based actions.
	 */
/*	public function actions()
	{
		return array(
			// captcha action renders the CAPTCHA image displayed on the contact page
			'captcha'=>array(
				'class'=>'CCaptchaAction',
				'backColor'=>0xFFFFFF,
			),
			// page action renders "static" pages stored under 'protected/views/site/pages'
			// They can be accessed via: index.php?r=site/page&view=FileName
			'page'=>array(
				'class'=>'CViewAction',
			),
		);
	}
*/
	/**
	 * This is the default 'index' action that is invoked
	 * when an action is not explicitly requested by users.
	 */
	/*public function actionIndex()
	{
		//$this->pageTitle = 'Blocks CMS';
		//$this->render('index');
	}*/

/*	public function actionAbout()
	{
		
	}
*/
	/**
	 * This is the action to handle external exceptions.
	 */
/*	public function actionError()
	{
		if ($error = Blocks::app()->errorHandler->error)
		{
			if (Blocks::app()->request->isAjaxRequest)
				echo $error['message'];
			else
				$this->render('error', $error);
		}
	}*/

	/**
	 * Displays the contact page
	 */
/*	public function actionContact()
	{
		$model = new ContactForm;
		if (Blocks::app()->request->getPost('ContactForm', null) !== null)
		{
			$model->attributes = Blocks::app()->request->getPost('ContactForm');
			if ($model->validate())
			{
				$headers = "From: {$model->email}\r\nReply-To: {$model->email}";
				mail(Blocks::app()->params['adminEmail'], $model->subject, $model->body, $headers);
				Blocks::app()->user->setFlash('contact','Thank you for contacting us. We will respond to you as soon as possible.');
				$this->refresh();
			}
		}
		$this->render('contact', array('model' => $model));
	}
*/
	/**
	 * Displays the login page
	 */
/*	public function actionLogin()
	{
		$model=new LoginForm;

		// if it is ajax validation request
		if(Blocks::app()->request->isAjaxRequest && Blocks::app()->request->getPost('ajax') === 'login-form')
		{
			echo CActiveForm::validate($model);
			Blocks::app()->end();
		}

		// collect user input data
		if (Blocks::app()->request->getPost('LoginForm', null) !== null)
		{
			$model->attributes = Blocks::app()->request->getPost('LoginForm');

			// validate user input and redirect to the previous page if valid
			if ($model->validate() && $model->login())
				$this->redirect(Blocks::app()->user->returnUrl);
		}

		// display the login form
		$this->render('login', array('model' => $model));
	}
*/
	/**
	 * Logs out the current user and redirect to homepage.
	 */
/*	public function actionLogout()
	{
		Blocks::app()->user->logout();
		$this->redirect(Blocks::app()->homeUrl);
	}
}*/
