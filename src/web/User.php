<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web;

use Craft;
use craft\app\dates\DateInterval;
use craft\app\helpers\DateTimeHelper;
use craft\app\elements\User as UserElement;
use craft\app\helpers\UrlHelper;
use yii\web\Cookie;
use yii\web\IdentityInterface;

/**
 * The User service provides APIs for managing the user authentication status.
 *
 * An instance of the User service is globally accessible in Craft via [[Application::userSession `Craft::$app->getUser()`]].
 *
 * @property UserElement|null $identity The logged-in user.
 *
 * @method UserElement|null getIdentity() Returns the logged-in user.
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

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function __construct($config = [])
	{
		// Set the configurable properties
		$configService = Craft::$app->getConfig();
		$config['loginUrl']    = UrlHelper::getUrl($configService->getLoginPath());
		$config['authTimeout'] = $configService->getUserSessionDuration(false);

		// Set the state-based property names
		$appId = Craft::$app->getConfig()->get('appId');
		$stateKeyPrefix = md5('Craft.'.get_class($this).($appId ? '.'.$appId : ''));
		$config['identityCookie']           = Craft::getCookieConfig(['name' => $stateKeyPrefix.'_identity']);
		$config['usernameCookie']           = Craft::getCookieConfig(['name' => $stateKeyPrefix.'_username']);
		$config['idParam']                  = $stateKeyPrefix.'__id';
		$config['authTimeoutParam']         = $stateKeyPrefix.'__expire';
		$config['absoluteAuthTimeoutParam'] = $stateKeyPrefix.'__absoluteExpire';
		$config['returnUrlParam']           = $stateKeyPrefix.'__returnUrl';

		parent::__construct($config);
	}

	/**
	 * Initializes the application component.
	 */
	public function init()
	{
		parent::init();
		$this->_setStaticIdentity();
	}

	// Authentication
	// -------------------------------------------------------------------------

	/**
	 * Sends a username cookie.
	 *
	 * This method is used after a user is logged in. It saves the logged-in user's username in a cookie,
	 * so that login forms can remember the initial Username value on login forms.
	 *
	 * @param UserElement $user
	 * @see afterLogin()
	 */
	public function sendUsernameCookie(UserElement $user)
	{
		$rememberUsernameDuration = Craft::$app->getConfig()->get('rememberUsernameDuration');

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
	 * @inheritdoc
	 */
	public function getReturnUrl($defaultUrl = null)
	{
		$url = parent::getReturnUrl($defaultUrl);

		// Strip out any tags that may have gotten in there by accident
		// i.e. if there was a {siteUrl} tag in the Site URL setting, but no matching environment variable,
		// so they ended up on something like http://example.com/%7BsiteUrl%7D/some/path
		$url = str_replace(['{', '}'], '', $url);

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
	 * Returns how many seconds are left in the current user session.
	 *
	 * @return int The seconds left in the session, or -1 if their session will expire when their HTTP session ends.
	 */
	public function getRemainingSessionTime()
	{
		// Are they logged in?
		if (!$this->getIsGuest())
		{
			if ($this->authTimeout === null)
			{
				// The session duration must have been empty (expire when the HTTP session ends)
				return -1;
			}

			$expire = Craft::$app->getSession()->get($this->authTimeoutParam);
			$time = time();

			if ($expire !== null && $expire > $time)
			{
				return $expire - $time;
			}
		}

		return 0;
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

	// Misc
	// -------------------------------------------------------------------------

	/**
	 * Saves the logged-in user’s Debug toolbar preferences to the session.
	 */
	public function saveDebugPreferencesToSession()
	{
		$identity = $this->getIdentity();
		$session = Craft::$app->getSession();

		$this->destroyDebugPreferencesInSession();

		if ($identity->admin && $identity->getPreference('enableDebugToolbarForSite'))
		{
			$session->set('enableDebugToolbarForSite', true);
		}

		if ($identity->admin && $identity->getPreference('enableDebugToolbarForCp'))
		{
			$session->set('enableDebugToolbarForCp', true);
		}
	}

	/**
	 * Removes the debug preferences from the session.
	 */
	public function destroyDebugPreferencesInSession()
	{
		$session = Craft::$app->getSession();
		$session->remove('enableDebugToolbarForSite');
		$session->remove('enableDebugToolbarForCp');
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected function beforeLogin($identity, $cookieBased, $duration)
	{
		// Only allow the login if the request meets our user agent and IP requirements
		if ($this->_validateUserAgentAndIp())
		{
			return parent::beforeLogin($identity, $cookieBased, $duration);
		}

		return false;
	}

	/**
	 * @inheritdoc
	 */
	protected function afterLogin($identity, $cookieBased, $duration)
	{
		/** @var \craft\app\elements\User $identity */
		// Save the username cookie
		$this->sendUsernameCookie($identity);

		// Delete any stale session rows
		$this->_deleteStaleSessions();

		// Save the Debug preferences to the session
		$this->saveDebugPreferencesToSession();

		parent::afterLogin($identity, $cookieBased, $duration);
	}

	/**
	 * @inheritdoc
	 */
	protected function renewAuthStatus()
	{
		// Only renew if the request meets our user agent and IP requirements
		if ($this->_validateUserAgentAndIp())
		{
			parent::renewAuthStatus();
		}
	}

	/**
     * @inheritdoc
     */
    protected function renewIdentityCookie()
    {
    	// Prevent the session row from getting stale
    	$this->_updateSessionToken();

    	parent::renewIdentityCookie();
    }

	/**
	 * @inheritdoc
	 */
	protected function afterLogout($identity)
	{
		// Delete the impersonation session, if there is one
		Craft::$app->getSession()->remove(UserElement::IMPERSONATE_KEY);

		// Delete the session row
		$value = Craft::$app->getRequest()->getCookies()->getValue($this->identityCookie['name']);

		if ($value !== null)
		{
			$data = json_decode($value, true);

			if (count($data) === 4 && isset($data[0], $data[1], $data[2], $data[3]))
			{
				$authKey = $data[1];

				Craft::$app->getDb()->createCommand()->delete('{{%sessions}}', ['and', 'userId=:userId', 'uid=:uid'], [
					'userId' => $identity->id,
					'token'  => $authKey
				])->execute();
			}
		}

		$this->destroyDebugPreferencesInSession();

		parent::afterLogout($identity);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Statically sets the identity in the event that this request should not be extending it.
	 *
	 * @return bool
	 */
	private function _setStaticIdentity()
	{
		// Is the request specifying that the session should not be extended?
		$request = Craft::$app->getRequest();

		if (
			$request->getIsGet() &&
			$request->getIsCpRequest() &&
			$request->getParam('dontExtendSession')
		)
		{
			// Prevent getIdentity() from automatically fetching the identity from session
			$this->enableSession = false;

			// Code adapted from \yii\web\User::renewAuthStatus()
			$session = Craft::$app->getSession();
			$id = $session->getHasSessionId() || $session->getIsActive() ? $session->get($this->idParam) : null;

			if ($id === null)
			{
				$identity = null;
			}
			else
			{
				/* @var $class IdentityInterface */
				$class = $this->identityClass;
				$identity = $class::findIdentity($id);
			}

			$this->setIdentity($identity);
		}
	}

	/**
	 * Validates that the request has a user agent and IP associated with it,
	 * if the 'requireUserAgentAndIpForSession' config setting is enabled.
	 *
	 * @return boolean
	 */
	private function _validateUserAgentAndIp()
	{
		if (Craft::$app->getConfig()->get('requireUserAgentAndIpForSession'))
		{
			$request = Craft::$app->getRequest();

			if ($request->getUserAgent() === null || $request->getUserIP() === null)
			{
				Craft::warning('Request didn’t meet the user agent and IP requirement for maintaining a user session.', __METHOD__);
				return false;
			}
		}

		return true;
	}

	/**
	 * Updates the dateUpdated column on the session's row, so it doesn't get stale.
	 *
	 * @see _deleteStaleSessions()
	 */
	private function _updateSessionToken()
	{
		// Extract the current session token's UID from the identity cookie
		$cookieValue = Craft::$app->getRequest()->getCookies()->getValue($this->identityCookie['name']);

		if ($cookieValue !== null)
		{
			$identityData = json_decode($cookieValue, true);

			if (count($identityData) === 3 && isset($identityData[0], $identityData[1], $identityData[2]))
			{
				$authData = UserElement::getAuthData($identityData[1]);

				if ($authData)
				{
					$tokenUid = $authData[1];

					// Now update the associated session row's dateUpdated column
					Craft::$app->getDb()->createCommand()->update('{{%sessions}}',
						[],
						['and', 'userId=:userId', 'uid=:uid'],
						[':userId' => $this->getId(), ':uid' => $tokenUid]
					)->execute();
				}
			}
		}
	}

	/**
	 * Deletes any session rows that have gone stale.
	 */
	private function _deleteStaleSessions()
	{
		$interval = new DateInterval('P3M');
		$expire = DateTimeHelper::currentUTCDateTime();
		$pastTimeStamp = $expire->sub($interval)->getTimestamp();
		$pastTime = DateTimeHelper::formatTimeForDb($pastTimeStamp);
		Craft::$app->getDb()->createCommand()->delete('{{%sessions}}', 'dateUpdated < :pastTime', ['pastTime' => $pastTime])->execute();
	}
}
