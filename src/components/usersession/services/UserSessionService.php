<?php
namespace Blocks;

/**
 *
 */
class UserSessionService extends \CWebUser
{
	const FLASH_KEY_PREFIX = 'Blocks.UserSessionService.flash.';
	const FLASH_COUNTERS   = 'Blocks.UserSessionService.flashcounters';

	/**
	 * Stores the user identity.
	 *
	 * @access private
	 * @var UserIdentity
	 */
	private $_identity;

	/**
	 * Stores the current user model.
	 *
	 * @access private
	 * @var UserModel|false
	 */
	private $_user;

	/**
	 * Gets the currently logged-in user.
	 *
	 * @return UserModel|false
	 */
	public function getUser()
	{
		// Does a user appear to be logged in?
		if (blx()->isInstalled() && !$this->getIsGuest())
		{
			if (!isset($this->_user))
			{
				$userRecord = UserRecord::model()->findById($this->getId());

				if ($userRecord)
				{
					$this->_user = UserModel::populateModel($userRecord);
				}
				else
				{
					$this->_user = false;
				}
			}

			return $this->_user ? $this->_user : null;
		}
	}

	/**
	 * @param null $defaultUrl
	 * @return mixed
	 */
	public function getReturnUrl($defaultUrl = null)
	{
		return $this->getState('__returnUrl', $defaultUrl === null ? UrlHelper::getUrl('dashboard') : HtmlHelper::normalizeUrl($defaultUrl));
	}

	/**
	 * @throws HttpException
	 */
	public function loginRequired()
	{
		if (!blx()->request->isAjaxRequest())
		{
			if (blx()->request->getPathInfo() !== '')
			{
				$this->setReturnUrl(blx()->request->getPath());
			}
		}
		elseif (isset($this->loginRequiredAjaxResponse))
		{
			echo $this->loginRequiredAjaxResponse;
			blx()->end();
		}

		$url = UrlHelper::getUrl($this->loginUrl);
		blx()->request->redirect($url);
	}

	/**
	 * @param $id
	 * @param $states
	 * @param $fromCookie
	 * @return bool
	 */
	protected function beforeLogin($id, $states, $fromCookie)
	{
		if (isset($states['authSessionToken']))
		{
			$authSessionToken = $states['authSessionToken'];

			$userCount = UserRecord::model()->countByAttributes(array(
				'id' => $id,
				'authSessionToken' => $authSessionToken
			));

			if ($userCount == 1)
			{
				// everything is cool.
				return true;
			}
			else
			{
				// everything is not cool.
				Blocks::log('During login, could not find a user with an id of '.$id.' or the user\'s authSessionToken: '.$authSessionToken.' did not match the one we have on record.', \CLogger::LEVEL_ERROR);
				return false;
			}
		}
		else
		{
			// also not cool.
			return false;
		}
	}

	/**
	 * Saves necessary user data into a cookie.
	 * This method is used when automatic login ({@link allowAutoLogin}) is enabled.
	 * This method saves user ID, username, other identity states and a validation key to cookie.
	 * These information are used to do authentication next time when user visits the application.
	 *
	 * @param integer $duration number of seconds that the user can remain in logged-in status. Defaults to 0, meaning login till the user closes the browser.
	 * @see restoreFromCookie
	*/
	protected function saveToCookie($duration)
	{
		$cookie = $this->createIdentityCookie($this->getStateKeyPrefix());
		$cookie->expire = time() + $duration;
		$cookie->httpOnly = true;

		$data = array(
			$this->getId(),
			$this->getName(),
			$duration,
			$this->saveIdentityStates(),
		);

		$cookie->value = blx()->getSecurityManager()->hashData(serialize($data));
		blx()->getRequest()->getCookies()->add($cookie->name, $cookie);
	}

	/**
	 * Sets a notice to the user.
	 *
	 * @param string $message
	 */
	public function setNotice($message)
	{
		$this->setFlash('notice', $message);
	}

	/**
	 * Sets an error notification.
	 *
	 * @param string $message
	 */
	public function setError($message)
	{
		$this->setFlash('error', $message);
	}

	/**
	 *
	 * Check to see if the current web user is a guest.
	 *
	 * (wrapper for getIsGuest() for consistency)
	 *
	 * @return bool
	 */
	public function isGuest()
	{
		$user = $this->getUser();
		return empty($user);
	}

	/**
	 * Check to see if the current web user is logged in.
	 *
	 * @return bool
	 */
	public function isLoggedIn()
	{
		return !$this->isGuest();
	}

	/**
	 * Returns whether the current user is an admin.
	 *
	 * @return bool
	 */
	public function isAdmin()
	{
		$user = $this->getUser();
		return ($user && $user->admin);
	}

	/**
	 * Returns whether the current user has a given permission.
	 *
	 * @param string $permissionName
	 * @return bool
	 */
	public function can($permissionName)
	{
		$user = $this->getUser();
		return ($user && $user->can($permissionName));
	}

	/**
	 * Requires that the current user has a given permission, otherwise a 403 exception is thrown.
	 *
	 * @param string $permissionName
	 * @throws HttpException
	 */
	public function requirePermission($permissionName)
	{
		if (!$this->can($permissionName))
		{
			throw new HttpException(403);
		}
	}

	/**
	 * Logs a user in.
	 *
	 * @param $username
	 * @param $password
	 * @param bool $rememberMe
	 * @return bool
	 */
	public function login($username, $password, $rememberMe = false)
	{
		// Validate the username/password first.
		$usernameModel = new UsernameModel();
		$passwordModel = new PasswordModel();

		$usernameModel->username = $username;
		$passwordModel->password = $password;

		if ($usernameModel->validate() && $passwordModel->validate())
		{
			$this->_identity = new UserIdentity($username, $password);
			$this->_identity->authenticate();

			// Was the login successful?
			if ($this->_identity->errorCode == UserIdentity::ERROR_NONE)
			{
				$rememberUsernameDuration = blx()->config->get('rememberUsernameDuration');
				if ($rememberUsernameDuration)
				{
					$interval = new DateInterval($rememberUsernameDuration);
					$expire = new DateTime();
					$expire->add($interval);

					$cookie = new \CHttpCookie('username', $username);
					$cookie->expire = $expire->getTimestamp();
					$cookie->httpOnly = true;
					blx()->request->cookies['username'] = $cookie;
				}

				if ($rememberMe)
				{
					$duration = blx()->config->get('rememberedUserSessionDuration');
				}
				else
				{
					$duration = blx()->config->get('userSessionDuration');
				}

				if ($duration)
				{
					$interval = new DateInterval($duration);
					$seconds = $interval->seconds();
				}
				else
				{
					$seconds = 0;
				}

				return parent::login($this->_identity, $seconds);
			}
		}

		Blocks::log($username.' tried to log in unsuccessfully.', \CLogger::LEVEL_WARNING);
		return false;
	}

	/**
	 * Returns the login error code from the user identity.
	 *
	 * @return UserIdentity
	 */
	public function getLoginErrorCode()
	{
		if (isset($this->_identity))
		{
			return $this->_identity->errorCode;
		}
	}

	/**
	 * @return string
	 */
	public function getRememberedUsername()
	{
		return blx()->request->getCookie('username');
	}
}
