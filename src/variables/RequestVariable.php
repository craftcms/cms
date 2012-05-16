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
		return b()->request->getIsSecureConnection();
	}

	/**
	 * Returns a value from POST data.
	 * @param $name
	 * @return mixed
	 */
	public function post($name)
	{
		return b()->request->getPost($name);
	}

	/**
	 * @return string
	 */
	public function servername()
	{
		return b()->request->getServerName();
	}
}
