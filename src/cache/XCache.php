<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\cache;

/**
 * XCache implements a cache application module based on [xcache](http://xcache.lighttpd.net/).
 *
 * To use this application component, the XCache PHP extension must be loaded. Flush functionality will only work
 * correctly if "xcache.admin.enable_auth" is set to "Off" in php.ini.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class XCache extends \yii\caching\XCache
{

}
