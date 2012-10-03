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
		$app = blx();
		$request = $app->getRequest();

		if (!$request->isAjaxRequest())
		{
			if ($request->getPathInfo() !== '')
			{
				$this->setReturnUrl($request->getUrl());
			}
		}
		elseif (isset($this->loginRequiredAjaxResponse))
		{
			echo $this->loginRequiredAjaxResponse;
			blx()->end();
		}

		if (($url = $this->loginUrl) !== null)
		{
			if (is_array($url))
			{
				$route = isset($url[0]) ? $url[0] : $app->defaultController;
				$url = $app->createUrl($route, array_splice($url, 1));
			}

			$request->redirect($url);
		}
		else
		{
			throw new HttpException(403, Blocks::t('Login is required.'));
		}
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
				Blocks::log('During login, could not find a user with an id of '.$id.' or the user\'s authSessionToken: '.$authSessionToken.' did not match the one we have on record.));
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
		return $this->getIsGuest();
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
				$rememberUsernameDuration = blx()->config->rememberUsernameDuration;
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
					$duration = blx()->config->rememberedUserSessionDuration;
				}
				else
				{
					$duration = blx()->config->userSessionDuration;
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
		return (isset(blx()->request->cookies['username'])) ? blx()->request->cookies['username']->value : null;
	}
}
