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

		if (b()->request->getPost('suspend', null) !== null)
		{
			$user->status = UserAccountStatus::Suspended;
			if ($this->_processUserChange($user))
				$this->_setMessageAndRedirect('User suspended.', MessageType::Notice, b()->request->getPost('redirect'));
		}
		else if (b()->request->getPost('validationEmail', null) !== null)
		{
			if (($emailStatus = b()->email->sendRegistrationEmail($user, b()->sites->currentSite)) == true)
				$this->_setMessageAndRedirect('Validation email sent.', MessageType::Notice, b()->request->getPost('redirect'));
		}
		else if (b()->request->getPost('unsuspend', null) !== null)
		{
			$user->status = UserAccountStatus::Active;
			if ($this->_processUserChange($user))
				$this->_setMessageAndRedirect('User unsuspended.', MessageType::Notice, b()->request->getPost('redirect'));
		}
		else if (b()->request->getPost('unlock', null) !== null)
		{
			$user->status = UserAccountStatus::Active;
			$user->cooldown_start = null;
			if ($this->_processUserChange($user))
				$this->_setMessageAndRedirect('User unlocked.', MessageType::Notice, b()->request->getPost('redirect'));
		}
		else if (b()->request->getPost('delete', null) !== null)
		{
			if ($user->id == b()->users->current->id)
			{
				$this->_setMessageAndRedirect('Trying to delete yourself?  It can’t be that bad.', MessageType::Notice, b()->request->getPost('redirect'));
			}
			else
			{
				b()->users->delete($user);
				$this->_setMessageAndRedirect('User archived.', MessageType::Notice, 'users', b()->request->getPost('redirect'));
			}
		}
		else if (b()->request->getPost('save', null) !== null)
		{
			$user->username = b()->request->getPost('username');
			$user->first_name = b()->request->getPost('first_name');
			$user->last_name = b()->request->getPost('last_name');
			$user->email = b()->request->getPost('email');
			$user->admin = (b()->request->getPost('admin') === 'y');
			$user->html_email = (b()->request->getPost('html_email') === 'y');
			$user->status = b()->request->getPost('status');
			$user->password_reset_required = (b()->request->getPost('password_reset') === 'y');

			$sendValidationEmail = (b()->request->getPost('send_validation_email') === 'y');

			if ($user->validate())
			{
				if (!$existingUser)
				{
					$user = b()->users->registerUser($user, null, true);

					if ($user !== null)
					{
						if ($sendValidationEmail)
						{
							$site = b()->sites->currentSite;
							if (($emailStatus = b()->email->sendRegistrationEmail($user, $site)) == true)
							{
								// registered and sent email
								$this->_setMessageAndRedirect('User registered and registration email sent.', MessageType::Notice, b()->request->getPost('redirect'));
							}
							else
							{
								// registered but there was a problem sending the email.
								$this->_setMessageAndRedirect('User registered, but couldn’t send the registration email: '.$emailStatus, MessageType::Notice, b()->request->getPost('redirect'));
							}
						}
						else
						{
							// registered user with no email validation
							$this->_setMessageAndRedirect('User registered.', MessageType::Notice, b()->request->getPost('redirect'));
						}
					}
					else
					{
						// there was a problem registering the user.
						$this->_setMessageAndRedirect('Couldn’t register user. See the log for details.', MessageType::Error, b()->request->getPost('redirect'));
					}
				}
				else
					$user->save(false);

				if ($existingUser)
					$this->_setMessageAndRedirect('User saved.', MessageType::Notice, b()->request->getPost('redirect'));

				$this->_redirect(b()->request->getPost('redirect'));
			}
			else
			{
				b()->user->setMessage(MessageType::Error, 'Couldn’t save user.');
			}
		}

		$this->loadRequestedTemplate(array('theUser' => $user));
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
	 * @param $url
	 */
	private function _setMessageAndRedirect($message, $messageStatus, $url)
	{
		b()->user->setMessage($messageStatus, $message);
		$this->_redirect($url);
	}

	/**
	 * @param $url
	 */
	private function _redirect($url)
	{
		if ($url !== null)
			$this->redirect($url);
	}
}

