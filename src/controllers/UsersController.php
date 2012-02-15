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
		$user = new User();

		// Check to see if it's a submit.
		if(Blocks::app()->request->isPostRequest)
		{
			$user->username = Blocks::app()->request->getPost('userName');
			$user->email = Blocks::app()->request->getPost('email');
			$user->first_name = Blocks::app()->request->getPost('firstName');
			$user->last_name = Blocks::app()->request->getPost('lastName');

			// validate user input and redirect to the previous page if valid
			if ($user->validate())
			{
				$randomPassword = Blocks::app()->security->generatePassword();

				$user = Blocks::app()->users->registerUser($user, $randomPassword, true);

				if ($user !== null)
				{
					if (Blocks::app()->request->getPost('sendRegistrationEmail') == 'on')
					{
						$site = Blocks::app()->site->currentSite;

						if (($emailStatus = Blocks::app()->email->sendRegistrationEmail($user, $site)) == true)
						{
							// registered and sent email
							Blocks::app()->user->setMessage(MessageStatus::Success, 'Successfully registered user and sent registration email.');
						}
						else
						{
							// registered but there was a problem sending the email.
							Blocks::app()->user->setMessage(MessageStatus::Notice, 'Successfully registered user, but there was a problem sending the email: '.$emailStatus);
						}
					}
				}
				else
				{
					// there was a problem registering the user.
					Blocks::app()->user->setMessage(MessageStatus::Error, 'There was a problem registering the user.  Check your log files.');
				}

				$this->redirect(UrlHelper::generateActionUrl('app/users/register'));
			}

			$messages = ModelHelper::flattenErrors($user);
			Blocks::app()->user->setMessage(MessageStatus::Error, $messages);
		}

		// display the login form
		$this->loadTemplate('users/register', array('user' => $user));
	}

	public function actionSave()
	{
		$this->requirePostRequest();

		$existingUser = false;
		$randomPassword = null;

		// Are we editing an existing user?
		$postUserId = Blocks::app()->request->getPost('user_id');
		if ($postUserId)
		{
			$existingUser = true;
			$user = Blocks::app()->users->getById($postUserId);
		}

		if (empty($user))
			$user = new User();

		$user->username = Blocks::app()->request->getPost('username');
		$user->first_name = Blocks::app()->request->getPost('first_name');
		$user->last_name = Blocks::app()->request->getPost('last_name');
		$user->email = Blocks::app()->request->getPost('email');
		$user->admin = (Blocks::app()->request->getPost('admin') === 'y');
		$user->html_email = (Blocks::app()->request->getPost('html_email') === 'y');
		$user->password_reset_required = (Blocks::app()->request->getPost('password_reset') === 'y');

		if (!$existingUser)
		{
			$randomPassword = Blocks::app()->security->generatePassword();
			$user->password = $randomPassword;
			$user->enc_type = 'md5';
		}

		if ($user->validate())
		{
			if (!$existingUser)
			{
				$user = Blocks::app()->users->registerUser($user, $randomPassword, true);

				if ($user !== null)
				{
					if (Blocks::app()->request->getPost('send_registration_email') === 'y')
					{
						$site = Blocks::app()->sites->currentSite;

						if (($emailStatus = Blocks::app()->email->sendRegistrationEmail($user, $site)) == true)
						{
							// registered and sent email
							Blocks::app()->user->setMessage(MessageStatus::Success, 'Successfully registered user and sent registration email.');
						}
						else
						{
							// registered but there was a problem sending the email.
							Blocks::app()->user->setMessage(MessageStatus::Notice, 'Successfully registered user, but there was a problem sending the email: '.$emailStatus);
						}
					}
				}
				else
				{
					// there was a problem registering the user.
					Blocks::app()->user->setMessage(MessageStatus::Error, 'There was a problem registering the user.  Check your log files.');
				}
			}
			else
				$user->save(false);

			if ($existingUser)
				Blocks::app()->user->setMessage(MessageStatus::Success, 'User saved successfully.');

			$url = Blocks::app()->request->getPost('redirect');
			if ($url !== null)
				$this->redirect($url);
		}

		$this->loadRequestedTemplate(array('theUser' => $user));
	}
}

