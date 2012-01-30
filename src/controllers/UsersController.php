<?php
namespace Blocks;

/**
 * Handles user management tasks including registering, etc., etc.
 */
class UsersController extends BaseController
{
	/**
	 * Displays the register template for creating a new user.
	 */
	public function actionRegister()
	{
		$model = new bRegisterUserForm();

		// Check to see if it's a submit.
		if(Blocks::app()->request->isPostRequest)
		{
			$model->userName = Blocks::app()->request->getPost('userName');
			$model->email = Blocks::app()->request->getPost('email');
			$model->firstName = Blocks::app()->request->getPost('firstName');
			$model->lastName = Blocks::app()->request->getPost('lastName');
			$model->sendRegistrationEmail = Blocks::app()->request->getPost('sendRegistrationEmail') == 'on' ? 1 : 0;

			// validate user input and redirect to the previous page if valid
			if ($model->validate())
			{
				$randomPassword = Blocks::app()->security->generatePassword();
				$user = Blocks::app()->users->registerUser($model->userName, $model->email, $model->firstName, $model->lastName, $randomPassword, true);

				if ($user !== null)
				{
					// success message (flash?)
					$this->redirect(bUrlHelper::generateActionUrl('app/users/register'));
				}
			}

		}

		// display the login form
		$this->loadTemplate('users/register', array('model' => $model));
	}
}

