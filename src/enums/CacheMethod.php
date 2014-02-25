<?php
namespace Craft;

/**
 *
 */
class CacheMethod
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
