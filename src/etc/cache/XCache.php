<?php
namespace Craft;

/**
 * XCache implements a cache application module based on {@link http://xcache.lighttpd.net/ xcache}.
 *
 * To use this application component, the XCache PHP extension must be loaded.
 * Flush functionality will only work correctly if "xcache.admin.enable_auth" is set to "Off" in php.ini.
 *
 * @package craft.app.etc.cache
 */
class XCache extends \CXCache
{

}
