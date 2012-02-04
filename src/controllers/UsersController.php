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

				$user = Blocks::app()->users->registerUser($user->username, $user->email, $user->first_name, $user->last_name, $randomPassword, true);

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

		$postUser = Blocks::app()->request->getPost('user');
		$user->username = $postUser['username'];
		$user->first_name = $postUser['first_name'];
		$user->last_name = $postUser['last_name'];
		$user->email = $postUser['email'];
		$user->admin = isset($postUser['admin']) ? 1 : 0;
		$user->html_email = isset($postUser['html_email']) ? 1 : 0;
		$user->password_reset_required = isset($postUser['password_reset']) ? 1 : 0;

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
					if (isset($postUser['send_registration_email']))
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

