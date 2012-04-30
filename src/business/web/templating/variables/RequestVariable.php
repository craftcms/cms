<?php
namespace Blocks;

/**
 * Request functions
 */
class RequestVariable
{
	/**
	 * Returns whether this is a secure connection
	 */
	public function secure($class)
	{
		return b()->request->getIsSecureConnection();
	}

	/**
	 * Returns a value from POST data
	 */
	public function post($name)
	{
		return b()->request->getPost($name);
	}
}
