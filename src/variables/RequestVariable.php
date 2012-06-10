<?php
namespace Blocks;

/**
 * Request functions
 */
class RequestVariable
{
	/**
	 * Returns whether this is a secure connection.
	 * @return bool
	 */
	public function secure()
	{
		return blx()->request->getIsSecureConnection();
	}

	/**
	 * Returns a value from POST data.
	 * @param $name
	 * @return mixed
	 */
	public function post($name)
	{
		return blx()->request->getPost($name);
	}

	/**
	 * @return string
	 */
	public function servername()
	{
		return blx()->request->getServerName();
	}
}
