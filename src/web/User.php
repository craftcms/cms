<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web;

use craft\app\Craft;
use craft\app\dates\DateInterval;
use craft\app\enums\LogLevel;
use craft\app\events\Event;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\StringHelper;
use craft\app\models\Password     as PasswordModel;
use craft\app\models\User         as UserModel;
use craft\app\models\Username     as UsernameModel;
use craft\app\records\Session     as SessionRecord;
use craft\app\users\UserIdentity;
use craft\app\web\Application;
use craft\app\web\HttpCookie;
use yii\web\Cookie;

/**
 * The User service provides APIs for managing the user authentication status.
 *
 * An instance of the User service is globally accessible in Craft via [[Application::userSession `Craft::$app->getUser()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class User extends \yii\web\User
{
	// Properties
	// =========================================================================

	/**
	 * @var array The configuration of the username cookie.
	 * @see Cookie
	 */
	public $usernameCookie;

	/**
	 * Stores whether the request has requested to not extend the user's session.
	 *
	 * @var bool
	 */
	private $_dontExtendSession;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc \yii\base\object::__construct()
	 *
	 * @param array $config
	 */
	public function __construct($config = [])
	{
		// Set the configurable properties
		$configService = Craft::$app->config;
		$config['loginUrl']    = (array) $configService->getLoginPath();
		$config['authTimeout'] = $configService->getUserSessionDuration(false);

		// Should we skip auto login and cookie renewal?
		$this->_dontExtendSession = !$this->shouldExtendSession();
		$config['autoRenewCookie'] = !$this->_dontExtendSession;

		// Set the state-based property names
		$appId = Craft::$app->config->get('appId');
		$stateKeyPrefix = md5('Craft.'.get_class($this).($appId ? '.'.$appId : ''));
		$config['identityCookie']           = ['name' => $stateKeyPrefix.'_identity', 'httpOnly' => true];
		$config['usernameCookie']           = ['name' => $stateKeyPrefix.'_username', 'httpOnly' => true];
		$config['idParam']                  = $stateKeyPrefix.'__id';
		$config['authTimeoutParam']         = $stateKeyPrefix.'__expire';
		$config['absoluteAuthTimeoutParam'] = $stateKeyPrefix.'__absoluteExpire';
		$config['returnUrlParam']           = $stateKeyPrefix.'__returnUrl';

		parent::__construct($config);
	}

	// Authentication
	// -------------------------------------------------------------------------

	/**
	 * Sends a username cookie.
	 *
	 * This method is used after a user is logged in. It saves the logged-in user's username in a cookie,
	 * so that login forms can remember the initial Username value on login forms.
	 *
	 * @param UserModel $user
	 * @see afterLogin()
	 */
	public function sendUsernameCookie(UserModel $user)
	{
		$rememberUsernameDuration = Craft::$app->config->get('rememberUsernameDuration');

		if ($rememberUsernameDuration)
		{
			$cookie = new Cookie($this->usernameCookie);
			$cookie->value = $user->username;
			$cookie->expire = time() + DateTimeHelper::timeFormatToSeconds($rememberUsernameDuration);
			Craft::$app->getResponse()->getCookies()->add($cookie);
		}
		else
		{
			Craft::$app->getResponse()->getCookies()->remove(new Cookie($this->usernameCookie));
		}
	}

	/**
	 * @inheritDoc \yii\web\User::getReturnUrl()
	 *
	 * @param string|array $defaultUrl
	 * @return string
	 * @see loginRequired()
	 */
	public function getReturnUrl($defaultUrl = null)
	{
		$url = parent::getReturnUrl($defaultUrl);

		// Strip out any tags that may have gotten in there by accident
		// i.e. if there was a {siteUrl} tag in the Site URL setting, but no matching environment variable,
		// so they ended up on something like http://example.com/%7BsiteUrl%7D/some/path
		$url = str_replace(['{', '}'], ['', ''], $url);

		return $url;
	}

	/**
	 * Removes the stored return URL, if there is one.
	 *
	 * @return null
	 * @see getReturnUrl()
	 */
	public function removeReturnUrl()
	{
		Craft::$app->getSession()->remove($this->returnUrlParam);
	}

	// Authorization
	// -------------------------------------------------------------------------

	/**
	 * Returns whether the current user is an admin.
	 *
	 * @return bool Whether the current user is an admin.
	 */
	public function getIsAdmin()
	{
		$user = $this->getIdentity();
		return ($user && $user->admin);
	}

	/**
	 * Returns whether the current user has a given permission.
	 *
	 * @param string $permissionName The name of the permission.
	 *
	 * @return bool Whether the current user has the permission.
	 */
	public function checkPermission($permissionName)
	{
		$user = $this->getIdentity();
		return ($user && $user->can($permissionName));
	}

	// User Identity/Authentication
	// -------------------------------------------------------------------------

	/**
	 * Saves a new session record for a given user.
	 *
	 * @param UserModel $user
	 * @param string    $sessionToken
	 *
	 * @return string The session's UID.
	 */
	public function storeSessionToken(UserModel $user, $sessionToken)
	{
		$sessionRecord = new SessionRecord();
		$sessionRecord->userId = $user->id;
		$sessionRecord->token = $sessionToken;

		$sessionRecord->save();

		return $sessionRecord->uid;
	}

	/**
	 * Returns the username of the account that the browser was last logged in as.
	 *
	 * @return string|null
	 */
	public function getRememberedUsername()
	{
		return Craft::$app->getRequest()->getCookies()->getValue($this->usernameCookie['name']);
	}

	/**
	 * Sets a cookie on the browser.
	 *
	 * @param string $name     The name of the cookie.
	 * @param mixed  $data     The data that should be stored on the cookie.
	 * @param int    $duration The duration that the cookie should be stored for, in seconds.
	 *
	 * @return HttpCookie The cookie.
	 */
	public function saveCookie($name, $data, $duration = 0)
	{
		$name = $this->getStateKeyPrefix().$name;
		$cookie = new HttpCookie($name, '');
		$cookie->httpOnly = true;
		$cookie->expire = time() + $duration;

		if (Craft::$app->request->isSecureConnection())
		{
			$cookie->secure = true;
		}

		$cookie->value = Craft::$app->security->hashData(base64_encode(serialize($data)));
		Craft::$app->request->getCookies()->add($cookie->name, $cookie);

		return $cookie;
	}

	/**
	 * Deletes a cookie on the browser that was stored for the current application state.
	 *
	 * @param string $name The name of the cookie.
	 *
	 * @return null
	 */
	public function deleteStateCookie($name)
	{
		$name = $this->getStateKeyPrefix().$name;
		Craft::$app->request->deleteCookie($name);
	}

	/**
	 * Returns a cookie that was stored for the current application state.
	 *
	 * @param string $name The cookie name.
	 *
	 * @return HttpCookie|null The cookie, or `null` if it didn’t exist.
	 */
	public function getStateCookie($name)
	{
		$name = $this->getStateKeyPrefix().$name;
		return Craft::$app->request->getCookie($name);
	}

	/**
	 * Returns the value of a cookie by its name, ensuring that the data hasn’t been tampered with.
	 *
	 * @param HttpCookie|string $cookie The cookie, or the name of the cookie.
	 *
	 * @return mixed The value of the cookie if it exists and hasn’t been tampered with, or `null`.
	 */
	public function getStateCookieValue($cookie)
	{
		if (is_string($cookie))
		{
			$cookie = $this->getStateCookie($cookie);
		}

		if ($cookie && !empty($cookie->value) && ($data = Craft::$app->security->validateData($cookie->value)) !== false)
		{
			return @unserialize(base64_decode($data));
		}
	}

	/**
	 * Returns the current user identity cookie, if there is one.
	 *
	 * @return HttpCookie|null The user identity cookie.
	 */
	public function getIdentityCookie()
	{
		if (!isset($this->_identityCookie))
		{
			$cookie = $this->getStateCookie('');

			if ($cookie)
			{
				$this->_identityCookie = $cookie;
			}
			else
			{
				$this->_identityCookie = false;
			}
		}

		// Don't return false if that's what it is
		if ($this->_identityCookie)
		{
			return $this->_identityCookie;
		}
	}

	/**
	 * Returns the current user identity cookie’s value, if there is one.
	 *
	 * @param HttpCookie|null The user identity cookie, or `null` if you don’t have it on hand.
	 *
	 * @return array|null The user identity cookie’s data, or `null` if it didn’t exist.
	 */
	public function getIdentityCookieValue(HttpCookie $cookie = null)
	{
		if (!$cookie)
		{
			$cookie = $this->getIdentityCookie();
		}

		if (
			$cookie &&
			($data = $this->getStateCookieValue($cookie)) &&
			is_array($data) &&
			isset($data[0], $data[1], $data[2], $data[3], $data[4], $data[5])
		)
		{
			return $data;
		}
	}

	/**
	 * Returns whether the current user session was just restored from a cookie.
	 *
	 * This happens when a user with an active session closes their browser, and then re-opens it before their session
	 * is supposed to expire.
	 *
	 * @return bool Whether the current user session was just restored from a cookie.
	 */
	public function wasSessionRestoredFromCookie()
	{
		return $this->_sessionRestoredFromCookie;
	}

	/**
	 * Returns how many seconds are left in the current user session.
	 *
	 * @return int The seconds left in the session, or -1 if their session will expire when their HTTP session ends.
	 */
	public function getRemainingAuthTime()
	{
		// Are they logged in?
		if (!$this->getIsGuest())
		{
			// Is the site configured to have fixed user session durations?
			if ($this->authTimeout)
			{
				$expires = Craft::$app->getSession()->get($this->authTimeoutParam);
				$expires = $this->getState(static::AUTH_TIMEOUT_VAR);

				if ($expires !== null)
				{
					$currentTime = time();

					// Shouldn't be possible for $expires to be < $currentTime because updateAuthStatus() would have
					// logged them out, but what the hell.
					if ($expires > $currentTime)
					{
						return $expires - $currentTime;
					}
				}
			}
			else
			{
				// The session duration must have been empty (expire when the HTTP session ends)
				return -1;
			}
		}

		return 0;
	}

	/**
	 * Returns whether the request should extend the current session timeout or not.
	 *
	 * @return bool
	 */
	public function shouldExtendSession()
	{
		return !(
			Craft::$app->request->getIsGet() &&
			Craft::$app->request->isCpRequest() &&
			Craft::$app->request->getParam('dontExtendSession')
		);
	}

	// Events
	// -------------------------------------------------------------------------

	/**
	 * Fires an 'onBeforeLogin' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeLogin(Event $event)
	{
		$this->raiseEvent('onBeforeLogin', $event);
	}

	/**
	 * Fires an 'onLogin' event.
	 *
	 * @param Event $event
	 *
	 * @return null
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

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc \yii\web\User::afterLogin()
	 * @param IdentityInterface $identity
	 * @param boolean $cookieBased
	 * @param integer $duration
	 */
	protected function afterLogin($identity, $cookieBased, $duration)
	{
		// Save the username cookie
		$this->sendUsernameCookie($identity);

		parent::afterLogin($identity, $cookieBased, $duration);
	}

	/**
	 * Updates the authentication status according to [[authTimeout]].
	 *
	 * Based on the parts of [[\CWebUser::updateAuthStatus()]] that are relevant to Craft, but this version also
	 * enforces the [requireUserAgentAndIpForSession](http://buildwithcraft.com/docs/config-settings#requireUserAgentAndIpForSession)
	 * config setting, and it won't update the timeout state if the 'dontExtendSession' param is set.
	 *
	 * @return null
	 */
	protected function updateAuthStatus()
	{
		// Do we think that they're logged in?
		if (!$this->getIsGuest())
		{
			// Enforce the requireUserAgentAndIpForSession config setting
			// (helps prevent direct socket connections from trying to log in)
			if (Craft::$app->config->get('requireUserAgentAndIpForSession'))
			{
				if (!Craft::$app->request->getUserAgent() || !Craft::$app->request->getIpAddress())
				{
					Craft::log('Request didn’t meet the user agent and IP requirement for maintaining a user session.', LogLevel::Warning);
					$this->logout(true);
					return;
				}
			}

			// Is the site configured to have fixed user session durations?
			if ($this->authTimeout)
			{
				// Has the session expired?
				$expires = $this->getState(self::AUTH_TIMEOUT_VAR);

				if ($expires === null || $expires < time())
				{
					// Log 'em out
					$this->logout(false);
				}
				else if (!$this->_dontExtendSession)
				{
					// Update our record of when the session will expire
					$this->setState(self::AUTH_TIMEOUT_VAR, time() + $this->authTimeout);
				}
			}
		}
	}

	/**
	 * Renews the user’s identity cookie.
	 *
	 * This function extends the identity cookie's expiration time based on either the
	 * [userSessionDuration](http://buildwithcraft.com/docs/config-settings#userSessionDuration) or
	 * [rememberedUserSessionDuration](http://buildwithcraft.com/docs/config-settings#rememberedUserSessionDuration)
	 * config setting, depending on whether Remember Me was checked when they logged in.
	 *
	 * @return null
	 */
	protected function renewCookie()
	{
		$cookie = $this->getIdentityCookie();

		if ($cookie)
		{
			$data = $this->getIdentityCookieValue($cookie);

			if ($data && $this->_checkUserAgentString($data[4]))
			{
				// Extend the expiration time
				$expiration = time() + $this->authTimeout;
				$cookie->expire = $expiration;
				$cookie->httpOnly = true;
				Craft::$app->request->getCookies()->add($cookie->name, $cookie);
			}
		}
	}

	/**
	 * Restores a user session from the identity cookie.
	 *
	 * This method is used when automatic login ([[allowAutoLogin]]) is enabled. The user identity information is
	 * recovered from cookie.
	 *
	 * @todo Verify that it's totally necessary to re-save the cookie with a new user session token
	 * @return null
	 */
	protected function restoreFromCookie()
	{
		// If this request doesn't want to extend the user session, it is unfortunately not possible for us to restore
		// their user session from a cookie, because the cookie needs to be re-saved with a new user session token,
		// but we can't do that without also setting a new expiration date.
		if ($this->_dontExtendSession)
		{
			return;
		}

		// See if they have an existing identity cookie.
		$cookie = $this->getIdentityCookie();

		if ($cookie)
		{
			$data = $this->getIdentityCookieValue($cookie);

			if ($data && $this->_checkUserAgentString($data[4]))
			{
				$loginName = $data[0];
				$currentSessionToken = $data[1];
				$uid = $data[2];
				$rememberMe = $data[3];
				$states = $data[5];
				$currentUserAgent = Craft::$app->request->userAgent;
				$this->authTimeout = Craft::$app->config->getUserSessionDuration($rememberMe);

				// Get the hashed token from the db based on login name and uid.
				if (($sessionRow = $this->_findSessionToken($loginName, $uid)) !== false)
				{
					$dbHashedToken = $sessionRow['token'];
					$userId = $sessionRow['userId'];

					// Make sure the given session token matches what we have in the db.
					$checkHashedToken= Craft::$app->security->hashData(base64_encode(serialize($currentSessionToken)));

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
								$hashedNewToken = Craft::$app->security->hashData(base64_encode(serialize($newSessionToken)));
								$this->_updateSessionToken($loginName, $dbHashedToken, $hashedNewToken);

								// While we're let's clean up stale sessions.
								$this->_cleanStaleSessions();

								// Save updated info back to identity cookie.
								$data = [
									$this->getName(),
									$newSessionToken,
									$uid,
									($rememberMe ? 1 : 0),
									$currentUserAgent,
									$states,
								];

								$this->_identityCookie = $this->saveCookie('', $data, $this->authTimeout);
								$this->_sessionRestoredFromCookie = true;
								$this->_userRow = null;
							}

							$this->afterLogin(true);
						}
					}
					else
					{
						Craft::log('Tried to restore session from a cookie, but the given hashed database token value does not appear to belong to the given login name. Hashed db value: '.$dbHashedToken.' and loginName: '.$loginName.'.', LogLevel::Warning);
						// Forcing logout here clears the identity cookie helping to prevent session fixation.
						$this->logout(true);
					}
				}
				else
				{
					Craft::log('Tried to restore session from a cookie, but the given login name does not match the given uid. UID: '.$uid.' and loginName: '.$loginName.'.', LogLevel::Warning);
					// Forcing logout here clears the identity cookie helping to prevent session fixation.
					$this->logout(true);
				}
			}
			else
			{
				Craft::log('Tried to restore session from a cookie, but it appears we the data in the cookie is invalid.', LogLevel::Warning);
				$this->logout(true);
			}
		}
	}

	/**
	 * Called after a user is logged in.
	 *
	 * @return null
	 */
	protected function afterLogin()
	{
		if ($this->authTimeout)
		{
			$this->setState(static::AUTH_TIMEOUT_VAR, time()+$this->authTimeout);
		}
	}

	/**
	 * Called before a user is logged out.
	 *
	 * @return bool So true.
	 */
	protected function beforeLogout()
	{
		// Fire an 'onBeforeLogout' event
		$event = new Event($this, [
			'user'      => $this->getIdentity(),
		]);

		$this->onBeforeLogout($event);

		// Is the event is giving us the go-ahead?
		if ($event->performAction)
		{
			$cookie = $this->getIdentityCookie();

			if ($cookie)
			{
				$data = $this->getIdentityCookieValue($cookie);

				if ($data)
				{
					$loginName = $data[0];
					$uid = $data[2];

					// Clean up their row in the sessions table.
					$user = Craft::$app->users->getUserByUsernameOrEmail($loginName);

					if ($user)
					{
						Craft::$app->db->createCommand()->delete('sessions', 'userId=:userId AND uid=:uid', ['userId' => $user->id, 'uid' => $uid]);
					}
				}
				else
				{
					Craft::log('During logout, tried to remove the row from the sessions table, but it appears the cookie data is invalid.', LogLevel::Warning);
				}
			}

			$this->_userRow = null;

			return true;
		}

		return false;
	}

	/**
	 * Fires an 'onLogout' event after a user has been logged out.
	 *
	 * @return null
	 */
	protected function afterLogout()
	{
		// Clear the stored user model
		$this->_userModel = null;

		// Delete the identity cookie, if there is one
		$this->deleteStateCookie('');

		// Fire an 'onLogout' event
		$this->onLogout(new Event($this));
	}

	// Private Methods
	// =========================================================================

	/**
	 * @param string $loginName
	 * @param string $uid
	 *
	 * @return bool
	 */
	private function _findSessionToken($loginName, $uid)
	{
		$result = Craft::$app->db->createCommand()
			->select('s.token, s.userId')
			->from('sessions s')
			->join('users u', 's.userId = u.id')
			->where('(u.username=:username OR u.email=:email) AND s.uid=:uid', [':username' => $loginName, ':email' => $loginName, 'uid' => $uid])
			->queryRow();

		if (is_array($result) && count($result) > 0)
		{
			return $result;
		}

		return false;
	}

	/**
	 * @param string $loginName
	 * @param string $currentToken
	 * @param string $newToken
	 *
	 * @return int
	 */
	private function _updateSessionToken($loginName, $currentToken, $newToken)
	{
		$user = Craft::$app->users->getUserByUsernameOrEmail($loginName);

		Craft::$app->db->createCommand()->update('sessions', ['token' => $newToken], 'token=:currentToken AND userId=:userId', ['currentToken' => $currentToken, 'userId' => $user->id]);
	}

	/**
	 * @return null
	 */
	private function _cleanStaleSessions()
	{
		$interval = new DateInterval('P3M');
		$expire = DateTimeHelper::currentUTCDateTime();
		$pastTimeStamp = $expire->sub($interval)->getTimestamp();
		$pastTime = DateTimeHelper::formatTimeForDb($pastTimeStamp);

		Craft::$app->db->createCommand()->delete('sessions', 'dateUpdated < :pastTime', ['pastTime' => $pastTime]);
	}

	/**
	 * @param int $id
	 *
	 * @return int
	 */
	private function _getUserRow($id)
	{
		if (!isset($this->_userRow))
		{
			if ($id)
			{
				$userRow = Craft::$app->db->createCommand()
					->select('*')
					->from('users')
					->where('id=:id', [':id' => $id])
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
	 * Enforces the requireMatchingUserAgentForSession config setting by verifying that the user agent string on the
	 * identity cookie matches the current request's user agent string.
	 *
	 * If they don't match, a warning will be logged and the user will be logged out.
	 *
	 * @param string $userAgent  The user agent string stored in the cookie.
	 * @param bool   $autoLogout Whether the user should be logged out if the user agents don't match.
	 *
	 * @return bool Whether the user agent strings matched.
	 */
	private function _checkUserAgentString($userAgent, $autoLogout = true)
	{
		if (Craft::$app->config->get('requireMatchingUserAgentForSession'))
		{
			$currentUserAgent = Craft::$app->request->getUserAgent();

			if ($userAgent !== $currentUserAgent)
			{
				Craft::log('Tried to restore session from the the identity cookie, but the saved user agent ('.$userAgent.') does not match the current userAgent ('.$currentUserAgent.').', LogLevel::Warning);

				if ($autoLogout)
				{
					$this->logout(true);
				}

				return false;
			}
		}

		return true;
	}
}
