<?php
namespace Blocks;

/**
 * Config service
 */
class ConfigService extends BaseApplicationComponent
{
	private $_tablePrefix;

	/**
	 * Adds config items to the mix of possible magic getter properties
	 *
	 * @param $name
	 * @return mixed|null
	 */
	function __get($name)
	{
		if (parent::__isset($name))
			return parent::__get($name);
		else
			return $this->getItem($name);
	}

	/**
	 * Get a config item
	 *
	 * @param      $item
	 * @param null $default
	 * @return null
	 */
	public function getItem($item, $default = null)
	{
		if (isset(blx()->params['blocksConfig'][$item]))
			return blx()->params['blocksConfig'][$item];

		return $default;
	}

	/**
	 * Get a DB config item
	 *
	 * @param      $item
	 * @param null $default
	 * @return null
	 */
	public function getDbItem($item, $default = null)
	{
		if (isset(blx()->params['dbConfig'][$item]))
			return blx()->params['dbConfig'][$item];

		return $default;
	}

	/**
	 * Returns the user session duration in seconds.
	 *
	 * @param bool $rememberMe
	 * @return int
	 */
	public function getUserSessionDuration($rememberMe = false)
	{
		if ($rememberMe)
		{
			$item = 'rememberedUserSessionDuration';
		}
		else
		{
			$item = 'userSessionDuration';
		}

		return ConfigHelper::getTimeInSeconds($this->getItem($item));
	}

	/**
	 * Returns the user session duration in seconds.
	 *
	 * @return int
	 */
	public function getRememberUsernameDuration()
	{
		return ConfigHelper::getTimeInSeconds($this->getItem('rememberUsernameDuration'));
	}

	/**
	 * Returns the failed password window duration in seconds.
	 *
	 * @return int
	 */
	public function getInvalidLoginWindowDuration()
	{
		return ConfigHelper::getTimeInSeconds($this->getItem('invalidLoginWindowDuration'));
	}

	/**
	 * Returns the cooldown duration in seconds.
	 *
	 * @return int
	 */
	public function getCooldownDuration()
	{
		return ConfigHelper::getTimeInSeconds($this->getItem('cooldownDuration'));
	}

	/**
	 * Returns the verification code life span in seconds.
	 *
	 * @return int
	 */
	public function getVerificationCodeLifespan()
	{
		return ConfigHelper::getTimeInSeconds($this->getItem('verificationCodeLifespan'));
	}
}
