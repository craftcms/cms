<?php
namespace Craft;

/**
 * Class CacheMethod
 *
 * @abstract
 * @package craft.app.enums
 */
abstract class CacheMethod extends BaseEnum
{
	const APC          = 'apc';
	const Db           = 'db';
	const EAccelerator = 'eaccelerator';
	const File         = 'file';
	const MemCache     = 'memcache';
	const Redis        = 'redis';
	const WinCache     = 'wincache';
	const XCache       = 'xcache';
	const ZendData     = 'zenddata';
}
