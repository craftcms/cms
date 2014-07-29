<?php
namespace Craft;

/**
 * Class ConfigFile
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.enums
 * @since     2.0
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
