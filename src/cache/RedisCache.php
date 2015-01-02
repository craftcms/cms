<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\cache;

/**
 * RedisCache implements a cache application component based on [redis](http://redis.io/).
 *
 * RedisCache needs to be configured with [[hostname]], [[port]] and [[database]] of the server to connect
 * to. By default RedisCache assumes there is a redis server running on localhost at port 6379 and uses the database
 * number 0.
 *
 * RedisCache also supports [the AUTH command](http://redis.io/commands/auth) of redis. When the server needs
 * authentication, you can set the [[password]] property to authenticate with the server after connect.
 *
 * The minimum required redis version is 2.0.0.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RedisCache extends \CRedisCache
{
	// Protected Methods
	// =========================================================================

	/**
	 * Establishes a connection to the redis server.  It does nothing if the connection has already been established.
	 *
	 * Craft overrides this from Yii because the parent is explicitly checking for null in the password.
	 *
	 * @throws \CException
	 * @return null
	 */
	protected function connect()
	{
		if ($this->password === '')
		{
			$this->password = null;
		}

		parent::connect();
	}
}
