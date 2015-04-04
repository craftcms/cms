<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\cache;

/**
 * MemCache implements a cache application component based on [memcached](http://memcached.org/).
 *
 * MemCache can be configured with a list of memcache servers.  By default, MemCache assumes there is a memcache server
 * running on localhost at port 11211.
 *
 * Note, there is no security measure to protected data in memcache. All data in memcache can be accessed by any process
 * running in the system.
 *
 * MemCache can also be used with [memcached](http://pecl.php.net/package/memcached). To do so, set useMemcached to
 * be true.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MemCache extends \yii\caching\MemCache
{

}
