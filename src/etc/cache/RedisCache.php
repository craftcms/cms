<?php
namespace Craft;

/**
 * RedisCache implements a cache application component based on [redis](http://redis.io/).
 *
 * RedisCache needs to be configured with {@link hostname}, {@link port} and {@link database} of the server to connect
 * to. By default RedisCache assumes there is a redis server running on localhost at port 6379 and uses the database
 * number 0.
 *
 * RedisCache also supports [the AUTH command](http://redis.io/commands/auth) of redis. When the server needs
 * authentication, you can set the {@link password} property to authenticate with the server after connect.
 *
 * The minimum required redis version is 2.0.0.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.cache
 * @since     2.0
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
