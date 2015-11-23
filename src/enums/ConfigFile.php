<?php
namespace Craft;

/**
 * The ConfigFile class is an abstract class that defines all of the config file options that are available in Craft.
 *
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.enums
 * @since     2.0
 */
abstract class ConfigFile extends BaseEnum
{
	// Constants
	// =========================================================================

	const FileCache    = 'filecache';
	const General      = 'general';
	const Db           = 'db';
	const DbCache      = 'dbcache';
	const Memcache     = 'memcache';
	const RedisCache   = 'rediscache';
}
