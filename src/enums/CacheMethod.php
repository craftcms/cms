<?php
namespace Craft;

/**
 * The CacheMethod class is an abstract class that defines all of the cache methods (except for template caching) that
 * are available in Craft.
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
abstract class CacheMethod extends BaseEnum
{
	// Constants
	// =========================================================================

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
