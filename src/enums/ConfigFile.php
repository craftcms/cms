<?php
namespace Craft;

/**
 * Class ConfigFile
 *
 * @package craft.app.enums
 */
abstract class ConfigFile extends BaseEnum
{
	const FileCache    = 'filecache';
	const General      = 'general';
	const Db           = 'db';
	const DbCache      = 'dbcache';
	const Memcache     = 'memcache';
	const RedisCache   = 'rediscache';
}
