<?php
namespace Craft;

/**
 * XCache implements a cache application module based on [xcache](http://xcache.lighttpd.net/).
 *
 * To use this application component, the XCache PHP extension must be loaded. Flush functionality will only work
 * correctly if "xcache.admin.enable_auth" is set to "Off" in php.ini.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.cache
 * @since     2.0
 */
class XCache extends \CXCache
{

}
