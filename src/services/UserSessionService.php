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
	 * @var UserModel
	 */
	private $_user;

	/**
	 * @var
	 */
	private $_sessionRestoredFromCookie = null;

	/**
	 * Gets the currently logged-in user.
	 *
	 * @return UserModel|null
	 */
	public function getUser()
	{
		// Does a user appear to be logged in?
		if (blx()->isInstalled() && $this->getState('__id') !== null)
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
	 * Returns the URL the user was trying to access before getting sent to the login page.
	 *
	 * @param string $defaultUrl
	 * @return mixed
	 */
	public function getReturnUrl($defaultUrl = '')
	{
		return $this->getState('__returnUrl', UrlHelper::getUrl($defaultUrl));
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
	public function checkPermission($permissionName)
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
		if (!$this->checkPermission($permissionName))
		{
			throw new HttpException(403);
		}
	}

	/**
	 * Requires that the current user is an admin, otherwise a 403 exception is thrown.
	 *
	 * @throws HttpException
	 */
	public function requireAdmin()
	{
		if (!$this->isAdmin())
		{
			throw new HttpException(403);
		}
	}

	/**
	 * Requires that the user is logged in, otherwise redirects them to the login page.
	 */
	public function requireLogin()
	{
		if ($this->isGuest())
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
	}

	/**
	 * Pointless Wrapper for requireLogin(), but \CWebUser uses loginRequired() so we must support it as well.
	 */
	public function loginRequired()
	{
		$this->requireLogin();
	}

	/**
	 * Logs a user in.
	 *
	 * @param \IUserIdentity $username
	 * @param int            $password
	 * @param bool           $rememberMe
	 * @throws Exception
	 * @return bool
	 */
	public function login($username, $password, $rememberMe = false)
	{
		// Validate the username/password first.
		$usernameModel = new UsernameModel();
		$passwordModel = new PasswordModel();

		$usernameModel->username = $username;
		$passwordModel->password = $password;

		// Require a userAgent string and an IP address to help prevent direct socket connections from trying to login.
		if (!blx()->request->userAgent || !blx()->request->getIpAddress())
		{
			Blocks::log('Someone tried to login with loginName: '.$username.', without presenting an IP address or userAgent string.', \CLogger::LEVEL_WARNING);
			$this->logout(true);
			$this->requireLogin();
		}

		// Validate the model.
		if ($usernameModel->validate() && $passwordModel->validate())
		{
			// Authenticate the credentials.
			$this->_identity = new UserIdentity($username, $password);
			$this->_identity->authenticate();

			// Was the login successful?
			if ($this->_identity->errorCode == UserIdentity::ERROR_NONE)
			{
				// See if the 'rememberUsernameDuration' config item is set. If so, save the name to a cookie.
				$rememberUsernameDuration = blx()->config->get('rememberUsernameDuration');
				if ($rememberUsernameDuration)
				{
					$interval = new DateInterval($rememberUsernameDuration);
					$expire = new DateTime();
					$expire->add($interval);

					// Save the username cookie.
					$this->saveCookie('username', $username, $expire->getTimestamp());
				}

				// Get how long this session is supposed to last.
				$seconds = $this->_getSessionDuration($rememberMe);
				$this->authTimeout = $seconds;

				$id = $this->_identity->getId();
				$states = $this->_identity->getPersistentStates();

				// Run any before login logic.
				if ($this->beforeLogin($id, $states, false))
				{
					$this->changeIdentity($id, $this->_identity->getName(), $states);

					if ($seconds > 0)
					{
						if ($this->allowAutoLogin)
						{
							$user = blx()->users->getUserById($id);

							if ($user)
							{
								// Save the necessary info to the identity cookie.
								$sessionToken = StringHelper::UUID();
								$hashedToken = blx()->security->hashString($sessionToken);
								$uid = blx()->users->handleSuccessfulLogin($user, $hashedToken['hash']);
								$userAgent = blx()->request->userAgent;

								$data = array(
									$this->getName(),
									$sessionToken,
									$uid,
									$seconds,
									$userAgent,
									$this->saveIdentityStates(),
								);

								$this->saveCookie('', $data, $seconds);
								$this->_sessionRestoredFromCookie = false;
							}
							else
							{
								throw new Exception(Blocks::t('Could not find a user with Id of {userId}.', array('{userId}' => $this->getId())));
							}
						}
						else
						{
							throw new Exception(Blocks::t('{class}.allowAutoLogin must be set true in order to use cookie-based authentication.', array('{class}' => get_class($this))));
						}
					}

					// Run any after login logic.
					$this->afterLogin(false);
				}

				return !$this->getIsGuest();
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
		return $this->getCookieValue('username');
	}

	/**
	 * Overriding Yii's because it's stupid.
	 *
	 * @return bool
	 */
	public function getIsGuest()
	{
		return $this->isGuest();
	}

	/**
	 * TODO: set domain to wildcard?  .example.com, .example.co.uk, .too.many.subdomains.com
	 *
	 * @param     $cookieName
	 * @param     $data
	 * @param int $duration
	 */
	public function saveCookie($cookieName, $data, $duration = 0)
	{
		$cookieName = $this->getStateKeyPrefix().$cookieName;
		$cookie = new \CHttpCookie($cookieName, '');
		$cookie->httpOnly = true;
		$cookie->expire = time() + $duration;

		if (blx()->request->isSecureConnection)
		{
			$cookie->secure = true;
		}

		$cookie->value = blx()->getSecurityManager()->hashData(base64_encode(serialize($data)));
		blx()->getRequest()->getCookies()->add($cookie->name, $cookie);
	}

	/**
	 * @param $cookieName
	 * @return mixed|null
	 */
	public function getCookieValue($cookieName)
	{
		$cookie = blx()->request->getCookie($this->getStateKeyPrefix().$cookieName);

		if ($cookie && !empty($cookie->value) && ($data = blx()->securityManager->validateData($cookie->value)) !== false)
		{
			$data = @unserialize(base64_decode($data));
			return $data;
		}

		return null;
	}

	/**
	 * @return null
	 */
	public function wasSessionRestoredFromCookie()
	{
		return $this->_sessionRestoredFromCookie;
	}

	/**
	 *
	 */
	protected function renewCookie()
	{
		$cookies = blx()->request->getCookies();
		$cookie = $cookies->itemAt($this->getStateKeyPrefix());

		// Check the identity cookie and make sure the data hasn't been tampered with.
		if ($cookie && !empty($cookie->value) && ($data = blx()->securityManager->validateData($cookie->value)) !== false)
		{
			$data = $this->getCookieValue('');

			if (is_array($data) && isset($data[0], $data[1], $data[2], $data[3], $data[4], $data[5]))
			{
				$savedUserAgent = $data[4];
				$currentUserAgent = blx()->request->userAgent;

				// If the saved userAgent differs from the current one, bail.
				if ($savedUserAgent !== $currentUserAgent)
				{
					Blocks::log('Tried to renew the identity cookie, but the saved userAgent ('.$savedUserAgent.') does not match the current userAgent ('.$currentUserAgent.').', \CLogger::LEVEL_WARNING);
					$this->logout(true);
				}

				// Bump the expiration time.
				$cookie->expire = time() + $data[3];
				$cookies->add($cookie->name, $cookie);

				$this->authTimeout = $data[3];
			}
		}
		else
		{
			// If session duration is set to 0, then the session will be over when the browser is closed.
			if ($this->_getSessionDuration(false) > 0)
			{
				// If they are not a guest, they still have a valid PHP session, but at this point their identity cookie has expired, so let's kill it all.
				if (!$this->isGuest())
				{
					$this->logout(true);
				}
			}
		}
	}

	/**
	 * Populates the current user object with the information obtained from cookie.
	 * This method is used when automatic login ({@link allowAutoLogin}) is enabled.
	 * The user identity information is recovered from cookie.
	 */
	protected function restoreFromCookie()
	{
		// Require a userAgent string and an IP address to help prevent direct socket connections from trying to login.
		if (!blx()->request->userAgent || !blx()->request->getIpAddress())
		{
			Blocks::log('Someone tried to restore a session from a cookie without presenting an IP address or userAgent string.', \CLogger::LEVEL_WARNING);
			$this->logout(true);
			$this->requireLogin();
		}

		// See if they have an existing identity cookie.
		$cookie = blx()->request->getCookies()->itemAt($this->getStateKeyPrefix());

		// Grab the identity cookie and make sure the data hasn't been tampered with.
		if ($cookie && !empty($cookie->value) && is_string($cookie->value) && ($data = blx()->securityManager->validateData($cookie->value)) !== false)
		{
			// Grab the data
			$data = $this->getCookieValue('');

			if (is_array($data) && isset($data[0], $data[1], $data[2], $data[3], $data[4], $data[5]))
			{
				$loginName = $data[0];
				$currentSessionToken = $data[1];
				$uid = $data[2];
				$seconds = $data[3];
				$savedUserAgent = $data[4];
				$states = $data[5];
				$currentUserAgent = blx()->request->userAgent;

				// If the saved userAgent differs from the current one, bail.
				if ($savedUserAgent !== $currentUserAgent)
				{
					Blocks::log('Tried to restore session from the the identity cookie, but the saved userAgent ('.$savedUserAgent.') does not match the current userAgent ('.$currentUserAgent.').', \CLogger::LEVEL_WARNING);
					$this->logout(true);
				}

				// Get the hashed token from the db based on login name and uid.
				if (($sessionRow = $this->_findSessionToken($loginName, $uid)) !== false)
				{
					$dbHashedToken = $sessionRow['token'];
					$userId = $sessionRow['userId'];

					// Make sure the given session token matches what we have in the db.
					if (blx()->security->checkString($currentSessionToken, $dbHashedToken))
					{
						// It's all good.
						if($this->beforeLogin($loginName, $states, true))
						{
							$this->changeIdentity($userId, $loginName, $states);

							if ($this->autoRenewCookie)
							{
								// Generate a new session token for the database and cookie.
								$newSessionToken = StringHelper::UUID();
								$hashedNewToken = blx()->security->hashString($newSessionToken);
								$this->_updateSessionToken($loginName, $dbHashedToken, $hashedNewToken['hash']);

								// While we're let's clean up stale sessions.
								$this->_cleanStaleSessions();

								// Save updated info back to identity cookie.
								$data = array(
									$this->getName(),
									$newSessionToken,
									$uid,
									$seconds,
									$currentUserAgent,
									$states,
								);

								$this->saveCookie('', $data, $seconds);
								$this->authTimeout = $seconds;
								$this->_sessionRestoredFromCookie = true;
							}

							$this->afterLogin(true);
						}
					}
					else
					{
						Blocks::log('Tried to restore session from a cookie, but the given hashed database token value does not appear to belong to the given login name. Hashed db value: '.$dbHashedToken.' and loginName: '.$loginName.'.', \CLogger::LEVEL_ERROR);
						// Forcing logout here clears the identity cookie helping to prevent session fixation.
						$this->logout(true);
					}
				}
				else
				{
					Blocks::log('Tried to restore session from a cookie, but the given login name does not match the given uid. UID: '.$uid.' and loginName: '.$loginName.'.', \CLogger::LEVEL_ERROR);
					// Forcing logout here clears the identity cookie helping to prevent session fixation.
					$this->logout(true);
				}
			}
			else
			{
				Blocks::log('Tried to restore session from a cookie, but it appears we the data in the cookie is invalid.', \CLogger::LEVEL_ERROR);
				$this->logout(true);
			}
		}
		else
		{
			// If session duration is set to 0, then the session will be over when the browser is closed.
			if ($this->_getSessionDuration(false) > 0)
			{
				// If they are not a guest, they still have a valid PHP session, but at this point their identity cookie has expired, so let's kill it all.
				if (!$this->isGuest())
				{
					$this->logout(true);
				}
			}
		}
	}

	/**
	 * @return bool|void
	 */
	protected function beforeLogout()
	{
		$cookie = blx()->request->getCookies()->itemAt($this->getStateKeyPrefix());

		// Grab the identity cookie information and make sure the data hasn't been tampered with.
		if ($cookie && !empty($cookie->value) && is_string($cookie->value) && ($data = blx()->securityManager->validateData($cookie->value)) !== false)
		{
			// Grab the data
			$data = $this->getCookieValue('');

			if (is_array($data) && isset($data[0], $data[1], $data[2], $data[3], $data[4], $data[5]))
			{
				$loginName = $data[0];
				$uid = $data[2];

				// Clean up their row in the sessions table.
				$user = blx()->users->getUserByUsernameOrEmail($loginName);
				blx()->db->createCommand()->delete('sessions', 'userId=:userId AND uid=:uid', array('userId' => $user->id, 'uid' => $uid));
			}
			else
			{
				Blocks::log('During logout, tried to remove the row from the sessions table, but it appears the cookie data is invalid.', \CLogger::LEVEL_ERROR);
			}
		}

		return true;
	}

	/**
	 * @param $loginName
	 * @param $uid
	 * @return bool
	 */
	private function _findSessionToken($loginName, $uid)
	{
		$result = blx()->db->createCommand()
		    ->select('s.token, s.userId')
		    ->from('sessions s')
		    ->join('users u', 's.userId = u.id')
		    ->where('(u.username=:username OR u.email=:email) AND s.uid=:uid', array(':username' => $loginName, ':email' => $loginName, 'uid' => $uid))
		    ->queryRow();

		if (is_array($result) && count($result) > 0)
		{
			return $result;
		}

		return false;
	}

	/**
	 * @param $loginName
	 * @param $currentToken
	 * @param $newToken
	 * @return int
	 */
	private function _updateSessionToken($loginName, $currentToken, $newToken)
	{
		$user = blx()->users->getUserByUsernameOrEmail($loginName);
		blx()->db->createCommand()->update('sessions', array('token' => $newToken), 'token=:currentToken AND userId=:userId', array('currentToken' => $currentToken, 'userId' => $user->id));
	}

	/**
	 *
	 */
	private function _cleanStaleSessions()
	{
		$interval = new DateInterval('P3M');
		$expire = DateTimeHelper::currentUTCDateTime();
		$pastTimeStamp = $expire->sub($interval)->getTimestamp();
		$pastTime = DateTimeHelper::formatTimeForDb($pastTimeStamp);

		blx()->db->createCommand()->delete('sessions', 'dateUpdated < :pastTime', array('pastTime' => $pastTime));
	}

	/**
	 * @param $rememberMe
	 * @return int
	 */
	private function _getSessionDuration($rememberMe)
	{
		if ($rememberMe)
		{
			$duration = blx()->config->get('rememberedUserSessionDuration');
		}
		else
		{
			$duration = blx()->config->get('userSessionDuration');
		}

		// Calculate how long the session should last.
		if ($duration)
		{
			$interval = new DateInterval($duration);
			$expire = DateTimeHelper::currentUTCDateTime();
			$currentTimeStamp = $expire->getTimestamp();
			$futureTimeStamp = $expire->add($interval)->getTimestamp();
			$seconds = $futureTimeStamp - $currentTimeStamp;
		}
		else
		{
			$seconds = 0;
		}

		return $seconds;
	}
}
