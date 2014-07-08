<?php
namespace Craft;

/**
 *
 */
class UserSessionService extends \CWebUser
{
	const FLASH_KEY_PREFIX = 'Craft.UserSessionService.flash.';
	const FLASH_COUNTERS   = 'Craft.UserSessionService.flashcounters';

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
	private $_userModel;

	/**
	 * @var
	 */
	private $_userRow;

	/**
	 * @var
	 */
	private $_sessionRestoredFromCookie;

	/**
	 *
	 */
	public function init()
	{
		craft()->getSession()->open();

		// Let's set our own state key prefix. Leaving identical to CWebUser for the key so people won't get logged out when updating.
		$this->setStateKeyPrefix(md5('Yii.Craft\UserSessionService.'.craft()->getId()));

		$rememberMe = craft()->request->getCookie('rememberMe') !== null ? true : false;
		$seconds = $this->_getSessionDuration($rememberMe);
		$this->authTimeout = $seconds;

		$this->updateAuthStatus();

		parent::init();
	}

	/**
	 * Gets the currently logged-in user.
	 *
	 * @return UserModel|null
	 */
	public function getUser()
	{
		// Does a user appear to be logged in?
		if (craft()->isInstalled() && $this->getState('__id') !== null)
		{
			if (!isset($this->_user))
			{
				$userRow = $this->_getUserRow($this->getId());

				if ($userRow && $userRow['status'] == UserStatus::Active || $userRow['status'] == UserStatus::Pending)
				{
					$this->_userModel = UserModel::populateModel($userRow);
				}
				else
				{
					$this->_userModel = false;
				}
			}

			return $this->_userModel ? $this->_userModel : null;
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
	 * Adds a JS resource flash.
	 *
	 * @param string $resource
	 */
	public function addJsResourceFlash($resource)
	{
		$resources = $this->getJsResourceFlashes(false);

		if (!in_array($resource, $resources))
		{
			$resources[] = $resource;
			$this->setFlash('jsResources', $resources);
		}
	}

	/**
	 * Returns the queued-up JS flashes.
	 *
	 * @param bool $delete
	 * @return array
	 */
	public function getJsResourceFlashes($delete = true)
	{
		return $this->getFlash('jsResources', array(), $delete);
	}

	/**
	 * Adds a JS flash.
	 *
	 * @param string $js
	 */
	public function addJsFlash($js)
	{
		$scripts = $this->getJsFlashes();
		$scripts[] = $js;
		$this->setFlash('js', $scripts);
	}

	/**
	 * Returns the queued-up JS flashes.
	 *
	 * @param bool $delete
	 * @return array
	 */
	public function getJsFlashes($delete = true)
	{
		return $this->getFlash('js', array(), $delete);
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
		$user = $this->_getUserRow($this->getId());
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
			if (craft()->config->get('loginPath') === craft()->request->getPath())
			{
				throw new Exception(Craft::t('requireLogin was used on the login page, creating an infinite loop.'));
			}

			if (!craft()->request->isAjaxRequest())
			{
				$url = craft()->request->getPath();
				if (($queryString = craft()->request->getQueryStringWithoutPath()))
				{
					if (craft()->request->getPathInfo())
					{
						$url .= '?'.$queryString;
					}
					else
					{
						$url .= '&'.$queryString;
					}
				}

				$this->setReturnUrl($url);
			}
			elseif (isset($this->loginRequiredAjaxResponse))
			{
				echo $this->loginRequiredAjaxResponse;
				craft()->end();
			}

			$url = UrlHelper::getUrl(craft()->config->getLoginPath());
			craft()->request->redirect($url);
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
		if (!craft()->request->userAgent || !$_SERVER['REMOTE_ADDR'])
		{
			Craft::log('Someone tried to login with loginName: '.$username.', without presenting an IP address or userAgent string.', LogLevel::Warning);
			$this->logout();
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
				$rememberUsernameDuration = craft()->config->get('rememberUsernameDuration');
				if ($rememberUsernameDuration)
				{
					$interval = new DateInterval($rememberUsernameDuration);
					$expire = new DateTime();
					$expire->add($interval);

					// Save the username cookie.
					$this->saveCookie('username', $username, $expire->getTimestamp());
				}

				// If there is a remember me cookie, but $rememberMe is false, they logged in with an unchecked remember me box, so let's remove the cookie.
				if (craft()->request->getCookie('rememberMe') !== null && !$rememberMe)
				{
					craft()->request->deleteCookie('rememberMe');
				}

				if ($rememberMe)
				{
					$rememberMeSessionDuration = craft()->config->get('rememberedUserSessionDuration');
					if ($rememberMeSessionDuration)
					{
						$interval = new DateInterval($rememberMeSessionDuration);
						$expire = new DateTime();
						$expire->add($interval);

						// Save the username cookie.
						$this->saveCookie('rememberMe', true, $expire->getTimestamp());
					}
				}

				// Get how long this session is supposed to last.
				$seconds = $this->_getSessionDuration($rememberMe);

				$id = $this->_identity->getId();
				$states = $this->_identity->getPersistentStates();

				// Fire an 'onBeforeLogin' event
				$this->onBeforeLogin(new Event($this, array(
					'username'      => $usernameModel->username,
				)));

				// Run any before login logic.
				if ($this->beforeLogin($id, $states, false))
				{
					$this->changeIdentity($id, $this->_identity->getName(), $states);

					// Fire an 'onLogin' event
					$this->onLogin(new Event($this, array(
						'username'      => $usernameModel->username,
					)));

					if ($seconds > 0)
					{
						if ($this->allowAutoLogin)
						{
							$user = craft()->users->getUserById($id);

							if ($user)
							{
								// Save the necessary info to the identity cookie.
								$sessionToken = StringHelper::UUID();
								$hashedToken = craft()->security->hashData(base64_encode(serialize($sessionToken)));
								$uid = craft()->users->handleSuccessfulLogin($user, $hashedToken);
								$userAgent = craft()->request->userAgent;

								$data = array(
									$this->getName(),
									$sessionToken,
									$uid,
									$seconds,
									$userAgent,
									$this->saveIdentityStates(),
								);

								$this->saveCookie('', $data, $seconds);
							}
							else
							{
								throw new Exception(Craft::t('Could not find a user with Id of {userId}.', array('{userId}' => $this->getId())));
							}
						}
						else
						{
							throw new Exception(Craft::t('{class}.allowAutoLogin must be set true in order to use cookie-based authentication.', array('{class}' => get_class($this))));
						}
					}

					$this->_sessionRestoredFromCookie = false;
					$this->_userRow = null;

					// Run any after login logic.
					$this->afterLogin(false);
				}

				return !$this->getIsGuest();
			}
		}

		Craft::log($username.' tried to log in unsuccessfully.', LogLevel::Warning);
		return false;
	}

	/**
	 * Logs a user in for impersonation.
	 *
	 * @param \IUserIdentity $userId
	 * @throws Exception
	 * @return bool
	 */
	public function impersonate($userId)
	{
		$userModel = craft()->users->getUserById($userId);

		if (!$userModel)
		{
			throw new Exception(Craft::t('Could not find a user with Id of {userId}.', array('{userId}' => $userId)));
		}

		$this->_identity = new UserIdentity($userModel->username, null);
		$this->_identity->logUserIn($userModel);

		$id = $this->_identity->getId();
		$states = $this->_identity->getPersistentStates();

		// Run any before login logic.
		if ($this->beforeLogin($id, $states, false))
		{
			// Fire an 'onBeforeLogin' event
			$this->onBeforeLogin(new Event($this, array(
				'username'      => $userModel->username,
			)));

			$this->changeIdentity($id, $this->_identity->getName(), $states);

			// Fire an 'onLogin' event
			$this->onLogin(new Event($this, array(
				'username'      => $userModel->username,
			)));

			$this->_sessionRestoredFromCookie = false;
			$this->_userRow = null;

			// Run any after login logic.
			$this->afterLogin(false);

			return !$this->getIsGuest();
		}

		Craft::log($userModel->username.' tried to log in unsuccessfully.', LogLevel::Warning);
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
	 * Gets the proper error message from the given error code.
	 *
	 * @param $errorCode
	 * @param $loginName
	 * @return null|string
	 */
	public function getLoginErrorMessage($errorCode, $loginName)
	{
		switch ($errorCode)
		{
			case UserIdentity::ERROR_PASSWORD_RESET_REQUIRED:
			{
				$error = Craft::t('You need to reset your password. Check your email for instructions.');
				break;
			}
			case UserIdentity::ERROR_ACCOUNT_LOCKED:
			{
				$error = Craft::t('Account locked.');
				break;
			}
			case UserIdentity::ERROR_ACCOUNT_COOLDOWN:
			{
				$user = craft()->users->getUserByUsernameOrEmail($loginName);

				if ($user)
				{
					$timeRemaining = $user->getRemainingCooldownTime();

					if ($timeRemaining)
					{
						$humanTimeRemaining = $timeRemaining->humanDuration();
						$error = Craft::t('Account locked. Try again in {time}.', array('time' => $humanTimeRemaining));
					}
					else
					{
						$error = Craft::t('Account locked.');
					}
				}
				else
				{
					$error = Craft::t('Account locked.');
				}
				break;
			}
			case UserIdentity::ERROR_ACCOUNT_SUSPENDED:
			{
				$error = Craft::t('Account suspended.');
				break;
			}
			case UserIdentity::ERROR_NO_CP_ACCESS:
			{
				$error = Craft::t('You cannot access the CP with that account.');
				break;
			}
			case UserIdentity::ERROR_NO_CP_OFFLINE_ACCESS:
			{
				$error = Craft::t('You cannot access the CP while the system is offline with that account.');
				break;
			}
			default:
			{
				$error = Craft::t('Invalid username or password.');
			}
		}

		return $error;
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

		if (craft()->request->isSecureConnection())
		{
			$cookie->secure = true;
		}

		$cookie->value = craft()->security->hashData(base64_encode(serialize($data)));
		craft()->getRequest()->getCookies()->add($cookie->name, $cookie);
	}

	/**
	 * @param $cookieName
	 * @return mixed|null
	 */
	public function getCookieValue($cookieName)
	{
		$cookie = craft()->request->getCookie($this->getStateKeyPrefix().$cookieName);

		if ($cookie && !empty($cookie->value) && ($data = craft()->security->validateData($cookie->value)) !== false)
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
	 * Clears all user identity information from persistent storage. This will remove the data stored via {@link setState}.
	 */
	public function clearStates()
	{
		if (isset($_SESSION))
		{
			$keys = array_keys($_SESSION);
			$prefix = $this->getStateKeyPrefix();

			$n = mb_strlen($prefix);
			foreach($keys as $key)
			{
				if (!strncmp($key, $prefix, $n))
				{
					unset($_SESSION[$key]);
				}
			}
		}
	}

	// Events
	// ----------------------------------------------------------------------

	/**
	 * Fires an 'onBeforeLogin' event.
	 *
	 * @param Event $event
	 */
	public function onBeforeLogin(Event $event)
	{
		$this->raiseEvent('onBeforeLogin', $event);
	}

	/**
	 * Fires an 'onLogin' event.
	 *
	 * @param Event $event
	 */
	public function onLogin(Event $event)
	{
		$this->raiseEvent('onLogin', $event);
	}

	/**
	 * Fires an 'onBeforeLogout' event.
	 *
	 * @param Event $event
	 */
	public function onBeforeLogout(Event $event)
	{
		$this->raiseEvent('onBeforeLogout', $event);
	}

	/**
	 * Fires an 'onLogout' event.
	 *
	 * @param Event $event
	 */
	public function onLogout(Event $event)
	{
		$this->raiseEvent('onLogout', $event);
	}

	// Protected and private methods
	// ----------------------------------------------------------------------

	/**
	 * Changes the current user with the specified identity information. This method is called by {@link login} and {@link restoreFromCookie}
	 * when the current user needs to be populated with the corresponding identity information. Derived classes may override this method
	 * by retrieving additional user-related information. Make sure the parent implementation is called first.
	 *
	 * @param mixed  $id     A unique identifier for the user
	 * @param string $name   The display name for the user
	 * @param array  $states Identity states
	 */
	protected function changeIdentity($id,$name,$states)
	{
		$this->setId($id);
		$this->setName($name);
		$this->loadIdentityStates($states);
	}

	/**
	 *
	 */
	protected function renewCookie()
	{
		$this->_checkVitals();

		$cookies = craft()->request->getCookies();
		$cookie = $cookies->itemAt($this->getStateKeyPrefix());

		// Check the identity cookie and make sure the data hasn't been tampered with.
		if ($cookie && !empty($cookie->value) && ($data = craft()->security->validateData($cookie->value)) !== false)
		{
			$data = $this->getCookieValue('');

			if (is_array($data) && isset($data[0], $data[1], $data[2], $data[3], $data[4], $data[5]))
			{
				$savedUserAgent = $data[4];
				$currentUserAgent = craft()->request->userAgent;

				$this->_checkUserAgentString($currentUserAgent, $savedUserAgent);

				// Bump the expiration time.
				$expiration = time() + $data[3];
				$cookie->expire = $expiration;
				$cookies->add($cookie->name, $cookie);

				$this->authTimeout = $data[3];
				$this->setState(static::AUTH_TIMEOUT_VAR, $expiration);
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
		$this->_checkVitals();

		// See if they have an existing identity cookie.
		$cookie = craft()->request->getCookies()->itemAt($this->getStateKeyPrefix());

		// Grab the identity cookie and make sure the data hasn't been tampered with.
		if ($cookie && !empty($cookie->value) && is_string($cookie->value) && ($data = craft()->security->validateData($cookie->value)) !== false)
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
				$currentUserAgent = craft()->request->userAgent;

				$this->_checkUserAgentString($currentUserAgent, $savedUserAgent);

				// Get the hashed token from the db based on login name and uid.
				if (($sessionRow = $this->_findSessionToken($loginName, $uid)) !== false)
				{
					$dbHashedToken = $sessionRow['token'];
					$userId = $sessionRow['userId'];

					// Make sure the given session token matches what we have in the db.
					$checkHashedToken= craft()->security->hashData(base64_encode(serialize($currentSessionToken)));
					if (strcmp($checkHashedToken, $dbHashedToken) === 0)
					{
						// It's all good.
						if($this->beforeLogin($loginName, $states, true))
						{
							$this->changeIdentity($userId, $loginName, $states);

							if ($this->autoRenewCookie)
							{
								// Generate a new session token for the database and cookie.
								$newSessionToken = StringHelper::UUID();
								$hashedNewToken = craft()->security->hashData(base64_encode(serialize($newSessionToken)));
								$this->_updateSessionToken($loginName, $dbHashedToken, $hashedNewToken);

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
								$this->_userRow = null;
							}

							$this->afterLogin(true);
						}
					}
					else
					{
						Craft::log('Tried to restore session from a cookie, but the given hashed database token value does not appear to belong to the given login name. Hashed db value: '.$dbHashedToken.' and loginName: '.$loginName.'.', LogLevel::Error);
						// Forcing logout here clears the identity cookie helping to prevent session fixation.
						$this->logout();
					}
				}
				else
				{
					Craft::log('Tried to restore session from a cookie, but the given login name does not match the given uid. UID: '.$uid.' and loginName: '.$loginName.'.', LogLevel::Error);
					// Forcing logout here clears the identity cookie helping to prevent session fixation.
					$this->logout();
				}
			}
			else
			{
				Craft::log('Tried to restore session from a cookie, but it appears we the data in the cookie is invalid.', LogLevel::Error);
				$this->logout();
			}
		}
	}

	/**
	 * @return bool|void
	 */
	protected function beforeLogout()
	{
		// Fire an 'onBeforeLogout' event
		$this->onBeforeLogout(new Event($this));

		$cookie = craft()->request->getCookies()->itemAt($this->getStateKeyPrefix());

		// Grab the identity cookie information and make sure the data hasn't been tampered with.
		if ($cookie && !empty($cookie->value) && is_string($cookie->value) && ($data = craft()->security->validateData($cookie->value)) !== false)
		{
			// Grab the data
			$data = $this->getCookieValue('');

			if (is_array($data) && isset($data[0], $data[1], $data[2], $data[3], $data[4], $data[5]))
			{
				$loginName = $data[0];
				$uid = $data[2];

				// Clean up their row in the sessions table.
				$user = craft()->users->getUserByUsernameOrEmail($loginName);

				if ($user)
				{
					craft()->db->createCommand()->delete('sessions', 'userId=:userId AND uid=:uid', array('userId' => $user->id, 'uid' => $uid));
				}
			}
			else
			{
				Craft::log('During logout, tried to remove the row from the sessions table, but it appears the cookie data is invalid.', LogLevel::Error);
			}
		}

		$this->_userRow = null;

		return true;
	}

	/**
	 * Fires an 'onLogout' event after a user has been logged out.
	 */
	protected function afterLogout()
	{
		// Fire an 'onLogout' event
		$this->onLogout(new Event($this));
	}

	/**
	 * @param $loginName
	 * @param $uid
	 * @return bool
	 */
	private function _findSessionToken($loginName, $uid)
	{
		$result = craft()->db->createCommand()
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
		$user = craft()->users->getUserByUsernameOrEmail($loginName);
		craft()->db->createCommand()->update('sessions', array('token' => $newToken), 'token=:currentToken AND userId=:userId', array('currentToken' => $currentToken, 'userId' => $user->id));
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

		craft()->db->createCommand()->delete('sessions', 'dateUpdated < :pastTime', array('pastTime' => $pastTime));
	}

	/**
	 * @param $rememberMe
	 * @return int
	 */
	private function _getSessionDuration($rememberMe)
	{
		if ($rememberMe)
		{
			$duration = craft()->config->get('rememberedUserSessionDuration');
		}
		else
		{
			$duration = craft()->config->get('userSessionDuration');
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
			$seconds = null;
		}

		return $seconds;
	}

	/**
	 * @param $id
	 * @return int
	 */
	private function _getUserRow($id)
	{
		if (!isset($this->_userRow))
		{
			if ($id)
			{
				$userRow = craft()->db->createCommand()
				    ->select('*')
				    ->from('users')
				    ->where('id=:id', array(':id' => $id))
				    ->queryRow();

				if ($userRow)
				{
					$this->_userRow = $userRow;
				}
				else
				{
					$this->_userRow = false;
				}
			}
			else
			{
				$this->_userRow = false;
			}
		}

		return $this->_userRow;
	}

	/**
	 * Checks whether the the current request has a user agent string or IP address.
	 */
	private function _checkVitals()
	{
		if (craft()->config->get('requireUserAgentAndIpForSession'))
		{
			// Require a userAgent string and an IP address to help prevent direct socket connections from trying to login.
			if (!craft()->request->userAgent || !craft()->request->getIpAddress())
			{
				Craft::log('Someone tried to restore a session from a cookie without presenting an IP address or userAgent string.', LogLevel::Warning);
				$this->logout(true);
				$this->requireLogin();
			}
		}
	}

	/**
	 * Checks whether the current user agent string matches the user agent string saved in the identity cookie.
	 */
	private function _checkUserAgentString($currentUserAgent, $savedUserAgent)
	{
		if (craft()->config->get('requireMatchingUserAgentForSession'))
		{
			// If the saved userAgent differs from the current one, bail.
			if ($savedUserAgent !== $currentUserAgent)
			{
				Craft::log('Tried to restore session from the the identity cookie, but the saved userAgent ('.$savedUserAgent.') does not match the current userAgent ('.$currentUserAgent.').', LogLevel::Warning);
				$this->logout(true);
			}
		}
	}
}
