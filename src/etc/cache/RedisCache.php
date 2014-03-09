<?php
namespace Craft;

/**
 * RedisCache implements a cache application component based on {@link http://redis.io/ redis}.
 *
 * RedisCache needs to be configured with {@link hostname}, {@link port} and {@link database} of the server
 * to connect to. By default RedisCache assumes there is a redis server running on localhost at
 * port 6379 and uses the database number 0.
 *
 * RedisCache also supports {@link http://redis.io/commands/auth the AUTH command} of redis.
 * When the server needs authentication, you can set the {@link password} property to
 * authenticate with the server after connect.
 *
 * The minimum required redis version is 2.0.0.
 */
class RedisCache extends \CRedisCache
{

}
