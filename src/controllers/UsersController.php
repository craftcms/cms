<?php
namespace Blocks;

/**
 * Handles user management tasks including registering, etc., etc.
 */
class UsersController extends Controller
{
	/**
	 * All user actions require the user to be logged in
	 */
	public function init()
	{
		$this->requireLogin();
	}

	/**
	 * @param $user
	 * @param $site
	 * @return bool
	 */
	public function actionSendRegistrationEmail($user, $site)
	{
		$this->requireAjaxRequest();
		$this->requirePostRequest();

		if (($emailStatus = b()->email->sendRegistrationEmail($user, $site)) == true)
		{
			return true;
		}

		return false;
	}

	public function actionSave()
	{
		$this->requirePostRequest();

		$existingUser = false;

		// Are we editing an existing user?
		$postUserId = b()->request->getPost('user_id');
		if ($postUserId)
		{
			$existingUser = true;
			$user = b()->users->getById($postUserId);
		}

		if (empty($user))
			$user = new User();

		if (b()->request->getPost('suspend') !== null)
		{
			$user->status = UserAccountStatus::Suspended;
			if ($this->_processUserChange($user))
				$this->_setMessageAndRedirect('User suspended.', MessageType::Notice);
		}
		else if (b()->request->getPost('validationEmail') !== null)
		{
			if (($emailStatus = b()->email->sendRegistrationEmail($user, b()->sites->current)) == true)
				$this->_setMessageAndRedirect('Validation email sent.', MessageType::Notice);
		}
		else if (b()->request->getPost('unsuspend') !== null)
		{
			$user->status = UserAccountStatus::Active;
			if ($this->_processUserChange($user))
				$this->_setMessageAndRedirect('User unsuspended.', MessageType::Notice);
		}
		else if (b()->request->getPost('unlock') !== null)
		{
			$user->status = UserAccountStatus::Active;
			$user->cooldown_start = null;
			if ($this->_processUserChange($user))
				$this->_setMessageAndRedirect('User unlocked.', MessageType::Notice);
		}
		else if (b()->request->getPost('delete') !== null)
		{
			if ($user->id == b()->users->current->id)
			{
				$this->_setMessageAndRedirect('Trying to delete yourself?  It can’t be that bad.', MessageType::Notice);
			}
			else
			{
				b()->users->delete($user);
				$this->_setMessageAndRedirect('User archived.', MessageType::Notice, 'users');
			}
		}
		else if (b()->request->getPost('save') !== null)
		{
			$user->username = b()->request->getPost('username');
			$user->first_name = b()->request->getPost('first_name');
			$user->last_name = b()->request->getPost('last_name');
			$user->email = b()->request->getPost('email');
			$user->admin = (b()->request->getPost('admin') === 'y');
			$user->html_email = (b()->request->getPost('email_format') == 'html');
			$user->status = b()->request->getPost('status');
			$user->password_reset_required = (b()->request->getPost('password_reset') === 'y');

			$sendValidationEmail = (b()->request->getPost('send_validation_email') === 'y');

			$userValidates = $user->validate();

			// Is this the current user?
			if ($user->isCurrent)
			{
				// Are they changing their password?
				if (($password = b()->request->getPost('password')))
				{
					// Make sure the passwords match and are at least the minimum length
					$changePasswordForm = new ChangePasswordForm();
					$changePasswordForm->password = $password;
					$changePasswordForm->confirmPassword = b()->request->getPost('confirm-password');
					$passwordValidates = $changePasswordForm->validate();

					// Store the new hashed password on the User record, but don't save it yet
					if ($passwordValidates)
						b()->users->changePassword($user, $password, false);
				}
			}

			if ($userValidates && (!isset($passwordValidates) || $passwordValidates))
			{
				if ($existingUser)
				{
					$user->save(false);
					$this->_setMessageAndRedirect('User saved.', MessageType::Notice);
				}
				else
				{
					$user = b()->users->registerUser($user, null, true);

					if ($user !== null)
					{
						if ($sendValidationEmail)
						{
							$site = b()->sites->current;
							if (($emailStatus = b()->email->sendRegistrationEmail($user, $site)) == true)
							{
								// registered and sent email
								$this->_setMessageAndRedirect('User registered and registration email sent.', MessageType::Notice);
							}
							else
							{
								// registered but there was a problem sending the email.
								$this->_setMessageAndRedirect('User registered, but couldn’t send the registration email: '.$emailStatus, MessageType::Notice);
							}
						}
						else
						{
							// registered user with no email validation
							$this->_setMessageAndRedirect('User registered.', MessageType::Notice);
						}
					}
					else
					{
						// there was a problem registering the user.
						$this->_setMessageAndRedirect('Couldn’t register user. See the log for details.', MessageType::Error);
					}
				}
			}
			else
			{
				b()->user->setMessage(MessageType::Error, 'Couldn’t save user.');
			}
		}

		$this->loadRequestedTemplate(array(
			'theUser' => $user,
			'changePasswordForm' => (isset($changePasswordForm) ? $changePasswordForm : null)
		));
	}

	/**
	 * @param User $user
	 * @return bool
	 */
	private function _processUserChange(User $user)
	{
		if ($user->validate())
		{
			$user->save();
			return true;
		}
		else
			return false;
	}

	/**
	 * @param $message
	 * @param $messageStatus
	 */
	private function _setMessageAndRedirect($message, $messageStatus)
	{
		b()->user->setMessage($messageStatus, $message);
		$this->redirectToPostedUrl();
	}
}

