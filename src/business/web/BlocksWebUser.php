<?php

/**
 *
 */
class BlocksWebUser extends CWebUser
{
	public $authTimeout;

	/**
	 *
	 */
	public function init()
	{
		$this->authTimeout = ConfigHelper::getTimeInSeconds('sessionTimeout');
		parent::init();
	}

	/**
	 * @param $id
	 * @param $states
	 * @param $fromCookie
	 * @return bool
	 */
	protected function beforeLogin($id, $states, $fromCookie)
	{
		$authToken = '1';

		if (isset($states['authToken']))
			$authToken = $states['authToken'];

		$user = Users::model()->findByPk($id);

		if ($user === null || $user->authToken !== $authToken)
		{
			Blocks::log('During login, could not find a user with an id of '.$id.' or the user\'s authToken: '.$authToken.' did not match the one we have on record: '.$user->authToken.'.');
			return false;
		}

		return true;
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
		$app = Blocks::app();
		$cookie = $this->createIdentityCookie($this->getStateKeyPrefix());
		$cookie->expire = time() + $duration;
		$cookie->httpOnly = true;

		$data = array(
			$this->getId(),
			$this->getName(),
			$duration,
			$this->saveIdentityStates(),
		);

		$cookie->value = $app->getSecurityManager()->hashData(serialize($data));
		$app->getRequest()->getCookies()->add($cookie->name, $cookie);
	}
}
